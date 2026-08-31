<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('maintenance_requests', 'request_code')) {
            Schema::table('maintenance_requests', function (Blueprint $table) {
                $table->string('request_code')->nullable()->unique();
            });
        }
        if (!Schema::hasColumn('maintenance_requests', 'actual_cost')) {
            Schema::table('maintenance_requests', function (Blueprint $table) {
                $table->decimal('actual_cost', 15, 2)->nullable()->after('estimated_cost');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('maintenance_requests', 'request_code')) {
            Schema::table('maintenance_requests', function (Blueprint $table) {
                $table->dropColumn('request_code');
            });
        }
        if (Schema::hasColumn('maintenance_requests', 'actual_cost')) {
            Schema::table('maintenance_requests', function (Blueprint $table) {
                $table->dropColumn('actual_cost');
            });
        }
    }
};