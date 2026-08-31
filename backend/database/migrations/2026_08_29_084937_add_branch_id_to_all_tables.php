<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $tables = ['clients', 'vehicles', 'drivers', 'invoices', 'financial_transactions', 'fuel_expenses', 'delivery_tasks'];
        foreach ($tables as $table) {
            if (!Schema::hasColumn($table, 'branch_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->foreignId('branch_id')->nullable()->after('id')->constrained()->nullOnDelete();
                });
            }
        }
    }

    public function down()
    {
        $tables = ['clients', 'vehicles', 'drivers', 'invoices', 'financial_transactions', 'fuel_expenses', 'delivery_tasks'];
        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'branch_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropForeign(['branch_id']);
                    $table->dropColumn('branch_id');
                });
            }
        }
    }
};