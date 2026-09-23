<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Ahmed El-Deeb: name without "Coach", bio, 4 years of experience and events gallery. */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('team_members')->where('slug', 'ahmed-el-deeb')->update([
            'name' => 'Ahmed El-Deeb',
            'name_ar' => 'أحمد الديب',
            'bio' => "An expert and specialist in trading and financial investment, with broad experience in managing and developing financial businesses.\n\nHe works on delivering innovative investment solutions and an integrated trading environment that empowers traders and investors to reach their financial goals in global markets — built on well-studied strategies and professional risk management.",
            'bio_ar' => "خبير ومتخصص في مجال التداول والاستثمار المالي، مع خبرة واسعة في إدارة وتطوير الأعمال المالية.\n\nيعمل على تقديم حلول استثمارية مبتكرة وتوفير بيئة تداول متكاملة تهدف إلى تمكين التُّجار والمستثمرين من تحقيق أهدافهم المالية في الأسواق العالمية بناءً على استراتيجيات مدروسة وإدارة مخاطر احترافية.",
            'experience_years' => 4,
            'gallery' => json_encode(array_map(fn ($n) => "media/team/ahmed/event-{$n}.webp", range(1, 11))),
            'updated_at' => now(),
        ]);
    }

    public function down(): void {}
};
