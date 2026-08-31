<?php

namespace App\Http\Controllers;

use App\Models\SparePart;
use App\Models\SparePartMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Traits\BranchScopeTrait;

class SparePartController extends Controller
{
    use BranchScopeTrait;

    public function index(Request $request)
    {
        try {
            $query = SparePart::with(['branch', 'creator']);
            $this->applyBranchFilter($query);

            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            } else {
                $user = auth()->user();
                if ($user->role !== 'super_admin') {
                    $query->active();
                }
            }

            if ($request->has('category') && $request->category) {
                $query->where('category', $request->category);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'LIKE', "%{$search}%")
                      ->orWhere('name', 'LIKE', "%{$search}%");
                });
            }

            $spareParts = $query->orderBy('id', 'desc')->get();
            return response()->json(['data' => $spareParts]);
        } catch (\Exception $e) {
            Log::error('Error fetching spare parts: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memuat data spare part'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'code'           => 'required|string|max:50|unique:spare_parts,code',
                'name'           => 'required|string|max:255',
                'category'       => 'required|in:sekali_pakai,berulang',
                'unit'           => 'nullable|string|max:50',
                'stock'          => 'required|integer|min:0',
                'min_stock'      => 'nullable|integer|min:0',
                'price'          => 'nullable|numeric|min:0',
                'lifespan_km'    => 'nullable|integer|min:0',
                'lifespan_months'=> 'nullable|integer|min:0',
                'branch_id'      => 'required|exists:branches,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $request->all();
            $data['created_by'] = $user->id;

            if ($data['stock'] <= 0) {
                $data['status'] = 'stok_habis';
            } elseif ($data['stock'] <= ($data['min_stock'] ?? 0)) {
                $data['status'] = 'perlu_restok';
            } else {
                $data['status'] = 'tersedia';
            }

            $sparePart = SparePart::create($data);

            if ($sparePart->stock > 0) {
                SparePartMovement::create([
                    'spare_part_id' => $sparePart->id,
                    'quantity'      => $sparePart->stock,
                    'movement_type' => 'masuk',
                    'notes'         => 'Stok awal',
                    'created_by'    => $user->id,
                ]);
            }

            return response()->json([
                'message' => 'Spare part berhasil ditambahkan',
                'data'    => $sparePart
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating spare part: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menambahkan spare part'], 500);
        }
    }

    public function show($id)
    {
        try {
            $sparePart = SparePart::with(['branch', 'creator', 'movements.creator'])->findOrFail($id);
            return response()->json(['data' => $sparePart]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Spare part tidak ditemukan'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $sparePart = SparePart::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'code'           => 'sometimes|string|max:50|unique:spare_parts,code,' . $id,
                'name'           => 'sometimes|string|max:255',
                'category'       => 'sometimes|in:sekali_pakai,berulang',
                'unit'           => 'nullable|string|max:50',
                'stock'          => 'nullable|integer|min:0',
                'min_stock'      => 'nullable|integer|min:0',
                'price'          => 'nullable|numeric|min:0',
                'lifespan_km'    => 'nullable|integer|min:0',
                'lifespan_months'=> 'nullable|integer|min:0',
                'status'         => 'nullable|in:tersedia,sedang_dipakai,stok_habis,perlu_restok,rusak_tidak_layak',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $sparePart->update($request->all());

            if ($request->has('stock')) {
                $sparePart->updateStatus();
            }

            return response()->json([
                'message' => 'Spare part berhasil diperbarui',
                'data'    => $sparePart
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating spare part: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui spare part'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $sparePart = SparePart::findOrFail($id);
            if (auth()->user()->role !== 'super_admin') {
                return response()->json(['message' => 'Hanya Super Admin yang dapat menghapus spare part'], 403);
            }
            $sparePart->delete();
            return response()->json(['message' => 'Spare part berhasil dihapus']);
        } catch (\Exception $e) {
            Log::error('Error deleting spare part: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menghapus spare part'], 500);
        }
    }

    public function restock(Request $request, $id)
    {
        try {
            $user = auth()->user();
            $sparePart = SparePart::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'quantity' => 'required|integer|min:1',
                'notes'    => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $sparePart->stock += $request->quantity;
            $sparePart->updateStatus();

            SparePartMovement::create([
                'spare_part_id' => $sparePart->id,
                'quantity'      => $request->quantity,
                'movement_type' => 'masuk',
                'notes'         => $request->notes ?? 'Restok',
                'created_by'    => $user->id,
            ]);

            return response()->json([
                'message' => 'Stok berhasil ditambahkan',
                'data'    => $sparePart
            ]);
        } catch (\Exception $e) {
            Log::error('Error restocking spare part: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal restok spare part'], 500);
        }
    }

    public function markUnusable($id)
    {
        try {
            $user = auth()->user();
            $sparePart = SparePart::findOrFail($id);

            if (!in_array($user->role, ['super_admin', 'admin_transport'])) {
                return response()->json(['message' => 'Anda tidak memiliki akses'], 403);
            }

            $sparePart->status = 'rusak_tidak_layak';
            $sparePart->stock = 0;
            $sparePart->save();

            SparePartMovement::create([
                'spare_part_id' => $sparePart->id,
                'quantity'      => $sparePart->getOriginal('stock') ?: 0,
                'movement_type' => 'rusak',
                'notes'         => 'Ditandai rusak/tidak layak',
                'created_by'    => $user->id,
            ]);

            return response()->json([
                'message' => 'Spare part ditandai rusak/tidak layak dan dihapus dari inventory',
                'data'    => $sparePart
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking spare part as unusable: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menandai spare part'], 500);
        }
    }

    public function movements($id)
    {
        try {
            $sparePart = SparePart::findOrFail($id);
            $movements = $sparePart->movements()->with('creator')->orderBy('id', 'desc')->get();
            return response()->json(['data' => $movements]);
        } catch (\Exception $e) {
            Log::error('Error fetching movements: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memuat history'], 500);
        }
    }
}