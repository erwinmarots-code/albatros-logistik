<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('fuel_expenses')) {
            Schema::create('fuel_expenses', function (Blueprint $table) {
                $table->id();
                $table->string('unique_code')->unique();
                $table->foreignId('delivery_task_id')->constrained();
                $table->foreignId('vehicle_id')->constrained();
                $table->foreignId('driver_id')->constrained();
                $table->enum('type', ['bahan_bakar', 'toll', 'parkir', 'lainnya']);
                $table->decimal('amount', 15, 2);
                $table->date('transaction_date');
                $table->text('description')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->foreignId('created_by')->constrained('users');
                $table->foreignId('approved_by')->nullable()->constrained('users');
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('branch_id')->constrained();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('fuel_expenses');
    }
};