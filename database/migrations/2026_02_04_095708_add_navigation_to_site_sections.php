<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Atualizar site_layouts existentes para adicionar campos de navegação
        DB::table('site_layouts')->get()->each(function ($layout) {
            if (!isset($layout->draft_content)) {
                return;
            }
            $content = json_decode($layout->draft_content, true);
            
            if (!isset($content['sections'])) {
                return;
            }

            // Adicionar navigation em Save the Date
            if (isset($content['sections']['saveTheDate'])) {
                if (!isset($content['sections']['saveTheDate']['navigation'])) {
                    $content['sections']['saveTheDate']['navigation'] = [
                        'label' => 'Save the Date',
                        'showInMenu' => true,
                    ];
                }
            }

            // Adicionar navigation em Gift Registry
            if (isset($content['sections']['giftRegistry'])) {
                if (!isset($content['sections']['giftRegistry']['navigation'])) {
                    $content['sections']['giftRegistry']['navigation'] = [
                        'label' => 'Lista de Presentes',
                        'showInMenu' => true,
                    ];
                }
            }

            // Adicionar navigation em RSVP
            if (isset($content['sections']['rsvp'])) {
                if (!isset($content['sections']['rsvp']['navigation'])) {
                    $content['sections']['rsvp']['navigation'] = [
                        'label' => 'Confirme Presença',
                        'showInMenu' => true,
                    ];
                }
            }

            // Adicionar navigation em Photo Gallery
            if (isset($content['sections']['photoGallery'])) {
                if (!isset($content['sections']['photoGallery']['navigation'])) {
                    $content['sections']['photoGallery']['navigation'] = [
                        'label' => 'Galeria de Fotos',
                        'showInMenu' => true,
                    ];
                }
            }

            DB::table('site_layouts')
                ->where('id', $layout->id)
                ->update(['draft_content' => json_encode($content)]);
        });

        // Atualizar site_versions existentes para adicionar campos de navegação
        DB::table('site_versions')->get()->each(function ($version) {
            $content = json_decode($version->content, true);
            
            if (!isset($content['sections'])) {
                return;
            }

            // Adicionar navigation em Save the Date
            if (isset($content['sections']['saveTheDate'])) {
                if (!isset($content['sections']['saveTheDate']['navigation'])) {
                    $content['sections']['saveTheDate']['navigation'] = [
                        'label' => 'Save the Date',
                        'showInMenu' => true,
                    ];
                }
            }

            // Adicionar navigation em Gift Registry
            if (isset($content['sections']['giftRegistry'])) {
                if (!isset($content['sections']['giftRegistry']['navigation'])) {
                    $content['sections']['giftRegistry']['navigation'] = [
                        'label' => 'Lista de Presentes',
                        'showInMenu' => true,
                    ];
                }
            }

            // Adicionar navigation em RSVP
            if (isset($content['sections']['rsvp'])) {
                if (!isset($content['sections']['rsvp']['navigation'])) {
                    $content['sections']['rsvp']['navigation'] = [
                        'label' => 'Confirme Presença',
                        'showInMenu' => true,
                    ];
                }
            }

            // Adicionar navigation em Photo Gallery
            if (isset($content['sections']['photoGallery'])) {
                if (!isset($content['sections']['photoGallery']['navigation'])) {
                    $content['sections']['photoGallery']['navigation'] = [
                        'label' => 'Galeria de Fotos',
                        'showInMenu' => true,
                    ];
                }
            }

            DB::table('site_versions')
                ->where('id', $version->id)
                ->update(['content' => json_encode($content)]);
        });

        // Atualizar site_templates existentes para adicionar campos de navegação
        DB::table('site_templates')->get()->each(function ($template) {
            $content = json_decode($template->content, true);
            
            if (!isset($content['sections'])) {
                return;
            }

            // Adicionar navigation em Save the Date
            if (isset($content['sections']['saveTheDate'])) {
                if (!isset($content['sections']['saveTheDate']['navigation'])) {
                    $content['sections']['saveTheDate']['navigation'] = [
                        'label' => 'Save the Date',
                        'showInMenu' => true,
                    ];
                }
            }

            // Adicionar navigation em Gift Registry
            if (isset($content['sections']['giftRegistry'])) {
                if (!isset($content['sections']['giftRegistry']['navigation'])) {
                    $content['sections']['giftRegistry']['navigation'] = [
                        'label' => 'Lista de Presentes',
                        'showInMenu' => true,
                    ];
                }
            }

            // Adicionar navigation em RSVP
            if (isset($content['sections']['rsvp'])) {
                if (!isset($content['sections']['rsvp']['navigation'])) {
                    $content['sections']['rsvp']['navigation'] = [
                        'label' => 'Confirme Presença',
                        'showInMenu' => true,
                    ];
                }
            }

            // Adicionar navigation em Photo Gallery
            if (isset($content['sections']['photoGallery'])) {
                if (!isset($content['sections']['photoGallery']['navigation'])) {
                    $content['sections']['photoGallery']['navigation'] = [
                        'label' => 'Galeria de Fotos',
                        'showInMenu' => true,
                    ];
                }
            }

            DB::table('site_templates')
                ->where('id', $template->id)
                ->update(['content' => json_encode($content)]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover campos de navegação dos site_layouts
        DB::table('site_layouts')->get()->each(function ($layout) {
            if (!isset($layout->draft_content)) {
                return;
            }
            $content = json_decode($layout->draft_content, true);
            
            if (!isset($content['sections'])) {
                return;
            }

            if (isset($content['sections']['saveTheDate']['navigation'])) {
                unset($content['sections']['saveTheDate']['navigation']);
            }
            if (isset($content['sections']['giftRegistry']['navigation'])) {
                unset($content['sections']['giftRegistry']['navigation']);
            }
            if (isset($content['sections']['rsvp']['navigation'])) {
                unset($content['sections']['rsvp']['navigation']);
            }
            if (isset($content['sections']['photoGallery']['navigation'])) {
                unset($content['sections']['photoGallery']['navigation']);
            }

            DB::table('site_layouts')
                ->where('id', $layout->id)
                ->update(['draft_content' => json_encode($content)]);
        });

        // Remover campos de navegação dos site_versions
        DB::table('site_versions')->get()->each(function ($version) {
            $content = json_decode($version->content, true);
            
            if (!isset($content['sections'])) {
                return;
            }

            if (isset($content['sections']['saveTheDate']['navigation'])) {
                unset($content['sections']['saveTheDate']['navigation']);
            }
            if (isset($content['sections']['giftRegistry']['navigation'])) {
                unset($content['sections']['giftRegistry']['navigation']);
            }
            if (isset($content['sections']['rsvp']['navigation'])) {
                unset($content['sections']['rsvp']['navigation']);
            }
            if (isset($content['sections']['photoGallery']['navigation'])) {
                unset($content['sections']['photoGallery']['navigation']);
            }

            DB::table('site_versions')
                ->where('id', $version->id)
                ->update(['content' => json_encode($content)]);
        });

        // Remover campos de navegação dos site_templates
        DB::table('site_templates')->get()->each(function ($template) {
            $content = json_decode($template->content, true);
            
            if (!isset($content['sections'])) {
                return;
            }

            if (isset($content['sections']['saveTheDate']['navigation'])) {
                unset($content['sections']['saveTheDate']['navigation']);
            }
            if (isset($content['sections']['giftRegistry']['navigation'])) {
                unset($content['sections']['giftRegistry']['navigation']);
            }
            if (isset($content['sections']['rsvp']['navigation'])) {
                unset($content['sections']['rsvp']['navigation']);
            }
            if (isset($content['sections']['photoGallery']['navigation'])) {
                unset($content['sections']['photoGallery']['navigation']);
            }

            DB::table('site_templates')
                ->where('id', $template->id)
                ->update(['content' => json_encode($content)]);
        });
    }
};
