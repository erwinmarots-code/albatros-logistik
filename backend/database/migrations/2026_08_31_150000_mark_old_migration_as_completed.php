<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Cek apakah migrasi sudah ada di tabel migrations
        $exists = DB::table('migrations')
            ->where('migration', '2026_08_29_071223_fix_financial_transactions_category_constraint')
            ->exists();

        if (!$exists) {
            DB::table('migrations')->insert([
                'migration' => '2026_08_29_071223_fix_financial_transactions_category_constraint',
                'batch' => DB::table('migrations')->max('batch') + 1
            ]);
        }
    }

    public function down()
    {
        DB::table('migrations')
            ->where('migration', '2026_08_29_071223_fix_financial_transactions_category_constraint')
            ->delete();
    }
};