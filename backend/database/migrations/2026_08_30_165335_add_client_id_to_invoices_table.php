<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('invoices', 'client_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreignId('client_id')->after('shipping_project_id')->constrained('clients')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('invoices', 'client_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropForeign(['client_id']);
                $table->dropColumn('client_id');
            });
        }
    }
};