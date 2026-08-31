<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('maintenance_schedules')->nullOnDelete();
            $table->date('request_date');
            $table->text('description');
            $table->enum('service_type', ['oil_change', 'tire_replacement', 'sparepart', 'general', 'other'])->default('general');
            $table->decimal('estimated_cost', 15, 2)->nullable();
            $table->enum('urgency', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['pending', 'approved', 'rejected', 'done'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->decimal('actual_cost', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->boolean('is_executed')->default(false);
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('maintenance_requests');
    }
};