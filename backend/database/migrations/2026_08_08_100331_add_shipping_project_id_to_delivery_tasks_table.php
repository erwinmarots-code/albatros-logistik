<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('delivery_tasks', function (Blueprint $table) {
            $table->foreignId('shipping_project_id')->nullable()->constrained('shipping_projects')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('delivery_tasks', function (Blueprint $table) {
            $table->dropForeign(['shipping_project_id']);
            $table->dropColumn('shipping_project_id');
        });
    }
};