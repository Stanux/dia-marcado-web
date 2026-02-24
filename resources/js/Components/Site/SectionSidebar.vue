<script setup>
/**
 * SectionSidebar Component
 * 
 * Displays a list of site sections with toggle controls and selection.
 * Allows users to navigate between sections and enable/disable them.
 * 
 * @Requirements: 8.1-14.6
 */
import { ref } from 'vue';

const props = defineProps({
    sections: {
        type: Array,
        required: true,
        default: () => [],
    },
    activeSection: {
        type: String,
        required: true,
    },
    mobile: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['select', 'toggle', 'reorder']);

/**
 * Sections that cannot be disabled (always required)
 */
const requiredSections = ['header', 'footer'];
const draggingSectionKey = ref(null);
const dropTargetSectionKey = ref(null);

/**
 * Check if a section can be toggled
 */
const canToggle = (sectionKey) => {
    return !requiredSections.includes(sectionKey);
};

/**
 * Check if a section can be reordered via drag and drop.
 */
const canReorder = (sectionKey) => {
    return canToggle(sectionKey);
};

/**
 * Get icon SVG path based on section type
 */
const getIconPath = (icon) => {
    const icons = {
        header: 'M4 6h16M4 12h16M4 18h7',
        image: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
        calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        gift: 'M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7',
        users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
        images: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
        footer: 'M4 6h16M4 12h8m-8 6h16',
    };
    return icons[icon] || icons.header;
};

/**
 * Handle section click
 */
const handleSelect = (sectionKey) => {
    emit('select', sectionKey);
};

/**
 * Handle toggle click (enable/disable section)
 */
const handleToggle = (event, sectionKey) => {
    event.stopPropagation();
    if (!canToggle(sectionKey)) return;
    emit('toggle', sectionKey);
};

/**
 * Handle drag start from the drag handle.
 */
const handleDragStart = (event, sectionKey) => {
    if (!canReorder(sectionKey)) return;
    draggingSectionKey.value = sectionKey;
    dropTargetSectionKey.value = null;

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', sectionKey);
    }
};

/**
 * Handle drag over a potential drop target.
 */
const handleDragOver = (event, sectionKey) => {
    if (!canReorder(sectionKey)) return;
    if (!draggingSectionKey.value || draggingSectionKey.value === sectionKey) return;

    event.preventDefault();
    dropTargetSectionKey.value = sectionKey;
};

/**
 * Handle drop and emit new order for movable sections.
 */
const handleDrop = (event, targetSectionKey) => {
    if (!canReorder(targetSectionKey)) return;
    if (!draggingSectionKey.value || draggingSectionKey.value === targetSectionKey) return;

    event.preventDefault();

    const currentOrder = props.sections
        .filter((section) => canReorder(section.key))
        .map((section) => section.key);

    const sourceIndex = currentOrder.indexOf(draggingSectionKey.value);
    const targetIndex = currentOrder.indexOf(targetSectionKey);

    if (sourceIndex === -1 || targetIndex === -1) {
        draggingSectionKey.value = null;
        dropTargetSectionKey.value = null;
        return;
    }

    const [movedItem] = currentOrder.splice(sourceIndex, 1);
    currentOrder.splice(targetIndex, 0, movedItem);

    emit('reorder', currentOrder);

    draggingSectionKey.value = null;
    dropTargetSectionKey.value = null;
};

const handleDragEnd = () => {
    draggingSectionKey.value = null;
    dropTargetSectionKey.value = null;
};
</script>

