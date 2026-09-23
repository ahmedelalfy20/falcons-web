<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Leaders order: Mariam, Habiba, Rahma, Ahmed, Ibrahim. */
return new class extends Migration
{
    public const ORDER = ['mariam-el-masry', 'habiba-ayman', 'rahma-mohamed', 'ahmed-el-deeb', 'ibrahim-mohamed'];

    public function up(): void
    {
        foreach (self::ORDER as $i => $slug) {
            DB::table('team_members')->where('slug', $slug)->update(['sort_order' => $i + 1]);
        }
    }

    public function down(): void {}
};
