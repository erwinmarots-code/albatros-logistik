<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MaintenanceScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $schedules = MaintenanceSchedule::with('vehicle')->get();
        return response()->json(['data' => $schedules]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id'       => 'required|exists:vehicles,id',
            'type'             => 'required|in:oil_change,tire_replacement,sparepart,general',
            'description'      => 'nullable|string',
            'last_date'        => 'nullable|date',
            'next_date'        => 'nullable|date|after_or_equal:today',
            'mileage_interval' => 'nullable|integer|min:0',
            'estimated_cost'   => 'nullable|numeric|min:0',
            'status'           => 'sometimes|in:scheduled,done,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $schedule = MaintenanceSchedule::create($request->all());
        return response()->json([
            'message' => 'Jadwal service berhasil ditambahkan',
            'data'    => $schedule
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $schedule = MaintenanceSchedule::with('vehicle')->findOrFail($id);
        return response()->json(['data' => $schedule]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $schedule = MaintenanceSchedule::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'vehicle_id'       => 'sometimes|exists:vehicles,id',
            'type'             => 'sometimes|in:oil_change,tire_replacement,sparepart,general',
            'description'      => 'nullable|string',
            'last_date'        => 'nullable|date',
            'next_date'        => 'nullable|date|after_or_equal:today',
            'mileage_interval' => 'nullable|integer|min:0',
            'estimated_cost'   => 'nullable|numeric|min:0',
            'status'           => 'sometimes|in:scheduled,done,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $schedule->update($request->all());
        return response()->json([
            'message' => 'Jadwal service berhasil diupdate',
            'data'    => $schedule
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $schedule = MaintenanceSchedule::findOrFail($id);
        $schedule->delete();
        return response()->json(['message' => 'Jadwal service berhasil dihapus']);
    }
}