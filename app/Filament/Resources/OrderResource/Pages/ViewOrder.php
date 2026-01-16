<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected static ?string $title = 'Перегляд чека';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Редагувати'),
            Actions\DeleteAction::make()
                ->label('Видалити'),
        ];
    }

    protected function mutateRecordForView($record)
    {
        // Завантажуємо зв'язки для оптимізації
        $record->load([
            'sales.saleable',
            'customer',
            'user'
        ]);
        
        // Якщо saleable - Part, завантажуємо partType
        // Якщо saleable - Device, завантажуємо parts та contractor
        foreach ($record->sales as $sale) {
            if ($sale->saleable instanceof \App\Models\Part && !$sale->saleable->relationLoaded('partType')) {
                $sale->saleable->load('partType');
            }
            if ($sale->saleable instanceof \App\Models\Device) {
                $device = $sale->saleable;
                // Завантажуємо parts з усіма необхідними зв'язками
                $device->load([
                    'parts' => function ($query) {
                        $query->with(['partType', 'contractor']);
                    }
                ]);
            }
        }
        
        return $record;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Інформація про чек')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('order_number')
                                    ->label('Номер чека')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('completed_at')
                                    ->label('Дата створення')
                                    ->dateTime('d.m.Y H:i')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                            ]),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('customer.phone')
                                    ->label('Клієнт')
                                    ->formatStateUsing(fn($record) => $record->customer->phone . ($record->customer->name ? ' - ' . $record->customer->name : '')),
                                Infolists\Components\TextEntry::make('user.name')
                                    ->label('Продавець')
                                    ->default('Не вказано'),
                            ]),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_amount')
                                    ->label('Загальна сума')
                                    ->money('UAH')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Medium)
                                    ->weight('bold')
                                    ->color('success'),
                                Infolists\Components\TextEntry::make('total_profit')
                                    ->label('Загальний прибуток')
                                    ->money('UAH')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Medium)
                                    ->weight('bold')
                                    ->color('success'),
                            ]),
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Примітка')
                            ->default('Немає приміток')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Товари в чеку')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('sales')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('saleable_info')
                                    ->label('Товар')
                                    ->state(function ($record) {
                                        $item = $record->saleable;
                                        if (!$item) {
                                            // Якщо saleable не завантажений, завантажуємо вручну
                                            $saleableId = $record->saleable_id ?? null;
                                            $saleableType = $record->saleable_type ?? null;
                                            
                                            if ($saleableType === \App\Models\Device::class && $saleableId) {
                                                $item = \App\Models\Device::withoutGlobalScopes()->find($saleableId);
                                            } elseif ($saleableType === \App\Models\Part::class && $saleableId) {
                                                $item = \App\Models\Part::find($saleableId);
                                                if ($item && !$item->relationLoaded('partType')) {
                                                    $item->load('partType');
                                                }
                                            }
                                        }
                                        
                                        if (!$item) {
                                            return 'Товар не знайдено';
                                        }
                                        
                                        if ($item instanceof \App\Models\Device) {
                                            $info = $item->model;
                                            if ($item->marker) $info .= ' | ' . $item->marker;
                                            if ($item->imei) $info .= ' | IMEI: ' . $item->imei;
                                            return $info;
                                        } elseif ($item instanceof \App\Models\Part) {
                                            $info = $item->name;
                                            if ($item->partType) {
                                                $info .= ' (' . $item->partType->name . ')';
                                            }
                                            return $info;
                                        }
                                        return 'Невідомий товар';
                                    })
                                    ->weight('bold')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->columnSpanFull(),
                                
                                Infolists\Components\Grid::make(4)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('quantity')
                                            ->label('Кількість')
                                            ->badge()
                                            ->color('info')
                                            ->size(Infolists\Components\TextEntry\TextEntrySize::Medium),
                                        Infolists\Components\TextEntry::make('sell_price')
                                            ->label('Ціна продажу (за од.)')
                                            ->money('UAH')
                                            ->color('success')
                                            ->weight('bold'),
                                        Infolists\Components\TextEntry::make('total_sell_price')
                                            ->label('Загальна ціна продажу')
                                            ->formatStateUsing(function ($record) {
                                                $total = ($record->sell_price ?? 0) * ($record->quantity ?? 1);
                                                return number_format($total, 2, ',', ' ') . ' ₴';
                                            })
                                            ->color('success')
                                            ->weight('bold')
                                            ->size(Infolists\Components\TextEntry\TextEntrySize::Medium),
                                        Infolists\Components\TextEntry::make('profit')
                                            ->label('Прибуток')
                                            ->money('UAH')
                                            ->weight('bold')
                                            ->color('success')
                                            ->size(Infolists\Components\TextEntry\TextEntrySize::Medium),
                                    ]),
                                
                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('buy_price')
                                            ->label('Собівартість (за од.)')
                                            ->money('UAH')
                                            ->color('gray'),
                                        Infolists\Components\TextEntry::make('total_buy_price')
                                            ->label('Загальна собівартість')
                                            ->formatStateUsing(function ($record) {
                                                $total = ($record->buy_price ?? 0) * ($record->quantity ?? 1);
                                                return number_format($total, 2, ',', ' ') . ' ₴';
                                            })
                                            ->color('gray')
                                            ->weight('bold'),
                                        Infolists\Components\TextEntry::make('profit_margin')
                                            ->label('Маржа')
                                            ->formatStateUsing(function ($record) {
                                                $sellPrice = ($record->sell_price ?? 0) * ($record->quantity ?? 1);
                                                $buyPrice = ($record->buy_price ?? 0) * ($record->quantity ?? 1);
                                                if ($sellPrice > 0) {
                                                    $margin = (($sellPrice - $buyPrice) / $sellPrice) * 100;
                                                    return number_format($margin, 1, ',', ' ') . '%';
                                                }
                                                return '0%';
                                            })
                                            ->color('info'),
                                    ]),
                                
                                Infolists\Components\TextEntry::make('sold_at')
                                    ->label('Дата продажу')
                                    ->dateTime('d.m.Y H:i')
                                    ->color('gray')
                                    ->icon('heroicon-o-clock'),
                                
                                // Кнопка для відкриття деталізації витрат
                                Infolists\Components\Actions::make([
                                    Infolists\Components\Actions\Action::make('show_cost_details')
                                        ->label('Деталізація витрат')
                                        ->icon('heroicon-o-calculator')
                                        ->color('success')
                                        ->button()
                                        ->outlined()
                                        ->visible(function ($record) {
                                            // Перевіряємо чи це пристрій
                                            if ($record->saleable instanceof \App\Models\Device) {
                                                return true;
                                            }
                                            // Якщо saleable не завантажений, завантажуємо вручну
                                            if (!$record->saleable) {
                                                $saleableId = $record->saleable_id ?? null;
                                                $saleableType = $record->saleable_type ?? null;
                                                if ($saleableType === \App\Models\Device::class && $saleableId) {
                                                    return true;
                                                }
                                            }
                                            return false;
                                        })
                                        ->modalHeading(function ($record) {
                                            $device = $record->saleable;
                                            if (!$device instanceof \App\Models\Device) {
                                                $saleableId = $record->saleable_id ?? null;
                                                if ($saleableId) {
                                                    $device = \App\Models\Device::withoutGlobalScopes()->find($saleableId);
                                                }
                                            }
                                            return 'Деталізація витрат: ' . ($device->model ?? 'Пристрій');
                                        })
                                        ->modalContent(function ($record) {
                                            $device = $record->saleable;
                                            
                                            // Якщо saleable не завантажений, завантажуємо вручну
                                            if (!$device instanceof \App\Models\Device) {
                                                $saleableId = $record->saleable_id ?? null;
                                                if ($saleableId) {
                                                    $device = \App\Models\Device::withoutGlobalScopes()
                                                        ->with(['parts.partType', 'parts.contractor'])
                                                        ->find($saleableId);
                                                }
                                            } else {
                                                // Завантажуємо пристрій з усіма даними
                                                $device = \App\Models\Device::withoutGlobalScopes()
                                                    ->with(['parts.partType', 'parts.contractor'])
                                                    ->find($device->id);
                                            }
                                            
                                            if (!$device) {
                                                return new \Illuminate\Support\HtmlString('<p>Пристрій не знайдено</p>');
                                            }
                                            
                                            if (!$device) {
                                                return new \Illuminate\Support\HtmlString('<p>Пристрій не знайдено</p>');
                                            }
                                            
                                            $html = '<div class="space-y-4">';
                                            
                                            // Ціна закупівлі та курс
                                            $html .= '<div class="grid grid-cols-2 gap-4">';
                                            $price = $device->purchase_price_currency ?? 0;
                                            $currency = $device->purchase_currency ?? 'USD';
                                            $html .= '<div><div class="text-sm text-gray-500">Ціна закупівлі</div>';
                                            $html .= '<div class="text-gray-900 font-medium">';
                                            if ($price > 0) {
                                                $html .= number_format($price, 2, ',', ' ') . ' ' . $currency;
                                            } else {
                                                $html .= '<span class="text-gray-400">Не вказано</span>';
                                            }
                                            $html .= '</div></div>';
                                            
                                            $html .= '<div><div class="text-sm text-gray-500">Курс</div>';
                                            $html .= '<div class="text-gray-900 font-medium">' . number_format($device->exchange_rate ?? 1, 2, ',', ' ') . '</div></div>';
                                            $html .= '</div>';
                                            
                                            // Вартість у гривні
                                            $priceUah = ($device->purchase_price_currency ?? 0) * ($device->exchange_rate ?? 1);
                                            $html .= '<div><div class="text-sm text-gray-500">Вартість у гривні</div>';
                                            $html .= '<div class="text-gray-900 font-bold text-lg">';
                                            if ($priceUah > 0) {
                                                $html .= number_format($priceUah, 2, ',', ' ') . ' ₴';
                                            } else {
                                                $html .= '<span class="text-gray-400">Не вказано</span>';
                                            }
                                            $html .= '</div></div>';
                                            
                                            // Додаткові витрати
                                            $additional = $device->additional_costs ?? 0;
                                            $html .= '<div><div class="text-sm text-gray-500">Додаткові витрати</div>';
                                            $html .= '<div class="text-warning-600 font-bold">' . number_format($additional, 2, ',', ' ') . ' ₴</div></div>';
                                            
                                            // Запчастини
                                            if ($device->parts->count() > 0) {
                                                $html .= '<div><div class="text-sm text-gray-500 mb-2">Запчастини та комплектуючі</div>';
                                                $html .= '<div class="space-y-1">';
                                                foreach ($device->parts as $part) {
                                                    $partTypeName = $part->partType->name ?? 'Деталь';
                                                    $partName = $part->name;
                                                    $contractorName = $part->contractor ? ' (Пост: ' . $part->contractor->name . ')' : '';
                                                    $cost = number_format($part->cost_uah, 2, ',', ' ') . ' ₴';
                                                    $html .= '<div class="text-gray-900">• ' . $partTypeName . ': ' . $partName . $contractorName . ' → <strong>+' . $cost . '</strong></div>';
                                                }
                                                $html .= '</div></div>';
                                                
                                                $totalPartsCost = $device->parts->sum('cost_uah');
                                                $html .= '<div><div class="text-sm text-gray-500">Загальна вартість запчастин</div>';
                                                $html .= '<div class="text-warning-600 font-bold">' . number_format($totalPartsCost, 2, ',', ' ') . ' ₴</div></div>';
                                            }
                                            
                                            // Загальна собівартість
                                            $total = $device->purchase_cost ?? 0;
                                            if ($total == 0) {
                                                $price = ($device->purchase_price_currency ?? 0) * ($device->exchange_rate ?? 1);
                                                $additional = $device->additional_costs ?? 0;
                                                $partsCost = $device->parts->sum('cost_uah');
                                                $total = $price + $additional + $partsCost;
                                            }
                                            
                                            $html .= '<div><div class="text-sm text-gray-500">Загальна собівартість</div>';
                                            $html .= '<div class="text-danger-600 font-bold text-xl">' . number_format($total, 2, ',', ' ') . ' ₴</div></div>';
                                            
                                            $html .= '</div>';
                                            
                                            return new \Illuminate\Support\HtmlString($html);
                                        })
                                        ->modalWidth('3xl'),
                                ])
                                    ->alignEnd(),
                                
                                // Деталізація витрат для пристроїв (прихована, використовується кнопка вище)
                                Infolists\Components\Section::make('Деталізація витрат')
                                    ->visible(false)
                                    ->schema([
                                        Infolists\Components\Grid::make(2)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('device_purchase_price')
                                                    ->label('Ціна закупівлі')
                                                    ->formatStateUsing(function ($record) {
                                                        $device = $record->saleable;
                                                        if (!$device instanceof \App\Models\Device) {
                                                            return '—';
                                                        }
                                                        $price = $device->purchase_price_currency ?? 0;
                                                        $currency = $device->purchase_currency ?? 'USD';
                                                        return number_format($price, 2, ',', ' ') . ' ' . $currency;
                                                    })
                                                    ->color('gray'),
                                                Infolists\Components\TextEntry::make('device_exchange_rate')
                                                    ->label('Курс')
                                                    ->formatStateUsing(function ($record) {
                                                        $device = $record->saleable;
                                                        if (!$device instanceof \App\Models\Device) {
                                                            return '—';
                                                        }
                                                        return number_format($device->exchange_rate ?? 1, 2, ',', ' ');
                                                    })
                                                    ->color('gray'),
                                            ]),
                                        Infolists\Components\TextEntry::make('device_price_uah')
                                            ->label('Вартість у гривні')
                                            ->formatStateUsing(function ($record) {
                                                $device = $record->saleable;
                                                if (!$device instanceof \App\Models\Device) {
                                                    return '—';
                                                }
                                                $price = ($device->purchase_price_currency ?? 0) * ($device->exchange_rate ?? 1);
                                                return number_format($price, 2, ',', ' ') . ' ₴';
                                            })
                                            ->weight('bold')
                                            ->color('gray'),
                                        Infolists\Components\TextEntry::make('device_additional_costs')
                                            ->label('Додаткові витрати')
                                            ->formatStateUsing(function ($record) {
                                                $device = $record->saleable;
                                                if (!$device instanceof \App\Models\Device) {
                                                    return '—';
                                                }
                                                return number_format($device->additional_costs ?? 0, 2, ',', ' ') . ' ₴';
                                            })
                                            ->color('warning')
                                            ->weight('bold'),
                                        
                                        // Список запчастин
                                        Infolists\Components\TextEntry::make('device_parts_list')
                                            ->label('Запчастини та комплектуючі')
                                            ->visible(fn($record) => $record->saleable instanceof \App\Models\Device && $record->saleable->parts->count() > 0)
                                            ->formatStateUsing(function ($record) {
                                                $device = $record->saleable;
                                                if (!$device instanceof \App\Models\Device || $device->parts->isEmpty()) {
                                                    return 'Немає запчастин';
                                                }
                                                
                                                $list = [];
                                                foreach ($device->parts as $part) {
                                                    $partTypeName = $part->partType->name ?? 'Деталь';
                                                    $partName = $part->name;
                                                    $contractorName = $part->contractor ? ' (Пост: ' . $part->contractor->name . ')' : '';
                                                    $cost = number_format($part->cost_uah, 2, ',', ' ') . ' ₴';
                                                    
                                                    $list[] = $partTypeName . ': ' . $partName . $contractorName . ' → +' . $cost;
                                                }
                                                
                                                return implode("\n", $list);
                                            })
                                            ->markdown()
                                            ->columnSpanFull(),
                                        
                                        Infolists\Components\TextEntry::make('device_parts_total')
                                            ->label('Загальна вартість запчастин')
                                            ->visible(function ($record) {
                                                $device = $record->saleable;
                                                if (!$device instanceof \App\Models\Device) {
                                                    return false;
                                                }
                                                if (!$device->relationLoaded('parts')) {
                                                    $device->load('parts');
                                                }
                                                return $device->parts->count() > 0;
                                            })
                                            ->formatStateUsing(function ($record) {
                                                $device = $record->saleable;
                                                if (!$device instanceof \App\Models\Device) {
                                                    return '0 ₴';
                                                }
                                                if (!$device->relationLoaded('parts')) {
                                                    $device->load('parts');
                                                }
                                                $totalPartsCost = $device->parts->sum('cost_uah');
                                                return number_format($totalPartsCost, 2, ',', ' ') . ' ₴';
                                            })
                                            ->color('warning')
                                            ->weight('bold')
                                            ->columnSpanFull(),
                                        
                                        Infolists\Components\TextEntry::make('device_total_cost')
                                            ->label('Загальна собівартість')
                                            ->formatStateUsing(function ($record) {
                                                $device = $record->saleable;
                                                if (!$device instanceof \App\Models\Device) {
                                                    return '—';
                                                }
                                                // Переконаємося, що parts завантажені
                                                if (!$device->relationLoaded('parts')) {
                                                    $device->load('parts');
                                                }
                                                $price = ($device->purchase_price_currency ?? 0) * ($device->exchange_rate ?? 1);
                                                $additional = $device->additional_costs ?? 0;
                                                $partsCost = $device->parts->sum('cost_uah');
                                                $total = $price + $additional + $partsCost;
                                                return number_format($total, 2, ',', ' ') . ' ₴';
                                            })
                                            ->weight('bold')
                                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                            ->color('danger')
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(false)
                                    ->columnSpanFull(),
                            ])
                            ->columns(1),
                    ]),
            ]);
    }
}
