<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationLabel = 'Клієнтська база';
    protected static ?string $pluralModelLabel = 'Клієнти';
    protected static ?string $modelLabel = 'Клієнт';
    protected static ?string $navigationIcon = null;
    protected static ?string $navigationGroup = 'Продажі';
    protected static ?int $navigationSort = 23;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Ім\'я')
                    ->placeholder('Іван Іванов'),
                Forms\Components\TextInput::make('phone')
                    ->label('Номер телефону')
                    ->tel()
                    ->required()
                    ->placeholder('+380...'),
                Forms\Components\Textarea::make('notes')
                    ->label('Примітка')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Ім\'я')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Чеків')
                    ->counts('orders')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('total_spent')
                    ->label('Загальна сума')
                    ->money('UAH')
                    ->getStateUsing(function ($record) {
                        return $record->orders()->sum('total_amount');
                    })
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Примітка')
                    ->limit(50)
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            RelationManagers\OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCustomers::route('/'),
            'view' => Pages\ViewCustomer::route('/{record}'),
        ];
    }
}
