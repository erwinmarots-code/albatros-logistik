<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('maintenance_requests', 'branch_id')) {
            Schema::table('maintenance_requests', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('executed_at')->constrained('branches')->onDelete('set null');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('maintenance_requests', 'branch_id')) {
            Schema::table('maintenance_requests', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            });
        }
    }
};