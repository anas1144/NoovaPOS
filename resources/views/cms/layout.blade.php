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
                @foreach(($menu ?? []) as $m)
                    <a href="/p/{{ $m->slug }}" class="hover:text-indigo-600">{{ $m->title }}</a>
                @endforeach
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
