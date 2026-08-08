<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::all();
        return response()->json(['data' => $vehicles]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plate_number' => 'required|unique:vehicles|string|max:20',
            'brand'        => 'required|string|max:50',
            'model'        => 'required|string|max:50',
            'year'         => 'required|integer|min:1900|max:' . date('Y'),
            'color'        => 'nullable|string|max:30',
            'engine_capacity' => 'nullable|string|max:20',
            'fuel_type'    => 'required|in:bensin,diesel,electric',
            'status'       => 'required|in:available,maintenance,in_use,retired',
            'purchase_date'=> 'nullable|date',
            'price'        => 'nullable|numeric',
            'notes'        => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vehicle = Vehicle::create($request->all());
        return response()->json(['message' => 'Kendaraan berhasil ditambahkan', 'data' => $vehicle], 201);
    }

    public function show($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        return response()->json(['data' => $vehicle]);
    }

    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'plate_number' => 'sometimes|unique:vehicles,plate_number,' . $id,
            'brand'        => 'sometimes|string|max:50',
            'model'        => 'sometimes|string|max:50',
            'year'         => 'sometimes|integer|min:1900|max:' . date('Y'),
            'color'        => 'nullable|string|max:30',
            'engine_capacity' => 'nullable|string|max:20',
            'fuel_type'    => 'sometimes|in:bensin,diesel,electric',
            'status'       => 'sometimes|in:available,maintenance,in_use,retired',
            'purchase_date'=> 'nullable|date',
            'price'        => 'nullable|numeric',
            'notes'        => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vehicle->update($request->all());
        return response()->json(['message' => 'Kendaraan berhasil diupdate', 'data' => $vehicle]);
    }

    public function destroy($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->delete();
        return response()->json(['message' => 'Kendaraan berhasil dihapus']);
    }
}