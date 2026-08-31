<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 🔥 Hapus semua foreign key yang sudah ada
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropForeign('financial_transactions_vehicle_id_foreign');
            $table->dropForeign('financial_transactions_driver_id_foreign');
            $table->dropForeign('financial_transactions_client_id_foreign');
            $table->dropForeign('financial_transactions_created_by_foreign');
            $table->dropForeign('financial_transactions_branch_id_foreign');
        });

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
            $table->dropForeign('financial_transactions_vehicle_id_foreign');
            $table->dropForeign('financial_transactions_driver_id_foreign');
            $table->dropForeign('financial_transactions_client_id_foreign');
            $table->dropForeign('financial_transactions_created_by_foreign');
            $table->dropForeign('financial_transactions_branch_id_foreign');
        });
    }
};