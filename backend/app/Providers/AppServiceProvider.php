<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ============================================================
        // 🛡️ GATE UNTUK EKSPOR MODUL (Sudah Ada)
        // ============================================================
        Gate::define('exportModule', function ($user, $module) {
            $allowed = [
                // Data Master
                'clients'        => ['super_admin', 'admin_project', 'staff'],
                'vehicles'       => ['super_admin', 'admin_transport', 'admin_project'],
                'drivers'        => ['super_admin', 'admin_transport', 'admin_project'],

                // Operasional
                'projects'       => ['super_admin', 'admin_project'],
                'delivery-tasks' => ['super_admin', 'admin_project'],

                // Keuangan
                'financials'     => ['super_admin', 'admin_finance'],
                'invoices'       => ['super_admin', 'admin_finance'],
                'fuel-expenses'  => ['super_admin', 'admin_finance', 'admin_transport'],

                // Inventory & Perawatan
                'spare-parts'          => ['super_admin', 'admin_transport'],
                'maintenance-requests' => ['super_admin', 'admin_finance', 'admin_transport'],
            ];

            return in_array($user->role, $allowed[$module] ?? []);
        });

        // ============================================================
        // 🆕 GATE UNTUK MENU / PERMISSION
        // ============================================================
        Gate::define('view-menu', function ($user, $permission) {
            // Super Admin memiliki semua akses
            if ($user->role === 'super_admin') {
                return true;
            }

            // Cek apakah user memiliki permission ini
            return $user->hasPermission($permission);
        });

        // ============================================================
        // 🆕 GATE UNTUK UPDATE PASSWORD (Hanya untuk user sendiri atau super_admin)
        // ============================================================
        Gate::define('update-password', function ($user, $targetUserId) {
            if ($user->role === 'super_admin') {
                return true;
            }
            return $user->id === $targetUserId;
        });

        // ============================================================
        // 🆕 GATE UNTUK MANAJEMEN PERMISSION (Hanya Super Admin)
        // ============================================================
        Gate::define('manage-permissions', function ($user) {
            return $user->role === 'super_admin';
        });
    }
}