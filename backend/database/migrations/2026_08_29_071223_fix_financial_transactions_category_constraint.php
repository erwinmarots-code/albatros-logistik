<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Cek constraint yang sudah ada
        $existingConstraints = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'financial_transactions' 
            AND CONSTRAINT_SCHEMA = DATABASE()
        ");
        $existingNames = array_column($existingConstraints, 'CONSTRAINT_NAME');

        Schema::table('financial_transactions', function (Blueprint $table) use ($existingNames) {
            // 🔥 Hanya tambahkan foreign key jika belum ada
            if (!in_array('financial_transactions_vehicle_id_foreign', $existingNames)) {
                $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('set null');
            }
            if (!in_array('financial_transactions_driver_id_foreign', $existingNames)) {
                $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('set null');
            }
            if (!in_array('financial_transactions_client_id_foreign', $existingNames)) {
                $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            }
            if (!in_array('financial_transactions_created_by_foreign', $existingNames)) {
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            }
            if (!in_array('financial_transactions_branch_id_foreign', $existingNames)) {
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            }
        });
    }

    public function down()
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
            $table->dropForeign(['driver_id']);
            $table->dropForeign(['client_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['branch_id']);
        });
    }
};