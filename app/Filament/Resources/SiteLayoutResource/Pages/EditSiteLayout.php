<?php

namespace App\Filament\Resources\SiteLayoutResource\Pages;

use App\Filament\Resources\SiteLayoutResource;
use App\Services\Site\AccessTokenService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSiteLayout extends EditRecord
{
    protected static string $resource = SiteLayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_site')
                ->label('Ver Site')
                ->icon('heroicon-o-eye')
                ->url(fn () => route('public.site.show', ['slug' => $this->record->slug]))
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->is_published),

            Actions\Action::make('edit_visual')
                ->label('Editor Visual')
                ->icon('heroicon-o-pencil-square')
                ->url(fn () => route('filament.admin.pages.site-editor', ['site' => $this->record->id]))
                ->color('primary'),

            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle password update
        if (isset($data['access_token']) && filled($data['access_token'])) {
            $accessTokenService = app(AccessTokenService::class);
            $accessTokenService->setToken($this->record, $data['access_token']);
            unset($data['access_token']);
        } elseif (array_key_exists('access_token', $data) && empty($data['access_token'])) {
            // If password field is empty, remove the token
            $accessTokenService = app(AccessTokenService::class);
            $accessTokenService->removeToken($this->record);
            unset($data['access_token']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
