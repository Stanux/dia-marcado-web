<script setup>
/**
 * PublicHero Component
 * 
 * Renders the hero section with media (image/video), title, subtitle,
 * and CTA buttons with animations.
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
});

// Computed properties
const media = computed(() => props.content.media || { type: 'image', url: '', fallback: '' });
const ctaPrimary = computed(() => props.content.ctaPrimary || { label: '', target: '' });
const ctaSecondary = computed(() => props.content.ctaSecondary || { label: '', target: '' });
const style = computed(() => props.content.style || {});
const overlay = computed(() => style.value.overlay || { color: '#000000', opacity: 0.3 });

// Animation state
const isVisible = ref(false);

onMounted(() => {
    // Trigger animation after mount
    setTimeout(() => {
        isVisible.value = true;
    }, 100);
});

// Layout classes
const layoutClasses = computed(() => {
    switch (props.content.layout) {
        case 'boxed':
            return 'max-w-6xl mx-auto my-8 rounded-2xl overflow-hidden shadow-2xl';
        case 'split':
            return 'flex flex-col lg:flex-row';
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
    if (!isVisible.value) return 'opacity-0';
    
    switch (style.value.animation) {
        case 'fade': return 'animate-fade-in';
        case 'slide': return 'animate-slide-up';
        case 'zoom': return 'animate-zoom-in';
        default: return 'opacity-100';
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

// Placeholder gradient for empty state
const placeholderStyle = computed(() => ({
    background: `linear-gradient(135deg, ${props.theme.primaryColor || '#d4a574'}, ${props.theme.secondaryColor || '#8b7355'})`,
}));

// Navigate to target
const navigateTo = (target) => {
    if (target.startsWith('#')) {
        const element = document.querySelector(target);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth' });
        }
    } else {
        window.location.href = target;
    }
};
</script>

<template>
    <section 
        class="relative min-h-[500px] md:min-h-[700px] flex items-center justify-center"
        :class="layoutClasses"
    >
        <!-- Background Media -->
        <div class="absolute inset-0 overflow-hidden">
            <!-- Image Background -->
            <template v-if="!isVideo && media.url">
                <img
                    :src="media.url"
                    :alt="content.title || 'Hero image'"
                    class="w-full h-full object-cover"
                    loading="eager"
                />
            </template>

            <!-- Video Background (Direct) -->
            <template v-else-if="isVideo && !isYouTube && !isVimeo && media.url">
                <video
                    :src="media.url"
                    :poster="media.fallback"
                    :autoplay="media.autoplay !== false"
                    :loop="media.loop !== false"
                    muted
                    playsinline
                    class="w-full h-full object-cover"
                />
            </template>

            <!-- YouTube Embed -->
            <template v-else-if="isYouTube && youtubeId">
                <div class="absolute inset-0 pointer-events-none">
                    <iframe
                        :src="`https://www.youtube.com/embed/${youtubeId}?autoplay=${media.autoplay !== false ? 1 : 0}&loop=${media.loop !== false ? 1 : 0}&mute=1&controls=0&showinfo=0&rel=0&playlist=${youtubeId}&modestbranding=1`"
                        class="w-full h-full scale-150"
                        frameborder="0"
                        allow="autoplay; encrypted-media"
                        allowfullscreen
                    />
                </div>
            </template>

            <!-- Vimeo Embed -->
            <template v-else-if="isVimeo && vimeoId">
                <div class="absolute inset-0 pointer-events-none">
                    <iframe
                        :src="`https://player.vimeo.com/video/${vimeoId}?autoplay=${media.autoplay !== false ? 1 : 0}&loop=${media.loop !== false ? 1 : 0}&muted=1&background=1`"
                        class="w-full h-full scale-150"
                        frameborder="0"
                        allow="autoplay; fullscreen"
                        allowfullscreen
                    />
                </div>
            </template>

            <!-- Placeholder Gradient -->
            <template v-else>
                <div class="w-full h-full" :style="placeholderStyle" />
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
            class="relative z-10 px-6 py-16 md:py-24 flex flex-col max-w-4xl mx-auto"
            :class="[textAlignClass, animationClass]"
            :style="{ animationDuration: `${style.animationDuration || 600}ms` }"
        >
            <!-- Title -->
            <h1 
                v-if="content.title"
                class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight"
                :style="{ fontFamily: theme.fontFamily }"
            >
                {{ content.title }}
            </h1>

            <!-- Subtitle -->
            <p 
                v-if="content.subtitle"
                class="text-lg md:text-xl lg:text-2xl text-white/90 mb-10 max-w-2xl leading-relaxed"
                :class="{ 'mx-auto': style.textAlign === 'center' }"
            >
                {{ content.subtitle }}
            </p>

            <!-- CTA Buttons -->
            <div 
                v-if="ctaPrimary.label || ctaSecondary.label"
                class="flex flex-col sm:flex-row gap-4"
                :class="{ 
                    'justify-center': style.textAlign === 'center',
                    'justify-start': style.textAlign === 'left',
                    'justify-end': style.textAlign === 'right',
                }"
            >
                <a
                    v-if="ctaPrimary.label"
                    :href="ctaPrimary.target || '#'"
                    class="px-8 py-4 text-base font-semibold rounded-lg text-white transition-all duration-200 hover:scale-105 hover:shadow-lg"
                    :style="{ backgroundColor: theme.primaryColor }"
                    @click.prevent="navigateTo(ctaPrimary.target)"
                >
                    {{ ctaPrimary.label }}
                </a>
                <a
                    v-if="ctaSecondary.label"
                    :href="ctaSecondary.target || '#'"
                    class="px-8 py-4 text-base font-semibold rounded-lg bg-white/20 text-white border-2 border-white/50 hover:bg-white/30 transition-all duration-200 backdrop-blur-sm"
                    @click.prevent="navigateTo(ctaSecondary.target)"
                >
                    {{ ctaSecondary.label }}
                </a>
            </div>
        </div>

        <!-- Scroll Indicator -->
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce">
            <svg class="w-6 h-6 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
            </svg>
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
        transform: translateY(30px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes zoom-in {
    from { 
        opacity: 0;
        transform: scale(0.9);
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

/* Scale video embeds to cover container */
.scale-150 {
    transform: scale(1.5);
}
</style>
