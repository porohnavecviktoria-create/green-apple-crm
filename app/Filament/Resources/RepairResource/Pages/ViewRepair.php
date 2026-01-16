<?php

namespace App\Filament\Resources\RepairResource\Pages;

use App\Filament\Resources\RepairResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewRepair extends ViewRecord
{
    protected static string $resource = RepairResource::class;

    protected static ?string $title = 'Перегляд ремонту';

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
            'parts.partType',
            'parts.contractor',
            'customer'
        ]);
        
        return $record;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Інформація про ремонт')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('customer.name')
                                    ->label('Клієнт')
                                    ->formatStateUsing(function ($record) {
                                        $customer = $record->customer;
                                        if ($customer->name) {
                                            return $customer->name;
                                        }
                                        return $customer->phone;
                                    })
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('phone_model')
                                    ->label('Модель телефону')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold'),
                            ]),
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('imei')
                                    ->label('IMEI')
                                    ->default('Не вказано')
                                    ->color('gray'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Статус')
                                    ->badge()
                                    ->formatStateUsing(fn(string $state): string => match ($state) {
                                        'pending' => 'В очікуванні',
                                        'in_progress' => 'В роботі',
                                        'completed' => 'Виконано',
                                        'issued' => 'Видано клієнту',
                                        default => $state,
                                    })
                                    ->color(fn(string $state): string => match ($state) {
                                        'pending' => 'gray',
                                        'in_progress' => 'warning',
                                        'completed' => 'success',
                                        'issued' => 'info',
                                        default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Дата створення')
                                    ->dateTime('d.m.Y H:i')
                                    ->color('gray'),
                            ]),
                        Infolists\Components\TextEntry::make('problem_description')
                            ->label('Опис проблеми')
                            ->default('Не вказано')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Використані деталі')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('parts')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Деталь')
                                    ->formatStateUsing(function ($record) {
                                        $part = $record;
                                        $info = $part->name;
                                        if ($part->partType) {
                                            $info .= ' (' . $part->partType->name . ')';
                                        }
                                        return $info;
                                    })
                                    ->weight('bold')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->columnSpanFull(),
                                
                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('pivot.quantity')
                                            ->label('Кількість')
                                            ->badge()
                                            ->color('info')
                                            ->size(Infolists\Components\TextEntry\TextEntrySize::Medium),
                                        Infolists\Components\TextEntry::make('pivot.cost_per_unit')
                                            ->label('Собівартість за одиницю')
                                            ->money('UAH')
                                            ->color('gray'),
                                        Infolists\Components\TextEntry::make('total_cost')
                                            ->label('Загальна вартість')
                                            ->formatStateUsing(function ($record) {
                                                $quantity = $record->pivot->quantity ?? 1;
                                                $costPerUnit = $record->pivot->cost_per_unit ?? 0;
                                                $total = $quantity * $costPerUnit;
                                                return number_format($total, 2, ',', ' ') . ' ₴';
                                            })
                                            ->color('gray')
                                            ->weight('bold')
                                            ->size(Infolists\Components\TextEntry\TextEntrySize::Medium),
                                    ]),
                            ])
                            ->columns(1),
                    ]),

                Infolists\Components\Section::make('Фінансова інформація')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('parts_cost')
                                    ->label('Собівартість деталей')
                                    ->money('UAH')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Medium)
                                    ->weight('bold')
                                    ->color('gray'),
                                Infolists\Components\TextEntry::make('repair_cost')
                                    ->label('Вартість ремонту')
                                    ->money('UAH')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Medium)
                                    ->weight('bold')
                                    ->color('success'),
                                Infolists\Components\TextEntry::make('profit')
                                    ->label('Прибуток')
                                    ->money('UAH')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Medium)
                                    ->weight('bold')
                                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Додаткова інформація')
                    ->schema([
                        Infolists\Components\TextEntry::make('description')
                            ->label('Виконані роботи / Коментар')
                            ->default('Немає коментарів')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
