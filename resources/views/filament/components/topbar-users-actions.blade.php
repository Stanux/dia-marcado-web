@php
    $user = auth()->user();
    $weddingId = $user?->current_wedding_id ?? session('filament_wedding_id');
    $wedding = $weddingId ? \App\Models\Wedding::find($weddingId) : null;
    $canCreateUser = false;
    $createLabel = 'Novo Usuário';

    if ($user && $wedding) {
        if ($user->isAdmin() || $user->isCoupleIn($wedding)) {
            $canCreateUser = true;
            $createLabel = 'Novo Usuário';
        } elseif ($user->isOrganizerIn($wedding) && $user->hasPermissionIn($wedding, 'users')) {
            $canCreateUser = true;
            $createLabel = 'Novo Convidado';
        }
    }
@endphp

@if ($canCreateUser)
    <div class="border-t border-gray-200 bg-white px-4 py-2 dark:border-white/10 dark:bg-gray-900 md:hidden md:px-6 lg:px-8">
        <div class="flex items-center justify-end gap-2">
            <x-filament::button
                tag="a"
                href="{{ route('filament.admin.resources.users.create') }}"
                color="danger"
                icon="heroicon-o-plus"
                size="sm"
            >
                {{ $createLabel }}
            </x-filament::button>
        </div>
    </div>
@endif
