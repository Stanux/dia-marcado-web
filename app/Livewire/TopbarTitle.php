<?php

namespace App\Livewire;

use App\Filament\Pages\PlanningDashboard;
use App\Filament\Pages\WeddingSettings;
use App\Filament\Resources\GiftItemResource;
use App\Filament\Resources\SiteLayoutResource;
use App\Filament\Resources\SiteTemplateResource;
use App\Filament\Resources\TaskResource;
use App\Filament\Resources\TransactionResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\WeddingEventResource;
use App\Filament\Resources\WeddingPlanResource;
use App\Filament\Resources\WeddingGuestResource;
use App\Filament\Resources\WeddingInviteResource;
use App\Filament\Resources\WeddingVendorResource;
use App\Models\GiftItem;
use App\Models\GiftRegistryConfig;
use App\Models\SiteLayout;
use App\Models\Task;
use App\Models\WeddingEvent;
use App\Models\WeddingGuest;
use App\Models\WeddingInvite;
use App\Models\Wedding;
use App\Models\WeddingPlan;
use App\Models\WeddingVendor;
use App\Services\GiftRegistryModeService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Livewire\Component;
use Livewire\Attributes\On;

class TopbarTitle extends Component
{
    public string $title = '';
    public bool $isGiftItemsListPage = false;
    public bool $isGiftItemsCreatePage = false;
    public bool $isGiftItemsEditPage = false;
    public bool $isWeddingSettingsPage = false;
    public bool $isTransactionsListPage = false;
    public bool $isSiteLayoutsListPage = false;
    public bool $isSiteTemplatesListPage = false;
    public bool $isWeddingPlansListPage = false;
    public bool $isWeddingPlansCreatePage = false;
    public bool $isWeddingPlansEditPage = false;
    public bool $isTaskCreatePage = false;
    public bool $isTaskEditPage = false;
    public bool $isUsersListPage = false;
    public bool $isUsersCreatePage = false;
    public bool $isUsersEditPage = false;
    public bool $isWeddingVendorsListPage = false;
    public bool $isWeddingVendorsCreatePage = false;
    public bool $isWeddingVendorsEditPage = false;
    public bool $isWeddingEventsListPage = false;
    public bool $isWeddingEventsCreatePage = false;
    public bool $isWeddingEventsEditPage = false;
    public bool $isWeddingGuestsListPage = false;
    public bool $isWeddingGuestsCreatePage = false;
    public bool $isWeddingGuestsEditPage = false;
    public bool $isWeddingInvitesListPage = false;
    public bool $isWeddingInvitesCreatePage = false;
    public bool $isWeddingInvitesEditPage = false;
    public bool $showOrganizerWeddingContext = false;
    public string $organizerWeddingContext = '';
    public string $giftRegistryMode = GiftRegistryModeService::MODE_QUANTITY;
    public string $giftItemsIndexUrl = '';
    public string $giftItemsCreateUrl = '';
    public bool $canDeleteGiftItem = false;
    public string $transactionsIndexUrl = '';
    public string $transactionsExportUrl = '';
    public string $siteLayoutsIndexUrl = '';
    public string $siteEditorUrl = '';
    public bool $canCreateSite = false;
    public string $siteTemplatesIndexUrl = '';
    public string $siteTemplatesCreateUrl = '';
    public bool $canCreateTemplate = false;
    public string $weddingPlansIndexUrl = '';
    public string $weddingPlansCreateUrl = '';
    public string $weddingPlanDashboardUrl = '';
    public string $weddingPlanCreateTaskUrl = '';
    public bool $isWeddingPlanTimelineView = false;
    public bool $canCreateWeddingPlanTask = false;
    public bool $canArchiveWeddingPlan = false;
    public bool $canUnarchiveWeddingPlan = false;
    public bool $canCreateWeddingPlan = false;
    public string $taskCreateReturnUrl = '';
    public bool $canDeleteTask = false;
    public string $usersIndexUrl = '';
    public string $usersCreateUrl = '';
    public bool $canCreateUser = false;
    public string $usersCreateLabel = 'Novo Usuário';
    public string $weddingVendorsIndexUrl = '';
    public string $weddingVendorsCreateUrl = '';
    public bool $canCreateWeddingVendor = false;
    public bool $canDeleteWeddingVendor = false;
    public string $weddingEventsIndexUrl = '';
    public string $weddingEventsCreateUrl = '';
    public bool $canCreateWeddingEvent = false;
    public bool $canDeleteWeddingEvent = false;
    public string $weddingGuestsIndexUrl = '';
    public string $weddingGuestsCreateUrl = '';
    public bool $canCreateWeddingGuest = false;
    public bool $canDeleteWeddingGuest = false;
    public string $weddingInvitesIndexUrl = '';
    public string $weddingInvitesCreateUrl = '';
    public bool $canCreateWeddingInvite = false;
    public bool $canDeleteWeddingInvite = false;

    public function mount(): void
    {
        $this->updateTitle();
    }

