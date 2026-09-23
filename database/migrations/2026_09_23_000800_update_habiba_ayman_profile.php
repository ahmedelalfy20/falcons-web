<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Habiba Ayman: name without "Coach", Founder & CEO of Million Team, bio and events gallery. */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('team_members')->where('slug', 'habiba-ayman')->update([
            'name' => 'Habiba Ayman',
            'name_ar' => 'حبيبة أيمن',
            'role' => 'Founder & CEO — Million Team',
            'role_ar' => 'المؤسس والرئيس التنفيذي لفريق مليون تيم',
            'bio' => "Habiba Ayman — ambitious, and a firm believer that success isn't just arriving somewhere, but a journey of learning, growth and persistence.\n\nI started my journey with simple steps, and over time I learned that every challenge makes me stronger and every experience adds something new.\n\nI'm passionate about networking, social media and trading, and I'm always working to develop myself and build something that carries my own mark. I love to learn, to work on myself, and to turn my ideas into real goals.",
            'bio_ar' => "حبيبة أيمن، شخصية طموحة ومؤمنة إن النجاح مش مجرد وصول، لكنه رحلة من التعلم، التطور، والاستمرار.\n\nبدأت رحلتي بخطوات بسيطة، ومع الوقت اتعلمت إن كل تحدي بيقوّيني، وكل تجربة بتضيف ليا حاجة جديدة.\n\nبهتم بمجال النتورك والسوشيال ميديا والتداول، وبسعى دايمًا إني أطور من نفسي وأبني حاجة تكون بصمتي الخاصة بيا. بحب أتعلم، أشتغل على نفسي، وأحوّل أفكاري لأهداف حقيقية.",
            'company_id' => DB::table('companies')->where('logo', 'media/companies/million-team.webp')->value('id'),
            'gallery' => json_encode(array_map(fn ($n) => "media/team/habiba/event-{$n}.webp", range(1, 6))),
            'updated_at' => now(),
        ]);
    }

    public function down(): void {}
};
