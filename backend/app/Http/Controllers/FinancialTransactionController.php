<?php

namespace App\Http\Controllers;

use App\Models\FinancialTransaction;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Traits\BranchScopeTrait;
use App\Exports\FinancialTransactionsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Gate;

class FinancialTransactionController extends Controller
{
    use BranchScopeTrait;

    public function index(Request $request)
    {
        try {
            $query = FinancialTransaction::with(['vehicle', 'driver', 'client', 'creator']);
            $this->applyBranchFilter($query);

            if ($request->has('from')) {
                $query->whereDate('transaction_date', '>=', $request->from);
            }
            if ($request->has('to')) {
                $query->whereDate('transaction_date', '<=', $request->to);
            }
            if ($request->has('type') && in_array($request->type, ['income', 'expense'])) {
                $query->where('type', $request->type);
            }
            if ($request->has('category')) {
                $query->where('category', 'LIKE', '%' . $request->category . '%');
            }
            if ($request->has('vehicle_id')) {
                $query->where('vehicle_id', $request->vehicle_id);
            }
            if ($request->has('limit')) {
                $query->limit($request->limit);
            }

            $transactions = $query->orderBy('transaction_date', 'desc')->get();
            return response()->json(['data' => $transactions]);
        } catch (\Exception $e) {
            Log::error('Error fetching transactions: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memuat transaksi'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'transaction_date' => 'required|date',
                'type' => 'required|in:income,expense',
                'category' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0',
                'description' => 'nullable|string',
                'vehicle_id' => 'nullable|exists:vehicles,id',
                'driver_id' => 'nullable|exists:drivers,id',
                'client_id' => 'nullable|exists:clients,id',
                'po_number' => 'nullable|string|max:100',
                'maintenance_number' => 'nullable|string|max:100',
                'reference_type' => 'nullable|string|max:50',
                'reference_id' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $request->all();
            $data['created_by'] = $user->id ?? 1;
            $data['status'] = 'confirmed';

            if ($user->role !== 'super_admin') {
                $data['branch_id'] = $user->branch_id;
            }

            $transaction = FinancialTransaction::create($data);
            return response()->json([
                'message' => 'Transaksi berhasil ditambahkan',
                'data' => $transaction
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating transaction: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menambahkan transaksi: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $transaction = FinancialTransaction::with(['vehicle', 'driver', 'client', 'creator'])
                ->findOrFail($id);

            $user = auth()->user();
            if ($user->role !== 'super_admin' && $transaction->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke transaksi ini'], 403);
            }

            return response()->json(['data' => $transaction]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = auth()->user();
            $transaction = FinancialTransaction::findOrFail($id);

            if ($user->role !== 'super_admin' && $transaction->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke transaksi ini'], 403);
            }

            $validator = Validator::make($request->all(), [
                'transaction_date' => 'sometimes|date',
                'type' => 'sometimes|in:income,expense',
                'category' => 'sometimes|string|max:255',
                'amount' => 'sometimes|numeric|min:0',
                'description' => 'nullable|string',
                'vehicle_id' => 'nullable|exists:vehicles,id',
                'driver_id' => 'nullable|exists:drivers,id',
                'client_id' => 'nullable|exists:clients,id',
                'po_number' => 'nullable|string|max:100',
                'maintenance_number' => 'nullable|string|max:100',
                'reference_type' => 'nullable|string|max:50',
                'reference_id' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $transaction->update($request->all());
            return response()->json([
                'message' => 'Transaksi berhasil diperbarui',
                'data' => $transaction
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating transaction: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui transaksi'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = auth()->user();
            $transaction = FinancialTransaction::findOrFail($id);

            if ($user->role !== 'super_admin' && $transaction->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke transaksi ini'], 403);
            }

            $transaction->delete();
            return response()->json(['message' => 'Transaksi berhasil dihapus']);
        } catch (\Exception $e) {
            Log::error('Error deleting transaction: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menghapus transaksi'], 500);
        }
    }

    public function summary(Request $request)
    {
        try {
            $user = auth()->user();
            $from = $request->from ?? now()->startOfMonth()->toDateString();
            $to = $request->to ?? now()->endOfMonth()->toDateString();

            $query = FinancialTransaction::whereBetween('transaction_date', [$from, $to]);
            $this->applyBranchFilter($query);

            $totalIncome = (clone $query)->where('type', 'income')->sum('amount');
            $totalExpense = (clone $query)->where('type', 'expense')->sum('amount');

            $byCategory = (clone $query)
                ->selectRaw('category, type, SUM(amount) as total')
                ->groupBy('category', 'type')
                ->get();

            $outstandingCount = 0;
            try {
                $invoiceQuery = Invoice::where('status', '!=', 'paid')
                    ->where('status', '!=', 'cancelled');
                if ($user->role !== 'super_admin') {
                    $invoiceQuery->where('branch_id', $user->branch_id);
                }
                $outstandingCount = $invoiceQuery->count();
            } catch (\Exception $e) {
                Log::warning('Invoice table not found, skipping outstanding count.');
            }

            return response()->json([
                'period' => ['from' => $from, 'to' => $to],
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'balance' => $totalIncome - $totalExpense,
                'by_category' => $byCategory,
                'outstanding_count' => $outstandingCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating summary: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memuat ringkasan'], 500);
        }
    }

    public function chartData()
    {
        try {
            $user = auth()->user();
            $months = collect();
            for ($i = 5; $i >= 0; $i--) {
                $months->push(now()->subMonths($i)->format('Y-m'));
            }

            $labels = [];
            $income = [];
            $expense = [];

            foreach ($months as $month) {
                [$year, $monthNum] = explode('-', $month);

                $query = FinancialTransaction::query();
                $this->applyBranchFilter($query);

                $incomeTotal = (clone $query)
                    ->whereYear('transaction_date', $year)
                    ->whereMonth('transaction_date', $monthNum)
                    ->where('type', 'income')
                    ->sum('amount') ?? 0;

                $expenseTotal = (clone $query)
                    ->whereYear('transaction_date', $year)
                    ->whereMonth('transaction_date', $monthNum)
                    ->where('type', 'expense')
                    ->sum('amount') ?? 0;

                $labels[] = now()->setYear((int)$year)->setMonth((int)$monthNum)->translatedFormat('M Y');
                $income[] = $incomeTotal;
                $expense[] = $expenseTotal;
            }

            return response()->json([
                'labels' => $labels,
                'income' => $income,
                'expense' => $expense,
            ]);
        } catch (\Exception $e) {
            Log::error('Chart data error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memuat data grafik'], 500);
        }
    }

    /**
     * Export financial transactions to Excel dengan filter
     * 🔥 Method baru untuk export Excel
     */
    public function export(Request $request)
    {
        try {
            if (!Gate::allows('exportModule', 'financials')) {
                return response()->json(['message' => 'Anda tidak memiliki izin untuk mengekspor data ini.'], 403);
            }

            $query = FinancialTransaction::with(['vehicle', 'driver', 'client']);
            $this->applyBranchFilter($query);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'LIKE', "%{$search}%")
                      ->orWhere('category', 'LIKE', "%{$search}%")
                      ->orWhere('po_number', 'LIKE', "%{$search}%");
                });
            }

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
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
            $filename = 'financials_' . date('Y-m-d_His') . '.xlsx';

            return Excel::download(new FinancialTransactionsExport($query), $filename);

        } catch (\Exception $e) {
            Log::error('Error exporting financial transactions: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal ekspor data: ' . $e->getMessage()], 500);
        }
    }
}