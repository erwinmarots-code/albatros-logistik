<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 🔥 Hapus semua foreign key yang mungkin ada (gunakan try-catch agar aman)
        $constraints = [
            'financial_transactions_vehicle_id_foreign',
            'financial_transactions_driver_id_foreign',
            'financial_transactions_client_id_foreign',
            'financial_transactions_created_by_foreign',
            'financial_transactions_branch_id_foreign',
        ];

        foreach ($constraints as $constraint) {
            try {
                DB::statement("ALTER TABLE financial_transactions DROP FOREIGN KEY {$constraint}");
            } catch (\Exception $e) {
                // Constraint tidak ada, abaikan
            }
        }

        // 🔥 Tambahkan kembali foreign key dengan benar
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('set null');
            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('set null');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
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