<?php

namespace App\Filament\Resources\SiteTemplateResource\Pages;

use App\Filament\Resources\SiteTemplateResource;
use App\Models\SiteLayout;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSiteTemplate extends ViewRecord
{
    protected static string $resource = SiteTemplateResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informações')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nome'),
                        TextEntry::make('description')
                            ->label('Descrição'),
                        TextEntry::make('is_public')
                            ->label('Visibilidade')
                            ->formatStateUsing(fn ($state) => $state ? 'Público' : 'Privado')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'gray'),
                    ])
                    ->columns(3),

                Section::make('Preview do Template')
                    ->schema([
                        ViewEntry::make('preview')
                            ->view('filament.resources.site-template.preview')
                            ->viewData([
                                'content' => $this->record->content,
                            ]),
                    ]),

                Section::make('Configurações do Tema')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('content.theme.primaryColor')
                                    ->label('Cor Primária')
                                    ->formatStateUsing(fn ($state) => $state ?? '#000000')
                                    ->html()
                                    ->formatStateUsing(fn ($state) => '<div class="flex items-center gap-2"><span class="w-6 h-6 rounded border" style="background-color: ' . ($state ?? '#000000') . '"></span> ' . ($state ?? '#000000') . '</div>'),
                                TextEntry::make('content.theme.secondaryColor')
                                    ->label('Cor Secundária')
                                    ->html()
                                    ->formatStateUsing(fn ($state) => '<div class="flex items-center gap-2"><span class="w-6 h-6 rounded border" style="background-color: ' . ($state ?? '#000000') . '"></span> ' . ($state ?? '#000000') . '</div>'),
                                TextEntry::make('content.theme.fontFamily')
                                    ->label('Fonte'),
                                TextEntry::make('content.theme.fontSize')
                                    ->label('Tamanho da Fonte'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Seções Configuradas')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('content.sections.header.enabled')
                                    ->label('Cabeçalho')
                                    ->formatStateUsing(fn ($state) => $state ? 'Ativado' : 'Desativado')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                                TextEntry::make('content.sections.hero.enabled')
                                    ->label('Hero')
                                    ->formatStateUsing(fn ($state) => $state ? 'Ativado' : 'Desativado')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                                TextEntry::make('content.sections.saveTheDate.enabled')
                                    ->label('Save the Date')
                                    ->formatStateUsing(fn ($state) => $state ? 'Ativado' : 'Desativado')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                                TextEntry::make('content.sections.giftRegistry.enabled')
                                    ->label('Lista de Presentes')
                                    ->formatStateUsing(fn ($state) => $state ? 'Ativado' : 'Desativado')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                                TextEntry::make('content.sections.rsvp.enabled')
                                    ->label('RSVP')
                                    ->formatStateUsing(fn ($state) => $state ? 'Ativado' : 'Desativado')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                                TextEntry::make('content.sections.photoGallery.enabled')
                                    ->label('Galeria de Fotos')
                                    ->formatStateUsing(fn ($state) => $state ? 'Ativado' : 'Desativado')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                                TextEntry::make('content.sections.footer.enabled')
                                    ->label('Rodapé')
                                    ->formatStateUsing(fn ($state) => $state ? 'Ativado' : 'Desativado')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('apply')
                ->label('Aplicar ao Meu Site')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Aplicar Template')
                ->modalDescription(fn () => "Deseja aplicar o template \"{$this->record->name}\" ao seu site? O conteúdo atual será mesclado com o template.")
                ->modalSubmitActionLabel('Aplicar Template')
                ->action(function () {
                    $user = auth()->user();
                    $weddingId = $user?->current_wedding_id ?? session('filament_wedding_id');
                    
                    if (!$weddingId) {
                        Notification::make()
                            ->title('Erro')
                            ->body('Nenhum casamento selecionado.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $site = SiteLayout::where('wedding_id', $weddingId)->first();
                    
                    if (!$site) {
                        Notification::make()
                            ->title('Erro')
                            ->body('Você precisa criar um site primeiro.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $siteBuilder = app(\App\Contracts\Site\SiteBuilderServiceInterface::class);
                    $siteBuilder->applyTemplate($site, $this->record);

                    Notification::make()
                        ->title('Template aplicado!')
                        ->body("O template \"{$this->record->name}\" foi aplicado ao seu site.")
                        ->success()
                        ->send();

                    $this->redirect(route('filament.admin.pages.site-editor', ['site' => $site->id]));
                })
                ->visible(function () {
                    $user = auth()->user();
                    $weddingId = $user?->current_wedding_id ?? session('filament_wedding_id');
                    return $weddingId && SiteLayout::where('wedding_id', $weddingId)->exists();
                }),
        ];
    }
}
