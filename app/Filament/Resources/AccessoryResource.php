<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccessoryResource\Pages;
use App\Filament\Resources\AccessoryResource\RelationManagers;
use App\Models\Accessory;
use App\Models\Part;
use App\Models\PartType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccessoryResource extends Resource
{
    protected static ?string $model = Part::class;

    protected static ?string $navigationLabel = 'Аксесуари';
    protected static ?string $pluralModelLabel = 'Аксесуари';
    protected static ?string $modelLabel = 'Аксесуар';
    protected static ?string $navigationGroup = 'Склад';
    protected static ?int $navigationSort = 13;
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    public static function getEloquentQuery(): Builder
    {
        // Для аксесуарів не фільтруємо по quantity - показуємо навіть з 0 кількістю
        return parent::getEloquentQuery()
            ->whereHas('partType', function ($query) {
                $query->where('name', 'like', '%Аксесуар%');
            });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Інформація про аксесуар')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Назва')
                                    ->required()
                                    ->placeholder('напр. Чохол iPhone 13 Pro'),
                                Forms\Components\Select::make('part_type_id')
                                    ->label('Тип аксесуара')
                                    ->options(function () {
                                        return \App\Models\PartType::where('name', 'like', '%Аксесуар%')
                                            ->orderBy('name', 'asc')
                                            ->get()
                                            ->mapWithKeys(function ($partType) {
                                                // Прибираємо слово "Аксесуар" з назви для відображення
                                                $displayName = str_replace('Аксесуар', '', $partType->name);
                                                $displayName = str_replace('аксесуар', '', $displayName);
                                                $displayName = trim($displayName);
                                                return [$partType->id => $displayName];
                                            });
                                    })
                                    ->required()
                                    ->searchable()
                                    ->getSearchResultsUsing(function (string $search) {
                                        return \App\Models\PartType::where('name', 'like', '%Аксесуар%')
                                            ->where('name', 'like', "%{$search}%")
                                            ->orderBy('name', 'asc')
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(function ($partType) {
                                                // Прибираємо слово "Аксесуар" з назви для відображення
                                                $displayName = str_replace('Аксесуар', '', $partType->name);
                                                $displayName = str_replace('аксесуар', '', $displayName);
                                                $displayName = trim($displayName);
                                                return [$partType->id => $displayName];
                                            });
                                    })
                                    ->getOptionLabelUsing(function ($value) {
                                        if (!$value) {
                                            return null;
                                        }
                                        $partType = \App\Models\PartType::find($value);
                                        if (!$partType) {
                                            return $value;
                                        }
                                        // Прибираємо слово "Аксесуар" з назви для відображення
                                        $displayName = str_replace('Аксесуар', '', $partType->name);
                                        $displayName = str_replace('аксесуар', '', $displayName);
                                        return trim($displayName);
                                    })
                                    ->formatStateUsing(function ($state) {
                                        if (!$state) {
                                            return null;
                                        }
                                        $partType = \App\Models\PartType::find($state);
                                        if (!$partType) {
                                            return $state;
                                        }
                                        // Прибираємо слово "Аксесуар" з назви для відображення вибраного значення
                                        $displayName = str_replace('Аксесуар', '', $partType->name);
                                        $displayName = str_replace('аксесуар', '', $displayName);
                                        return trim($displayName);
                                    })
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')->label('Назва типу')->required(),
                                    ])
                                    ->createOptionUsing(function (array $data): int {
                                        // Додаємо "Аксесуар" до назви при створенні, якщо його немає
                                        $name = $data['name'];
                                        if (stripos($name, 'Аксесуар') === false && stripos($name, 'аксесуар') === false) {
                                            $name = 'Аксесуар ' . $name;
                                        }
                                        $partType = \App\Models\PartType::create([
                                            'name' => $name,
                                        ]);
                                        return $partType->id;
                                    })
                                    ->live(),
                                Forms\Components\TextInput::make('cost_uah')
                                    ->label('Собівартість (грн)')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('₴'),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Кількість')
                                    ->required()
                                    ->numeric()
                                    ->default(1),
                                Forms\Components\Select::make('contractor_id')
                                    ->label('Контрагент (Постачальник)')
                                    ->relationship('contractor', 'name')
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\TextInput::make('serial_number')
                                    ->label('Серійний номер (S/N)')
                                    ->placeholder('необов\'язково'),
                                Forms\Components\Select::make('status')
                                    ->label('Статус')
                                    ->options([
                                        'Stock' => '✅ На складі',
                                        'Restore' => '🛠 До відновлення',
                                        'Installed' => '📱 Встановлено',
                                        'Broken' => '❌ Брак/Зіпсовано',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->default('Stock'),
                            ]),
                        Forms\Components\Textarea::make('description')
                            ->label('Додатковий опис')
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('partType.name', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('partType.name')
                    ->label('Тип')
                    ->formatStateUsing(function (Part $record) {
                        $typeLabel = $record->type_label;
                        // Прибираємо слово "Аксесуар" з назви типу
                        $typeLabel = str_replace('Аксесуар', '', $typeLabel);
                        $typeLabel = str_replace('аксесуар', '', $typeLabel);
                        $typeLabel = trim($typeLabel);
                        return $typeLabel;
                    })
                    ->searchable()
                    ->badge()
                    ->color('gray')
                    ->weight('bold')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Назва')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        // Прибираємо все в дужках з назви
                        return preg_replace('/\s*\([^)]*\)\s*/', '', $state);
                    })
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('cost_uah')
                    ->label('Ціна (грн)')
                    ->money('UAH')
                    ->color('gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Кількість')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('contractor.name')
                    ->label('Постачальник')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('gray'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'Stock' => 'На складі',
                        'Restore' => 'До відновлення',
                        'Installed' => 'Встановлено',
                        'Broken' => 'Брак',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'Stock' => 'success',
                        'Restore' => 'warning',
                        'Installed' => 'info',
                        'Broken' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('part_type_id')
                    ->label('Тип аксесуара')
                    ->options(function () {
                        return \App\Models\PartType::where('name', 'like', '%Аксесуар%')
                            ->orderBy('name', 'asc')
                            ->get()
                            ->mapWithKeys(function ($partType) {
                                // Прибираємо слово "Аксесуар" з назви для відображення
                                $displayName = str_replace('Аксесуар', '', $partType->name);
                                $displayName = str_replace('аксесуар', '', $displayName);
                                $displayName = trim($displayName);
                                return [$partType->id => $displayName];
                            });
                    })
                    ->getSearchResultsUsing(function (string $search) {
                        return \App\Models\PartType::where('name', 'like', '%Аксесуар%')
                            ->where('name', 'like', "%{$search}%")
                            ->orderBy('name', 'asc')
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($partType) {
                                // Прибираємо слово "Аксесуар" з назви для відображення
                                $displayName = str_replace('Аксесуар', '', $partType->name);
                                $displayName = str_replace('аксесуар', '', $displayName);
                                $displayName = trim($displayName);
                                return [$partType->id => $displayName];
                            });
                    })
                    ->getOptionLabelUsing(function ($value) {
                        $partType = \App\Models\PartType::find($value);
                        if (!$partType) {
                            return $value;
                        }
                        // Прибираємо слово "Аксесуар" з назви для відображення
                        $displayName = str_replace('Аксесуар', '', $partType->name);
                        $displayName = str_replace('аксесуар', '', $displayName);
                        return trim($displayName);
                    })
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'Stock' => '✅ На складі',
                        'Restore' => '🛠 До відновлення',
                        'Installed' => '📱 Встановлено',
                        'Broken' => '❌ Брак',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('receive')
                    ->label('📦 Поступлення')
                    ->color('success')
                    ->icon('heroicon-o-plus-circle')
                    ->form([
                        Forms\Components\Placeholder::make('part_info')
                            ->label('Аксесуар')
                            ->content(fn(Part $record) => $record->name),
                        Forms\Components\Placeholder::make('current_quantity')
                            ->label('Поточна кількість')
                            ->content(fn(Part $record) => $record->quantity . ' шт.'),
                        Forms\Components\Placeholder::make('current_cost')
                            ->label('Поточна собівартість (1 шт.)')
                            ->content(fn(Part $record) => number_format($record->cost_uah, 2) . ' грн.'),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('quantity_to_add')
                                    ->label('Кількість до додавання')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1)
                                    ->helperText('Введіть кількість аксесуарів, які надходять на склад')
                                    ->live(),
                                Forms\Components\TextInput::make('cost_per_unit')
                                    ->label('Ціна за 1 шт. (грн)')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->minValue(0)
                                    ->prefix('₴')
                                    ->helperText('Введіть ціну за одиницю нових аксесуарів')
                                    ->live(),
                            ]),
                        Forms\Components\Placeholder::make('new_average_cost')
                            ->label('Нова середня собівартість (1 шт.)')
                            ->content(function (Forms\Get $get, Part $record) {
                                $qtyToAdd = (float) ($get('quantity_to_add') ?? 0);
                                $newCost = (float) ($get('cost_per_unit') ?? 0);
                                
                                if ($qtyToAdd <= 0 || $newCost < 0) {
                                    return '—';
                                }
                                
                                $oldQty = $record->quantity;
                                $oldCost = $record->cost_uah;
                                
                                if ($oldQty + $qtyToAdd == 0) {
                                    return '—';
                                }
                                
                                // Середньозважена ціна: ((стара_кількість * стара_ціна) + (нова_кількість * нова_ціна)) / (стара_кількість + нова_кількість)
                                $totalOldValue = $oldQty * $oldCost;
                                $totalNewValue = $qtyToAdd * $newCost;
                                $averageCost = ($totalOldValue + $totalNewValue) / ($oldQty + $qtyToAdd);
                                
                                return number_format($averageCost, 2) . ' грн.';
                            }),
                    ])
                    ->action(function (Part $record, array $data) {
                        $qtyToAdd = (int) $data['quantity_to_add'];
                        $newCost = (float) $data['cost_per_unit'];
                        
                        $oldQty = $record->quantity;
                        $oldCost = $record->cost_uah;
                        
                        // Розраховуємо середньозважenu ціну
                        $totalOldValue = $oldQty * $oldCost;
                        $totalNewValue = $qtyToAdd * $newCost;
                        $averageCost = ($totalOldValue + $totalNewValue) / ($oldQty + $qtyToAdd);
                        
                        // Оновлюємо кількість та собівартість
                        $record->update([
                            'quantity' => $oldQty + $qtyToAdd,
                            'cost_uah' => round($averageCost, 2),
                            'description' => trim(($record->description ?? '') . "\nПоступлення: +{$qtyToAdd} шт. по {$newCost} грн. (" . now()->format('d.m.Y H:i') . ")")
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Поступлення оформлено! 📦')
                            ->body("Додано {$qtyToAdd} шт. по {$newCost} грн.\nНова кількість: {$record->quantity} шт.\nСередня собівартість: " . number_format($averageCost, 2) . " грн.")
                            ->success()
                            ->seconds(5)
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAccessories::route('/'),
        ];
    }
}
