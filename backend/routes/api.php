<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\MaintenanceScheduleController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\ShippingProjectController;
use App\Http\Controllers\DeliveryTaskController;
use App\Http\Controllers\FuelExpenseController;
use App\Http\Controllers\FinancialTransactionController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\DashboardController;
use App\Exports\VehiclesExport;
use App\Exports\DriversExport;
use App\Exports\ClientsExport;
use App\Exports\FinancialTransactionsExport;
use Maatwebsite\Excel\Facades\Excel;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// =============================================
// ROUTE PUBLIK (Tidak memerlukan autentikasi)
// =============================================
Route::post('/login', [AuthController::class, 'login']);

// =============================================
// ROUTE YANG MEMERLUKAN AUTENTIKASI (SANCTUM)
// =============================================
Route::middleware('auth:sanctum')->group(function () {

    // --- Autentikasi ---
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // =============================================
    // DASHBOARD
    // =============================================
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/chart', [DashboardController::class, 'chart']);
    Route::get('/dashboard/recent-transactions', [DashboardController::class, 'recentTransactions']);

    // =============================================
    // MASTER DATA
    // =============================================
    Route::apiResource('vehicles', VehicleController::class);
    Route::apiResource('drivers', DriverController::class);
    Route::apiResource('clients', ClientController::class);

    // =============================================
    // MAINTENANCE SCHEDULES
    // =============================================
    Route::apiResource('maintenance-schedules', MaintenanceScheduleController::class);

    // =============================================
    // MAINTENANCE REQUESTS (Pengajuan Perawatan)
    // =============================================
    Route::apiResource('maintenance-requests', MaintenanceRequestController::class);

    // Approve/Reject/Execute (khusus Admin Finance & Super Admin)
    Route::post('maintenance-requests/{id}/approve', [MaintenanceRequestController::class, 'approve'])
        ->middleware('role:admin_finance,super_admin');
    Route::post('maintenance-requests/{id}/reject', [MaintenanceRequestController::class, 'reject'])
        ->middleware('role:admin_finance,super_admin');
    Route::post('maintenance-requests/{id}/execute', [MaintenanceRequestController::class, 'markAsExecuted'])
        ->middleware('role:admin_finance,super_admin');

    // =============================================
    // SHIPPING PROJECT
    // =============================================
    Route::apiResource('shipping-projects', ShippingProjectController::class);

    // =============================================
    // DELIVERY TASK
    // =============================================
    Route::apiResource('delivery-tasks', DeliveryTaskController::class);
    Route::patch('delivery-tasks/{id}/status', [DeliveryTaskController::class, 'updateStatus']);

    // =============================================
    // FUEL EXPENSE (Pengajuan Biaya Operasional)
    // =============================================
    Route::apiResource('fuel-expenses', FuelExpenseController::class);
    Route::post('fuel-expenses/{id}/approve', [FuelExpenseController::class, 'approve'])
        ->middleware('role:admin_finance,super_admin');
    Route::post('fuel-expenses/{id}/reject', [FuelExpenseController::class, 'reject'])
        ->middleware('role:admin_finance,super_admin');

    // =============================================
    // FINANCIAL
    // =============================================
    Route::apiResource('financial-transactions', FinancialTransactionController::class)
        ->middleware('role:admin_finance,super_admin');
    Route::get('financial-summary', [FinancialTransactionController::class, 'summary'])
        ->middleware('role:admin_finance,super_admin');

    // =============================================
    // INVOICE
    // =============================================
    Route::apiResource('invoices', InvoiceController::class)
        ->middleware('role:admin_finance,super_admin');
    Route::get('available-projects', [InvoiceController::class, 'getAvailableProjects'])
        ->middleware('role:admin_finance,super_admin');
    Route::get('invoices/{id}/detail', [InvoiceController::class, 'detail'])
        ->middleware('role:admin_finance,super_admin');

    // =============================================
    // EXPORT EXCEL
    // =============================================
    Route::get('/export/vehicles', function () {
        return Excel::download(new VehiclesExport, 'kendaraan.xlsx');
    });
    Route::get('/export/drivers', function () {
        return Excel::download(new DriversExport, 'drivers.xlsx');
    });
    Route::get('/export/clients', function () {
        return Excel::download(new ClientsExport, 'clients.xlsx');
    });
    Route::get('/export/financial-transactions', function (Request $request) {
        $from = $request->query('from');
        $to = $request->query('to');
        return Excel::download(new FinancialTransactionsExport($from, $to), 'laporan_keuangan.xlsx');
    });

    // =============================================
    // ROUTE KHUSUS SUPER ADMIN
    // =============================================
    Route::get('/admin-only', function () {
        return response()->json(['message' => 'Hanya untuk Super Admin']);
    })->middleware('role:super_admin');

});