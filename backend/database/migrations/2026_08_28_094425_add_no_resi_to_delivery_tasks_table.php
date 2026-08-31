<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Cek apakah kolom 'no_resi' sudah ada, jika belum tambahkan
        if (!Schema::hasColumn('delivery_tasks', 'no_resi')) {
            Schema::table('delivery_tasks', function (Blueprint $table) {
                $table->string('no_resi')->nullable()->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus kolom jika ada
        if (Schema::hasColumn('delivery_tasks', 'no_resi')) {
            Schema::table('delivery_tasks', function (Blueprint $table) {
                $table->dropColumn('no_resi');
            });
        }
    }
};