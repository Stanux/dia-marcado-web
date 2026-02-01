<?php

namespace Tests\Feature\Properties;

use App\Models\SiteLayout;
use App\Models\User;
use App\Models\Wedding;
use App\Services\Site\SlugGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testes de propriedade para unicidade de slug.
 * 
 * Propriedade 9: Unicidade de Slug
 */
class SlugUniquenessPropertyTest extends TestCase
{
    use RefreshDatabase;

    private SlugGeneratorService $slugGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->slugGenerator = new SlugGeneratorService();
    }

    /**
     * Propriedade 9: Slugs gerados devem ser únicos mesmo para casamentos com nomes iguais.
     * 
     * Valida: Requisitos 6.5, 6.6
     */
    #[Test]
    public function generated_slugs_are_unique_for_same_names(): void
    {
        // Create first wedding with couple
        $user1 = User::factory()->create(['name' => 'João Silva']);
        $wedding1 = Wedding::factory()->create();
        $wedding1->users()->attach($user1, ['role' => 'couple']);

        // Generate first slug
        $slug1 = $this->slugGenerator->generate($wedding1);
        
        // Create site with first slug
        SiteLayout::factory()->create([
            'wedding_id' => $wedding1->id,
            'slug' => $slug1,
        ]);

        // Create second wedding with same name
        $user2 = User::factory()->create(['name' => 'João Silva']);
        $wedding2 = Wedding::factory()->create();
        $wedding2->users()->attach($user2, ['role' => 'couple']);

        // Generate second slug - should be different
        $slug2 = $this->slugGenerator->generate($wedding2);

        $this->assertNotEquals($slug1, $slug2);
        $this->assertStringStartsWith('casamento-joao', $slug2);
    }

    /**
     * Propriedade 9: Múltiplos casamentos com mesmos nomes geram slugs sequenciais.
     * 
     * Valida: Requisitos 6.5, 6.6
     */
    #[Test]
    public function multiple_weddings_with_same_names_get_sequential_slugs(): void
    {
        $slugs = [];

        for ($i = 0; $i < 5; $i++) {
            $user = User::factory()->create(['name' => 'Maria Santos']);
            $wedding = Wedding::factory()->create();
            $wedding->users()->attach($user, ['role' => 'couple']);

            $slug = $this->slugGenerator->generate($wedding);
            $slugs[] = $slug;

            SiteLayout::factory()->create([
                'wedding_id' => $wedding->id,
                'slug' => $slug,
            ]);
        }

        // All slugs should be unique
        $this->assertCount(5, array_unique($slugs));

        // First slug should be base, others should have suffix
        $this->assertEquals('casamento-maria', $slugs[0]);
        $this->assertEquals('casamento-maria-2', $slugs[1]);
        $this->assertEquals('casamento-maria-3', $slugs[2]);
        $this->assertEquals('casamento-maria-4', $slugs[3]);
        $this->assertEquals('casamento-maria-5', $slugs[4]);
    }

    /**
     * Propriedade 9: Slugs para casais com dois nomes seguem padrão correto.
     * 
     * Valida: Requisitos 6.5, 6.6
     */
    #[Test]
    public function couple_slugs_use_both_first_names(): void
    {
        $user1 = User::factory()->create(['name' => 'Ana Paula']);
        $user2 = User::factory()->create(['name' => 'Carlos Eduardo']);
        
        $wedding = Wedding::factory()->create();
        $wedding->users()->attach($user1, ['role' => 'couple']);
        $wedding->users()->attach($user2, ['role' => 'couple']);

        $slug = $this->slugGenerator->generate($wedding);

        $this->assertEquals('ana-e-carlos', $slug);
    }

    /**
     * Propriedade 9: Slugs são normalizados corretamente (sem acentos, lowercase).
     * 
     * Valida: Requisitos 6.5, 6.6
     */
    #[Test]
    public function slugs_are_properly_normalized(): void
    {
        $user1 = User::factory()->create(['name' => 'José Antônio']);
        $user2 = User::factory()->create(['name' => 'Márcia Cristina']);
        
        $wedding = Wedding::factory()->create();
        $wedding->users()->attach($user1, ['role' => 'couple']);
        $wedding->users()->attach($user2, ['role' => 'couple']);

        $slug = $this->slugGenerator->generate($wedding);

        // Should be lowercase, no accents
        $this->assertEquals('jose-e-marcia', $slug);
        $this->assertDoesNotMatchRegularExpression('/[A-Z]/', $slug);
        $this->assertDoesNotMatchRegularExpression('/[áéíóúàèìòùâêîôûãõç]/i', $slug);
    }

    /**
     * Propriedade 9: Casamento sem membros gera slug com identificador aleatório.
     * 
     * Valida: Requisitos 6.5, 6.6
     */
    #[Test]
    public function wedding_without_members_gets_random_slug(): void
    {
        $wedding = Wedding::factory()->create();

        $slug = $this->slugGenerator->generate($wedding);

        $this->assertStringStartsWith('casamento-', $slug);
        $this->assertGreaterThan(strlen('casamento-'), strlen($slug));
    }

    /**
     * Propriedade 9: Slugs não excedem tamanho máximo.
     * 
     * Valida: Requisitos 6.5, 6.6
     */
    #[Test]
    public function slugs_do_not_exceed_max_length(): void
    {
        $longName = str_repeat('Abcdefghij ', 20); // Very long name
        $user = User::factory()->create(['name' => $longName]);
        
        $wedding = Wedding::factory()->create();
        $wedding->users()->attach($user, ['role' => 'couple']);

        $slug = $this->slugGenerator->generate($wedding);

        $this->assertLessThanOrEqual(100, strlen($slug));
    }

    /**
     * Propriedade 9: ensureUnique adiciona sufixo numérico quando necessário.
     * 
     * Valida: Requisitos 6.5, 6.6
     */
    #[Test]
    public function ensure_unique_adds_numeric_suffix(): void
    {
        // Create existing site with base slug
        $wedding = Wedding::factory()->create();
        SiteLayout::factory()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'test-slug',
        ]);

        // ensureUnique should add suffix
        $uniqueSlug = $this->slugGenerator->ensureUnique('test-slug');

        $this->assertEquals('test-slug-2', $uniqueSlug);
    }
}
