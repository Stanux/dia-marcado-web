<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $actions = [];

        $weddingId = $user?->current_wedding_id ?? session('filament_wedding_id');
        $wedding = $weddingId ? \App\Models\Wedding::find($weddingId) : null;

        if (!$user || !$wedding) {
            return $actions;
        }

        // Admin or Couple can create organizers and guests
        if ($user->isAdmin() || $user->isCoupleIn($wedding)) {
            $actions[] = Actions\CreateAction::make()
                ->label('Novo Usuário');
        }
        // Organizer with permission can only create guests
        elseif ($user->isOrganizerIn($wedding) && $user->hasPermissionIn($wedding, 'users')) {
            $actions[] = Actions\CreateAction::make()
                ->label('Novo Convidado');
        }

        return $actions;
    }
}
