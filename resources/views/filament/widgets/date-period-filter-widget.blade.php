<x-filament-widgets::widget>
    <div class="w-full">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">Період аналітики</h3>
                </div>
                <div class="flex items-center gap-4 flex-wrap">
                    <div class="flex items-center gap-3">
                        <label class="text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">Від:</label>
                        <input 
                            type="date" 
                            wire:model.live="dateFrom" 
                            class="block w-full rounded-lg border-gray-300 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm ring-1 ring-gray-300 dark:ring-gray-600 focus:border-primary-500 focus:ring-2 focus:ring-primary-500 dark:border-gray-600"
                        >
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">До:</label>
                        <input 
                            type="date" 
                            wire:model.live="dateTo" 
                            class="block w-full rounded-lg border-gray-300 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm ring-1 ring-gray-300 dark:ring-gray-600 focus:border-primary-500 focus:ring-2 focus:ring-primary-500 dark:border-gray-600"
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
