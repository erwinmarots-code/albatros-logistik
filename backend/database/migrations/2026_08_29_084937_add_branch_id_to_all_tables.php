<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Tabel clients
        if (!Schema::hasColumn('clients', 'branch_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->constrained();
            });
        }
        // Tabel vehicles
        if (!Schema::hasColumn('vehicles', 'branch_id')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->constrained();
            });
        }
        // Tabel drivers
        if (!Schema::hasColumn('drivers', 'branch_id')) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->constrained();
            });
        }
        // Tabel shipping_projects
        if (!Schema::hasColumn('shipping_projects', 'branch_id')) {
            Schema::table('shipping_projects', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->constrained();
            });
        }
        // Tabel delivery_tasks
        if (!Schema::hasColumn('delivery_tasks', 'branch_id')) {
            Schema::table('delivery_tasks', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->constrained();
            });
        }
        // Tabel fuel_expenses
        if (!Schema::hasColumn('fuel_expenses', 'branch_id')) {
            Schema::table('fuel_expenses', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->constrained();
            });
        }
        // Tabel maintenance_requests
        if (!Schema::hasColumn('maintenance_requests', 'branch_id')) {
            Schema::table('maintenance_requests', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->constrained();
            });
        }
        // Tabel financial_transactions
        if (!Schema::hasColumn('financial_transactions', 'branch_id')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->constrained();
            });
        }
        // Tabel invoices
        if (!Schema::hasColumn('invoices', 'branch_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->constrained();
            });
        }
    }

    public function down()
    {
        // Drop hanya jika kolom ada
        $tables = ['clients', 'vehicles', 'drivers', 'shipping_projects', 'delivery_tasks', 'fuel_expenses', 'maintenance_requests', 'financial_transactions', 'invoices'];
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropForeign(['branch_id']);
                    $table->dropColumn('branch_id');
                });
            }
        }
    }
};