<template>
    <aside
        class="bg-white border-r border-gray-200 flex flex-col h-full"
        :class="mobile ? 'w-full' : 'w-64'"
    >
        <!-- Header -->
        <div class="p-4 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">
                Seções
            </h2>
        </div>

        <!-- Section List -->
        <nav class="flex-1 overflow-y-auto py-2">
            <ul class="space-y-1 px-2">
                <li v-for="section in sections" :key="section.key">
                    <div
                        class="w-full rounded-lg transition-colors"
                        @dragover="(event) => handleDragOver(event, section.key)"
                        @drop="(event) => handleDrop(event, section.key)"
                    >
                        <div
                            @click="handleSelect(section.key)"
                            class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-left transition-colors"
                            :class="[
                                activeSection === section.key
                                    ? 'bg-wedding-100 text-wedding-900'
                                    : 'text-gray-700 hover:bg-gray-100',
                                !section.enabled && 'opacity-60',
                                dropTargetSectionKey === section.key && draggingSectionKey !== section.key && canReorder(section.key)
                                    ? 'ring-2 ring-wedding-500 ring-offset-1'
                                    : ''
                            ]"
                        >
                            <div class="flex items-center min-w-0">
                                <!-- Drag Handle -->
                                <button
                                    v-if="canReorder(section.key)"
                                    type="button"
                                    draggable="true"
                                    @click.stop
                                    @dragstart="(event) => handleDragStart(event, section.key)"
                                    @dragend="handleDragEnd"
                                    class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded text-gray-400 hover:bg-gray-100 hover:text-gray-600 cursor-grab active:cursor-grabbing"
                                    :aria-label="`Reordenar ${section.label}`"
                                    title="Arrastar para reordenar"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 12h16M4 16h16" />
                                    </svg>
                                </button>

                                <span
                                    v-else
                                    class="mr-2 inline-block h-6 w-6 flex-shrink-0"
                                    aria-hidden="true"
                                ></span>

                                <!-- Section Icon -->
                                <svg
                                    class="w-5 h-5 mr-3 flex-shrink-0"
                                    :class="activeSection === section.key ? 'text-wedding-600' : 'text-gray-400'"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        :d="getIconPath(section.icon)"
                                    />
                                </svg>

                                <!-- Section Label -->
                                <span class="truncate text-sm font-medium">
                                    {{ section.label }}
                                </span>
                            </div>

                            <!-- Toggle Switch (hidden for required sections) -->
                            <button
                                v-if="canToggle(section.key)"
                                @click="(e) => handleToggle(e, section.key)"
                                class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-wedding-500 focus:ring-offset-2"
                                :class="section.enabled ? 'bg-wedding-600' : 'bg-gray-200'"
                                role="switch"
                                :aria-checked="section.enabled"
                                :aria-label="`${section.enabled ? 'Desativar' : 'Ativar'} ${section.label}`"
                            >
                                <span
                                    class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                    :class="section.enabled ? 'translate-x-4' : 'translate-x-0'"
                                />
                            </button>
                        </div>
                    </div>
                </li>
            </ul>
        </nav>

        <!-- Footer with Meta/Theme links -->
        <div class="p-4 border-t border-gray-200 space-y-2">
            <button
                @click="$emit('select', 'meta')"
                class="w-full flex items-center px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg"
                :class="activeSection === 'meta' ? 'bg-gray-100 text-gray-900' : ''"
            >
                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                SEO & Meta Tags
            </button>

            <button
                @click="$emit('select', 'theme')"
                class="w-full flex items-center px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg"
                :class="activeSection === 'theme' ? 'bg-gray-100 text-gray-900' : ''"
            >
                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                </svg>
                Tema & Cores
            </button>

            <button
                @click="$emit('select', 'settings')"
                class="w-full flex items-center px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg"
                :class="activeSection === 'settings' ? 'bg-gray-100 text-gray-900' : ''"
            >
                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Configurações
            </button>
        </div>
    </aside>
</template>

<style scoped>
.bg-wedding-100 {
    background-color: #f5ebe4;
}
.bg-wedding-600 {
    background-color: #a18072;
}
.text-wedding-600 {
    color: #a18072;
}
.text-wedding-900 {
    color: #4a3f3a;
}
.ring-wedding-500 {
    --tw-ring-color: #b8998a;
}
</style>
