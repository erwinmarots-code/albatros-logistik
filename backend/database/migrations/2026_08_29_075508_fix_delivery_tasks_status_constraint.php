<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Hapus constraint dengan rename-copy-drop
        Schema::rename('delivery_tasks', 'delivery_tasks_old');

        Schema::create('delivery_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('shipping_projects')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->date('tanggal')->nullable();
            $table->string('status')->default('draft'); // tanpa CHECK constraint
            $table->text('notes')->nullable();
            $table->string('no_resi')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Copy data dari tabel lama
        DB::statement('INSERT INTO delivery_tasks 
            (id, project_id, vehicle_id, driver_id, client_id, tanggal, status, notes, no_resi, created_by, created_at, updated_at)
            SELECT id, project_id, vehicle_id, driver_id, client_id, tanggal, status, notes, no_resi, created_by, created_at, updated_at
            FROM delivery_tasks_old');

        Schema::dropIfExists('delivery_tasks_old');
    }

    public function down()
    {
        // Rollback: kembalikan ke struktur lama (dengan constraint)
        Schema::rename('delivery_tasks', 'delivery_tasks_new');
        Schema::create('delivery_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('shipping_projects')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->date('tanggal')->nullable();
            $table->enum('status', ['draft', 'assigned', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->string('no_resi')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        DB::statement('INSERT INTO delivery_tasks SELECT * FROM delivery_tasks_new');
        Schema::dropIfExists('delivery_tasks_new');
    }
};