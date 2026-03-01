<div
    @class([
        'dm-topbar-title flex min-w-0 items-center gap-x-4',
        'flex-1' => $isGiftItemsListPage || $isGiftItemsCreatePage || $isWeddingSettingsPage || $isTransactionsListPage || $isSiteLayoutsListPage || $isSiteTemplatesListPage || $isWeddingPlansListPage || $isWeddingPlansCreatePage || $isUsersListPage || $isUsersCreatePage || $isUsersEditPage,
    ])
    x-data
    @navigate.window="$wire.updateTitle()"
>
    @if ($isGiftItemsListPage)
        <div class="dm-topbar-gifts flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                <a
                    href="{{ $giftItemsIndexUrl }}"
                    class="truncate text-sm font-semibold text-primary-600 dark:text-primary-400"
                >
                    {{ $title }}
                </a>

                <x-filament::icon
                    icon="heroicon-m-chevron-right"
                    class="h-4 w-4 text-gray-400 dark:text-gray-500"
                />

                <span class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                    Listar
                </span>
            </nav>

            <div class="hidden shrink-0 items-center gap-2 md:flex">
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
    @elseif ($isGiftItemsCreatePage)
        <div class="dm-topbar-gifts-create flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                <a
                    href="{{ $giftItemsIndexUrl }}"
                    class="truncate text-sm font-semibold text-primary-600 dark:text-primary-400"
                >
                    {{ $title }}
                </a>

                <x-filament::icon
                    icon="heroicon-m-chevron-right"
                    class="h-4 w-4 text-gray-400 dark:text-gray-500"
                />

                <span class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                    Criar
                </span>
            </nav>

            <div class="hidden shrink-0 items-center gap-2 md:flex">
                <x-filament::button
                    tag="a"
                    :href="$giftItemsIndexUrl"
                    color="gray"
                    size="sm"
                >
                    Cancelar
                </x-filament::button>

                <x-filament::button
                    type="button"
                    color="gray"
                    icon="heroicon-o-document-duplicate"
                    size="sm"
                    wire:click="createAnotherGiftFromTopbar"
                >
                    Salvar e criar outro
                </x-filament::button>

                <x-filament::button
                    type="submit"
                    form="form"
                    color="danger"
                    icon="heroicon-o-check"
                    size="sm"
                >
                    Criar Presente
                </x-filament::button>
            </div>
        </div>
    @elseif ($isWeddingSettingsPage)
        <div class="dm-topbar-wedding-settings flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                <span class="truncate text-sm font-semibold text-primary-600 dark:text-primary-400">
                    {{ $title }}
                </span>

                <x-filament::icon
                    icon="heroicon-m-chevron-right"
                    class="h-4 w-4 text-gray-400 dark:text-gray-500"
                />

                <span class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                    Editar
                </span>
            </nav>

            <div class="hidden shrink-0 items-center gap-2 md:flex">
                <x-filament::button
                    type="submit"
                    form="wedding-settings-form"
                    color="danger"
                    icon="heroicon-o-check"
                    size="sm"
                >
                    Salvar Configurações
                </x-filament::button>
            </div>
        </div>
    @elseif ($isTransactionsListPage)
        <div class="dm-topbar-transactions flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                <a
                    href="{{ $transactionsIndexUrl }}"
                    class="truncate text-sm font-semibold text-primary-600 dark:text-primary-400"
                >
                    {{ $title }}
                </a>

                <x-filament::icon
                    icon="heroicon-m-chevron-right"
                    class="h-4 w-4 text-gray-400 dark:text-gray-500"
                />

                <span class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                    Listar
                </span>
            </nav>

            <div class="hidden shrink-0 items-center gap-2 md:flex">
                <x-filament::button
                    tag="a"
                    :href="$transactionsExportUrl"
                    color="success"
                    icon="heroicon-o-arrow-down-tray"
                    size="sm"
                >
                    Exportar Tudo (CSV)
                </x-filament::button>
            </div>
        </div>
    @elseif ($isSiteLayoutsListPage)
        <div class="dm-topbar-site-layouts flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                <a
                    href="{{ $siteLayoutsIndexUrl }}"
                    class="truncate text-sm font-semibold text-primary-600 dark:text-primary-400"
                >
                    {{ $title }}
                </a>

                <x-filament::icon
                    icon="heroicon-m-chevron-right"
                    class="h-4 w-4 text-gray-400 dark:text-gray-500"
                />

                <span class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                    Listar
                </span>
            </nav>

            <div class="hidden shrink-0 items-center gap-2 md:flex">
                @if ($canCreateSite)
                    <x-filament::button
                        tag="a"
                        :href="$siteEditorUrl"
                        color="danger"
                        icon="heroicon-o-plus"
                        size="sm"
                    >
                        Criar Site
                    </x-filament::button>
                @endif
            </div>
        </div>
    @elseif ($isSiteTemplatesListPage)
        <div class="dm-topbar-site-templates flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                <a
                    href="{{ $siteTemplatesIndexUrl }}"
                    class="truncate text-sm font-semibold text-primary-600 dark:text-primary-400"
                >
                    {{ $title }}
                </a>

                <x-filament::icon
                    icon="heroicon-m-chevron-right"
                    class="h-4 w-4 text-gray-400 dark:text-gray-500"
                />

                <span class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                    Listar
                </span>
            </nav>

            <div class="hidden shrink-0 items-center gap-2 md:flex">
                @if ($canCreateTemplate)
                    <x-filament::button
                        tag="a"
                        :href="$siteTemplatesCreateUrl"
                        color="danger"
                        icon="heroicon-o-plus"
                        size="sm"
                    >
                        Criar Template
                    </x-filament::button>
                @endif
            </div>
        </div>
    @elseif ($isWeddingPlansListPage)
        <div class="dm-topbar-wedding-plans flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                <a
                    href="{{ $weddingPlansIndexUrl }}"
                    class="truncate text-sm font-semibold text-primary-600 dark:text-primary-400"
                >
                    {{ $title }}
                </a>

                <x-filament::icon
                    icon="heroicon-m-chevron-right"
                    class="h-4 w-4 text-gray-400 dark:text-gray-500"
                />

                <span class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                    Listar
                </span>
            </nav>

            <div class="hidden shrink-0 items-center gap-2 md:flex">
                @if ($canCreateWeddingPlan)
                    <x-filament::button
                        tag="a"
                        :href="$weddingPlansCreateUrl"
                        color="danger"
                        icon="heroicon-o-plus"
                        size="sm"
                    >
                        Criar Planejamento
                    </x-filament::button>
                @endif
            </div>
        </div>
    @elseif ($isWeddingPlansCreatePage)
        <div class="dm-topbar-wedding-plans-create flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                <a
                    href="{{ $weddingPlansIndexUrl }}"
                    class="truncate text-sm font-semibold text-primary-600 dark:text-primary-400"
                >
                    {{ $title }}
                </a>

                <x-filament::icon
                    icon="heroicon-m-chevron-right"
                    class="h-4 w-4 text-gray-400 dark:text-gray-500"
                />

                <span class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                    Criar
                </span>
            </nav>

            <div class="hidden shrink-0 items-center gap-2 md:flex">
                <x-filament::button
                    tag="a"
                    :href="$weddingPlansIndexUrl"
                    color="gray"
                    size="sm"
                >
                    Cancelar
                </x-filament::button>

                <x-filament::button
                    type="button"
                    color="gray"
                    icon="heroicon-o-document-duplicate"
                    size="sm"
                    wire:click="createAnotherWeddingPlanFromTopbar"
                >
                    Salvar e criar outro
                </x-filament::button>

                <x-filament::button
                    type="submit"
                    form="form"
                    color="danger"
                    icon="heroicon-o-check"
                    size="sm"
                >
                    Criar Planejamento
                </x-filament::button>
            </div>
        </div>
    @elseif ($isUsersListPage)
        <div class="dm-topbar-users flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                <a
                    href="{{ $usersIndexUrl }}"
                    class="truncate text-sm font-semibold text-primary-600 dark:text-primary-400"
                >
                    {{ $title }}
                </a>

                <x-filament::icon
                    icon="heroicon-m-chevron-right"
                    class="h-4 w-4 text-gray-400 dark:text-gray-500"
                />

                <span class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                    Listar
                </span>
            </nav>

            <div class="hidden shrink-0 items-center gap-2 md:flex">
                @if ($canCreateUser)
                    <x-filament::button
                        tag="a"
                        :href="$usersCreateUrl"
                        color="danger"
                        icon="heroicon-o-plus"
                        size="sm"
                    >
                        {{ $usersCreateLabel }}
                    </x-filament::button>
                @endif
            </div>
        </div>
    @elseif ($isUsersCreatePage)
        <div class="dm-topbar-users-create flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                <a
                    href="{{ $usersIndexUrl }}"
                    class="truncate text-sm font-semibold text-primary-600 dark:text-primary-400"
                >
                    {{ $title }}
                </a>

                <x-filament::icon
                    icon="heroicon-m-chevron-right"
                    class="h-4 w-4 text-gray-400 dark:text-gray-500"
                />

                <span class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                    Criar
                </span>
            </nav>

            <div class="hidden shrink-0 items-center gap-2 md:flex">
                <x-filament::button
                    tag="a"
                    :href="$usersIndexUrl"
                    color="gray"
                    size="sm"
                >
                    Cancelar
                </x-filament::button>

                <x-filament::button
                    type="button"
                    color="gray"
                    icon="heroicon-o-document-duplicate"
                    size="sm"
                    wire:click="createAnotherUserFromTopbar"
                >
                    Salvar e criar outro
                </x-filament::button>

                <x-filament::button
                    type="submit"
                    form="form"
                    color="danger"
                    icon="heroicon-o-check"
                    size="sm"
                >
                    Criar Usuário
                </x-filament::button>
            </div>
        </div>
    @elseif ($isUsersEditPage)
        <div class="dm-topbar-users-edit flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                <a
                    href="{{ $usersIndexUrl }}"
                    class="truncate text-sm font-semibold text-primary-600 dark:text-primary-400"
                >
                    {{ $title }}
                </a>

                <x-filament::icon
                    icon="heroicon-m-chevron-right"
                    class="h-4 w-4 text-gray-400 dark:text-gray-500"
                />

                <span class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                    Editar
                </span>
            </nav>

            <div class="hidden shrink-0 items-center gap-2 md:flex">
                <x-filament::button
                    tag="a"
                    :href="$usersIndexUrl"
                    color="gray"
                    size="sm"
                >
                    Cancelar
                </x-filament::button>

                <x-filament::button
                    type="button"
                    color="danger"
                    icon="heroicon-o-trash"
                    size="sm"
                    x-on:click="if (confirm('Remover usuário deste casamento?')) { $wire.deleteUserFromTopbar() }"
                >
                    Remover do Casamento
                </x-filament::button>

                <x-filament::button
                    type="submit"
                    form="form"
                    color="danger"
                    icon="heroicon-o-check"
                    size="sm"
                >
                    Salvar Alterações
                </x-filament::button>
            </div>
        </div>
    @else
        <span class="text-lg font-semibold text-gray-950 dark:text-white">
            {{ $title }}
        </span>
    @endif
</div>
