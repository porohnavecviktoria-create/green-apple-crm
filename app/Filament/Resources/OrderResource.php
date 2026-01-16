<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationLabel = 'Новий продаж';
    protected static ?string $pluralModelLabel = 'Чеки';
    protected static ?string $modelLabel = 'Чек';
    protected static ?string $navigationGroup = 'Продажі';
    protected static ?int $navigationSort = 20;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function getNavigationUrl(): string
    {
        return static::getUrl('create');
    }

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
                            })
                            ->helperText('Введіть номер телефону, щоб знайти клієнта, або додайте нового'),
                    ]),

                Forms\Components\Section::make('Товари')
                    ->schema([
                        Forms\Components\Repeater::make('sales')
                            ->relationship()
                            ->defaultItems(1)
                            ->minItems(1)
                            ->label(false)
                            ->schema([
                                Forms\Components\Select::make('warehouse_type')
                                    ->label('Склад')
                                            ->options([
                                        'device' => 'Техніка',
                                        'accessory' => 'Аксесуари',
                                        'part' => 'Деталі',
                                        'inventory' => 'Інвентар',
                                        'consumable' => 'Розхідники',
                                            ])
                                            ->required()
                                    ->reactive()
                                    ->live()
                                    ->dehydrated()
                                    ->afterStateUpdated(function ($set) {
                                        $set('saleable_id', null);
                                        $set('sell_price', null);
                                        $set('buy_price', null);
                                    })
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('saleable_id')
                                    ->label('Назва/Модель (повна)')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->getSearchResultsUsing(function (string $search, $get) {
                                        $warehouseType = $get('warehouse_type');
                                        if (!$warehouseType) {
                                            return [];
                                        }

                                        $results = [];

                                        if ($warehouseType === 'device') {
                                            $devices = \App\Models\Device::where('status', 'Stock')
                                                ->where('model', 'like', "%{$search}%")
                                                ->orderBy('model')
                                                ->limit(50)
                                                ->get();
                                            foreach ($devices as $device) {
                                                $label = $device->model;
                                                if ($device->marker) $label .= ' | ' . $device->marker;
                                                if ($device->imei) $label .= ' | IMEI: ' . $device->imei;
                                                $results[$device->id] = $label;
                                            }
                                        } else {
                                            $query = \App\Models\Part::where('status', 'Stock')
                                                ->where('quantity', '>', 0)
                                                ->where('name', 'like', "%{$search}%");

                                            if ($warehouseType === 'part') {
                                                $query->whereHas('partType', function ($q) {
                                                    $q->where('name', 'not like', '%Аксесуар%')
                                                      ->where('name', 'not like', '%Інвентар%')
                                                      ->where('name', 'not like', '%Розхідник%');
                                                });
                                            } elseif ($warehouseType === 'accessory') {
                                                $query->whereHas('partType', function ($q) {
                                                    $q->where('name', 'like', '%Аксесуар%');
                                                });
                                            } elseif ($warehouseType === 'inventory') {
                                                $query->whereHas('partType', function ($q) {
                                                    $q->where('name', 'like', '%Інвентар%');
                                                });
                                            } elseif ($warehouseType === 'consumable') {
                                                $query->whereHas('partType', function ($q) {
                                                    $q->where('name', 'like', '%Розхідник%');
                                                });
                                            }

                                            $parts = $query->orderBy('name')->limit(50)->get();
                                            foreach ($parts as $part) {
                                                $label = $part->name;
                                                if ($part->quantity > 0) $label .= ' (Наявно: ' . $part->quantity . ' шт)';
                                                $results[$part->id] = $label;
                                            }
                                            
                                            // Якщо товар не знайдено і введено текст, додаємо опцію для створення нового
                                            if (empty($results) && !empty(trim($search)) && $warehouseType !== 'device') {
                                                $results['__new__' . $search] = '✨ Створити новий товар: ' . $search;
                                            }
                                        }

                                        return $results;
                                    })
                                    ->options(function ($get) {
                                        $warehouseType = $get('warehouse_type');
                                        if (!$warehouseType) {
                                            return [];
                                        }

                                        $results = [];

                                        if ($warehouseType === 'device') {
                                            $devices = \App\Models\Device::where('status', 'Stock')
                                                ->orderBy('model')
                                                ->limit(500)
                                                ->get();
                                            foreach ($devices as $device) {
                                                $label = $device->model;
                                                if ($device->marker) $label .= ' | ' . $device->marker;
                                                if ($device->imei) $label .= ' | IMEI: ' . $device->imei;
                                                $results[$device->id] = $label;
                                            }
                                        } else {
                                            $query = \App\Models\Part::where('status', 'Stock')
                                                ->where('quantity', '>', 0);

                                            if ($warehouseType === 'part') {
                                                $query->whereHas('partType', function ($q) {
                                                    $q->where('name', 'not like', '%Аксесуар%')
                                                      ->where('name', 'not like', '%Інвентар%')
                                                      ->where('name', 'not like', '%Розхідник%');
                                                });
                                            } elseif ($warehouseType === 'accessory') {
                                                $query->whereHas('partType', function ($q) {
                                                    $q->where('name', 'like', '%Аксесуар%');
                                                });
                                            } elseif ($warehouseType === 'inventory') {
                                                $query->whereHas('partType', function ($q) {
                                                    $q->where('name', 'like', '%Інвентар%');
                                                });
                                            } elseif ($warehouseType === 'consumable') {
                                                $query->whereHas('partType', function ($q) {
                                                    $q->where('name', 'like', '%Розхідник%');
                                                });
                                            }

                                            $parts = $query->orderBy('name')->limit(500)->get();
                                            foreach ($parts as $part) {
                                                $label = $part->name;
                                                if ($part->quantity > 0) $label .= ' (Наявно: ' . $part->quantity . ' шт)';
                                                $results[$part->id] = $label;
                                            }
                                        }

                                        return $results;
                                    })
                                    ->createOptionForm(function ($get) {
                                        $warehouseType = $get('warehouse_type');
                                        
                                        // Для техніки не додаємо можливість створення (занадто багато полів)
                                        if ($warehouseType === 'device') {
                                            return [];
                                        }
                                        
                                        // Для Part визначаємо тип на основі warehouse_type
                                        $partTypeQuery = \App\Models\PartType::query();
                                        
                                        if ($warehouseType === 'part') {
                                            $partTypeQuery->where('name', 'not like', '%Аксесуар%')
                                                         ->where('name', 'not like', '%Інвентар%')
                                                         ->where('name', 'not like', '%Розхідник%');
                                        } elseif ($warehouseType === 'accessory') {
                                            $partTypeQuery->where('name', 'like', '%Аксесуар%');
                                        } elseif ($warehouseType === 'inventory') {
                                            $partTypeQuery->where('name', 'like', '%Інвентар%');
                                        } elseif ($warehouseType === 'consumable') {
                                            $partTypeQuery->where('name', 'like', '%Розхідник%');
                                        }
                                        
                                        // Отримуємо перший тип за замовчуванням
                                        $defaultPartType = $partTypeQuery->first();
                                        
                                        return [
                                            Forms\Components\TextInput::make('name')
                                                ->label('Назва товару')
                                                ->required()
                                                ->placeholder('Введіть назву товару')
                                                ->autofocus()
                                                ->columnSpanFull(),
                                            Forms\Components\Select::make('part_type_id')
                                                ->label('Тип товару')
                                                ->options($partTypeQuery->pluck('name', 'id'))
                                                ->searchable()
                                                ->required()
                                                ->default($defaultPartType?->id)
                                                ->createOptionForm([
                                                    Forms\Components\TextInput::make('name')
                                                        ->label('Назва типу')
                                                        ->required()
                                                        ->autofocus(),
                                                ])
                                                ->createOptionUsing(function (array $data) {
                                                    return \App\Models\PartType::create($data)->id;
                                                }),
                                            Forms\Components\Grid::make(2)
                                                ->schema([
                                                    Forms\Components\TextInput::make('cost_uah')
                                                        ->label('Собівартість (грн)')
                                                        ->numeric()
                                                        ->required()
                                                        ->default(0)
                                                        ->prefix('₴'),
                                                    Forms\Components\TextInput::make('quantity')
                                                        ->label('Кількість')
                                                        ->numeric()
                                                        ->required()
                                                        ->default(1)
                                                        ->minValue(1),
                                                ]),
                                            Forms\Components\Hidden::make('status')
                                                ->default('Stock'),
                                        ];
                                    })
                                    ->createOptionUsing(function (array $data, $get) {
                                        $warehouseType = $get('warehouse_type');
                                        
                                        // Для техніки не створюємо
                                        if ($warehouseType === 'device') {
                                            return null;
                                        }
                                        
                                        // Створюємо новий Part
                                        $part = \App\Models\Part::create($data);
                                        return $part->id;
                                    })
                                    ->reactive()
                                    ->live()
                                    ->dehydrated()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        $warehouseType = $get('warehouse_type');
                                        
                                        // Якщо обрано опцію створення нового товару
                                        if (is_string($state) && str_starts_with($state, '__new__')) {
                                            $newItemName = substr($state, 7); // Видаляємо префікс "__new__"
                                            
                                            // Визначаємо тип товару
                                            $partTypeQuery = \App\Models\PartType::query();
                                            
                                            if ($warehouseType === 'part') {
                                                $partTypeQuery->where('name', 'not like', '%Аксесуар%')
                                                             ->where('name', 'not like', '%Інвентар%')
                                                             ->where('name', 'not like', '%Розхідник%');
                                            } elseif ($warehouseType === 'accessory') {
                                                $partTypeQuery->where('name', 'like', '%Аксесуар%');
                                            } elseif ($warehouseType === 'inventory') {
                                                $partTypeQuery->where('name', 'like', '%Інвентар%');
                                            } elseif ($warehouseType === 'consumable') {
                                                $partTypeQuery->where('name', 'like', '%Розхідник%');
                                            }
                                            
                                            $defaultPartType = $partTypeQuery->first();
                                            
                                            if ($defaultPartType) {
                                                // Створюємо новий товар з мінімальними даними
                                                $part = \App\Models\Part::create([
                                                    'name' => $newItemName,
                                                    'part_type_id' => $defaultPartType->id,
                                                    'cost_uah' => 0,
                                                    'quantity' => 1,
                                                    'status' => 'Stock',
                                                ]);
                                                
                                                // Встановлюємо створений товар
                                                $set('saleable_id', $part->id);
                                                $set('sell_price', 0);
                                                $set('buy_price', 0);
                                            } else {
                                                // Якщо немає типу, скидаємо вибір
                                                $set('saleable_id', null);
                                            }
                                            return;
                                        }
                                        
                                        if ($warehouseType === 'device') {
                                            $item = \App\Models\Device::find($state);
                                            if ($item) {
                                                // purchase_cost вже включає все: purchase_price_currency * exchange_rate + additional_costs + parts cost
                                                $set('sell_price', $item->selling_price ?? 0);
                                                $set('buy_price', $item->purchase_cost ?? 0);
                                                $set('quantity', 1);
                                            }
                                        } else {
                                            $item = \App\Models\Part::find($state);
                                            if ($item) {
                                                $set('sell_price', 0);
                                                $set('buy_price', $item->cost_uah ?? 0);
                                            }
                                        }
                                    })
                                    ->columnSpanFull(),

                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Кількість')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->required()
                                            ->hidden(fn($get) => $get('warehouse_type') === 'device')
                                            ->visible(fn($get) => $get('warehouse_type') !== 'device'),

                                        Forms\Components\TextInput::make('sell_price')
                                            ->label('Ціна продажу')
                                            ->numeric()
                                            ->required()
                                            ->suffix('грн')
                                            ->default(0),

                                        Forms\Components\Placeholder::make('profit')
                                            ->label('Прибуток')
                                            ->content(function ($get) {
                                                $sellPrice = (float) ($get('sell_price') ?? 0);
                                                $buyPrice = (float) ($get('buy_price') ?? 0);
                                                $quantity = (float) ($get('quantity') ?? 1);
                                                $profit = ($sellPrice - $buyPrice) * $quantity;
                                                return number_format($profit, 2, ',', ' ') . ' грн';
                                            }),
                                    ]),

                                Forms\Components\Hidden::make('buy_price')
                                    ->default(0)
                                    ->dehydrated(),

                                Forms\Components\Hidden::make('profit')
                                    ->default(0)
                                    ->dehydrated()
                                    ->afterStateHydrated(function ($set, $get) {
                                        $sellPrice = (float) ($get('sell_price') ?? 0);
                                        $buyPrice = (float) ($get('buy_price') ?? 0);
                                        $quantity = (float) ($get('quantity') ?? 1);
                                        $profit = ($sellPrice - $buyPrice) * $quantity;
                                        $set('profit', $profit);
                                    }),
                            ])
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                \Log::info('mutateRelationshipDataBeforeCreateUsing START', [
                                    'data' => $data,
                                    'keys' => array_keys($data),
                                    'saleable_id' => $data['saleable_id'] ?? 'NOT SET',
                                    'warehouse_type' => $data['warehouse_type'] ?? 'NOT SET',
                                ]);
                                
                                if (empty($data['saleable_id'])) {
                                    \Log::error('saleable_id is empty', ['data' => $data]);
                                    throw new \Illuminate\Validation\ValidationException(
                                        validator([], []),
                                        ['saleable_id' => ['Оберіть товар для продажу або створіть новий']]
                                    );
                                }
                                
                                $warehouseType = $data['warehouse_type'] ?? null;
                                
                                if (!$warehouseType) {
                                    \Log::error('warehouse_type is missing', ['data' => $data]);
                                    throw new \Illuminate\Validation\ValidationException(
                                        validator([], []),
                                        ['warehouse_type' => ['Оберіть тип складу']]
                                    );
                                }
                                
                                if ($warehouseType === 'device') {
                                    $data['saleable_type'] = \App\Models\Device::class;
                                } else {
                                    $data['saleable_type'] = \App\Models\Part::class;
                                }

                                $data['user_id'] = auth()->id();
                                $data['sold_at'] = now();
                                $data['quantity'] = $data['quantity'] ?? 1;
                                
                                $sellPrice = (float) ($data['sell_price'] ?? 0);
                                $buyPrice = (float) ($data['buy_price'] ?? 0);
                                $quantity = (float) ($data['quantity'] ?? 1);
                                $data['profit'] = ($sellPrice - $buyPrice) * $quantity;

                                unset($data['warehouse_type']);

                                \Log::info('mutateRelationshipDataBeforeCreateUsing SUCCESS', ['data' => $data]);
                                return $data;
                            })
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Примітка')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Примітка до чека')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('№ Чека')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('customer.phone')
                    ->label('Клієнт')
                    ->searchable()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Сума')
                    ->money('UAH')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\TextColumn::make('total_profit')
                    ->label('Прибуток')
                    ->money('UAH')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->color('gray'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('completed_at', 'desc');
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
