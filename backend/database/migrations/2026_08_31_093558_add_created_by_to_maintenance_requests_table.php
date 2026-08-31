<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('maintenance_requests', 'created_by')) {
            Schema::table('maintenance_requests', function (Blueprint $table) {
                $table->foreignId('created_by')->after('branch_id')->nullable()->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('maintenance_requests', 'created_by')) {
            Schema::table('maintenance_requests', function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            });
        }
    }
};