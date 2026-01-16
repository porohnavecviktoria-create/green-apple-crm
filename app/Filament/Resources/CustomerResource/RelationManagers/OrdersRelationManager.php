<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';
    protected static ?string $title = 'Історія замовлень';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_number')
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('№ Чека')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
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
                Tables\Columns\TextColumn::make('notes')
                    ->label('Примітка')
                    ->limit(30)
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('completed_at', 'desc')
            ->filters([])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Переглянути')
                    ->url(fn($record) => \App\Filament\Resources\OrderResource::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([]);
    }
}
