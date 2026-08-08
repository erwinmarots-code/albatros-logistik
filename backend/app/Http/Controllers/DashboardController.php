<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\Client;
use App\Models\FinancialTransaction;
use App\Models\MaintenanceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Total data
        $totalVehicles = Vehicle::count();
        $totalDrivers = Driver::count();
        $totalClients = Client::count();

        // Keuangan (bulan ini)
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $income = FinancialTransaction::where('type', 'income')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $expense = FinancialTransaction::where('type', 'expense')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // Grafik pengajuan per bulan (6 bulan terakhir)
        $maintenanceRequests = MaintenanceRequest::select(
            DB::raw('YEAR(created_at) as year'),
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as total')
        )
        ->where('created_at', '>=', now()->subMonths(6))
        ->groupBy('year', 'month')
        ->orderBy('year')
        ->orderBy('month')
        ->get();

        $chartData = [];
        foreach ($maintenanceRequests as $item) {
            $monthName = date('M Y', mktime(0,0,0, $item->month, 1, $item->year));
            $chartData[] = [
                'month' => $monthName,
                'total' => $item->total,
            ];
        }

        // Pengajuan perawatan pending
        $pendingRequests = MaintenanceRequest::where('status', 'pending')->count();

        return response()->json([
            'total_vehicles' => $totalVehicles,
            'total_drivers' => $totalDrivers,
            'total_clients' => $totalClients,
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
            'pending_requests' => $pendingRequests,
            'chart_data' => $chartData,
        ]);
    }
}