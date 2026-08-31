<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Tabel permissions (daftar menu/akses)
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g. 'dashboard', 'clients', 'projects'
            $table->string('label')->nullable(); // Label untuk tampilan, e.g. 'Dashboard', 'Client'
            $table->string('group')->nullable(); // Kelompok menu, e.g. 'Data Master', 'Operasional'
            $table->timestamps();
        });

        // Tabel role_permission (hubungan role dengan permission)
        Schema::create('role_permission', function (Blueprint $table) {
            $table->id();
            $table->string('role'); // nama role, sesuai dengan nilai di kolom 'role' user
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['role', 'permission_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
    }
};