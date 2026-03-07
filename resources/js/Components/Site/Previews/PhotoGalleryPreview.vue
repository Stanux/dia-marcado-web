<script setup>
import PublicPhotoGallery from '@/Components/Public/PublicPhotoGallery.vue';

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
    <PublicPhotoGallery :content="props.content" :theme="props.theme" />
</template>
