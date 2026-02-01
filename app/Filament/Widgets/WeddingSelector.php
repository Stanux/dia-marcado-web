<?php

namespace App\Filament\Widgets;

use App\Models\Wedding;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

/**
 * Widget that allows users to select which wedding they want to manage.
 * 
 * This widget is displayed in the Filament dashboard and allows
 * non-admin users to switch between weddings they have access to.
 */
class WeddingSelector extends Widget
{
    protected static string $view = 'filament.widgets.wedding-selector';

    protected int | string | array $columnSpan = 'full';

    public ?string $selectedWeddingId = null;

    public function mount(): void
    {
        $this->selectedWeddingId = session('filament_wedding_id') 
            ?? auth()->user()?->current_wedding_id;
    }

    public function getWeddings(): array
    {
        $user = auth()->user();

        if (!$user) {
            return [];
        }

        if ($user->isAdmin()) {
            return Wedding::all()->pluck('title', 'id')->toArray();
        }

        return $user->weddings()->pluck('title', 'weddings.id')->toArray();
    }

    public function selectWedding(string $weddingId): void
    {
        $user = auth()->user();

        if (!$user) {
            return;
        }

        // Verify user has access to this wedding
        if (!$user->isAdmin()) {
            $hasAccess = $user->weddings()->where('wedding_id', $weddingId)->exists();
            
            if (!$hasAccess) {
                return;
            }
        }

        // Update session and user context
        session(['filament_wedding_id' => $weddingId]);
        $user->current_wedding_id = $weddingId;

        $this->selectedWeddingId = $weddingId;

        // Redirect to refresh the page with new context
        $this->redirect(request()->header('Referer') ?? route('filament.admin.pages.dashboard'));
    }

    public function getCurrentWedding(): ?Wedding
    {
        if (!$this->selectedWeddingId) {
            return null;
        }

        return Wedding::find($this->selectedWeddingId);
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Show for all authenticated users
        return true;
    }
}
