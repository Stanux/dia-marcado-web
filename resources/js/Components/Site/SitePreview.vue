<script setup>
/**
 * SitePreview Component
 * 
 * Renders a complete live preview of the site based on draft content.
 * Supports 100% of customizations from SiteContentSchema.
 */
import { computed } from 'vue';
import { SECTION_IDS } from '@/Composables/useSiteEditor';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
    mode: {
        type: String,
        default: 'desktop',
    },
});

// Theme
const theme = computed(() => props.content?.theme || {});
const sections = computed(() => props.content?.sections || {});
const primaryColor = computed(() => theme.value.primaryColor || '#d4a574');
const secondaryColor = computed(() => theme.value.secondaryColor || '#8b7355');
const fontFamily = computed(() => theme.value.fontFamily || 'Playfair Display');
const fontSize = computed(() => theme.value.fontSize || '16px');

/**
 * Extract YouTube video ID from various URL formats
 */
const getYouTubeId = (url) => {
    if (!url) return null;
    const patterns = [
        /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/,
        /youtube\.com\/v\/([^&\n?#]+)/,
    ];
    for (const pattern of patterns) {
        const match = url.match(pattern);
        if (match) return match[1];
    }
    return null;
};

/**
 * Extract Vimeo video ID from URL
 */
const getVimeoId = (url) => {
    if (!url) return null;
    const match = url.match(/vimeo\.com\/(\d+)/);
    return match ? match[1] : null;
};

/**
 * Check if URL is a YouTube video
 */
const isYouTubeUrl = (url) => {
    return url && (url.includes('youtube.com') || url.includes('youtu.be'));
};

/**
 * Check if URL is a Vimeo video
 */
const isVimeoUrl = (url) => {
    return url && url.includes('vimeo.com');
};

/**
 * Check if URL is a direct video file
 */
const isDirectVideoUrl = (url) => {
    if (!url) return false;
    return /\.(mp4|webm|ogg|mov)(\?.*)?$/i.test(url);
};

// Section configs
const header = computed(() => sections.value.header || {});
const hero = computed(() => sections.value.hero || {});
const saveTheDate = computed(() => sections.value.saveTheDate || {});
const giftRegistry = computed(() => sections.value.giftRegistry || {});
const rsvp = computed(() => sections.value.rsvp || {});
const photoGallery = computed(() => sections.value.photoGallery || {});
const footer = computed(() => sections.value.footer || {});

// Styles
const headerStyle = computed(() => header.value.style || {});
const heroStyle = computed(() => hero.value.style || {});
const saveTheDateStyle = computed(() => saveTheDate.value.style || {});
const footerStyle = computed(() => footer.value.style || {});
const galleryStyle = computed(() => photoGallery.value.style || {});
</script>

<template>
    <div 
        class="site-preview overflow-hidden"
        :style="{ fontFamily: fontFamily + ', serif', fontSize: fontSize }"
    >
        <!-- HEADER -->
        <header 
            v-if="header.enabled"
            class="flex items-center px-3 py-2"
            :class="{
                'justify-start': headerStyle.alignment === 'left',
                'justify-center': headerStyle.alignment === 'center',
                'justify-between': headerStyle.alignment !== 'left' && headerStyle.alignment !== 'center',
            }"
            :style="{ backgroundColor: headerStyle.backgroundColor || '#ffffff', minHeight: headerStyle.height || '60px' }"
        >
            <div class="flex items-center gap-2">
                <img v-if="header.logo?.url" :src="header.logo.url" :alt="header.logo.alt || 'Logo'" class="h-8 w-auto object-contain" />
                <div v-else class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs" :style="{ backgroundColor: primaryColor }">♥</div>
                <div v-if="header.title || header.subtitle">
                    <span class="font-semibold text-sm" :style="{ color: secondaryColor }">{{ header.title || 'Seu Casamento' }}</span>
                    <span v-if="header.subtitle" class="text-xs text-gray-500 block">{{ header.subtitle }}</span>
                </div>
            </div>
            <nav v-if="header.navigation?.length" class="flex gap-2 text-xs text-gray-600">
                <span v-for="(item, i) in header.navigation" :key="i">{{ item.label }}</span>
            </nav>
            <nav v-else class="flex gap-2 text-xs text-gray-500"><span>Início</span><span>•</span><span>RSVP</span></nav>
            <button v-if="header.actionButton?.label" class="px-2 py-1 rounded text-xs text-white" :style="{ backgroundColor: primaryColor }">{{ header.actionButton.label }}</button>
        </header>

        <!-- HERO -->
        <section 
            v-if="hero.enabled"
            :id="SECTION_IDS.hero"
            class="relative flex items-center justify-center overflow-hidden"
            :class="{ 'min-h-[300px]': hero.layout === 'full-bleed', 'min-h-[200px]': hero.layout !== 'full-bleed' }"
        >
            <div class="absolute inset-0">
                <!-- YouTube Video -->
                <iframe 
                    v-if="hero.media?.type === 'video' && hero.media?.url && isYouTubeUrl(hero.media.url)"
                    :src="`https://www.youtube.com/embed/${getYouTubeId(hero.media.url)}?autoplay=1&mute=1&loop=1&playlist=${getYouTubeId(hero.media.url)}&controls=0&showinfo=0&rel=0&modestbranding=1`"
                    class="w-full h-full absolute inset-0"
                    style="pointer-events: none;"
                    frameborder="0"
                    allow="autoplay; encrypted-media"
                    allowfullscreen
                ></iframe>
                <!-- Vimeo Video -->
                <iframe 
                    v-else-if="hero.media?.type === 'video' && hero.media?.url && isVimeoUrl(hero.media.url)"
                    :src="`https://player.vimeo.com/video/${getVimeoId(hero.media.url)}?autoplay=1&muted=1&loop=1&background=1`"
                    class="w-full h-full absolute inset-0"
                    style="pointer-events: none;"
                    frameborder="0"
                    allow="autoplay; fullscreen"
                ></iframe>
                <!-- Direct Video File -->
                <video 
                    v-else-if="hero.media?.type === 'video' && hero.media?.url && isDirectVideoUrl(hero.media.url)"
                    :src="hero.media.url"
                    :autoplay="hero.media.autoplay !== false"
                    :loop="hero.media.loop !== false"
                    muted playsinline
                    class="w-full h-full object-cover"
                ></video>
                <!-- Image -->
                <img v-else-if="hero.media?.url" :src="hero.media.url" alt="Hero" class="w-full h-full object-cover" />
                <!-- Fallback gradient -->
                <div v-else class="w-full h-full" :style="{ background: `linear-gradient(135deg, ${primaryColor}22 0%, ${secondaryColor}22 100%)` }"></div>
                <!-- Overlay -->
                <div v-if="heroStyle.overlay" class="absolute inset-0" :style="{ backgroundColor: heroStyle.overlay.color || '#000000', opacity: heroStyle.overlay.opacity || 0.3 }"></div>
            </div>
            <div class="relative z-10 p-6 max-w-lg" :class="{ 'text-left': heroStyle.textAlign === 'left', 'text-center': heroStyle.textAlign === 'center' || !heroStyle.textAlign, 'text-right': heroStyle.textAlign === 'right' }">
                <h1 class="text-2xl md:text-3xl font-bold mb-2" :style="{ color: hero.media?.url ? '#ffffff' : secondaryColor }">{{ hero.title || 'Bem-vindos ao nosso casamento' }}</h1>
                <p class="text-sm md:text-base mb-4" :style="{ color: hero.media?.url ? '#ffffffcc' : '#666666' }">{{ hero.subtitle || 'Estamos muito felizes em compartilhar este momento com você' }}</p>
                <div class="flex gap-2 flex-wrap" :class="{ 'justify-center': heroStyle.textAlign === 'center' || !heroStyle.textAlign }">
                    <button v-if="hero.ctaPrimary?.label" class="px-4 py-2 rounded text-sm text-white font-medium" :style="{ backgroundColor: primaryColor }">{{ hero.ctaPrimary.label }}</button>
                    <button v-if="hero.ctaSecondary?.label" class="px-4 py-2 rounded text-sm border font-medium" :style="{ borderColor: primaryColor, color: hero.media?.url ? '#ffffff' : primaryColor }">{{ hero.ctaSecondary.label }}</button>
                </div>
            </div>
        </section>

        <!-- SAVE THE DATE -->
        <section v-if="saveTheDate.enabled" :id="SECTION_IDS.saveTheDate" class="p-6" :style="{ backgroundColor: saveTheDateStyle.backgroundColor || '#f5f5f5' }">
            <div class="max-w-md mx-auto" :class="{ 'bg-white rounded-lg shadow-md p-4': saveTheDateStyle.layout === 'card' }">
                <h2 class="text-xl font-semibold text-center mb-3" :style="{ color: secondaryColor }">Save the Date</h2>
                <p v-if="saveTheDate.description" class="text-sm text-gray-600 text-center mb-4">{{ saveTheDate.description }}</p>
                <div v-if="saveTheDate.showCountdown" class="flex justify-center gap-3 mb-4">
                    <div class="text-center"><div class="text-2xl font-bold" :style="{ color: primaryColor }">120</div><div class="text-xs text-gray-500">Dias</div></div>
                    <div class="text-center"><div class="text-2xl font-bold" :style="{ color: primaryColor }">08</div><div class="text-xs text-gray-500">Horas</div></div>
                    <div class="text-center"><div class="text-2xl font-bold" :style="{ color: primaryColor }">45</div><div class="text-xs text-gray-500">Min</div></div>
                </div>
                <div v-if="saveTheDate.showMap" class="bg-gray-200 rounded h-32 flex items-center justify-center mb-3">
                    <div class="text-center text-gray-500">
                        <svg class="w-8 h-8 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        <span class="text-xs">{{ saveTheDate.mapProvider === 'google' ? 'Google Maps' : 'Mapa' }}</span>
                    </div>
                </div>
                <button v-if="saveTheDate.showCalendarButton" class="w-full py-2 rounded text-sm text-white" :style="{ backgroundColor: primaryColor }">📅 Adicionar ao Calendário</button>
            </div>
        </section>

        <!-- GIFT REGISTRY -->
        <section v-if="giftRegistry.enabled" :id="SECTION_IDS.giftRegistry" class="p-6" :style="{ backgroundColor: giftRegistry.style?.backgroundColor || '#ffffff' }">
            <div class="max-w-md mx-auto text-center">
                <h2 class="text-xl font-semibold mb-2" :style="{ color: secondaryColor }">{{ giftRegistry.title || 'Lista de Presentes' }}</h2>
                <p class="text-sm text-gray-600 mb-4">{{ giftRegistry.description || 'Em breve disponibilizaremos nossa lista de presentes.' }}</p>
                <div class="grid grid-cols-3 gap-2">
                    <div v-for="i in 3" :key="i" class="bg-gray-100 rounded p-3">
                        <div class="w-10 h-10 mx-auto mb-2 bg-gray-200 rounded flex items-center justify-center">🎁</div>
                        <div class="text-xs text-gray-500">Presente {{ i }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- RSVP -->
        <section v-if="rsvp.enabled" :id="SECTION_IDS.rsvp" class="p-6" :style="{ backgroundColor: rsvp.style?.backgroundColor || '#f5f5f5' }">
            <div class="max-w-md mx-auto">
                <h2 class="text-xl font-semibold text-center mb-2" :style="{ color: secondaryColor }">{{ rsvp.title || 'Confirme sua Presença' }}</h2>
                <p v-if="rsvp.description" class="text-sm text-gray-600 text-center mb-4">{{ rsvp.description }}</p>
                <div class="space-y-3">
                    <div v-for="(field, i) in (rsvp.mockFields || [])" :key="i">
                        <label class="block text-xs text-gray-600 mb-1">{{ field.label }}</label>
                        <input v-if="field.type === 'text' || field.type === 'email' || field.type === 'number'" :type="field.type" :placeholder="field.label" class="w-full px-3 py-2 border rounded text-sm" disabled />
                        <select v-else-if="field.type === 'select'" class="w-full px-3 py-2 border rounded text-sm bg-white" disabled><option>Selecione...</option></select>
                    </div>
                    <template v-if="!rsvp.mockFields?.length">
                        <div><label class="block text-xs text-gray-600 mb-1">Nome</label><input type="text" placeholder="Seu nome" class="w-full px-3 py-2 border rounded text-sm" disabled /></div>
                        <div><label class="block text-xs text-gray-600 mb-1">Email</label><input type="email" placeholder="seu@email.com" class="w-full px-3 py-2 border rounded text-sm" disabled /></div>
                    </template>
                    <button class="w-full py-2 rounded text-sm text-white font-medium" :style="{ backgroundColor: primaryColor }">Confirmar Presença</button>
                </div>
            </div>
        </section>

        <!-- PHOTO GALLERY -->
        <section v-if="photoGallery.enabled" :id="SECTION_IDS.photoGallery" class="p-6" :style="{ backgroundColor: galleryStyle.backgroundColor || '#ffffff' }">
            <div class="max-w-lg mx-auto">
                <div v-for="(album, key) in photoGallery.albums" :key="key" class="mb-6">
                    <h3 class="text-lg font-semibold mb-3" :style="{ color: secondaryColor }">{{ album.title || key }}</h3>
                    <div v-if="album.photos?.length" class="grid gap-2" :class="{ 'grid-cols-2': galleryStyle.columns === 2, 'grid-cols-3': galleryStyle.columns === 3 || !galleryStyle.columns, 'grid-cols-4': galleryStyle.columns === 4 }">
                        <div v-for="(photo, i) in album.photos.slice(0, 6)" :key="i" class="aspect-square bg-gray-100 rounded overflow-hidden">
                            <img :src="photo.url || photo" :alt="photo.alt || `Foto ${i + 1}`" class="w-full h-full object-cover" />
                        </div>
                    </div>
                    <div v-else class="grid grid-cols-3 gap-2">
                        <div v-for="i in 6" :key="i" class="aspect-square bg-gray-100 rounded flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                    </div>
                </div>
                <div v-if="!photoGallery.albums || Object.keys(photoGallery.albums).length === 0" class="text-center py-8">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    <p class="text-sm text-gray-500">Galeria de Fotos</p>
                </div>
                <p v-if="photoGallery.showLightbox" class="text-xs text-gray-400 text-center mt-2">Clique nas fotos para ampliar</p>
            </div>
        </section>

        <!-- FOOTER -->
        <footer v-if="footer.enabled" class="p-4" :style="{ backgroundColor: footerStyle.backgroundColor || '#333333', color: footerStyle.textColor || '#ffffff', borderTop: footerStyle.borderTop ? '1px solid #e5e5e5' : 'none' }">
            <div class="max-w-md mx-auto text-center">
                <div v-if="footer.socialLinks?.length" class="flex justify-center gap-3 mb-3">
                    <a v-for="(link, i) in footer.socialLinks" :key="i" :href="link.url || '#'" class="w-8 h-8 rounded-full flex items-center justify-center text-sm" :style="{ backgroundColor: primaryColor }">
                        <span v-if="link.platform === 'instagram'">📷</span>
                        <span v-else-if="link.platform === 'facebook'">📘</span>
                        <span v-else-if="link.platform === 'twitter'">🐦</span>
                        <span v-else-if="link.platform === 'youtube'">▶️</span>
                        <span v-else>🔗</span>
                    </a>
                </div>
                <p class="text-xs opacity-80">{{ footer.copyrightText || '© ' + (footer.copyrightYear || new Date().getFullYear()) + ' - Todos os direitos reservados' }}</p>
                <a v-if="footer.showPrivacyPolicy" :href="footer.privacyPolicyUrl || '#'" class="text-xs underline opacity-60 hover:opacity-100">Política de Privacidade</a>
                <button v-if="footer.showBackToTop" class="mt-3 text-xs opacity-60 hover:opacity-100 flex items-center justify-center mx-auto gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                    Voltar ao topo
                </button>
            </div>
        </footer>

        <!-- EMPTY STATE -->
        <div v-if="!header.enabled && !hero.enabled && !saveTheDate.enabled && !giftRegistry.enabled && !rsvp.enabled && !photoGallery.enabled && !footer.enabled" class="flex items-center justify-center min-h-[300px] bg-gray-50">
            <div class="text-center text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                <p class="text-sm">Ative as seções para visualizar o preview</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.site-preview { background-color: #ffffff; min-height: 400px; }
.site-preview section { transition: all 0.3s ease; }
video { object-fit: cover; }
</style>
