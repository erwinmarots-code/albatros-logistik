<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('delivery_tasks', function (Blueprint $table) {
            // Hapus foreign key yang salah ke delivery_projects (jika ada)
            // SQLite tidak bisa drop foreign key langsung, kita harus drop table dan recreate
            // Tapi untuk SQLite, kita bisa menggunakan pendekatan: rename, create baru, copy data, drop old
            // Atau kita bisa langsung mengubah constraint dengan cara SQLite
        });
    }

    public function down()
    {
        // Tidak perlu
    }
};