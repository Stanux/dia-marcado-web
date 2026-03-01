@php
    $user = auth()->user();
    $weddingId = $user?->current_wedding_id ?? session('filament_wedding_id');
    $hasSite = $weddingId && \App\Models\SiteLayout::query()->where('wedding_id', $weddingId)->exists();
@endphp

@if ($weddingId && ! $hasSite)
    <div class="border-t border-gray-200 bg-white px-4 py-2 dark:border-white/10 dark:bg-gray-900 md:hidden md:px-6 lg:px-8">
        <div class="flex items-center justify-end gap-2">
            <x-filament::button
                tag="a"
                href="{{ route('filament.admin.pages.site-editor') }}"
                color="danger"
                icon="heroicon-o-plus"
                size="sm"
            >
                Criar Site
            </x-filament::button>
        </div>
    </div>
@endif

