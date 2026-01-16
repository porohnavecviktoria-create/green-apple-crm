<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartResource\Pages;
use App\Filament\Resources\PartResource\RelationManagers;
use App\Models\Part;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PartResource extends Resource
{
    protected static ?string $model = Part::class;

    protected static ?string $navigationLabel = 'Деталі';
    protected static ?string $pluralModelLabel = 'Запчастини';
    protected static ?string $modelLabel = 'Запчастина';
    protected static ?string $navigationGroup = 'Склад';
    protected static ?int $navigationSort = 12;
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('partType', function ($query) {
                $query->where('name', 'not like', '%Аксесуар%')
                    ->where('name', 'not like', '%Інвентар%')
                    ->where('name', 'not like', '%Розхідник%')
                    ->where('name', 'not like', '%Викрутка%')
                    ->where('name', 'not like', '%Паяльник%')
                    ->where('name', 'not like', '%Переклей%')
                    ->where('name', 'not like', '%Чохол%');
            })
            ->where(function ($query) {
                // Показуємо деталі з кількістю > 0 АБО дисплеї (завжди)
                $query->where('quantity', '>', 0)
                    ->orWhereHas('partType', function ($q) {
                        $q->where('name', 'like', '%Дисплей%')
                          ->orWhere('name', 'like', '%дисплей%')
                          ->orWhere('name', 'like', '%екран%');
                    });
            })
            ->with('partType');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Інформація про запчастину')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Назва')
                                    ->required()
                                    ->placeholder('напр. Дисплей iPhone 13 Pro'),
                                Forms\Components\Select::make('part_type_id')
                                    ->label('Тип запчастини')
                                    ->relationship('partType', 'name', function ($query) {
                                        $query->where('name', 'not like', '%Аксесуар%')
                                              ->where('name', 'not like', '%Інвентар%')
                                              ->where('name', 'not like', '%Розхідник%')
                                              ->where('name', 'not like', '%Викрутка%')
                                              ->where('name', 'not like', '%Паяльник%')
                                              ->where('name', 'not like', '%Переклей%')
                                              ->where('name', 'not like', '%Чохол%')
                                              ->orderBy('name', 'asc');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')->label('Назва типу')->required(),
                                    ]),
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
                                    ->default(function ($livewire) {
                                        if (method_exists($livewire, 'getActiveTab')) {
                                            $tab = $livewire->getActiveTab();
                                            if ($tab === 'restoration')
                                                return 'Restore';
                                            if ($tab === 'broken')
                                                return 'Broken';
                                        }
                                        return 'Stock';
                                    }),
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
                    ->formatStateUsing(fn(Part $record) => $record->type_label)
                    ->searchable()
                    ->badge()
                    ->color(fn(Part $record) => str_contains($record->name, 'Відновлен') ? 'info' : 'gray')
                    ->weight('bold')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Назва')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state, Part $record) {
                        // Прибираємо все в дужках з назви
                        $cleanName = preg_replace('/\s*\([^)]*\)\s*/', '', $state);
                        // Додаємо емодзі для відновлених деталей
                        return str_contains($state, 'Відновлен') ? "✨ {$cleanName}" : $cleanName;
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
                    ->label('Тип запчастини')
                    ->relationship('partType', 'name', function ($query) {
                        $query->where('name', 'not like', '%Аксесуар%')
                              ->where('name', 'not like', '%Інвентар%')
                              ->where('name', 'not like', '%Розхідник%')
                              ->where('name', 'not like', '%Викрутка%')
                              ->where('name', 'not like', '%Паяльник%')
                              ->where('name', 'not like', '%Переклей%')
                              ->where('name', 'not like', '%Чохол%')
                              ->orderBy('name', 'asc');
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
                            ->label('Деталь')
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
                                    ->helperText('Введіть кількість деталей, які надходять на склад')
                                    ->live(),
                                Forms\Components\TextInput::make('cost_per_unit')
                                    ->label('Ціна за 1 шт. (грн)')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->minValue(0)
                                    ->prefix('₴')
                                    ->helperText('Введіть ціну за одиницю нових деталей')
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
                Tables\Actions\Action::make('finalize_restoration')
                    ->label('🔧 Полагодити (Реставрація)')
                    ->color('warning')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->form([
                        Forms\Components\Section::make('Створення готової деталі')
                            ->description('Виберіть, що саме ви отримаєте в результаті та які матеріали використали.')
                            ->schema([
                                Forms\Components\TextInput::make('new_name')
                                    ->label('Назва готової деталі')
                                    ->default(fn(Part $record) => "Відновлений " . trim(str_replace(['Битий ', 'До відновлення '], '', $record->name)))
                                    ->required(),
                                Forms\Components\Select::make('part_type_id')
                                    ->label('Тип готової деталі')
                                    ->relationship('partType', 'name', function ($query) {
                                        $query->where('name', 'not like', '%Аксесуар%')
                                              ->where('name', 'not like', '%Інвентар%')
                                              ->where('name', 'not like', '%Розхідник%');
                                    })
                                    ->required()
                                    ->default(fn(Part $record) => $record->part_type_id),
                                Forms\Components\Repeater::make('components')
                                    ->label('Використані матеріали зі складу')
                                    ->schema([
                                        Forms\Components\Select::make('part_id')
                                            ->label('Деталь')
                                            ->options(function () {
                                                return Part::where('status', 'Stock')
                                                    ->where('quantity', '>', 0)
                                                    ->with('partType')
                                                    ->get()
                                                    ->filter(function ($part) {
                                                        $typeName = $part->partType->name ?? '';
                                                        return strpos($typeName, 'Аксесуар') === false &&
                                                               strpos($typeName, 'Інвентар') === false &&
                                                               strpos($typeName, 'Розхідник') === false &&
                                                               strpos($typeName, 'Викрутка') === false &&
                                                               strpos($typeName, 'Паяльник') === false &&
                                                               strpos($typeName, 'Переклей') === false &&
                                                               strpos($typeName, 'Чохол') === false;
                                                    })
                                                    ->sortBy(function ($part) {
                                                        return $part->partType->name ?? '';
                                                    })
                                                    ->pluck('name', 'id');
                                            })
                                    ->searchable()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                                if ($state) {
                                                    $part = Part::find($state);
                                                    if ($part && $part->quantity > 0) {
                                                        $set('quantity', 1);
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
                                            ->live()
                                            ->dehydrated(),
                                    ])
                                    ->defaultItems(0)
                                    ->addActionLabel('➕ Додати деталь')
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
                                    })
                                    ->helperText('Виберіть запчастини, які ви використали для ремонту, та їх кількість'),
                                Forms\Components\TextInput::make('additional_cost')
                                    ->label('Додаткові витрати (робота тощо)')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('₴'),
                            ])
                    ])
                    ->action(function (Part $record, array $data) {
                        // 1. Отримуємо компоненти з кількістю
                        $componentsData = $data['components'] ?? [];
                        $componentsCost = 0;
                        $componentsList = [];
                        $componentsIds = [];

                        foreach ($componentsData as $compData) {
                            if (!empty($compData['part_id']) && !empty($compData['quantity'])) {
                                $comp = Part::find($compData['part_id']);
                                if ($comp) {
                                    $qty = (int) $compData['quantity'];
                                    $componentsCost += $comp->cost_uah * $qty;
                                    $componentsList[] = "{$comp->name} (x{$qty})";
                                    $componentsIds[] = $comp->id;
                                }
                            }
                        }

                        // 2. Рахуємо загальну собівартість: ціна битого + ціна всіх штук компонентів + робота
                        $totalCost = $record->cost_uah + $componentsCost + (float) ($data['additional_cost'] ?? 0);

                        // 3. Створюємо нову готову деталь
                        $newPart = Part::create([
                            'name' => $data['new_name'],
                            'part_type_id' => $data['part_type_id'],
                            'cost_uah' => $totalCost,
                            'quantity' => 1,
                            'contractor_id' => $record->contractor_id,
                            'status' => 'Stock',
                            'description' => "Відновлено з: {$record->name}. Використано компонентів: " . implode(', ', $componentsList),
                        ]);

                        // 4. Списуємо биту деталь (зменшуємо кількість)
                        $record->decrement('quantity');
                        if ($record->quantity <= 0) {
                            $record->update(['status' => 'Broken']);
                        }

                        // 5. Списуємо використані компоненти з урахуванням кількості
                        foreach ($componentsData as $compData) {
                            if (!empty($compData['part_id']) && !empty($compData['quantity'])) {
                                $comp = Part::find($compData['part_id']);
                                if ($comp) {
                                    $qty = (int) $compData['quantity'];
                                    $comp->decrement('quantity', $qty);
                                }
                            }
                        }

                        // 6. Зв'язуємо для історії (в нову деталь додаємо биту і компоненти як subParts)
                        $idsToAttach = array_merge([$record->id], $componentsIds);
                        $newPart->subParts()->attach($idsToAttach);

                        \Filament\Notifications\Notification::make()
                            ->title('Реставрація завершена успішно! ✅')
                            ->body("Нову деталь '{$newPart->name}' додано на склад. Собівартість: {$totalCost} грн.")
                            ->success()
                            ->seconds(5)
                            ->send();
                    })
                    ->visible(fn(Part $record) => $record->status === 'Restore' && $record->quantity > 0),
                Tables\Actions\Action::make('write_off')
                    ->label('❌ Списати')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->form([
                        Forms\Components\Placeholder::make('part_info')
                            ->label('Деталь')
                            ->content(fn(Part $record) => $record->name),
                        Forms\Components\Placeholder::make('current_quantity')
                            ->label('Поточна кількість')
                            ->content(fn(Part $record) => $record->quantity . ' шт.'),
                        Forms\Components\TextInput::make('quantity_to_write_off')
                            ->label('Кількість до списання')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(fn(Part $record) => $record->quantity)
                            ->helperText('Введіть кількість деталей, які потрібно списати'),
                        Forms\Components\Textarea::make('reason')
                            ->label('Причина списання')
                            ->placeholder('Наприклад: деталь зламалася під час реставрації')
                            ->required(),
                    ])
                    ->action(function (Part $record, array $data) {
                        $qtyToWriteOff = (int) $data['quantity_to_write_off'];
                        $reason = $data['reason'] ?? '';
                        $costPerUnit = $record->cost_uah;
                        $totalCost = $qtyToWriteOff * $costPerUnit;
                        
                        // Якщо списуємо всю кількість - змінюємо статус на Broken, але залишаємо кількість
                        if ($qtyToWriteOff >= $record->quantity) {
                            $record->update([
                                'status' => 'Broken',
                                'description' => trim(($record->description ?? '') . "\nСписано: {$reason} (" . now()->format('d.m.Y H:i') . ")")
                            ]);
                        } else {
                            // Списуємо частину - зменшуємо кількість оригінальної деталі
                            $record->decrement('quantity', $qtyToWriteOff);
                            
                            // Створюємо запис про списану деталь з правильною кількістю
                            \App\Models\Part::create([
                                'name' => $record->name . ' (Списано)',
                                'part_type_id' => $record->part_type_id,
                                'cost_uah' => $costPerUnit,
                                'quantity' => $qtyToWriteOff, // Кількість яку списали
                                'contractor_id' => $record->contractor_id,
                                'status' => 'Broken',
                                'description' => "Списано з: {$record->name}\nПричина: {$reason}\nДата: " . now()->format('d.m.Y H:i'),
                            ]);
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Деталь списана! ❌')
                            ->body("Списано {$qtyToWriteOff} шт. {$record->name}. Сума списання: " . number_format($totalCost, 2) . " грн.")
                            ->success()
                            ->seconds(5)
                            ->send();
                    })
                    ->visible(fn(Part $record) => ($record->status === 'Restore' || $record->status === 'Stock') && $record->quantity > 0),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListParts::route('/'),
        ];
    }
}
