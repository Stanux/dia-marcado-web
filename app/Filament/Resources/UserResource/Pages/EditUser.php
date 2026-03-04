<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Wedding;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

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

    #[On('topbar-user-delete')]
    public function deleteFromTopbar(): void
    {
        abort_unless(static::getResource()::canDelete($this->getRecord()), 403);

        $this->getRecord()->delete();

        Notification::make()
            ->success()
            ->title('Usuário removido com sucesso.')
            ->send();

        $this->redirect($this->getResource()::getUrl('index'));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $wedding = UserResource::getCurrentWeddingFromContext();

        if (!$wedding instanceof Wedding) {
            return $data;
        }

        $record = $this->getRecord();
        $pivotRole = $record->roleIn($wedding) ?? 'guest';

        $data['pivot_role'] = $pivotRole;
        $data['permissions'] = $pivotRole === 'organizer'
            ? UserResource::sanitizePermissionsForRole('organizer', $record->permissionsIn($wedding))
            : UserResource::getDefaultPermissionsForRole($pivotRole);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $wedding = UserResource::getCurrentWeddingFromContext();

        if (!$wedding instanceof Wedding) {
            abort(422, 'Contexto de casamento não encontrado para atualizar permissões.');
        }

        $availableRoles = array_keys(UserResource::getPivotRoleOptionsForCurrentUser());
        $pivotRole = $data['pivot_role'] ?? ($record->roleIn($wedding) ?? 'guest');

        if (!in_array($pivotRole, $availableRoles, true)) {
            $pivotRole = $record->roleIn($wedding) ?? 'guest';
        }

        $permissions = UserResource::sanitizePermissionsForRole(
            $pivotRole,
            is_array($data['permissions'] ?? null) ? $data['permissions'] : []
        );

        unset($data['pivot_role'], $data['permissions']);

        $record->update([
            ...$data,
            'role' => $pivotRole,
        ]);

        $wedding->users()->updateExistingPivot($record->id, [
            'role' => $pivotRole,
            'permissions' => $permissions,
        ]);

        return $record;
    }
}
