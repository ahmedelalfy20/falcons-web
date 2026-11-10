<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * URLs and structured data shared by every public page.
 * Each page exists once per language as ?lang=en / ?lang=ar; the bare URL is
 * the x-default (language picked from the visitor's preference).
 */
class Seo
{
    public const LOCALES = ['ar' => 'ar_EG', 'en' => 'en_US'];

    /** The current page's URL in a language (other query parameters dropped). */
    public static function url(?string $locale = null, ?Request $request = null): string
    {
        $request ??= request();
        $base = self::canonicalBase($request->url());

        return $locale ? $base.'?lang='.$locale : $base;
    }

    /** The site root is written with its trailing slash (https://domain/). */
    public static function canonicalBase(string $url): string
    {
        return parse_url($url, PHP_URL_PATH) ? $url : rtrim($url, '/').'/';
    }

    public static function brand(): string
    {
        return __('Falcons Academy');
    }

    /** Absolute URL for an image path or URL. */
    public static function image(?string $path): string
    {
        return media($path ?: 'media/founder-original.webp');
    }

    /** sameAs links from the social settings (Telegram, Facebook, Instagram, ...). */
    public static function socialProfiles(): array
    {
        return array_values(array_filter((array) site('social', [], false), fn ($v) => is_string($v) && str_starts_with($v, 'http')));
    }

    /** Site-wide JSON-LD: the academy and the website. */
    public static function baseGraph(): array
    {
        $home = route('home');

        return [
            [
                '@type' => 'EducationalOrganization',
                '@id' => $home.'#organization',
                'name' => 'Falcons Academy',
                'alternateName' => ['Falcons Organization', 'أكاديمية فالكونز', 'منظمة فالكونز'],
                'url' => $home,
                'logo' => ['@type' => 'ImageObject', 'url' => asset('media/brand/logo.webp')],
                'image' => self::image(null),
                'description' => __('Falcons Academy — elite trading education, mentorship and a community of professional traders across the MENA region.'),
                'founder' => ['@type' => 'Person', 'name' => 'Mahmoud Magdy'],
                'areaServed' => ['@type' => 'Place', 'name' => 'Middle East and North Africa'],
                'knowsLanguage' => ['ar', 'en'],
                'sameAs' => self::socialProfiles(),
            ],
            [
                '@type' => 'WebSite',
                '@id' => $home.'#website',
                'url' => $home,
                'name' => 'Falcons Academy',
                'inLanguage' => ['ar', 'en'],
                'publisher' => ['@id' => $home.'#organization'],
            ],
        ];
    }

    /** BreadcrumbList from [label => url] pairs (Home is added first). */
    public static function breadcrumbs(array $trail): array
    {
        $items = [['name' => __('Home'), 'url' => route('home')]];
        foreach ($trail as $name => $url) {
            $items[] = ['name' => $name, 'url' => $url];
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn ($i, $n) => [
                '@type' => 'ListItem', 'position' => $n + 1, 'name' => $i['name'], 'item' => $i['url'],
            ], $items, array_keys($items)),
        ];
    }

    /**
     * The page itself, with its main image spelled out so search engines use
     * it as the result thumbnail instead of guessing from images on the page.
     */
    public static function webPage(string $title, string $description, string $image): array
    {
        $home = route('home');

        return [
            '@type' => 'WebPage',
            '@id' => self::url(app()->getLocale()).'#webpage',
            'url' => self::url(app()->getLocale()),
            'name' => $title,
            'description' => $description,
            'inLanguage' => app()->getLocale(),
            'isPartOf' => ['@id' => $home.'#website'],
            'about' => ['@id' => $home.'#organization'],
            'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => $image],
            'image' => $image,
        ];
    }

    public static function jsonLd(array $extra = []): string
    {
        $graph = ['@context' => 'https://schema.org', '@graph' => array_values(array_merge(self::baseGraph(), $extra))];

        return json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    }
}
