<?php

namespace Tests\Feature;

use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_lang_parameter_selects_the_language_and_is_remembered(): void
    {
        $this->seed(ContentSeeder::class);

        $this->get('/?lang=ar')->assertOk()->assertSee('<html lang="ar" dir="rtl">', false);
        // Remembered for the next page without the parameter.
        $this->get('/competition')->assertSee('<html lang="ar" dir="rtl">', false);
        $this->get('/?lang=en')->assertSee('<html lang="en" dir="ltr">', false);
    }

    public function test_public_pages_have_canonical_hreflang_social_tags_and_schema(): void
    {
        $this->seed(ContentSeeder::class);

        $html = $this->get('/competition?lang=ar&utm_source=x')->assertOk()->getContent();
        $this->assertStringContainsString('<link rel="canonical" href="'.url('/competition').'?lang=ar">', $html);
        $this->assertStringContainsString('hreflang="en" href="'.url('/competition').'?lang=en"', $html);
        $this->assertStringContainsString('hreflang="x-default" href="'.url('/competition').'"', $html);
        $this->assertStringContainsString('property="og:locale" content="ar_EG"', $html);
        $this->assertStringContainsString('name="twitter:card" content="summary_large_image"', $html);
        $this->assertStringContainsString('"@type":"EducationalOrganization"', $html);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $html);
        $this->assertStringContainsString('content="index, follow', $html);

        // Home canonical keeps the root slash.
        $this->get('/?lang=en')->assertSee('<link rel="canonical" href="'.url('/').'/?lang=en">', false);
    }

    public function test_private_pages_are_noindex_without_canonical(): void
    {
        foreach (['/login', '/register'] as $url) {
            $html = $this->get($url)->getContent();
            $this->assertStringContainsString('<meta name="robots" content="noindex, follow">', $html, $url);
            $this->assertStringNotContainsString('rel="canonical"', $html, $url);
        }
    }

    public function test_robots_and_sitemap(): void
    {
        $this->seed(ContentSeeder::class);

        $robots = $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')->getContent();
        $this->assertStringContainsString('Sitemap: '.url('/sitemap.xml'), $robots);
        $this->assertStringContainsString('Disallow: /admin', $robots);
        // Public profiles under /leaders/ must stay crawlable.
        $this->assertStringNotContainsString("Disallow: /leader\n", $robots);
        $this->assertStringContainsString('Disallow: /leader/', $robots);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $doc = simplexml_load_string($xml);
        $this->assertNotFalse($doc);
        $this->assertStringContainsString('<loc>'.url('/').'/?lang=ar</loc>', $xml);
        $this->assertStringContainsString(url('/competition').'?lang=en', $xml);
        $this->assertStringContainsString('hreflang="x-default"', $xml);
        $this->assertStringContainsString('/scanners/', $xml);
        $this->assertStringContainsString('/leaders/', $xml);
        $this->assertStringNotContainsString('/admin', $xml);
    }

    public function test_language_switch_drops_a_stale_lang_parameter(): void
    {
        $this->get('/competition?lang=en');
        $this->from(url('/competition?lang=en'))->get('/locale/ar')->assertRedirect(url('/competition'));
        $this->get('/competition')->assertSee('<html lang="ar" dir="rtl">', false);
    }
}
