<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Client::truncate();

        $clients = [
            [
                'name'     => 'PT. Sumber Makmur',
                'phone'    => '0411-123456',
                'email'    => 'info@sumbermakmur.co.id',
                'address'  => 'Jl. Urip Sumoharjo No. 88, Makassar',
                'pic_name' => 'Hendra',
            ],
            [
                'name'     => 'CV. Jaya Abadi',
                'phone'    => '0411-789012',
                'email'    => 'jayaabadi@yahoo.com',
                'address'  => 'Jl. Perintis Kemerdekaan Km. 10, Makassar',
                'pic_name' => 'Rina',
            ],
            [
                'name'     => 'UD. Sejahtera',
                'phone'    => '0411-345678',
                'email'    => 'sejahtera@gmail.com',
                'address'  => 'Jl. Sultan Hasanuddin No. 45, Makassar',
                'pic_name' => 'Agus',
            ],
        ];

        foreach ($clients as $client) {
            Client::create($client);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}