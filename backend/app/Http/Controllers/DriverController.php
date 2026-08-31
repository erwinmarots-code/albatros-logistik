<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Traits\BranchScopeTrait;

class DriverController extends Controller
{
    use BranchScopeTrait;

    /**
     * Display a listing of drivers.
     * Admin transport & super admin melihat semua driver di cabangnya.
     */
    public function index(Request $request)
    {
        try {
            $query = Driver::query();
            $this->applyBranchFilter($query);

            $user = auth()->user();

            // 🔥 Admin transport & super admin lihat SEMUA driver di cabangnya
            // Role lain hanya lihat yang status = available
            if (!in_array($user->role, ['admin_transport', 'super_admin'])) {
                if (!$request->has('all') || $request->all != 'true') {
                    $query->available();
                }
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('license_number', 'LIKE', "%{$search}%")
                      ->orWhere('phone', 'LIKE', "%{$search}%");
                });
            }

            $drivers = $query->orderBy('id', 'desc')->get();
            return response()->json(['data' => $drivers]);
        } catch (\Exception $e) {
            Log::error('Error fetching drivers: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memuat data driver'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name'            => 'required|string|max:255',
                'license_number'  => 'required|string|max:50|unique:drivers,license_number',
                'phone'           => 'nullable|string|max:20',
                'status'          => 'nullable|in:available,unavailable',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $request->all();
            $this->setBranchIdIfNeeded($data);

            $driver = Driver::create($data);
            return response()->json([
                'message' => 'Driver berhasil ditambahkan',
                'data'    => $driver
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating driver: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menambahkan driver'], 500);
        }
    }

    public function show($id)
    {
        try {
            $driver = Driver::findOrFail($id);
            return response()->json(['data' => $driver]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Driver tidak ditemukan'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $driver = Driver::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name'            => 'sometimes|string|max:255',
                'license_number'  => 'sometimes|string|max:50|unique:drivers,license_number,' . $id,
                'phone'           => 'nullable|string|max:20',
                'status'          => 'nullable|in:available,unavailable',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $driver->update($request->all());
            return response()->json([
                'message' => 'Driver berhasil diperbarui',
                'data'    => $driver
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating driver: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui driver'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $driver = Driver::findOrFail($id);
            $driver->delete();
            return response()->json(['message' => 'Driver berhasil dihapus']);
        } catch (\Exception $e) {
            Log::error('Error deleting driver: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menghapus driver'], 500);
        }
    }

    /**
     * Export data drivers ke Excel dengan filter.
     */
    public function export(Request $request)
    {
        try {
            if (!Gate::allows('exportModule', 'drivers')) {
                return response()->json(['message' => 'Anda tidak memiliki izin untuk mengekspor data ini.'], 403);
            }

            $query = Driver::query();
            $this->applyBranchFilter($query);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('license_number', 'LIKE', "%{$search}%")
                      ->orWhere('phone', 'LIKE', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('branch_id') && auth()->user()->role === 'super_admin') {
                $query->where('branch_id', $request->branch_id);
            }

            $query->orderBy('id', 'desc');
            $filename = 'drivers_' . date('Y-m-d_His') . '.xlsx';

            return Excel::download(new DriversExport($query), $filename);
        } catch (\Exception $e) {
            Log::error('Error exporting drivers: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal ekspor data: ' . $e->getMessage()], 500);
        }
    }

    public function import(Request $request)
    {
        return response()->json(['message' => 'Import belum diimplementasikan'], 501);
    }
}