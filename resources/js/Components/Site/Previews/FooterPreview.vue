<script setup>
import PublicFooter from '@/Components/Public/PublicFooter.vue';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
    theme: {
        type: Object,
        default: () => ({}),
    },
    isEditMode: {
        type: Boolean,
        default: false,
    },
    viewport: {
        type: String,
        default: 'desktop',
    },
});
</script>

<template>
    <div class="relative">
        <PublicFooter :content="props.content" :theme="props.theme" />

        <div
            v-if="isEditMode"
            class="absolute left-2 top-2 z-10 rounded bg-blue-500 px-2 py-0.5 text-xs text-white"
        >
            Rodapé
        </div>
    </div>
</template>
