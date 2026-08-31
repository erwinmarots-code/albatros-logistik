<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            // Tambah kolom request_code (unique)
            if (!Schema::hasColumn('maintenance_requests', 'request_code')) {
                $table->string('request_code')->nullable()->unique()->after('id');
            }
            // Tambah kolom actual_cost
            if (!Schema::hasColumn('maintenance_requests', 'actual_cost')) {
                $table->decimal('actual_cost', 15, 2)->nullable()->after('estimated_cost');
            }
        });
    }

    public function down()
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropColumn(['request_code', 'actual_cost']);
        });
    }
};