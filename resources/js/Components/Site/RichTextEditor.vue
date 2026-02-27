<script setup>
/**
 * RichTextEditor Component
 * 
 * A simple rich text editor with basic formatting options.
 * Uses contenteditable with custom toolbar for bold, italic, and links.
 * Supports placeholder display as chips and basic client-side sanitization.
 * 
 * @Requirements: 8.3, 20.4
 */
import { ref, watch, computed, onMounted, nextTick } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Digite aqui...',
    },
    placeholders: {
        type: Array,
        default: () => [
            { key: '{noivo}', label: 'Nome do Noivo' },
            { key: '{noiva}', label: 'Nome da Noiva' },
            { key: '{noivos}', label: 'Nomes dos Noivos' },
            { key: '{data}', label: 'Data do Evento' },
            { key: '{data_curta}', label: 'Data Curta' },
            { key: '{local}', label: 'Local' },
            { key: '{cidade}', label: 'Cidade' },
            { key: '{estado}', label: 'Estado' },
        ],
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    minHeight: {
        type: String,
        default: '100px',
    },
});

const emit = defineEmits(['update:modelValue']);

const editorRef = ref(null);
const showLinkModal = ref(false);
const linkUrl = ref('');
const linkText = ref('');
const savedSelection = ref(null);
const showPlaceholderMenu = ref(false);

/**
 * Sanitize HTML content - remove dangerous tags and attributes
 */
const sanitizeHtml = (html) => {
    if (!html) return '';
    
    // Create a temporary element to parse HTML
    const temp = document.createElement('div');
    temp.innerHTML = html;
    
    // Remove script tags
    const scripts = temp.querySelectorAll('script');
    scripts.forEach(script => script.remove());
    
    // Remove style tags
    const styles = temp.querySelectorAll('style');
    styles.forEach(style => style.remove());
    
    // Remove dangerous attributes from all elements
    const allElements = temp.querySelectorAll('*');
    const dangerousAttrs = [
        'onclick', 'onerror', 'onload', 'onmouseover', 'onmouseout',
        'onfocus', 'onblur', 'onchange', 'onsubmit', 'onkeydown',
        'onkeyup', 'onkeypress', 'ondblclick', 'oncontextmenu'
    ];
    
    allElements.forEach(el => {
        // Remove event handlers
        dangerousAttrs.forEach(attr => {
            el.removeAttribute(attr);
        });
        
        // Check href for javascript:
        const href = el.getAttribute('href');
        if (href && href.toLowerCase().startsWith('javascript:')) {
            el.removeAttribute('href');
        }
        
        // Check src for javascript:
        const src = el.getAttribute('src');
        if (src && src.toLowerCase().startsWith('javascript:')) {
            el.removeAttribute('src');
        }
    });
    
    return temp.innerHTML;
};

/**
 * Initialize editor content
 */
onMounted(() => {
    if (editorRef.value && props.modelValue) {
        editorRef.value.innerHTML = sanitizeHtml(props.modelValue);
    }
});

/**
 * Watch for external value changes
 */
watch(() => props.modelValue, (newValue) => {
    if (editorRef.value && editorRef.value.innerHTML !== newValue) {
        editorRef.value.innerHTML = sanitizeHtml(newValue);
    }
});

/**
 * Handle input and emit sanitized content
 */
const handleInput = () => {
    if (editorRef.value) {
        const sanitized = sanitizeHtml(editorRef.value.innerHTML);
        emit('update:modelValue', sanitized);
    }
};

/**
 * Execute formatting command
 */
const execCommand = (command, value = null) => {
    document.execCommand(command, false, value);
    editorRef.value?.focus();
    handleInput();
};

/**
 * Toggle bold formatting
 */
const toggleBold = () => {
    execCommand('bold');
};

/**
 * Toggle italic formatting
 */
const toggleItalic = () => {
    execCommand('italic');
};

/**
 * Save current selection for link insertion
 */
const saveSelection = () => {
    const selection = window.getSelection();
    if (selection.rangeCount > 0) {
        savedSelection.value = selection.getRangeAt(0).cloneRange();
        linkText.value = selection.toString();
    }
};

/**
 * Restore saved selection
 */
const restoreSelection = () => {
    if (savedSelection.value) {
        const selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(savedSelection.value);
    }
};

/**
 * Open link modal
 */
const openLinkModal = () => {
    saveSelection();
    linkUrl.value = '';
    showLinkModal.value = true;
};

/**
 * Insert link
 */
const insertLink = () => {
    if (!linkUrl.value) {
        showLinkModal.value = false;
        return;
    }
    
    // Ensure URL has protocol
    let url = linkUrl.value;
    if (!url.startsWith('http://') && !url.startsWith('https://')) {
        url = 'https://' + url;
    }
    
    restoreSelection();
    editorRef.value?.focus();
    
    if (linkText.value) {
        execCommand('createLink', url);
    } else {
        // Insert link with URL as text
        const link = `<a href="${url}">${url}</a>`;
        execCommand('insertHTML', link);
    }
    
    showLinkModal.value = false;
    linkUrl.value = '';
    linkText.value = '';
    savedSelection.value = null;
};

/**
 * Remove link from selection
 */
const removeLink = () => {
    execCommand('unlink');
};

/**
 * Insert placeholder at cursor position
 */
const insertPlaceholder = (placeholder) => {
    editorRef.value?.focus();
    execCommand('insertText', placeholder.key);
    showPlaceholderMenu.value = false;
};

/**
 * Check if current selection has formatting
 */
const isFormatActive = (format) => {
    return document.queryCommandState(format);
};

/**
 * Handle paste - sanitize pasted content
 */
const handlePaste = (event) => {
    event.preventDefault();
    const text = event.clipboardData?.getData('text/plain') || '';
    execCommand('insertText', text);
};

