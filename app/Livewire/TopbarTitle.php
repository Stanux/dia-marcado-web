<?php

namespace App\Livewire;

use App\Filament\Pages\WeddingSettings;
use App\Filament\Resources\GiftItemResource;
use App\Filament\Resources\SiteLayoutResource;
use App\Filament\Resources\SiteTemplateResource;
use App\Filament\Resources\TransactionResource;
use App\Filament\Resources\WeddingPlanResource;
use App\Models\GiftRegistryConfig;
use App\Models\SiteLayout;
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
    public bool $isWeddingSettingsPage = false;
    public bool $isTransactionsListPage = false;
    public bool $isSiteLayoutsListPage = false;
    public bool $isSiteTemplatesListPage = false;
    public bool $isWeddingPlansListPage = false;
    public string $giftRegistryMode = GiftRegistryModeService::MODE_QUANTITY;
    public string $giftItemsIndexUrl = '';
    public string $giftItemsCreateUrl = '';
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
    public bool $canCreateWeddingPlan = false;

    public function mount(): void
    {
        $this->updateTitle();
    }

    #[On('page-title-updated')]
    public function updateTitle(): void
    {
        $routeName = request()->route()?->getName() ?? '';
        $this->isGiftItemsListPage = $routeName === 'filament.admin.resources.gift-items.index';
        $this->isGiftItemsCreatePage = $routeName === 'filament.admin.resources.gift-items.create';
        $this->isWeddingSettingsPage = $routeName === 'filament.admin.pages.wedding-settings';
        $this->isTransactionsListPage = $routeName === 'filament.admin.resources.transactions.index';
        $this->isSiteLayoutsListPage = $routeName === 'filament.admin.resources.site-layouts.index';
        $this->isSiteTemplatesListPage = $routeName === 'filament.admin.resources.site-templates.index';
        $this->isWeddingPlansListPage = $routeName === 'filament.admin.resources.wedding-plans.index';

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
            ? 'Configuração: Quota'
            : 'Configuração: Quantidade';
    }

    public function createAnotherGiftFromTopbar(): void
    {
        $this->dispatch('topbar-gift-create-another');
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

    public function render()
    {
        return view('livewire.topbar-title');
    }
}
