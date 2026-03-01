@if (auth()->user()?->isAdmin())
    <div class="border-t border-gray-200 bg-white px-4 py-2 dark:border-white/10 dark:bg-gray-900 md:hidden md:px-6 lg:px-8">
        <div class="flex items-center justify-end gap-2">
            <x-filament::button
                tag="a"
                href="{{ route('filament.admin.resources.site-templates.create') }}"
                color="danger"
                icon="heroicon-o-plus"
                size="sm"
            >
                Criar Template
            </x-filament::button>
        </div>
    </div>
@endif

