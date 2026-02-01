<?php

namespace App\Filament\Resources;

use App\Models\Wedding;
use App\Services\PermissionService;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Resource class for FilamentPHP that integrates with WeddingScope.
 * 
 * All resources that manage wedding-scoped data should extend this class.
 * It automatically:
 * - Filters queries by the current wedding context
 * - Verifies user permissions before allowing access
 * - Injects wedding_id when creating new records
 */
abstract class WeddingScopedResource extends Resource
{
    /**
     * The module this resource belongs to for permission checking.
     * Override this in child classes to specify the module.
     */
    protected static ?string $module = null;

    /**
     * Get the Eloquent query for the resource.
     * The WeddingScope global scope handles filtering automatically.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    /**
     * Determine if the user can access this resource.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Admin always has access
        if ($user->isAdmin()) {
            return true;
        }

        // Check module permission if specified
        if (static::$module) {
            $permissionService = app(PermissionService::class);
            $wedding = static::getCurrentWedding();

            if (!$wedding) {
                return false;
            }

            return $permissionService->canAccess($user, static::$module, $wedding);
        }

        return true;
    }

    /**
     * Determine if the user can view any records.
     */
    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    /**
     * Determine if the user can view a specific record.
     */
    public static function canView(Model $record): bool
    {
        return static::canAccess() && static::belongsToCurrentWedding($record);
    }

    /**
     * Determine if the user can create records.
     */
    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    /**
     * Determine if the user can edit a specific record.
     */
    public static function canEdit(Model $record): bool
    {
        return static::canAccess() && static::belongsToCurrentWedding($record);
    }

    /**
     * Determine if the user can delete a specific record.
     */
    public static function canDelete(Model $record): bool
    {
        return static::canAccess() && static::belongsToCurrentWedding($record);
    }

    /**
     * Get the current wedding from the user context.
     */
    protected static function getCurrentWedding(): ?Wedding
    {
        $user = auth()->user();

        if (!$user) {
            return null;
        }

        $weddingId = $user->current_wedding_id ?? session('filament_wedding_id');

        if (!$weddingId) {
            return null;
        }

        return Wedding::find($weddingId);
    }

    /**
     * Check if a record belongs to the current wedding context.
     */
    protected static function belongsToCurrentWedding(Model $record): bool
    {
        $user = auth()->user();

        // Admin can access all records
        if ($user && $user->isAdmin()) {
            return true;
        }

        // Check if record has wedding_id and matches current context
        if (!property_exists($record, 'wedding_id') && !isset($record->wedding_id)) {
            return true;
        }

        $currentWeddingId = $user?->current_wedding_id ?? session('filament_wedding_id');

        return $record->wedding_id === $currentWeddingId;
    }

    /**
     * Mutate form data before creating a record.
     * Automatically injects wedding_id if not set.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!isset($data['wedding_id'])) {
            $user = auth()->user();
            $data['wedding_id'] = $user?->current_wedding_id ?? session('filament_wedding_id');
        }

        return $data;
    }
}
