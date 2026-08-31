<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('delivery_tasks', function (Blueprint $table) {
            $table->string('no_resi')->unique()->nullable()->after('project_id');
        });
    }

    public function down()
    {
        Schema::table('delivery_tasks', function (Blueprint $table) {
            $table->dropColumn('no_resi');
        });
    }
};