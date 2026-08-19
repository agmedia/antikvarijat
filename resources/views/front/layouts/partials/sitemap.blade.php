<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
    @foreach ($items as $item)
        <url>
            <loc>{{ $item['url'] }}</loc>
            <lastmod>{{ $item['lastmod'] }}</lastmod>
            @foreach (($item['alternates'] ?? []) as $locale => $alternateUrl)
                <xhtml:link rel="alternate" hreflang="{{ config('localization.locales.' . $locale . '.hreflang', $locale) }}" href="{{ $alternateUrl }}" />
            @endforeach
            @if (! empty($item['alternates']))
                <xhtml:link rel="alternate" hreflang="x-default" href="{{ $item['alternates']['hr'] ?? collect($item['alternates'])->first() }}" />
            @endif
{{--            <changefreq>{{ isset($change) ? $change : 'montly' }}</changefreq>--}}
{{--            <priority>{{ $priority }}</priority>--}}
        </url>
    @endforeach
</urlset>
