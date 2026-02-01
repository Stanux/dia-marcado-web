<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Models\Wedding;
use App\Services\PermissionManagementService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class ManagePermissions extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = UserResource::class;

    protected static string $view = 'filament.resources.user-resource.pages.manage-permissions';

    public ?User $record = null;

    public ?array $data = [];

    public function mount(User $record): void
    {
        $this->record = $record;

        $user = auth()->user();
        $weddingId = $user->current_wedding_id ?? session('filament_wedding_id');
        $wedding = Wedding::find($weddingId);

        // Check if user can manage permissions
        if (!$user->isAdmin() && !$user->isCoupleIn($wedding)) {
            abort(403, 'Você não tem permissão para gerenciar permissões.');
        }

        // Check if record is an organizer in this wedding
        if (!$record->isOrganizerIn($wedding)) {
            abort(404, 'Usuário não é um organizador deste casamento.');
        }

        $this->form->fill([
            'permissions' => $record->permissionsIn($wedding),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Permissões de ' . $this->record->name)
                    ->description('Selecione os módulos que este organizador pode acessar.')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('Módulos')
                            ->options(PermissionManagementService::AVAILABLE_MODULES)
                            ->columns(2),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $formData = $this->form->getState();
        
        $user = auth()->user();
        $weddingId = $user->current_wedding_id ?? session('filament_wedding_id');
        $wedding = Wedding::findOrFail($weddingId);

        $permissionService = new PermissionManagementService();
        $permissionService->updateOrganizerPermissions(
            $user,
            $wedding,
            $this->record,
            $formData['permissions'] ?? []
        );

        Notification::make()
            ->title('Permissões atualizadas')
            ->success()
            ->send();

        $this->redirect(UserResource::getUrl('index'));
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return 'Gerenciar Permissões - ' . $this->record->name;
    }
}
