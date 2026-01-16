<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BatchResource\Pages;
use App\Filament\Resources\BatchResource\RelationManagers;
use App\Models\Batch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BatchResource extends Resource
{
    protected static ?string $model = Batch::class;

    protected static ?string $navigationLabel = 'Партії товарів';
    protected static ?string $pluralModelLabel = 'Партії';
    protected static ?string $modelLabel = 'Партія';
    protected static ?string $navigationGroup = 'Склад';
    protected static ?int $navigationSort = 11;
    protected static ?string $navigationIcon = null;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Інформація про партію')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Назва партії')
                            ->required(),
                        Forms\Components\DatePicker::make('purchase_date')
                            ->label('Дата поступлення')
                            ->default(now()),
                        Forms\Components\Textarea::make('description')
                            ->label('Опис')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                return $query->with('devices');
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Назва партії')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('devices_count')
                    ->counts('devices')
                    ->label('Кількість пристроїв')
                    ->badge()
                    ->color('info')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Medium),
                Tables\Columns\TextColumn::make('total_purchase_cost')
                    ->label('Собівартість')
                    ->money('UAH')
                    ->getStateUsing(function ($record) {
                        return $record->devices->sum('purchase_cost');
                    })
                    ->color('gray'),
                Tables\Columns\TextColumn::make('total_additional_costs')
                    ->label('Витрати')
                    ->money('UAH')
                    ->getStateUsing(function ($record) {
                        return $record->devices->sum('additional_costs');
                    })
                    ->color('gray'),
                Tables\Columns\TextColumn::make('total_expenses')
                    ->label('Загальна сума витрат')
                    ->money('UAH')
                    ->getStateUsing(function ($record) {
                        $purchaseCost = $record->devices->sum('purchase_cost');
                        $additionalCosts = $record->devices->sum('additional_costs');
                        return $purchaseCost + $additionalCosts;
                    })
                    ->weight('bold')
                    ->color('gray')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Medium),
                Tables\Columns\TextColumn::make('purchase_date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable()
                    ->color('gray'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            RelationManagers\DevicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBatches::route('/'),
            'create' => Pages\CreateBatch::route('/create'),
            'view' => Pages\ViewBatch::route('/{record}'),
            'edit' => Pages\EditBatch::route('/{record}/edit'),
        ];
    }
}