    #[On('page-title-updated')]
    public function updateTitle(): void
    {
        $routeName = request()->route()?->getName() ?? '';
        [$this->showOrganizerWeddingContext, $this->organizerWeddingContext] = $this->resolveOrganizerWeddingContext();
        $this->isGiftItemsListPage = $routeName === 'filament.admin.resources.gift-items.index';
        $this->isGiftItemsCreatePage = $routeName === 'filament.admin.resources.gift-items.create';
        $this->isGiftItemsEditPage = $routeName === 'filament.admin.resources.gift-items.edit';
        $this->isWeddingSettingsPage = $routeName === 'filament.admin.pages.wedding-settings';
        $this->isTransactionsListPage = $routeName === 'filament.admin.resources.transactions.index';
        $this->isSiteLayoutsListPage = $routeName === 'filament.admin.resources.site-layouts.index';
        $this->isSiteTemplatesListPage = $routeName === 'filament.admin.resources.site-templates.index';
        $this->isWeddingPlansListPage = $routeName === 'filament.admin.resources.wedding-plans.index';
        $this->isWeddingPlansCreatePage = $routeName === 'filament.admin.resources.wedding-plans.create';
        $this->isWeddingPlansEditPage = $routeName === 'filament.admin.resources.wedding-plans.edit';
        $this->isTaskCreatePage = $routeName === 'filament.admin.resources.plan-tasks.create';
        $this->isTaskEditPage = $routeName === 'filament.admin.resources.plan-tasks.edit';
        $this->isUsersListPage = $routeName === 'filament.admin.resources.users.index';
        $this->isUsersCreatePage = $routeName === 'filament.admin.resources.users.create';
        $this->isUsersEditPage = $routeName === 'filament.admin.resources.users.edit';
        $this->isWeddingVendorsListPage = $routeName === 'filament.admin.resources.wedding-vendors.index';
        $this->isWeddingVendorsCreatePage = $routeName === 'filament.admin.resources.wedding-vendors.create';
        $this->isWeddingVendorsEditPage = $routeName === 'filament.admin.resources.wedding-vendors.edit';
        $this->isWeddingEventsListPage = $routeName === 'filament.admin.resources.events.index';
        $this->isWeddingEventsCreatePage = $routeName === 'filament.admin.resources.events.create';
        $this->isWeddingEventsEditPage = $routeName === 'filament.admin.resources.events.edit';
        $this->isWeddingGuestsListPage = $routeName === 'filament.admin.resources.guests-v2.index';
        $this->isWeddingGuestsCreatePage = $routeName === 'filament.admin.resources.guests-v2.create';
        $this->isWeddingGuestsEditPage = $routeName === 'filament.admin.resources.guests-v2.edit';
        $this->isWeddingInvitesListPage = $routeName === 'filament.admin.resources.invites-v2.index';
        $this->isWeddingInvitesCreatePage = $routeName === 'filament.admin.resources.invites-v2.create';
        $this->isWeddingInvitesEditPage = $routeName === 'filament.admin.resources.invites-v2.edit';
        $this->isWeddingPlanTimelineView = false;
        $this->weddingPlanDashboardUrl = '';
        $this->weddingPlanCreateTaskUrl = '';
        $this->canCreateWeddingPlanTask = false;
        $this->canArchiveWeddingPlan = false;
        $this->canUnarchiveWeddingPlan = false;
        $this->taskCreateReturnUrl = '';
        $this->canDeleteTask = false;
        $this->usersIndexUrl = '';
        $this->usersCreateUrl = '';
        $this->canCreateUser = false;
        $this->usersCreateLabel = 'Novo Usuário';
        $this->weddingVendorsIndexUrl = '';
        $this->weddingVendorsCreateUrl = '';
        $this->canCreateWeddingVendor = false;
        $this->canDeleteWeddingVendor = false;
        $this->weddingEventsIndexUrl = '';
        $this->weddingEventsCreateUrl = '';
        $this->canCreateWeddingEvent = false;
        $this->canDeleteWeddingEvent = false;
        $this->weddingGuestsIndexUrl = '';
        $this->weddingGuestsCreateUrl = '';
        $this->canCreateWeddingGuest = false;
        $this->canDeleteWeddingGuest = false;
        $this->weddingInvitesIndexUrl = '';
        $this->weddingInvitesCreateUrl = '';
        $this->canCreateWeddingInvite = false;
        $this->canDeleteWeddingInvite = false;
        $this->canDeleteGiftItem = false;

        if ($this->isGiftItemsListPage) {
            $this->title = (string) GiftItemResource::getPluralModelLabel();
            $this->giftRegistryMode = $this->getCurrentRegistryMode();
            $this->giftItemsIndexUrl = GiftItemResource::getUrl('index');
            $this->giftItemsCreateUrl = GiftItemResource::getUrl('create');
            $this->transactionsIndexUrl = '';
            $this->transactionsExportUrl = '';
            $this->siteLayoutsIndexUrl = '';
            $this->siteEditorUrl = '';
            $this->canCreateSite = false;
            $this->siteTemplatesIndexUrl = '';
            $this->siteTemplatesCreateUrl = '';
            $this->canCreateTemplate = false;
            $this->weddingPlansIndexUrl = '';
            $this->weddingPlansCreateUrl = '';
            $this->canCreateWeddingPlan = false;

            return;
        }

        if ($this->isGiftItemsCreatePage) {
            $this->title = (string) GiftItemResource::getPluralModelLabel();
            $this->giftItemsIndexUrl = GiftItemResource::getUrl('index');
            $this->giftItemsCreateUrl = GiftItemResource::getUrl('create');
            $this->transactionsIndexUrl = '';
            $this->transactionsExportUrl = '';
            $this->siteLayoutsIndexUrl = '';
            $this->siteEditorUrl = '';
            $this->canCreateSite = false;
            $this->siteTemplatesIndexUrl = '';
            $this->siteTemplatesCreateUrl = '';
            $this->canCreateTemplate = false;
            $this->weddingPlansIndexUrl = '';
            $this->weddingPlansCreateUrl = '';
            $this->canCreateWeddingPlan = false;

            return;
        }

        if ($this->isGiftItemsEditPage) {
            $this->title = (string) GiftItemResource::getPluralModelLabel();
            $this->giftItemsIndexUrl = GiftItemResource::getUrl('index');
            $this->giftItemsCreateUrl = '';
            $this->transactionsIndexUrl = '';
            $this->transactionsExportUrl = '';
            $this->siteLayoutsIndexUrl = '';
            $this->siteEditorUrl = '';
            $this->canCreateSite = false;
            $this->siteTemplatesIndexUrl = '';
            $this->siteTemplatesCreateUrl = '';
            $this->canCreateTemplate = false;
            $this->weddingPlansIndexUrl = '';
            $this->weddingPlansCreateUrl = '';
            $this->canCreateWeddingPlan = false;
            $this->usersIndexUrl = '';
            $this->usersCreateUrl = '';
            $this->canCreateUser = false;
            $this->usersCreateLabel = 'Novo Usuário';
            $this->weddingVendorsIndexUrl = '';
            $this->weddingVendorsCreateUrl = '';
            $this->canCreateWeddingVendor = false;
            $this->canDeleteWeddingVendor = false;

            $routeRecord = request()->route('record');
            $gift = $routeRecord instanceof GiftItem
                ? $routeRecord
                : GiftItem::withoutGlobalScopes()->find($routeRecord);
            $this->canDeleteGiftItem = $gift ? GiftItemResource::canDelete($gift) : false;

            return;
        }

        if ($this->isWeddingSettingsPage) {
            $this->title = (string) WeddingSettings::getNavigationLabel();
            $this->giftItemsIndexUrl = '';
            $this->giftItemsCreateUrl = '';
            $this->transactionsIndexUrl = '';
            $this->transactionsExportUrl = '';
            $this->siteLayoutsIndexUrl = '';
            $this->siteEditorUrl = '';
            $this->canCreateSite = false;
            $this->siteTemplatesIndexUrl = '';
            $this->siteTemplatesCreateUrl = '';
            $this->canCreateTemplate = false;
            $this->weddingPlansIndexUrl = '';
            $this->weddingPlansCreateUrl = '';
            $this->canCreateWeddingPlan = false;

            return;
        }

        if ($this->isTransactionsListPage) {
            $this->title = (string) TransactionResource::getPluralModelLabel();
            $this->giftItemsIndexUrl = '';
            $this->giftItemsCreateUrl = '';
            $this->transactionsIndexUrl = TransactionResource::getUrl('index');
            $this->transactionsExportUrl = route('admin.transactions.export-all');
            $this->siteLayoutsIndexUrl = '';
            $this->siteEditorUrl = '';
            $this->canCreateSite = false;
            $this->siteTemplatesIndexUrl = '';
            $this->siteTemplatesCreateUrl = '';
            $this->canCreateTemplate = false;
            $this->weddingPlansIndexUrl = '';
            $this->weddingPlansCreateUrl = '';
            $this->canCreateWeddingPlan = false;

            return;
        }

        if ($this->isSiteLayoutsListPage) {
            $this->title = (string) SiteLayoutResource::getNavigationLabel();
            $this->giftItemsIndexUrl = '';
            $this->giftItemsCreateUrl = '';
            $this->transactionsIndexUrl = '';
            $this->transactionsExportUrl = '';
            $this->siteLayoutsIndexUrl = SiteLayoutResource::getUrl('index');
            $this->siteEditorUrl = route('filament.admin.pages.site-editor');
            $this->canCreateSite = $this->canCreateSiteForCurrentWedding();
            $this->siteTemplatesIndexUrl = '';
            $this->siteTemplatesCreateUrl = '';
            $this->canCreateTemplate = false;
            $this->weddingPlansIndexUrl = '';
            $this->weddingPlansCreateUrl = '';
            $this->canCreateWeddingPlan = false;

            return;
        }

        if ($this->isSiteTemplatesListPage) {
            $this->title = (string) SiteTemplateResource::getNavigationLabel();
            $this->giftItemsIndexUrl = '';
            $this->giftItemsCreateUrl = '';
            $this->transactionsIndexUrl = '';
            $this->transactionsExportUrl = '';
            $this->siteLayoutsIndexUrl = '';
            $this->siteEditorUrl = '';
            $this->canCreateSite = false;
            $this->siteTemplatesIndexUrl = SiteTemplateResource::getUrl('index');
            $this->siteTemplatesCreateUrl = SiteTemplateResource::getUrl('create');
            $this->canCreateTemplate = SiteTemplateResource::canCreate();
            $this->weddingPlansIndexUrl = '';
            $this->weddingPlansCreateUrl = '';
            $this->canCreateWeddingPlan = false;

            return;
        }

        if ($this->isWeddingPlansListPage) {
            $this->title = (string) WeddingPlanResource::getNavigationLabel();
            $this->giftItemsIndexUrl = '';
            $this->giftItemsCreateUrl = '';
            $this->transactionsIndexUrl = '';
            $this->transactionsExportUrl = '';
            $this->siteLayoutsIndexUrl = '';
            $this->siteEditorUrl = '';
            $this->canCreateSite = false;
            $this->siteTemplatesIndexUrl = '';
            $this->siteTemplatesCreateUrl = '';
            $this->canCreateTemplate = false;
            $this->weddingPlansIndexUrl = WeddingPlanResource::getUrl('index');
            $this->weddingPlansCreateUrl = WeddingPlanResource::getUrl('create');
            $this->canCreateWeddingPlan = WeddingPlanResource::canCreate();

            return;
        }

        if ($this->isWeddingPlansCreatePage) {
            $this->title = (string) WeddingPlanResource::getNavigationLabel();
            $this->giftItemsIndexUrl = '';
            $this->giftItemsCreateUrl = '';
            $this->transactionsIndexUrl = '';
            $this->transactionsExportUrl = '';
            $this->siteLayoutsIndexUrl = '';
            $this->siteEditorUrl = '';
            $this->canCreateSite = false;
            $this->siteTemplatesIndexUrl = '';
            $this->siteTemplatesCreateUrl = '';
            $this->canCreateTemplate = false;
            $this->weddingPlansIndexUrl = WeddingPlanResource::getUrl('index');
            $this->weddingPlansCreateUrl = '';
            $this->canCreateWeddingPlan = false;

            return;
        }

        if ($this->isWeddingPlansEditPage) {
            $this->title = (string) WeddingPlanResource::getNavigationLabel();
            $this->giftItemsIndexUrl = '';
            $this->giftItemsCreateUrl = '';
            $this->transactionsIndexUrl = '';
            $this->transactionsExportUrl = '';
            $this->siteLayoutsIndexUrl = '';
            $this->siteEditorUrl = '';
            $this->canCreateSite = false;
            $this->siteTemplatesIndexUrl = '';
            $this->siteTemplatesCreateUrl = '';
            $this->canCreateTemplate = false;
            $this->weddingPlansIndexUrl = WeddingPlanResource::getUrl('index');
            $this->weddingPlansCreateUrl = '';
            $this->canCreateWeddingPlan = false;

            $routeRecord = request()->route('record');
            $plan = $routeRecord instanceof WeddingPlan
                ? $routeRecord
                : WeddingPlan::withoutGlobalScopes()->find($routeRecord);

            if ($plan) {
                $isAdmin = auth()->user()?->isAdmin() ?? false;

                $this->weddingPlanDashboardUrl = PlanningDashboard::getUrl(['plan' => $plan->getKey()]);
                $this->weddingPlanCreateTaskUrl = TaskResource::getUrl('create', [
                    'plan' => $plan->getKey(),
                    'return_to_plan' => $plan->getKey(),
                ]);
                $this->canCreateWeddingPlanTask = TaskResource::canCreate() && (! $plan->isArchived() || $isAdmin);
                $this->canArchiveWeddingPlan = ! $plan->isArchived();
                $this->canUnarchiveWeddingPlan = $plan->isArchived() && $isAdmin;
            }

            return;
        }

        if ($this->isTaskCreatePage) {
            $this->title = (string) WeddingPlanResource::getNavigationLabel();
            $this->giftItemsIndexUrl = '';
            $this->giftItemsCreateUrl = '';
            $this->transactionsIndexUrl = '';
            $this->transactionsExportUrl = '';
            $this->siteLayoutsIndexUrl = '';
            $this->siteEditorUrl = '';
            $this->canCreateSite = false;
            $this->siteTemplatesIndexUrl = '';
            $this->siteTemplatesCreateUrl = '';
            $this->canCreateTemplate = false;
            $this->weddingPlansIndexUrl = '';
            $this->weddingPlansCreateUrl = '';
            $this->canCreateWeddingPlan = false;
            $this->usersIndexUrl = '';
            $this->usersCreateUrl = '';
            $this->canCreateUser = false;
            $this->usersCreateLabel = 'Novo Usuário';

            $planId = request()->query('return_to_plan') ?? request()->query('plan');
            $this->taskCreateReturnUrl = $planId
                ? WeddingPlanResource::getUrl('edit', ['record' => $planId])
                : TaskResource::getUrl('index');

            return;
        }

        if ($this->isTaskEditPage) {
            $this->title = (string) WeddingPlanResource::getNavigationLabel();
            $this->giftItemsIndexUrl = '';
            $this->giftItemsCreateUrl = '';
            $this->transactionsIndexUrl = '';
            $this->transactionsExportUrl = '';
            $this->siteLayoutsIndexUrl = '';
            $this->siteEditorUrl = '';
            $this->canCreateSite = false;
            $this->siteTemplatesIndexUrl = '';
            $this->siteTemplatesCreateUrl = '';
            $this->canCreateTemplate = false;
            $this->weddingPlansIndexUrl = '';
            $this->weddingPlansCreateUrl = '';
            $this->canCreateWeddingPlan = false;
            $this->usersIndexUrl = '';
            $this->usersCreateUrl = '';
            $this->canCreateUser = false;
            $this->usersCreateLabel = 'Novo Usuário';

            $routeRecord = request()->route('record');
            $task = $routeRecord instanceof Task
                ? $routeRecord
                : Task::withoutGlobalScopes()->find($routeRecord);

            $planId = request()->query('return_to_plan') ?? $task?->wedding_plan_id;
            $this->taskCreateReturnUrl = $planId
                ? WeddingPlanResource::getUrl('edit', ['record' => $planId])
                : TaskResource::getUrl('index');
            $this->canDeleteTask = $task ? TaskResource::canDelete($task) : false;

            return;
        }

        if ($this->isUsersListPage) {
            $this->title = (string) UserResource::getNavigationLabel();
            $this->giftItemsIndexUrl = '';
            $this->giftItemsCreateUrl = '';
            $this->transactionsIndexUrl = '';
            $this->transactionsExportUrl = '';
            $this->siteLayoutsIndexUrl = '';
            $this->siteEditorUrl = '';
            $this->canCreateSite = false;
            $this->siteTemplatesIndexUrl = '';
            $this->siteTemplatesCreateUrl = '';
            $this->canCreateTemplate = false;
            $this->weddingPlansIndexUrl = '';
            $this->weddingPlansCreateUrl = '';
            $this->canCreateWeddingPlan = false;
            $this->usersIndexUrl = UserResource::getUrl('index');
            $this->usersCreateUrl = UserResource::getUrl('create');
            [$this->canCreateUser, $this->usersCreateLabel] = $this->getUserCreateActionConfig();

            return;
        }

        if ($this->isUsersCreatePage) {
            $this->title = (string) UserResource::getNavigationLabel();
            $this->giftItemsIndexUrl = '';
            $this->giftItemsCreateUrl = '';
            $this->transactionsIndexUrl = '';
            $this->transactionsExportUrl = '';
            $this->siteLayoutsIndexUrl = '';
            $this->siteEditorUrl = '';
            $this->canCreateSite = false;
            $this->siteTemplatesIndexUrl = '';
            $this->siteTemplatesCreateUrl = '';
            $this->canCreateTemplate = false;
            $this->weddingPlansIndexUrl = '';
            $this->weddingPlansCreateUrl = '';
            $this->canCreateWeddingPlan = false;
            $this->usersIndexUrl = UserResource::getUrl('index');
            $this->usersCreateUrl = '';

            return;
        }

        if ($this->isUsersEditPage) {
            $this->title = (string) UserResource::getNavigationLabel();
            $this->giftItemsIndexUrl = '';
            $this->giftItemsCreateUrl = '';
            $this->transactionsIndexUrl = '';
            $this->transactionsExportUrl = '';
            $this->siteLayoutsIndexUrl = '';
            $this->siteEditorUrl = '';
            $this->canCreateSite = false;
            $this->siteTemplatesIndexUrl = '';
            $this->siteTemplatesCreateUrl = '';
            $this->canCreateTemplate = false;
            $this->weddingPlansIndexUrl = '';
            $this->weddingPlansCreateUrl = '';
            $this->canCreateWeddingPlan = false;
            $this->usersIndexUrl = UserResource::getUrl('index');
            $this->usersCreateUrl = '';

            return;
        }

        if ($this->isWeddingVendorsListPage) {
            $this->title = (string) WeddingVendorResource::getNavigationLabel();
            $this->giftItemsIndexUrl = '';
            $this->giftItemsCreateUrl = '';
            $this->transactionsIndexUrl = '';
            $this->transactionsExportUrl = '';
            $this->siteLayoutsIndexUrl = '';
            $this->siteEditorUrl = '';
            $this->canCreateSite = false;
            $this->siteTemplatesIndexUrl = '';
            $this->siteTemplatesCreateUrl = '';
            $this->canCreateTemplate = false;
            $this->weddingPlansIndexUrl = '';
            $this->weddingPlansCreateUrl = '';
            $this->canCreateWeddingPlan = false;
            $this->usersIndexUrl = '';
            $this->usersCreateUrl = '';
            $this->canCreateUser = false;
            $this->usersCreateLabel = 'Novo Usuário';
            $this->weddingVendorsIndexUrl = WeddingVendorResource::getUrl('index');
            $this->weddingVendorsCreateUrl = WeddingVendorResource::getUrl('create');
            $this->canCreateWeddingVendor = WeddingVendorResource::canCreate();

            return;
        }

        if ($this->isWeddingVendorsCreatePage) {
            $this->title = (string) WeddingVendorResource::getNavigationLabel();
            $this->giftItemsIndexUrl = '';
            $this->giftItemsCreateUrl = '';
            $this->transactionsIndexUrl = '';
            $this->transactionsExportUrl = '';
            $this->siteLayoutsIndexUrl = '';
            $this->siteEditorUrl = '';
            $this->canCreateSite = false;
            $this->siteTemplatesIndexUrl = '';
            $this->siteTemplatesCreateUrl = '';
            $this->canCreateTemplate = false;
            $this->weddingPlansIndexUrl = '';
            $this->weddingPlansCreateUrl = '';
            $this->canCreateWeddingPlan = false;
            $this->usersIndexUrl = '';
            $this->usersCreateUrl = '';
            $this->canCreateUser = false;
            $this->usersCreateLabel = 'Novo Usuário';
            $this->weddingVendorsIndexUrl = WeddingVendorResource::getUrl('index');
            $this->weddingVendorsCreateUrl = '';
            $this->canCreateWeddingVendor = false;

            return;
        }

        if ($this->isWeddingVendorsEditPage) {
            $this->title = (string) WeddingVendorResource::getNavigationLabel();
            $this->giftItemsIndexUrl = '';
            $this->giftItemsCreateUrl = '';
            $this->transactionsIndexUrl = '';
            $this->transactionsExportUrl = '';
            $this->siteLayoutsIndexUrl = '';
            $this->siteEditorUrl = '';
            $this->canCreateSite = false;
            $this->siteTemplatesIndexUrl = '';
            $this->siteTemplatesCreateUrl = '';
            $this->canCreateTemplate = false;
            $this->weddingPlansIndexUrl = '';
            $this->weddingPlansCreateUrl = '';
            $this->canCreateWeddingPlan = false;
            $this->usersIndexUrl = '';
            $this->usersCreateUrl = '';
            $this->canCreateUser = false;
            $this->usersCreateLabel = 'Novo Usuário';
            $this->weddingVendorsIndexUrl = WeddingVendorResource::getUrl('index');
            $this->weddingVendorsCreateUrl = '';
            $this->canCreateWeddingVendor = false;

            $routeRecord = request()->route('record');
            $vendor = $routeRecord instanceof WeddingVendor
                ? $routeRecord
                : WeddingVendor::withoutGlobalScopes()->find($routeRecord);
            $this->canDeleteWeddingVendor = $vendor ? WeddingVendorResource::canDelete($vendor) : false;

            return;
        }

        if ($this->isWeddingEventsListPage) {
            $this->title = (string) WeddingEventResource::getNavigationLabel();
            $this->weddingEventsIndexUrl = WeddingEventResource::getUrl('index');
            $this->weddingEventsCreateUrl = WeddingEventResource::getUrl('create');
            $this->canCreateWeddingEvent = WeddingEventResource::canCreate();

            return;
        }

        if ($this->isWeddingEventsCreatePage) {
            $this->title = (string) WeddingEventResource::getNavigationLabel();
            $this->weddingEventsIndexUrl = WeddingEventResource::getUrl('index');

            return;
        }

        if ($this->isWeddingEventsEditPage) {
            $this->title = (string) WeddingEventResource::getNavigationLabel();
            $this->weddingEventsIndexUrl = WeddingEventResource::getUrl('index');

            $routeRecord = request()->route('record');
            $event = $routeRecord instanceof WeddingEvent
                ? $routeRecord
                : WeddingEvent::withoutGlobalScopes()->find($routeRecord);
            $this->canDeleteWeddingEvent = $event ? WeddingEventResource::canDelete($event) : false;

            return;
        }

        if ($this->isWeddingGuestsListPage) {
            $this->title = (string) WeddingGuestResource::getNavigationLabel();
            $this->weddingGuestsIndexUrl = WeddingGuestResource::getUrl('index');
            $this->weddingGuestsCreateUrl = WeddingGuestResource::getUrl('create');
            $this->canCreateWeddingGuest = WeddingGuestResource::canCreate();

            return;
        }

        if ($this->isWeddingGuestsCreatePage) {
            $this->title = (string) WeddingGuestResource::getNavigationLabel();
            $this->weddingGuestsIndexUrl = WeddingGuestResource::getUrl('index');

            return;
        }

        if ($this->isWeddingGuestsEditPage) {
            $this->title = (string) WeddingGuestResource::getNavigationLabel();
            $this->weddingGuestsIndexUrl = WeddingGuestResource::getUrl('index');

            $routeRecord = request()->route('record');
            $guest = $routeRecord instanceof WeddingGuest
                ? $routeRecord
                : WeddingGuest::withoutGlobalScopes()->find($routeRecord);
            $this->canDeleteWeddingGuest = $guest ? WeddingGuestResource::canDelete($guest) : false;

            return;
        }

        if ($this->isWeddingInvitesListPage) {
            $this->title = (string) WeddingInviteResource::getNavigationLabel();
            $this->weddingInvitesIndexUrl = WeddingInviteResource::getUrl('index');
            $this->weddingInvitesCreateUrl = WeddingInviteResource::getUrl('create');
            $this->canCreateWeddingInvite = WeddingInviteResource::canCreate();

            return;
        }

        if ($this->isWeddingInvitesCreatePage) {
            $this->title = (string) WeddingInviteResource::getNavigationLabel();
            $this->weddingInvitesIndexUrl = WeddingInviteResource::getUrl('index');

            return;
        }

        if ($this->isWeddingInvitesEditPage) {
            $this->title = (string) WeddingInviteResource::getNavigationLabel();
            $this->weddingInvitesIndexUrl = WeddingInviteResource::getUrl('index');

            $routeRecord = request()->route('record');
            $invite = $routeRecord instanceof WeddingInvite
                ? $routeRecord
                : WeddingInvite::withoutGlobalScopes()->find($routeRecord);
            $this->canDeleteWeddingInvite = $invite ? WeddingInviteResource::canDelete($invite) : false;

            return;
        }

        $this->title = $this->getCurrentPageTitle();
        $this->giftItemsIndexUrl = '';
        $this->giftItemsCreateUrl = '';
        $this->transactionsIndexUrl = '';
        $this->transactionsExportUrl = '';
        $this->siteLayoutsIndexUrl = '';
        $this->siteEditorUrl = '';
        $this->canCreateSite = false;
        $this->siteTemplatesIndexUrl = '';
        $this->siteTemplatesCreateUrl = '';
        $this->canCreateTemplate = false;
        $this->weddingPlansIndexUrl = '';
        $this->weddingPlansCreateUrl = '';
        $this->canCreateWeddingPlan = false;
        $this->usersIndexUrl = '';
        $this->usersCreateUrl = '';
        $this->canCreateUser = false;
        $this->usersCreateLabel = 'Novo Usuário';
        $this->weddingVendorsIndexUrl = '';
        $this->weddingVendorsCreateUrl = '';
        $this->canCreateWeddingVendor = false;
        $this->canDeleteWeddingVendor = false;
        $this->weddingEventsIndexUrl = '';
        $this->weddingEventsCreateUrl = '';
        $this->canCreateWeddingEvent = false;
        $this->canDeleteWeddingEvent = false;
        $this->weddingGuestsIndexUrl = '';
        $this->weddingGuestsCreateUrl = '';
        $this->canCreateWeddingGuest = false;
        $this->canDeleteWeddingGuest = false;
        $this->weddingInvitesIndexUrl = '';
        $this->weddingInvitesCreateUrl = '';
        $this->canCreateWeddingInvite = false;
        $this->canDeleteWeddingInvite = false;
        $this->canDeleteGiftItem = false;
    }

