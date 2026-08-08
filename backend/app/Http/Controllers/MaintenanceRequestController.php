<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\FinancialTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MaintenanceRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $requests = MaintenanceRequest::with(['vehicle', 'driver', 'schedule', 'approver', 'executor'])
            ->orderBy('id', 'desc')
            ->get();
        return response()->json(['data' => $requests]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id'    => 'required|exists:vehicles,id',
            'driver_id'     => 'nullable|exists:drivers,id',
            'schedule_id'   => 'nullable|exists:maintenance_schedules,id',
            'request_date'  => 'required|date',
            'description'   => 'required|string',
            'service_type'  => 'required|in:oil_change,tire_replacement,sparepart,general,other',
            'estimated_cost'=> 'nullable|numeric|min:0',
            'urgency'       => 'required|in:low,medium,high',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['status'] = 'pending';
        $data['is_executed'] = false;

        $maintenanceRequest = MaintenanceRequest::create($data);

        return response()->json([
            'message' => 'Pengajuan perawatan berhasil dibuat',
            'data' => $maintenanceRequest
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $request = MaintenanceRequest::with(['vehicle', 'driver', 'schedule', 'approver', 'executor'])
            ->findOrFail($id);
        return response()->json(['data' => $request]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $maintenanceRequest = MaintenanceRequest::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'vehicle_id'    => 'sometimes|exists:vehicles,id',
            'driver_id'     => 'nullable|exists:drivers,id',
            'schedule_id'   => 'nullable|exists:maintenance_schedules,id',
            'request_date'  => 'sometimes|date',
            'description'   => 'sometimes|string',
            'service_type'  => 'sometimes|in:oil_change,tire_replacement,sparepart,general,other',
            'estimated_cost'=> 'nullable|numeric|min:0',
            'urgency'       => 'sometimes|in:low,medium,high',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $maintenanceRequest->update($request->all());
        return response()->json([
            'message' => 'Pengajuan berhasil diupdate',
            'data' => $maintenanceRequest
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $maintenanceRequest = MaintenanceRequest::findOrFail($id);
        $maintenanceRequest->delete();
        return response()->json(['message' => 'Pengajuan berhasil dihapus']);
    }

    /**
     * Approve the maintenance request (by admin finance).
     */
    public function approve($id)
    {
        $request = MaintenanceRequest::findOrFail($id);

        if ($request->status !== 'pending') {
            return response()->json(['message' => 'Pengajuan sudah diproses'], 400);
        }

        // Update status
        $request->status = 'approved';
        $request->approved_by = auth()->id() ?? 1;
        $request->approved_at = now();
        $request->save();

        // Catat transaksi keuangan
        FinancialTransaction::create([
            'transaction_date' => now(),
            'type' => 'expense',
            'category' => 'service',
            'amount' => $request->estimated_cost ?? 0,
            'description' => 'Biaya perawatan: ' . $request->description,
            'reference_type' => 'MaintenanceRequest',
            'reference_id' => $request->id,
            'vehicle_id' => $request->vehicle_id,
            'driver_id' => $request->driver_id,
            'status' => 'confirmed',
            'created_by' => auth()->id() ?? 1,
        ]);

        return response()->json([
            'message' => 'Pengajuan berhasil disetujui',
            'data' => $request
        ]);
    }

    /**
     * Reject the maintenance request (by admin finance).
     */
    public function reject($id)
    {
        $request = MaintenanceRequest::findOrFail($id);

        if ($request->status !== 'pending') {
            return response()->json(['message' => 'Pengajuan sudah diproses'], 400);
        }

        $request->status = 'rejected';
        $request->approved_by = auth()->id() ?? 1;
        $request->approved_at = now();
        $request->save();

        return response()->json([
            'message' => 'Pengajuan berhasil ditolak',
            'data' => $request
        ]);
    }

    /**
     * Mark the maintenance as executed (completed) by driver/admin.
     */
    public function markAsExecuted($id)
    {
        $request = MaintenanceRequest::findOrFail($id);

        if ($request->status !== 'approved') {
            return response()->json([
                'message' => 'Hanya pengajuan yang sudah disetujui yang dapat dieksekusi'
            ], 400);
        }

        if ($request->is_executed) {
            return response()->json([
                'message' => 'Perawatan ini sudah ditandai selesai'
            ], 400);
        }

        $request->status = 'done';
        $request->is_executed = true;
        $request->executed_at = now();
        $request->executed_by = auth()->id() ?? 1;
        $request->save();

        return response()->json([
            'message' => 'Perawatan berhasil ditandai selesai',
            'data' => $request
        ]);
    }
}