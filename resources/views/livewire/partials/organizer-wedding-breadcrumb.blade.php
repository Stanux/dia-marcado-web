@if ($showOrganizerWeddingContext)
    <span class="truncate text-sm font-semibold text-warning-700 dark:text-warning-300">
        {{ $organizerWeddingContext }}
    </span>

    <x-filament::icon
        icon="heroicon-m-chevron-right"
        class="h-4 w-4 text-gray-400 dark:text-gray-500"
    />
@endif
