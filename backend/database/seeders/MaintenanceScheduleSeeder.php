<?php

namespace Database\Seeders;

use App\Models\MaintenanceSchedule;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaintenanceScheduleSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        MaintenanceSchedule::truncate();

        $vehicleIds = Vehicle::pluck('id')->toArray();

        if (empty($vehicleIds)) {
            $this->command->warn('Tidak ada kendaraan, jalankan VehicleSeeder dulu.');
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            return;
        }

        $schedules = [
            [
                'vehicle_id'       => $vehicleIds[0],
                'type'             => 'oil_change',
                'description'      => 'Ganti Oli Mesin dan Filter Oli',
                'last_date'        => '2026-07-01',
                'next_date'        => '2026-09-01',
                'mileage_interval' => 5000,
                'estimated_cost'   => 500000,
                'status'           => 'scheduled',
            ],
            [
                'vehicle_id'       => $vehicleIds[0],
                'type'             => 'tire_replacement',
                'description'      => 'Ganti Ban Depan dan Belakang',
                'last_date'        => '2026-06-15',
                'next_date'        => '2026-10-15',
                'mileage_interval' => 10000,
                'estimated_cost'   => 2000000,
                'status'           => 'scheduled',
            ],
            [
                'vehicle_id'       => $vehicleIds[1] ?? $vehicleIds[0],
                'type'             => 'sparepart',
                'description'      => 'Ganti Kampas Rem dan Minyak Rem',
                'last_date'        => '2026-05-10',
                'next_date'        => '2026-08-10',
                'mileage_interval' => 8000,
                'estimated_cost'   => 750000,
                'status'           => 'done',
            ],
            [
                'vehicle_id'       => $vehicleIds[2] ?? $vehicleIds[0],
                'type'             => 'general',
                'description'      => 'Service Rutin 6 Bulan',
                'last_date'        => '2026-04-01',
                'next_date'        => '2026-10-01',
                'mileage_interval' => 6000,
                'estimated_cost'   => 1200000,
                'status'           => 'scheduled',
            ],
        ];

        foreach ($schedules as $schedule) {
            MaintenanceSchedule::create($schedule);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}