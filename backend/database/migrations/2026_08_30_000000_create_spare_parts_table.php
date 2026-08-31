<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('spare_parts')) {
            Schema::create('spare_parts', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->enum('category', ['sekali_pakai', 'berulang'])->default('sekali_pakai');
                $table->string('unit')->nullable();
                $table->integer('stock')->default(0);
                $table->integer('min_stock')->default(0);
                $table->decimal('price', 15, 2)->nullable();
                $table->integer('lifespan_km')->nullable();
                $table->integer('lifespan_months')->nullable();
                $table->enum('status', ['tersedia', 'sedang_dipakai', 'stok_habis', 'perlu_restok', 'rusak_tidak_layak'])->default('tersedia');
                $table->foreignId('created_by')->constrained('users');
                $table->foreignId('branch_id')->constrained();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('spare_parts');
    }
};