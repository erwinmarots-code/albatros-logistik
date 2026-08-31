<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Hapus index unique email jika sudah ada (untuk menghindari error)
        DB::statement('DROP INDEX IF EXISTS users_email_unique');

        // Nonaktifkan foreign key sementara
        DB::statement('PRAGMA foreign_keys = OFF');

        // Buat tabel sementara dengan role sebagai string
        Schema::create('users_temp', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('staff'); // string, bukan enum
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->rememberToken();
            $table->timestamps();
        });

        // Copy data dari tabel users ke users_temp
        DB::statement('
            INSERT INTO users_temp (id, name, email, email_verified_at, password, role, branch_id, remember_token, created_at, updated_at)
            SELECT id, name, email, email_verified_at, password, role, branch_id, remember_token, created_at, updated_at
            FROM users
        ');

        // Drop tabel users lama
        Schema::drop('users');

        // Rename users_temp menjadi users
        Schema::rename('users_temp', 'users');

        // Reset auto-increment
        DB::statement("DELETE FROM sqlite_sequence WHERE name='users'");
        DB::statement("INSERT INTO sqlite_sequence (name, seq) SELECT 'users', MAX(id) FROM users");

        // Aktifkan kembali foreign key
        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down()
    {
        // Rollback: kembalikan ke struktur lama jika diperlukan
        DB::statement('PRAGMA foreign_keys = OFF');

        Schema::rename('users', 'users_rollback');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['super_admin', 'admin_project', 'admin_transport', 'admin_finance', 'branch_admin', 'staff'])->default('staff');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->rememberToken();
            $table->timestamps();
        });

        DB::statement('INSERT INTO users SELECT * FROM users_rollback');
        Schema::drop('users_rollback');
        DB::statement('PRAGMA foreign_keys = ON');
    }
};