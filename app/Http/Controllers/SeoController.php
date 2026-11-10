<?php

namespace App\Http\Controllers;

use App\Models\Scanner;
use App\Models\TeamMember;
use Illuminate\Http\Response;

/** robots.txt and sitemap.xml, generated so they always use the live domain. */
class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            // Private or per-visitor pages: accounts, forms, live JSON, language switch.
            'Disallow: /admin',
            // The leader dashboard only — public profiles live under /leaders/.
            'Disallow: /leader$',
            'Disallow: /leader/',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /registration/',
            'Disallow: /live/',
            'Disallow: /locale/',
            'Disallow: /free-courses/video/',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600']);
    }

    public function sitemap(): Response
    {
        $pages = collect([
            ['url' => route('home'), 'priority' => '1.0', 'freq' => 'weekly', 'lastmod' => null],
            ['url' => route('competition'), 'priority' => '0.9', 'freq' => 'daily', 'lastmod' => null],
            ['url' => route('free-courses'), 'priority' => '0.8', 'freq' => 'weekly', 'lastmod' => null],
            ['url' => route('leader.signup'), 'priority' => '0.7', 'freq' => 'monthly', 'lastmod' => null],
            ['url' => route('app.download'), 'priority' => '0.6', 'freq' => 'monthly', 'lastmod' => null],
        ]);
        foreach (Scanner::visible()->get() as $s) {
            $pages->push(['url' => route('scanners.show', $s), 'priority' => '0.8', 'freq' => 'monthly', 'lastmod' => $s->updated_at]);
        }
        foreach (TeamMember::visible()->get() as $m) {
            $pages->push(['url' => route('team.show', $m), 'priority' => '0.7', 'freq' => 'monthly', 'lastmod' => $m->updated_at]);
        }

        $pages = $pages->map(fn ($p) => ['url' => \App\Support\Seo::canonicalBase($p['url'])] + $p);
        $xml = view('seo.sitemap', ['pages' => $pages])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600']);
    }
}
