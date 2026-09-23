<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\FreeVideo;
use App\Models\GalleryImage;
use App\Models\Scanner;
use App\Models\Setting;
use App\Models\TeamMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Website content migrated from the previous site (repository translations,
 * component data and the live API). Idempotent: safe to run repeatedly.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->settings();
        $this->scanners();
        $this->team();
        $this->courses();
        $this->gallery();
        $this->freeVideos();
    }

    private function settings(): void
    {
        $defaults = [
            'contact' => [
                'whatsapp' => '201044416826',
                'whatsapp_message' => 'Hello, I want to join Falcons Academy',
                'whatsapp_message_ar' => 'مرحباً، أريد الانضمام لأكاديمية فالكونز',
                'register_url' => 'https://forms.gle/zCtM7iUjqDoo948KA',
                'email' => null,
            ],
            'app' => [
                'play_url' => 'https://play.google.com/store/apps/details?id=com.falcons.lms',
                'appstore_url' => 'https://apps.apple.com/eg/app/falcons-edu/id6760587004',
            ],
            'social' => [
                'telegram' => 'https://t.me/FALCONSORGANIZATIONN',
                'facebook' => 'https://www.facebook.com/share/1VQrUgtut9/',
                'instagram' => 'https://www.instagram.com/falc.ons111',
                'tiktok' => null,
                'youtube' => null,
            ],
            'hero' => [
                'badge' => '#1 Trading Academy in MENA',
                'badge_ar' => 'أكاديمية التداول رقم 1 في الشرق الأوسط',
                'title' => 'Master the Art of Professional Trading',
                'title_ar' => 'أتقن فن التداول الاحترافي',
                'subtitle' => 'Transform your trading journey with expert mentorship, proven strategies, and a supportive community of successful traders.',
                'subtitle_ar' => 'حوّل رحلتك في التداول مع إرشاد الخبراء واستراتيجيات مثبتة ومجتمع داعم من المتداولين الناجحين.',
                'primary_cta' => 'Join Falcons Academy',
                'primary_cta_ar' => 'انضم لأكاديمية فالكونز',
                'secondary_cta' => 'Register Now',
                'secondary_cta_ar' => 'سجل الآن',
            ],
            'founder' => [
                'name' => 'Mahmoud Magdy',
                'name_ar' => 'محمود مجدي',
                'title' => 'Founder & Head Educator',
                'title_ar' => 'المؤسس والمدرب الرئيسي',
                'image' => 'media/founder.webp',
                'bio_1' => 'With over 5 years of trading experience in global markets, Mahmoud Magdy has established himself as one of the leading trading educators in the MENA region. His journey from a passionate beginner to a successful professional trader inspired him to create Falcons Academy.',
                'bio_1_ar' => 'مع أكثر من 5 أعوام من الخبرة في التداول في الأسواق العالمية، أثبت محمود مجدي نفسه كواحد من أبرز معلمي التداول في منطقة الشرق الأوسط وشمال أفريقيا. رحلته من مبتدئ شغوف إلى متداول محترف ناجح ألهمته لإنشاء أكاديمية فالكونز.',
                'bio_2' => 'Mahmoud has mentored thousands of traders, helping them develop the skills, mindset, and discipline needed to succeed in the markets. His teaching philosophy combines technical expertise with practical wisdom, ensuring students not only learn strategies but also understand when and how to apply them.',
                'bio_2_ar' => 'قام محمود بتوجيه آلاف المتداولين، مساعدتهم على تطوير المهارات والعقلية والانضباط اللازمة للنجاح في الأسواق. فلسفته التعليمية تجمع بين الخبرة الفنية والحكمة العملية، مما يضمن أن الطلاب لا يتعلمون الاستراتيجيات فحسب، بل يفهمون أيضًا متى وكيف يطبقونها.',
                'quote' => 'Success in trading isn\'t about finding the perfect strategy — it\'s about building the perfect mindset and having the right community to support your journey.',
                'quote_ar' => 'النجاح في التداول لا يتعلق بإيجاد الاستراتيجية المثالية — بل يتعلق ببناء العقلية المثالية ووجود المجتمع المناسب لدعم رحلتك.',
                'mission_quote' => 'My mission is to empower traders with the knowledge and skills they need to succeed. Trading is a journey, not a destination, and I\'m here to guide you every step of the way.',
                'mission_quote_ar' => 'مهمتي هي تمكين المتداولين بالمعرفة والمهارات التي يحتاجونها للنجاح. التداول رحلة وليس وجهة، وأنا هنا لإرشادك في كل خطوة على الطريق.',
            ],
            'vision' => [
                'vision' => [
                    'Building the strongest community of professional traders worldwide',
                    'Creating an environment where knowledge and experience are shared freely',
                    'Establishing the gold standard for trading education and mentorship',
                ],
                'vision_ar' => [
                    'بناء أقوى مجتمع من المتداولين المحترفين في جميع أنحاء العالم',
                    'خلق بيئة يتم فيها مشاركة المعرفة والخبرة بحرية',
                    'إنشاء المعيار الذهبي لتعليم وإرشاد التداول',
                ],
                'mission' => [
                    'Educate traders with proven, real-world strategies and techniques',
                    'Mentor individuals to develop the psychological resilience for trading',
                    'Guide every student to achieve consistent, long-term success',
                    'Foster a supportive community that celebrates growth and learning',
                ],
                'mission_ar' => [
                    'تثقيف المتداولين باستراتيجيات وتقنيات مثبتة في العالم الحقيقي',
                    'إرشاد الأفراد لتطوير المرونة النفسية للتداول',
                    'توجيه كل طالب لتحقيق نجاح ثابت طويل الأمد',
                    'تعزيز مجتمع داعم يحتفل بالنمو والتعلم',
                ],
            ],
            'about' => [
                'story_1' => 'Falcons Academy was founded with a singular vision: to create a community where aspiring traders can transform into confident, successful professionals. We believe that trading is not just about numbers and charts — it\'s about mindset, discipline, and continuous growth.',
                'story_1_ar' => 'تأسست أكاديمية فالكونز برؤية واحدة: إنشاء مجتمع حيث يمكن للمتداولين الطموحين التحول إلى محترفين واثقين وناجحين. نؤمن بأن التداول ليس فقط عن الأرقام والرسوم البيانية — بل يتعلق بالعقلية والانضباط والنمو المستمر.',
                'story_2' => 'We believe in developing the complete trader — someone who understands not just technical analysis, but also risk management, trading psychology, and market dynamics. Our comprehensive approach ensures that every student receives personalized attention and guidance.',
                'story_2_ar' => 'نؤمن بتطوير المتداول الكامل — شخص يفهم ليس فقط التحليل الفني، ولكن أيضًا إدارة المخاطر وعلم نفس التداول وديناميكيات السوق. نهجنا الشامل يضمن أن كل طالب يحصل على اهتمام وتوجيه شخصي.',
                'story_3' => 'Join us on this transformative journey where knowledge meets practice, and ambition meets achievement. Together, we\'ll build your path to trading success.',
                'story_3_ar' => 'انضم إلينا في هذه الرحلة التحويلية حيث تلتقي المعرفة بالممارسة، والطموح بالإنجاز. معًا، سنبني طريقك إلى النجاح في التداول.',
                'quote' => 'Success in trading is not about luck, it\'s about discipline, strategy, and continuous learning. Every successful trader was once a beginner who refused to give up.',
                'quote_ar' => 'النجاح في التداول ليس عن الحظ، بل عن الانضباط والاستراتيجية والتعلم المستمر. كل متداول ناجح كان في يوم من الأيام مبتدئًا رفض الاستسلام.',
            ],
            'stats' => [
                'active_members' => 12000,
                'countries' => 18,
                'students_trained' => 12000,
                'satisfaction_rate' => 95,
                'years_experience' => 5,
            ],
            // Server-side invite code for the free courses (was hard-coded in the old frontend).
            'free_courses' => [
                'invite_code_hash' => Hash::make('1000000'),
                'enabled' => true,
            ],
        ];

        foreach ($defaults as $key => $value) {
            if (Setting::find($key) === null) {
                Setting::put($key, $value);
            }
        }
    }

    private function scanners(): void
    {
        $scanners = [
            ['slug' => 'wolf', 'accent' => '#6EA8FE', 'sort_order' => 1] + require database_path('data/wolf.php'),
            [
                'slug' => 'falcon', 'name' => 'Falcon', 'name_ar' => 'Falcon', 'accent' => '#F5B454', 'sort_order' => 2,
                'timeframe' => 'M5 – H1', 'methodology' => 'Volume Profile', 'targets' => 5,
                'tagline' => 'Volume Profile Trading System', 'tagline_ar' => 'نظام تداول Volume Profile',
                'summary' => 'Advanced system based on the Volume Profile methodology for high-precision signals.',
                'summary_ar' => 'نظام متطور يعتمد على منهجية Volume Profile لإشارات عالية الدقة.',
                'description' => "The FALCON indicator is an advanced trading system that operates on various timeframes from 5 minutes to 1 hour, relying on Volume Profile methodology to identify price behavior zones with real trading density. The indicator generates highly accurate bullish or bearish signals, displaying two optimal entry zones according to liquidity positioning and volume distribution within the movement.\n\nThe indicator provides a clear structure for trade management through five target levels for profit realization: TP1 – TP2 – TP3 – TP4 – TP5, in addition to a precisely calculated SL level according to Volume Profile zone boundaries.\n\nFALCON gives you a professional vision built on volume analysis and price balance, helping to capture the best trend opportunities with precise target identification and clarity in entry zones across short and medium timeframes.",
                'description_ar' => "مؤشر FALCON هو نظام تداول متطور يعمل على مختلف الأطر الزمنية بدءًا من فريم 5 دقائق وحتى فريم الساعة، ويعتمد في قراءاته على منهجية Volume Profile لتحديد مناطق السلوك السعري ذات الكثافة الحقيقية في التداول. يقوم المؤشر بتوليد إشارات صعود أو هبوط عالية الدقة، مع عرض منطقتين مثاليتين للدخول وفقًا لتمركز السيولة وتوزيع الأحجام داخل الحركة.\n\nيوفر المؤشر هيكلًا واضحًا لإدارة الصفقة من خلال خمسة مستويات مستهدفة لتحقيق الأرباح: TP1 – TP2 – TP3 – TP4 – TP5، إضافةً إلى مستوى SL محسوب بدقة وفقًا لحدود مناطق الفوليم بروفايل.\n\nيمنحك مؤشر FALCON رؤية احترافية مبنية على تحليلات الحجم والتوازن السعري، مما يساعد على التقاط أفضل فرص الاتجاه مع تحديد دقيق للأهداف ووضوح في مناطق الدخول عبر الأطر الزمنية القصيرة والمتوسطة.",
                'features' => [
                    ['title' => 'Multi-Timeframe', 'title_ar' => 'متعدد الفريمات', 'text' => '5min to 1H flexibility', 'text_ar' => 'مرونة من 5 دقائق إلى ساعة'],
                    ['title' => '5 TP Levels', 'title_ar' => '5 مستويات TP', 'text' => 'Extended profit targets', 'text_ar' => 'أهداف ربح ممتدة'],
                    ['title' => 'Volume Based SL', 'title_ar' => 'SL مبني على الحجم', 'text' => 'Profile zone boundaries', 'text_ar' => 'حدود مناطق البروفايل'],
                    ['title' => 'Dual Entry Zones', 'title_ar' => 'منطقتين دخول', 'text' => 'Two optimal entry points', 'text_ar' => 'نقطتين دخول مثاليتين'],
                ],
                'images' => $this->scannerImages('falcon', 3),
            ],
            [
                'slug' => 'dragons', 'name' => 'Dragons', 'name_ar' => 'Dragons', 'accent' => '#4ADE9A', 'sort_order' => 3,
                'timeframe' => 'M5 – H1', 'methodology' => 'Trend following', 'targets' => 3,
                'tagline' => 'Trend Following & Scalping System', 'tagline_ar' => 'نظام تتبع الاتجاه والسكالبينج',
                'summary' => 'Trend-following tool using moving averages for scalping and swing trades.',
                'summary_ar' => 'أداة تتبع الاتجاه باستخدام المتوسطات المتحركة للسكالبينج والسوينغ.',
                'description' => "The DRAGONS indicator is an advanced trading tool that relies on the principle of trend following using a set of moving averages designed to capture moments of price strength with high precision. The indicator aims to provide clear and applicable trading signals across various timeframes, with special focus on achieving the best scalping opportunities on 5-minute to 30-minute frames, in addition to providing strong swing trades when used on the hourly timeframe.\n\nThe indicator displays Long and Short signals according to the adopted trend structure, providing three price targets for each trade to facilitate position management and improve the risk-to-reward ratio. The indicator also includes a \"Protection Wave\" that acts as an additional directional filter; if this wave is in the same direction as the signal, it strengthens the trade and supports its probability of success.\n\nDRAGONS also features a dynamic panel displaying a list of pairs or currencies that have shown an entry signal at the current time, making it easier for the trader to monitor the market and make quick decisions without needing to switch between charts.\n\nThe indicator is characterized by combining reading simplicity with algorithm power, making it suitable for traders looking for a professional tool that gives them a clearer view of the trend and precise entry opportunities in the market.",
                'description_ar' => "مؤشر Dragons هو أداة تداول متقدمة تعتمد على مبدأ تتبّع الاتجاه باستخدام مجموعة من المتوسطات المتحركة المصممة لالتقاط لحظات قوة السعر بدقة عالية. يهدف المؤشر إلى توفير إشارات تداول واضحة وقابلة للتطبيق على مختلف الأطر الزمنية، مع تركيز خاص على تحقيق أفضل فرص السكالبينج على فريم 5 دقائق حتى 30 دقيقة، بالإضافة إلى تقديم صفقات سوينغ قوية عند استخدامه على الإطار الزمني ساعة.\n\nيعرض المؤشر إشارات Long و Short وفقاً لهيكلة الاتجاه المعتمدة، مع توفير ثلاثة أهداف سعرية لكل صفقة لتسهيل إدارة المراكز وتحسين نسبة العائد إلى المخاطرة. كما يتضمن المؤشر \"موجة حماية\" تعمل كمرشح اتجاهي إضافي؛ فإذا كانت هذه الموجة في نفس اتجاه الإشارة، فهذا يعزز قوّة الصفقة ويدعم احتمالية نجاحها.\n\nيحتوي مؤشر Dragons أيضاً على لوحة ديناميكية تعرض قائمة الأزواج أو العملات التي ظهرت عليها إشارة دخول في الوقت الحالي، مما يسهّل على المتداول مراقبة السوق واتخاذ قرارات سريعة دون الحاجة للتنقّل بين الرسوم البيانية.\n\nيتميز المؤشر بالجمع بين بساطة القراءة وقوّة الخوارزمية، مما يجعله مناسباً للمتداولين الذين يبحثون عن أداة احترافية تمنحهم رؤية أوضح للاتجاه وفرص دخول دقيقة في السوق.",
                'features' => [
                    ['title' => 'Scalp & Swing', 'title_ar' => 'سكالب وسوينغ', 'text' => '5min to 1H versatility', 'text_ar' => 'تنوع من 5 دقائق إلى ساعة'],
                    ['title' => '3 TP Levels', 'title_ar' => '3 مستويات TP', 'text' => 'Optimized targets per trade', 'text_ar' => 'أهداف محسّنة لكل صفقة'],
                    ['title' => 'Protection Wave', 'title_ar' => 'موجة الحماية', 'text' => 'Additional trend filter', 'text_ar' => 'فلتر اتجاهي إضافي'],
                    ['title' => 'Dynamic Panel', 'title_ar' => 'لوحة ديناميكية', 'text' => 'Real-time signal alerts', 'text_ar' => 'تنبيهات إشارات فورية'],
                ],
                'images' => $this->scannerImages('dragons', 4),
            ],
            [
                'slug' => 'sniper', 'name' => 'Sniper', 'name_ar' => 'Sniper', 'accent' => '#B89CFF', 'sort_order' => 4,
                'timeframe' => 'H1', 'methodology' => 'Zigzag structure', 'targets' => 3,
                'tagline' => 'Swing Trading Precision System', 'tagline_ar' => 'نظام دقة صفقات السوينغ',
                'summary' => 'Precise system analyzing peaks and troughs for swing trading on H1.',
                'summary_ar' => 'نظام دقيق يحلل القمم والقيعان لصفقات السوينغ على فريم الساعة.',
                'description' => "The SNIPER indicator is a precise trading system that relies on analyzing peaks and troughs and zigzag movement to extract strong reversal zones and identify medium to long-term market trends. The indicator is specifically designed to work on the hourly timeframe, making it suitable for swing trades that rely on wide price movements with higher probability.\n\nThe indicator precisely identifies the entry line and displays three target levels (Take Profit 1 – Take Profit 2 – Take Profit 3), in addition to a suggested Stop Loss calculated based on zigzag movement and peak/trough structure. This gives the trader complete clarity in trade management before entry.\n\nThe indicator also clearly displays the trade range, helping to visualize the expected movement space between the entry zone and targets. Although it provides three targets, the indicator prefers using only the first and second targets, as they represent the highest success rates and are most compatible with the nature of swing movements on this timeframe.\n\nSNIPER is distinguished by its ability to identify key reversal points and provide complete, clearly defined trading plans, making it an ideal tool for traders looking for calculated decisions based on actual price movement.",
                'description_ar' => "مؤشر SNIPER هو نظام تداول دقيق يعتمد على تحليل القمم والقيعان وحركة الزجزاج لاستخراج مناطق الانعكاس القوية وتحديد اتجاهات السوق المتوسطة إلى طويلة المدى. صُمّم المؤشر خصيصاً للعمل على الإطار الزمني ساعة، مما يجعله مناسباً لصفقات السوينغ التي تعتمد على تحركات سعرية واسعة وذات احتمالية أعلى.\n\nيقوم المؤشر بتحديد خط الدخول بدقة، ويعرض ثلاثة مستويات مستهدفة (Take Profit 1 – Take Profit 2 – Take Profit 3)، إضافة إلى Stop Loss مقترح محسوب بناءً على حركة الزجزاج وبُنية القيعان والقمم. هذا يتيح للمتداول وضوحاً كاملاً في إدارة الصفقة قبل الدخول.\n\nكما يعرض المؤشر مجال الصفقة بشكل واضح، مما يساعد على تصور مساحة الحركة المتوقعة بين منطقة الدخول والأهداف. ورغم أنه يوفر ثلاثة أهداف، إلا أن المؤشر يُفضل استخدام الهدف الأول والثاني فقط، حيث يمثلان أعلى نسب نجاح وأكثر توافقاً مع طبيعة تحركات السوينغ على هذا الفريم.\n\nيمتاز مؤشر SNIPER بقدرته على تحديد نقاط الانعكاس الرئيسية وتقديم خطط تداول كاملة واضحة المعالم، مما يجعله أداة مثالية للمتداول الذي يبحث عن قرارات محسوبة ومبنية على حركة السعر الفعلية.",
                'features' => [
                    ['title' => 'H1 Swing Focus', 'title_ar' => 'سوينغ H1', 'text' => 'Optimized for swing trading', 'text_ar' => 'محسّن لتداول السوينغ'],
                    ['title' => '3 TP Levels', 'title_ar' => '3 مستويات TP', 'text' => 'TP1 & TP2 recommended', 'text_ar' => 'يُنصح بـ TP1 و TP2'],
                    ['title' => 'Zigzag Based', 'title_ar' => 'مبني على الزجزاج', 'text' => 'Structure-based analysis', 'text_ar' => 'تحليل مبني على الهيكل'],
                    ['title' => 'Trade Range', 'title_ar' => 'مجال الصفقة', 'text' => 'Visual movement space', 'text_ar' => 'مساحة الحركة المرئية'],
                ],
                'images' => $this->scannerImages('sniper', 5),
            ],
        ];

        foreach ($scanners as $data) {
            Scanner::firstOrCreate(['slug' => $data['slug']], $data);
        }
    }

    private function scannerImages(string $slug, int $count): array
    {
        return array_map(fn ($i) => "media/scanners/{$slug}/{$i}.webp", range(1, $count));
    }

    private function team(): void
    {
        // Amr Samir removed from the public leaders list (requested change). Bios pending from the client.
        $members = [
            ['slug' => 'rahma-mohamed', 'name' => 'Rahma Mohamed', 'name_ar' => 'رحمة محمد', 'role' => 'Founder & CEO — 3AQRAB Team', 'role_ar' => 'المؤسس والرئيس التنفيذي لفريق عقرب', 'city' => 'Cairo', 'city_ar' => 'القاهرة', 'image' => 'media/team/rahma.webp', 'sort_order' => 3],
            ['slug' => 'mariam-el-masry', 'name' => 'Mariam El-Masry', 'name_ar' => 'مريم المصري', 'role' => 'Founder & CEO — Mega Team', 'role_ar' => 'المؤسس والرئيس التنفيذي لفريق ميجا تيم', 'city' => 'Mansoura', 'city_ar' => 'المنصورة', 'image' => 'media/team/mariam.webp', 'sort_order' => 1],
            ['slug' => 'ahmed-el-deeb', 'name' => 'Ahmed El-Deeb', 'name_ar' => 'أحمد الديب', 'role' => 'Expert Trainer & Mentor', 'role_ar' => 'مدرب خبير ومرشد', 'city' => null, 'city_ar' => null, 'image' => 'media/team/ahmed.webp', 'sort_order' => 4],
            ['slug' => 'habiba-ayman', 'name' => 'Habiba Ayman', 'name_ar' => 'حبيبة أيمن', 'role' => 'Founder & CEO — Million Team', 'role_ar' => 'المؤسس والرئيس التنفيذي لفريق مليون تيم', 'city' => 'Alexandria', 'city_ar' => 'الإسكندرية', 'image' => 'media/team/habiba.webp', 'sort_order' => 2],
        ];
        $members[] = require database_path('data/ibrahim.php');
        foreach ($members as $m) {
            TeamMember::firstOrCreate(['slug' => $m['slug']], $m);
        }

        // Rahma's full profile (same content as migration 2026_09_23_000600 for existing installs).
        TeamMember::where('slug', 'rahma-mohamed')->whereNull('bio')->update([
            'bio' => "Trading & Marketing specialist with 3 years of experience teaching and developing trading and marketing skills.\n\nShe works to build a generation that is more aware of digital opportunities and of work and communication skills, through hands-on training and continuous development.",
            'bio_ar' => "متخصصة في تعليم وتطوير مهارات التداول والـ Marketing بخبرة 3 سنوات.\n\nتسعى لبناء جيل أكثر وعيًا بالفرص الرقمية ومهارات العمل والتواصل من خلال التدريب العملي والتطوير المستمر.",
            'experience_years' => 3,
            'company_id' => \App\Models\Company::where('logo', 'media/companies/3aqrab.webp')->value('id'),
            'gallery' => json_encode(array_map(fn ($n) => "media/team/rahma/event-{$n}.webp", [3, 2, 1, 4, 6, 7, 5])),
        ]);

        // Ahmed's full profile (same content as migration 2026_09_23_000700).
        TeamMember::where('slug', 'ahmed-el-deeb')->whereNull('bio')->update([
            'bio' => "An expert and specialist in trading and financial investment, with broad experience in managing and developing financial businesses.\n\nHe works on delivering innovative investment solutions and an integrated trading environment that empowers traders and investors to reach their financial goals in global markets — built on well-studied strategies and professional risk management.",
            'bio_ar' => "خبير ومتخصص في مجال التداول والاستثمار المالي، مع خبرة واسعة في إدارة وتطوير الأعمال المالية.\n\nيعمل على تقديم حلول استثمارية مبتكرة وتوفير بيئة تداول متكاملة تهدف إلى تمكين التُّجار والمستثمرين من تحقيق أهدافهم المالية في الأسواق العالمية بناءً على استراتيجيات مدروسة وإدارة مخاطر احترافية.",
            'experience_years' => 4,
            'gallery' => json_encode(array_map(fn ($n) => "media/team/ahmed/event-{$n}.webp", range(1, 11))),
        ]);

        // Habiba's full profile (same content as migration 2026_09_23_000800).
        TeamMember::where('slug', 'habiba-ayman')->whereNull('bio')->update([
            'bio' => "Habiba Ayman — ambitious, and a firm believer that success isn't just arriving somewhere, but a journey of learning, growth and persistence.\n\nI started my journey with simple steps, and over time I learned that every challenge makes me stronger and every experience adds something new.\n\nI'm passionate about networking, social media and trading, and I'm always working to develop myself and build something that carries my own mark. I love to learn, to work on myself, and to turn my ideas into real goals.",
            'bio_ar' => "حبيبة أيمن، شخصية طموحة ومؤمنة إن النجاح مش مجرد وصول، لكنه رحلة من التعلم، التطور، والاستمرار.\n\nبدأت رحلتي بخطوات بسيطة، ومع الوقت اتعلمت إن كل تحدي بيقوّيني، وكل تجربة بتضيف ليا حاجة جديدة.\n\nبهتم بمجال النتورك والسوشيال ميديا والتداول، وبسعى دايمًا إني أطور من نفسي وأبني حاجة تكون بصمتي الخاصة بيا. بحب أتعلم، أشتغل على نفسي، وأحوّل أفكاري لأهداف حقيقية.",
            'experience_years' => 4,
            'company_id' => \App\Models\Company::where('logo', 'media/companies/million-team.webp')->value('id'),
            'gallery' => json_encode(array_map(fn ($n) => "media/team/habiba/event-{$n}.webp", range(1, 6))),
        ]);

        // Mariam's full profile (same content as migration 2026_09_23_000900).
        TeamMember::where('slug', 'mariam-el-masry')->whereNull('bio')->update([
            'bio' => "I'm Mariam El-Masry — ambitious, and a believer that reaching your dream is not impossible. A dream was never just imagination; it's the beginning of an idea that can become reality through effort, learning and persistence.\n\nI started my journey with simple steps, and with every step I learned that success isn't a paved road and that every challenge adds experience. The journey has now been going for 4 years, and together we've made more than 500 growth stories.\n\nI'm passionate about social media — marketing and trading — and about building a real personal brand.\n\nA dream isn't imagination — a dream is a beginning.",
            'bio_ar' => "أنا مريم المصري، شخصية طموحة تؤمن أن الوصول للحلم مش مستحيل، وإن الحلم عمره ما كان مجرد خيال؛ لكنه بداية لفكرة ممكن تتحول لحقيقة مع السعي، التعلم، والاستمرار.\n\nبدأت رحلتي بخطوات بسيطة، ومع كل خطوة اتعلمت إن النجاح مش طريق مُمهد، وإن كل تحدي بيضيف خبرة. وحاليًا الرحلة مستمرة من 4 سنين، وعملنا أكتر من 500 Growth Stories.\n\nبهتم بمجال السوشيال ميديا (الماركتينج والتداول) وبناء Personal Brand حقيقي.\n\nالحلم مش خيال، الحلم بداية.",
            'experience_years' => 4,
            'company_id' => \App\Models\Company::where('logo', 'media/companies/mega-team.webp')->value('id'),
            'gallery' => json_encode(array_map(fn ($n) => "media/team/mariam/event-{$n}.webp", range(1, 8))),
        ]);
    }

    private function courses(): void
    {
        $courses = [
            [
                'track' => 'trading', 'level' => 1, 'difficulty' => 'beginner',
                'title' => 'Level 1', 'title_ar' => 'المستوى ١',
                'subtitle' => 'TradingView basics and how to add currency pairs',
                'subtitle_ar' => 'أساسيات TradingView وكيفية إضافة أزواج العملات',
                'description' => "If you're eager to start trading but don't know where to begin, or are afraid of losses due to a lack of understanding… this course is designed specifically for you.\n\nWe'll guide you step-by-step from the basics of trading until you can confidently enter trades on your own, knowing when to enter and exit without losing your profits.\n\nIn this course, you'll learn:\n• The fundamentals of trading and the correct way to think before risking your money.\n• Professional use of the TradingView platform and adding currency pairs.\n• Reading Japanese candlesticks and understanding the relationship between price movement and currency pairs.\n• How to easily calculate pips and calculate profit and loss.\n• The concept of support and resistance as a key to understanding market movement.\n• The steps to entering a trade from start to finish.\n• Setting the target, stop-loss, and take-profit.\n• An explanation of the MetaTrader 5 trading application and how to enter trades yourself.\n• Methods for protecting your capital and securing your entry.\n• Identifying strong entry points using confluence of support and resistance levels.\n• Reading candlestick patterns through action and reaction.\n• Understanding trends and reversal moments.\n• Choosing the right timeframe and understanding why changing timeframes affects trading strategies.\n• Smart entry strategies and professional trade management.\n• Opening pending trades and entering at the best possible points.\n• Combining all the tools to make sound decisions and enter with confidence.\n\nWho is this course for?\n• Any beginner who wants to enter the world of trading and start on the right foot.\n• Anyone who has tried trading and lost money due to a lack of understanding.\n• Anyone who wants to build a reliable trading style instead of relying on predictions and luck.\n\nAfter completing the course, you will be able to:\n• Read charts independently.\n• Identify entry and exit points.\n• Calculate your profits and losses.\n• Manage your trades without fear.\n• Make confident and professional trading decisions.",
                'description_ar' => "لو نفسك تبدأ مجال التداول لكن مش عارف تبدأ منين، أو خايف من الخسارة بسبب قلة الفهم… الكورس ده معمول مخصوص عشانك.\nهنتدرّج معاك من أول مفهوم التداول لحد ما تدخل صفقات بنفسك بثقة، وتعرف إمتى تدخل وأمتى تخرج بدون ما تضيع أرباحك.\n\nهتتعلم في الكورس:\n• فهم أساسيات التداول وطريقة التفكير الصحيحة قبل ما تخاطر بفلوسك.\n• استخدام منصة TradingView باحتراف وإضافة أزواج العملات.\n• قراءة الشموع اليابانية وفهم العلاقة بين حركة السعر والأزواج.\n• كيفية حساب النقطة (Pip) وحساب الربح والخسارة بسهولة.\n• مفهوم الدعم والمقاومة كأهم مفتاح لحركة السوق.\n• خطوات الدخول في الصفقة من البداية للنهاية.\n• تحديد الهدف، وقف الخسارة (Stop Loss) وأخذ الربح (Take Profit).\n• شرح تطبيق التداول MetaTrader 5 وكيف تدخل صفقة بنفسك.\n• طرق حماية رأس المال وتأمين دخولك.\n• تحديد مناطق قوية للدخول باستخدام الدمج والتقاء الدعوم والمقاومات.\n• قراءة حركة الشموع من خلال الفعل ورد الفعل.\n• فهم الاتجاهات (الترند) ولحظات الانعكاس (الكسر).\n• اختيار الفريم المناسب ولماذا تغيير الفريم يغيّر طريقة الشغل.\n• استراتيجيات الدخول الذكي وإدارة الصفقات باحتراف.\n• فتح الصفقات المعلقة والدخول في أفضل مناطق ممكنة.\n• دمج كل الأدوات مع بعض لاتخاذ قرار صحيح والدخول بثقة.\n\nمين الكورس ده مناسب له؟\n• أي مبتدئ حب يدخل مجال التداول وعايز يبدأ صح.\n• أي شخص جرب التداول وخسر بسبب عدم الفهم.\n• اللي حابب يبني أسلوب تداول يعتمد عليه بدل التوقعات والحظ.\n\nبعد نهاية الكورس، هتكون قادر على:\n• قراءة الشارت بنفسك\n• تحديد أماكن الدخول والخروج\n• حساب أرباحك وخسارتك\n• إدارة صفقاتك بدون خوف\n• اتخاذ قرارات تداول بثقة واحتراف",
                'highlights' => [
                    'Complete foundation for beginners: understanding trading, reading candlesticks, and using TradingView & MetaTrader 5.',
                    'Learn market basics: pips, profit and loss, support & resistance, trends, and breakouts.',
                    'Professional skills: setting targets, stop loss, strong entry zones, and pending orders.',
                    'Final outcome: ability to read charts, make confident entry/exit decisions, and manage trades without fear.',
                ],
                'highlights_ar' => [
                    'تأسيس شامل للمبتدئين: فهم التداول، قراءة الشموع، واستخدام TradingView و MetaTrader 5.',
                    'تعلم أسس السوق: النقطة، الربح والخسارة، الدعم والمقاومة، الاتجاه والكسر.',
                    'مهارات احترافية: تحديد أهدافك، وقف الخسارة، مناطق الدخول القوية، والصفقات المعلقة.',
                    'نتيجة نهائية: القدرة على قراءة الشارت واتخاذ قرارات دخول وخروج بثقة وإدارة صفقات بدون خوف.',
                ],
            ],
            [
                'track' => 'trading', 'level' => 2, 'difficulty' => 'intermediate',
                'title' => 'Level 2', 'title_ar' => 'المستوى ٢',
                'subtitle' => 'Upgrade your trading skills with clear, practical strategies and start making decisions based on analysis, not guesswork.',
                'subtitle_ar' => 'طور مستواك في التداول باستراتيجيات عملية واضحة، وابدأ تاخد قرارات مبنية على تحليل مش تخمين.',
                'description' => "This level is designed for those who have already completed the basics and want to improve their trading skills in a practical and structured way, focusing on clear and easy-to-apply strategies within the market.\n\nIn this course, you will learn a set of simple, short-term strategies that you can rely on to achieve solid results, along with gaining a deeper understanding of market movements instead of relying on guesswork.\n\nCourse Content:\n• Proper capital management, and how to protect and grow your account gradually\n• Controlling emotions during trading and avoiding random decisions\n• Understanding engulfing candlesticks and how to use them to identify entry points\n• A simple and practical explanation of the MACD indicator and how to use it to confirm trades\n• Strategies using EMA to determine the overall market trend\n• Using the RSI indicator to identify overbought and oversold areas and find opportunities\n• Understanding liquidity in different forms and how to benefit from it in the market\n• Easy trend reversal strategies based on clear conditions\n• Using Fibonacci to determine targets and retracement levels\n• A dedicated module on news and its impact on the market, and how to deal with it\n\nThis course is suitable for an intermediate level and helps you develop your trading mindset, enabling you to make decisions based on analysis and understanding rather than randomness.\n\nThe goal is for you to trade with a clear strategy, reduce risk, and gradually increase your chances of success.",
                'description_ar' => "الليفل ده مخصص للناس اللي خلصت الأساسيات وعايزة تطور مستواها في التداول بشكل عملي ومنظم، وتركز على استراتيجيات واضحة وسهلة التطبيق داخل السوق.\n\nفي الكورس هتتعلم مجموعة من الاستراتيجيات البسيطة وقصيرة المدى، واللي تقدر تعتمد عليها في تحقيق نتائج كويسة، مع فهم أعمق لحركة السوق بدل الاعتماد على التخمين.\n\nمحتوى الكورس:\n• إدارة رأس المال بشكل صحيح، وازاي تحافظ على حسابك وتكبره تدريجياً\n• التحكم في المشاعر أثناء التداول، وازاي تتجنب القرارات العشوائية\n• فهم الشموع الابتلاعية وطريقة استخدامها في تحديد نقاط الدخول\n• شرح مبسط وعملي لمؤشر MACD واستخدامه في تأكيد الصفقات\n• استراتيجيات العمل باستخدام EMA وتحديد الاتجاه العام\n• استخدام مؤشر RSI لتحديد مناطق التشبع والفرص المناسبة\n• فهم مفهوم السيولة بأكثر من شكل، وازاي تستفيد منها في السوق\n• استراتيجيات تغيير الاتجاه بطريقة سهلة وعلى شرط واضح\n• استخدام الفيبوناتشي لتحديد الأهداف ونقاط الارتداد\n• كورس خاص بالأخبار وتأثيرها على السوق وازاي تتعامل معاها\n\nالكورس ده مناسب للمستوى المتوسط، وبيساعدك تطور طريقة تفكيرك في التداول، وتاخد قرارات مبنية على فهم وتحليل مش عشوائية.\n\nالهدف إنك تشتغل باستراتيجية واضحة، تقلل المخاطرة، وتزود فرص النجاح بشكل تدريجي.",
                'highlights' => [
                    'Capital management and emotional control during trading.',
                    'Practical indicator strategies: MACD, EMA, RSI and engulfing candles.',
                    'Liquidity, trend reversals and Fibonacci targets.',
                    'A dedicated module on news and its impact on the market.',
                ],
                'highlights_ar' => [
                    'إدارة رأس المال والتحكم في المشاعر أثناء التداول.',
                    'استراتيجيات عملية بالمؤشرات: MACD و EMA و RSI والشموع الابتلاعية.',
                    'السيولة وتغيير الاتجاه وأهداف الفيبوناتشي.',
                    'كورس خاص بالأخبار وتأثيرها على السوق.',
                ],
            ],
            ['track' => 'trading', 'level' => 3, 'difficulty' => 'advanced', 'title' => 'Level 3', 'title_ar' => 'المستوى ٣'],
            ['track' => 'trading', 'level' => 4, 'difficulty' => 'professional', 'title' => 'Level 4', 'title_ar' => 'المستوى ٤'],
            [
                'track' => 'marketing', 'level' => 1, 'difficulty' => 'beginner',
                'title' => 'Level 1', 'title_ar' => 'المستوى ١',
                'subtitle' => 'Master the essentials of marketing and team building for real results',
                'subtitle_ar' => 'إتقان أساسيات التسويق وبناء الفريق لتحقيق نتائج حقيقية',
                'description' => "Marketing Fundamentals Course: The Foundation of a Successful Team\n\nIf your goal is to launch a real marketing business and learn the right way to start, this course will be your first step towards building a strong foundation, understanding the business, and developing a team capable of achieving and growing results.\n\nWe'll focus on a strong marketing mindset, follow-up tools, influencing customers, building relationships, and managing invitations and follow-up professionally.\n\nWhat will you learn in the course?\n• Proper orientation and starting steps (Orientation).\n• How to set your goals with a dream list and real commitment (Dream List & Commitment).\n• Building a strong database to organize leads (Database).\n• Opportunity evaluation skills and positive approval (+A).\n• Understanding the commission plan and how to profit from marketing (Commission Plan).\n• Effective methods for finding clients and sending invitations (Prospecting & Inviting).\n• Follow-up techniques until closing opportunities (Follow-up).\n• Practical application of the SMP strategy to turn knowledge into results (Apply & SMP).\n• The importance of first impressions and how others see you (First Impressions).\n• Using social media for professional business growth.\n• Gaining practical marketing experience, not just information.\n• The fundamentals of building effective marketing (Fundamentals).\n• How your attitude and behavior impact results (Attitude).\n• The power of communication and building effective relationships.\n• Customer follow-up using organized schedules and professional strategies.\n• Building credibility and marketing influence (Advocacy).\n• Understanding the direct and indirect action plan (Plan 2 / Plan 3) for building a strong team.\n\nWho is this course for?\n• Anyone who wants to enter the field of marketing and build a real business.\n• Those working in marketing but lacking a system and strategy.\n• Those looking to build a successful team and ensure continuous growth.\n\nWhat will you gain after completing the course?\n• A clear, step-by-step system for your business.\n• The ability to attract customers and build an effective team.\n• Strong follow-up, persuasion and credibility skills.\n• A clear strategy for profit and growing your project.",
                'description_ar' => "كورس أساسيات التسويق وبناء فريق عمل ناجح\n\nلو هدفك تبني شغل حقيقي في مجال التسويق وتعرف تبدأ صح، الكورس ده هيكون خطوتك الأولى لبناء أساس قوي، فهم استراتيجيات العمل، وطريقة تكوين فريق قادر على تحقيق نتائج ونمو مستمر.\nهنركز على تكوين عقلية تسويقية قوية، أدوات المتابعة، والتأثير الحقيقي على العملاء، بالإضافة لفنيات بناء علاقات، وإدارة الدعوات والمتابعة بشكل احترافي.\n\nماذا ستتعلم في الكورس؟\n• توجيه صحيح وخطوات البداية العملية (Orientation).\n• كيفية تحديد أهدافك ووضع قائمة الأحلام مع الالتزام الحقيقي (Dream List & Commitment).\n• بناء قاعدة بيانات قوية وتنظيم العملاء المحتملين (Database).\n• مهارات تقييم الفرص والموافقة الإيجابية (+A).\n• فهم خطة العمولات وكيف تربح من التسويق (Commission Plan).\n• طرق البحث عن العملاء وإرسال الدعوات بشكل مؤثر (Prospecting & Invitation).\n• فن المتابعة الصحيحة حتى إغلاق الفرص (Follow Up).\n• تطبيق عملي لاستراتيجية SMP لتحويل المعرفة لنتائج (Apply & SMP).\n• أهمية الانطباع الأول وكيف يراك الآخرون (First Impressions).\n• استخدام وسائل التواصل الاجتماعي لنمو الأعمال بطريقة احترافية.\n• اكتساب خبرة عملية في عالم التسويق، وليست مجرد معلومات.\n• أساسيات بناء التسويق الصحيح (Fundamentals).\n• كيف يؤثر موقفك وسلوكك في تحقيق نتائج (Your Attitude).\n• قوة التواصل وبناء علاقات فعّالة.\n• متابعة العملاء باستخدام جداول منظمة واستراتيجيات احترافية.\n• بناء المصداقية والتأثير التسويقي (Advocation).\n• فهم خطة العمل المباشر وغير المباشر (Plan 2 / Plan 3) ودورها في بناء فريق قوي.\n\nلمن يناسب هذا الكورس؟\n• أي شخص يريد دخول مجال التسويق وبناء عمل حقيقي.\n• من يعمل في التسويق لكن يفتقر للنظام والاستراتيجية.\n• الباحثين عن بناء فريق عمل ناجح وضمان نمو مستمر.\n\nماذا ستحصل بعد الكورس؟\n• نظام واضح للعمل خطوة بخطوة.\n• قدرة على جذب العملاء وبناء فريق فعال.\n• مهارة المتابعة والإقناع وبناء المصداقية.\n• استراتيجية واضحة للربح وتطوير مشروعك.",
                'highlights' => [
                    'Marketing foundation: proper orientation, goal setting, and building a database.',
                    'Work skills: prospecting, invitations, and making a strong first impression.',
                    'Growth strategies: effective follow-up, using social media, and building credibility.',
                    'Success plans: understanding commission structures, direct & indirect plans, and practical SMP application.',
                ],
                'highlights_ar' => [
                    'بناء أساس التسويق: التوجيه الصحيح، تحديد الأهداف، وتكوين قاعدة بيانات.',
                    'مهارات العمل: البحث عن العملاء، الدعوات، والانطباع الأول.',
                    'استراتيجيات النمو: المتابعة الاحترافية، استخدام السوشيال ميديا، وبناء المصداقية.',
                    'خطط النجاح: فهم نظام العمولة، العمل المباشر وغير المباشر، وتطبيق SMP عمليًا.',
                ],
            ],
            ['track' => 'marketing', 'level' => 2, 'difficulty' => 'intermediate', 'title' => 'Level 2', 'title_ar' => 'المستوى ٢'],
            ['track' => 'marketing', 'level' => 3, 'difficulty' => 'advanced', 'title' => 'Level 3', 'title_ar' => 'المستوى ٣'],
            ['track' => 'marketing', 'level' => 4, 'difficulty' => 'professional', 'title' => 'Level 4', 'title_ar' => 'المستوى ٤'],
        ];

        foreach ($courses as $c) {
            Course::firstOrCreate(['track' => $c['track'], 'level' => $c['level']], $c);
        }
    }

    private function gallery(): void
    {
        if (GalleryImage::exists()) {
            return;
        }
        $manifest = json_decode((string) @file_get_contents(public_path('media/manifest.json')), true) ?: [];
        foreach (range(1, 23) as $i) {
            [$w, $h] = $manifest["gallery/{$i}.webp"] ?? (@getimagesize(public_path("media/gallery/{$i}.webp")) ?: [1600, 950]);
            GalleryImage::create(['collection' => 'academy', 'path' => "media/gallery/{$i}.webp", 'thumb_path' => "media/gallery/{$i}-thumb.webp", 'width' => $w, 'height' => $h, 'sort_order' => $i]);
        }
    }

    private function freeVideos(): void
    {
        if (FreeVideo::exists()) {
            return;
        }
        $videos = [
            ['Getting Started in Trading - Must Watch', 'داخل مجال التداول؟ لازم تشوف الفيديو ده', 'Comprehensive introduction to trading and how to start your investment journey', 'فيديو تعريفي شامل عن مجال التداول وكيفية البدء في رحلتك الاستثمارية', '15:30', 'beginner'],
            ['Start Right - Trading Concepts from Scratch', 'ابدأ صح ومفهوم التداول من الصفر', 'Learn fundamental trading concepts and how to start the right way from scratch', 'تعلم المفاهيم الأساسية للتداول وكيفية البدء بالطريقة الصحيحة من الصفر', '20:45', 'beginner'],
            ['TradingView Basics & Adding Currency Pairs', 'أساسيات TradingView وكيفية إضافة أزواج العملات', 'Learn how to use the TradingView platform and add currency pairs for analysis', 'تعلم كيفية استخدام منصة TradingView وإضافة أزواج العملات للتحليل', '18:20', 'beginner'],
            ['Understanding Inverse Correlation Between Pairs', 'مفهوم العلاقة العكسية بين الأزواج', 'Learn how to understand inverse correlation between currency pairs and use it in trading', 'تعلم كيفية فهم العلاقة العكسية بين أزواج العملات واستخدامها في التداول', '22:15', 'intermediate'],
            ['Japanese Candlesticks & Timeframes', 'اتعرف أكثر على الشموع اليابانية والفريمات', 'Deep understanding of Japanese candlesticks and how to use different timeframes in analysis', 'فهم عميق للشموع اليابانية وكيفية استخدام الفريمات الزمنية المختلفة في التحليل', '25:00', 'intermediate'],
            ['What is a PIP & How to Calculate Your Profit', 'اعرف إيه هي نقطة الـ PIP وإزاي تقدر تحسب مكسبك صح', 'Understanding the PIP concept and how to accurately calculate profits and losses', 'فهم مفهوم نقطة البيب وكيفية حساب الأرباح والخسائر بدقة في التداول', '30:10', 'intermediate'],
            ['Market Movement & Support/Resistance', 'اتعرف على حركة السوق ومفهوم الدعم والمقاومة', 'Understanding market movement and how to identify support and resistance levels', 'فهم حركة السوق وكيفية تحديد مستويات الدعم والمقاومة في التحليل الفني', '19:30', 'intermediate'],
            ['Everything You Need to Open a Trade', 'كل اللي محتاجه عشان تقدر تفتح صفقة', 'Complete guide to everything you need to open a successful trade with confidence', 'دليل شامل لكل ما تحتاجه لفتح صفقة تداول ناجحة بثقة', '22:15', 'intermediate'],
            ['Inverse Correlation in Trading Strategies', 'مفهوم العلاقة العكسية بين الأزواج', 'Deep understanding of inverse correlation between currency pairs and how to use it in trading strategies', 'فهم عميق للعلاقة العكسية بين أزواج العملات وكيفية استخدامها في استراتيجيات التداول', '24:30', 'intermediate'],
            ['Setting Targets & Closing Losses — Take Profit • Stop Loss', 'إزاي تحدد هدفك وامتى تقفل خسارتك ومفهوم TAKE PROFIT • STOP LOSS', 'Learn how to properly set profit targets and stop losses for effective risk management', 'تعلم كيفية تحديد أهداف الربح ووقف الخسارة بشكل صحيح لإدارة المخاطر بفعالية', '26:45', 'intermediate'],
            ['MT5 Application Explained', 'شرح أبليكيشن MT5', 'Complete guide to using the MetaTrader 5 platform with all its tools and features', 'دليل شامل لاستخدام منصة MetaTrader 5 وجميع أدواتها ومميزاتها', '28:20', 'beginner'],
            ['Practical Application on MT5', 'تطبيق عملي على MT5', 'Step-by-step practical application on the MT5 platform for opening and managing trades', 'تطبيق عملي خطوة بخطوة على منصة MT5 لفتح وإدارة الصفقات', '30:15', 'intermediate'],
            ['How to Modify and Close Trades in MT5', 'طريقة التعديل وإغلاق الصفقات داخل MT5', 'Learn how to professionally modify and close trades within the MT5 platform', 'تعلم كيفية تعديل وإغلاق الصفقات بشكل احترافي داخل منصة MT5', '25:40', 'intermediate'],
        ];
        foreach ($videos as $i => [$t, $ta, $d, $da, $dur, $diff]) {
            FreeVideo::create([
                'title' => $t, 'title_ar' => $ta, 'description' => $d, 'description_ar' => $da,
                'duration' => $dur, 'difficulty' => $diff, 'video_path' => 'free-courses/video-'.($i + 1).'.mp4', 'sort_order' => $i + 1,
            ]);
        }
    }
}
