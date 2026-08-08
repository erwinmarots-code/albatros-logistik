<?php

namespace Database\Seeders;

use App\Models\MaintenanceRequest;
use App\Models\Vehicle;
use App\Models\Driver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaintenanceRequestSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        MaintenanceRequest::truncate();

        $vehicleIds = Vehicle::pluck('id')->toArray();
        $driverIds  = Driver::pluck('id')->toArray();

        if (empty($vehicleIds) || empty($driverIds)) {
            $this->command->warn('Data kendaraan atau driver kosong, jalankan seeder lain dulu.');
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            return;
        }

        $requests = [
            [
                'vehicle_id'     => $vehicleIds[0],
                'driver_id'      => $driverIds[0],
                'schedule_id'    => null,
                'request_date'   => '2026-08-01',
                'description'    => 'Mesin overheat, perlu pengecekan radiator',
                'estimated_cost' => 300000,
                'urgency'        => 'high',
                'status'         => 'pending',
                'approved_by'    => null,
                'approved_at'    => null,
                'actual_cost'    => null,
                'notes'          => 'Segera ditangani',
            ],
            [
                'vehicle_id'     => $vehicleIds[1] ?? $vehicleIds[0],
                'driver_id'      => $driverIds[1] ?? $driverIds[0],
                'schedule_id'    => null,
                'request_date'   => '2026-08-05',
                'description'    => 'AC tidak dingin, perlu isi freon dan perbaikan kompresor',
                'estimated_cost' => 450000,
                'urgency'        => 'medium',
                'status'         => 'pending',
                'approved_by'    => null,
                'approved_at'    => null,
                'actual_cost'    => null,
                'notes'          => 'Prioritas sedang',
            ],
            [
                'vehicle_id'     => $vehicleIds[2] ?? $vehicleIds[0],
                'driver_id'      => $driverIds[2] ?? $driverIds[0],
                'schedule_id'    => null,
                'request_date'   => '2026-08-07',
                'description'    => 'Ganti busi dan filter udara',
                'estimated_cost' => 200000,
                'urgency'        => 'low',
                'status'         => 'approved',
                'approved_by'    => null,
                'approved_at'    => '2026-08-08',
                'actual_cost'    => 250000,
                'notes'          => 'Sudah disetujui admin finance',
            ],
        ];

        foreach ($requests as $req) {
            MaintenanceRequest::create($req);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}