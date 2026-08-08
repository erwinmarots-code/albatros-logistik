<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('delivery_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('po_number')->nullable()->unique(); // No PO dari client
            $table->string('invoice_number')->unique(); // auto generate
            $table->string('sender_name');
            $table->text('sender_address');
            $table->string('sender_phone');
            $table->string('receiver_name');
            $table->text('receiver_address');
            $table->string('receiver_phone');
            $table->text('goods_description')->nullable();
            $table->decimal('weight_kg', 10, 2)->nullable();
            $table->integer('collie')->nullable(); // koli
            $table->decimal('volumetric', 10, 2)->nullable();
            $table->boolean('ppn')->default(false);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('insurance', 10, 2)->default(0);
            $table->decimal('goods_value', 15, 2)->nullable();
            $table->enum('shipping_mode', ['darat', 'udara'])->default('darat');
            $table->enum('status', ['draft', 'confirmed', 'delivered', 'invoiced'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('delivery_projects');
    }
};