<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RepairResource\Pages;
use App\Models\Part;
use App\Models\Repair;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RepairResource extends Resource
{
    protected static ?string $model = Repair::class;

    protected static ?string $navigationLabel = 'Ремонти';
    protected static ?string $pluralModelLabel = 'Ремонти';
    protected static ?string $modelLabel = 'Ремонт';
    protected static ?string $navigationGroup = 'Сервіс';
    protected static ?int $navigationSort = 30;
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Клієнт')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Клієнт')
                            ->relationship('customer', 'phone')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('phone')
                                    ->label('Телефон')
                                    ->tel()
                                    ->required()
                                    ->unique('customers', 'phone'),
                                Forms\Components\TextInput::make('name')
                                    ->label('Ім\'я'),
                            ])
                            ->getOptionLabelUsing(function ($value) {
                                $customer = \App\Models\Customer::find($value);
                                if ($customer) {
                                    return $customer->phone . ($customer->name ? ' - ' . $customer->name : '');
                                }
                                return '';
                            }),
                    ]),
                
                Forms\Components\Section::make('Інформація про телефон')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('phone_model')
                                    ->label('Модель телефону')
                                    ->required()
                                    ->placeholder('напр. iPhone 13 Pro'),
                                Forms\Components\TextInput::make('imei')
                                    ->label('IMEI')
                                    ->placeholder('необов\'язково'),
                            ]),
                        Forms\Components\Textarea::make('problem_description')
                            ->label('Опис проблеми')
                            ->placeholder('Що зламалося? Що потрібно зробити?')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                
                Forms\Components\Section::make('Деталі для ремонту')
                    ->schema([
                        Forms\Components\Repeater::make('parts')
                            ->label('Деталі')
                            ->schema([
                                Forms\Components\Select::make('part_id')
                                    ->label('Деталь')
                                    ->options(function () {
                                        return Part::where('status', 'Stock')
                                            ->where('quantity', '>', 0)
                                            ->whereHas('partType', function ($query) {
                                                $query->where('name', 'not like', '%Аксесуар%')
                                                      ->where('name', 'not like', '%Інвентар%')
                                                      ->where('name', 'not like', '%Розхідник%');
                                            })
                                            ->with('partType')
                                            ->get()
                                            ->mapWithKeys(function ($part) {
                                                return [$part->id => $part->name . ' (₴' . number_format($part->cost_uah, 2) . ' | ' . $part->quantity . ' шт.)'];
                                            });
                                    })
                                    ->required()
                                    ->searchable()
                                    ->reactive()
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state, $livewire) {
                                        if ($state) {
                                            $part = Part::find($state);
                                            if ($part) {
                                                $set('quantity', 1);
                                                $set('cost_per_unit', $part->cost_uah);
                                            }
                                        }
                                    }),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Кількість')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1)
                                    ->maxValue(function (Forms\Get $get) {
                                        $partId = $get('../part_id');
                                        if ($partId) {
                                            $part = Part::find($partId);
                                            return $part ? $part->quantity : 999;
                                        }
                                        return 999;
                                    })
                                    ->reactive()
                                    ->live(),
                                Forms\Components\TextInput::make('cost_per_unit')
                                    ->label('Собівартість за одиницю (грн)')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->prefix('₴')
                                    ->disabled()
                                    ->dehydrated(),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Додати деталь')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(function (array $state): ?string {
                                if (!empty($state['part_id'])) {
                                    $part = Part::find($state['part_id']);
                                    if ($part) {
                                        $qty = $state['quantity'] ?? 1;
                                        return "{$part->name} (x{$qty})";
                                    }
                                }
                                return null;
                            }),
                    ]),
                
                Forms\Components\Section::make('Вартість та прибуток')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('repair_cost')
                                    ->label('Вартість ремонту (грн)')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->prefix('₴')
                                    ->reactive()
                                    ->live(),
                                Forms\Components\Placeholder::make('parts_cost_display')
                                    ->label('Собівартість деталей')
                                    ->content(function (Forms\Get $get) {
                                        $parts = $get('parts') ?? [];
                                        $total = 0;
                                        foreach ($parts as $part) {
                                            if (!empty($part['part_id']) && !empty($part['quantity']) && !empty($part['cost_per_unit'])) {
                                                $total += ($part['cost_per_unit'] * $part['quantity']);
                                            }
                                        }
                                        return number_format($total, 2) . ' грн.';
                                    })
                                    ->live(),
                                Forms\Components\Placeholder::make('profit_display')
                                    ->label('Прибуток')
                                    ->content(function (Forms\Get $get) {
                                        $repairCost = (float) ($get('repair_cost') ?? 0);
                                        $parts = $get('parts') ?? [];
                                        $partsCost = 0;
                                        foreach ($parts as $part) {
                                            if (!empty($part['part_id']) && !empty($part['quantity']) && !empty($part['cost_per_unit'])) {
                                                $partsCost += ($part['cost_per_unit'] * $part['quantity']);
                                            }
                                        }
                                        $profit = $repairCost - $partsCost;
                                        $color = $profit >= 0 ? 'success' : 'danger';
                                        return '<span class="text-' . $color . '-600 font-bold">' . number_format($profit, 2) . ' грн.</span>';
                                    })
                                    ->live(),
                            ]),
                        Forms\Components\TextInput::make('parts_cost')
                            ->label('Собівартість деталей (автоматично)')
                            ->hidden()
                            ->dehydrated(),
                        Forms\Components\TextInput::make('profit')
                            ->label('Прибуток (автоматично)')
                            ->hidden()
                            ->dehydrated(),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'В очікуванні',
                                'in_progress' => 'В роботі',
                                'completed' => 'Виконано',
                                'issued' => 'Видано клієнту',
                            ])
                            ->default('pending')
                            ->required()
                            ->native(false),
                        Forms\Components\Textarea::make('description')
                            ->label('Виконані роботи / Коментар')
                            ->placeholder('Що було виконано під час ремонту? Які дії були зроблені?')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function calculateProfit(Forms\Get $get, Forms\Set $set): void
    {
        $repairCost = (float) ($get('repair_cost') ?? 0);
        $parts = $get('parts') ?? [];
        $partsCost = 0;
        
        foreach ($parts as $part) {
            if (!empty($part['part_id']) && !empty($part['quantity']) && !empty($part['cost_per_unit'])) {
                $partsCost += ($part['cost_per_unit'] * $part['quantity']);
            }
        }
        
        $profit = $repairCost - $partsCost;
        
        $set('parts_cost', round($partsCost, 2));
        $set('profit', round($profit, 2));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Клієнт')
                    ->formatStateUsing(function ($record) {
                        return $record->customer->name ?? $record->customer->phone;
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('customer', function (Builder $query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                  ->orWhere('phone', 'like', "%{$search}%");
                        });
                    })
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('phone_model')
                    ->label('Модель')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('imei')
                    ->label('IMEI')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('repair_cost')
                    ->label('Вартість ремонту')
                    ->money('UAH')
                    ->sortable()
                    ->color('success')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('parts_cost')
                    ->label('Собівартість деталей')
                    ->money('UAH')
                    ->sortable()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('profit')
                    ->label('Прибуток')
                    ->money('UAH')
                    ->badge()
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('problem_description')
                    ->label('Опис проблеми')
                    ->limit(50)
                    ->tooltip(function ($record) {
                        return $record->problem_description;
                    })
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => 'В очікуванні',
                        'in_progress' => 'В роботі',
                        'completed' => 'Виконано',
                        'issued' => 'Видано',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'gray',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'issued' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'В очікуванні',
                        'in_progress' => 'В роботі',
                        'completed' => 'Виконано',
                        'issued' => 'Видано клієнту',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('З'),
                        Forms\Components\DatePicker::make('until')->label('По'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Перегляд'),
                Tables\Actions\EditAction::make()
                    ->label('Редагувати'),
                Tables\Actions\DeleteAction::make()
                    ->label('Видалити'),
            ])
            ->defaultSort('created_at', 'desc')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Видалити'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRepairs::route('/'),
            'create' => Pages\CreateRepair::route('/create'),
            'view' => Pages\ViewRepair::route('/{record}'),
            'edit' => Pages\EditRepair::route('/{record}/edit'),
        ];
    }
}
