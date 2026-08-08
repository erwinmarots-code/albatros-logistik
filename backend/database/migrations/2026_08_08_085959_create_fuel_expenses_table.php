<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fuel_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('unique_code')->unique()->nullable();
            $table->foreignId('delivery_task_id')->constrained('delivery_tasks')->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->enum('type', ['fuel', 'toll', 'parking', 'meal', 'other']);
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('receipt_photo')->nullable();
            $table->date('request_date');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fuel_expenses');
    }
};