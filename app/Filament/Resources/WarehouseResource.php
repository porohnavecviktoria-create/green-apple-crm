<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseResource\Pages;
use App\Filament\Resources\WarehouseResource\RelationManagers;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WarehouseResource extends Resource
{
    protected static ?string $navigationLabel = 'Склади';
    protected static ?string $pluralModelLabel = 'Склади';
    protected static ?string $modelLabel = 'Склад';
    protected static ?string $navigationGroup = 'Довідники';
    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static ?int $navigationSort = 104;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Назва складу')
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label('Тип складу')
                    ->options([
                        'Technic' => '📱 Техніка / Запчастини',
                        'Accessory' => '🎁 Аксесуари',
                        'Inventory' => '🛠 Інвентар',
                    ])
                    ->required()
                    ->default('Technic'),
                Forms\Components\TextInput::make('location')
                    ->label('Місцезнаходження'),
                Forms\Components\Textarea::make('description')
                    ->label('Опис')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Назва')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Technic' => 'info',
                        'Accessory' => 'success',
                        'Inventory' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('location')
                    ->label('Локація'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
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
            'index' => Pages\ListWarehouses::route('/'),
            'create' => Pages\CreateWarehouse::route('/create'),
            'edit' => Pages\EditWarehouse::route('/{record}/edit'),
        ];
    }
}
