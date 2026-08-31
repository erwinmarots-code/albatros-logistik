<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->string('reference_type')->nullable()->after('description');
            $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
        });
    }

    public function down()
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropColumn(['reference_type', 'reference_id']);
        });
    }
};