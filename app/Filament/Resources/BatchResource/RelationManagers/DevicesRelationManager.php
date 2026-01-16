<?php

namespace App\Filament\Resources\BatchResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DevicesRelationManager extends RelationManager
{
    protected static string $relationship = 'devices';
    protected static ?string $title = 'Пристрої в партії';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('model')
                            ->label('Модель')
                            ->required(),
                        Forms\Components\TextInput::make('marker')
                            ->label('Маркер'),
                    ]),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('imei')
                            ->label('IMEI/SN'),
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
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('purchase_cost')
                            ->label('Собівартість')
                            ->numeric()
                            ->prefix('₴')
                            ->required(),
                        Forms\Components\TextInput::make('additional_costs')
                            ->label('Окремі витрати')
                            ->numeric()
                            ->prefix('₴')
                            ->default(0),
                    ]),
                Forms\Components\Textarea::make('description')
                    ->label('Коментар')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('model')
            ->columns([
                Tables\Columns\TextColumn::make('model')
                    ->label('Модель')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('marker')
                    ->label('Маркер')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                Tables\Columns\TextColumn::make('imei')
                    ->label('IMEI')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lock_status')
                    ->label('Блокування')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            'unlock' => '🔓 Unlock',
                            'lock' => '🔒 Lock',
                            'mdm' => '📱 MDM',
                            'bypass' => '🔓 Bypass',
                            default => '—'
                        };
                    })
                    ->color(fn($state) => match($state) {
                        'unlock' => 'success',
                        'lock' => 'danger',
                        'mdm' => 'warning',
                        'bypass' => 'info',
                        default => 'gray'
                    }),
                Tables\Columns\TextColumn::make('purchase_cost')
                    ->label('Собівартість')
                    ->money('UAH')
                    ->color('gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('additional_costs')
                    ->label('Витрати')
                    ->money('UAH')
                    ->color('warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Загальна вартість')
                    ->money('UAH')
                    ->getStateUsing(function ($record) {
                        return ($record->purchase_cost ?? 0) + ($record->additional_costs ?? 0);
                    })
                    ->weight('bold')
                    ->color('danger')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Додати пристрій'),
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
}
