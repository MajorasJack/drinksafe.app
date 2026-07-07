{{--
    Server-rendered SEO metadata.

    Reads the `seo` Inertia prop (shared sitewide default, overridable per controller)
    from $page and renders title, description, canonical, robots, Open Graph and
    Twitter tags plus JSON-LD structured data.

    SECURITY: venue names/addresses are user-submitted, so every value is escaped.
    Attribute content uses Blade {{ }}; JSON-LD is encoded with JSON_HEX_* flags so
    it cannot break out of the <script> tag.
--}}
@php
    $seo = $page['props']['seo'] ?? [];
    $seoTitle = $seo['title'] ?? config('app.name');
    $seoDescription = $seo['description'] ?? '';
    $seoCanonical = $seo['canonical'] ?? '';
    $seoRobots = $seo['robots'] ?? 'index,follow';
    $seoImage = $seo['image'] ?? '';
    $seoType = $seo['type'] ?? 'website';
    $seoJsonLd = $seo['jsonLd'] ?? [];
@endphp
<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDescription }}">
<link rel="canonical" href="{{ $seoCanonical }}">
<meta name="robots" content="{{ $seoRobots }}">

<meta property="og:type" content="{{ $seoType }}">
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $seoCanonical }}">
<meta property="og:image" content="{{ $seoImage }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $seoImage }}">

@foreach ($seoJsonLd as $jsonLdBlock)
    <script type="application/ld+json">{!! json_encode($jsonLdBlock, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endforeach
