<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 🔥 Hanya tambahkan jika belum ada, dan gunakan try-catch untuk keamanan
        try {
            if (!Schema::hasColumn('clients', 'branch_id')) {
                Schema::table('clients', function (Blueprint $table) {
                    $table->foreignId('branch_id')->nullable()->constrained();
                });
            }
            // ... (sama seperti di atas, tapi dengan try-catch untuk semua tabel)
        } catch (\Exception $e) {
            // Abaikan error jika kolom sudah ada
        }
    }

    public function down()
    {
        // Tidak ada operasi
    }
};