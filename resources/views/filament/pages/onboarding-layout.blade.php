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

    <style>
        /* Destaque da seção Dia Marcado */
        .wedding-date-section {
            background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%) !important;
            border: 2px solid #f9a8d4 !important;
            border-radius: 1rem !important;
        }
        
        .dark .wedding-date-section {
            background: linear-gradient(135deg, #831843 0%, #9d174d 100%) !important;
            border: 2px solid #ec4899 !important;
        }
        
        /* Melhorias no DatePicker */
        .fi-fo-date-time-picker .fi-fo-date-time-picker-calendar {
            min-width: 320px;
        }
        
        .fi-fo-date-time-picker button[data-selected="true"] {
            background-color: #ec4899 !important;
            color: white !important;
        }
        
        .fi-fo-date-time-picker button:hover:not([disabled]) {
            background-color: #fce7f3 !important;
        }
        
        .dark .fi-fo-date-time-picker button:hover:not([disabled]) {
            background-color: #9d174d !important;
        }
    </style>

    @livewireStyles
</head>

<body class="fi-body min-h-screen bg-gray-50 font-normal text-gray-950 antialiased dark:bg-gray-950 dark:text-white">
    {{ $slot }}

    @livewire(Filament\Livewire\Notifications::class)

    @filamentScripts
    @livewireScripts
</body>
</html>
