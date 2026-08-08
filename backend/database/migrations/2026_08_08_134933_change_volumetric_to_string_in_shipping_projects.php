<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_projects', function (Blueprint $table) {
            $table->string('volumetric')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('shipping_projects', function (Blueprint $table) {
            $table->decimal('volumetric', 10, 2)->nullable()->change();
        });
    }
};