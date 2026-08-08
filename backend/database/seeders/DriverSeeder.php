<?php

namespace Database\Seeders;

use App\Models\Driver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DriverSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Driver::truncate();

        $drivers = [
            [
                'name'           => 'Andi Saputra',
                'phone'          => '081234567890',
                'license_number' => 'SIM-123456',
                'address'        => 'Jl. Mawar No. 10, Makassar',
                'status'         => 'active',
            ],
            [
                'name'           => 'Budi Santoso',
                'phone'          => '081298765432',
                'license_number' => 'SIM-654321',
                'address'        => 'Jl. Melati No. 5, Makassar',
                'status'         => 'active',
            ],
            [
                'name'           => 'Citra Dewi',
                'phone'          => '081355577788',
                'license_number' => 'SIM-987654',
                'address'        => 'Jl. Kenanga No. 12, Makassar',
                'status'         => 'inactive',
            ],
        ];

        foreach ($drivers as $driver) {
            Driver::create($driver);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}