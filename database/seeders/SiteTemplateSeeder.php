<?php

namespace Database\Seeders;

use App\Models\SiteTemplate;
use App\Services\Site\SiteContentSchema;
use Illuminate\Database\Seeder;

/**
 * SiteTemplateSeeder
 * 
 * Seeds the database with pre-defined public templates for wedding sites.
 */
class SiteTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            $this->getClassicTemplate(),
            $this->getModernTemplate(),
            $this->getMinimalistTemplate(),
            $this->getRomanticTemplate(),
        ];

        foreach ($templates as $template) {
            SiteTemplate::updateOrCreate(
                ['name' => $template['name'], 'wedding_id' => null],
                $template
            );
        }
    }

    /**
     * Get the Classic template configuration.
     * Elegant and traditional style with gold and brown colors.
     */
    private function getClassicTemplate(): array
    {
        $content = SiteContentSchema::getDefaultContent();
        
        // Theme customization
        $content['theme'] = [
            'primaryColor' => '#d4a574',
            'secondaryColor' => '#8b7355',
            'fontFamily' => 'Playfair Display',
            'fontSize' => '16px',
        ];

        // Header style
        $content['sections']['header']['style'] = [
            'height' => '100px',
            'alignment' => 'center',
            'backgroundColor' => '#faf8f5',
            'sticky' => true,
            'overlay' => [
                'enabled' => false,
                'opacity' => 0.3,
            ],
        ];

        // Hero style
        $content['sections']['hero']['layout'] = 'full-bleed';
        $content['sections']['hero']['style'] = [
            'overlay' => [
                'color' => '#8b7355',
                'opacity' => 0.4,
            ],
            'textAlign' => 'center',
            'animation' => 'fade',
            'animationDuration' => 800,
        ];

        // Save the Date style
        $content['sections']['saveTheDate']['style'] = [
            'backgroundColor' => '#faf8f5',
            'layout' => 'card',
        ];

        // Gift Registry style
        $content['sections']['giftRegistry']['style'] = [
            'backgroundColor' => '#ffffff',
        ];

        // RSVP style
        $content['sections']['rsvp']['style'] = [
            'backgroundColor' => '#faf8f5',
        ];

        // Photo Gallery style
        $content['sections']['photoGallery']['layout'] = 'masonry';
        $content['sections']['photoGallery']['style'] = [
            'backgroundColor' => '#ffffff',
            'columns' => 3,
        ];

        // Footer style
        $content['sections']['footer']['style'] = [
            'backgroundColor' => '#8b7355',
            'textColor' => '#ffffff',
            'borderTop' => true,
        ];

        return [
            'wedding_id' => null,
            'name' => 'Clássico',
            'description' => 'Estilo elegante e tradicional com tons dourados e marrons. Perfeito para casamentos sofisticados.',
            'thumbnail' => null,
            'content' => $content,
            'is_public' => true,
        ];
    }

    /**
     * Get the Modern template configuration.
     * Clean and contemporary style with dark gray and green colors.
     */
    private function getModernTemplate(): array
    {
        $content = SiteContentSchema::getDefaultContent();
        
        // Theme customization
        $content['theme'] = [
            'primaryColor' => '#2d3436',
            'secondaryColor' => '#00b894',
            'fontFamily' => 'Montserrat',
            'fontSize' => '16px',
        ];

        // Header style
        $content['sections']['header']['style'] = [
            'height' => '70px',
            'alignment' => 'left',
            'backgroundColor' => '#ffffff',
            'sticky' => true,
            'overlay' => [
                'enabled' => false,
                'opacity' => 0.3,
            ],
        ];

        // Hero style
        $content['sections']['hero']['layout'] = 'split';
        $content['sections']['hero']['style'] = [
            'overlay' => [
                'color' => '#2d3436',
                'opacity' => 0.3,
            ],
            'textAlign' => 'left',
            'animation' => 'slide',
            'animationDuration' => 600,
        ];

        // Save the Date style
        $content['sections']['saveTheDate']['style'] = [
            'backgroundColor' => '#f8f9fa',
            'layout' => 'inline',
        ];

        // Gift Registry style
        $content['sections']['giftRegistry']['style'] = [
            'backgroundColor' => '#ffffff',
        ];

        // RSVP style
        $content['sections']['rsvp']['style'] = [
            'backgroundColor' => '#f8f9fa',
        ];

        // Photo Gallery style
        $content['sections']['photoGallery']['layout'] = 'grid';
        $content['sections']['photoGallery']['style'] = [
            'backgroundColor' => '#ffffff',
            'columns' => 4,
        ];

        // Footer style
        $content['sections']['footer']['style'] = [
            'backgroundColor' => '#2d3436',
            'textColor' => '#ffffff',
            'borderTop' => false,
        ];

        return [
            'wedding_id' => null,
            'name' => 'Moderno',
            'description' => 'Estilo clean e contemporâneo com design minimalista. Ideal para casais que apreciam modernidade.',
            'thumbnail' => null,
            'content' => $content,
            'is_public' => true,
        ];
    }

    /**
     * Get the Minimalist template configuration.
     * Simple and direct style with black and white colors.
     */
    private function getMinimalistTemplate(): array
    {
        $content = SiteContentSchema::getDefaultContent();
        
        // Theme customization
        $content['theme'] = [
            'primaryColor' => '#000000',
            'secondaryColor' => '#ffffff',
            'fontFamily' => 'Inter',
            'fontSize' => '15px',
        ];

        // Header style
        $content['sections']['header']['style'] = [
            'height' => '60px',
            'alignment' => 'center',
            'backgroundColor' => '#ffffff',
            'sticky' => false,
            'overlay' => [
                'enabled' => false,
                'opacity' => 0.3,
            ],
        ];

        // Hero style
        $content['sections']['hero']['layout'] = 'boxed';
        $content['sections']['hero']['style'] = [
            'overlay' => [
                'color' => '#000000',
                'opacity' => 0.2,
            ],
            'textAlign' => 'center',
            'animation' => 'none',
            'animationDuration' => 0,
        ];

        // Save the Date style
        $content['sections']['saveTheDate']['style'] = [
            'backgroundColor' => '#ffffff',
            'layout' => 'inline',
        ];

        // Gift Registry style
        $content['sections']['giftRegistry']['style'] = [
            'backgroundColor' => '#fafafa',
        ];

        // RSVP style
        $content['sections']['rsvp']['style'] = [
            'backgroundColor' => '#ffffff',
        ];

        // Photo Gallery style
        $content['sections']['photoGallery']['layout'] = 'grid';
        $content['sections']['photoGallery']['style'] = [
            'backgroundColor' => '#fafafa',
            'columns' => 3,
        ];

        // Footer style
        $content['sections']['footer']['style'] = [
            'backgroundColor' => '#000000',
            'textColor' => '#ffffff',
            'borderTop' => false,
        ];

        return [
            'wedding_id' => null,
            'name' => 'Minimalista',
            'description' => 'Estilo simples e direto com foco no conteúdo. Para quem prefere elegância na simplicidade.',
            'thumbnail' => null,
            'content' => $content,
            'is_public' => true,
        ];
    }

    /**
     * Get the Romantic template configuration.
     * Delicate and feminine style with pink colors.
     */
    private function getRomanticTemplate(): array
    {
        $content = SiteContentSchema::getDefaultContent();
        
        // Theme customization
        $content['theme'] = [
            'primaryColor' => '#e84393',
            'secondaryColor' => '#fd79a8',
            'fontFamily' => 'Dancing Script',
            'fontSize' => '18px',
        ];

        // Header style
        $content['sections']['header']['style'] = [
            'height' => '90px',
            'alignment' => 'center',
            'backgroundColor' => '#fff5f8',
            'sticky' => true,
            'overlay' => [
                'enabled' => false,
                'opacity' => 0.3,
            ],
        ];

        // Hero style
        $content['sections']['hero']['layout'] = 'full-bleed';
        $content['sections']['hero']['style'] = [
            'overlay' => [
                'color' => '#e84393',
                'opacity' => 0.25,
            ],
            'textAlign' => 'center',
            'animation' => 'zoom',
            'animationDuration' => 1000,
        ];

        // Save the Date style
        $content['sections']['saveTheDate']['style'] = [
            'backgroundColor' => '#fff5f8',
            'layout' => 'card',
        ];

        // Gift Registry style
        $content['sections']['giftRegistry']['style'] = [
            'backgroundColor' => '#ffffff',
        ];

        // RSVP style
        $content['sections']['rsvp']['style'] = [
            'backgroundColor' => '#fff5f8',
        ];

        // Photo Gallery style
        $content['sections']['photoGallery']['layout'] = 'slideshow';
        $content['sections']['photoGallery']['style'] = [
            'backgroundColor' => '#ffffff',
            'columns' => 3,
        ];

        // Footer style
        $content['sections']['footer']['style'] = [
            'backgroundColor' => '#e84393',
            'textColor' => '#ffffff',
            'borderTop' => true,
        ];

        return [
            'wedding_id' => null,
            'name' => 'Romântico',
            'description' => 'Estilo delicado e feminino com tons de rosa. Perfeito para casamentos românticos e sonhadores.',
            'thumbnail' => null,
            'content' => $content,
            'is_public' => true,
        ];
    }
}
