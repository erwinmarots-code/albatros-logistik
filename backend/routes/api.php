<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ShippingProjectController;
use App\Http\Controllers\DeliveryTaskController;
use App\Http\Controllers\FuelExpenseController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\MaintenanceScheduleController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\FinancialTransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SparePartController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\PermissionController; // 🔥 Tambahkan ini nanti

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// ================================================================
// 🔓 PUBLIC ROUTES (tanpa autentikasi)
// ================================================================
Route::post('/login', [AuthController::class, 'login']);

// ================================================================
// 🔒 PROTECTED ROUTES (memerlukan autentikasi Sanctum)
// ================================================================
Route::middleware('auth:sanctum')->group(function () {

    // ============================================================
    // AUTH
    // ============================================================
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // 🔥 UPDATE PASSWORD (Semua user bisa ganti password sendiri)
    Route::post('/update-password', [AuthController::class, 'updatePassword']);

    // ============================================================
    // DASHBOARD
    // ============================================================
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/monthly', [DashboardController::class, 'monthlyStats']);
    Route::get('/dashboard/branch-stats', [DashboardController::class, 'branchStats']);
    Route::get('/dashboard/recent-transactions', [DashboardController::class, 'recentTransactions']);

    // ============================================================
    // BRANCHES (Cabang)
    // ============================================================
    Route::get('/branches', [BranchController::class, 'index']);
    Route::post('/branches', [BranchController::class, 'store']);
    Route::get('/branches/{branch}', [BranchController::class, 'show']);
    Route::put('/branches/{branch}', [BranchController::class, 'update']);
    Route::delete('/branches/{branch}', [BranchController::class, 'destroy']);

    // ============================================================
    // 🔥 ROUTE EKSPOR EXCEL – HARUS DI ATAS ROUTE {id} (apiResource)
    // ============================================================
    // Data Master
    Route::get('/clients/export', [ClientController::class, 'export']);
    Route::get('/vehicles/export', [VehicleController::class, 'export']);
    Route::get('/drivers/export', [DriverController::class, 'export']);

    // Operasional
    Route::get('/projects/export', [ShippingProjectController::class, 'export']);
    Route::get('/delivery-tasks/export', [DeliveryTaskController::class, 'export']);

    // Keuangan
    Route::get('/financial-transactions/export', [FinancialTransactionController::class, 'export']);
    Route::get('/invoices/export', [InvoiceController::class, 'export']);
    Route::get('/fuel-expenses/export', [FuelExpenseController::class, 'export']);

    // Inventory & Perawatan
    Route::get('/spare-parts/export', [SparePartController::class, 'export']);
    Route::get('/maintenance-requests/export', [MaintenanceRequestController::class, 'export']);

    // ============================================================
    // CLIENTS (Data Master)
    // ============================================================
    Route::apiResource('clients', ClientController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::post('/clients/import', [ClientController::class, 'import']);

    // ============================================================
    // VEHICLES (Data Master)
    // ============================================================
    Route::apiResource('vehicles', VehicleController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::post('/vehicles/import', [VehicleController::class, 'import']);

    // ============================================================
    // DRIVERS (Data Master)
    // ============================================================
    Route::apiResource('drivers', DriverController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::post('/drivers/import', [DriverController::class, 'import']);

    // ============================================================
    // SHIPPING PROJECTS (Operasional)
    // ============================================================
    Route::get('/projects/po-list', [ShippingProjectController::class, 'poList']);
    Route::post('/projects/import', [ShippingProjectController::class, 'import']);
    Route::patch('/projects/{id}/status', [ShippingProjectController::class, 'updateStatus']);
    Route::get('/generate-resi', [ShippingProjectController::class, 'generateResi']);
    Route::apiResource('projects', ShippingProjectController::class);

    // ============================================================
    // DELIVERY TASKS (Operasional)
    // ============================================================
    Route::patch('/delivery-tasks/{id}/status', [DeliveryTaskController::class, 'updateStatus']);
    Route::post('/delivery-tasks/import', [DeliveryTaskController::class, 'import']);
    Route::apiResource('delivery-tasks', DeliveryTaskController::class);

    // ============================================================
    // FUEL EXPENSES (Keuangan)
    // ============================================================
    Route::post('/fuel-expenses/{id}/approve', [FuelExpenseController::class, 'approve']);
    Route::post('/fuel-expenses/{id}/reject', [FuelExpenseController::class, 'reject']);
    Route::post('/fuel-expenses/import', [FuelExpenseController::class, 'import']);
    Route::apiResource('fuel-expenses', FuelExpenseController::class);

    // ============================================================
    // MAINTENANCE REQUESTS (Perawatan)
    // ============================================================
    Route::post('/maintenance-requests/{id}/approve', [MaintenanceRequestController::class, 'approve']);
    Route::post('/maintenance-requests/{id}/reject', [MaintenanceRequestController::class, 'reject']);
    Route::post('/maintenance-requests/{id}/execute', [MaintenanceRequestController::class, 'markAsExecuted']);
    Route::post('/maintenance-requests/import', [MaintenanceRequestController::class, 'import']);
    Route::apiResource('maintenance-requests', MaintenanceRequestController::class);

    // ============================================================
    // MAINTENANCE SCHEDULES (Perawatan)
    // ============================================================
    Route::apiResource('maintenance-schedules', MaintenanceScheduleController::class);

    // ============================================================
    // SPARE PARTS (Inventory)
    // ============================================================
    Route::apiResource('spare-parts', SparePartController::class);
    Route::post('/spare-parts/{id}/restock', [SparePartController::class, 'restock']);
    Route::post('/spare-parts/{id}/mark-unusable', [SparePartController::class, 'markUnusable']);
    Route::get('/spare-parts/{id}/movements', [SparePartController::class, 'movements']);
    Route::post('/spare-parts/import', [SparePartController::class, 'import']);

    // ============================================================
    // INVOICES (Keuangan)
    // ============================================================
    Route::get('/invoices/available-projects', [InvoiceController::class, 'getAvailableProjects']);
    Route::get('/invoices/{id}/detail', [InvoiceController::class, 'detail']);
    Route::get('/invoices/{id}/print', [InvoiceController::class, 'printInvoice']);
    Route::get('/invoices/{id}/preview', [InvoiceController::class, 'previewInvoice']);
    Route::post('/invoices/import', [InvoiceController::class, 'import']);
    Route::apiResource('invoices', InvoiceController::class);

    // ============================================================
    // FINANCIAL TRANSACTIONS (Keuangan)
    // ============================================================
    Route::get('/financial-summary', [FinancialTransactionController::class, 'summary']);
    Route::get('/financial/chart', [FinancialTransactionController::class, 'chartData']);
    Route::post('/financial-transactions/import', [FinancialTransactionController::class, 'import']);
    Route::apiResource('financial-transactions', FinancialTransactionController::class);

    // ============================================================
    // SETTINGS (Pengaturan)
    // ============================================================
    Route::get('/settings', [SettingController::class, 'index']);
    Route::get('/settings/company-profile', [SettingController::class, 'getCompanyProfile']);
    Route::put('/settings/company-profile', [SettingController::class, 'updateCompanyProfile']);
    Route::post('/settings/logo', [SettingController::class, 'uploadLogo']);
    Route::get('/settings/code-formats', [SettingController::class, 'getCodeFormats']);
    Route::put('/settings/code-formats', [SettingController::class, 'updateCodeFormats']);

    // ============================================================
    // USERS (Super Admin only)
    // ============================================================
    Route::apiResource('users', UserController::class)->only(['index', 'store', 'show', 'update', 'destroy']);

    // 🔥 MANAJEMEN PERMISSION (Super Admin only)
    Route::get('/permissions', [UserController::class, 'getPermissions']);
    Route::get('/permissions/role/{role}', [UserController::class, 'getRolePermissions']);
    Route::put('/permissions/role/{role}', [UserController::class, 'updateRolePermissions']);
    Route::post('/update-password', [AuthController::class, 'updatePassword']);
});

// ================================================================
// 🔄 FALLBACK ROUTE (jika tidak ada route yang cocok)
// ================================================================
Route::fallback(function () {
    return response()->json([
        'message' => 'Endpoint tidak ditemukan. Periksa URL dan method Anda.'
    ], 404);
});