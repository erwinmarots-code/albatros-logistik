<?php

namespace App\Http\Controllers;

use App\Models\DeliveryTask;
use App\Models\ShippingProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeliveryTaskController extends Controller
{
    public function index()
    {
        $tasks = DeliveryTask::with([
            'shippingProject', 'client', 'vehicle', 'driver', 'creator'
        ])->orderBy('id', 'desc')->get();
        return response()->json(['data' => $tasks]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shipping_project_id' => 'nullable|exists:shipping_projects,id',
            'client_id'           => 'required|exists:clients,id',
            'vehicle_id'          => 'required|exists:vehicles,id',
            'driver_id'           => 'required|exists:drivers,id',
            'task_date'           => 'required|date',
            'departure_time'      => 'nullable|date_format:H:i',
            'estimated_return_time' => 'nullable|date_format:H:i',
            'origin'              => 'nullable|string',
            'destination'         => 'nullable|string',
            'description'         => 'nullable|string',
            'distance_km'         => 'nullable|integer|min:0',
            'status'              => 'sometimes|in:planned,ongoing,completed,cancelled',
            'notes'               => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'planned';
        $data['created_by'] = auth()->id(); // nullable

        $task = DeliveryTask::create($data);
        return response()->json(['message' => 'Tugas berhasil dibuat', 'data' => $task], 201);
    }

    public function show($id)
    {
        $task = DeliveryTask::with([
            'shippingProject', 'client', 'vehicle', 'driver', 'creator'
        ])->findOrFail($id);
        return response()->json(['data' => $task]);
    }

    public function update(Request $request, $id)
    {
        $task = DeliveryTask::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'shipping_project_id' => 'nullable|exists:shipping_projects,id',
            'client_id'           => 'sometimes|exists:clients,id',
            'vehicle_id'          => 'sometimes|exists:vehicles,id',
            'driver_id'           => 'sometimes|exists:drivers,id',
            'task_date'           => 'sometimes|date',
            'departure_time'      => 'nullable|date_format:H:i',
            'estimated_return_time' => 'nullable|date_format:H:i',
            'actual_return_time'  => 'nullable|date_format:H:i',
            'origin'              => 'nullable|string',
            'destination'         => 'nullable|string',
            'description'         => 'nullable|string',
            'distance_km'         => 'nullable|integer|min:0',
            'status'              => 'sometimes|in:planned,ongoing,completed,cancelled',
            'notes'               => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $task->update($request->all());
        return response()->json(['message' => 'Tugas berhasil diupdate', 'data' => $task]);
    }

    public function destroy($id)
    {
        $task = DeliveryTask::findOrFail($id);
        $task->delete();
        return response()->json(['message' => 'Tugas berhasil dihapus']);
    }

    public function updateStatus(Request $request, $id)
    {
        $task = DeliveryTask::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:planned,ongoing,completed,cancelled',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $task->status = $request->status;
        $task->save();
        return response()->json(['message' => 'Status berhasil diupdate', 'data' => $task]);
    }
}