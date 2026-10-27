<!DOCTYPE html>
<html lang="en" class="h-full bg-ink-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title', 'Administration') | {{ setting('business.name', 'Tajira') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-mark.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full items-center justify-center bg-ink-950 p-4 text-ink-900 antialiased">
    <div class="w-full max-w-md">
        <div class="mb-6 flex items-center gap-3 text-white">
            <img src="{{ asset('images/logo-mark.svg') }}" alt="" width="42" height="42" class="h-10 w-10">
            <div>
                <p class="text-lg font-bold tracking-wide">{{ setting('business.name', 'Tajira') }}</p>
                <p class="text-xs text-ink-300">Administration</p>
            </div>
        </div>

        @if(session('success') || session('error') || session('warning') || session('info'))
            <div class="mb-3 space-y-2">
                @foreach([
                    'success' => 'rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900',
                    'error' => 'rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900',
                    'warning' => 'rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900',
                    'info' => 'rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900',
                ] as $key => $classes)
                    @if(session($key))
                        <div class="{{ $classes }}">{{ session($key) }}</div>
                    @endif
                @endforeach
            </div>
        @endif

        @if($errors->any())
            <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        @yield('content')

        <p class="mt-6 text-center text-xs text-ink-400">
            <a href="{{ route('home') }}" class="hover:text-white">← Back to storefront</a>
        </p>
    </div>
</body>
</html>
