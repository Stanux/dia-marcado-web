<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Wedding;
use App\Services\PermissionService;
use App\Services\UserManagementService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $creator = auth()->user();
        $weddingId = $creator->current_wedding_id ?? session('filament_wedding_id');
        $wedding = Wedding::findOrFail($weddingId);

        $userManagementService = new UserManagementService(new PermissionService());

        $pivotRole = $data['pivot_role'] ?? 'guest';
        $permissions = $data['permissions'] ?? [];

        // Validate that only valid roles can be created
        if (!in_array($pivotRole, ['couple', 'organizer', 'guest'])) {
            $pivotRole = 'guest';
        }

        unset($data['pivot_role'], $data['permissions']);

        if ($pivotRole === 'couple') {
            return $userManagementService->createCouple($creator, $wedding, $data);
        }

        if ($pivotRole === 'organizer') {
            return $userManagementService->createOrganizer($creator, $wedding, $data, $permissions);
        }

        return $userManagementService->createGuest($creator, $wedding, $data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
