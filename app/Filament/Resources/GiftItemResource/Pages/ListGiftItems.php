<?php

namespace App\Filament\Resources\GiftItemResource\Pages;

use App\Filament\Resources\GiftItemResource;
use App\Models\GiftRegistryConfig;
use App\Services\GiftRegistryModeService;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListGiftItems extends ListRecords
{
    protected static string $resource = GiftItemResource::class;

    public function getTitle(): string
    {
        return 'Presentes';
    }

    public function getHeading(): string
    {
        return 'Presentes';
    }

    protected function getHeaderActions(): array
    {
        $modeLabel = $this->getCurrentRegistryMode() === GiftRegistryModeService::MODE_QUOTA
            ? 'Configuração: Quota'
            : 'Configuração: Quantidade';

        return [
            ActionGroup::make([
                Actions\Action::make('mode_quantity')
                    ->label('Quantidade')
                    ->icon('heroicon-o-list-bullet')
                    ->color($this->getCurrentRegistryMode() === GiftRegistryModeService::MODE_QUANTITY ? 'primary' : 'gray')
                    ->action(fn () => $this->updateRegistryMode(GiftRegistryModeService::MODE_QUANTITY)),
                Actions\Action::make('mode_quota')
                    ->label('Quota')
                    ->icon('heroicon-o-chart-bar')
                    ->color($this->getCurrentRegistryMode() === GiftRegistryModeService::MODE_QUOTA ? 'primary' : 'gray')
                    ->action(fn () => $this->updateRegistryMode(GiftRegistryModeService::MODE_QUOTA)),
            ])
                ->label($modeLabel)
                ->icon('heroicon-o-adjustments-horizontal')
                ->button(),
            Actions\CreateAction::make(),
        ];
    }

    private function updateRegistryMode(string $mode): void
    {
        $weddingId = auth()->user()?->currentWedding?->id;

        if (!$weddingId) {
            Notification::make()
                ->title('Nenhum casamento selecionado')
                ->danger()
                ->body('Selecione um casamento para alterar a configuração da lista de presentes.')
                ->send();

            return;
        }

        app(GiftRegistryModeService::class)->changeMode($weddingId, $mode);

        Notification::make()
            ->title('Configuração atualizada')
            ->success()
            ->body($mode === GiftRegistryModeService::MODE_QUOTA
                ? 'Modo Quota ativado com sucesso.'
                : 'Modo Quantidade ativado com sucesso.')
            ->send();

        $this->redirect(static::getResource()::getUrl('index'));
    }

    private function getCurrentRegistryMode(): string
    {
        $weddingId = auth()->user()?->currentWedding?->id;

        if (!$weddingId) {
            return GiftRegistryModeService::MODE_QUANTITY;
        }

        return GiftRegistryConfig::withoutGlobalScopes()
                ->where('wedding_id', $weddingId)
                ->value('registry_mode')
            ?? GiftRegistryModeService::MODE_QUANTITY;
    }
}
