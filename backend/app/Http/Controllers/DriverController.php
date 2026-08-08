<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DriverController extends Controller
{
    public function index()
    {
        $drivers = Driver::all();
        return response()->json(['data' => $drivers]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:100',
            'phone'          => 'nullable|string|max:20',
            'license_number' => 'required|string|unique:drivers|max:50',
            'address'        => 'nullable|string',
            'status'         => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $driver = Driver::create($request->all());
        return response()->json(['message' => 'Driver berhasil ditambahkan', 'data' => $driver], 201);
    }

    public function show($id)
    {
        $driver = Driver::findOrFail($id);
        return response()->json(['data' => $driver]);
    }

    public function update(Request $request, $id)
    {
        $driver = Driver::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'           => 'sometimes|string|max:100',
            'phone'          => 'nullable|string|max:20',
            'license_number' => 'sometimes|string|unique:drivers,license_number,' . $id,
            'address'        => 'nullable|string',
            'status'         => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $driver->update($request->all());
        return response()->json(['message' => 'Driver berhasil diupdate', 'data' => $driver]);
    }

    public function destroy($id)
    {
        $driver = Driver::findOrFail($id);
        $driver->delete();
        return response()->json(['message' => 'Driver berhasil dihapus']);
    }
}