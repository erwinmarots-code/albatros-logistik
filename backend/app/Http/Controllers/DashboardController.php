<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\ShippingProject;
use App\Models\DeliveryTask;
use App\Models\FinancialTransaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats()
    {
        try {
            $totalVehicles = Vehicle::count();
            $totalDrivers = Driver::count();
            $totalProjects = ShippingProject::count();
            $totalTasks = DeliveryTask::count();
            $completedProjects = ShippingProject::where('status', 'completed')->count();

            $monthlyIncome = FinancialTransaction::where('type', 'income')
                ->whereMonth('transaction_date', now()->month)
                ->whereYear('transaction_date', now()->year)
                ->sum('amount');

            return response()->json([
                'total_vehicles' => $totalVehicles,
                'total_drivers'  => $totalDrivers,
                'total_projects' => $totalProjects,
                'total_tasks'    => $totalTasks,
                'completed_projects' => $completedProjects,
                'monthly_income' => $monthlyIncome,
                'income_change'  => 0,
                'expense_change' => 0,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching stats: ' . $e->getMessage()
            ], 500);
        }
    }

    public function chart()
    {
        try {
            $months = collect(range(5, 0))->map(function ($i) {
                return now()->subMonths($i)->format('M');
            });

            $income = collect(range(5, 0))->map(function ($i) {
                $date = now()->subMonths($i);
                return FinancialTransaction::where('type', 'income')
                    ->whereMonth('transaction_date', $date->month)
                    ->whereYear('transaction_date', $date->year)
                    ->sum('amount');
            });

            $expense = collect(range(5, 0))->map(function ($i) {
                $date = now()->subMonths($i);
                return FinancialTransaction::where('type', 'expense')
                    ->whereMonth('transaction_date', $date->month)
                    ->whereYear('transaction_date', $date->year)
                    ->sum('amount');
            });

            return response()->json([
                'labels' => $months,
                'income' => $income,
                'expense' => $expense,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching chart data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function recentTransactions()
    {
        try {
            $transactions = FinancialTransaction::with(['vehicle', 'client'])
                ->orderBy('transaction_date', 'desc')
                ->limit(5)
                ->get();
            return response()->json(['data' => $transactions]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching recent transactions: ' . $e->getMessage()
            ], 500);
        }
    }
}