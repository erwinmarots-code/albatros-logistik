<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->enum('type', ['income', 'expense']);
            $table->enum('category', ['service', 'fuel', 'toll', 'parking', 'salary', 'client_payment', 'other']);
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('reference_type')->nullable();
            $table->bigInteger('reference_id')->nullable();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['pending', 'confirmed'])->default('confirmed');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('financial_transactions');
    }
};