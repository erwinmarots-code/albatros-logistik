<?php

namespace App\Http\Controllers;

use App\Models\DeliveryProject;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeliveryProjectController extends Controller
{
    public function index()
    {
        $projects = DeliveryProject::with(['client', 'creator'])->get();
        return response()->json(['data' => $projects]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id'         => 'required|exists:clients,id',
            'po_number'         => 'nullable|unique:delivery_projects,po_number',
            'sender_name'       => 'required|string|max:100',
            'sender_address'    => 'required|string',
            'sender_phone'      => 'required|string|max:20',
            'receiver_name'     => 'required|string|max:100',
            'receiver_address'  => 'required|string',
            'receiver_phone'    => 'required|string|max:20',
            'goods_description' => 'nullable|string',
            'weight_kg'         => 'nullable|numeric|min:0',
            'collie'            => 'nullable|integer|min:0',
            'volumetric'        => 'nullable|numeric|min:0',
            'ppn'               => 'boolean',
            'discount'          => 'nullable|numeric|min:0',
            'insurance'         => 'nullable|numeric|min:0',
            'goods_value'       => 'nullable|numeric|min:0',
            'shipping_mode'     => 'required|in:darat,udara',
            'status'            => 'sometimes|in:draft,confirmed,delivered,invoiced',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['invoice_number'] = DeliveryProject::generateInvoiceNumber();
        $data['created_by'] = auth()->id() ?? 1;
        $data['status'] = $data['status'] ?? 'draft';

        $project = DeliveryProject::create($data);

        return response()->json(['message' => 'Project berhasil dibuat', 'data' => $project], 201);
    }

    public function show($id)
    {
        $project = DeliveryProject::with(['client', 'creator', 'deliveryTasks'])->findOrFail($id);
        return response()->json(['data' => $project]);
    }

    public function update(Request $request, $id)
    {
        $project = DeliveryProject::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'client_id'         => 'sometimes|exists:clients,id',
            'po_number'         => 'nullable|unique:delivery_projects,po_number,' . $id,
            'sender_name'       => 'sometimes|string|max:100',
            'sender_address'    => 'sometimes|string',
            'sender_phone'      => 'sometimes|string|max:20',
            'receiver_name'     => 'sometimes|string|max:100',
            'receiver_address'  => 'sometimes|string',
            'receiver_phone'    => 'sometimes|string|max:20',
            'goods_description' => 'nullable|string',
            'weight_kg'         => 'nullable|numeric|min:0',
            'collie'            => 'nullable|integer|min:0',
            'volumetric'        => 'nullable|numeric|min:0',
            'ppn'               => 'boolean',
            'discount'          => 'nullable|numeric|min:0',
            'insurance'         => 'nullable|numeric|min:0',
            'goods_value'       => 'nullable|numeric|min:0',
            'shipping_mode'     => 'sometimes|in:darat,udara',
            'status'            => 'sometimes|in:draft,confirmed,delivered,invoiced',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $project->update($request->all());

        return response()->json(['message' => 'Project berhasil diupdate', 'data' => $project]);
    }

    public function destroy($id)
    {
        $project = DeliveryProject::findOrFail($id);
        $project->delete();
        return response()->json(['message' => 'Project berhasil dihapus']);
    }

    // Endpoint untuk autofill client data
    public function getClientData($clientId)
    {
        $client = Client::findOrFail($clientId);
        return response()->json([
            'name' => $client->name,
            'phone' => $client->phone,
            'address' => $client->address,
        ]);
    }
}