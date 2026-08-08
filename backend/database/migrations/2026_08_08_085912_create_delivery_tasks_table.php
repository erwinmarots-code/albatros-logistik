<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('delivery_tasks', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke client (pengirim)
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            
            // Relasi ke kendaraan
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            
            // Relasi ke driver
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            
            // Tanggal dan waktu
            $table->date('task_date');
            $table->time('departure_time')->nullable();
            $table->time('estimated_return_time')->nullable();
            $table->time('actual_return_time')->nullable();
            
            // Lokasi
            $table->text('origin')->nullable();
            $table->text('destination')->nullable();
            
            // Informasi tugas
            $table->text('description')->nullable();
            $table->integer('distance_km')->nullable(); // perkiraan jarak dalam km
            
            // Status perjalanan
            $table->enum('status', ['planned', 'ongoing', 'completed', 'cancelled'])->default('planned');
            
            // Catatan
            $table->text('notes')->nullable();
            
            // User yang membuat tugas
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('delivery_tasks');
    }
};