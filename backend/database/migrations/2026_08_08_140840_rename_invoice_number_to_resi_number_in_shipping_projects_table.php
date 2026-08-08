<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_projects', function (Blueprint $table) {
            // Cek apakah kolom invoice_number ada
            if (Schema::hasColumn('shipping_projects', 'invoice_number')) {
                $table->renameColumn('invoice_number', 'resi_number');
            } else {
                // Jika kolom resi_number belum ada, tambahkan
                $table->string('resi_number')->nullable()->unique()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipping_projects', function (Blueprint $table) {
            if (Schema::hasColumn('shipping_projects', 'resi_number')) {
                $table->renameColumn('resi_number', 'invoice_number');
            }
        });
    }
};