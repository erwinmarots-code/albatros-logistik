<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShippingProjectsTable extends Migration
{
    public function up()
    {
        Schema::create('shipping_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('no_po')->nullable();
            $table->string('no_resi')->unique()->nullable();
            $table->string('sender_name');
            $table->text('sender_address');
            $table->string('sender_phone');
            $table->string('receiver_name');
            $table->text('receiver_address');
            $table->string('receiver_phone');
            $table->text('goods_description')->nullable();
            $table->decimal('weight_kg', 10, 2)->nullable();
            $table->integer('collie')->nullable();
            $table->string('volumetric')->nullable();
            $table->decimal('goods_value', 15, 2)->nullable();
            $table->enum('shipping_method', ['darat', 'udara', 'laut'])->default('darat');
            $table->enum('status', ['draft', 'confirmed', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shipping_projects');
    }
}