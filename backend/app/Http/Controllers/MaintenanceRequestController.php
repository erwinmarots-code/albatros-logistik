<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\FinancialTransaction;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Traits\BranchScopeTrait;
use App\Exports\MaintenanceRequestsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Gate;

class MaintenanceRequestController extends Controller
{
    use BranchScopeTrait;

    /**
     * Display a listing of maintenance requests.
     */
    public function index(Request $request)
    {
        try {
            $query = MaintenanceRequest::with(['vehicle', 'driver', 'creator', 'approver', 'executor', 'items.sparePart']);
            $this->applyBranchFilter($query);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            if ($request->has('service_type')) {
                $query->where('service_type', $request->service_type);
            }
            if ($request->has('urgency')) {
                $query->where('urgency', $request->urgency);
            }
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('request_code', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            $items = $query->orderBy('id', 'desc')->get();
            return response()->json(['data' => $items]);
        } catch (\Exception $e) {
            Log::error('Error fetching maintenance requests: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memuat data'], 500);
        }
    }

    /**
     * Store a newly created maintenance request.
     * 🔥 Sekarang mendukung items (spare part).
     */
    public function store(Request $request)
    {
        try {
            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'vehicle_id'     => 'required|exists:vehicles,id',
                'driver_id'      => 'required|exists:drivers,id',
                'service_type'   => 'required|in:oil_change,tire_replacement,sparepart,general,other',
                'urgency'        => 'required|in:low,medium,high',
                'estimated_cost' => 'nullable|numeric|min:0',
                'request_date'   => 'required|date',
                'scheduled_date' => 'nullable|date',
                'description'    => 'required|string',
                'items'          => 'nullable|array',
                'items.*.spare_part_id' => 'required|exists:spare_parts,id',
                'items.*.quantity'      => 'required|integer|min:1',
                'items.*.odometer_before' => 'nullable|integer|min:0',
                'items.*.odometer_after'  => 'nullable|integer|min:0',
                'items.*.notes'           => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $request->all();
            $data['created_by'] = $user->id;
            $data['status'] = 'pending';

            if (empty($data['request_code'])) {
                $data['request_code'] = 'MNT-' . strtoupper(uniqid());
            }

            if ($user->role !== 'super_admin') {
                $data['branch_id'] = $user->branch_id;
            } else {
                $vehicle = Vehicle::find($data['vehicle_id']);
                $data['branch_id'] = $vehicle->branch_id ?? $user->branch_id ?? 1;
            }

            $item = MaintenanceRequest::create($data);

            // 🔥 Simpan items (spare part)
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $item->items()->create([
                        'spare_part_id'    => $itemData['spare_part_id'],
                        'quantity'         => $itemData['quantity'] ?? 1,
                        'odometer_before'  => $itemData['odometer_before'] ?? null,
                        'odometer_after'   => $itemData['odometer_after'] ?? null,
                        'notes'            => $itemData['notes'] ?? null,
                    ]);
                }
            }

