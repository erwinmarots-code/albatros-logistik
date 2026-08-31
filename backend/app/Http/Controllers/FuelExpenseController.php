<?php

namespace App\Http\Controllers;

use App\Models\FuelExpense;
use App\Models\FinancialTransaction;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Traits\BranchScopeTrait;
use Illuminate\Support\Facades\Schema;
use App\Exports\FuelExpensesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Gate;

class FuelExpenseController extends Controller
{
    use BranchScopeTrait;

    /**
     * Display a listing of fuel expenses.
     */
    public function index(Request $request)
    {
        try {
            $query = FuelExpense::with(['deliveryTask', 'vehicle', 'driver', 'creator']);
            $this->applyBranchFilter($query);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('unique_code', 'LIKE', "%{$search}%")
                      ->orWhereHas('deliveryTask', function ($q2) use ($search) {
                          $q2->where('no_resi', 'LIKE', "%{$search}%");
                      });
                });
            }

            $expenses = $query->orderBy('id', 'desc')->get();
            return response()->json(['data' => $expenses]);
        } catch (\Exception $e) {
            Log::error('Error fetching fuel expenses: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memuat data'], 500);
        }
    }

    /**
     * Store a newly created fuel expense.
     */
    public function store(Request $request)
    {
        try {
            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'delivery_task_id' => 'required|exists:delivery_tasks,id',
                'type'             => 'required|in:bahan_bakar,toll,parkir,lainnya',
                'amount'           => 'required|numeric|min:0',
                'vehicle_id'       => 'required|exists:vehicles,id',
                'driver_id'        => 'required|exists:drivers,id',
                'transaction_date' => 'required|date',
                'description'      => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $request->all();

            $vehicle = Vehicle::find($data['vehicle_id']);
            $driver  = Driver::find($data['driver_id']);
            if (!$vehicle || !$driver) {
                return response()->json(['message' => 'Kendaraan atau driver tidak valid'], 422);
            }

            $data['created_by'] = $user->id ?? 1;
            $data['status']     = 'pending';
            if (empty($data['unique_code'])) {
                $data['unique_code'] = 'FUEL-' . strtoupper(uniqid());
            }

            if ($user->role !== 'super_admin') {
                $data['branch_id'] = $user->branch_id;
            } else {
                $data['branch_id'] = $vehicle->branch_id ?? null;
            }

            $expense = FuelExpense::create($data);

            return response()->json([
                'message' => 'Pengajuan biaya berhasil ditambahkan',
                'data'    => $expense
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating fuel expense: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal menambahkan pengajuan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve the specified fuel expense.
     * Saat approve, buat transaksi keuangan sebagai pengeluaran.
     */
    public function approve($id)
    {
        try {
            $user = auth()->user();
            $expense = FuelExpense::findOrFail($id);

            if ($user->role !== 'super_admin' && $expense->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke pengajuan ini'], 403);
            }

            if ($expense->status !== 'pending') {
                return response()->json(['message' => 'Pengajuan sudah diproses'], 400);
            }

            // Update status expense
            $expense->status = 'approved';
            $expense->approved_by = $user->id;
            $expense->approved_at = now();
            $expense->save();

            // 🔥 Catat ke FinancialTransaction
            try {
                $this->createFinancialTransaction($expense, $user);
                $message = 'Pengajuan disetujui dan transaksi keuangan berhasil dicatat';
            } catch (\Exception $e) {
                Log::error('Failed to create financial transaction for approved fuel expense ID ' . $expense->id . ': ' . $e->getMessage());
                $message = 'Pengajuan berhasil disetujui, tetapi gagal mencatat ke transaksi keuangan. Error: ' . $e->getMessage();
            }

            return response()->json([
                'message' => $message,
                'data'    => $expense
            ]);
        } catch (\Exception $e) {
            Log::error('Error approving fuel expense: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menyetujui pengajuan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Reject the specified fuel expense.
     */
    public function reject($id)
    {
        try {
            $user = auth()->user();
            $expense = FuelExpense::findOrFail($id);

            if ($user->role !== 'super_admin' && $expense->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke pengajuan ini'], 403);
            }

            if ($expense->status !== 'pending') {
                return response()->json(['message' => 'Pengajuan sudah diproses'], 400);
            }

            $expense->status = 'rejected';
            $expense->approved_by = $user->id;
            $expense->approved_at = now();
            $expense->save();

            return response()->json([
                'message' => 'Pengajuan ditolak',
                'data'    => $expense
            ]);
        } catch (\Exception $e) {
            Log::error('Error rejecting fuel expense: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menolak pengajuan'], 500);
        }
    }

    /**
     * Create financial transaction for an approved fuel expense.
     * 🔥 Isi po_number dengan no_resi dari delivery task.
     */
    private function createFinancialTransaction($expense, $user)
    {
        // Ambil ID yang valid dari database (fallback jika ID asli tidak ada)
        $vehicleId = $expense->vehicle_id;
        $driverId  = $expense->driver_id;
        $branchId  = $expense->branch_id;
        $createdBy = $user->id ?? 1;

        // Validasi dan perbaiki foreign key
        if (!Vehicle::find($vehicleId)) {
            $vehicle = Vehicle::first();
            if ($vehicle) {
                $vehicleId = $vehicle->id;
                Log::warning('Vehicle ID ' . $expense->vehicle_id . ' tidak ditemukan, menggunakan ID ' . $vehicleId);
            } else {
                throw new \Exception('Tidak ada kendaraan di database.');
            }
        }

        if (!Driver::find($driverId)) {
            $driver = Driver::first();
            if ($driver) {
                $driverId = $driver->id;
                Log::warning('Driver ID ' . $expense->driver_id . ' tidak ditemukan, menggunakan ID ' . $driverId);
            } else {
                throw new \Exception('Tidak ada driver di database.');
            }
        }

        if ($branchId && !Branch::find($branchId)) {
            $branch = Branch::first();
            if ($branch) {
                $branchId = $branch->id;
                Log::warning('Branch ID ' . $expense->branch_id . ' tidak ditemukan, menggunakan ID ' . $branchId);
            } else {
                throw new \Exception('Tidak ada cabang di database.');
            }
        }

        if (!User::find($createdBy)) {
            $userModel = User::first();
            if ($userModel) {
                $createdBy = $userModel->id;
                Log::warning('User ID ' . $user->id . ' tidak ditemukan, menggunakan ID ' . $createdBy);
            } else {
                throw new \Exception('Tidak ada user di database.');
            }
        }

        // 🔥 Ambil no_resi dari delivery task
        $noResi = null;
        if ($expense->delivery_task_id) {
            $deliveryTask = \App\Models\DeliveryTask::find($expense->delivery_task_id);
            if ($deliveryTask) {
                $noResi = $deliveryTask->no_resi;
            }
        }

        $financialData = [
            'transaction_date' => $expense->transaction_date ?? now(),
            'type'             => 'expense',
            'category'         => $expense->type,
            'amount'           => $expense->amount,
            'description'      => 'Biaya operasional: ' . ($expense->description ?? $expense->type),
            'vehicle_id'       => $vehicleId,
            'driver_id'        => $driverId,
            'status'           => 'confirmed',
            'created_by'       => $createdBy,
            'branch_id'        => $branchId,
            // 🔥 Isi po_number dengan no_resi, maintenance_number = null
            'po_number'        => $noResi,
            'maintenance_number' => null,
        ];

        // Tambahkan reference jika kolom tersedia
        $columns = Schema::getColumnListing('financial_transactions');
        if (in_array('reference_type', $columns) && in_array('reference_id', $columns)) {
            $financialData['reference_type'] = 'FuelExpense';
            $financialData['reference_id']   = $expense->id;
        }

        return FinancialTransaction::create($financialData);
    }

    /**
     * Display the specified fuel expense.
     */
    public function show($id)
    {
        try {
            $expense = FuelExpense::with(['deliveryTask', 'vehicle', 'driver', 'creator', 'approver'])
                ->findOrFail($id);

            $user = auth()->user();
            if ($user->role !== 'super_admin' && $expense->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke pengajuan ini'], 403);
            }

            return response()->json(['data' => $expense]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Pengajuan tidak ditemukan'], 404);
        }
    }

    /**
     * Update the specified fuel expense.
     */
    public function update(Request $request, $id)
    {
        try {
            $user = auth()->user();
            $expense = FuelExpense::findOrFail($id);

            if ($user->role !== 'super_admin' && $expense->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke pengajuan ini'], 403);
            }

            if ($expense->status !== 'pending') {
                return response()->json(['message' => 'Pengajuan yang sudah diproses tidak dapat diedit'], 400);
            }

            $validator = Validator::make($request->all(), [
                'delivery_task_id' => 'sometimes|exists:delivery_tasks,id',
                'type'             => 'sometimes|in:bahan_bakar,toll,parkir,lainnya',
                'amount'           => 'sometimes|numeric|min:0',
                'vehicle_id'       => 'sometimes|exists:vehicles,id',
                'driver_id'        => 'sometimes|exists:drivers,id',
                'transaction_date' => 'sometimes|date',
                'description'      => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $expense->update($request->all());
            return response()->json([
                'message' => 'Pengajuan berhasil diperbarui',
                'data'    => $expense
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating fuel expense: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui pengajuan'], 500);
        }
    }

    /**
     * Remove the specified fuel expense.
     * Hanya Super Admin yang dapat menghapus (agar No. Resi bisa diajukan kembali).
     */
    public function destroy($id)
    {
        try {
            $user = auth()->user();

            if ($user->role !== 'super_admin') {
                return response()->json(['message' => 'Hanya Super Admin yang dapat menghapus pengajuan'], 403);
            }

            $expense = FuelExpense::findOrFail($id);
            $expense->delete();

            // Hapus juga transaksi keuangan terkait (jika ada)
            FinancialTransaction::where('reference_type', 'FuelExpense')
                ->where('reference_id', $id)
                ->delete();

            return response()->json(['message' => 'Pengajuan berhasil dihapus']);
        } catch (\Exception $e) {
            Log::error('Error deleting fuel expense: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menghapus pengajuan'], 500);
        }
    }

    /**
     * Export fuel expenses to Excel with filters.
     */
    public function export(Request $request)
    {
        try {
            if (!Gate::allows('exportModule', 'fuel-expenses')) {
                return response()->json(['message' => 'Anda tidak memiliki izin untuk mengekspor data ini.'], 403);
            }

            $query = FuelExpense::with(['deliveryTask', 'vehicle', 'driver']);
            $this->applyBranchFilter($query);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('unique_code', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%")
                      ->orWhere('type', 'LIKE', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            if ($request->filled('branch_id') && auth()->user()->role === 'super_admin') {
                $query->where('branch_id', $request->branch_id);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('transaction_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('transaction_date', '<=', $request->date_to);
            }

            $query->orderBy('id', 'desc');
            $filename = 'fuel_expenses_' . date('Y-m-d_His') . '.xlsx';

            return Excel::download(new FuelExpensesExport($query), $filename);
        } catch (\Exception $e) {
            Log::error('Error exporting fuel expenses: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal ekspor data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Import fuel expenses from CSV (placeholder).
     */
    public function import(Request $request)
    {
        return response()->json(['message' => 'Import belum diimplementasikan'], 501);
    }
}