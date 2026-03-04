<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Filament\Resources\TaskResource\RelationManagers\TaskBudgetsRelationManager;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use App\Models\VendorCategory;
use App\Models\Wedding;
use App\Models\WeddingVendor;
use App\Models\WeddingPlan;
use App\Services\Planning\WeddingVendorService;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Filament Resource for managing Tasks.
 * 
 * Extends WeddingScopedResource to automatically:
 * - Filter tasks by the current wedding context
 * - Verify user has 'plans' module permission
 * - Inject wedding_id when creating new tasks
 */
class TaskResource extends WeddingScopedResource
{
    protected static ?string $model = Task::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Plano de Tarefas';

    protected static ?string $navigationGroup = null;

    protected static ?string $module = 'plans';

    protected static ?string $modelLabel = 'Tarefa';

    protected static ?string $pluralModelLabel = 'Tarefas';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalhes da Tarefa')
                    ->schema(static::taskFormFields())
                    ->columns(2),
                Forms\Components\Section::make('Orçamentos')
                    ->description('Opcional: adicione orçamentos já no cadastro da tarefa.')
                    ->schema([
                        static::taskBudgetRepeater(),
                    ])
                    ->visible(fn (string $operation): bool => $operation === 'create')
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::taskTableColumns())
            ->filters(static::taskTableFilters())
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar esta tarefa'),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Excluir esta tarefa'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('due_date', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            TaskBudgetsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['plan', 'category', 'assignedUser', 'wedding']);

        $planId = request()->query('plan');

        if ($planId) {
            $query->where('wedding_plan_id', $planId);
        }

        return $query;
    }

    public static function getSlug(): string
    {
        return 'plan-tasks';
    }

    public static function canEdit(Model $record): bool
    {
        if (!parent::canEdit($record)) {
            return false;
        }

        if ($record->plan && $record->plan->isArchived() && !auth()->user()?->isAdmin()) {
            return false;
        }

        return true;
    }

    public static function canDelete(Model $record): bool
    {
        if (!parent::canDelete($record)) {
            return false;
        }

        if ($record->plan && $record->plan->isArchived() && !auth()->user()?->isAdmin()) {
            return false;
        }

        return true;
    }

    public static function taskFormFields(?Closure $resolveWeddingId = null, bool $includeWeddingPlanField = true): array
    {
        $resolveWeddingId ??= fn (): ?string => static::resolveCurrentWeddingId();

        $fields = [
            Forms\Components\TextInput::make('title')
                ->label('Título')
                ->required()
                ->maxLength(255),

            Forms\Components\Textarea::make('description')
                ->label('Descrição')
                ->rows(3)
                ->maxLength(65535),
        ];

        if ($includeWeddingPlanField) {
            $fields[] = Forms\Components\Select::make('wedding_plan_id')
                ->label('Planejamento')
                ->required()
                ->options(function () use ($resolveWeddingId) {
                    return static::getWeddingPlanOptions($resolveWeddingId());
                })
                ->default(fn (): ?string => request()->query('plan'))
                ->searchable();
        }

        return [
            ...$fields,
            Forms\Components\Select::make('task_category_id')
                ->label('Categoria')
                ->options(TaskCategory::orderBy('sort')->pluck('name', 'id'))
                ->searchable(),

            Forms\Components\Select::make('status')
                ->label('Status')
                ->options([
                    'pending' => 'Pendente',
                    'in_progress' => 'Em Andamento',
                    'completed' => 'Concluída',
                    'cancelled' => 'Cancelada',
                ])
                ->default('pending')
                ->required(),

            Forms\Components\DatePicker::make('start_date')
                ->label('Data de Início')
                ->native(false)
                ->displayFormat('d/m/Y')
                ->required()
                ->beforeOrEqual('due_date'),

            Forms\Components\DatePicker::make('due_date')
                ->label('Data Limite')
                ->native(false)
                ->displayFormat('d/m/Y')
                ->required()
                ->afterOrEqual('start_date'),

            Forms\Components\Select::make('priority')
                ->label('Prioridade')
                ->options([
                    'low' => 'Baixa',
                    'medium' => 'Média',
                    'high' => 'Alta',
                ])
                ->default('medium')
                ->required(),

            Forms\Components\Select::make('assigned_to')
                ->label('Responsável')
                ->options(function () use ($resolveWeddingId) {
                    return static::getAssignableUsersOptions($resolveWeddingId());
                })
                ->searchable()
                ->nullable(),

            Forms\Components\TextInput::make('estimated_value')
                ->label('Valor Estimado')
                ->prefix('R$')
                ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                ->formatStateUsing(fn ($state): ?string => static::formatMoneyForInput($state))
                ->dehydrateStateUsing(fn ($state): ?string => static::normalizeMoneyForStorage($state)),

            Forms\Components\TextInput::make('actual_value')
                ->label('Valor Real')
                ->prefix('R$')
                ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                ->formatStateUsing(fn ($state): ?string => static::formatMoneyForInput($state))
                ->dehydrateStateUsing(fn ($state): ?string => static::normalizeMoneyForStorage($state))
                ->visible(fn (Get $get) => $get('status') === 'completed')
                ->required(fn (Get $get) => $get('status') === 'completed'),

            Forms\Components\DatePicker::make('executed_at')
                ->label('Data de Execução')
                ->native(false)
                ->displayFormat('d/m/Y')
                ->visible(fn (Get $get) => $get('status') === 'completed')
                ->required(fn (Get $get) => $get('status') === 'completed'),
        ];
    }

    public static function taskBudgetRepeater(?Closure $resolveWeddingId = null): Forms\Components\Repeater
    {
        $resolveWeddingId ??= fn (): ?string => static::resolveCurrentWeddingId();

        return Forms\Components\Repeater::make('budgets')
            ->label('Orçamentos')
            ->schema([
                Forms\Components\Hidden::make('wedding_vendor_id'),
                Forms\Components\Hidden::make('wedding_vendor_name'),
                Forms\Components\Hidden::make('status'),
                Forms\Components\Hidden::make('value'),
                Forms\Components\Hidden::make('valid_until'),
                Forms\Components\Hidden::make('notes'),
            ])
            ->itemLabel(fn (array $state): string => static::formatBudgetItemLabel($state))
            ->collapsible()
            ->collapsed()
            ->reorderable(false)
            ->cloneable(false)
            ->deleteAction(fn (FormAction $action): FormAction => $action->requiresConfirmation())
            ->addAction(function (FormAction $action) use ($resolveWeddingId): FormAction {
                return $action
                    ->label('Cadastrar orçamento')
                    ->modalHeading('Cadastrar orçamento')
                    ->modalWidth(MaxWidth::FourExtraLarge)
                    ->modalSubmitActionLabel('Salvar')
                    ->extraModalFooterActions(fn (FormAction $action): array => [
                        $action
                            ->makeModalSubmitAction('createAnother', arguments: ['another' => true])
                            ->label('Salvar e criar outro'),
                    ])
                    ->form(static::taskBudgetFormFields($resolveWeddingId))
                    ->action(function (
                        array $arguments,
                        array $data,
                        FormAction $action,
                        Forms\Components\Repeater $component,
                        Form $form
                    ): void {
                        $state = $component->getState();
                        $itemData = static::normalizeBudgetDraftData($data);

                        $newUuid = $component->generateUuid();

                        if ($newUuid) {
                            $state[$newUuid] = $itemData;
                        } else {
                            $state[] = $itemData;
                        }

                        $component->state($state);
                        $component->callAfterStateUpdated();

                        if ($arguments['another'] ?? false) {
                            $form->fill();
                            $action->halt();
                        }
                    });
            })
            ->extraItemActions([
                FormAction::make('editBudget')
                    ->icon('heroicon-o-pencil-square')
                    ->tooltip('Editar orçamento')
                    ->modalHeading('Editar orçamento')
                    ->modalWidth(MaxWidth::FourExtraLarge)
                    ->modalSubmitActionLabel('Salvar')
                    ->fillForm(function (array $arguments, Forms\Components\Repeater $component): array {
                        $itemData = $component->getRawItemState($arguments['item']);
                        $itemData['value'] = static::formatMoneyForInput($itemData['value'] ?? null);

                        return $itemData;
                    })
                    ->form(static::taskBudgetFormFields($resolveWeddingId))
                    ->action(function (array $arguments, array $data, Forms\Components\Repeater $component): void {
                        $state = $component->getState();
                        $state[$arguments['item']] = static::normalizeBudgetDraftData($data);

                        $component->state($state);
                        $component->callAfterStateUpdated();
                    }),
            ])
            ->defaultItems(0)
            ->columns(1)
            ->dehydrated(fn (string $operation): bool => $operation === 'create')
            ->columnSpanFull();
    }

    /**
     * @return array<\Filament\Forms\Components\Component>
     */
    private static function taskBudgetFormFields(?Closure $resolveWeddingId = null): array
    {
        $resolveWeddingId ??= fn (): ?string => static::resolveCurrentWeddingId();

        return [
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\Select::make('wedding_vendor_id')
                        ->label('Fornecedor')
                        ->options(function () use ($resolveWeddingId): array {
                            $weddingId = $resolveWeddingId();

                            if (! $weddingId) {
                                return [];
                            }

                            return WeddingVendor::query()
                                ->where('wedding_id', $weddingId)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all();
                        })
                        ->searchable()
                        ->required()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->label('Nome')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('document')
                                ->label('CPF/CNPJ')
                                ->required()
                                ->maxLength(20),
                            Forms\Components\TextInput::make('phone')
                                ->label('Telefone')
                                ->maxLength(30),
                            Forms\Components\TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('website')
                                ->label('Site')
                                ->maxLength(255),
                            Forms\Components\Select::make('category_ids')
                                ->label('Categorias')
                                ->options(VendorCategory::query()->orderBy('sort')->pluck('name', 'id'))
                                ->multiple()
                                ->required(),
                        ])
                        ->createOptionUsing(function (array $data) use ($resolveWeddingId): string {
                            $weddingId = $resolveWeddingId();
                            $wedding = $weddingId ? Wedding::find($weddingId) : null;

                            if (! $wedding) {
                                throw new \RuntimeException('Casamento não encontrado para criação do fornecedor.');
                            }

                            $vendor = app(WeddingVendorService::class)->createOrUpdateForWedding($wedding, $data);

                            return $vendor->id;
                        }),

                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'negotiation' => 'Em negociação',
                            'approved' => 'Aprovado',
                            'rejected' => 'Rejeitado',
                        ])
                        ->default('negotiation')
                        ->required(),

                    Forms\Components\TextInput::make('value')
                        ->label('Valor')
                        ->prefix('R$')
                        ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                        ->required()
                        ->dehydrateStateUsing(fn ($state): ?string => static::normalizeMoneyForStorage($state)),

                    Forms\Components\DatePicker::make('valid_until')
                        ->label('Validade')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->placeholder('dd/mm/aaaa'),

                    Forms\Components\Textarea::make('notes')
                        ->label('Observações')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ];
    }

    public static function taskTableColumns(bool $includePlanColumn = true): array
    {
        $columns = [
            Tables\Columns\TextColumn::make('title')
                ->label('Título')
                ->searchable()
                ->sortable(),
        ];

        if ($includePlanColumn) {
            $columns[] = Tables\Columns\TextColumn::make('plan.title')
                ->label('Planejamento')
                ->toggleable();
        }

        return [
            ...$columns,
            Tables\Columns\TextColumn::make('category.name')
                ->label('Categoria')
                ->toggleable(),

            Tables\Columns\TextColumn::make('effective_status')
                ->label('Status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'overdue' => 'danger',
                    'pending' => 'warning',
                    'in_progress' => 'info',
                    'completed' => 'success',
                    'cancelled' => 'gray',
                    default => 'gray',
                })
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'overdue' => 'Atrasada',
                    'pending' => 'Pendente',
                    'in_progress' => 'Em Andamento',
                    'completed' => 'Concluída',
                    'cancelled' => 'Cancelada',
                    default => $state,
                }),

            Tables\Columns\TextColumn::make('start_date')
                ->label('Início')
                ->date('d/m/Y')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('due_date')
                ->label('Data Limite')
                ->date('d/m/Y')
                ->sortable(),

            Tables\Columns\TextColumn::make('estimated_value')
                ->label('Estimado')
                ->formatStateUsing(fn ($state): ?string => static::formatMoneyForTable($state))
                ->toggleable(),

            Tables\Columns\TextColumn::make('actual_value')
                ->label('Real')
                ->formatStateUsing(fn ($state): ?string => static::formatMoneyForTable($state))
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('assignedUser.name')
                ->label('Responsável')
                ->placeholder('Não atribuído'),
        ];
    }

    public static function taskTableFilters(?Closure $resolveWeddingId = null): array
    {
        $resolveWeddingId ??= fn (): ?string => static::resolveCurrentWeddingId();

        return [
            Tables\Filters\SelectFilter::make('status')
                ->label('Status')
                ->options([
                    'overdue' => 'Atrasada',
                    'pending' => 'Pendente',
                    'in_progress' => 'Em Andamento',
                    'completed' => 'Concluída',
                    'cancelled' => 'Cancelada',
                ])
                ->query(function (Builder $query, array $data) use ($resolveWeddingId): Builder {
                    $value = $data['value'] ?? null;

                    if (!$value) {
                        return $query;
                    }

                    if ($value !== 'overdue') {
                        return $query->where('status', $value);
                    }

                    $today = now(static::resolveWeddingTimezone($resolveWeddingId()))->toDateString();

                    return $query
                        ->whereDate('due_date', '<', $today)
                        ->whereNotIn('status', ['completed', 'cancelled']);
                }),

            Tables\Filters\SelectFilter::make('assigned_to')
                ->label('Responsável')
                ->options(function () use ($resolveWeddingId) {
                    return static::getAssignableUsersOptions($resolveWeddingId());
                }),

            Tables\Filters\SelectFilter::make('task_category_id')
                ->label('Categoria')
                ->options(TaskCategory::orderBy('sort')->pluck('name', 'id')),

            Tables\Filters\Filter::make('due_date_range')
                ->label('Faixa de Data')
                ->form([
                    Forms\Components\DatePicker::make('from')
                        ->label('De'),
                    Forms\Components\DatePicker::make('to')
                        ->label('Até'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('due_date', '>=', $date))
                        ->when($data['to'] ?? null, fn (Builder $q, $date) => $q->whereDate('due_date', '<=', $date));
                }),

            Tables\Filters\Filter::make('value_range')
                ->label('Faixa de Valor')
                ->form([
                    Forms\Components\TextInput::make('min')
                        ->label('Mínimo')
                        ->prefix('R$')
                        ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                        ->dehydrateStateUsing(fn ($state): ?string => static::normalizeMoneyForStorage($state)),
                    Forms\Components\TextInput::make('max')
                        ->label('Máximo')
                        ->prefix('R$')
                        ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                        ->dehydrateStateUsing(fn ($state): ?string => static::normalizeMoneyForStorage($state)),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['min'] ?? null, fn (Builder $q, $value) => $q->where('estimated_value', '>=', $value))
                        ->when($data['max'] ?? null, fn (Builder $q, $value) => $q->where('estimated_value', '<=', $value));
                }),
        ];
    }

    public static function getWeddingPlanOptions(?string $weddingId): array
    {
        if (!$weddingId) {
            return [];
        }

        $query = WeddingPlan::query()->where('wedding_id', $weddingId);

        if (!auth()->user()?->isAdmin()) {
            $query->whereNull('archived_at');
        }

        return $query->orderBy('title')->pluck('title', 'id')->all();
    }

    public static function getAssignableUsersOptions(?string $weddingId): array
    {
        if (!$weddingId) {
            return [];
        }

        return User::whereHas('weddings', function (Builder $query) use ($weddingId) {
            $query->where('wedding_id', $weddingId)
                ->whereIn('wedding_user.role', ['couple', 'organizer']);
        })->pluck('name', 'id')->all();
    }

    public static function resolveCurrentWeddingId(): ?string
    {
        return auth()->user()?->current_wedding_id ?? session('filament_wedding_id');
    }

    public static function resolveWeddingTimezone(?string $weddingId): string
    {
        $timezone = config('app.timezone');

        if (!$weddingId) {
            return $timezone;
        }

        $wedding = Wedding::find($weddingId);

        return $wedding?->getSetting('timezone', $timezone) ?? $timezone;
    }

    private static function formatMoneyForInput(mixed $state): ?string
    {
        if ($state === null || $state === '') {
            return null;
        }

        return number_format((float) $state, 2, ',', '.');
    }

    private static function normalizeMoneyForStorage(mixed $state): ?string
    {
        if ($state === null || $state === '') {
            return null;
        }

        $value = preg_replace('/[^\d,\.]/', '', (string) $state) ?? '';

        if ($value === '') {
            return null;
        }

        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        if (!is_numeric($value)) {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    private static function normalizeBudgetDraftData(array $data): array
    {
        $vendorId = $data['wedding_vendor_id'] ?? null;
        $vendorName = null;

        if ($vendorId) {
            $vendorName = WeddingVendor::query()->whereKey($vendorId)->value('name');
        }

        return [
            'wedding_vendor_id' => $vendorId,
            'wedding_vendor_name' => $vendorName,
            'status' => $data['status'] ?? 'negotiation',
            'value' => static::normalizeMoneyForStorage($data['value'] ?? null),
            'valid_until' => $data['valid_until'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    private static function formatBudgetItemLabel(array $state): string
    {
        $vendorName = trim((string) ($state['wedding_vendor_name'] ?? ''));

        if ($vendorName === '') {
            $vendorName = 'Fornecedor não informado';
        }

        $status = static::formatBudgetStatusLabel($state['status'] ?? null);
        $value = static::formatMoneyForTable($state['value'] ?? null) ?? 'R$ 0,00';

        return "{$vendorName} · {$status} · {$value}";
    }

    private static function formatBudgetStatusLabel(?string $status): string
    {
        return match ($status) {
            'approved' => 'Aprovado',
            'rejected' => 'Rejeitado',
            default => 'Em negociação',
        };
    }

    private static function formatMoneyForTable(mixed $state): ?string
    {
        if ($state === null || $state === '') {
            return null;
        }

        if (!is_numeric($state)) {
            return (string) $state;
        }

        return 'R$ ' . number_format((float) $state, 2, ',', '.');
    }
}
