<x-filament-widgets::widget>
    @php
        $data = $this->getShopData();
    @endphp
    
    <div class="w-full">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Аналітика по складам</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300">Склад</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300">Дохід</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300">Собівартість</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300">Чистий прибуток</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">Техніка</td>
                            <td class="px-6 py-4 text-right font-bold text-green-600 dark:text-green-400">{{ number_format($data['devices']['sales'], 2) }} грн</td>
                            <td class="px-6 py-4 text-right text-gray-600 dark:text-gray-400">{{ number_format($data['devices']['cost'], 2) }} грн</td>
                            <td class="px-6 py-4 text-right font-bold text-green-600 dark:text-green-400">+{{ number_format($data['devices']['profit'], 2) }} грн</td>
                        </tr>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">Аксесуари</td>
                            <td class="px-6 py-4 text-right font-bold text-green-600 dark:text-green-400">{{ number_format($data['accessories']['sales'], 2) }} грн</td>
                            <td class="px-6 py-4 text-right text-gray-600 dark:text-gray-400">{{ number_format($data['accessories']['cost'], 2) }} грн</td>
                            <td class="px-6 py-4 text-right font-bold text-green-600 dark:text-green-400">+{{ number_format($data['accessories']['profit'], 2) }} грн</td>
                        </tr>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">Ремонти</td>
                            <td class="px-6 py-4 text-right font-bold text-green-600 dark:text-green-400">{{ number_format($data['repairs']['sales'], 2) }} грн</td>
                            <td class="px-6 py-4 text-right text-gray-600 dark:text-gray-400">{{ number_format($data['repairs']['cost'], 2) }} грн</td>
                            <td class="px-6 py-4 text-right font-bold text-green-600 dark:text-green-400">+{{ number_format($data['repairs']['profit'], 2) }} грн</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
