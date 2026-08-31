<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Karena SQLite tidak bisa drop constraint, kita rename tabel, buat baru, copy data
        Schema::rename('financial_transactions', 'financial_transactions_old');

        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->enum('type', ['income', 'expense']);
            $table->string('category', 255);  // tanpa CHECK constraint
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('confirmed');
            $table->string('po_number')->nullable();
            $table->string('maintenance_number')->nullable();
            $table->timestamps();
        });

        // Copy data dari tabel lama
        DB::statement('INSERT INTO financial_transactions 
            (id, transaction_date, type, category, amount, description, vehicle_id, driver_id, client_id, created_by, status, po_number, maintenance_number, created_at, updated_at)
            SELECT id, transaction_date, type, category, amount, description, vehicle_id, driver_id, client_id, created_by, status, po_number, maintenance_number, created_at, updated_at
            FROM financial_transactions_old');

        Schema::dropIfExists('financial_transactions_old');
    }

    public function down()
    {
        // Rollback: kembalikan ke struktur lama (dengan enum constraint)
        Schema::rename('financial_transactions', 'financial_transactions_new');
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->enum('type', ['income', 'expense']);
            $table->enum('category', ['service','fuel','toll','parking','salary','client_payment','other']);
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('confirmed');
            $table->timestamps();
        });
        DB::statement('INSERT INTO financial_transactions SELECT * FROM financial_transactions_new');
        Schema::dropIfExists('financial_transactions_new');
    }
};