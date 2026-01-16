@php
    $items = $items ?? [];
    $warehouseType = $warehouseType ?? '';
    $selectedId = $selectedId ?? null;
    $editUrl = $editUrl ?? null;
@endphp

<div class="space-y-2" x-data="{ setSaleableId(id) { 
    // Знаходимо Hidden поле saleable_id в тому ж Repeater item
    const repeaterItem = $el.closest('[wire\\:id]');
    if (repeaterItem) {
        // Шукаємо Hidden input поле через різні можливі селектори
        let hiddenInput = repeaterItem.querySelector('input[type=\"hidden\"][name*=\"saleable_id\"]');
        if (!hiddenInput) {
            // Спробуємо знайти через data-атрибут або інші способи
            hiddenInput = repeaterItem.querySelector('input[data-name*=\"saleable_id\"]');
        }
        if (!hiddenInput) {
            // Шукаємо в усіх input полях
            const allInputs = repeaterItem.querySelectorAll('input[type=\"hidden\"]');
            for (let input of allInputs) {
                if (input.name && input.name.includes('saleable_id')) {
                    hiddenInput = input;
                    break;
                }
            }
        }
        
        if (hiddenInput) {
            hiddenInput.value = id;
            // Викликаємо події для оновлення Livewire
            hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            // Також спробуємо через wire:model
            if (hiddenInput.hasAttribute('wire:model')) {
                hiddenInput.dispatchEvent(new Event('livewire:update', { bubbles: true }));
            }
        } else {
            console.warn('Hidden input saleable_id not found');
        }
    }
} }">
    <div class="flex items-center justify-between mb-3">
        <span class="block text-sm font-medium text-gray-700">Виберіть товар</span>
        <span class="text-xs text-gray-500">Знайдено: {{ count($items) }} товарів</span>
    </div>
    
    @if(count($items) > 0)
        <div class="max-h-96 overflow-y-auto space-y-2 pr-2">
            @foreach($items as $item)
                <label 
                    class="flex items-start gap-3 p-3 border rounded-lg hover:bg-gray-50 transition-colors cursor-pointer {{ $selectedId == $item->id ? 'border-primary-500 bg-primary-50 ring-2 ring-primary-200' : '' }}"
                >
                    <input 
                        type="radio" 
                        name="saleable_id" 
                        value="{{ $item->id }}" 
                        @if($selectedId == $item->id) checked @endif
                        class="h-4 w-4 mt-1 text-primary-600 focus:ring-primary-500 border-gray-300"
                        x-on:change="setSaleableId({{ $item->id }})"
                    >
                    <div class="flex-1">
                        @if($warehouseType === 'device')
                            <div class="font-semibold text-gray-900 mb-1">{{ $item->model }}</div>
                            <div class="text-sm text-gray-600 space-y-1">
                                <div class="flex flex-wrap gap-x-4 gap-y-1">
                                    @if($item->marker)
                                        <span class="font-medium">🏷 Маркер: <span class="text-gray-900">{{ $item->marker }}</span></span>
                                    @else
                                        <span class="text-gray-400">🏷 Маркер: не вказано</span>
                                    @endif
                                    @if($item->imei)
                                        <span class="font-medium">📱 IMEI: <span class="text-gray-900">{{ $item->imei }}</span></span>
                                    @else
                                        <span class="text-gray-400">📱 IMEI: не вказано</span>
                                    @endif
                                </div>
                                @if($item->description)
                                    <div class="mt-1 text-gray-700">
                                        <span class="font-medium">💬 Коментар:</span> {{ mb_substr($item->description, 0, 100) }}{{ mb_strlen($item->description) > 100 ? '...' : '' }}
                                    </div>
                                @else
                                    <div class="mt-1 text-gray-400">💬 Коментар: не вказано</div>
                                @endif
                            </div>
                        @else
                            <div class="font-semibold text-gray-900 mb-1">
                                {{ $item->name }}
                                @if($item->quantity > 0)
                                    <span class="ml-2 text-sm font-normal text-gray-500">(Наявно: {{ $item->quantity }} шт)</span>
                                @endif
                            </div>
                            <div class="text-sm text-gray-600 flex flex-wrap gap-x-4 gap-y-1">
                                @if($item->cost_uah)
                                    <span class="font-medium">💰 Собівартість: <span class="text-gray-900">{{ number_format($item->cost_uah, 0, ',', ' ') }} ₴</span></span>
                                @endif
                            </div>
                        @endif
                    </div>
                    @if($editUrl)
                        <a 
                            href="{{ $editUrl($item->id) }}" 
                            target="_blank"
                            class="inline-flex items-center px-2 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-primary-50 hover:text-primary-700 hover:border-primary-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors flex-shrink-0"
                            title="Редагувати товар"
                            onclick="event.stopPropagation(); event.preventDefault(); window.open('{{ $editUrl($item->id) }}', '_blank');"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </a>
                    @endif
                </label>
            @endforeach
        </div>
    @else
        <div class="text-center text-gray-500 py-8 border border-gray-200 rounded-lg">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
            </svg>
            <p class="mt-2 text-sm font-medium">Товари не знайдено</p>
            <p class="mt-1 text-xs text-gray-400">Оберіть склад для відображення товарів</p>
        </div>
    @endif
</div>
