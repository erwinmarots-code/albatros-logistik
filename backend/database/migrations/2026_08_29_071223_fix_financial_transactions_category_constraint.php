<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 🔥 Migrasi ini dinonaktifkan karena foreign key sudah ada di database production.
        // Tidak ada operasi yang dilakukan untuk menghindari error duplikasi constraint.
    }

    public function down()
    {
        // Tidak ada operasi
    }
};