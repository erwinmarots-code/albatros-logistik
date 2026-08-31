<?php

namespace App\Policies;

use App\Models\User;

class ExportPolicy
{
    public function exportModule(User $user, string $module): bool
    {
        $role = $user->role;
        $allowed = [
            'clients'        => ['super_admin', 'admin_project', 'staff'],
            'vehicles'       => ['super_admin', 'admin_transport', 'admin_project'],
            'drivers'        => ['super_admin', 'admin_transport', 'admin_project'],
            'projects'       => ['super_admin', 'admin_project'],
            'delivery-tasks' => ['super_admin', 'admin_project'],
            'financials'     => ['super_admin', 'admin_finance'],
            'invoices'       => ['super_admin', 'admin_finance'],
            'fuel-expenses'  => ['super_admin', 'admin_finance', 'admin_transport'],
        ];

        return in_array($role, $allowed[$module] ?? []);
    }
}