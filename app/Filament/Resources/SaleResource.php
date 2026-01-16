<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Filament\Resources\SaleResource\RelationManagers;
use App\Models\Sale;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationLabel = 'Історія (по товарах)';
    protected static ?string $pluralModelLabel = 'Продані товари';
    protected static ?string $modelLabel = 'Продаж';
    protected static ?string $navigationGroup = 'Продажі';
    protected static ?int $navigationSort = 22;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['saleable' => function ($morphTo) {
                $morphTo->morphWith([
                    \App\Models\Device::class => [],
                    \App\Models\Part::class => ['partType'],
                ]);
            }]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Placeholder::make('product_name')
                            ->label('Товар')
                            ->content(fn($record) => $record->saleable instanceof \App\Models\Device ? "📱 " . $record->saleable->model : "🛠 " . $record->saleable->name),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('buy_price')->label('Собівартість')->numeric()->suffix('грн')->disabled(),
                                Forms\Components\TextInput::make('sell_price')->label('Ціна продажу')->numeric()->suffix('грн')->disabled(),
                            ]),
                        Forms\Components\TextInput::make('profit')->label('Прибуток')->numeric()->suffix('грн')->disabled(),
                        Forms\Components\Textarea::make('description')->label('Примітка')->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sold_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('saleable')
                    ->label('Товар')
                    ->state(function (Sale $record) {
                        if ($record->saleable instanceof \App\Models\Device) {
                            return $record->saleable->model;
                        }
                        if ($record->saleable instanceof \App\Models\Part) {
                            return $record->saleable->name . ($record->quantity > 1 ? " ({$record->quantity} шт)" : "");
                        }
                        return '—';
                    })
                    ->weight('bold')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHasMorph('saleable', [\App\Models\Device::class, \App\Models\Part::class], function (Builder $query, string $type) use ($search) {
                            if ($type === \App\Models\Device::class) {
                                $query->where('model', 'like', "%{$search}%");
                            } else {
                                $query->where('name', 'like', "%{$search}%");
                            }
                        });
                    }),
                Tables\Columns\TextColumn::make('device_marker')
                    ->label('Маркер')
                    ->state(function (Sale $record) {
                        if ($record->saleable instanceof \App\Models\Device) {
                            return $record->saleable->marker ?? '—';
                        }
                        return '—';
                    })
                    ->badge()
                    ->color('info')
                    ->toggleable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHasMorph('saleable', [\App\Models\Device::class], function (Builder $query) use ($search) {
                            $query->where('marker', 'like', "%{$search}%");
                        });
                    }),
                Tables\Columns\TextColumn::make('device_imei')
                    ->label('IMEI/SN')
                    ->state(function (Sale $record) {
                        if ($record->saleable instanceof \App\Models\Device) {
                            return $record->saleable->imei ?? '—';
                        }
                        return '—';
                    })
                    ->badge()
                    ->color('info')
                    ->toggleable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHasMorph('saleable', [\App\Models\Device::class], function (Builder $query) use ($search) {
                            $query->where('imei', 'like', "%{$search}%");
                        });
                    }),
                Tables\Columns\TextColumn::make('sell_price')
                    ->label('Сума')
                    ->money('UAH')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\TextColumn::make('profit')
                    ->label('Прибуток')
                    ->money('UAH')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Продавець')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sold_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('sold_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('З'),
                        Forms\Components\DatePicker::make('until')->label('По'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn(Builder $query, $date): Builder => $query->whereDate('sold_at', '>=', $date))
                            ->when($data['until'], fn(Builder $query, $date): Builder => $query->whereDate('sold_at', '<=', $date));
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ManageSales::route('/'),
            'view' => Pages\ViewSale::route('/{record}'),
        ];
    }
}
