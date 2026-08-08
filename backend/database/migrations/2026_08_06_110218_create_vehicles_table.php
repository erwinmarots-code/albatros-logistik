<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number')->unique();
            $table->string('brand');
            $table->string('model');
            $table->year('year');
            $table->string('color')->nullable();
            $table->string('engine_capacity')->nullable();
            $table->enum('fuel_type', ['bensin', 'diesel', 'electric'])->default('bensin');
            $table->enum('status', ['available', 'maintenance', 'in_use', 'retired'])->default('available');
            $table->date('purchase_date')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};