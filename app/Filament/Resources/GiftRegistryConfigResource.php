<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GiftRegistryConfigResource\Pages;
use App\Models\GiftRegistryConfig;
use App\Services\FeeCalculator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filament Resource for managing Gift Registry Configuration.
 * 
 * @deprecated This resource is being migrated to the Site Editor.
 * Configuration is now managed directly in the Gift Registry section
 * of the Site Editor (sites/{site}/edit).
 * 
 * Allows couples to configure their gift registry settings including
 * typography and fee modality.
 * 
 * @Requirements: 3.1, 3.2, 6.1, 6.2, 6.7, 6.8
 */
class GiftRegistryConfigResource extends Resource
{
    protected static ?string $model = GiftRegistryConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    // Remover do menu de navegação
    protected static ?string $navigationGroup = null;

    protected static ?string $navigationLabel = 'Configurações';

    protected static ?string $modelLabel = 'Configuração';

    protected static ?string $pluralModelLabel = 'Configurações';

    protected static ?int $navigationSort = 51;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Configurações Gerais')
                    ->schema([
                        Forms\Components\Toggle::make('is_enabled')
                            ->label('Lista de Presentes Habilitada')
                            ->default(true)
                            ->helperText('Habilite ou desabilite a seção de lista de presentes no site'),
                    ]),

                Forms\Components\Section::make('Personalização do Título')
                    ->description('Configure a aparência do título da seção de lista de presentes')
                    ->schema([
                        Forms\Components\TextInput::make('section_title')
                            ->label('Título da Seção')
                            ->required()
                            ->default('Lista de Presentes')
                            ->maxLength(255)
                            ->helperText('Título exibido na seção de presentes'),

                        Forms\Components\Select::make('title_font_family')
                            ->label('Fonte')
                            ->options([
                                'Arial' => 'Arial',
                                'Helvetica' => 'Helvetica',
                                'Times New Roman' => 'Times New Roman',
                                'Georgia' => 'Georgia',
                                'Verdana' => 'Verdana',
                                'Courier New' => 'Courier New',
                                'Roboto' => 'Roboto',
                                'Open Sans' => 'Open Sans',
                                'Lato' => 'Lato',
                                'Montserrat' => 'Montserrat',
                                'Playfair Display' => 'Playfair Display',
                                'Dancing Script' => 'Dancing Script',
                            ])
                            ->searchable()
                            ->helperText('Família da fonte do título'),

                        Forms\Components\TextInput::make('title_font_size')
                            ->label('Tamanho da Fonte (px)')
                            ->numeric()
                            ->minValue(12)
                            ->maxValue(72)
                            ->suffix('px')
                            ->helperText('Tamanho da fonte em pixels'),

                        Forms\Components\ColorPicker::make('title_color')
                            ->label('Cor do Texto')
                            ->helperText('Cor do título em hexadecimal'),

                        Forms\Components\Select::make('title_style')
                            ->label('Estilo do Texto')
                            ->options([
                                'normal' => 'Normal',
                                'bold' => 'Negrito',
                                'italic' => 'Itálico',
                                'bold_italic' => 'Negrito Itálico',
                            ])
                            ->default('normal')
                            ->helperText('Estilo de formatação do texto'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Configuração de Taxas')
                    ->description('Defina quem paga a taxa da plataforma')
                    ->schema([
                        Forms\Components\Select::make('fee_modality')
                            ->label('Modalidade de Taxa')
                            ->required()
                            ->options([
                                'couple_pays' => 'Noivos Pagam a Taxa',
                                'guest_pays' => 'Convidados Pagam a Taxa',
                            ])
                            ->default('couple_pays')
                            ->live()
                            ->helperText('Escolha quem absorve o custo da taxa da plataforma'),

                        Forms\Components\Placeholder::make('fee_example')
                            ->label('Exemplo de Cálculo')
                            ->content(function (Get $get) {
                                $feeModality = $get('fee_modality') ?? 'couple_pays';
                                $feePercentage = 0.05; // 5% - TODO: Get from plan
                                $examplePrice = 10000; // R$ 100,00
                                
                                $calculator = app(FeeCalculator::class);
                                $amounts = $calculator->calculate($examplePrice, $feePercentage, $feeModality);
                                
                                $displayPrice = number_format($amounts->displayPrice / 100, 2, ',', '.');
                                $feeAmount = number_format($amounts->feeAmount / 100, 2, ',', '.');
                                $netAmountCouple = number_format($amounts->netAmountCouple / 100, 2, ',', '.');
                                
                                if ($feeModality === 'couple_pays') {
                                    return new \Illuminate\Support\HtmlString("
                                        <div class='text-sm space-y-1'>
                                            <p><strong>Exemplo:</strong> Presente de R$ 100,00</p>
                                            <p>• Convidado paga: <strong>R$ {$displayPrice}</strong></p>
                                            <p>• Taxa da plataforma (5%): <strong>R$ {$feeAmount}</strong></p>
                                            <p>• Vocês recebem: <strong>R$ {$netAmountCouple}</strong></p>
                                        </div>
                                    ");
                                } else {
                                    return new \Illuminate\Support\HtmlString("
                                        <div class='text-sm space-y-1'>
                                            <p><strong>Exemplo:</strong> Presente de R$ 100,00</p>
                                            <p>• Convidado paga: <strong>R$ {$displayPrice}</strong></p>
                                            <p>• Taxa da plataforma (5%): <strong>R$ {$feeAmount}</strong></p>
                                            <p>• Vocês recebem: <strong>R$ {$netAmountCouple}</strong></p>
                                        </div>
                                    ");
                                }
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('wedding.title')
                    ->label('Casamento')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('section_title')
                    ->label('Título')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\IconColumn::make('is_enabled')
                    ->label('Habilitado')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('fee_modality')
                    ->label('Modalidade de Taxa')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'couple_pays' => 'Noivos Pagam',
                        'guest_pays' => 'Convidados Pagam',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'couple_pays' => 'info',
                        'guest_pays' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_enabled')
                    ->label('Habilitado')
                    ->placeholder('Todos')
                    ->trueLabel('Apenas habilitados')
                    ->falseLabel('Apenas desabilitados'),

                Tables\Filters\SelectFilter::make('fee_modality')
                    ->label('Modalidade de Taxa')
                    ->options([
                        'couple_pays' => 'Noivos Pagam',
                        'guest_pays' => 'Convidados Pagam',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();
        if ($user && !$user->isAdmin()) {
            $wedding = $user->currentWedding;
            if ($wedding) {
                $query->where('wedding_id', $wedding->id);
            } else {
                $query->whereRaw('1 = 0'); // No results if no wedding context
            }
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGiftRegistryConfigs::route('/'),
            'create' => Pages\CreateGiftRegistryConfig::route('/create'),
            'edit' => Pages\EditGiftRegistryConfig::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Admin can always access
        if ($user->isAdmin()) {
            return true;
        }

        // Others need wedding context and couple/organizer role
        $wedding = $user->currentWedding;
        if (!$wedding) {
            return false;
        }

        $role = $user->roleIn($wedding);
        return in_array($role, ['couple', 'organizer']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Ocultar do menu de navegação
    }
}
