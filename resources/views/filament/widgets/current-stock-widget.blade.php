<x-filament-widgets::widget>
    @php
        $data = $this->getStockData();
    @endphp
    
    <div class="w-full">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Поточний стан складу</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300">Категорія</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300">Сума в наявності</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">Техніка</td>
                            <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">{{ number_format($data['devices'], 2) }} грн</td>
                        </tr>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">Деталі</td>
                            <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">{{ number_format($data['parts'], 2) }} грн</td>
                        </tr>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">Інвентар</td>
                            <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">{{ number_format($data['inventory'], 2) }} грн</td>
                        </tr>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">Розхідники</td>
                            <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">{{ number_format($data['consumables'], 2) }} грн</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
