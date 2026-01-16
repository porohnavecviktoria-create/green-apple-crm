<?php

namespace App\Filament\Resources\BatchResource\Pages;

use App\Filament\Resources\BatchResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms;
use Filament\Notifications\Notification;

class CreateBatch extends CreateRecord
{
    protected static string $resource = BatchResource::class;

    protected static ?string $title = '📦 Додати партію пристроїв';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Інформація про партію')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Назва партії')
                                    ->required()
                                    ->placeholder('Наприклад: iPhone 14 Pro - Січень 2026'),
                                Forms\Components\DatePicker::make('purchase_date')
                                    ->label('Дата поступлення')
                                    ->default(now())
                                    ->required(),
                            ]),
                        Forms\Components\Select::make('default_contractor_id')
                            ->label('Контрагент (за замовчуванням)')
                            ->options(\App\Models\Contractor::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->helperText('Буде застосовано до всіх пристроїв у партії'),
                        Forms\Components\Textarea::make('description')
                            ->label('Опис партії')
                            ->rows(2),
                    ]),

                Forms\Components\Section::make('Пристрої в партії')
                    ->schema([
                        Forms\Components\Repeater::make('devices')
                            ->schema([
                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\Select::make('subcategory_id')
                                            ->label('Модель')
                                            ->options(\App\Models\Subcategory::pluck('name', 'id'))
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
                                                    ->options(\App\Models\Category::pluck('name', 'id'))
                                                    ->required(),
                                            ])
                                            ->createOptionUsing(function (array $data) {
                                                return \App\Models\Subcategory::create($data)->id;
                                            })
                                            ->required()
                                            ->columnSpan(2),
                                        Forms\Components\TextInput::make('model')
                                            ->label('Назва повна')
                                            ->required()
                                            ->columnSpan(2),
                                    ]),
                                Forms\Components\Grid::make(5)
                                    ->schema([
                                        Forms\Components\Select::make('storage')
                                            ->label('Пам\'ять')
                                            ->options(['64GB' => '64GB', '128GB' => '128GB', '256GB' => '256GB', '512GB' => '512GB', '1TB' => '1TB']),
                                        Forms\Components\TextInput::make('imei')
                                            ->label('IMEI/SN')
                                            ->placeholder('15 цифр або S/N'),
                                        Forms\Components\TextInput::make('color')
                                            ->label('Колір'),
                                        Forms\Components\Select::make('condition')
                                            ->label('Стан')
                                            ->options([
                                                'New' => '🆕 Новий',
                                                'Used - Excellent' => '✨ Відмінний',
                                                'Used - Good' => '👍 Гарний',
                                                'Used - Fair' => '👌 Задовільний',
                                                'For Parts' => '🔧 На запчастини',
                                            ])
                                            ->default('New'),
                                        Forms\Components\Select::make('lock_status')
                                            ->label('Блокування')
                                            ->options([
                                                'unlock' => '🔓 Unlock',
                                                'lock' => '🔒 Lock',
                                                'mdm' => '📱 MDM',
                                                'bypass' => '🔓 Bypass',
                                            ])
                                            ->default('unlock'),
                                    ]),
                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\Select::make('purchase_currency')
                                            ->label('Валюта')
                                            ->options(['UAH' => '₴ UAH', 'EUR' => '€ EUR', 'USD' => '$ USD'])
                                            ->default('UAH')
                                            ->live()
                                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                                if ($state === 'UAH') {
                                                    $set('exchange_rate', 1);
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
                                                                break;
                                                            }
                                                        }
                                                    }
                                                } catch (\Exception $e) {
                                                    \Illuminate\Support\Facades\Log::error('PrivatBank API Error: ' . $e->getMessage());
                                                }
                                            }),
                                        Forms\Components\TextInput::make('purchase_price_currency')
                                            ->label('Ціна закупки')
                                            ->numeric()
                                            ->required()
                                            ->reactive(),
                                        Forms\Components\TextInput::make('exchange_rate')
                                            ->label('Курс')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->reactive()
                                            ->placeholder('Чекайте...')
                                            ->helperText('Курс ПриватБанку'),
                                        Forms\Components\TextInput::make('additional_costs')
                                            ->label('Дод. витрати (₴)')
                                            ->numeric()
                                            ->default(0),
                                    ]),
                                Forms\Components\Textarea::make('additional_costs_note')
                                    ->label('Коментар до витрат')
                                    ->placeholder('Доставка, мито, ремонт...')
                                    ->rows(1)
                                    ->columnSpanFull(),
                            ])
                            ->label('Додати пристрої')
                            ->addActionLabel('➕ Додати ще пристрій')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => $state['model'] ?? 'Новий пристрій')
                            ->defaultItems(1)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $devices = $data['devices'] ?? [];
        $defaultContractorId = $data['default_contractor_id'] ?? null;

        unset($data['devices']);
        unset($data['default_contractor_id']);

        // Зберігаємо пристрої для обробки після створення партії
        $this->devicesToCreate = $devices;
        $this->defaultContractorId = $defaultContractorId;

        return $data;
    }

    protected function afterCreate(): void
    {
        $batch = $this->record;
        $createdCount = 0;

        foreach ($this->devicesToCreate as $deviceData) {
            $deviceData['batch_id'] = $batch->id;
            $deviceData['status'] = 'Stock';

            // Застосовуємо контрагента за замовчуванням
            if (!isset($deviceData['contractor_id']) && $this->defaultContractorId) {
                $deviceData['contractor_id'] = $this->defaultContractorId;
            }

            // Розраховуємо собівартість
            $price = (float) ($deviceData['purchase_price_currency'] ?? 0);
            $rate = (float) ($deviceData['exchange_rate'] ?? 1);
            $additional = (float) ($deviceData['additional_costs'] ?? 0);
            $deviceData['purchase_cost'] = round(($price * $rate) + $additional, 2);

            \App\Models\Device::create($deviceData);
            $createdCount++;
        }

        Notification::make()
            ->success()
            ->title('Партію створено!')
            ->body("Успішно додано {$createdCount} пристроїв у партію \"{$batch->name}\"")
            ->seconds(5)
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    private $devicesToCreate = [];
    private $defaultContractorId = null;
}