            return response()->json([
                'message' => 'Pengajuan perawatan berhasil ditambahkan',
                'data'    => $item->load('items.sparePart')
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating maintenance request: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal menambahkan pengajuan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified maintenance request.
     */
    public function show($id)
    {
        try {
            $item = MaintenanceRequest::with(['vehicle', 'driver', 'creator', 'approver', 'executor', 'items.sparePart'])
                ->findOrFail($id);

            $user = auth()->user();
            if ($user->role !== 'super_admin' && $item->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke data ini'], 403);
            }

            return response()->json(['data' => $item]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }
    }

    /**
     * Update the specified maintenance request.
     * 🔥 Sekarang mendukung items (spare part).
     */
    public function update(Request $request, $id)
    {
        try {
            $user = auth()->user();
            $item = MaintenanceRequest::findOrFail($id);

            if ($user->role !== 'super_admin' && $item->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke data ini'], 403);
            }

            if (!in_array($item->status, ['draft', 'pending'])) {
                return response()->json(['message' => 'Pengajuan yang sudah diproses tidak dapat diedit'], 400);
            }

            $validator = Validator::make($request->all(), [
                'vehicle_id'     => 'sometimes|exists:vehicles,id',
                'driver_id'      => 'sometimes|exists:drivers,id',
                'service_type'   => 'sometimes|in:oil_change,tire_replacement,sparepart,general,other',
                'urgency'        => 'sometimes|in:low,medium,high',
                'estimated_cost' => 'nullable|numeric|min:0',
                'request_date'   => 'sometimes|date',
                'scheduled_date' => 'nullable|date',
                'description'    => 'sometimes|string',
                'items'          => 'nullable|array',
                'items.*.spare_part_id' => 'required|exists:spare_parts,id',
                'items.*.quantity'      => 'required|integer|min:1',
                'items.*.odometer_before' => 'nullable|integer|min:0',
                'items.*.odometer_after'  => 'nullable|integer|min:0',
                'items.*.notes'           => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Update data utama
            $item->update($request->only([
                'vehicle_id', 'driver_id', 'service_type', 'urgency',
                'estimated_cost', 'request_date', 'scheduled_date', 'description'
            ]));

            // 🔥 Update items: hapus semua items lama, lalu buat baru
            if ($request->has('items')) {
                $item->items()->delete();
                foreach ($request->items as $itemData) {
                    $item->items()->create([
                        'spare_part_id'    => $itemData['spare_part_id'],
                        'quantity'         => $itemData['quantity'] ?? 1,
                        'odometer_before'  => $itemData['odometer_before'] ?? null,
                        'odometer_after'   => $itemData['odometer_after'] ?? null,
                        'notes'            => $itemData['notes'] ?? null,
                    ]);
                }
            }

            return response()->json([
                'message' => 'Pengajuan berhasil diperbarui',
                'data'    => $item->load('items.sparePart')
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating maintenance request: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui pengajuan'], 500);
        }
    }

    /**
     * Remove the specified maintenance request.
     */
    public function destroy($id)
    {
        try {
            $user = auth()->user();
            $item = MaintenanceRequest::findOrFail($id);

            if ($user->role !== 'super_admin' && $item->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke data ini'], 403);
            }

            if ($user->role !== 'super_admin' && !in_array($item->status, ['draft', 'pending'])) {
                return response()->json(['message' => 'Pengajuan yang sudah diproses tidak dapat dihapus'], 400);
            }

            // Hapus transaksi keuangan terkait jika ada
            FinancialTransaction::where('reference_type', 'MaintenanceRequest')
                ->where('reference_id', $id)
                ->delete();

            // Hapus items terkait
            $item->items()->delete();
            $item->delete();

            return response()->json(['message' => 'Pengajuan berhasil dihapus']);
        } catch (\Exception $e) {
            Log::error('Error deleting maintenance request: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menghapus pengajuan'], 500);
        }
    }

    /**
     * Approve the specified maintenance request.
     * 🔥 Juga buat transaksi keuangan untuk estimasi biaya.
     */
    public function approve($id)
    {
        try {
            $user = auth()->user();

            if (!in_array($user->role, ['admin_finance', 'super_admin'])) {
                return response()->json(['message' => 'Anda tidak memiliki akses untuk menyetujui'], 403);
            }

            $item = MaintenanceRequest::findOrFail($id);

            if ($user->role !== 'super_admin' && $item->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke data ini'], 403);
            }

            if ($item->status !== 'pending') {
                return response()->json(['message' => 'Pengajuan sudah diproses'], 400);
            }

            $item->status = 'approved';
            $item->approved_by = $user->id;
            $item->approved_at = now();
            $item->save();

            // Catat transaksi keuangan (estimasi biaya)
            try {
                $this->createFinancialTransaction($item, $user, 'approved');
                $message = 'Pengajuan disetujui dan transaksi keuangan berhasil dicatat';
            } catch (\Exception $e) {
                Log::error('Failed to create financial transaction for approved maintenance request ID ' . $item->id . ': ' . $e->getMessage());
                $message = 'Pengajuan berhasil disetujui, tetapi gagal mencatat transaksi keuangan. Error: ' . $e->getMessage();
            }

            return response()->json([
                'message' => $message,
                'data'    => $item
            ]);
        } catch (\Exception $e) {
            Log::error('Error approving maintenance request: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menyetujui pengajuan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Reject the specified maintenance request.
     */
    public function reject($id)
    {
        try {
            $user = auth()->user();

            if (!in_array($user->role, ['admin_finance', 'super_admin'])) {
                return response()->json(['message' => 'Anda tidak memiliki akses untuk menolak'], 403);
            }

            $item = MaintenanceRequest::findOrFail($id);

            if ($user->role !== 'super_admin' && $item->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke data ini'], 403);
            }

            if ($item->status !== 'pending') {
                return response()->json(['message' => 'Pengajuan sudah diproses'], 400);
            }

            $item->status = 'rejected';
            $item->approved_by = $user->id;
            $item->approved_at = now();
            $item->save();

            return response()->json([
                'message' => 'Pengajuan berhasil ditolak',
                'data'    => $item
            ]);
        } catch (\Exception $e) {
            Log::error('Error rejecting maintenance request: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menolak pengajuan'], 500);
        }
    }

    /**
     * Mark maintenance request as executed (done).
     * 🔥 Saat selesai, catat transaksi dengan actual_cost (jika ada).
     * 🔥 Sekarang admin_transport juga bisa menandai selesai.
     */
    public function markAsExecuted($id)
    {
        try {
            $user = auth()->user();

            if (!in_array($user->role, ['admin_finance', 'super_admin', 'admin_transport'])) {
                return response()->json(['message' => 'Anda tidak memiliki akses untuk menandai selesai'], 403);
            }

            $item = MaintenanceRequest::findOrFail($id);

            if ($user->role !== 'super_admin' && $item->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke data ini'], 403);
            }

            if ($item->status !== 'approved') {
                return response()->json(['message' => 'Hanya pengajuan yang sudah disetujui yang bisa ditandai selesai'], 400);
            }

            $item->status = 'done';
            $item->executed_by = $user->id;
            $item->executed_at = now();
            $item->is_executed = true;
            $item->save();

            // Jika ada actual_cost, update transaksi keuangan atau buat baru
            try {
                $this->createFinancialTransaction($item, $user, 'executed');
                $message = 'Perawatan berhasil ditandai selesai dan transaksi keuangan diperbarui';
            } catch (\Exception $e) {
                Log::error('Failed to update financial transaction for executed maintenance request ID ' . $item->id . ': ' . $e->getMessage());
                $message = 'Perawatan berhasil ditandai selesai, tetapi gagal memperbarui transaksi keuangan. Error: ' . $e->getMessage();
            }

            return response()->json([
                'message' => $message,
                'data'    => $item
            ]);
        } catch (\Exception $e) {
            Log::error('Error executing maintenance request: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menandai selesai: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 🔥 Create or update financial transaction for maintenance request.
     * 🔥 Isi maintenance_number dengan request_code.
     */
    private function createFinancialTransaction($item, $user, $action = 'approved')
    {
        // Cek apakah sudah ada transaksi untuk maintenance request ini
        $existing = FinancialTransaction::where('reference_type', 'MaintenanceRequest')
            ->where('reference_id', $item->id)
            ->first();

        // Ambil amount: jika action == 'executed' dan actual_cost ada, pakai actual_cost, else estimated_cost
        $amount = ($action === 'executed' && $item->actual_cost) 
            ? $item->actual_cost 
            : ($item->estimated_cost ?? 0);

        if ($amount <= 0) {
            Log::info('Maintenance request ' . $item->id . ' has zero cost, skipping financial transaction.');
            return;
        }

        // Cari vehicle_id, driver_id, branch_id yang valid
        $vehicleId = $item->vehicle_id;
        $driverId = $item->driver_id;
        $branchId = $item->branch_id;
        $createdBy = $user->id ?? 1;

        // Validasi foreign key (fallback jika data tidak valid)
        if (!Vehicle::find($vehicleId)) {
            $vehicle = Vehicle::first();
            $vehicleId = $vehicle ? $vehicle->id : null;
        }
        if (!Driver::find($driverId)) {
            $driver = Driver::first();
            $driverId = $driver ? $driver->id : null;
        }
        if ($branchId && !Branch::find($branchId)) {
            $branch = Branch::first();
            $branchId = $branch ? $branch->id : null;
        }
        if (!User::find($createdBy)) {
            $userModel = User::first();
            $createdBy = $userModel ? $userModel->id : 1;
        }

        $financialData = [
            'transaction_date' => $item->executed_at ?? $item->approved_at ?? now(),
            'type'             => 'expense',
            'category'         => 'maintenance',
            'amount'           => $amount,
            'description'      => 'Biaya perawatan: ' . ($item->description ?? $item->service_type),
            'vehicle_id'       => $vehicleId,
            'driver_id'        => $driverId,
            'status'           => 'confirmed',
            'created_by'       => $createdBy,
            'branch_id'        => $branchId,
            // 🔥 Isi maintenance_number dengan request_code, po_number = null
            'po_number'        => null,
            'maintenance_number' => $item->request_code ?? null,
            'reference_type'   => 'MaintenanceRequest',
            'reference_id'     => $item->id,
        ];

        if ($existing) {
            $existing->update($financialData);
        } else {
            FinancialTransaction::create($financialData);
        }
    }

    /**
     * 🔥 EXPORT maintenance requests to Excel with filters
     */
    public function export(Request $request)
    {
        try {
            if (!Gate::allows('exportModule', 'maintenance-requests')) {
                return response()->json(['message' => 'Anda tidak memiliki izin untuk mengekspor data ini.'], 403);
            }

            $query = MaintenanceRequest::with(['vehicle', 'driver', 'approver', 'executor']);
            $this->applyBranchFilter($query);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('request_code', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('service_type')) {
                $query->where('service_type', $request->service_type);
            }

            if ($request->filled('urgency')) {
                $query->where('urgency', $request->urgency);
            }

            if ($request->filled('branch_id') && auth()->user()->role === 'super_admin') {
                $query->where('branch_id', $request->branch_id);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('request_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('request_date', '<=', $request->date_to);
            }

            $query->orderBy('id', 'desc');
            $filename = 'maintenance_requests_' . date('Y-m-d_His') . '.xlsx';

            return Excel::download(new MaintenanceRequestsExport($query), $filename);
        } catch (\Exception $e) {
            Log::error('Error exporting maintenance requests: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal ekspor data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Import maintenance requests from CSV (placeholder).
     */
    public function import(Request $request)
    {
        return response()->json(['message' => 'Import belum diimplementasikan'], 501);
    }
}