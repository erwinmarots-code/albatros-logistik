<?php

namespace App\Http\Controllers;

use App\Models\ShippingProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ShippingProjectController extends Controller
{
    public function index()
    {
        try {
            $projects = ShippingProject::with(['client', 'creator', 'deliveryTasks'])
                ->orderBy('id', 'desc')
                ->get();
            return response()->json(['data' => $projects]);
        } catch (\Exception $e) {
            Log::error('Error fetching shipping projects: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'client_id'         => 'required|exists:clients,id',
                'no_po'             => 'nullable|string|max:50',
                'resi_number'       => 'nullable|string|max:50',
                'sender_name'       => 'required|string|max:100',
                'sender_address'    => 'required|string',
                'sender_phone'      => 'required|string|max:20',
                'receiver_name'     => 'required|string|max:100',
                'receiver_address'  => 'required|string',
                'receiver_phone'    => 'required|string|max:20',
                'goods_description' => 'nullable|string',
                'weight_kg'         => 'nullable|numeric|min:0',
                'collie'            => 'nullable|integer|min:0',
                'volumetric'        => 'nullable|string|max:100',
                'goods_value'       => 'nullable|numeric|min:0',
                'shipping_method'   => 'required|in:darat,udara',
                'status'            => 'sometimes|in:draft,confirmed,completed,cancelled',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $request->all();
            $data['created_by'] = auth()->id();
            
            // Auto generate resi_number jika kosong
            if (empty($data['resi_number'])) {
                $data['resi_number'] = $this->generateResiNumber();
            }
            
            $data['status'] = $data['status'] ?? 'draft';

            $project = ShippingProject::create($data);
            return response()->json(['message' => 'Project berhasil dibuat', 'data' => $project], 201);
        } catch (\Exception $e) {
            Log::error('Error creating shipping project: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal membuat project: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $project = ShippingProject::with(['client', 'creator', 'deliveryTasks'])->findOrFail($id);
            return response()->json(['data' => $project]);
        } catch (\Exception $e) {
            Log::error('Error showing shipping project: ' . $e->getMessage());
            return response()->json(['message' => 'Project tidak ditemukan'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $project = ShippingProject::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'client_id'         => 'sometimes|exists:clients,id',
                'no_po'             => 'nullable|string|max:50',
                'resi_number'       => 'nullable|string|max:50',
                'sender_name'       => 'sometimes|string|max:100',
                'sender_address'    => 'sometimes|string',
                'sender_phone'      => 'sometimes|string|max:20',
                'receiver_name'     => 'sometimes|string|max:100',
                'receiver_address'  => 'sometimes|string',
                'receiver_phone'    => 'sometimes|string|max:20',
                'goods_description' => 'nullable|string',
                'weight_kg'         => 'nullable|numeric|min:0',
                'collie'            => 'nullable|integer|min:0',
                'volumetric'        => 'nullable|string|max:100',
                'goods_value'       => 'nullable|numeric|min:0',
                'shipping_method'   => 'sometimes|in:darat,udara',
                'status'            => 'sometimes|in:draft,confirmed,completed,cancelled',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $project->update($request->all());
            return response()->json(['message' => 'Project berhasil diupdate', 'data' => $project]);
        } catch (\Exception $e) {
            Log::error('Error updating shipping project: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal update project: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $project = ShippingProject::findOrFail($id);
            $project->delete();
            return response()->json(['message' => 'Project berhasil dihapus']);
        } catch (\Exception $e) {
            Log::error('Error deleting shipping project: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal hapus project: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate nomor resi otomatis
     * Format: RESI-YYYYMMDD-XXXX
     */
    private function generateResiNumber()
    {
        $prefix = 'RESI-' . date('Ymd');
        $last = ShippingProject::where('resi_number', 'LIKE', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();
        if ($last) {
            $lastNumber = intval(substr($last->resi_number, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        return $prefix . '-' . $newNumber;
    }
}