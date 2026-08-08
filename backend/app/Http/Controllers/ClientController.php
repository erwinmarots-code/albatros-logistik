<?php
namespace App\Http\Controllers;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    public function index() { return response()->json(['data' => Client::all()]); }
    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'email' => 'nullable|email|max:100',
            'pic_name' => 'nullable|string|max:100',
        ]);
        if ($validator->fails()) return response()->json(['errors' => $validator->errors()], 422);
        $client = Client::create($request->all());
        return response()->json(['message' => 'Client berhasil ditambahkan', 'data' => $client], 201);
    }
    public function show($id) { return response()->json(['data' => Client::findOrFail($id)]); }
    public function update(Request $request, $id) {
        $client = Client::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'email' => 'nullable|email|max:100',
            'pic_name' => 'nullable|string|max:100',
        ]);
        if ($validator->fails()) return response()->json(['errors' => $validator->errors()], 422);
        $client->update($request->all());
        return response()->json(['message' => 'Client berhasil diupdate', 'data' => $client]);
    }
    public function destroy($id) {
        $client = Client::findOrFail($id);
        $client->delete();
        return response()->json(['message' => 'Client berhasil dihapus']);
    }
}