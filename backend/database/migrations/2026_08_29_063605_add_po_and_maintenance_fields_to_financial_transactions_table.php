<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->string('po_number')->nullable()->after('description');
            $table->string('maintenance_number')->nullable()->after('po_number');
        });
    }

    public function down()
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropColumn(['po_number', 'maintenance_number']);
        });
    }
};