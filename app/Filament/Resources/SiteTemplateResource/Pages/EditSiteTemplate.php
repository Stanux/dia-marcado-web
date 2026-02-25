<?php

namespace App\Filament\Resources\SiteTemplateResource\Pages;

use App\Filament\Resources\SiteTemplateResource;
use App\Services\Site\TemplateWorkspaceService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSiteTemplate extends EditRecord
{
    protected static string $resource = SiteTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('visual_editor')
                ->label('Editar no Editor Visual')
                ->icon('heroicon-o-pencil-square')
                ->url(fn (): string => SiteTemplateResource::templateEditorUrl($this->record)),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        app(TemplateWorkspaceService::class)->syncEditorSiteFromTemplate($this->record);
    }
}
