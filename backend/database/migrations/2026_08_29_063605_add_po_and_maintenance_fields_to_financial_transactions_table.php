<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 🔥 Cek dan tambahkan kolom hanya jika belum ada
        Schema::table('financial_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('financial_transactions', 'po_number')) {
                $table->string('po_number')->nullable()->after('description');
            }
            if (!Schema::hasColumn('financial_transactions', 'maintenance_number')) {
                $table->string('maintenance_number')->nullable()->after('po_number');
            }
        });
    }

    public function down()
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('financial_transactions', 'po_number')) {
                $table->dropColumn('po_number');
            }
            if (Schema::hasColumn('financial_transactions', 'maintenance_number')) {
                $table->dropColumn('maintenance_number');
            }
        });
    }
};