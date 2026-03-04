<div
    @class([
        'dm-topbar-title flex min-w-0 items-center gap-x-4',
        'flex-1' => $isGiftItemsListPage || $isGiftItemsCreatePage || $isGiftItemsEditPage || $isWeddingSettingsPage || $isTransactionsListPage || $isSiteLayoutsListPage || $isSiteTemplatesListPage || $isWeddingPlansListPage || $isWeddingPlansCreatePage || $isWeddingPlansEditPage || $isTaskCreatePage || $isTaskEditPage || $isUsersListPage || $isUsersCreatePage || $isUsersEditPage || $isWeddingVendorsListPage || $isWeddingVendorsCreatePage || $isWeddingVendorsEditPage || $isWeddingEventsListPage || $isWeddingEventsCreatePage || $isWeddingEventsEditPage || $isWeddingGuestsListPage || $isWeddingGuestsCreatePage || $isWeddingGuestsEditPage || $isWeddingInvitesListPage || $isWeddingInvitesCreatePage || $isWeddingInvitesEditPage,
    ])
    x-data
    @navigate.window="$wire.updateTitle()"
>
    @if ($isGiftItemsListPage)
        <div class="dm-topbar-gifts flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                @include('livewire.partials.organizer-wedding-breadcrumb')
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
                @include('livewire.partials.organizer-wedding-breadcrumb')
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
                    form-id="form"
                    color="danger"
                    icon="heroicon-o-check"
                    size="sm"
                >
                    Criar Presente
                </x-filament::button>
            </div>
        </div>
    @elseif ($isGiftItemsEditPage)
        <div class="dm-topbar-gifts-edit flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                @include('livewire.partials.organizer-wedding-breadcrumb')
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
                    Editar
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
                    color="warning"
                    icon="heroicon-o-arrow-path"
                    size="sm"
                    wire:click="restoreGiftFromTopbar"
                >
                    Restaurar
                </x-filament::button>

                @if ($canDeleteGiftItem)
                    <x-filament::button
                        type="button"
                        color="danger"
                        icon="heroicon-o-trash"
                        size="sm"
                        wire:click="deleteGiftFromTopbar"
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
    @elseif ($isWeddingSettingsPage)
        <div class="dm-topbar-wedding-settings flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                @include('livewire.partials.organizer-wedding-breadcrumb')
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
                    form-id="wedding-settings-form"
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
                @include('livewire.partials.organizer-wedding-breadcrumb')
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
                @include('livewire.partials.organizer-wedding-breadcrumb')
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
                @include('livewire.partials.organizer-wedding-breadcrumb')
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
                @include('livewire.partials.organizer-wedding-breadcrumb')
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
                @include('livewire.partials.organizer-wedding-breadcrumb')
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
                    type="submit"
                    form-id="form"
                    color="danger"
                    icon="heroicon-o-check"
                    size="sm"
                >
                    Salvar Planejamento
                </x-filament::button>
            </div>
        </div>
    @elseif ($isWeddingPlansEditPage)
        <div class="dm-topbar-wedding-plans-edit flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                @include('livewire.partials.organizer-wedding-breadcrumb')
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
                    Editar
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

                @if ($isWeddingPlanTimelineView)
                    <x-filament::button
                        type="button"
                        color="gray"
                        icon="heroicon-o-table-cells"
                        size="sm"
                        wire:click="showWeddingPlanTableFromTopbar"
                    >
                        Tabela
                    </x-filament::button>
                @else
                    <x-filament::button
                        type="button"
                        color="gray"
                        icon="heroicon-o-calendar-days"
                        size="sm"
                        wire:click="showWeddingPlanTimelineFromTopbar"
                    >
                        Timeline
                    </x-filament::button>
                @endif

                @if ($canCreateWeddingPlanTask && $weddingPlanCreateTaskUrl)
                    <x-filament::button
                        tag="a"
                        :href="$weddingPlanCreateTaskUrl"
                        color="gray"
                        icon="heroicon-o-plus"
                        size="sm"
                    >
                        Criar Tarefa
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
    @elseif ($isTaskCreatePage)
        <div class="dm-topbar-task-create flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                @include('livewire.partials.organizer-wedding-breadcrumb')
                <a
                    href="{{ $taskCreateReturnUrl }}"
                    class="truncate text-sm font-semibold text-primary-600 dark:text-primary-400"
                >
                    {{ $title }}
                </a>

                <x-filament::icon
                    icon="heroicon-m-chevron-right"
                    class="h-4 w-4 text-gray-400 dark:text-gray-500"
                />

                <span class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                    Criar Tarefa
                </span>
            </nav>

            <div class="hidden shrink-0 items-center gap-2 md:flex">
                <x-filament::button
                    type="button"
                    color="gray"
                    size="sm"
                    wire:click="returnToPlanFromTopbar"
                >
                    Cancelar
                </x-filament::button>

                <x-filament::button
                    type="button"
                    color="gray"
                    icon="heroicon-o-document-duplicate"
                    size="sm"
                    wire:click="createAnotherTaskFromTopbar"
                >
                    Salvar e criar outro
                </x-filament::button>

                <x-filament::button
                    type="button"
                    color="danger"
                    icon="heroicon-o-check"
                    size="sm"
                    wire:click="createTaskFromTopbar"
                >
                    Salvar Tarefa
                </x-filament::button>
            </div>
        </div>
    @elseif ($isTaskEditPage)
        <div class="dm-topbar-task-edit flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                @include('livewire.partials.organizer-wedding-breadcrumb')
                <a
                    href="{{ $taskCreateReturnUrl }}"
                    class="truncate text-sm font-semibold text-primary-600 dark:text-primary-400"
                >
                    {{ $title }}
                </a>

                <x-filament::icon
                    icon="heroicon-m-chevron-right"
                    class="h-4 w-4 text-gray-400 dark:text-gray-500"
                />

                <span class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                    Editar Tarefa
                </span>
            </nav>

            <div class="hidden shrink-0 items-center gap-2 md:flex">
                <x-filament::button
                    type="button"
                    color="gray"
                    size="sm"
                    wire:click="returnToPlanFromTopbar"
                >
                    Cancelar
                </x-filament::button>

                @if ($canDeleteTask)
                    <x-filament::button
                        type="button"
                        color="danger"
                        icon="heroicon-o-trash"
                        size="sm"
                        wire:click="deleteTaskFromTopbar"
                    >
                        Excluir
                    </x-filament::button>
                @endif

                <x-filament::button
                    type="button"
                    color="danger"
                    icon="heroicon-o-check"
                    size="sm"
                    wire:click="saveTaskFromTopbar"
                >
                    Salvar Alterações
                </x-filament::button>
            </div>
        </div>
    @elseif ($isUsersListPage)
        <div class="dm-topbar-users flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                @include('livewire.partials.organizer-wedding-breadcrumb')
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
                @include('livewire.partials.organizer-wedding-breadcrumb')
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
                    form-id="form"
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
                @include('livewire.partials.organizer-wedding-breadcrumb')
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
                    form-id="form"
                    color="danger"
                    icon="heroicon-o-check"
                    size="sm"
                >
                    Salvar Alterações
                </x-filament::button>
            </div>
        </div>
    @elseif ($isWeddingVendorsListPage)
        <div class="dm-topbar-wedding-vendors flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                @include('livewire.partials.organizer-wedding-breadcrumb')
                <a
                    href="{{ $weddingVendorsIndexUrl }}"
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
                @if ($canCreateWeddingVendor)
                    <x-filament::button
                        tag="a"
                        :href="$weddingVendorsCreateUrl"
                        color="danger"
                        icon="heroicon-o-plus"
                        size="sm"
                    >
                        Novo Fornecedor
                    </x-filament::button>
                @endif
            </div>
        </div>
    @elseif ($isWeddingVendorsCreatePage)
        <div class="dm-topbar-wedding-vendors-create flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                @include('livewire.partials.organizer-wedding-breadcrumb')
                <a
                    href="{{ $weddingVendorsIndexUrl }}"
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
                    :href="$weddingVendorsIndexUrl"
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
                    wire:click="createAnotherWeddingVendorFromTopbar"
                >
                    Salvar e criar outro
                </x-filament::button>

                <x-filament::button
                    type="submit"
                    form-id="form"
                    color="danger"
                    icon="heroicon-o-check"
                    size="sm"
                >
                    Criar Fornecedor
                </x-filament::button>
            </div>
        </div>
    @elseif ($isWeddingVendorsEditPage)
        <div class="dm-topbar-wedding-vendors-edit flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                @include('livewire.partials.organizer-wedding-breadcrumb')
                <a
                    href="{{ $weddingVendorsIndexUrl }}"
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
                    :href="$weddingVendorsIndexUrl"
                    color="gray"
                    size="sm"
                >
                    Cancelar
                </x-filament::button>

                @if ($canDeleteWeddingVendor)
                    <x-filament::button
                        type="button"
                        color="danger"
                        icon="heroicon-o-trash"
                        size="sm"
                        wire:click="deleteWeddingVendorFromTopbar"
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
    @elseif ($isWeddingEventsListPage)
        <div class="dm-topbar-wedding-events flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                @include('livewire.partials.organizer-wedding-breadcrumb')
                <a
                    href="{{ $weddingEventsIndexUrl }}"
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
                @if ($canCreateWeddingEvent)
                    <x-filament::button
                        tag="a"
                        :href="$weddingEventsCreateUrl"
                        color="danger"
                        icon="heroicon-o-plus"
                        size="sm"
                    >
                        Criar Evento
                    </x-filament::button>
                @endif
            </div>
        </div>
    @elseif ($isWeddingEventsCreatePage)
        <div class="dm-topbar-wedding-events-create flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                @include('livewire.partials.organizer-wedding-breadcrumb')
                <a
                    href="{{ $weddingEventsIndexUrl }}"
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
                    :href="$weddingEventsIndexUrl"
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
                    wire:click="createAnotherWeddingEventFromTopbar"
                >
                    Salvar e criar outro
                </x-filament::button>

                <x-filament::button
                    type="submit"
                    form-id="form"
                    color="danger"
                    icon="heroicon-o-check"
                    size="sm"
                >
                    Salvar Evento
                </x-filament::button>
            </div>
        </div>
    @elseif ($isWeddingEventsEditPage)
        <div class="dm-topbar-wedding-events-edit flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                @include('livewire.partials.organizer-wedding-breadcrumb')
                <a
                    href="{{ $weddingEventsIndexUrl }}"
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
                    :href="$weddingEventsIndexUrl"
                    color="gray"
                    size="sm"
                >
                    Cancelar
                </x-filament::button>

                @if ($canDeleteWeddingEvent)
                    <x-filament::button
                        type="button"
                        color="danger"
                        icon="heroicon-o-trash"
                        size="sm"
                        wire:click="deleteWeddingEventFromTopbar"
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
    @elseif ($isWeddingGuestsListPage)
        <div class="dm-topbar-wedding-guests flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                @include('livewire.partials.organizer-wedding-breadcrumb')
                <a
                    href="{{ $weddingGuestsIndexUrl }}"
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
                @if ($canCreateWeddingGuest)
                    <x-filament::button
                        tag="a"
                        :href="$weddingGuestsCreateUrl"
                        color="danger"
                        icon="heroicon-o-plus"
                        size="sm"
                    >
                        Criar Convidado
                    </x-filament::button>
                @endif
            </div>
        </div>
    @elseif ($isWeddingGuestsCreatePage)
        <div class="dm-topbar-wedding-guests-create flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                @include('livewire.partials.organizer-wedding-breadcrumb')
                <a
                    href="{{ $weddingGuestsIndexUrl }}"
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
                    :href="$weddingGuestsIndexUrl"
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
                    wire:click="createAnotherWeddingGuestFromTopbar"
                >
                    Salvar e criar outro
                </x-filament::button>

                <x-filament::button
                    type="submit"
                    form-id="form"
                    color="danger"
                    icon="heroicon-o-check"
                    size="sm"
                >
                    Salvar Convidado
                </x-filament::button>
            </div>
        </div>
    @elseif ($isWeddingGuestsEditPage)
        <div class="dm-topbar-wedding-guests-edit flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                @include('livewire.partials.organizer-wedding-breadcrumb')
                <a
                    href="{{ $weddingGuestsIndexUrl }}"
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
                    :href="$weddingGuestsIndexUrl"
                    color="gray"
                    size="sm"
                >
                    Cancelar
                </x-filament::button>

                @if ($canDeleteWeddingGuest)
                    <x-filament::button
                        type="button"
                        color="danger"
                        icon="heroicon-o-trash"
                        size="sm"
                        wire:click="deleteWeddingGuestFromTopbar"
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
    @elseif ($isWeddingInvitesListPage)
        <div class="dm-topbar-wedding-invites flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                @include('livewire.partials.organizer-wedding-breadcrumb')
                <a
                    href="{{ $weddingInvitesIndexUrl }}"
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
                @if ($canCreateWeddingInvite)
                    <x-filament::button
                        tag="a"
                        :href="$weddingInvitesCreateUrl"
                        color="danger"
                        icon="heroicon-o-plus"
                        size="sm"
                    >
                        Criar Convite
                    </x-filament::button>
                @endif
            </div>
        </div>
    @elseif ($isWeddingInvitesCreatePage)
        <div class="dm-topbar-wedding-invites-create flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                @include('livewire.partials.organizer-wedding-breadcrumb')
                <a
                    href="{{ $weddingInvitesIndexUrl }}"
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
                    :href="$weddingInvitesIndexUrl"
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
                    wire:click="createAnotherWeddingInviteFromTopbar"
                >
                    Salvar e criar outro
                </x-filament::button>

                <x-filament::button
                    type="submit"
                    form-id="form"
                    color="danger"
                    icon="heroicon-o-check"
                    size="sm"
                >
                    Salvar Convite
                </x-filament::button>
            </div>
        </div>
    @elseif ($isWeddingInvitesEditPage)
        <div class="dm-topbar-wedding-invites-edit flex w-full min-w-0 items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-2 text-sm">
                @include('livewire.partials.organizer-wedding-breadcrumb')
                <a
                    href="{{ $weddingInvitesIndexUrl }}"
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
                    :href="$weddingInvitesIndexUrl"
                    color="gray"
                    size="sm"
                >
                    Cancelar
                </x-filament::button>

                @if ($canDeleteWeddingInvite)
                    <x-filament::button
                        type="button"
                        color="danger"
                        icon="heroicon-o-trash"
                        size="sm"
                        wire:click="deleteWeddingInviteFromTopbar"
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
    @else
        <span class="text-lg font-semibold text-gray-950 dark:text-white">
            {{ $title }}
        </span>
    @endif
</div>
