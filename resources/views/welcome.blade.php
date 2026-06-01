<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>NoovaPOS</title>
        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700"/>
        @viteReactRefresh
        {{-- Vite: entry point is the React app (index.jsx) --}}
        @vite('resources/pos/src/index.jsx')
    </head>
    <body class="antialiased">
        <div id="root"></div>
    </body>
</html>
