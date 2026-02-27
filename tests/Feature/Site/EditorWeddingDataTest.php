<?php

namespace Tests\Feature\Site;

use App\Contracts\Site\SiteBuilderServiceInterface;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EditorWeddingDataTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function editor_receives_null_wedding_date_and_false_flag_when_date_is_not_defined(): void
    {
        $wedding = Wedding::factory()->create([
            'wedding_date' => null,
        ]);

        $user = User::factory()->create();
        $wedding->users()->attach($user->id, ['role' => 'couple']);
        $user->update(['current_wedding_id' => $wedding->id]);

        $site = app(SiteBuilderServiceInterface::class)->create($wedding);

        $response = $this
            ->actingAs($user)
            ->withSession(['filament_wedding_id' => $wedding->id])
            ->get(route('sites.edit', ['site' => $site->id]));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Sites/Editor')
                ->where('wedding.wedding_date', null)
                ->where('wedding.has_wedding_date', false)
        );
    }

    #[Test]
    public function editor_receives_normalized_wedding_date_and_true_flag_when_date_exists(): void
    {
        $wedding = Wedding::factory()->create([
            'wedding_date' => '2028-07-25',
        ]);

        $user = User::factory()->create();
        $wedding->users()->attach($user->id, ['role' => 'couple']);
        $user->update(['current_wedding_id' => $wedding->id]);

        $site = app(SiteBuilderServiceInterface::class)->create($wedding);

        $response = $this
            ->actingAs($user)
            ->withSession(['filament_wedding_id' => $wedding->id])
            ->get(route('sites.edit', ['site' => $site->id]));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Sites/Editor')
                ->where('wedding.wedding_date', '2028-07-25')
                ->where('wedding.has_wedding_date', true)
        );
    }
}
