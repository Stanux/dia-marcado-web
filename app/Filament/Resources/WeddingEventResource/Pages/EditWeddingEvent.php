<?php

namespace App\Filament\Resources\WeddingEventResource\Pages;

use App\Filament\Resources\WeddingEventResource;
use Filament\Actions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

class EditWeddingEvent extends EditRecord
{
    protected static string $resource = WeddingEventResource::class;

    public function getHeading(): string
    {
        return '';
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
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

    #[On('topbar-wedding-event-delete')]
    public function openDeleteModalFromTopbar(): void
    {
        abort_unless(static::getResource()::canDelete($this->getRecord()), 403);

        $this->mountAction('delete');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function configureDeleteAction(DeleteAction $action): void
    {
        parent::configureDeleteAction($action);

        $action->successRedirectUrl(fn (): string => $this->getResource()::getUrl('index'));
    }

    protected function deleteAction(): DeleteAction
    {
        return Actions\DeleteAction::make();
    }
}
