@php
    $statePath = $getStatePath();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div 
        x-data="{
            state: $wire.entangle('{{ $statePath }}'),
            maxWidth: {{ $getImageMaxWidth() ?? 'null' }},
            maxHeight: {{ $getImageMaxHeight() ?? 'null' }},
            
            openGallery() {
                Livewire.dispatch('openMediaGallery', { 
                    maxWidth: this.maxWidth, 
                    maxHeight: this.maxHeight 
                });
            },
            
            removeImage() {
                this.state = null;
            }
        }"
        @image-selected.window="state = $event.detail.url"
        class="media-gallery-picker"
    >
        <!-- Button to open gallery -->
        <button
            type="button"
            @click="openGallery()"
            class="inline-flex items-center gap-2 px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg hover:border-primary-500 transition-colors text-gray-600 hover:text-primary-600 bg-white w-full justify-center dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span>{{ $getButtonLabel() }}</span>
            @if($getImageMaxWidth() || $getImageMaxHeight())
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    (máx. {{ $getImageMaxWidth() }}×{{ $getImageMaxHeight() }}px)
                </span>
            @endif
        </button>

        <!-- Preview -->
        <template x-if="state">
            <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <img 
                        :src="state" 
                        alt="Preview" 
                        class="w-20 h-20 object-cover rounded border border-gray-300 dark:border-gray-600"
                    />
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Imagem selecionada</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="state"></p>
                    </div>
                    <button
                        type="button"
                        @click="removeImage()"
                        class="p-2 text-red-400 hover:text-red-600 transition-colors"
                        title="Remover"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </template>
    </div>
</x-dynamic-component>

