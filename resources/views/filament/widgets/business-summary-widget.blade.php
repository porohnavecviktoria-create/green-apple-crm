<x-filament-widgets::widget>
    @php
        $data = $this->getBusinessData();
    @endphp
    
    <div class="w-full">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Загальний дохід -->
            <div class="bg-white dark:bg-gray-800 p-8 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-3">Загальний дохід бізнесу</div>
                <div class="text-3xl font-bold text-green-600 dark:text-green-400 mb-1">{{ number_format($data['total_income'], 2) }} <span class="text-lg">грн</span></div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Техніка + аксесуари + ремонти</div>
            </div>
            
            <!-- Загальні розходи -->
            <div class="bg-white dark:bg-gray-800 p-8 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-3">Загальні розходи</div>
                <div class="text-3xl font-bold text-red-600 dark:text-red-400 mb-1">{{ number_format($data['total_expenses'], 2) }} <span class="text-lg">грн</span></div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Закупки + витрати деталей + списання</div>
            </div>
            
            <!-- Прибуток -->
            <div class="bg-white dark:bg-gray-800 p-8 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-3">Прибуток</div>
                <div class="text-3xl font-bold {{ $data['profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} mb-1">
                    {{ number_format($data['profit'], 2) }} <span class="text-lg">грн</span>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $data['profit'] >= 0 ? 'Позитивний результат' : 'Негативний результат' }}</div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
