<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number')->unique();
                $table->foreignId('client_id')->constrained();
                $table->foreignId('shipping_project_id')->constrained();
                $table->decimal('total_amount', 15, 2);
                $table->date('due_date');
                $table->enum('status', ['draft', 'sent', 'paid', 'cancelled'])->default('draft');
                $table->foreignId('branch_id')->constrained();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('invoices');
    }
};