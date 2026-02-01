@php
    $color = filament()->getDefaultThemeMode()->value === 'dark' ? 'dark' : 'light';
@endphp
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="ltr"
    class="fi min-h-screen antialiased"
>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    @if ($favicon = filament()->getFavicon())
        <link rel="icon" href="{{ $favicon }}" />
    @endif

    <title>Configuração Inicial - {{ filament()->getBrandName() }}</title>

    @filamentStyles
    {{ filament()->getTheme()->getHtml() }}
    {{ filament()->getFontHtml() }}

    @livewireStyles
</head>

<body class="fi-body min-h-screen bg-gray-50 font-normal text-gray-950 antialiased dark:bg-gray-950 dark:text-white">
    <div class="fi-simple-layout flex min-h-screen flex-col items-center justify-center p-4">
        <div class="w-full max-w-4xl">
            {{-- Welcome Header --}}
            <div class="text-center mb-8">
                <div class="text-6xl mb-4">💍</div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    Bem-vindo ao Dia Marcado!
                </h1>
                <p class="text-lg text-gray-600 dark:text-gray-400">
                    Vamos configurar seu casamento em apenas 3 passos simples.
                </p>
            </div>

            {{-- Wizard Form --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl p-6 sm:p-8">
                <form wire:submit="complete">
                    {{ $this->form }}
                </form>
            </div>

            {{-- Footer --}}
            <div class="text-center mt-6 text-sm text-gray-500 dark:text-gray-400">
                <p>Precisa de ajuda? Entre em contato conosco.</p>
            </div>
        </div>
    </div>

    @livewire(Filament\Livewire\Notifications::class)

    @filamentScripts
    @livewireScripts
</body>
</html>
