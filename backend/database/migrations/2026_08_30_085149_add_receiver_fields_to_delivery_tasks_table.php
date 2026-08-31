<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('delivery_tasks', function (Blueprint $table) {
            $table->string('receiver_name')->nullable()->after('client_id');
            $table->text('receiver_address')->nullable()->after('receiver_name');
            $table->string('receiver_phone')->nullable()->after('receiver_address');
            $table->string('goods_description')->nullable()->after('receiver_phone');
            $table->decimal('weight_kg', 10, 2)->nullable()->after('goods_description');
            $table->integer('collie')->nullable()->after('weight_kg');
        });
    }

    public function down()
    {
        Schema::table('delivery_tasks', function (Blueprint $table) {
            $table->dropColumn(['receiver_name', 'receiver_address', 'receiver_phone', 'goods_description', 'weight_kg', 'collie']);
        });
    }
};