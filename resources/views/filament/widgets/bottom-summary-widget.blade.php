<x-filament-widgets::widget>
    <div class="w-full">
        @php
            $data = $this->getSummaryData();
        @endphp
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 p-8 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-3">Списано товарів за період</div>
                <div class="text-3xl font-bold text-red-600 dark:text-red-400 mb-1">{{ number_format($data['write_offs'], 2) }} <span class="text-lg">грн</span></div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Загальна вартість списаних товарів</div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 p-8 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-3">Інвентар в наявності</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ number_format($data['inventory_in_stock'], 2) }} <span class="text-lg">грн</span></div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Поточна вартість інвентарю на складі</div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
