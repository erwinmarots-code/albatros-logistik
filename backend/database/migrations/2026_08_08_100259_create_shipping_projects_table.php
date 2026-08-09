<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('no_po')->nullable();
            $table->string('resi_number')->unique()->nullable();
            $table->string('sender_name');
            $table->text('sender_address');
            $table->string('sender_phone');
            $table->string('receiver_name');
            $table->text('receiver_address');
            $table->string('receiver_phone');
            $table->text('goods_description')->nullable();
            
            // Detail barang (sudah ada di sini)
            $table->decimal('weight_kg', 10, 2)->nullable();
            $table->integer('collie')->nullable();
            $table->string('volumetric')->nullable(); // diubah menjadi string
            $table->decimal('goods_value', 15, 2)->nullable();
            
            $table->enum('shipping_method', ['darat', 'udara'])->default('darat');
            $table->enum('status', ['draft', 'confirmed', 'completed', 'cancelled'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_projects');
    }
};