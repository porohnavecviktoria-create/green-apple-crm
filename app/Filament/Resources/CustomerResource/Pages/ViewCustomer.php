<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected static ?string $title = 'Перегляд клієнта';

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
            'orders' => function ($query) {
                $query->orderBy('completed_at', 'desc');
            }
        ]);
        
        return $record;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Інформація про клієнта')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Ім\'я')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Medium)
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('phone')
                                    ->label('Телефон')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Medium)
                                    ->color('gray'),
                            ]),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('orders_count')
                                    ->label('Кількість чеків')
                                    ->formatStateUsing(fn($record) => $record->orders->count())
                                    ->badge()
                                    ->color('info')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Medium),
                                Infolists\Components\TextEntry::make('total_spent')
                                    ->label('Загальна сума покупок')
                                    ->money('UAH')
                                    ->formatStateUsing(function ($record) {
                                        return $record->orders->sum('total_amount');
                                    })
                                    ->weight('bold')
                                    ->color('success')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Medium),
                            ]),
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Примітка')
                            ->default('Немає приміток')
                            ->columnSpanFull()
                            ->color('gray'),
                    ]),

            ]);
    }
}
