<script setup>
/**
 * HeroPreview Component
 * 
 * Renders the hero section preview with media (image/video/gallery),
 * title, subtitle, and CTA buttons.
 * 
 * @Requirements: 9.1, 9.2, 9.4, 9.5
 */
import { computed, ref, onMounted } from 'vue';

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

// Computed properties
const media = computed(() => props.content.media || { type: 'image', url: '', fallback: '' });
const ctaPrimary = computed(() => props.content.ctaPrimary || { label: '', target: '' });
const ctaSecondary = computed(() => props.content.ctaSecondary || { label: '', target: '' });
const style = computed(() => props.content.style || {});
const overlay = computed(() => style.value.overlay || { color: '#000000', opacity: 0.3 });

// Layout classes
const layoutClasses = computed(() => {
    switch (props.content.layout) {
        case 'boxed':
            return 'max-w-5xl mx-auto rounded-lg overflow-hidden';
        case 'split':
            return 'flex flex-col md:flex-row';
        default: // full-bleed
            return 'w-full';
    }
});

// Text alignment classes
const textAlignClass = computed(() => {
    switch (style.value.textAlign) {
        case 'left': return 'text-left items-start';
        case 'right': return 'text-right items-end';
        default: return 'text-center items-center';
    }
});

// Animation classes
const animationClass = computed(() => {
    switch (style.value.animation) {
        case 'fade': return 'animate-fade-in';
        case 'slide': return 'animate-slide-up';
        case 'zoom': return 'animate-zoom-in';
        default: return '';
    }
});

// Check if media is a video
const isVideo = computed(() => media.value.type === 'video');
const isYouTube = computed(() => {
    const url = media.value.url || '';
    return url.includes('youtube.com') || url.includes('youtu.be');
});
const isVimeo = computed(() => {
    const url = media.value.url || '';
    return url.includes('vimeo.com');
});

// Extract video ID for embeds
const youtubeId = computed(() => {
    const url = media.value.url || '';
    const match = url.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/);
    return match ? match[1] : '';
});

const vimeoId = computed(() => {
    const url = media.value.url || '';
    const match = url.match(/vimeo\.com\/(\d+)/);
    return match ? match[1] : '';
});

// Placeholder image for empty state
const placeholderImage = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="1200" height="600" viewBox="0 0 1200 600"%3E%3Crect fill="%23f3f4f6" width="1200" height="600"/%3E%3Ctext fill="%239ca3af" font-family="sans-serif" font-size="24" x="50%25" y="50%25" text-anchor="middle" dy=".3em"%3EImagem do Hero%3C/text%3E%3C/svg%3E';
</script>

<template>
    <section 
        class="relative min-h-[400px] md:min-h-[600px] flex items-center justify-center"
        :class="layoutClasses"
    >
        <!-- Background Media -->
        <div class="absolute inset-0 overflow-hidden">
            <!-- Image Background -->
            <template v-if="!isVideo">
                <img
                    :src="media.url || placeholderImage"
                    :alt="content.title || 'Hero image'"
                    class="w-full h-full object-cover"
                />
            </template>

            <!-- Video Background -->
            <template v-else-if="isVideo && !isYouTube && !isVimeo">
                <video
                    :src="media.url"
                    :poster="media.fallback"
                    :autoplay="media.autoplay"
                    :loop="media.loop"
                    muted
                    playsinline
                    class="w-full h-full object-cover"
                />
            </template>

            <!-- YouTube Embed -->
            <template v-else-if="isYouTube && youtubeId">
                <iframe
                    :src="`https://www.youtube.com/embed/${youtubeId}?autoplay=${media.autoplay ? 1 : 0}&loop=${media.loop ? 1 : 0}&mute=1&controls=0&showinfo=0&rel=0&playlist=${youtubeId}`"
                    class="w-full h-full"
                    frameborder="0"
                    allow="autoplay; encrypted-media"
                    allowfullscreen
                />
            </template>

            <!-- Vimeo Embed -->
            <template v-else-if="isVimeo && vimeoId">
                <iframe
                    :src="`https://player.vimeo.com/video/${vimeoId}?autoplay=${media.autoplay ? 1 : 0}&loop=${media.loop ? 1 : 0}&muted=1&background=1`"
                    class="w-full h-full"
                    frameborder="0"
                    allow="autoplay; fullscreen"
                    allowfullscreen
                />
            </template>

            <!-- Overlay -->
            <div 
                class="absolute inset-0"
                :style="{
                    backgroundColor: overlay.color,
                    opacity: overlay.opacity,
                }"
            />
        </div>

        <!-- Content -->
        <div 
            class="relative z-10 px-4 py-12 md:py-20 flex flex-col"
            :class="[textAlignClass, animationClass]"
            :style="{ animationDuration: `${style.animationDuration || 500}ms` }"
        >
            <!-- Title -->
            <h1 
                v-if="content.title"
                class="text-3xl md:text-5xl lg:text-6xl font-bold text-white mb-4"
                :style="{ fontFamily: theme.fontFamily }"
            >
                {{ content.title }}
            </h1>

            <!-- Subtitle -->
            <p 
                v-if="content.subtitle"
                class="text-lg md:text-xl text-white/90 mb-8 max-w-2xl"
            >
                {{ content.subtitle }}
            </p>

            <!-- CTA Buttons -->
            <div 
                v-if="ctaPrimary.label || ctaSecondary.label"
                class="flex flex-col sm:flex-row gap-4"
                :class="{ 'justify-center': style.textAlign === 'center' }"
            >
                <a
                    v-if="ctaPrimary.label"
                    :href="ctaPrimary.target || '#'"
                    class="px-6 py-3 text-base font-medium rounded-md text-white transition-colors"
                    :style="{ backgroundColor: theme.primaryColor }"
                >
                    {{ ctaPrimary.label }}
                </a>
                <a
                    v-if="ctaSecondary.label"
                    :href="ctaSecondary.target || '#'"
                    class="px-6 py-3 text-base font-medium rounded-md bg-white/20 text-white border border-white/40 hover:bg-white/30 transition-colors"
                >
                    {{ ctaSecondary.label }}
                </a>
            </div>
        </div>

        <!-- Edit Mode Indicator -->
        <div 
            v-if="isEditMode"
            class="absolute top-2 left-2 bg-blue-500 text-white text-xs px-2 py-0.5 rounded z-20"
        >
            Hero
        </div>
    </section>
</template>

<style scoped>
@keyframes fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slide-up {
    from { 
        opacity: 0;
        transform: translateY(20px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes zoom-in {
    from { 
        opacity: 0;
        transform: scale(0.95);
    }
    to { 
        opacity: 1;
        transform: scale(1);
    }
}

.animate-fade-in {
    animation: fade-in ease-out forwards;
}

.animate-slide-up {
    animation: slide-up ease-out forwards;
}

.animate-zoom-in {
    animation: zoom-in ease-out forwards;
}
</style>
