<div class="flex items-center justify-start py-1" x-on:click.stop="">
    <button type="button" wire:click="mountTableAction('show_breakdown', '{{ $getRecord()->id }}')" style="background-color: #16a34a !important; 
                   color: #ffffff !important; 
                   font-weight: 900 !important; 
                   padding: 6px 12px !important; 
                   border-radius: 6px !important; 
                   border: 2px solid #15803d !important; 
                   cursor: pointer !important; 
                   display: inline-flex !important; 
                   align-items: center !important; 
                   gap: 6px !important; 
                   font-size: 11px !important; 
                   text-transform: uppercase !important; 
                   letter-spacing: 0.05em !important; 
                   box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
                   transition: all 0.2s !important;"
        onmouseover="this.style.backgroundColor='#15803d'; this.style.transform='translateY(-1px)'"
        onmouseout="this.style.backgroundColor='#16a34a'; this.style.transform='translateY(0)'">
        <svg style="width: 16px; height: 16px; stroke-width: 3;" fill="none" stroke="currentColor" viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
            </path>
        </svg>
        <span>ВИТРАТИ</span>
    </button>
</div>