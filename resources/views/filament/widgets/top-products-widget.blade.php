<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            @php
                $heading = match($this->filter) {
                    'profit' => 'Топ-10 товарів за прибутком',
                    'revenue' => 'Топ-10 товарів за доходом',
                    'quantity' => 'Топ-10 товарів за кількістю продажів',
                    default => 'Топ-10 товарів',
                };
            @endphp
            {{ $heading }}
        </x-slot>
        
        <x-slot name="description">
            @php
                $description = match($this->filter) {
                    'profit' => 'Найприбутковіші товари',
                    'revenue' => 'Товари з найбільшим оборотом',
                    'quantity' => 'Найбільш продавані товари',
                    default => 'Топ товари',
                };
            @endphp
            {{ $description }}
        </x-slot>
        
        @php
            $topProducts = $this->getTopProducts();
        @endphp
        
        @if(count($topProducts) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-4 py-3">Товар</th>
                            <th scope="col" class="px-4 py-3">Тип</th>
                            <th scope="col" class="px-4 py-3">Кількість</th>
                            <th scope="col" class="px-4 py-3">Дохід</th>
                            <th scope="col" class="px-4 py-3">Прибуток</th>
                            <th scope="col" class="px-4 py-3">Продажів</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topProducts as $product)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $product['name'] }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $typeColor = match($product['type']) {
                                            'Техніка' => 'success',
                                            'Деталь' => 'info',
                                            'Аксесуар' => 'warning',
                                            'Інвентар' => 'gray',
                                            'Розхідник' => 'primary',
                                            default => 'gray',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $typeColor }}-100 text-{{ $typeColor }}-800">
                                        {{ $product['type'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ number_format($product['quantity'], 0) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-green-600 font-medium">
                                    {{ number_format($product['revenue'], 2) }} грн
                                </td>
                                <td class="px-4 py-3 text-green-600 font-bold">
                                    {{ number_format($product['profit'], 2) }} грн
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ $product['sales_count'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400">Немає даних для відображення</p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
