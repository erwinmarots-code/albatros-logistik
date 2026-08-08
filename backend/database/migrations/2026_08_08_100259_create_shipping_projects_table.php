<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('shipping_projects', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke client
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            
            // Nomor PO (dari client)
            $table->string('no_po')->nullable();
            
            // Nomor Invoice (auto generate)
            $table->string('invoice_number')->unique();
            
            // Data Pengirim
            $table->string('sender_name');
            $table->text('sender_address');
            $table->string('sender_phone');
            
            // Data Penerima
            $table->string('receiver_name');
            $table->text('receiver_address');
            $table->string('receiver_phone');
            
            // Detail Barang
            $table->text('goods_description')->nullable(); // catatan tambahan
            
            // Detail Barang (terstruktur)
            $table->decimal('weight_kg', 10, 2)->nullable();
            $table->integer('collie')->nullable();
            $table->decimal('volumetric', 10, 2)->nullable();
            $table->decimal('ppn', 5, 2)->nullable(); // persentase PPN
            $table->decimal('discount', 15, 2)->nullable();
            $table->decimal('insurance', 15, 2)->nullable();
            $table->decimal('goods_value', 15, 2)->nullable();
            
            // Metode pengiriman
            $table->enum('shipping_method', ['darat', 'udara'])->default('darat');
            
            // Status project
            $table->enum('status', ['draft', 'confirmed', 'completed', 'cancelled'])->default('draft');
            
            // User pembuat (nullable)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shipping_projects');
    }
};