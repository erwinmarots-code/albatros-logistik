<?php

namespace App\Http\Controllers;

use App\Models\FinancialTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FinancialTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = FinancialTransaction::with(['vehicle', 'driver', 'client', 'creator']);

        if ($request->from) {
            $query->whereDate('transaction_date', '>=', $request->from);
        }
        if ($request->to) {
            $query->whereDate('transaction_date', '<=', $request->to);
        }
        if ($request->type && in_array($request->type, ['income', 'expense'])) {
            $query->where('type', $request->type);
        }
        if ($request->category) {
            $query->where('category', $request->category);
        }
        if ($request->vehicle_id) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')->get();
        return response()->json(['data' => $transactions]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_date' => 'required|date',
            'type'             => 'required|in:income,expense',
            'category'         => 'required|in:service,fuel,toll,parking,salary,client_payment,other',
            'amount'           => 'required|numeric|min:0',
            'description'      => 'nullable|string',
            'vehicle_id'       => 'nullable|exists:vehicles,id',
            'driver_id'        => 'nullable|exists:drivers,id',
            'client_id'        => 'nullable|exists:clients,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['created_by'] = auth()->id() ?? 1; // sementara
        $data['status'] = 'confirmed';

        $transaction = FinancialTransaction::create($data);
        return response()->json(['message' => 'Transaksi berhasil ditambahkan', 'data' => $transaction], 201);
    }

    public function show($id)
    {
        $transaction = FinancialTransaction::with(['vehicle', 'driver', 'client', 'creator'])->findOrFail($id);
        return response()->json(['data' => $transaction]);
    }

    public function update(Request $request, $id)
    {
        $transaction = FinancialTransaction::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'transaction_date' => 'sometimes|date',
            'type'             => 'sometimes|in:income,expense',
            'category'         => 'sometimes|in:service,fuel,toll,parking,salary,client_payment,other',
            'amount'           => 'sometimes|numeric|min:0',
            'description'      => 'nullable|string',
            'vehicle_id'       => 'nullable|exists:vehicles,id',
            'driver_id'        => 'nullable|exists:drivers,id',
            'client_id'        => 'nullable|exists:clients,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $transaction->update($request->all());
        return response()->json(['message' => 'Transaksi berhasil diupdate', 'data' => $transaction]);
    }

    public function destroy($id)
    {
        $transaction = FinancialTransaction::findOrFail($id);
        $transaction->delete();
        return response()->json(['message' => 'Transaksi berhasil dihapus']);
    }

    public function summary(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->endOfMonth()->toDateString();

        $query = FinancialTransaction::whereBetween('transaction_date', [$from, $to]);

        $totalIncome = (clone $query)->where('type', 'income')->sum('amount');
        $totalExpense = (clone $query)->where('type', 'expense')->sum('amount');

        $byCategory = FinancialTransaction::whereBetween('transaction_date', [$from, $to])
            ->selectRaw('category, type, SUM(amount) as total')
            ->groupBy('category', 'type')
            ->get();

        return response()->json([
            'period' => ['from' => $from, 'to' => $to],
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'balance' => $totalIncome - $totalExpense,
            'by_category' => $byCategory,
        ]);
    }
}