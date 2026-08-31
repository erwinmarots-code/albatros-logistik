<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Nonaktifkan foreign key constraint sementara
        DB::statement('PRAGMA foreign_keys = OFF');

        $tables = [
            'clients', 'vehicles', 'drivers', 'invoices',
            'financial_transactions', 'fuel_expenses', 'delivery_tasks'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'branch_id')) {
                // Untuk SQLite, kita tambahkan kolom tanpa foreign key constraint
                Schema::table($table, function (Blueprint $table) {
                    $table->unsignedBigInteger('branch_id')->nullable()->after('id');
                    // Kita tidak tambahkan foreign key di sini untuk menghindari error index
                });
            }
        }

        // Aktifkan kembali foreign key
        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down()
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        $tables = [
            'clients', 'vehicles', 'drivers', 'invoices',
            'financial_transactions', 'fuel_expenses', 'delivery_tasks'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('branch_id');
                });
            }
        }

        DB::statement('PRAGMA foreign_keys = ON');
    }
};