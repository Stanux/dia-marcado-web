<?php

namespace App\Filament\Resources\SiteTemplateResource\Pages;

use App\Filament\Resources\SiteTemplateResource;
use App\Services\Site\SiteContentSchema;
use Filament\Resources\Pages\CreateRecord;

class CreateSiteTemplate extends CreateRecord
{
    protected static string $resource = SiteTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!isset($data['content']) || !is_array($data['content']) || $data['content'] === []) {
            $data['content'] = SiteContentSchema::getDefaultContent();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if (!auth()->user()?->isAdmin()) {
            return;
        }

        $this->redirect(
            SiteTemplateResource::templateEditorUrl($this->record)
        );
    }

    protected function getRedirectUrl(): string
    {
        if (auth()->user()?->isAdmin()) {
            return SiteTemplateResource::templateEditorUrl($this->record);
        }

        return $this->getResource()::getUrl('index');
    }
}
