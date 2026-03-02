<?php

namespace App\Filament\Resources\WeddingEventResource\Pages;

use App\Filament\Resources\WeddingEventResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

class CreateWeddingEvent extends CreateRecord
{
    protected static string $resource = WeddingEventResource::class;

    public function getHeading(): string
    {
        return '';
    }

    #[On('topbar-wedding-event-create-another')]
    public function createAnotherFromTopbar(): void
    {
        $this->create(another: true);
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $eventDateTime = $data['event_datetime'] ?? null;

        if (filled($eventDateTime)) {
            $parsedDateTime = Carbon::parse($eventDateTime);
            $data['event_date'] = $parsedDateTime->format('Y-m-d');
            $data['event_time'] = $parsedDateTime->format('H:i:s');
        } else {
            $data['event_date'] = null;
            $data['event_time'] = null;
        }

        unset($data['event_datetime']);

        return $data;
    }
}
