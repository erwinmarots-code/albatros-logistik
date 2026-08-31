<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Daftar semua menu/akses yang tersedia
        $permissions = [
            // Dashboard
            ['name' => 'dashboard', 'label' => 'Dashboard', 'group' => 'Dashboard'],

            // Data Master
            ['name' => 'clients', 'label' => 'Client', 'group' => 'Data Master'],
            ['name' => 'vehicles', 'label' => 'Kendaraan', 'group' => 'Data Master'],
            ['name' => 'drivers', 'label' => 'Driver', 'group' => 'Data Master'],

            // Operasional
            ['name' => 'projects', 'label' => 'Project Pengantaran', 'group' => 'Operasional'],
            ['name' => 'delivery-tasks', 'label' => 'Tugas Kirim', 'group' => 'Operasional'],
            ['name' => 'fuel-expenses', 'label' => 'Pengajuan Biaya', 'group' => 'Operasional'],

            // Keuangan
            ['name' => 'financial-dashboard', 'label' => 'Dashboard Keuangan', 'group' => 'Keuangan'],
            ['name' => 'financial-transactions', 'label' => 'Transaksi Keuangan', 'group' => 'Keuangan'],
            ['name' => 'invoices', 'label' => 'Invoice', 'group' => 'Keuangan'],

            // Perawatan
            ['name' => 'maintenance-requests', 'label' => 'Pengajuan Perawatan', 'group' => 'Perawatan'],
            ['name' => 'maintenance-schedules', 'label' => 'Jadwal Perawatan', 'group' => 'Perawatan'],
            ['name' => 'spare-parts', 'label' => 'Inventory Spare Part', 'group' => 'Perawatan'],

            // Pengaturan
            ['name' => 'users', 'label' => 'Manajemen User', 'group' => 'Pengaturan'],
            ['name' => 'branches', 'label' => 'Cabang', 'group' => 'Pengaturan'],
            ['name' => 'permissions', 'label' => 'Manajemen Akses', 'group' => 'Pengaturan'],
        ];

        foreach ($permissions as $perm) {
            Permission::updateOrCreate(
                ['name' => $perm['name']],
                ['label' => $perm['label'], 'group' => $perm['group']]
            );
        }

        // Beri semua permission untuk super_admin
        $superAdminRole = 'super_admin';
        $allPermissionIds = Permission::pluck('id')->toArray();

        foreach ($allPermissionIds as $permId) {
            DB::table('role_permission')->updateOrInsert(
                ['role' => $superAdminRole, 'permission_id' => $permId],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Beri permission default untuk role lain (sesuaikan)
        $rolePermissions = [
            'admin_finance' => [
                'dashboard',
                'financial-dashboard',
                'financial-transactions',
                'invoices',
                'fuel-expenses',
                'maintenance-requests',
            ],
            'admin_transport' => [
                'dashboard',
                'vehicles',
                'drivers',
                'delivery-tasks',
                'fuel-expenses',
                'maintenance-requests',
                'spare-parts',
                'maintenance-schedules',
            ],
            'admin_project' => [
                'dashboard',
                'clients',
                'projects',
                'delivery-tasks',
                'fuel-expenses',
            ],
            'branch_admin' => [
                'dashboard',
                'clients',
                'vehicles',
                'drivers',
                'projects',
                'delivery-tasks',
                'fuel-expenses',
            ],
            'staff' => [
                'dashboard',
                'clients',
                'fuel-expenses',
            ],
        ];

        foreach ($rolePermissions as $role => $permNames) {
            foreach ($permNames as $name) {
                $perm = Permission::where('name', $name)->first();
                if ($perm) {
                    DB::table('role_permission')->updateOrInsert(
                        ['role' => $role, 'permission_id' => $perm->id],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                }
            }
        }
    }
}