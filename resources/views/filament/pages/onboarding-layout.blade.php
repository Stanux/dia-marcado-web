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
        /* Destaque apenas no corpo do step Dia Marcado */
        .fi-fo-wizard-step#dia-marcado.fi-active {
            /* background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%) !important; */
            /* border-radius: 0.75rem; */
            padding: 1rem !important;
        }

        .dark .fi-fo-wizard-step#dia-marcado.fi-active {
            /* background: linear-gradient(135deg, #831843 0%, #9d174d 100%) !important; */
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

        /* Melhor navegação de steps no mobile/tablet */
        @media (max-width: 1023px) {
            .fi-simple-layout .fi-fo-wizard-header {
                display: flex !important;
                grid-template-columns: none !important;
                overflow-x: auto !important;
                overflow-y: hidden !important;
                -webkit-overflow-scrolling: touch;
                scroll-snap-type: x proximity;
                scrollbar-width: none;
            }

            .fi-simple-layout .fi-fo-wizard-header::-webkit-scrollbar {
                display: none;
            }

            .fi-simple-layout .fi-fo-wizard-header-step {
                flex: 0 0 auto;
                min-width: 3rem;
                scroll-snap-align: start;
            }

            .fi-simple-layout .fi-fo-wizard-header-step.fi-active {
                min-width: 9rem;
            }

            .fi-simple-layout .fi-fo-wizard-header-step-button {
                padding: 0.5rem 0.5rem !important;
                gap: 0.375rem !important;
            }

            .fi-simple-layout .fi-fo-wizard-header-step-icon-ctn {
                width: 1.85rem !important;
                height: 1.85rem !important;
            }

            .fi-simple-layout .fi-fo-wizard-header-step .grid {
                max-width: 6.5rem !important;
            }

            .fi-simple-layout .fi-fo-wizard-header-step-label {
                display: block;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                font-size: 0.8125rem !important;
            }

            .fi-simple-layout .fi-fo-wizard-header-step:not(.fi-active) .fi-fo-wizard-header-step-label {
                display: none !important;
            }

            .fi-simple-layout .fi-fo-wizard-header-step-description,
            .fi-simple-layout .fi-fo-wizard-header-step-separator {
                display: none !important;
            }

            .fi-simple-layout .fi-fo-wizard-step.fi-active {
                padding: 0.5rem !important;
            }

            .fi-simple-layout .fi-fo-wizard-step#dia-marcado.fi-active {
                padding: 0.5rem !important;
            }
        }

        /* Melhor aproveitamento em telas de notebook */
        @media (min-width: 1024px) and (max-width: 1600px) {
            .fi-simple-layout .fi-fo-wizard-header-step-button {
                padding: 0.625rem 0.875rem !important;
                gap: 0.625rem !important;
            }

            .fi-simple-layout .fi-fo-wizard-header-step-icon-ctn {
                width: 2.25rem !important;
                height: 2.25rem !important;
            }

            .fi-simple-layout .fi-fo-wizard-header-step-description {
                display: none !important;
            }
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
