<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run()
    {
        Branch::create([
            'code' => 'JKT',
            'name' => 'Jakarta',
            'city' => 'Jakarta',
            'address' => 'Jl. Sudirman No.1',
            'phone' => '021-123456',
        ]);

        Branch::create([
            'code' => 'UPG',
            'name' => 'Makassar',
            'city' => 'Makassar',
            'address' => 'Jl. Sultan Hasanuddin No.10',
            'phone' => '0411-123456',
        ]);
    }
}