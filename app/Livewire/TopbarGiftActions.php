<?php

namespace App\Livewire;

use App\Filament\Resources\GiftItemResource;
use App\Models\GiftRegistryConfig;
use App\Services\GiftRegistryModeService;
use Filament\Notifications\Notification;
use Livewire\Component;
use Livewire\Attributes\On;

class TopbarGiftActions extends Component
{
    public string $giftRegistryMode = GiftRegistryModeService::MODE_QUANTITY;
    public string $giftItemsIndexUrl = '';
    public string $giftItemsCreateUrl = '';

    public function mount(): void
    {
        $this->refreshState();
    }

    #[On('page-title-updated')]
    public function refreshState(): void
    {
        $this->giftRegistryMode = $this->getCurrentRegistryMode();
        $this->giftItemsIndexUrl = GiftItemResource::getUrl('index');
        $this->giftItemsCreateUrl = GiftItemResource::getUrl('create');
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

    public function render()
    {
        return view('livewire.topbar-gift-actions');
    }
}

