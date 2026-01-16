<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Топ-5 продажів за прибутком
        </x-slot>
        
        @php
            $topSales = $this->getTopSales();
        @endphp
        
        @if(count($topSales) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-4 py-3">Товар</th>
                            <th scope="col" class="px-4 py-3">Дохід</th>
                            <th scope="col" class="px-4 py-3">Прибуток</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topSales as $sale)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $sale['name'] }}</td>
                                <td class="px-4 py-3 text-green-600 dark:text-green-400">{{ number_format($sale['revenue'], 2) }} грн</td>
                                <td class="px-4 py-3 text-green-600 dark:text-green-400 font-bold">{{ number_format($sale['profit'], 2) }} грн</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400">Немає продажів за обраний період</p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
