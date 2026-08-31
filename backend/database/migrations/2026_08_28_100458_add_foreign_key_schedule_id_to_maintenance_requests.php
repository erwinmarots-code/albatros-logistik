<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            // Pastikan kolom schedule_id ada
            if (!Schema::hasColumn('maintenance_requests', 'schedule_id')) {
                $table->unsignedBigInteger('schedule_id')->nullable()->after('driver_id');
            }

            // Tambahkan foreign key jika belum ada
            try {
                $table->foreign('schedule_id')->references('id')->on('maintenance_schedules')->onDelete('set null');
            } catch (\Exception $e) {
                // Jika constraint sudah ada, abaikan
            }
        });
    }

    public function down()
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropForeign(['schedule_id']);
        });
    }
};