<div class="p-4 space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div class="text-gray-500">Ціна закупівлі:</div>
        <div class="font-bold text-right">{{ $record->purchase_price_currency }} {{ $record->purchase_currency }}</div>

        <div class="text-gray-500">Курс:</div>
        <div class="font-bold text-right">{{ $record->exchange_rate }}</div>

        <div class="border-t pt-2 text-gray-800 font-semibold">Вартість у гривні:</div>
        <div class="border-t pt-2 font-bold text-right">
            ₴{{ number_format($record->purchase_price_currency * $record->exchange_rate, 2) }}</div>

        <div class="text-gray-500">Додаткові витрати:</div>
        <div class="font-bold text-right text-gray-700">+ ₴{{ number_format($record->additional_costs, 2) }}</div>
        
        @if($record->additional_costs_note)
            <div class="text-gray-500 text-sm italic mt-2 col-span-2 border-l-2 border-gray-300 pl-2">
                <div class="font-medium mb-1">Коментар:</div>
                <div>{{ $record->additional_costs_note }}</div>
            </div>
        @endif
    </div>

    @if($record->parts->count() > 0)
        <div class="border-t pt-4">
            <h4 class="text-sm font-bold text-gray-700 mb-2 uppercase tracking-wider">Запчастини та комплектуючі:</h4>
            <div class="space-y-2">
                @foreach($record->parts as $part)
                    @php
                        $quantity = $part->pivot->quantity ?? 1;
                        $totalCost = $part->cost_uah * $quantity;
                    @endphp
                    <div class="flex justify-between text-sm">
                        <div class="flex flex-col">
                            <span class="font-medium">{{ $part->type_label }}: {{ $part->name }}</span>
                            @if($quantity > 1)
                                <span class="text-xs text-gray-500">Кількість: {{ $quantity }} шт.</span>
                            @endif
                            <span class="text-xs text-gray-500">Постачальник:
                                {{ $part->contractor?->name ?? 'Не вказано' }}</span>
                        </div>
                        <div class="text-right">
                            @if($quantity > 1)
                                <div class="text-xs text-gray-500">₴{{ number_format($part->cost_uah, 2) }} × {{ $quantity }}</div>
                            @endif
                            <span class="font-medium text-gray-700">+ ₴{{ number_format($totalCost, 2) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="border-t pt-4 flex justify-between items-baseline">
        <div class="text-lg font-extrabold text-gray-900">ЗАГАЛЬНА СОБІВАРТІСТЬ:</div>
        <div class="text-2xl font-black text-gray-700">₴{{ number_format($record->purchase_cost, 2) }}</div>
    </div>
</div>