    public function changeGiftRegistryMode(string $mode): void
    {
        if (! in_array($mode, [GiftRegistryModeService::MODE_QUANTITY, GiftRegistryModeService::MODE_QUOTA], true)) {
            return;
        }

        $weddingId = auth()->user()?->currentWedding?->id;

        if (! $weddingId) {
            Notification::make()
                ->title('Nenhum casamento selecionado')
                ->danger()
                ->body('Selecione um casamento para alterar a configuração da lista de presentes.')
                ->send();

            return;
        }

        app(GiftRegistryModeService::class)->changeMode($weddingId, $mode);
        $this->giftRegistryMode = $mode;

        Notification::make()
            ->title('Configuração atualizada')
            ->success()
            ->body($mode === GiftRegistryModeService::MODE_QUOTA
                ? 'Modo Quota ativado com sucesso.'
                : 'Modo Quantidade ativado com sucesso.')
            ->send();

        $this->redirect($this->giftItemsIndexUrl ?: GiftItemResource::getUrl('index'));
    }

    public function getGiftModeLabelProperty(): string
    {
        return $this->giftRegistryMode === GiftRegistryModeService::MODE_QUOTA
            ? 'Modo: Quota'
            : 'Modo: Quantidade';
    }

    public function createAnotherGiftFromTopbar(): void
    {
        $this->dispatch('topbar-gift-create-another');
    }

