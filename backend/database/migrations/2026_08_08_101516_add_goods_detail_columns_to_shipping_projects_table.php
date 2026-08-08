<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('shipping_projects', function (Blueprint $table) {
            // Hapus kolom goods_description jika ingin diganti, atau biarkan sebagai keterangan tambahan
            // Kita pertahankan goods_description untuk catatan tambahan
            $table->decimal('weight_kg', 10, 2)->nullable()->after('goods_description');
            $table->integer('collie')->nullable()->after('weight_kg');
            $table->decimal('volumetric', 10, 2)->nullable()->after('collie');
            $table->decimal('ppn', 5, 2)->nullable()->after('volumetric'); // persentase PPN
            $table->decimal('discount', 10, 2)->nullable()->after('ppn');
            $table->decimal('insurance', 10, 2)->nullable()->after('discount');
            $table->decimal('goods_value', 15, 2)->nullable()->after('insurance');
        });
    }

    public function down()
    {
        Schema::table('shipping_projects', function (Blueprint $table) {
            $table->dropColumn(['weight_kg', 'collie', 'volumetric', 'ppn', 'discount', 'insurance', 'goods_value']);
        });
    }
};