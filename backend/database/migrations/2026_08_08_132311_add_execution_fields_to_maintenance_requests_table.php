<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->timestamp('executed_at')->nullable()->after('approved_at');
            $table->boolean('is_executed')->default(false)->after('executed_at');
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete()->after('is_executed');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropForeign(['executed_by']);
            $table->dropColumn(['executed_at', 'is_executed', 'executed_by']);
        });
    }
};