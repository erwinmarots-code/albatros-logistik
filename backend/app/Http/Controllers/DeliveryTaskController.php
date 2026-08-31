<?php

namespace App\Http\Controllers;

use App\Models\DeliveryTask;
use App\Models\ShippingProject;
use App\Models\Vehicle;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Traits\BranchScopeTrait;
use Illuminate\Support\Facades\Gate;
use App\Exports\DeliveryTasksExport;
use Maatwebsite\Excel\Facades\Excel;

class DeliveryTaskController extends Controller
{
    use BranchScopeTrait;

    /**
     * Display a listing of delivery tasks.
     */
    public function index(Request $request)
    {
        try {
            $query = DeliveryTask::with(['project', 'vehicle', 'driver', 'client', 'creator']);
            $this->applyBranchFilter($query);

            if ($request->has('available_for_expense') && $request->available_for_expense == 'true') {
                $query->whereDoesntHave('fuelExpenses');
            }

            if ($request->has('project_id')) {
                $query->where('project_id', $request->project_id);
            }
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('no_resi', 'LIKE', "%{$search}%")
                      ->orWhere('notes', 'LIKE', "%{$search}%");
                });
            }

            $tasks = $query->orderBy('id', 'desc')->get();
            return response()->json(['data' => $tasks]);
        } catch (\Exception $e) {
            Log::error('Error fetching delivery tasks: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memuat data tugas kirim'], 500);
        }
    }

    /**
     * Store a newly created delivery task.
     */
    public function store(Request $request)
    {
        try {
            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'project_id'    => 'required|exists:shipping_projects,id',
                'vehicle_id'    => 'required|exists:vehicles,id',
                'driver_id'     => 'required|exists:drivers,id',
                'client_id'     => 'required|exists:clients,id',
                'no_resi'       => 'nullable|string|max:50',
                'delivery_date' => 'required|date',
                'status'        => 'nullable|in:draft,assigned,in_progress,completed,cancelled',
                'receiver_name' => 'required|string|max:100',
                'receiver_address' => 'required|string',
                'receiver_phone'   => 'required|string|max:20',
                'goods_description' => 'nullable|string',
                'weight_kg'         => 'nullable|numeric|min:0',
                'collie'            => 'nullable|integer|min:0',
                'notes'             => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Cek kendaraan apakah sedang memiliki tugas aktif
            $vehicle = Vehicle::find($request->vehicle_id);
            if ($vehicle && $vehicle->hasActiveTask()) {
                return response()->json([
                    'message' => 'Kendaraan ini sedang memiliki tugas kirim aktif (belum selesai/dibatalkan).'
                ], 422);
            }

            // Cek driver apakah sedang memiliki tugas aktif
            $driver = Driver::find($request->driver_id);
            if ($driver && $driver->hasActiveTask()) {
                return response()->json([
                    'message' => 'Driver ini sedang memiliki tugas kirim aktif (belum selesai/dibatalkan).'
                ], 422);
            }

            $data = $request->all();
            $data['created_by'] = $user->id;

            // 🔥 Mapping delivery_date → tanggal (karena kolom di database adalah 'tanggal')
            if (isset($data['delivery_date'])) {
                $data['tanggal'] = $data['delivery_date'];
                unset($data['delivery_date']);
            } else {
                $data['tanggal'] = now();
            }

            // Auto generate no_resi jika kosong
            if (empty($data['no_resi'])) {
                $data['no_resi'] = 'TASK-' . strtoupper(uniqid());
            }

            // Set branch_id
            if ($user->role !== 'super_admin') {
                $data['branch_id'] = $user->branch_id;
            } else {
                $project = ShippingProject::find($data['project_id']);
                $data['branch_id'] = $project->branch_id ?? $user->branch_id ?? 1;
            }

            $task = DeliveryTask::create($data);
            $task->load(['project', 'vehicle', 'driver', 'client', 'creator']);

            return response()->json([
                'message' => 'Tugas kirim berhasil ditambahkan',
                'data'    => $task
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating delivery task: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menambahkan tugas kirim: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified delivery task.
     */
    public function show($id)
    {
        try {
            $task = DeliveryTask::with(['project', 'vehicle', 'driver', 'client', 'creator'])
                ->findOrFail($id);

            $user = auth()->user();
            if ($user->role !== 'super_admin' && $task->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke tugas ini'], 403);
            }

            return response()->json(['data' => $task]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Tugas kirim tidak ditemukan'], 404);
        }
    }

    /**
     * Update the specified delivery task.
     */
    public function update(Request $request, $id)
    {
        try {
            $user = auth()->user();
            $task = DeliveryTask::findOrFail($id);

            if ($user->role !== 'super_admin' && $task->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke tugas ini'], 403);
            }

            $validator = Validator::make($request->all(), [
                'project_id'    => 'sometimes|exists:shipping_projects,id',
                'vehicle_id'    => 'sometimes|exists:vehicles,id',
                'driver_id'     => 'sometimes|exists:drivers,id',
                'client_id'     => 'sometimes|exists:clients,id',
                'delivery_date' => 'sometimes|date',
                'status'        => 'nullable|in:draft,assigned,in_progress,completed,cancelled',
                'notes'         => 'nullable|string',
                'no_resi'       => 'nullable|string|max:50',
                'receiver_name' => 'nullable|string|max:100',
                'receiver_address' => 'nullable|string',
                'receiver_phone'   => 'nullable|string|max:20',
                'goods_description' => 'nullable|string',
                'weight_kg'         => 'nullable|numeric|min:0',
                'collie'            => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Jika vehicle_id diubah, cek validasi
            if ($request->has('vehicle_id') && $request->vehicle_id != $task->vehicle_id) {
                $vehicle = Vehicle::find($request->vehicle_id);
                if ($vehicle && $vehicle->hasActiveTask()) {
                    return response()->json([
                        'message' => 'Kendaraan ini sedang memiliki tugas kirim aktif (belum selesai/dibatalkan).'
                    ], 422);
                }
            }

            // Jika driver_id diubah, cek validasi
            if ($request->has('driver_id') && $request->driver_id != $task->driver_id) {
                $driver = Driver::find($request->driver_id);
                if ($driver && $driver->hasActiveTask()) {
                    return response()->json([
                        'message' => 'Driver ini sedang memiliki tugas kirim aktif (belum selesai/dibatalkan).'
                    ], 422);
                }
            }

            $data = $request->all();

            // 🔥 Mapping delivery_date → tanggal
            if ($request->has('delivery_date')) {
                $data['tanggal'] = $request->delivery_date;
                unset($data['delivery_date']);
            }

            $task->update($data);
            $task->load(['project', 'vehicle', 'driver', 'client', 'creator']);

            return response()->json([
                'message' => 'Tugas kirim berhasil diperbarui',
                'data'    => $task
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating delivery task: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui tugas kirim'], 500);
        }
    }

    /**
     * Update only the status of a delivery task.
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $user = auth()->user();
            $task = DeliveryTask::findOrFail($id);

            if ($user->role !== 'super_admin' && $task->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke tugas ini'], 403);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:draft,assigned,in_progress,completed,cancelled'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $newStatus = $request->status;
            $currentStatus = $task->status;

            if ($currentStatus === $newStatus) {
                return response()->json(['message' => 'Status sudah sama'], 400);
            }

            $statusFlow = ['draft', 'assigned', 'in_progress', 'completed'];
            $currentIndex = array_search($currentStatus, $statusFlow);
            $newIndex = array_search($newStatus, $statusFlow);

            if ($newStatus === 'cancelled') {
                if ($currentStatus === 'completed') {
                    return response()->json(['message' => 'Tugas yang sudah selesai tidak dapat dibatalkan'], 400);
                }
                $task->status = 'cancelled';
                $task->save();
                return response()->json(['message' => 'Status berhasil diubah menjadi cancelled', 'data' => $task]);
            }

            if ($currentIndex === false) {
                return response()->json(['message' => 'Status saat ini tidak dapat diubah lebih lanjut'], 400);
            }

            if ($newIndex !== $currentIndex + 1) {
                return response()->json([
                    'message' => 'Status harus diubah secara berurutan. Status saat ini: ' . $currentStatus
                ], 422);
            }

            $task->status = $newStatus;
            $task->save();

            return response()->json(['message' => 'Status berhasil diubah', 'data' => $task]);
        } catch (\Exception $e) {
            Log::error('Error updating task status: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal mengubah status'], 500);
        }
    }

    /**
     * Remove the specified delivery task.
     */
    public function destroy($id)
    {
        try {
            $user = auth()->user();
            $task = DeliveryTask::findOrFail($id);

            if ($user->role !== 'super_admin' && $task->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke tugas ini'], 403);
            }

            $task->delete();
            return response()->json(['message' => 'Tugas kirim berhasil dihapus']);
        } catch (\Exception $e) {
            Log::error('Error deleting delivery task: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menghapus tugas kirim'], 500);
        }
    }

    /**
     * Export delivery tasks to Excel.
     */
    public function export(Request $request)
    {
        try {
            if (!Gate::allows('exportModule', 'delivery-tasks')) {
                return response()->json(['message' => 'Anda tidak memiliki izin untuk mengekspor data ini.'], 403);
            }

            $query = DeliveryTask::with(['project', 'vehicle', 'driver', 'client']);
            $this->applyBranchFilter($query);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('no_resi', 'LIKE', "%{$search}%")
                      ->orWhereHas('project', function ($sub) use ($search) {
                          $sub->where('no_po', 'LIKE', "%{$search}%");
                      });
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('project_id')) {
                $query->where('project_id', $request->project_id);
            }

            if ($request->filled('branch_id') && auth()->user()->role === 'super_admin') {
                $query->where('branch_id', $request->branch_id);
            }

            $query->orderBy('id', 'desc');
            $filename = 'delivery_tasks_' . date('Y-m-d_His') . '.xlsx';

            return Excel::download(new DeliveryTasksExport($query), $filename);
        } catch (\Exception $e) {
            Log::error('Error exporting delivery tasks: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal ekspor data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Import delivery tasks from CSV (placeholder).
     */
    public function import(Request $request)
    {
        return response()->json(['message' => 'Import belum diimplementasikan'], 501);
    }
}