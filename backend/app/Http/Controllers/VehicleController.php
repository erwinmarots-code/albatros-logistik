<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Traits\BranchScopeTrait;
use App\Exports\VehiclesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Gate;

class VehicleController extends Controller
{
    use BranchScopeTrait;

    /**
     * Display a listing of vehicles.
     * Admin transport & super admin see all vehicles in their branch.
     */
    public function index(Request $request)
    {
        try {
            $query = Vehicle::query();
            $this->applyBranchFilter($query);

            $user = auth()->user();

            // Admin transport & super admin lihat SEMUA kendaraan di cabangnya
            // Role lain hanya lihat yang status = available
            if (!in_array($user->role, ['admin_transport', 'super_admin'])) {
                if (!$request->has('all') || $request->all != 'true') {
                    $query->available();
                }
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('plate_number', 'LIKE', "%{$search}%")
                      ->orWhere('brand', 'LIKE', "%{$search}%")
                      ->orWhere('model', 'LIKE', "%{$search}%");
                });
            }

            $vehicles = $query->orderBy('id', 'desc')->get();
            return response()->json(['data' => $vehicles]);
        } catch (\Exception $e) {
            Log::error('Error fetching vehicles: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memuat data kendaraan'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'plate_number'     => 'required|string|max:20|unique:vehicles,plate_number',
                'brand'            => 'required|string|max:100',
                'model'            => 'required|string|max:100',
                'year'             => 'required|integer|min:1900|max:' . (date('Y') + 1),
                'color'            => 'nullable|string|max:50',
                'engine_capacity'  => 'nullable|numeric',
                'fuel_type'        => 'nullable|string|max:50',
                'status'           => 'nullable|in:available,maintenance,inactive,rented',
                'price'            => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $request->all();
            $this->setBranchIdIfNeeded($data);

            $vehicle = Vehicle::create($data);
            return response()->json([
                'message' => 'Kendaraan berhasil ditambahkan',
                'data'    => $vehicle
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating vehicle: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menambahkan kendaraan'], 500);
        }
    }

    public function show($id)
    {
        try {
            $vehicle = Vehicle::findOrFail($id);
            return response()->json(['data' => $vehicle]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Kendaraan tidak ditemukan'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $vehicle = Vehicle::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'plate_number'     => 'sometimes|string|max:20|unique:vehicles,plate_number,' . $id,
                'brand'            => 'sometimes|string|max:100',
                'model'            => 'sometimes|string|max:100',
                'year'             => 'sometimes|integer|min:1900|max:' . (date('Y') + 1),
                'color'            => 'nullable|string|max:50',
                'engine_capacity'  => 'nullable|numeric',
                'fuel_type'        => 'nullable|string|max:50',
                'status'           => 'nullable|in:available,maintenance,inactive,rented',
                'price'            => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $vehicle->update($request->all());
            return response()->json([
                'message' => 'Kendaraan berhasil diperbarui',
                'data'    => $vehicle
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating vehicle: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui kendaraan'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $vehicle = Vehicle::findOrFail($id);
            $vehicle->delete();
            return response()->json(['message' => 'Kendaraan berhasil dihapus']);
        } catch (\Exception $e) {
            Log::error('Error deleting vehicle: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menghapus kendaraan'], 500);
        }
    }

    /**
     * Export data vehicles ke Excel dengan filter.
     */
    public function export(Request $request)
    {
        try {
            if (!Gate::allows('exportModule', 'vehicles')) {
                return response()->json(['message' => 'Anda tidak memiliki izin untuk mengekspor data ini.'], 403);
            }

            $query = Vehicle::query();
            $this->applyBranchFilter($query);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('plate_number', 'LIKE', "%{$search}%")
                      ->orWhere('brand', 'LIKE', "%{$search}%")
                      ->orWhere('model', 'LIKE', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('branch_id') && auth()->user()->role === 'super_admin') {
                $query->where('branch_id', $request->branch_id);
            }

            $query->orderBy('id', 'desc');
            $filename = 'vehicles_' . date('Y-m-d_His') . '.xlsx';

            return Excel::download(new VehiclesExport($query), $filename);
        } catch (\Exception $e) {
            Log::error('Error exporting vehicles: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal ekspor data: ' . $e->getMessage()], 500);
        }
    }

    public function import(Request $request)
    {
        return response()->json(['message' => 'Import belum diimplementasikan'], 501);
    }
}