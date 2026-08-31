<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('spare_part_movements')) {
            Schema::create('spare_part_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('spare_part_id')->constrained()->onDelete('cascade');
                $table->integer('quantity');
                $table->enum('movement_type', ['masuk', 'keluar', 'rusak', 'koreksi']);
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('spare_part_movements');
    }
};