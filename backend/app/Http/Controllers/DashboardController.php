<?php

namespace App\Http\Controllers;

use App\Models\ShippingProject;
use App\Models\DeliveryTask;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\FuelExpense;
use App\Models\MaintenanceRequest;
use App\Models\FinancialTransaction;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Traits\BranchScopeTrait;

class DashboardController extends Controller
{
    use BranchScopeTrait;

    /**
     * Get main dashboard statistics.
     */
    public function stats()
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $role = $user->role;
            $isSuperAdmin = ($role === 'super_admin');
            $data = [];

            // ============================================================
            // 1. DATA UMUM (Semua Role) - dengan filter cabang
            // ============================================================
            $queryProjects = ShippingProject::query();
            $queryVehicles = Vehicle::query();
            $queryDrivers = Driver::query();
            $queryClients = Client::query();
            $queryTasks = DeliveryTask::query();

            if (!$isSuperAdmin) {
                $queryProjects->where('branch_id', $user->branch_id);
                $queryVehicles->where('branch_id', $user->branch_id);
                $queryDrivers->where('branch_id', $user->branch_id);
                $queryClients->where('branch_id', $user->branch_id);
                $queryTasks->where('branch_id', $user->branch_id);
            }

            $data['total_vehicles'] = $queryVehicles->count();
            $data['total_drivers'] = $queryDrivers->count();
            $data['total_clients'] = $queryClients->count();
            $data['total_projects'] = $queryProjects->count();
            $data['total_tasks'] = $queryTasks->count();

            // ============================================================
            // 2. DATA PENGAJUAN BIAYA & PERAWATAN
            // ============================================================
            $queryFuel = FuelExpense::query();
            $queryMaintenance = MaintenanceRequest::query();

            if (!$isSuperAdmin) {
                $queryFuel->whereHas('vehicle', function ($q) use ($user) {
                    $q->where('branch_id', $user->branch_id);
                });
                $queryMaintenance->whereHas('vehicle', function ($q) use ($user) {
                    $q->where('branch_id', $user->branch_id);
                });
            }

            $data['pending_fuel'] = (clone $queryFuel)->where('status', 'pending')->count();
            $data['approved_fuel'] = (clone $queryFuel)->where('status', 'approved')->count();
            $data['rejected_fuel'] = (clone $queryFuel)->where('status', 'rejected')->count();

            $data['pending_maintenance'] = (clone $queryMaintenance)->where('status', 'pending')->count();
            $data['approved_maintenance'] = (clone $queryMaintenance)->where('status', 'approved')->count();
            $data['rejected_maintenance'] = (clone $queryMaintenance)->where('status', 'rejected')->count();

            // ============================================================
            // 3. DATA UNTUK SUPER ADMIN / ADMIN FINANCE
            // ============================================================
            if ($role === 'super_admin' || $role === 'admin_finance') {
                $queryFin = FinancialTransaction::query();
                if (!$isSuperAdmin) {
                    $queryFin->where('branch_id', $user->branch_id);
                }

                // 3a. Pendapatan & Pengeluaran Bulan Ini
                $data['monthly_income'] = (clone $queryFin)
                    ->whereMonth('transaction_date', now()->month)
                    ->whereYear('transaction_date', now()->year)
                    ->where('type', 'income')
                    ->sum('amount') ?? 0;

                $data['monthly_expense'] = (clone $queryFin)
                    ->whereMonth('transaction_date', now()->month)
                    ->whereYear('transaction_date', now()->year)
                    ->where('type', 'expense')
                    ->sum('amount') ?? 0;

                $data['monthly_balance'] = $data['monthly_income'] - $data['monthly_expense'];

                // 3b. Outstanding PO / Invoice
                $queryInvoice = Invoice::query();
                if (!$isSuperAdmin) {
                    $queryInvoice->where('branch_id', $user->branch_id);
                }
                $queryInvoice->where('status', '!=', 'paid')
                    ->where('status', '!=', 'cancelled');

                $data['outstanding_invoices'] = (clone $queryInvoice)
                    ->with(['shippingProject.client'])
                    ->latest()
                    ->limit(10)
                    ->get()
                    ->map(function ($invoice) {
                        return [
                            'id' => $invoice->id,
                            'invoice_number' => $invoice->invoice_number,
                            'client' => $invoice->shippingProject?->client?->name ?? '-',
                            'total_amount' => $invoice->total_amount,
                            'due_date' => $invoice->due_date,
                            'status' => $invoice->status,
                            'days_overdue' => $invoice->due_date ? max(0, now()->diffInDays($invoice->due_date)) : 0,
                        ];
                    });

                $data['outstanding_count'] = $queryInvoice->count();
                $data['outstanding_total'] = $queryInvoice->sum('total_amount') ?? 0;

                // 3c. Transaksi Terakhir
                $queryRecent = FinancialTransaction::with(['vehicle', 'driver', 'client']);
                if (!$isSuperAdmin) {
                    $queryRecent->where('branch_id', $user->branch_id);
                }
                $data['recent_transactions'] = $queryRecent
                    ->latest()
                    ->limit(10)
                    ->get()
                    ->map(function ($tx) {
                        return [
                            'id' => $tx->id,
                            'transaction_date' => $tx->transaction_date->format('d-m-Y'),
                            'type' => $tx->type,
                            'category' => $tx->category,
                            'amount' => $tx->amount,
                            'description' => $tx->description,
                            'vehicle' => $tx->vehicle?->plate_number ?? '-',
                            'driver' => $tx->driver?->name ?? '-',
                            'client' => $tx->client?->name ?? '-',
                        ];
                    });

                // 3d. Proyek berdasarkan status
                $queryProjectStatus = ShippingProject::query();
                if (!$isSuperAdmin) {
                    $queryProjectStatus->where('branch_id', $user->branch_id);
                }
                $data['projects_by_status'] = $queryProjectStatus
                    ->select('status', DB::raw('count(*) as total'))
                    ->groupBy('status')
                    ->pluck('total', 'status')
                    ->toArray();
            }

