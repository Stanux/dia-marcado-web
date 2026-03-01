@php
    $routeRecord = request()->route('record');
    $giftItem = $routeRecord instanceof \App\Models\GiftItem
        ? $routeRecord
        : \App\Models\GiftItem::withoutGlobalScopes()->find($routeRecord);

    $canDeleteGiftItem = $giftItem && \App\Filament\Resources\GiftItemResource::canDelete($giftItem);
@endphp

<div class="border-t border-gray-200 bg-white px-4 py-2 dark:border-white/10 dark:bg-gray-900 md:hidden md:px-6 lg:px-8" x-data>
    <div class="flex flex-wrap items-center justify-end gap-2">
        <x-filament::button
            tag="a"
            href="{{ route('filament.admin.resources.gift-items.index') }}"
            color="gray"
            size="sm"
        >
            Cancelar
        </x-filament::button>

        <x-filament::button
            type="button"
            color="warning"
            icon="heroicon-o-arrow-path"
            size="sm"
            x-on:click="window.Livewire.dispatch('topbar-gift-restore')"
        >
            Restaurar
        </x-filament::button>

        @if ($canDeleteGiftItem)
            <x-filament::button
                type="button"
                color="danger"
                icon="heroicon-o-trash"
                size="sm"
                x-on:click="window.Livewire.dispatch('topbar-gift-delete')"
            >
                Excluir
            </x-filament::button>
        @endif

        <x-filament::button
            type="submit"
            form-id="form"
            color="danger"
            icon="heroicon-o-check"
            size="sm"
        >
            Salvar Alterações
        </x-filament::button>
    </div>
</div>