    public function restoreGiftFromTopbar(): void
    {
        $this->dispatch('topbar-gift-restore');
    }

    public function deleteGiftFromTopbar(): void
    {
        $this->dispatch('topbar-gift-delete');
    }

    public function createAnotherWeddingPlanFromTopbar(): void
    {
        $this->dispatch('topbar-wedding-plan-create-another');
    }

    public function createAnotherTaskFromTopbar(): void
    {
        $this->dispatch('topbar-task-create-another');
    }

    public function createTaskFromTopbar(): void
    {
        $this->dispatch('topbar-task-submit');
    }

    public function returnToPlanFromTopbar(): void
    {
        $this->dispatch('topbar-task-return');
    }

    public function saveTaskFromTopbar(): void
    {
        $this->dispatch('topbar-task-save');
    }

    public function deleteTaskFromTopbar(): void
    {
        $this->dispatch('topbar-task-delete');
    }

    public function showWeddingPlanTimelineFromTopbar(): void
    {
        $this->isWeddingPlanTimelineView = true;
        $this->dispatch('topbar-wedding-plan-show-timeline');
    }

    public function showWeddingPlanTableFromTopbar(): void
    {
        $this->isWeddingPlanTimelineView = false;
        $this->dispatch('topbar-wedding-plan-show-table');
    }

