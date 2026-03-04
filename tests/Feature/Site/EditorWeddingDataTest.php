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

    #[Test]
    public function editor_does_not_expose_access_token_or_cached_password_in_draft_settings(): void
    {
        $wedding = Wedding::factory()->create();

        $user = User::factory()->create();
        $wedding->users()->attach($user->id, ['role' => 'couple']);
        $user->update(['current_wedding_id' => $wedding->id]);

        $site = app(SiteBuilderServiceInterface::class)->create($wedding);
        $draftContent = $site->draft_content;
        $draftContent['settings'] = [
            'access_token' => 'senha-antiga',
            'custom_domain' => 'https://example.com',
        ];

        $site->update([
            'access_token' => 'senha-real',
            'draft_content' => $draftContent,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['filament_wedding_id' => $wedding->id])
            ->get(route('sites.edit', ['site' => $site->id]));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Sites/Editor')
                ->where('site.has_password', true)
                ->where('site', function ($site): bool {
                    $siteArray = is_array($site)
                        ? $site
                        : (method_exists($site, 'toArray') ? $site->toArray() : []);

                    return !array_key_exists('access_token', $siteArray)
                        && !array_key_exists('access_token', $siteArray['draft_content']['settings'] ?? []);
                })
        );
    }
}
