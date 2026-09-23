<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Mariam El-Masry: name without "Coach", Founder & CEO of Mega Team, bio, 4 years, events gallery. */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('team_members')->where('slug', 'mariam-el-masry')->update([
            'name' => 'Mariam El-Masry',
            'name_ar' => 'مريم المصري',
            'role' => 'Founder & CEO — Mega Team',
            'role_ar' => 'المؤسس والرئيس التنفيذي لفريق ميجا تيم',
            'bio' => "I'm Mariam El-Masry — ambitious, and a believer that reaching your dream is not impossible. A dream was never just imagination; it's the beginning of an idea that can become reality through effort, learning and persistence.\n\nI started my journey with simple steps, and with every step I learned that success isn't a paved road and that every challenge adds experience. The journey has now been going for 4 years, and together we've made more than 500 growth stories.\n\nI'm passionate about social media — marketing and trading — and about building a real personal brand.\n\nA dream isn't imagination — a dream is a beginning.",
            'bio_ar' => "أنا مريم المصري، شخصية طموحة تؤمن أن الوصول للحلم مش مستحيل، وإن الحلم عمره ما كان مجرد خيال؛ لكنه بداية لفكرة ممكن تتحول لحقيقة مع السعي، التعلم، والاستمرار.\n\nبدأت رحلتي بخطوات بسيطة، ومع كل خطوة اتعلمت إن النجاح مش طريق مُمهد، وإن كل تحدي بيضيف خبرة. وحاليًا الرحلة مستمرة من 4 سنين، وعملنا أكتر من 500 Growth Stories.\n\nبهتم بمجال السوشيال ميديا (الماركتينج والتداول) وبناء Personal Brand حقيقي.\n\nالحلم مش خيال، الحلم بداية.",
            'experience_years' => 4,
            'company_id' => DB::table('companies')->where('logo', 'media/companies/mega-team.webp')->value('id'),
            'gallery' => json_encode(array_map(fn ($n) => "media/team/mariam/event-{$n}.webp", range(1, 8))),
            'updated_at' => now(),
        ]);
    }

    public function down(): void {}
};