    #[On('topbar-wedding-plan-show-timeline')]
    public function markWeddingPlanTimelineView(): void
    {
        $this->isWeddingPlanTimelineView = true;
    }

    #[On('topbar-wedding-plan-show-table')]
    public function markWeddingPlanTableView(): void
    {
        $this->isWeddingPlanTimelineView = false;
    }

    public function archiveWeddingPlanFromTopbar(): void
    {
        $this->dispatch('topbar-wedding-plan-archive');
    }

    public function unarchiveWeddingPlanFromTopbar(): void
    {
        $this->dispatch('topbar-wedding-plan-unarchive');
    }

    public function createAnotherUserFromTopbar(): void
    {
        $this->dispatch('topbar-user-create-another');
    }

    public function deleteUserFromTopbar(): void
    {
        $this->dispatch('topbar-user-delete');
    }

    public function createAnotherWeddingVendorFromTopbar(): void
    {
        $this->dispatch('topbar-wedding-vendor-create-another');
    }

    public function deleteWeddingVendorFromTopbar(): void
    {
        $this->dispatch('topbar-wedding-vendor-delete');
    }

    public function createAnotherWeddingEventFromTopbar(): void
    {
        $this->dispatch('topbar-wedding-event-create-another');
    }

    public function deleteWeddingEventFromTopbar(): void
    {
        $this->dispatch('topbar-wedding-event-delete');
    }

