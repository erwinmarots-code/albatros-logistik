<?php

namespace App\Http\Controllers;

use App\Models\FuelExpense;
use App\Models\DeliveryTask;
use App\Models\FinancialTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FuelExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $expenses = FuelExpense::with([
            'deliveryTask.shippingProject', 'vehicle', 'driver', 'approver'
        ])->orderBy('id', 'desc')->get();
        return response()->json(['data' => $expenses]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Ambil data task
        $task = DeliveryTask::find($request->delivery_task_id);
        if (!$task) {
            return response()->json(['message' => 'Tugas pengantaran tidak ditemukan'], 404);
        }

        // Cek apakah sudah ada pengajuan untuk task ini dengan status pending atau approved
        $existing = FuelExpense::where('delivery_task_id', $task->id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($existing) {
            return response()->json([
                'message' => 'Pengajuan biaya untuk tugas ini sudah ada dan masih pending atau sudah disetujui. Tidak dapat mengajukan ulang.'
            ], 422);
        }

        // Merge vehicle_id dan driver_id dari task
        $request->merge([
            'vehicle_id' => $task->vehicle_id,
            'driver_id'  => $task->driver_id,
        ]);

        $validator = Validator::make($request->all(), [
            'delivery_task_id' => 'required|exists:delivery_tasks,id',
            'vehicle_id'       => 'required|exists:vehicles,id',
            'driver_id'        => 'required|exists:drivers,id',
            'type'             => 'required|in:fuel,toll,parking,meal,other',
            'amount'           => 'required|numeric|min:0',
            'description'      => 'nullable|string',
            'receipt_photo'    => 'nullable|string|max:255',
            'request_date'     => 'required|date',
            'notes'            => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['status'] = 'pending';
        $data['unique_code'] = FuelExpense::generateUniqueCode();

        $expense = FuelExpense::create($data);

        return response()->json(['message' => 'Pengajuan biaya berhasil dibuat', 'data' => $expense], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $expense = FuelExpense::with([
            'deliveryTask.shippingProject', 'vehicle', 'driver', 'approver'
        ])->findOrFail($id);
        return response()->json(['data' => $expense]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $expense = FuelExpense::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'delivery_task_id' => 'sometimes|exists:delivery_tasks,id',
            'vehicle_id'       => 'sometimes|exists:vehicles,id',
            'driver_id'        => 'sometimes|exists:drivers,id',
            'type'             => 'sometimes|in:fuel,toll,parking,meal,other',
            'amount'           => 'sometimes|numeric|min:0',
            'description'      => 'nullable|string',
            'receipt_photo'    => 'nullable|string|max:255',
            'request_date'     => 'sometimes|date',
            'status'           => 'sometimes|in:pending,approved,rejected',
            'notes'            => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $expense->update($request->all());
        return response()->json(['message' => 'Pengajuan biaya berhasil diupdate', 'data' => $expense]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $expense = FuelExpense::findOrFail($id);
        $expense->delete();
        return response()->json(['message' => 'Pengajuan biaya berhasil dihapus']);
    }

    /**
     * Approve the fuel expense (by admin finance).
     */
    public function approve($id)
    {
        $expense = FuelExpense::findOrFail($id);

        if ($expense->status !== 'pending') {
            return response()->json(['message' => 'Pengajuan sudah diproses'], 400);
        }

        $expense->status = 'approved';
        $expense->approved_by = auth()->id() ?? 1;
        $expense->approved_at = now();
        $expense->save();

        // Catat transaksi keuangan
        FinancialTransaction::create([
            'transaction_date' => now(),
            'type' => 'expense',
            'category' => $expense->type,
            'amount' => $expense->amount,
            'description' => 'Biaya operasional: ' . ($expense->description ?? ''),
            'reference_type' => 'FuelExpense',
            'reference_id' => $expense->id,
            'vehicle_id' => $expense->vehicle_id,
            'driver_id' => $expense->driver_id,
            'status' => 'confirmed',
            'created_by' => auth()->id() ?? 1,
        ]);

        return response()->json(['message' => 'Pengajuan biaya disetujui', 'data' => $expense]);
    }

    /**
     * Reject the fuel expense (by admin finance).
     */
    public function reject($id)
    {
        $expense = FuelExpense::findOrFail($id);

        if ($expense->status !== 'pending') {
            return response()->json(['message' => 'Pengajuan sudah diproses'], 400);
        }

        $expense->status = 'rejected';
        $expense->approved_by = auth()->id() ?? 1;
        $expense->approved_at = now();
        $expense->save();

        return response()->json(['message' => 'Pengajuan biaya ditolak', 'data' => $expense]);
    }
}