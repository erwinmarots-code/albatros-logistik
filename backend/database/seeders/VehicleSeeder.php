<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehicleSeeder extends Seeder
{
    public function run()
    {
        // Nonaktifkan foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Vehicle::truncate();

        $vehicles = [
            [
                'plate_number'   => 'DD 1234 AB',
                'brand'          => 'Toyota',
                'model'          => 'Hiace',
                'year'           => 2020,
                'color'          => 'Putih',
                'engine_capacity'=> '2800 cc',
                'fuel_type'      => 'diesel',
                'status'         => 'available',
                'purchase_date'  => '2020-05-15',
                'price'          => 350000000,
                'notes'          => 'Kondisi mesin baik, interior bersih'
            ],
            [
                'plate_number'   => 'DD 5678 CD',
                'brand'          => 'Mitsubishi',
                'model'          => 'Colt Diesel',
                'year'           => 2019,
                'color'          => 'Biru',
                'engine_capacity'=> '2500 cc',
                'fuel_type'      => 'diesel',
                'status'         => 'available',
                'purchase_date'  => '2019-08-20',
                'price'          => 280000000,
                'notes'          => 'Ban belakang perlu diperhatikan'
            ],
            [
                'plate_number'   => 'DD 9012 EF',
                'brand'          => 'Hino',
                'model'          => 'Dutro',
                'year'           => 2021,
                'color'          => 'Merah',
                'engine_capacity'=> '4000 cc',
                'fuel_type'      => 'diesel',
                'status'         => 'available',
                'purchase_date'  => '2021-11-01',
                'price'          => 420000000,
                'notes'          => 'Kondisi prima'
            ],
        ];

        foreach ($vehicles as $vehicle) {
            Vehicle::create($vehicle);
        }

        // Aktifkan kembali foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}