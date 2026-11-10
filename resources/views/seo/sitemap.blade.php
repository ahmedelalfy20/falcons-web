{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($pages as $page)
@foreach (['ar', 'en'] as $lang)
    <url>
        <loc>{{ $page['url'] }}?lang={{ $lang }}</loc>
@if ($page['lastmod'])
        <lastmod>{{ $page['lastmod']->toAtomString() }}</lastmod>
@endif
        <changefreq>{{ $page['freq'] }}</changefreq>
        <priority>{{ $page['priority'] }}</priority>
        <xhtml:link rel="alternate" hreflang="ar" href="{{ $page['url'] }}?lang=ar"/>
        <xhtml:link rel="alternate" hreflang="en" href="{{ $page['url'] }}?lang=en"/>
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ $page['url'] }}"/>
    </url>
@endforeach
@endforeach
</urlset>
