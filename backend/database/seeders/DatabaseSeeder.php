<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Nonaktifkan foreign key checks sementara
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->call([
            VehicleSeeder::class,
            DriverSeeder::class,
            ClientSeeder::class,
            MaintenanceScheduleSeeder::class,
            MaintenanceRequestSeeder::class,
        ]);

        // Aktifkan kembali foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}