    public function createAnotherWeddingGuestFromTopbar(): void
    {
        $this->dispatch('topbar-wedding-guest-create-another');
    }

    public function deleteWeddingGuestFromTopbar(): void
    {
        $this->dispatch('topbar-wedding-guest-delete');
    }

    public function createAnotherWeddingInviteFromTopbar(): void
    {
        $this->dispatch('topbar-wedding-invite-create-another');
    }

    public function deleteWeddingInviteFromTopbar(): void
    {
        $this->dispatch('topbar-wedding-invite-delete');
    }

    protected function getCurrentPageTitle(): string
    {
        $routeName = request()->route()?->getName() ?? '';
        
        // Verifica se é uma página de resource pelo nome da rota
        // Formato: filament.admin.resources.{resource}.{page}
        if (preg_match('/filament\.admin\.resources\.([^.]+)\.(\w+)/', $routeName, $matches)) {
            $resourceSlug = $matches[1];
            $pageName = $matches[2];
            
            // Encontra o resource correspondente
            foreach (Filament::getResources() as $resource) {
                if ($resource::getSlug() === $resourceSlug) {
                    return match ($pageName) {
                        'index' => $resource::getPluralModelLabel(),
                        'create' => 'Criar ' . $resource::getModelLabel(),
                        'edit' => 'Editar ' . $resource::getModelLabel(),
                        'view' => 'Visualizar ' . $resource::getModelLabel(),
                        default => $resource::getPluralModelLabel(),
                    };
                }
            }
        }
        
        // Verifica se é uma página customizada
        // Formato: filament.admin.pages.{page}
        if (preg_match('/filament\.admin\.pages\.(.+)/', $routeName, $matches)) {
            $pageSlug = $matches[1];
            
            foreach (Filament::getPages() as $page) {
                if ($page::getSlug() === $pageSlug || $page::getRouteName() === $routeName) {
                    return $page::getNavigationLabel();
                }
            }
        }
        
        return '';
    }

