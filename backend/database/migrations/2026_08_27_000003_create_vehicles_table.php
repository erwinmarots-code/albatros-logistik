<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('vehicles')) {
            Schema::create('vehicles', function (Blueprint $table) {
                $table->id();
                $table->string('plate_number')->unique();
                $table->string('brand');
                $table->string('model');
                $table->year('year')->nullable();
                $table->string('color')->nullable();
                $table->string('engine_capacity')->nullable();
                $table->string('fuel_type')->nullable();
                $table->enum('status', ['available', 'maintenance', 'rented', 'inactive'])->default('available');
                $table->date('purchase_date')->nullable();
                $table->decimal('price', 15, 2)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('vehicles');
    }
};