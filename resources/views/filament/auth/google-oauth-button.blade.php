@php
    $googleEnabled = (bool) config('services.google.enabled', false)
        && filled(config('services.google.client_id'))
        && filled(config('services.google.client_secret'))
        && filled(config('services.google.redirect'));
@endphp

@if ($googleEnabled)
    <div class="mt-4">
        <div class="relative mb-3">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-gray-200 dark:border-gray-700"></div>
            </div>
            <div class="relative flex justify-center">
                <span class="bg-white px-2 text-xs text-gray-500 dark:bg-gray-900 dark:text-gray-400">ou</span>
            </div>
        </div>

        <x-filament::button
            color="gray"
            outlined
            tag="a"
            :href="route('auth.google.redirect')"
            class="w-full"
        >
            <span class="inline-flex items-center gap-2">
                <svg class="h-4 w-4" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#EA4335" d="M12 10.2v3.9h5.4c-.2 1.3-1.6 3.9-5.4 3.9-3.2 0-5.8-2.7-5.8-6s2.6-6 5.8-6c1.8 0 3 .8 3.7 1.4l2.5-2.4C16.6 3.4 14.5 2.6 12 2.6 6.8 2.6 2.6 6.8 2.6 12s4.2 9.4 9.4 9.4c5.4 0 9-3.8 9-9.2 0-.6-.1-1.1-.2-1.6H12z"/>
                    <path fill="#34A853" d="M2.6 7.9l3.2 2.4c.8-2.4 3-4.2 6.2-4.2 1.8 0 3 .8 3.7 1.4l2.5-2.4C16.6 3.4 14.5 2.6 12 2.6c-3.7 0-6.9 2.1-8.4 5.3z"/>
                    <path fill="#FBBC05" d="M12 21.4c2.4 0 4.5-.8 6-2.1l-2.8-2.3c-.8.6-1.9 1-3.2 1-3.7 0-5.1-2.6-5.4-3.9l-3.2 2.5c1.5 3.2 4.7 5.4 8.6 5.4z"/>
                    <path fill="#4285F4" d="M21 12.2c0-.6-.1-1.1-.2-1.6H12v3.9h5.4c-.3 1.3-1.1 2.3-2.2 3l2.8 2.3c1.6-1.5 3-3.8 3-7.6z"/>
                </svg>
                <span>Continuar com Google</span>
            </span>
        </x-filament::button>
    </div>
@endif
