<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('delivery_tasks')) {
            Schema::create('delivery_tasks', function (Blueprint $table) {
                $table->id();
                $table->string('no_resi')->unique();
                $table->foreignId('project_id')->constrained('shipping_projects');
                $table->foreignId('vehicle_id')->constrained();
                $table->foreignId('driver_id')->constrained();
                $table->foreignId('client_id')->constrained();
                $table->date('tanggal');
                $table->enum('status', ['draft', 'assigned', 'in_progress', 'completed', 'cancelled'])->default('draft');
                $table->text('notes')->nullable();
                $table->string('receiver_name')->nullable();
                $table->text('receiver_address')->nullable();
                $table->string('receiver_phone')->nullable();
                $table->text('goods_description')->nullable();
                $table->decimal('weight_kg', 10, 2)->nullable();
                $table->integer('collie')->nullable();
                $table->foreignId('created_by')->constrained('users');
                $table->foreignId('branch_id')->constrained();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('delivery_tasks');
    }
};