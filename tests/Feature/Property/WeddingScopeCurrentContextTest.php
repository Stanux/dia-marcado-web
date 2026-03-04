<?php

namespace Tests\Feature\Property;

use App\Models\SiteLayout;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeddingScopeCurrentContextTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function wedding_scope_uses_current_wedding_context_only(): void
    {
        $weddingA = Wedding::factory()->create();
        $weddingB = Wedding::factory()->create();

        SiteLayout::factory()->create(['wedding_id' => $weddingA->id]);
        SiteLayout::factory()->create(['wedding_id' => $weddingB->id]);

        $organizer = User::factory()
            ->organizer()
            ->create(['current_wedding_id' => $weddingA->id]);

        $organizer->weddings()->attach($weddingA->id, [
            'role' => 'organizer',
            'permissions' => ['site_editor'],
        ]);
        $organizer->weddings()->attach($weddingB->id, [
            'role' => 'organizer',
            'permissions' => ['site_editor'],
        ]);

        $this->actingAs($organizer);
        session()->forget('filament_wedding_id');

        $visibleWeddingIds = SiteLayout::query()
            ->pluck('wedding_id')
            ->unique()
            ->values()
            ->all();

        $this->assertSame([$weddingA->id], $visibleWeddingIds);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function wedding_scope_prioritizes_session_context_over_user_current_context(): void
    {
        $weddingA = Wedding::factory()->create();
        $weddingB = Wedding::factory()->create();

        SiteLayout::factory()->create(['wedding_id' => $weddingA->id]);
        SiteLayout::factory()->create(['wedding_id' => $weddingB->id]);

        $organizer = User::factory()
            ->organizer()
            ->create(['current_wedding_id' => $weddingA->id]);

        $organizer->weddings()->attach($weddingA->id, [
            'role' => 'organizer',
            'permissions' => ['site_editor'],
        ]);
        $organizer->weddings()->attach($weddingB->id, [
            'role' => 'organizer',
            'permissions' => ['site_editor'],
        ]);

        $this->actingAs($organizer);
        session(['filament_wedding_id' => $weddingB->id]);

        $visibleWeddingIds = SiteLayout::query()
            ->pluck('wedding_id')
            ->unique()
            ->values()
            ->all();

        $this->assertSame([$weddingB->id], $visibleWeddingIds);
    }
}
