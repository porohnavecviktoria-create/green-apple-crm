<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Застійний товар (14+ днів)
        </x-slot>
        
        @php
            $stuckInventory = $this->getStuckInventory();
        @endphp
        
        @if(count($stuckInventory) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-4 py-3">Товар</th>
                            <th scope="col" class="px-4 py-3">Днів на складі</th>
                            <th scope="col" class="px-4 py-3">Вартість</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stuckInventory as $item)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ $item['name'] }}
                                    <span class="ml-2 text-xs text-gray-500">({{ $item['type'] }})</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                        {{ $item['days_in_stock'] }} дн.
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-orange-600 dark:text-orange-400">{{ number_format($item['cost'], 2) }} грн</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400">Немає застійного товару</p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
