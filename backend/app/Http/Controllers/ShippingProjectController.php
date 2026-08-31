<?php

namespace App\Http\Controllers;

use App\Models\ShippingProject;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Traits\BranchScopeTrait;

class ShippingProjectController extends Controller
{
    use BranchScopeTrait;

    /**
     * Display a listing of shipping projects.
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $query = ShippingProject::with(['client', 'branch', 'creator', 'deliveryTasks']);
            $this->applyBranchFilter($query);

            if ($request->has('client_id')) {
                $query->where('client_id', $request->client_id);
            }
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('no_po', 'LIKE', "%{$search}%")
                      ->orWhere('no_resi', 'LIKE', "%{$search}%");
                });
            }

            $projects = $query->orderBy('id', 'desc')->get();
            return response()->json(['data' => $projects]);
        } catch (\Exception $e) {
            Log::error('Error fetching projects: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memuat project'], 500);
        }
    }

    /**
     * Store a newly created shipping project.
     */
    public function store(Request $request)
    {
        try {
            $user = auth()->user();
            if (!in_array($user->role, ['admin_project', 'super_admin'])) {
                return response()->json(['message' => 'Anda tidak memiliki akses untuk membuat project'], 403);
            }

            $validator = Validator::make($request->all(), [
                'client_id'       => 'required|exists:clients,id',
                'branch_id'       => 'required|exists:branches,id',
                'no_po'           => 'required|string|max:50|unique:shipping_projects,no_po',
                'sender_name'     => 'required|string|max:100',
                'sender_address'  => 'required|string',
                'sender_phone'    => 'required|string|max:20',
                'receiver_name'   => 'nullable|string|max:100',
                'receiver_address'=> 'nullable|string',
                'receiver_phone'  => 'nullable|string|max:20',
                'goods_description' => 'nullable|string',
                'weight_kg'         => 'nullable|numeric|min:0',
                'collie'            => 'nullable|integer|min:0',
                'contract_value'    => 'nullable|numeric|min:0',
                'shipping_method'   => 'required|in:darat,udara,laut',
                'status'            => 'nullable|in:draft,confirmed,on_delivery,completed,cancelled',
                'notes'             => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $request->all();
            $data['created_by'] = $user->id;
            $data['status'] = $data['status'] ?? 'draft';

            // Auto generate no_resi jika kosong
            if (empty($data['no_resi'])) {
                $branch = Branch::find($data['branch_id']);
                if ($branch) {
                    $data['no_resi'] = ShippingProject::generateResiNumber($branch->code);
                }
            }

            // Jika user bukan super_admin, paksa branch_id sesuai user
            if ($user->role !== 'super_admin') {
                $data['branch_id'] = $user->branch_id;
            }

            $project = ShippingProject::create($data);
            $project->load(['client', 'branch', 'creator']);

            return response()->json([
                'message' => 'Project berhasil dibuat',
                'data'    => $project
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating project: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal membuat project: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified shipping project.
     */
    public function show($id)
    {
        try {
            $project = ShippingProject::with([
                'client',
                'branch',
                'creator',
                'deliveryTasks.vehicle',
                'deliveryTasks.driver',
                'deliveryTasks.fuelExpenses'
            ])->findOrFail($id);

            $user = auth()->user();
            if ($user->role !== 'super_admin' && $project->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke project ini'], 403);
            }

            return response()->json(['data' => $project]);
        } catch (\Exception $e) {
            Log::error('Error showing project: ' . $e->getMessage());
            return response()->json(['message' => 'Project tidak ditemukan'], 404);
        }
    }

    /**
     * Update the specified shipping project.
     */
    public function update(Request $request, $id)
    {
        try {
            $user = auth()->user();
            $project = ShippingProject::findOrFail($id);

            if (!in_array($user->role, ['admin_project', 'super_admin'])) {
                return response()->json(['message' => 'Anda tidak memiliki akses untuk mengedit project'], 403);
            }

            if ($user->role !== 'super_admin' && $project->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke project ini'], 403);
            }

            if (in_array($project->status, ['completed', 'cancelled']) && $user->role !== 'super_admin') {
                return response()->json(['message' => 'Project yang sudah selesai atau dibatalkan tidak dapat diedit'], 400);
            }

            $validator = Validator::make($request->all(), [
                'client_id'       => 'sometimes|exists:clients,id',
                'branch_id'       => 'sometimes|exists:branches,id',
                'no_po'           => 'sometimes|string|max:50|unique:shipping_projects,no_po,' . $id,
                'sender_name'     => 'sometimes|string|max:100',
                'sender_address'  => 'sometimes|string',
                'sender_phone'    => 'sometimes|string|max:20',
                'receiver_name'   => 'nullable|string|max:100',
                'receiver_address'=> 'nullable|string',
                'receiver_phone'  => 'nullable|string|max:20',
                'goods_description' => 'nullable|string',
                'weight_kg'         => 'nullable|numeric|min:0',
                'collie'            => 'nullable|integer|min:0',
                'contract_value'    => 'nullable|numeric|min:0',
                'shipping_method'   => 'sometimes|in:darat,udara,laut',
                'notes'             => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $project->update($request->all());
            $project->load(['client', 'branch', 'creator']);

            return response()->json([
                'message' => 'Project berhasil diupdate',
                'data'    => $project
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating project: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal update project: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified shipping project.
     */
    public function destroy($id)
    {
        try {
            $user = auth()->user();
            $project = ShippingProject::findOrFail($id);

            if (!in_array($user->role, ['admin_project', 'super_admin'])) {
                return response()->json(['message' => 'Anda tidak memiliki akses untuk menghapus project'], 403);
            }

            if ($user->role !== 'super_admin' && $project->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke project ini'], 403);
            }

            if (in_array($project->status, ['completed', 'cancelled']) && $user->role !== 'super_admin') {
                return response()->json(['message' => 'Project yang sudah selesai atau dibatalkan tidak dapat dihapus'], 400);
            }

            $project->delete();
            return response()->json(['message' => 'Project berhasil dihapus']);
        } catch (\Exception $e) {
            Log::error('Error deleting project: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal hapus project: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update only the status of a shipping project.
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $user = auth()->user();
            $project = ShippingProject::findOrFail($id);

            if ($user->role !== 'super_admin' && $project->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke project ini'], 403);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:draft,confirmed,on_delivery,completed,cancelled'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $newStatus = $request->status;

            $allowedRoles = ['admin_project', 'super_admin'];
            if (!in_array($user->role, $allowedRoles)) {
                return response()->json(['message' => 'Anda tidak memiliki akses untuk mengubah status project'], 403);
            }

            if ($project->status === $newStatus) {
                return response()->json(['message' => 'Status sudah sama'], 400);
            }

            $statuses = ShippingProject::$statuses ?? ['draft', 'confirmed', 'on_delivery', 'completed', 'cancelled'];
            $currentIndex = array_search($project->status, $statuses);
            $newIndex = array_search($newStatus, $statuses);

            if ($currentIndex === false || $newIndex === false) {
                return response()->json(['message' => 'Status tidak valid'], 400);
            }

            if ($newIndex < $currentIndex && $user->role !== 'super_admin') {
                return response()->json(['message' => 'Hanya Super Admin yang dapat mengembalikan status'], 403);
            }

            if ($newIndex !== $currentIndex + 1 && $newIndex !== $currentIndex - 1 && $user->role !== 'super_admin') {
                return response()->json(['message' => 'Status harus diubah secara berurutan (tidak boleh melompat)'], 400);
            }

            $project->status = $newStatus;
            $project->save();

            return response()->json([
                'message' => 'Status project berhasil diubah',
                'data'    => $project
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating project status: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal update status: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get PO list for dropdown (used in Delivery Task).
     * 🔥 Hanya menampilkan project dengan status yang belum selesai/dibatalkan.
     */
    public function poList()
    {
        try {
            $user = auth()->user();
            $query = ShippingProject::select('id', 'no_po', 'no_resi', 'client_id', 'status') // tambahkan status jika diperlukan
                ->with('client:id,name')
                ->whereNotIn('status', ['completed', 'cancelled']); // 🔥 Filter status
            $this->applyBranchFilter($query);

            $projects = $query->orderBy('id', 'desc')->get();
            return response()->json(['data' => $projects]);
        } catch (\Exception $e) {
            Log::error('Error fetching PO list: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memuat daftar PO'], 500);
        }
    }

    /**
     * Generate a new resi number for a branch.
     */
    public function generateResi(Request $request)
    {
        $branch = Branch::find($request->branch_id);
        if (!$branch) {
            return response()->json(['resi_number' => '']);
        }
        $resi = ShippingProject::generateResiNumber($branch->code);
        return response()->json(['resi_number' => $resi]);
    }

    /**
     * Export shipping projects to Excel with filters.
     */
    public function export(Request $request)
    {
        try {
            if (!Gate::allows('exportModule', 'projects')) {
                return response()->json(['message' => 'Anda tidak memiliki izin untuk mengekspor data ini.'], 403);
            }

            $query = ShippingProject::with(['client', 'branch']);
            $this->applyBranchFilter($query);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('no_po', 'LIKE', "%{$search}%")
                      ->orWhere('no_resi', 'LIKE', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('branch_id') && auth()->user()->role === 'super_admin') {
                $query->where('branch_id', $request->branch_id);
            }

            $query->orderBy('id', 'desc');
            $filename = 'projects_' . date('Y-m-d_His') . '.xlsx';

            return Excel::download(new ProjectsExport($query), $filename);
        } catch (\Exception $e) {
            Log::error('Error exporting projects: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal ekspor data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Import shipping projects from CSV (placeholder).
     */
    public function import(Request $request)
    {
        return response()->json(['message' => 'Import belum diimplementasikan'], 501);
    }
}