<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename tabel lama
        Schema::rename('shipping_projects', 'shipping_projects_old');

        // 2. Buat tabel baru dengan kolom receiver yang nullable
        Schema::create('shipping_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('no_po')->unique();
            $table->string('no_resi')->nullable();
            $table->string('sender_name');
            $table->text('sender_address');
            $table->string('sender_phone');
            
            // 🔥 Ubah menjadi nullable
            $table->string('receiver_name')->nullable();
            $table->text('receiver_address')->nullable();
            $table->string('receiver_phone')->nullable();
            
            $table->text('goods_description')->nullable();
            $table->decimal('weight_kg', 10, 2)->nullable();
            $table->integer('collie')->nullable();
            $table->decimal('contract_value', 15, 2)->nullable();
            $table->enum('shipping_method', ['darat', 'udara', 'laut']);
            $table->enum('status', ['draft', 'confirmed', 'on_delivery', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 3. Copy data dari tabel lama ke tabel baru
        DB::statement("
            INSERT INTO shipping_projects (
                id, client_id, branch_id, no_po, no_resi, 
                sender_name, sender_address, sender_phone,
                receiver_name, receiver_address, receiver_phone,
                goods_description, weight_kg, collie, contract_value,
                shipping_method, status, notes, created_by, created_at, updated_at
            )
            SELECT 
                id, client_id, branch_id, no_po, no_resi,
                sender_name, sender_address, sender_phone,
                receiver_name, receiver_address, receiver_phone,
                goods_description, weight_kg, collie, contract_value,
                shipping_method, status, notes, created_by, created_at, updated_at
            FROM shipping_projects_old
        ");

        // 4. Hapus tabel lama
        Schema::dropIfExists('shipping_projects_old');

        // 5. Reset auto-increment
        DB::statement("DELETE FROM sqlite_sequence WHERE name='shipping_projects'");
        DB::statement("INSERT INTO sqlite_sequence (name, seq) SELECT 'shipping_projects', MAX(id) FROM shipping_projects");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback: kembalikan ke struktur lama (receiver NOT NULL)
        Schema::rename('shipping_projects', 'shipping_projects_new');

        Schema::create('shipping_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('no_po')->unique();
            $table->string('no_resi')->nullable();
            $table->string('sender_name');
            $table->text('sender_address');
            $table->string('sender_phone');
            // NOT NULL
            $table->string('receiver_name');
            $table->text('receiver_address');
            $table->string('receiver_phone');
            $table->text('goods_description')->nullable();
            $table->decimal('weight_kg', 10, 2)->nullable();
            $table->integer('collie')->nullable();
            $table->decimal('contract_value', 15, 2)->nullable();
            $table->enum('shipping_method', ['darat', 'udara', 'laut']);
            $table->enum('status', ['draft', 'confirmed', 'on_delivery', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::statement("INSERT INTO shipping_projects SELECT * FROM shipping_projects_new");
        Schema::dropIfExists('shipping_projects_new');
    }
};