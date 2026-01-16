<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewSale extends ViewRecord
{
    protected static string $resource = SaleResource::class;

    protected static ?string $title = 'Деталі продажу';


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
            'saleable',
            'order.customer',
            'user'
        ]);
        
        // Якщо saleable - Part, завантажуємо partType
        if ($record->saleable instanceof \App\Models\Part && !$record->saleable->relationLoaded('partType')) {
            $record->saleable->load('partType');
        }
        
        // Якщо saleable - Device, завантажуємо parts та contractor
        if ($record->saleable instanceof \App\Models\Device) {
            $device = $record->saleable;
            $deviceId = $device->id;
            
            // Завантажуємо пристрій з бази з усіма необхідними полями
            $device = \App\Models\Device::with([
                'parts' => function ($query) {
                    $query->with(['partType', 'contractor']);
                },
                'contractor',
                'batch'
            ])->find($deviceId);
            
            if ($device) {
                // Оновлюємо saleable з повними даними
                $record->setRelation('saleable', $device);
                
                // Логуємо для діагностики
                \Log::info('Device loaded for sale', [
                    'device_id' => $device->id,
                    'purchase_price_currency' => $device->purchase_price_currency,
                    'purchase_currency' => $device->purchase_currency,
                    'exchange_rate' => $device->exchange_rate,
                    'additional_costs' => $device->additional_costs,
                    'purchase_cost' => $device->purchase_cost,
                    'parts_count' => $device->parts->count(),
                ]);
            } else {
                \Log::error('Device not found', ['device_id' => $deviceId]);
            }
        }
        
        return $record;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Інформація про продаж')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('sold_at')
                                    ->label('Дата продажу')
                                    ->dateTime('d.m.Y H:i')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('order.order_number')
                                    ->label('Номер чека')
                                    ->formatStateUsing(function ($record) {
                                        return $record->order ? $record->order->order_number : '—';
                                    })
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Medium),
                            ]),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('order.customer.phone')
                                    ->label('Клієнт')
                                    ->formatStateUsing(function ($record) {
                                        if (!$record->order || !$record->order->customer) {
                                            return '—';
                                        }
                                        $customer = $record->order->customer;
                                        return $customer->phone . ($customer->name ? ' - ' . $customer->name : '');
                                    }),
                                Infolists\Components\TextEntry::make('user.name')
                                    ->label('Продавець')
                                    ->default('Не вказано'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Інформація про товар')
                    ->schema([
                        Infolists\Components\TextEntry::make('saleable_info')
                            ->label('Товар')
                            ->formatStateUsing(function ($record) {
                                $item = $record->saleable;
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
                    ]),

                // Деталізація витрат для пристроїв
                Infolists\Components\Section::make('Деталізація витрат')
                    ->visible(function ($record) {
                        return $record->saleable_type === \App\Models\Device::class;
                    })
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('device_purchase_price')
                                    ->label('Ціна закупівлі')
                                    ->state(function ($record) {
                                        $saleableId = $record->saleable_id ?? null;
                                        if (!$saleableId) return '—';
                                        
                                        $device = \App\Models\Device::withoutGlobalScopes()->find($saleableId);
                                        if (!$device) return '—';
                                        
                                        $price = $device->purchase_price_currency ?? 0;
                                        $currency = $device->purchase_currency ?? 'USD';
                                        if ($price == 0) return 'Не вказано';
                                        return number_format($price, 2, ',', ' ') . ' ' . $currency;
                                    })
                                    ->color('gray'),
                                Infolists\Components\TextEntry::make('device_exchange_rate')
                                    ->label('Курс')
                                    ->state(function ($record) {
                                        $saleableId = $record->saleable_id ?? null;
                                        if (!$saleableId) return '—';
                                        
                                        $device = \App\Models\Device::withoutGlobalScopes()->find($saleableId);
                                        if (!$device) return '—';
                                        
                                        return number_format($device->exchange_rate ?? 1, 2, ',', ' ');
                                    })
                                    ->color('gray'),
                            ]),
                        Infolists\Components\TextEntry::make('device_price_uah')
                            ->label('Вартість у гривні')
                            ->state(function ($record) {
                                $saleableId = $record->saleable_id ?? null;
                                if (!$saleableId) return '—';
                                
                                $device = \App\Models\Device::withoutGlobalScopes()->find($saleableId);
                                if (!$device) return '—';
                                
                                $price = ($device->purchase_price_currency ?? 0) * ($device->exchange_rate ?? 1);
                                if ($price == 0) return 'Не вказано';
                                return number_format($price, 2, ',', ' ') . ' ₴';
                            })
                            ->weight('bold')
                            ->color('gray'),
                        Infolists\Components\TextEntry::make('device_additional_costs')
                            ->label('Додаткові витрати')
                            ->state(function ($record) {
                                $saleableId = $record->saleable_id ?? null;
                                if (!$saleableId) return '—';
                                
                                $device = \App\Models\Device::withoutGlobalScopes()->find($saleableId);
                                if (!$device) return '—';
                                
                                $additional = $device->additional_costs ?? 0;
                                return number_format($additional, 2, ',', ' ') . ' ₴';
                            })
                            ->color('gray')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('device_parts_list')
                            ->label('Запчастини та комплектуючі')
                            ->visible(function ($record) {
                                $saleableId = $record->saleable_id ?? null;
                                if (!$saleableId) return false;
                                
                                $device = \App\Models\Device::withoutGlobalScopes()->with('parts')->find($saleableId);
                                return $device && $device->parts->count() > 0;
                            })
                            ->state(function ($record) {
                                $saleableId = $record->saleable_id ?? null;
                                if (!$saleableId) return 'Немає запчастин';
                                
                                $device = \App\Models\Device::withoutGlobalScopes()
                                    ->with(['parts.partType', 'parts.contractor'])
                                    ->find($saleableId);
                                
                                if (!$device || $device->parts->isEmpty()) {
                                    return 'Немає запчастин';
                                }
                                
                                $list = [];
                                foreach ($device->parts as $part) {
                                    $partTypeName = $part->partType->name ?? 'Деталь';
                                    $partName = $part->name;
                                    $contractorName = $part->contractor ? ' (Пост: ' . $part->contractor->name . ')' : '';
                                    $cost = number_format($part->cost_uah, 2, ',', ' ') . ' ₴';
                                    $list[] = '• ' . $partTypeName . ': ' . $partName . $contractorName . ' → **+' . $cost . '**';
                                }
                                
                                return implode("\n", $list);
                            })
                            ->markdown()
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('device_parts_total')
                            ->label('Загальна вартість запчастин')
                            ->visible(function ($record) {
                                $saleableId = $record->saleable_id ?? null;
                                if (!$saleableId) return false;
                                
                                $device = \App\Models\Device::withoutGlobalScopes()->with('parts')->find($saleableId);
                                return $device && $device->parts->count() > 0;
                            })
                            ->state(function ($record) {
                                $saleableId = $record->saleable_id ?? null;
                                if (!$saleableId) return '0 ₴';
                                
                                $device = \App\Models\Device::withoutGlobalScopes()->with('parts')->find($saleableId);
                                if (!$device) return '0 ₴';
                                
                                $totalPartsCost = $device->parts->sum('cost_uah');
                                return number_format($totalPartsCost, 2, ',', ' ') . ' ₴';
                            })
                            ->color('gray')
                            ->weight('bold')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('device_total_cost')
                            ->label('Загальна собівартість')
                            ->state(function ($record) {
                                $saleableId = $record->saleable_id ?? null;
                                if (!$saleableId) {
                                    return number_format($record->buy_price ?? 0, 2, ',', ' ') . ' ₴';
                                }
                                
                                $device = \App\Models\Device::withoutGlobalScopes()->with('parts')->find($saleableId);
                                if (!$device) {
                                    return number_format($record->buy_price ?? 0, 2, ',', ' ') . ' ₴';
                                }
                                
                                $total = $device->purchase_cost ?? 0;
                                
                                if ($total == 0) {
                                    $price = ($device->purchase_price_currency ?? 0) * ($device->exchange_rate ?? 1);
                                    $additional = $device->additional_costs ?? 0;
                                    $partsCost = $device->parts->sum('cost_uah');
                                    $total = $price + $additional + $partsCost;
                                }
                                
                                if ($total == 0 && $record->buy_price > 0) {
                                    return number_format($record->buy_price, 2, ',', ' ') . ' ₴';
                                }
                                
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
            ]);
    }
}
