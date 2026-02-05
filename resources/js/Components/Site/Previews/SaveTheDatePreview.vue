<script setup>
/**
 * SaveTheDatePreview Component
 * 
 * Renders the Save the Date section with countdown timer,
 * map display, and calendar button.
 * 
 * @Requirements: 10.1, 10.2, 10.5, 10.6
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
    wedding: {
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
const style = computed(() => props.content.style || {});
const mapCoordinates = computed(() => props.content.mapCoordinates || { lat: null, lng: null });
const sectionTypography = computed(() => props.content.sectionTypography || {});
const descriptionTypography = computed(() => props.content.descriptionTypography || {});
const countdownNumbersTypography = computed(() => props.content.countdownNumbersTypography || {});
const countdownLabelsTypography = computed(() => props.content.countdownLabelsTypography || {});

// Countdown state
const countdown = ref({ days: 0, hours: 0, minutes: 0, seconds: 0 });
let countdownInterval = null;

// Calculate countdown
const calculateCountdown = () => {
    const weddingDate = props.wedding.wedding_date 
        ? new Date(props.wedding.wedding_date)
        : new Date(Date.now() + 90 * 24 * 60 * 60 * 1000); // Default: 90 days from now
    
    const now = new Date();
    const diff = weddingDate.getTime() - now.getTime();
    
    if (diff <= 0) {
        countdown.value = { days: 0, hours: 0, minutes: 0, seconds: 0 };
        return;
    }
    
    countdown.value = {
        days: Math.floor(diff / (1000 * 60 * 60 * 24)),
        hours: Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
        minutes: Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60)),
        seconds: Math.floor((diff % (1000 * 60)) / 1000),
    };
};

// Start countdown timer
onMounted(() => {
    calculateCountdown();
    countdownInterval = setInterval(calculateCountdown, 1000);
});

onUnmounted(() => {
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
});

// Format countdown based on format setting
const formattedCountdown = computed(() => {
    const format = props.content.countdownFormat || 'days';
    const { days, hours, minutes, seconds } = countdown.value;
    
    switch (format) {
        case 'days':
            return [{ value: days, label: 'dias' }];
        case 'hours':
            return [
                { value: days, label: 'dias' },
                { value: hours, label: 'horas' },
            ];
        case 'minutes':
            return [
                { value: days, label: 'dias' },
                { value: hours, label: 'horas' },
                { value: minutes, label: 'min' },
            ];
        default: // full
            return [
                { value: days, label: 'dias' },
                { value: hours, label: 'horas' },
                { value: minutes, label: 'min' },
                { value: seconds, label: 'seg' },
            ];
    }
});

// Map embed URL
const mapEmbedUrl = computed(() => {
    if (!props.content.showMap || !mapCoordinates.value.lat || !mapCoordinates.value.lng) {
        return null;
    }
    
    const { lat, lng } = mapCoordinates.value;
    const provider = props.content.mapProvider || 'google';
    
    if (provider === 'google') {
        return `https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3000!2d${lng}!3d${lat}!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zM!5e0!3m2!1spt-BR!2sbr!4v1`;
    }
    
    // Mapbox static map
    return `https://api.mapbox.com/styles/v1/mapbox/streets-v11/static/${lng},${lat},14,0/400x300?access_token=placeholder`;
});

// Layout classes
const layoutClasses = computed(() => {
    switch (style.value.layout) {
        case 'inline':
            return 'flex flex-col md:flex-row items-start gap-8';
        case 'modal':
            return 'max-w-lg mx-auto bg-white rounded-xl shadow-xl p-8';
        default: // card
            return 'max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-8';
    }
});

// Description style
const descriptionStyle = computed(() => ({
    fontFamily: descriptionTypography.value.fontFamily || 'Playfair Display',
    color: descriptionTypography.value.fontColor || '#666666',
    fontSize: `${descriptionTypography.value.fontSize || 16}px`,
    fontWeight: descriptionTypography.value.fontWeight || 400,
    fontStyle: descriptionTypography.value.fontItalic ? 'italic' : 'normal',
    textDecoration: descriptionTypography.value.fontUnderline ? 'underline' : 'none',
}));

// Section elements style (title, date, location) - uses sectionTypography
const sectionElementsStyle = computed(() => ({
    fontFamily: sectionTypography.value.fontFamily || descriptionTypography.value.fontFamily || 'Playfair Display',
    color: sectionTypography.value.fontColor || '#d4a574',
    fontSize: `${sectionTypography.value.fontSize || 18}px`,
    fontWeight: sectionTypography.value.fontWeight || 400,
    fontStyle: sectionTypography.value.fontItalic ? 'italic' : 'normal',
    textDecoration: sectionTypography.value.fontUnderline ? 'underline' : 'none',
}));

// Countdown numbers style
const countdownNumbersStyle = computed(() => {
    const baseFontSize = countdownNumbersTypography.value.fontSize || 48;
    return {
        fontFamily: countdownNumbersTypography.value.fontFamily || 'Playfair Display',
        color: countdownNumbersTypography.value.fontColor || '#d4a574',
        fontSize: `${baseFontSize}px`,
        fontWeight: countdownNumbersTypography.value.fontWeight || 700,
        fontStyle: countdownNumbersTypography.value.fontItalic ? 'italic' : 'normal',
        textDecoration: countdownNumbersTypography.value.fontUnderline ? 'underline' : 'none',
    };
});

// Countdown labels style
const countdownLabelsStyle = computed(() => {
    const baseFontSize = countdownLabelsTypography.value.fontSize || 12;
    return {
        fontFamily: countdownLabelsTypography.value.fontFamily || 'Montserrat',
        color: countdownLabelsTypography.value.fontColor || '#999999',
        fontSize: `${baseFontSize}px`,
        fontWeight: countdownLabelsTypography.value.fontWeight || 400,
        fontStyle: countdownLabelsTypography.value.fontItalic ? 'italic' : 'normal',
        textDecoration: countdownLabelsTypography.value.fontUnderline ? 'underline' : 'none',
    };
});

// Format wedding date
const formattedDate = computed(() => {
    if (!props.wedding.wedding_date) return 'Data a definir';
    
    return new Date(props.wedding.wedding_date).toLocaleDateString('pt-BR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
});
</script>

<template>
    <section 
        class="py-16 px-4"
        :style="{ backgroundColor: style.backgroundColor || '#f5f5f5' }"
        id="save-the-date"
    >
        <div class="max-w-6xl mx-auto">
            <div :class="layoutClasses">
                <!-- Date & Location Info -->
                <div 
                    class="flex-1"
                    :class="style.layout === 'inline' ? 'text-left' : 'text-center'"
                >
                    <h2 
                        class="text-2xl md:text-3xl font-bold mb-4"
                        :style="{ 
                            color: sectionTypography.fontColor || theme.primaryColor, 
                            fontFamily: sectionTypography.fontFamily || descriptionTypography.fontFamily || theme.fontFamily,
                            fontWeight: sectionTypography.fontWeight || 700,
                            fontStyle: sectionTypography.fontItalic ? 'italic' : 'normal',
                            textDecoration: sectionTypography.fontUnderline ? 'underline' : 'none'
                        }"
                    >
                        Save the Date
                    </h2>
                    
                    <!-- Wedding Date -->
                    <p 
                        class="mb-2"
                        :style="sectionElementsStyle"
                    >
                        {{ formattedDate }}
                    </p>
                    
                    <!-- Location Name with icon -->
                    <div 
                        v-if="wedding.venue" 
                        class="flex items-center mb-1"
                        :class="style.layout === 'inline' ? 'justify-start' : 'justify-center'"
                    >
                        <svg class="w-5 h-5 mr-2 flex-shrink-0" :style="{ color: sectionElementsStyle.color }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span 
                            class="font-bold"
                            :style="sectionElementsStyle"
                        >
                            {{ wedding.venue }}
                        </span>
                    </div>
                    
                    <!-- Full Address: Street, Neighborhood, City - State (all in one line) -->
                    <p 
                        v-if="wedding.settings?.venue_address || wedding.settings?.venue_neighborhood || wedding.city || wedding.state" 
                        class="mb-4 text-sm"
                        :style="{ 
                            fontFamily: sectionTypography.fontFamily || descriptionTypography.fontFamily || theme.fontFamily,
                            fontSize: sectionElementsStyle.fontSize,
                            fontWeight: sectionElementsStyle.fontWeight,
                            fontStyle: sectionElementsStyle.fontItalic ? 'italic' : 'normal',
                            textDecoration: sectionElementsStyle.fontUnderline ? 'underline' : 'none',
                            color: sectionElementsStyle.color
                        }"
                    >
                        <span v-if="wedding.settings?.venue_address">{{ wedding.settings.venue_address }}</span><span v-if="wedding.settings?.venue_address && (wedding.settings?.venue_neighborhood || wedding.city)">, </span><span v-if="wedding.settings?.venue_neighborhood">{{ wedding.settings.venue_neighborhood }}</span><span v-if="wedding.settings?.venue_neighborhood && wedding.city">, </span><span v-if="wedding.city">{{ wedding.city }}</span><span v-if="wedding.state"> - {{ wedding.state }}</span>
                    </p>
                    </p>
                    
                    <!-- Description -->
                    <p 
                        v-if="content.description"
                        class="mb-6 max-w-lg"
                        :class="style.layout === 'inline' ? 'mx-0' : 'mx-auto'"
                        :style="descriptionStyle"
                    >
                        {{ content.description }}
                    </p>

                    <!-- Countdown -->
                    <div 
                        v-if="content.showCountdown"
                        class="mb-6"
                    >
                        <div 
                            class="flex flex-wrap gap-2 sm:gap-3 md:gap-4 lg:gap-6 px-2 justify-center"
                            :class="style.layout === 'inline' ? 'sm:justify-start' : ''"
                        >
                            <div 
                                v-for="(item, index) in formattedCountdown"
                                :key="index"
                                class="text-center min-w-0 flex-shrink-0"
                            >
                                <div 
                                    class="font-bold leading-none"
                                    :style="countdownNumbersStyle"
                                >
                                    {{ String(item.value).padStart(2, '0') }}
                                </div>
                                <div 
                                    class="uppercase tracking-wider mt-1 md:mt-2"
                                    :style="countdownLabelsStyle"
                                >
                                    {{ item.label }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map -->
                <div 
                    v-if="content.showMap"
                    class="flex-1 w-full"
                    :class="style.layout === 'inline' ? '' : 'flex justify-center'"
                >
                    <div class="w-full max-w-md">
                        <div 
                            v-if="mapEmbedUrl"
                            class="rounded-lg overflow-hidden shadow-md"
                        >
                            <iframe
                                :src="mapEmbedUrl"
                                width="100%"
                                height="300"
                                style="border:0;"
                                allowfullscreen=""
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                            />
                        </div>
                        <div 
                            v-else
                            class="bg-gray-200 rounded-lg h-[300px] flex items-center justify-center"
                        >
                            <div class="text-center text-gray-500">
                                <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <p class="text-sm">Configure as coordenadas do mapa</p>
                            </div>
                        </div>

                        <!-- Directions Link -->
                        <a
                            v-if="mapCoordinates.lat && mapCoordinates.lng"
                            :href="`https://www.google.com/maps/dir/?api=1&destination=${mapCoordinates.lat},${mapCoordinates.lng}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-3 inline-flex items-center text-sm hover:underline"
                            :style="{ color: theme.primaryColor }"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            Como chegar
                        </a>
                    </div>
                </div>
            </div>

            <!-- Calendar Button - Always at the end, centered -->
            <div 
                v-if="content.showCalendarButton"
                class="mt-12 text-center"
            >
                <button
                    class="inline-flex items-center px-6 py-3 text-sm font-medium rounded-md text-white transition-colors hover:opacity-90"
                    :style="{ backgroundColor: theme.primaryColor }"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Adicionar ao Calendário
                </button>
            </div>
        </div>

        <!-- Edit Mode Indicator -->
        <div 
            v-if="isEditMode"
            class="absolute top-2 left-2 bg-blue-500 text-white text-xs px-2 py-0.5 rounded"
        >
            Save the Date
        </div>
    </section>
</template>

<style scoped>
/* Countdown animation */
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.countdown-value {
    animation: pulse 1s ease-in-out infinite;
}
</style>
