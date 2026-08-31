<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('delivery_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_tasks', 'receiver_name')) {
                $table->string('receiver_name')->nullable();
            }
            if (!Schema::hasColumn('delivery_tasks', 'receiver_address')) {
                $table->text('receiver_address')->nullable();
            }
            if (!Schema::hasColumn('delivery_tasks', 'receiver_phone')) {
                $table->string('receiver_phone')->nullable();
            }
            if (!Schema::hasColumn('delivery_tasks', 'goods_description')) {
                $table->text('goods_description')->nullable();
            }
            if (!Schema::hasColumn('delivery_tasks', 'weight_kg')) {
                $table->decimal('weight_kg', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('delivery_tasks', 'collie')) {
                $table->integer('collie')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('delivery_tasks', function (Blueprint $table) {
            $table->dropColumn(['receiver_name', 'receiver_address', 'receiver_phone', 'goods_description', 'weight_kg', 'collie']);
        });
    }
};