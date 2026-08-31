<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Tambah kolom notes ke delivery_tasks
        Schema::table('delivery_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_tasks', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
        });

        // Tambah kolom po_number dan maintenance_number ke financial_transactions
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
        Schema::table('delivery_tasks', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropColumn(['po_number', 'maintenance_number']);
        });
    }
};