/**
 * Render content with placeholder chips
 */
const renderWithPlaceholders = computed(() => {
    let content = props.modelValue || '';
    props.placeholders.forEach(p => {
        const regex = new RegExp(p.key.replace(/[{}]/g, '\\$&'), 'g');
        content = content.replace(regex, `<span class="placeholder-chip">${p.key}</span>`);
    });
    return content;
});
</script>

<template>
    <div class="rich-text-editor" :class="{ 'disabled': disabled }">
        <!-- Toolbar -->
        <div class="toolbar">
            <button
                type="button"
                @click="toggleBold"
                class="toolbar-btn"
                :class="{ 'active': isFormatActive('bold') }"
                title="Negrito (Ctrl+B)"
                :disabled="disabled"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z" />
                </svg>
            </button>
            
            <button
                type="button"
                @click="toggleItalic"
                class="toolbar-btn"
                :class="{ 'active': isFormatActive('italic') }"
                title="Itálico (Ctrl+I)"
                :disabled="disabled"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 4h4m-2 0v16m-4 0h8" transform="skewX(-10)" />
                </svg>
            </button>
            
            <div class="toolbar-divider"></div>
            
            <button
                type="button"
                @click="openLinkModal"
                class="toolbar-btn"
                title="Inserir Link"
                :disabled="disabled"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
            </button>
            
            <button
                type="button"
                @click="removeLink"
                class="toolbar-btn"
                title="Remover Link"
                :disabled="disabled"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
            </button>
            
            <div class="toolbar-divider"></div>
            
            <!-- Placeholder Menu -->
            <div class="relative">
                <button
                    type="button"
                    @click="showPlaceholderMenu = !showPlaceholderMenu"
                    class="toolbar-btn"
                    title="Inserir Placeholder"
                    :disabled="disabled"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                </button>
                
                <!-- Placeholder Dropdown -->
                <div
                    v-if="showPlaceholderMenu"
                    class="placeholder-menu"
                >
                    <button
                        v-for="p in placeholders"
                        :key="p.key"
                        type="button"
                        @click="insertPlaceholder(p)"
                        class="placeholder-item"
                    >
                        <span class="placeholder-key">{{ p.key }}</span>
                        <span class="placeholder-label">{{ p.label }}</span>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Editor Area -->
        <div
            ref="editorRef"
            contenteditable="true"
            class="editor-content"
            :style="{ minHeight: minHeight }"
            :data-placeholder="placeholder"
            @input="handleInput"
            @paste="handlePaste"
            @blur="showPlaceholderMenu = false"
            :contenteditable="!disabled"
        ></div>
        
        <!-- Link Modal -->
        <div v-if="showLinkModal" class="link-modal-overlay" @click.self="showLinkModal = false">
            <div class="link-modal">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Inserir Link</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">URL</label>
                        <input
                            v-model="linkUrl"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                            placeholder="https://exemplo.com"
                            @keyup.enter="insertLink"
                        />
                    </div>
                    
                    <div v-if="linkText">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Texto Selecionado</label>
                        <p class="text-sm text-gray-600 bg-gray-50 px-3 py-2 rounded">{{ linkText }}</p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button
                        type="button"
                        @click="showLinkModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        @click="insertLink"
                        class="px-4 py-2 text-sm font-medium text-white bg-wedding-600 rounded-md hover:bg-wedding-700"
                    >
                        Inserir
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.rich-text-editor {
    @apply border border-gray-300 rounded-md overflow-hidden bg-white;
}

.rich-text-editor.disabled {
    @apply opacity-60 pointer-events-none;
}

.toolbar {
    @apply flex items-center gap-1 px-2 py-1.5 bg-gray-50 border-b border-gray-200;
}

.toolbar-btn {
    @apply p-1.5 rounded text-gray-600 hover:bg-gray-200 hover:text-gray-900 transition-colors;
}

.toolbar-btn.active {
    @apply bg-gray-200 text-gray-900;
}

.toolbar-btn:disabled {
    @apply opacity-50 cursor-not-allowed;
}

.toolbar-divider {
    @apply w-px h-5 bg-gray-300 mx-1;
}

.editor-content {
    @apply px-3 py-2 outline-none;
}

.editor-content:empty:before {
    content: attr(data-placeholder);
    @apply text-gray-400 pointer-events-none;
}

.editor-content:deep(a) {
    @apply text-wedding-600 underline;
}

.editor-content:deep(.placeholder-chip) {
    @apply inline-block px-1.5 py-0.5 bg-wedding-100 text-wedding-700 rounded text-xs font-medium;
}

.placeholder-menu {
    @apply absolute top-full left-0 mt-1 w-56 bg-white border border-gray-200 rounded-md shadow-lg z-50;
}

.placeholder-item {
    @apply w-full flex items-center justify-between px-3 py-2 text-left hover:bg-gray-50 first:rounded-t-md last:rounded-b-md;
}

.placeholder-key {
    @apply text-xs font-mono text-wedding-600;
}

.placeholder-label {
    @apply text-xs text-gray-500;
}

.link-modal-overlay {
    @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50;
}

.link-modal {
    @apply bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4;
}

/* Wedding theme colors */
.text-wedding-600 {
    color: #c45a6f;
}

.text-wedding-700 {
    color: #b9163a;
}

.bg-wedding-100 {
    background-color: #fde8ee;
}

.bg-wedding-600 {
    background-color: #c45a6f;
}

.bg-wedding-700 {
    background-color: #b9163a;
}

.focus\:ring-wedding-500:focus {
    --tw-ring-color: #d87a8d;
}

.focus\:border-wedding-500:focus {
    border-color: #d87a8d;
}
</style>
