<x-filament-widgets::widget>
    <div class="w-full">
        @php
            $data = $this->getServiceData();
        @endphp
        
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Сервіс</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">За обраний період</p>
            </div>
            <div class="p-6 space-y-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300">Показник</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300">Сума</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">Дохід сервісу</td>
                                <td class="px-6 py-4 text-right font-bold text-green-600 dark:text-green-400">{{ number_format($data['period']['income'], 2) }} грн</td>
                            </tr>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">Витрати сервісу</td>
                                <td class="px-6 py-4 text-right font-bold text-red-600 dark:text-red-400">{{ number_format($data['period']['expenses'], 2) }} грн</td>
                            </tr>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">Прибуток сервісу</td>
                                <td class="px-6 py-4 text-right font-bold {{ $data['period']['profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ number_format($data['period']['profit'], 2) }} грн
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="bg-gray-50 dark:bg-gray-700/50 p-6 rounded-lg border border-gray-200 dark:border-gray-600">
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Загальна сума деталей на складі</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($data['parts_in_stock'], 2) }} грн</div>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
