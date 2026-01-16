<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartTypeResource\Pages;
use App\Filament\Resources\PartTypeResource\RelationManagers;
use App\Models\PartType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PartTypeResource extends Resource
{
    protected static ?string $model = PartType::class;

    protected static ?string $navigationLabel = 'Типи запчастин';
    protected static ?string $pluralModelLabel = 'Типи запчастин';
    protected static ?string $modelLabel = 'Тип запчастини';
    protected static ?string $navigationGroup = 'Довідники';
    protected static ?string $navigationIcon = null;
    protected static ?int $navigationSort = 102;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Назва типу')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->placeholder('напр. Дисплей, Акумулятор'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Назва')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePartTypes::route('/'),
        ];
    }
}
