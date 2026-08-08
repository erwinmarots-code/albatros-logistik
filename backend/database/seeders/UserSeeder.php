<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'Admin PO',
            'email' => 'adminpo@albatros.com',
            'password' => Hash::make('password'),
            'role' => 'admin_po',
        ]);

        User::create([
            'name' => 'Admin Transport',
            'email' => 'admintransport@albatros.com',
            'password' => Hash::make('password'),
            'role' => 'admin_transport',
        ]);

        User::create([
            'name' => 'Admin Finance',
            'email' => 'adminfinance@albatros.com',
            'password' => Hash::make('password'),
            'role' => 'admin_finance',
        ]);

        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@albatros.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
        ]);
    }
}