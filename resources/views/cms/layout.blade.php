<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>@yield('seo_title', config('app.name', 'NoovaPOS'))</title>
    <meta name="description" content="@yield('seo_description', '')"/>
    <meta name="keywords" content="@yield('seo_keywords', '')"/>
    <meta property="og:title" content="@yield('seo_title', config('app.name', 'NoovaPOS'))"/>
    <meta property="og:description" content="@yield('seo_description', '')"/>
    <meta property="og:type" content="website"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <style>body{font-family:'Inter',sans-serif}</style>
</head>
<body class="bg-slate-50 text-slate-800">
    <header class="bg-white border-b sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
            <a href="/" class="text-xl font-bold text-indigo-600">NoovaPOS</a>
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
                @if(count($menu ?? []))
                    <div class="relative group">
                        <button type="button" class="flex items-center gap-1 hover:text-indigo-600 focus:outline-none">
                            Solutions
                            <svg class="w-4 h-4 transition-transform duration-200 group-hover:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        {{-- pt-3 is an invisible hover bridge so the panel stays open
                             while the cursor moves from the button to the menu --}}
                        <div class="absolute left-1/2 -translate-x-1/2 top-full pt-3 w-60 z-50 opacity-0 invisible translate-y-1 group-hover:opacity-100 group-hover:visible group-hover:translate-y-0 transition-all duration-200">
                            <div class="bg-white border border-slate-200 rounded-xl shadow-xl p-2 max-h-[75vh] overflow-y-auto">
                                @foreach(($menu ?? []) as $m)
                                    <a href="/p/{{ $m->slug }}" class="block px-3 py-2 rounded-lg text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">{{ $m->title }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
                <a href="/blog" class="hover:text-indigo-600">Blog</a>
                <a href="/login" class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Login</a>
            </nav>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-8">
        @yield('content')
    </main>

    <footer class="border-t mt-12 py-8 text-center text-sm text-slate-500">
        &copy; {{ date('Y') }} NoovaPOS. All rights reserved.
    </footer>
</body>
</html>
