<script setup>
/**
 * PublicHero Component
 * 
 * Renders the hero section with media (image/video), title, subtitle,
 * and CTA buttons with animations.
 * 
 * @Requirements: 9.1, 9.2, 9.4, 9.5
 */
import { computed, ref, onMounted, onUnmounted } from 'vue';

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
const media = computed(() => props.content.media || { type: 'image', url: '', alt: '', fallback: '' });
const ctaPrimary = computed(() => props.content.ctaPrimary || { label: '', target: '' });
const ctaSecondary = computed(() => props.content.ctaSecondary || { label: '', target: '' });
const style = computed(() => props.content.style || {});
const overlay = computed(() => style.value.overlay || { color: '#000000', opacity: 0.3 });
const titleTypography = computed(() => props.content.titleTypography || {});
const subtitleTypography = computed(() => props.content.subtitleTypography || {});

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

const titleTextStyle = computed(() => ({
    fontFamily: titleTypography.value.fontFamily || props.theme.fontFamily,
    color: titleTypography.value.fontColor || '#ffffff',
    fontWeight: titleTypography.value.fontWeight || undefined,
    fontStyle: titleTypography.value.fontItalic ? 'italic' : 'normal',
    textDecoration: titleTypography.value.fontUnderline ? 'underline' : 'none',
}));

const subtitleTextStyle = computed(() => ({
    fontFamily: subtitleTypography.value.fontFamily || props.theme.fontFamily,
    color: subtitleTypography.value.fontColor || 'rgba(255, 255, 255, 0.9)',
    fontWeight: subtitleTypography.value.fontWeight || undefined,
    fontStyle: subtitleTypography.value.fontItalic ? 'italic' : 'normal',
    textDecoration: subtitleTypography.value.fontUnderline ? 'underline' : 'none',
}));

// Check media type
const isVideo = computed(() => media.value.type === 'video');
const isGallery = computed(() => media.value.type === 'gallery');
const isImage = computed(() => media.value.type === 'image' || (!isVideo.value && !isGallery.value));

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
    background: `linear-gradient(135deg, ${props.theme.primaryColor || '#f97373'}, ${props.theme.secondaryColor || '#b85c5c'})`,
}));

// Gallery carousel state
const currentGalleryIndex = ref(0);
let galleryInterval = null;

// Start gallery carousel
const startGalleryCarousel = () => {
    if (isGallery.value && media.value.images?.length > 1) {
        galleryInterval = setInterval(() => {
            currentGalleryIndex.value = (currentGalleryIndex.value + 1) % media.value.images.length;
        }, 5000); // Change image every 5 seconds
    }
};

// Stop gallery carousel
const stopGalleryCarousel = () => {
    if (galleryInterval) {
        clearInterval(galleryInterval);
        galleryInterval = null;
    }
};

onMounted(() => {
    // Trigger animation after mount
    setTimeout(() => {
        isVisible.value = true;
    }, 100);
    
    // Start gallery carousel if applicable
    startGalleryCarousel();
});

onUnmounted(() => {
    stopGalleryCarousel();
});

// Navigate to target
const navigateTo = (target) => {
    if (target && target.startsWith('#')) {
        const element = document.querySelector(target);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth' });
        }
    } else if (target) {
        window.location.href = target;
    }
};
</script>

<template>
    <section 
        id="hero"
        class="relative min-h-[420px] md:min-h-[700px] flex items-center justify-center"
        :class="layoutClasses"
    >
        <!-- Background Media -->
        <div class="absolute inset-0 overflow-hidden">
            <!-- Gallery (Banner Rotativo) -->
            <template v-if="isGallery && media.images?.length > 0">
                <div class="relative w-full h-full">
                    <!-- Gallery Images -->
                    <div
                        v-for="(image, index) in media.images"
                        :key="index"
                        class="absolute inset-0 transition-opacity duration-1000"
                        :class="{ 'opacity-100': index === currentGalleryIndex, 'opacity-0': index !== currentGalleryIndex }"
                    >
                        <img 
                            :src="image.url" 
                            :alt="image.alt || `Slide ${index + 1}`" 
                            class="w-full h-full object-cover"
                            loading="eager"
                        />
                    </div>
                    
                    <!-- Gallery Indicators -->
                    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 flex gap-2 z-10">
                        <button
                            v-for="(image, index) in media.images"
                            :key="index"
                            @click="currentGalleryIndex = index"
                            class="w-2 h-2 rounded-full transition-all"
                            :class="index === currentGalleryIndex ? 'bg-white w-6' : 'bg-white/50 hover:bg-white/75'"
                            :aria-label="`Ir para slide ${index + 1}`"
                        ></button>
                    </div>
                </div>
            </template>
            
            <!-- Image Background -->
            <template v-else-if="isImage && media.url">
                <img
                    :src="media.url"
                    :alt="media.alt || content.title || 'Imagem de destaque'"
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
            class="relative z-10 px-4 sm:px-6 py-12 md:py-24 flex flex-col max-w-4xl mx-auto w-full"
            :class="[textAlignClass, animationClass]"
            :style="{ animationDuration: `${style.animationDuration || 600}ms` }"
        >
            <!-- Title -->
            <h1 
                v-if="content.title"
                class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold mb-5 md:mb-6 leading-tight break-words [overflow-wrap:anywhere] max-w-full"
                :style="titleTextStyle"
            >
                {{ content.title }}
            </h1>

            <!-- Subtitle -->
            <p 
                v-if="content.subtitle"
                class="text-base sm:text-lg md:text-xl lg:text-2xl mb-8 md:mb-10 max-w-2xl leading-relaxed break-words [overflow-wrap:anywhere]"
                :class="{ 'mx-auto': style.textAlign === 'center' }"
                :style="subtitleTextStyle"
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
                    class="w-full sm:w-auto px-8 py-4 text-base font-semibold rounded-lg text-white text-center break-words transition-all duration-200 hover:scale-105 hover:shadow-lg"
                    :style="{ backgroundColor: theme.primaryColor }"
                    @click.prevent="navigateTo(ctaPrimary.target)"
                >
                    {{ ctaPrimary.label }}
                </a>
                <a
                    v-if="ctaSecondary.label"
                    :href="ctaSecondary.target || '#'"
                    class="w-full sm:w-auto px-8 py-4 text-base font-semibold rounded-lg bg-white/20 text-white border-2 border-white/50 text-center break-words hover:bg-white/30 transition-all duration-200 backdrop-blur-sm"
                    @click.prevent="navigateTo(ctaSecondary.target)"
                >
                    {{ ctaSecondary.label }}
                </a>
            </div>
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
