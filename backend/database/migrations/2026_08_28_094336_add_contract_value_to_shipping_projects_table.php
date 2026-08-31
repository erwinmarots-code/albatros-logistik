<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('shipping_projects', 'contract_value')) {
            Schema::table('shipping_projects', function (Blueprint $table) {
                $table->decimal('contract_value', 15, 2)->nullable()->after('goods_value');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('shipping_projects', 'contract_value')) {
            Schema::table('shipping_projects', function (Blueprint $table) {
                $table->dropColumn('contract_value');
            });
        }
    }
};