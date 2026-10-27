<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $meta['title'] ?? setting('seo.default_title', 'Tajira') }}</title>
    <meta name="description" content="{{ $meta['description'] ?? '' }}">
    <link rel="canonical" href="{{ $meta['canonical'] ?? url()->current() }}">
    <meta name="robots" content="{{ $meta['robots'] ?? 'index,follow' }}">
    @if(!empty($meta['gsc_verification']))
        <meta name="google-site-verification" content="{{ $meta['gsc_verification'] }}">
    @endif

    <meta property="og:site_name" content="{{ setting('business.name', 'Tajira') }}">
    <meta property="og:type" content="{{ $meta['type'] ?? 'website' }}">
    <meta property="og:title" content="{{ $meta['title'] ?? '' }}">
    <meta property="og:description" content="{{ $meta['description'] ?? '' }}">
    <meta property="og:url" content="{{ $meta['canonical'] ?? url()->current() }}">
    <meta property="og:image" content="{{ $meta['image'] ?? asset('images/og-default.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $meta['title'] ?? '' }}">
    <meta name="twitter:description" content="{{ $meta['description'] ?? '' }}">
    <meta name="twitter:image" content="{{ $meta['image'] ?? asset('images/og-default.png') }}">
    @if(!empty($meta['previous']))
        <link rel="prev" href="{{ $meta['previous'] }}">
    @endif
    @if(!empty($meta['next']))
        <link rel="next" href="{{ $meta['next'] }}">
    @endif

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-mark.svg') }}">
    <meta name="theme-color" content="#0b1f3a">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @isset($jsonLd)
        @foreach($jsonLd as $schema)
            <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
        @endforeach
    @endisset
</head>
<body class="flex min-h-screen flex-col bg-slate-50 text-ink-900 antialiased">
    <a href="#main-content" class="skip-link">Skip to content</a>

    @include('storefront.partials.header')

    @if(session('success') || session('error') || session('info') || session('warning') || $errors->any())
        <div class="container-page mt-4">
            @include('storefront.partials.flash')
        </div>
    @endif

    <main id="main-content" class="flex-1">
        @yield('content')
    </main>

    @include('storefront.partials.footer')
    @include('storefront.partials.consent')
    @include('storefront.partials.analytics')
    @stack('scripts')
</body>
</html>
