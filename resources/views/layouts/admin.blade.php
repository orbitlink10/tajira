<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title', 'Administration') | {{ setting('business.name', 'Tajira') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-mark.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-100 text-ink-900 antialiased">
    <div x-data="{ sidebarOpen: false }">
        <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 bg-ink-950/60 lg:hidden" @click="sidebarOpen = false"></div>

        <aside class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-ink-950 text-white transition-transform lg:translate-x-0" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="flex h-16 items-center justify-between border-b border-white/10 px-4">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                    <img src="{{ asset('images/logo-mark.svg') }}" alt="" width="34" height="34" class="h-8 w-8">
                    <span class="text-sm font-bold uppercase tracking-[0.2em]">Tajira</span>
                </a>
                <button type="button" class="rounded p-1 text-white/70 hover:text-white lg:hidden" @click="sidebarOpen = false" aria-label="Close menu">✕</button>
            </div>

            <div class="flex-1 overflow-y-auto px-3 py-4">
                @include('admin.partials.nav')
            </div>

            <div class="border-t border-white/10 p-4 text-xs text-ink-300">
                <p class="font-semibold text-white">{{ auth()->user()->name }}</p>
                <p>{{ auth()->user()->roleLabel() }}</p>
                <a href="{{ route('home') }}" target="_blank" rel="noopener" class="mt-2 inline-block text-brand-300 hover:text-white">View storefront ↗</a>
            </div>
        </aside>

        <div class="lg:pl-64">
            <header class="sticky top-0 z-30 flex h-16 items-center justify-between gap-3 border-b border-ink-200 bg-white px-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <button type="button" class="rounded-lg border border-ink-300 p-2 text-ink-700 lg:hidden" @click="sidebarOpen = true" aria-label="Open menu">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/></svg>
                    </button>
                    <div>
                        <h1 class="text-base font-bold text-ink-900">@yield('heading', 'Administration')</h1>
                        @hasSection('subheading')<p class="text-xs text-ink-500">@yield('subheading')</p>@endif
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @if(auth()->user()->mfa_enabled)
                        <span class="hidden rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800 sm:inline">MFA on</span>
                    @else
                        <a href="{{ route('admin.mfa.setup') }}" class="hidden rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-900 hover:bg-amber-100 sm:inline">Set up MFA</a>
                    @endif
                    <a href="{{ route('admin.mfa.setup') }}" class="hidden text-sm font-medium text-ink-600 hover:text-brand-700 sm:inline">Security</a>
                    <form method="post" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary px-3 py-1.5 text-xs">Sign out</button>
                    </form>
                </div>
            </header>

            <main class="p-4 sm:p-6">
                @if(session('success') || session('error') || session('info') || session('warning'))
                    <div class="mb-4 space-y-2">
                        @foreach([
                            'success' => 'rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900',
                            'error' => 'rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900',
                            'info' => 'rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900',
                            'warning' => 'rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900',
                        ] as $key => $classes)
                            @if(session($key))
                                <div class="{{ $classes }}" role="status">{{ session($key) }}</div>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
                        <p class="font-semibold">Please correct the following:</p>
                        <ul class="mt-1 list-disc pl-5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
