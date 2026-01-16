<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeviceResource\Pages;
use App\Filament\Resources\DeviceResource\RelationManagers;
use App\Models\Device;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DeviceResource extends Resource
{
    protected static ?string $navigationLabel = 'Склад техніки';
    protected static ?string $pluralModelLabel = 'Техніка';
    protected static ?string $modelLabel = 'Пристрій';
    protected static ?string $navigationGroup = 'Склад';
    protected static ?int $navigationSort = 10;
    protected static ?string $navigationIcon = null;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereDoesntHave('sales');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Прихід та Походження')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('batch_id')
                                    ->label('Поступлення (Партія)')
                                    ->relationship('batch', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')->label('Назва')->required(),
                                    ]),
                                Forms\Components\Select::make('contractor_id')
                                    ->label('Контрагент')
                                    ->relationship('contractor', 'name')
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\TextInput::make('marker')
                                    ->label('Маркер (B509)')
                                    ->placeholder('B509'),
                            ]),
                    ]),

                Forms\Components\Section::make('Характеристики Пристрою')
                    ->schema([
                        Forms\Components\Select::make('subcategory_id')
                            ->label('Модель')
                            ->relationship('subcategory', 'name')
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $subcategory = \App\Models\Subcategory::find($state);
                                    if ($subcategory) {
                                        $set('model', $subcategory->name);
                                    }
                                }
                            })
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->label('Назва моделі')->required(),
                                Forms\Components\Select::make('category_id')
                                    ->label('Категорія')
                                    ->relationship('category', 'name')
                                    ->required(),
                            ]),
                        Forms\Components\TextInput::make('model')
                            ->label('Назва/Модель (повна)')
                            ->required()
                            ->placeholder('Введіть назву моделі або оберіть зі списку')
                            ->datalist(function (Forms\Get $get) {
                                $subcategoryId = $get('subcategory_id');
                                if (!$subcategoryId) {
                                    return [];
                                }
                                // Отримуємо існуючі варіанти для автодоповнення
                                return \App\Models\Device::where('subcategory_id', $subcategoryId)
                                    ->distinct()
                                    ->pluck('model')
                                    ->toArray();
                            })
                            ->columnSpanFull(),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('storage')
                                    ->label('Пам\'ять')
                                    ->options(['64GB' => '64GB', '128GB' => '128GB', '256GB' => '256GB', '512GB' => '512GB', '1TB' => '1TB']),
                                Forms\Components\TextInput::make('imei')->label('IMEI/SN')->placeholder('15 цифр або S/N'),
                                Forms\Components\Select::make('lock_status')
                                    ->label('Блокування')
                                    ->options([
                                        'unlock' => '🔓 Unlock',
                                        'lock' => '🔒 Lock',
                                        'mdm' => '📱 MDM',
                                        'bypass' => '🔓 Bypass',
                                    ])
                                    ->default('unlock'),
                                Forms\Components\Select::make('status')
                                    ->label('Статус')
                                    ->options([
                                        'Stock' => 'На складі',
                                        'InTransit' => 'В дорозі',
                                        'Repair' => 'Ремонт',
                                    ])
                                    ->default('Stock')
                                    ->required()
                                    ->default('Stock'),
                            ]),
                    ]),

                Forms\Components\Section::make('Фінанси')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('purchase_currency')
                                    ->label('Валюта')
                                    ->options(['UAH' => '₴ UAH', 'EUR' => '€ EUR', 'USD' => '$ USD'])
                                    ->required()
                                    ->default('UAH')
                                    ->live()
                                    ->afterStateHydrated(function (Forms\Set $set, $state, $record) {
                                        if ($state === 'UAH' || !$state)
                                            return;
                                        // Якщо ми редагуємо і курс вже є в базі, не завантажуємо новий з API автоматично (за потреби користувач може змінити валюту)
                                        if ($record && $record->exchange_rate)
                                            return;

                                        try {
                                            $response = \Illuminate\Support\Facades\Http::timeout(5)->get("https://api.privatbank.ua/p24api/pubinfo?exchange&json&coursid=11");
                                            if ($response->successful()) {
                                                $data = $response->json();
                                                foreach ($data as $curr) {
                                                    if ($curr['ccy'] === $state) {
                                                        $rate = round((float) $curr['sale'], 2);
                                                        $set('exchange_rate', $rate);
                                                        break;
                                                    }
                                                }
                                            }
                                        } catch (\Exception $e) {
                                            \Illuminate\Support\Facades\Log::error('PrivatBank API Error (Hydrate): ' . $e->getMessage());
                                        }
                                    })
                                    ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                        if ($state === 'UAH') {
                                            $set('exchange_rate', 1);
                                            static::recalculatePurchaseCost($set, $get);
                                            return;
                                        }
                                        try {
                                            $response = \Illuminate\Support\Facades\Http::timeout(5)->get("https://api.privatbank.ua/p24api/pubinfo?exchange&json&coursid=11");
                                            if ($response->successful()) {
                                                $data = $response->json();
                                                foreach ($data as $curr) {
                                                    if ($curr['ccy'] === $state) {
                                                        $rate = round((float) $curr['sale'], 2);
                                                        $set('exchange_rate', $rate);
                                                        static::recalculatePurchaseCost($set, $get);
                                                        break;
                                                    }
                                                }
                                            }
                                        } catch (\Exception $e) {
                                            \Illuminate\Support\Facades\Log::error('PrivatBank API Error (Updated): ' . $e->getMessage());
                                        }
                                    }),
                                Forms\Components\TextInput::make('purchase_price_currency')
                                    ->label('Ціна у валюті')
                                    ->numeric()
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        static::recalculatePurchaseCost($set, $get);
                                    }),
                                Forms\Components\TextInput::make('exchange_rate')
                                    ->label('Курс')
                                    ->numeric()
                                    ->required()
                                    ->live(onBlur: true)
                                    ->placeholder('Чекайте...')
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        static::recalculatePurchaseCost($set, $get);
                                    })
                                    ->helperText('Курс ПриватБанку'),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('additional_costs')
                                            ->label('Дод. витрати (грн)')
                                            ->numeric()
                                            ->default(0)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                static::recalculatePurchaseCost($set, $get);
                                            }),
                                        Forms\Components\Textarea::make('additional_costs_note')
                                            ->label('На що пішли витрати')
                                            ->placeholder('Доставка, мито, ремонт...')
                                            ->rows(2),
                                    ])
                                    ->columnSpanFull(),
                            ]),
                        Forms\Components\TextInput::make('purchase_cost')
                            ->label('СОБІВАРТІСТЬ (ГРН)')
                            ->numeric()
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->prefix('₴')
                            ->extraInputAttributes(['style' => 'font-weight: 800; font-size: 1.5rem; color: #166534; background-color: #f0fdf4; border: 2px solid #22c55e;']),
                        Forms\Components\TextInput::make('selling_price')
                            ->label('Орієнтовна ціна продажу (UAH)')
                            ->numeric()
                            ->prefix('₴'),
                        Forms\Components\Textarea::make('description')
                            ->label('Коментарі')
                            ->placeholder('Нотатки про стан пристрою...')
                            ->columnSpanFull()
                            ->rows(3),
                    ]),

                Forms\Components\Section::make('Запчастини та комплектуючі')
                    ->schema([
                        Forms\Components\Repeater::make('parts')
                            ->label('')
                            ->schema([
                                Forms\Components\Select::make('part_id')
                                    ->label('Запчастина')
                                    ->options(function () {
                                        return \App\Models\Part::where('status', 'Stock')
                                            ->with(['partType', 'contractor'])
                                            ->get()
                                            ->mapWithKeys(function ($part) {
                                                $label = $part->type_label . ': ' . $part->name;
                                                if ($part->contractor) {
                                                    $label .= ' (' . $part->contractor->name . ')';
                                                }
                                                return [$part->id => $label];
                                            });
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Назва')
                                            ->required()
                                            ->autofocus(),
                                        Forms\Components\Select::make('part_type_id')
                                            ->label('Тип')
                                            ->relationship('partType', 'name')
                                            ->required(),
                                        Forms\Components\TextInput::make('cost_uah')
                                            ->label('Ціна (грн)')
                                            ->numeric()
                                            ->required(),
                                        Forms\Components\Hidden::make('status')->default('Stock'),
                                    ])
                                    ->createOptionUsing(function (array $data): int {
                                        $part = \App\Models\Part::create([
                                            'name' => $data['name'],
                                            'part_type_id' => $data['part_type_id'],
                                            'cost_uah' => $data['cost_uah'],
                                            'status' => $data['status'] ?? 'Stock',
                                            'quantity' => 1,
                                        ]);
                                        return $part->id;
                                    })
                                    ->getSearchResultsUsing(function (string $search) {
                                        return \App\Models\Part::where('status', 'Stock')
                                            ->where('name', 'like', "%{$search}%")
                                            ->with(['partType', 'contractor'])
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(function ($part) {
                                                $label = $part->type_label . ': ' . $part->name;
                                                if ($part->contractor) {
                                                    $label .= ' (' . $part->contractor->name . ')';
                                                }
                                                return [$part->id => $label];
                                            });
                                    }),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Кількість')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1)
                                    ->live()
                                    ->dehydrated()
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        static::recalculatePurchaseCost($set, $get);
                                    }),
                            ])
                            ->defaultItems(0)
                            ->itemLabel(fn(array $state): ?string => 
                                $state['part_id'] ? \App\Models\Part::find($state['part_id'])?->name . ' (x' . ($state['quantity'] ?? 1) . ')' : null
                            )
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                static::recalculatePurchaseCost($set, $get);
                            })
                            ->reorderable()
                            ->helperText('Додайте запчастини, які використовуються в цьому пристрої'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction(null)
            ->defaultSort('model', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('purchase_cost')
                    ->label('Собівартість')
                    ->money('UAH')
                    ->sortable()
                    ->weight('bold')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('marker')
                    ->label('Маркер')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('lock_status')
                    ->label('Блокування')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            'unlock' => 'Unlock',
                            'lock' => 'Lock',
                            'mdm' => 'MDM',
                            'bypass' => 'Bypass',
                            default => 'Не вказано'
                        };
                    })
                    ->color(fn($state) => match($state) {
                        'unlock' => 'success',
                        'lock' => 'danger',
                        'mdm' => 'warning',
                        'bypass' => 'info',
                        default => 'gray'
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('model')
                    ->label('Модель')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('imei')
                    ->label('IMEI/SN')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'Stock' => 'На складі',
                        'InTransit' => 'В дорозі',
                        'Repair' => 'Ремонт',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'Stock' => 'success',
                        'InTransit' => 'info',
                        'Repair' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('contractor.name')
                    ->label('Контрагент')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Склад')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('batch_id')
                    ->label('Партія')
                    ->relationship('batch', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('subcategory_id')
                    ->label('Модель')
                    ->relationship('subcategory', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'Stock' => 'На складі',
                        'InTransit' => 'В дорозі',
                        'Repair' => 'Ремонт',
                    ]),

                Tables\Filters\Filter::make('no_imei')
                    ->label('Без IMEI')
                    ->query(fn($query) => $query->whereNull('imei')->orWhere('imei', '')),

                Tables\Filters\Filter::make('no_marker')
                    ->label('Без маркера')
                    ->query(fn($query) => $query->whereNull('marker')->orWhere('marker', '')),
            ])
            ->actions([
                Tables\Actions\Action::make('show_breakdown')
                    ->label('Витрати')
                    ->icon('heroicon-o-calculator')
                    ->color('success')
                    ->modalContent(function ($record) {
                        $record->load('parts');
                        return view('filament.resources.device.breakdown', ['record' => $record]);
                    })
                    ->modalHeading(fn($record) => "Витрати: {$record->model}")
                    ->modalWidth('2xl'),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListDevices::route('/'),
            'create' => Pages\CreateDevice::route('/create'),
            'edit' => Pages\EditDevice::route('/{record}/edit'),
        ];
    }

    private static function recalculatePurchaseCost($set, $get): void
    {
        $price = (float) ($get('purchase_price_currency') ?? 0);
        $rate = (float) ($get('exchange_rate') ?? 1);
        $additional = (float) ($get('additional_costs') ?? 0);
        
        // Розрахунок вартості всіх запчастин з урахуванням кількості
        $partsCost = 0;
        $parts = $get('parts') ?? [];
        foreach ($parts as $part) {
            if (!empty($part['part_id'])) {
                $partModel = \App\Models\Part::find($part['part_id']);
                if ($partModel) {
                    $quantity = (int) ($part['quantity'] ?? 1);
                    $partsCost += $partModel->cost_uah * $quantity;
                }
            }
        }

        $total = ($price * $rate) + $additional + $partsCost;
        $set('purchase_cost', round($total, 2));
    }
}
