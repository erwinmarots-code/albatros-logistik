<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // ============================================================
        // 1. DELIVERY_TASKS: Tambah kolom notes (jika belum ada)
        // ============================================================
        if (!Schema::hasColumn('delivery_tasks', 'notes')) {
            Schema::table('delivery_tasks', function (Blueprint $table) {
                $table->text('notes')->nullable()->after('status');
            });
        }

        // ============================================================
        // 2. FINANCIAL_TRANSACTIONS: Tambah po_number & maintenance_number
        // ============================================================
        if (!Schema::hasColumn('financial_transactions', 'po_number')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->string('po_number')->nullable()->after('description');
            });
        }
        if (!Schema::hasColumn('financial_transactions', 'maintenance_number')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->string('maintenance_number')->nullable()->after('po_number');
            });
        }

        // ============================================================
        // 3. HAPUS CHECK CONSTRAINT pada status (delivery_tasks) & category (financial_transactions)
        // Karena SQLite tidak support DROP CONSTRAINT, kita re-create tabel
        // ============================================================
        // --- 3a. Delivery Tasks ---
        Schema::rename('delivery_tasks', 'delivery_tasks_old');
        Schema::create('delivery_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('shipping_projects')->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->foreignId('driver_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('no_resi')->nullable();
            $table->string('status')->nullable()->default('draft'); // tanpa CHECK constraint
            $table->date('tanggal')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Copy data dari tabel lama
        DB::statement('INSERT INTO delivery_tasks 
            (id, project_id, vehicle_id, driver_id, client_id, no_resi, status, tanggal, notes, created_by, created_at, updated_at)
            SELECT id, project_id, vehicle_id, driver_id, client_id, no_resi, status, tanggal, notes, created_by, created_at, updated_at
            FROM delivery_tasks_old');

        Schema::dropIfExists('delivery_tasks_old');

        // --- 3b. Financial Transactions ---
        Schema::rename('financial_transactions', 'financial_transactions_old');
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->enum('type', ['income', 'expense']);
            $table->string('category', 255); // tanpa CHECK constraint
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('po_number')->nullable();
            $table->string('maintenance_number')->nullable();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('confirmed');
            $table->timestamps();
        });

        DB::statement('INSERT INTO financial_transactions 
            (id, transaction_date, type, category, amount, description, po_number, maintenance_number, vehicle_id, driver_id, client_id, created_by, status, created_at, updated_at)
            SELECT id, transaction_date, type, category, amount, description, po_number, maintenance_number, vehicle_id, driver_id, client_id, created_by, status, created_at, updated_at
            FROM financial_transactions_old');

        Schema::dropIfExists('financial_transactions_old');
    }

    public function down()
    {
        // Rollback sederhana: kembalikan ke struktur sebelumnya
        // (opsional)
    }
};