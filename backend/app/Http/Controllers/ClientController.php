<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Traits\BranchScopeTrait;
use App\Exports\ClientsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Gate;

class ClientController extends Controller
{
    use BranchScopeTrait;

    public function index(Request $request)
    {
        try {
            $query = Client::query();
            $this->applyBranchFilter($query);

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('phone', 'LIKE', "%{$search}%")
                      ->orWhere('address', 'LIKE', "%{$search}%");
                });
            }

            $clients = $query->orderBy('id', 'desc')->get();
            return response()->json(['data' => $clients]);
        } catch (\Exception $e) {
            Log::error('Error fetching clients: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memuat data client'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name'    => 'required|string|max:255',
                'address' => 'nullable|string',
                'phone'   => 'nullable|string|max:20',
                'email'   => 'nullable|email|max:255|unique:clients,email',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $request->all();
            $this->setBranchIdIfNeeded($data);

            $client = Client::create($data);
            return response()->json([
                'message' => 'Client berhasil ditambahkan',
                'data'    => $client
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating client: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menambahkan client'], 500);
        }
    }

    public function show($id)
    {
        try {
            $client = Client::findOrFail($id);
            return response()->json(['data' => $client]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Client tidak ditemukan'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $client = Client::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name'    => 'sometimes|required|string|max:255',
                'address' => 'nullable|string',
                'phone'   => 'nullable|string|max:20',
                'email'   => 'nullable|email|max:255|unique:clients,email,' . $id,
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $client->update($request->all());
            return response()->json([
                'message' => 'Client berhasil diperbarui',
                'data'    => $client
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating client: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui client'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $client = Client::findOrFail($id);
            $client->delete();
            return response()->json(['message' => 'Client berhasil dihapus']);
        } catch (\Exception $e) {
            Log::error('Error deleting client: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menghapus client'], 500);
        }
    }

    /**
     * 🔥 EXPORT CLIENTS TO EXCEL WITH FILTERS
     * Method ini menggantikan export CSV sebelumnya.
     */
    public function export(Request $request)
    {
        try {
            // Cek izin (Gate didefinisikan di AppServiceProvider)
            if (!Gate::allows('exportModule', 'clients')) {
                return response()->json(['message' => 'Anda tidak memiliki izin untuk mengekspor data ini.'], 403);
            }

            $query = Client::query();
            $this->applyBranchFilter($query);

            // Filter pencarian
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('phone', 'LIKE', "%{$search}%")
                      ->orWhere('address', 'LIKE', "%{$search}%");
                });
            }

            // Filter cabang (hanya Super Admin)
            if ($request->filled('branch_id') && auth()->user()->role === 'super_admin') {
                $query->where('branch_id', $request->branch_id);
            }

            // Filter tanggal
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Urutkan
            $query->orderBy('id', 'desc');

            // Nama file
            $filename = 'clients_' . date('Y-m-d_His') . '.xlsx';

            // Download Excel
            return Excel::download(new ClientsExport($query), $filename);
        } catch (\Exception $e) {
            Log::error('Error exporting clients: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal ekspor data: ' . $e->getMessage()], 500);
        }
    }

    public function import(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:csv,txt|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $file = $request->file('file');
            $handle = fopen($file->getRealPath(), 'r');
            $header = fgetcsv($handle);

            $columnMap = [];
            foreach ($header as $index => $column) {
                $column = strtolower(trim($column));
                if (in_array($column, ['name', 'address', 'phone', 'email'])) {
                    $columnMap[$index] = $column;
                }
            }

            $imported = 0;
            $errors = [];

            while (($row = fgetcsv($handle)) !== false) {
                $data = [];
                foreach ($columnMap as $index => $field) {
                    $data[$field] = $row[$index] ?? null;
                }

                $rowValidator = Validator::make($data, [
                    'name'  => 'required|string|max:255',
                    'email' => 'nullable|email|max:255|unique:clients,email',
                ]);

                if ($rowValidator->fails()) {
                    $errors[] = ['row' => $data, 'errors' => $rowValidator->errors()];
                    continue;
                }

                $this->setBranchIdIfNeeded($data);
                Client::create($data);
                $imported++;
            }

            fclose($handle);

            return response()->json([
                'message' => "Import selesai. $imported data berhasil diimpor.",
                'imported' => $imported,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            Log::error('Error importing clients: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal import data: ' . $e->getMessage()], 500);
        }
    }
}