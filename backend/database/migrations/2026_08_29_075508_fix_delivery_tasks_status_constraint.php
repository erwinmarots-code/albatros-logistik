<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 🔥 Migrasi ini dinonaktifkan karena tabel delivery_tasks sudah memiliki struktur yang benar.
        // Tidak ada operasi yang dilakukan untuk menghindari error duplicate table.
    }

    public function down()
    {
        // Tidak ada operasi
    }
};