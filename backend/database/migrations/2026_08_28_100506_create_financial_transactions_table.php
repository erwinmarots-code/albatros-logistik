<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('financial_transactions')) {
            Schema::create('financial_transactions', function (Blueprint $table) {
                $table->id();
                $table->date('transaction_date');
                $table->enum('type', ['income', 'expense']);
                $table->string('category');
                $table->decimal('amount', 15, 2);
                $table->text('description')->nullable();
                $table->string('po_number')->nullable();
                $table->string('maintenance_number')->nullable();
                $table->foreignId('vehicle_id')->nullable()->constrained();
                $table->foreignId('driver_id')->nullable()->constrained();
                $table->foreignId('client_id')->nullable()->constrained();
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('confirmed');
                $table->foreignId('created_by')->constrained('users');
                $table->foreignId('branch_id')->constrained();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('financial_transactions');
    }
};