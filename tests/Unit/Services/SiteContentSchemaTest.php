<?php

namespace Tests\Unit\Services;

use App\Services\Site\SiteContentSchema;
use Tests\TestCase;

class SiteContentSchemaTest extends TestCase
{
    /**
     * @test
     */
    public function normalize_fills_rsvp_defaults_for_legacy_content(): void
    {
        $content = SiteContentSchema::getDefaultContent();
        $content['sections']['rsvp'] = [
            'enabled' => true,
            'navigation' => [
                'label' => 'Confirme Presença',
                'showInMenu' => true,
            ],
            'title' => 'RSVP personalizado',
            'description' => 'Descrição antiga',
            'style' => [
                'backgroundColor' => '#fef3f8',
            ],
            'mockFields' => [
                ['label' => 'Nome', 'type' => 'text'],
            ],
        ];

        $normalized = SiteContentSchema::normalize($content);
        $rsvp = $normalized['sections']['rsvp'];

        $this->assertSame('RSVP personalizado', $rsvp['title']);
        $this->assertSame('Descrição antiga', $rsvp['description']);
        $this->assertSame('#fef3f8', $rsvp['style']['backgroundColor']);

        $this->assertArrayHasKey('access', $rsvp);
        $this->assertArrayHasKey('eventSelection', $rsvp);
        $this->assertArrayHasKey('fields', $rsvp);
        $this->assertArrayHasKey('messages', $rsvp);
        $this->assertArrayHasKey('labels', $rsvp);
        $this->assertArrayHasKey('statusOptions', $rsvp);
        $this->assertArrayHasKey('preview', $rsvp);
    }

    /**
     * @test
     */
    public function normalize_preserves_indexed_arrays_without_merging_defaults(): void
    {
        $content = SiteContentSchema::getDefaultContent();
        $content['sections']['rsvp']['mockFields'] = [
            ['label' => 'Campo custom', 'type' => 'text'],
        ];
        $content['sections']['rsvp']['eventSelection']['selectedEventIds'] = ['event-1', 'event-2'];

        $normalized = SiteContentSchema::normalize($content);
        $rsvp = $normalized['sections']['rsvp'];

        $this->assertSame(
            [['label' => 'Campo custom', 'type' => 'text']],
            $rsvp['mockFields']
        );
        $this->assertSame(['event-1', 'event-2'], $rsvp['eventSelection']['selectedEventIds']);
    }

    /**
     * @test
     */
    public function photo_gallery_defaults_include_mixed_media_structure(): void
    {
        $gallery = SiteContentSchema::getPhotoGallerySection();

        $this->assertArrayHasKey('items', $gallery['albums']['before']);
        $this->assertArrayHasKey('items', $gallery['albums']['after']);
        $this->assertArrayHasKey('photos', $gallery['albums']['before']);
        $this->assertArrayHasKey('pagination', $gallery);
        $this->assertArrayHasKey('video', $gallery);
        $this->assertSame(20, $gallery['pagination']['perPage']);
        $this->assertSame(1000, $gallery['video']['hoverDelayMs']);
        $this->assertTrue($gallery['video']['hoverPreview']);
    }
}
