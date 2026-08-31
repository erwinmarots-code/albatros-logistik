<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('shipping_projects')) {
            Schema::create('shipping_projects', function (Blueprint $table) {
                $table->id();
                $table->string('no_po')->unique();
                $table->string('no_resi')->nullable()->unique();
                $table->foreignId('client_id')->constrained();
                $table->foreignId('branch_id')->constrained();
                $table->string('sender_name');
                $table->text('sender_address');
                $table->string('sender_phone');
                $table->string('receiver_name')->nullable();
                $table->text('receiver_address')->nullable();
                $table->string('receiver_phone')->nullable();
                $table->text('goods_description')->nullable();
                $table->decimal('weight_kg', 10, 2)->nullable();
                $table->integer('collie')->nullable();
                $table->decimal('volumetric', 10, 2)->nullable();
                $table->decimal('goods_value', 15, 2)->nullable();
                $table->decimal('contract_value', 15, 2)->nullable();
                $table->enum('shipping_method', ['darat', 'udara', 'laut']);
                $table->enum('status', ['draft', 'confirmed', 'on_delivery', 'completed', 'cancelled'])->default('draft');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->constrained('users');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('shipping_projects');
    }
};