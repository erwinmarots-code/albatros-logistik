<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 🔥 Periksa apakah kolom masih enum, jika sudah string, skip
        Schema::table('users', function (Blueprint $table) {
            // Tidak perlu ubah, biarkan saja karena sudah string
        });
    }

    public function down()
    {
        // Tidak ada operasi
    }
};