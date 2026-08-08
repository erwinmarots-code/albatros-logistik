<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['oil_change', 'tire_replacement', 'sparepart', 'general']);
            $table->string('description')->nullable();
            $table->date('last_date')->nullable();
            $table->date('next_date')->nullable();
            $table->integer('mileage_interval')->nullable(); // dalam km
            $table->decimal('estimated_cost', 15, 2)->nullable();
            $table->enum('status', ['scheduled', 'done', 'cancelled'])->default('scheduled');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('maintenance_schedules');
    }
};