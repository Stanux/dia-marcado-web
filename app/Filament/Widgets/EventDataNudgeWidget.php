<?php

namespace App\Filament\Widgets;

use App\Models\SiteLayout;
use App\Models\Wedding;
use App\Services\PermissionService;
use Filament\Widgets\Widget;

class EventDataNudgeWidget extends Widget
{
    protected static string $view = 'filament.widgets.event-data-nudge-widget';

    protected int | string | array $columnSpan = 'full';

    public function getCurrentWedding(): ?Wedding
    {
        $weddingId = session('filament_wedding_id') ?? auth()->user()?->current_wedding_id;

        if (!is_string($weddingId) || trim($weddingId) === '') {
            return null;
        }

        return Wedding::find($weddingId);
    }

    /**
     * @return array<int, string>
     */
    public function getPendingItems(): array
    {
        $wedding = $this->getCurrentWedding();
        if (!$wedding) {
            return [];
        }

        $settings = is_array($wedding->settings ?? null) ? $wedding->settings : [];
        $weddingTime = $this->normalizeWeddingTime($settings['wedding_time'] ?? null);
        $items = [];
        $canAccessEventData = $this->canAccessEventData();
        $canAccessUsers = $this->canAccessUsers();

        if ($canAccessEventData && $wedding->wedding_date === null) {
            $items[] = 'Definir a data do evento.';
        }

        if ($canAccessEventData && $weddingTime === null) {
            $items[] = 'Definir o horário do evento.';
        }

        $currentUser = auth()->user();
        $partnerLinked = $wedding->couple()
            ->when($currentUser, fn ($query) => $query->where('user_id', '!=', $currentUser->id))
            ->exists();

        if ($canAccessUsers && !$partnerLinked) {
            $items[] = 'Adicionar a conta do(a) parceiro(a) na tela de Usuários.';
        }

        return $items;
    }

    public function hasPendingItems(): bool
    {
        return $this->getPendingItems() !== [];
    }

    public function getWeddingSettingsUrl(): string
    {
        return route('filament.admin.pages.wedding-settings');
    }

    public function getPreviewEditorUrl(): ?string
    {
        $wedding = $this->getCurrentWedding();
        if (!$wedding) {
            return null;
        }

        $site = SiteLayout::query()
            ->where('wedding_id', $wedding->id)
            ->first();

        if (!$site) {
            return route('sites.create');
        }

        return route('sites.edit', ['site' => $site->id]);
    }

    public function getUsersCreateUrl(): string
    {
        return route('filament.admin.resources.users.create');
    }

    public function canAccessEventData(): bool
    {
        return $this->canAccessModule('event_data');
    }

    public function canAccessSiteEditor(): bool
    {
        return $this->canAccessModule('site_editor');
    }

    public function canAccessUsers(): bool
    {
        return $this->canAccessModule('users');
    }

    private function canAccessModule(string $module): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $wedding = $this->getCurrentWedding();
        if (! $wedding) {
            return false;
        }

        return app(PermissionService::class)->canAccess($user, $module, $wedding);
    }

    private function normalizeWeddingTime(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        if (preg_match('/^(?<hour>\d{2}):(?<minute>\d{2})(?::\d{2})?$/', $value, $matches) !== 1) {
            return null;
        }

        $hour = (int) $matches['hour'];
        $minute = (int) $matches['minute'];

        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return null;
        }

        return sprintf('%02d:%02d', $hour, $minute);
    }
}
