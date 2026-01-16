<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

аідклю    protected static ?string $title = 'Новий продаж';

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Створити чек');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()
            ->label('Створити та додати ще один');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Скасувати');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        \Log::info('mutateFormDataBeforeCreate', ['data' => $data]);
        
        $data['user_id'] = auth()->id();
        $data['completed_at'] = now();

        // Для Repeater з ->relationship() дані про sales обробляються окремо
        // через mutateRelationshipDataBeforeCreateUsing, тому тут їх може не бути
        // Валідація sales відбувається в OrderResource через mutateRelationshipDataBeforeCreateUsing

        return $data;
    }

    protected function afterCreate(): void
    {
        $order = $this->record;
        $order->load('sales.saleable');

        $totalAmount = 0;
        $totalProfit = 0;

        foreach ($order->sales as $sale) {
            $totalAmount += $sale->sell_price * ($sale->quantity ?: 1);
            $totalProfit += $sale->profit;

            $item = $sale->saleable;
            if ($item instanceof \App\Models\Device) {
                $item->update(['status' => 'Sold', 'selling_price' => $sale->sell_price]);
            } elseif ($item instanceof \App\Models\Part) {
                $item->decrement('quantity', $sale->quantity ?: 1);
            }
        }

        $order->update([
            'total_amount' => $totalAmount,
            'total_profit' => $totalProfit,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('create');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Чек успішно створено! ✅';
    }
}
