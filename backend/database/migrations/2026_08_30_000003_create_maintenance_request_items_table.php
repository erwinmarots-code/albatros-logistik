<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('maintenance_request_items')) {
            Schema::create('maintenance_request_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('maintenance_request_id')->constrained()->onDelete('cascade');
                $table->foreignId('spare_part_id')->constrained();
                $table->integer('quantity');
                $table->integer('odometer_before')->nullable();
                $table->integer('odometer_after')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('maintenance_request_items');
    }
};