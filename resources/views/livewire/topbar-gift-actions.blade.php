<div
    class="border-t border-gray-200 bg-white px-4 py-2 dark:border-white/10 dark:bg-gray-900 md:hidden md:px-6 lg:px-8"
    x-data
    @navigate.window="$wire.refreshState()"
>
    <div class="flex items-center justify-end gap-2">
        <x-filament::dropdown placement="bottom-end" teleport>
            <x-slot name="trigger">
                <x-filament::button
                    color="danger"
                    icon="heroicon-o-adjustments-horizontal"
                    size="sm"
                >
                    {{ $this->giftModeLabel }}
                </x-filament::button>
            </x-slot>

            <x-filament::dropdown.list>
                <x-filament::dropdown.list.item
                    :color="$giftRegistryMode === 'quantity' ? 'primary' : 'gray'"
                    icon="heroicon-o-list-bullet"
                    wire:click="changeGiftRegistryMode('quantity')"
                >
                    Quantidade
                </x-filament::dropdown.list.item>

                <x-filament::dropdown.list.item
                    :color="$giftRegistryMode === 'quota' ? 'primary' : 'gray'"
                    icon="heroicon-o-chart-bar"
                    wire:click="changeGiftRegistryMode('quota')"
                >
                    Quota
                </x-filament::dropdown.list.item>
            </x-filament::dropdown.list>
        </x-filament::dropdown>

        <x-filament::button
            tag="a"
            :href="$giftItemsCreateUrl"
            color="danger"
            icon="heroicon-o-plus"
            size="sm"
        >
            Criar Presente
        </x-filament::button>
    </div>
</div>

