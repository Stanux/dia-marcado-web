<?php

namespace App\Livewire;

use Filament\Facades\Filament;
use Livewire\Component;
use Livewire\Attributes\On;

class TopbarTitle extends Component
{
    public string $title = '';

    public function mount(): void
    {
        $this->updateTitle();
    }

    #[On('page-title-updated')]
    public function updateTitle(): void
    {
        $this->title = $this->getCurrentPageTitle();
    }

    protected function getCurrentPageTitle(): string
    {
        $routeName = request()->route()?->getName() ?? '';
        
        // Verifica se é uma página de resource pelo nome da rota
        // Formato: filament.admin.resources.{resource}.{page}
        if (preg_match('/filament\.admin\.resources\.([^.]+)\.(\w+)/', $routeName, $matches)) {
            $resourceSlug = $matches[1];
            $pageName = $matches[2];
            
            // Encontra o resource correspondente
            foreach (Filament::getResources() as $resource) {
                if ($resource::getSlug() === $resourceSlug) {
                    return match ($pageName) {
                        'index' => $resource::getPluralModelLabel(),
                        'create' => 'Criar ' . $resource::getModelLabel(),
                        'edit' => 'Editar ' . $resource::getModelLabel(),
                        'view' => 'Visualizar ' . $resource::getModelLabel(),
                        default => $resource::getPluralModelLabel(),
                    };
                }
            }
        }
        
        // Verifica se é uma página customizada
        // Formato: filament.admin.pages.{page}
        if (preg_match('/filament\.admin\.pages\.(.+)/', $routeName, $matches)) {
            $pageSlug = $matches[1];
            
            foreach (Filament::getPages() as $page) {
                if ($page::getSlug() === $pageSlug || $page::getRouteName() === $routeName) {
                    return $page::getNavigationLabel();
                }
            }
        }
        
        return '';
    }

    public function render()
    {
        return view('livewire.topbar-title');
    }
}
