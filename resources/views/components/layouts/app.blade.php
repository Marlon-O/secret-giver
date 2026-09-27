@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#15382e">
    <title>{{ isset($title) ? "$title · Secret Giver" : 'Secret Giver' }}</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎁</text></svg>">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh font-sans text-paper antialiased">
    <div class="mx-auto flex min-h-dvh w-full max-w-xl flex-col px-4 pt-6 pb-12 sm:pt-10">
        <header class="mb-8 flex items-center justify-between">
            <a href="{{ route('home') }}" class="font-display text-lg text-paper/90 hover:text-paper">Secret Giver</a>
            {{ $headerAction ?? '' }}
        </header>

        @if (session('status'))
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show" x-transition.opacity
                 role="status"
                 class="fixed inset-x-4 bottom-5 z-50 mx-auto max-w-md rounded-full bg-ink px-5 py-3 text-center text-sm font-medium text-paper shadow-lg">
                {{ session('status') }}
            </div>
        @endif

        <main class="flex-1">
            {{ $slot }}
        </main>
    </div>
</body>
</html>
