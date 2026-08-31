<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Nonaktifkan foreign key constraints (kompatibel dengan SQLite & MySQL)
        Schema::disableForeignKeyConstraints();

        // Panggil seeder master
        $this->call(MasterDataSeeder::class);

        // Aktifkan kembali foreign key constraints
        Schema::enableForeignKeyConstraints();
    }
}