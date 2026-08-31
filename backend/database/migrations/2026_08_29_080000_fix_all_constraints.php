<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // ==========================================================
        // 1. Tambah kolom ke financial_transactions (jika belum ada)
        // ==========================================================
        Schema::table('financial_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('financial_transactions', 'po_number')) {
                $table->string('po_number')->nullable()->after('description');
            }
            if (!Schema::hasColumn('financial_transactions', 'maintenance_number')) {
                $table->string('maintenance_number')->nullable()->after('po_number');
            }
            if (!Schema::hasColumn('financial_transactions', 'reference_type')) {
                $table->string('reference_type')->nullable()->after('maintenance_number');
            }
            if (!Schema::hasColumn('financial_transactions', 'reference_id')) {
                $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
            }
        });

        // ==========================================================
        // 2. Buat ulang tabel delivery_tasks tanpa CHECK constraint
        // (dengan cara copy data ke tabel baru lalu rename)
        // ==========================================================
        Schema::dropIfExists('delivery_tasks_new');
        Schema::rename('delivery_tasks', 'delivery_tasks_old');

        Schema::create('delivery_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('shipping_projects')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->date('tanggal')->nullable();
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->string('no_resi')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Copy data dari tabel lama (jika ada)
        $oldData = \DB::table('delivery_tasks_old')->get();
        if ($oldData->count() > 0) {
            foreach ($oldData as $row) {
                \DB::table('delivery_tasks')->insert((array) $row);
            }
        }

        Schema::dropIfExists('delivery_tasks_old');
    }

    public function down()
    {
        // Rollback: hapus kolom yang ditambahkan
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropColumn(['po_number', 'maintenance_number', 'reference_type', 'reference_id']);
        });

        // Kembalikan delivery_tasks ke kondisi semula (pakai enum status)
        Schema::rename('delivery_tasks', 'delivery_tasks_new');
        Schema::dropIfExists('delivery_tasks');
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
        $oldData = \DB::table('delivery_tasks_new')->get();
        if ($oldData->count() > 0) {
            foreach ($oldData as $row) {
                \DB::table('delivery_tasks')->insert((array) $row);
            }
        }
        Schema::dropIfExists('delivery_tasks_new');
    }
};