    protected function getCurrentRegistryMode(): string
    {
        $weddingId = auth()->user()?->currentWedding?->id;

        if (! $weddingId) {
            return GiftRegistryModeService::MODE_QUANTITY;
        }

        return GiftRegistryConfig::withoutGlobalScopes()
                ->where('wedding_id', $weddingId)
                ->value('registry_mode')
            ?? GiftRegistryModeService::MODE_QUANTITY;
    }

    protected function canCreateSiteForCurrentWedding(): bool
    {
        $user = auth()->user();
        $weddingId = $user?->current_wedding_id ?? session('filament_wedding_id');

        if (! $weddingId) {
            return false;
        }

        return ! SiteLayout::query()
            ->where('wedding_id', $weddingId)
            ->exists();
    }

    protected function getUserCreateActionConfig(): array
    {
        $user = auth()->user();
        $weddingId = $user?->current_wedding_id ?? session('filament_wedding_id');
        $wedding = $weddingId ? Wedding::find($weddingId) : null;

        if (! $user || ! $wedding) {
            return [false, 'Novo Usuário'];
        }

        if ($user->isAdmin() || $user->isCoupleIn($wedding)) {
            return [true, 'Novo Usuário'];
        }

        if ($user->isOrganizerIn($wedding) && $user->hasPermissionIn($wedding, 'users')) {
            return [true, 'Novo Convidado'];
        }

        return [false, 'Novo Usuário'];
    }

    protected function resolveOrganizerWeddingContext(): array
    {
        $user = auth()->user();

        if (! $user || $user->role !== 'organizer') {
            return [false, ''];
        }

        $weddingId = session('filament_wedding_id') ?? $user->current_wedding_id;

        if (! $weddingId) {
            return [true, 'Casamento não selecionado'];
        }

        $wedding = $user->weddings()
            ->where('weddings.id', $weddingId)
            ->first();

        if (! $wedding) {
            return [true, 'Casamento não encontrado'];
        }

        return [true, $wedding->title];
    }

    public function render()
    {
        return view('livewire.topbar-title');
    }
}
