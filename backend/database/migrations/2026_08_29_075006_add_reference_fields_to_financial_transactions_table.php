<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 🔥 Migrasi ini dinonaktifkan karena kolom reference_type dan reference_id sudah ada di create table.
        // Tidak ada operasi yang dilakukan untuk menghindari error duplicate column.
    }

    public function down()
    {
        // Tidak ada operasi
    }
};