            // ============================================================
            // 4. DATA TERBARU (Untuk Semua Role)
            // ============================================================
            $queryRecentProjects = ShippingProject::with(['client', 'branch']);
            if (!$isSuperAdmin) {
                $queryRecentProjects->where('branch_id', $user->branch_id);
            }
            $data['recent_projects'] = $queryRecentProjects
                ->latest()
                ->limit(5)
                ->get()
                ->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'no_po' => $p->no_po,
                        'no_resi' => $p->no_resi,
                        'client' => $p->client?->name ?? '-',
                        'branch' => $p->branch?->code ?? '-',
                        'status' => $p->status,
                        'contract_value' => $p->contract_value,
                        'created_at' => $p->created_at->format('d-m-Y'),
                    ];
                });

            $queryRecentTasks = DeliveryTask::with(['project', 'vehicle', 'driver', 'client']);
            if (!$isSuperAdmin) {
                $queryRecentTasks->where('branch_id', $user->branch_id);
            }
            $data['recent_tasks'] = $queryRecentTasks
                ->latest()
                ->limit(5)
                ->get()
                ->map(function ($t) {
                    return [
                        'id' => $t->id,
                        'no_resi' => $t->no_resi,
                        'project' => $t->project?->no_po ?? '-',
                        'client' => $t->client?->name ?? '-',
                        'vehicle' => $t->vehicle?->plate_number ?? '-',
                        'driver' => $t->driver?->name ?? '-',
                        'status' => $t->status,
                        'tanggal' => $t->tanggal?->format('d-m-Y') ?? '-',
                    ];
                });

            $queryFuelRecent = FuelExpense::with(['deliveryTask.project', 'vehicle', 'driver'])
                ->where('status', 'pending');
            if (!$isSuperAdmin) {
                $queryFuelRecent->whereHas('vehicle', function ($q) use ($user) {
                    $q->where('branch_id', $user->branch_id);
                });
            }
            $data['recent_fuel_requests'] = $queryFuelRecent
                ->latest()
                ->limit(5)
                ->get()
                ->map(function ($f) {
                    return [
                        'id' => $f->id,
                        'unique_code' => $f->unique_code,
                        'no_resi' => $f->deliveryTask?->no_resi ?? '-',
                        'type' => $f->type,
                        'amount' => $f->amount,
                        'vehicle' => $f->vehicle?->plate_number ?? '-',
                        'driver' => $f->driver?->name ?? '-',
                        'status' => $f->status,
                    ];
                });

            $queryRecentDrivers = Driver::query();
            if (!$isSuperAdmin) {
                $queryRecentDrivers->where('branch_id', $user->branch_id);
            }
            $data['recent_drivers'] = $queryRecentDrivers->latest()->limit(5)->get();

            // ============================================================
            // 5. NOTIFIKASI
            // ============================================================
            $queryNotifFuel = FuelExpense::query()->where('status', 'pending');
            $queryNotifMaintenance = MaintenanceRequest::query()->where('status', 'pending');
            $queryNotifInvoice = Invoice::query()
                ->where('status', '!=', 'paid')
                ->where('status', '!=', 'cancelled');

            if (!$isSuperAdmin) {
                $queryNotifFuel->whereHas('vehicle', function ($q) use ($user) {
                    $q->where('branch_id', $user->branch_id);
                });
                $queryNotifMaintenance->whereHas('vehicle', function ($q) use ($user) {
                    $q->where('branch_id', $user->branch_id);
                });
                $queryNotifInvoice->where('branch_id', $user->branch_id);
            }

            $data['notifications'] = [
                'pending_fuel' => $queryNotifFuel->count(),
                'pending_maintenance' => $queryNotifMaintenance->count(),
                'outstanding_invoices' => $queryNotifInvoice->count(),
            ];

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('Dashboard stats fatal error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'message' => 'Gagal memuat statistik dashboard',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get monthly income & expense for chart (6 bulan terakhir).
     */
    public function monthlyStats()
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

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
                if ($user->role !== 'super_admin') {
                    $query->where('branch_id', $user->branch_id);
                }

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
            Log::error('Monthly stats error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal memuat data grafik',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function branchStats()
    {
        try {
            $branches = \App\Models\Branch::withCount(['shippingProjects', 'clients', 'users'])
                ->get();
            return response()->json(['data' => $branches]);
        } catch (\Exception $e) {
            Log::error('Branch stats error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal memuat statistik cabang',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function recentTransactions()
    {
        try {
            $user = auth()->user();
            $query = FinancialTransaction::with(['vehicle', 'driver', 'client']);
            if ($user->role !== 'super_admin') {
                $query->where('branch_id', $user->branch_id);
            }
            $transactions = $query->latest()->limit(10)->get();
            return response()->json(['data' => $transactions]);
        } catch (\Exception $e) {
            Log::error('Recent transactions error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal memuat transaksi terbaru',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}