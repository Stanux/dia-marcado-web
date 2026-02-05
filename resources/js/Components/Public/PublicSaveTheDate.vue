<script setup>
/**
 * PublicSaveTheDate Component
 * 
 * Renders the Save the Date section with countdown timer,
 * embedded map (Google/Mapbox), and calendar button.
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
});

// Computed properties
const style = computed(() => props.content.style || {});
const mapCoordinates = computed(() => props.content.mapCoordinates || { lat: null, lng: null });
const sectionTypography = computed(() => props.content.sectionTypography || {});
const descriptionTypography = computed(() => props.content.descriptionTypography || {});
const countdownNumbersTypography = computed(() => props.content.countdownNumbersTypography || {});
const countdownLabelsTypography = computed(() => props.content.countdownLabelsTypography || {});

// Wedding date
const weddingDate = computed(() => {
    if (props.wedding.wedding_date) {
        return new Date(props.wedding.wedding_date);
    }
    return null;
});

// Countdown state
const countdown = ref({ days: 0, hours: 0, minutes: 0, seconds: 0 });
let countdownInterval = null;

// Calculate countdown
const calculateCountdown = () => {
    if (!weddingDate.value) return;
    
    const now = new Date();
    const diff = weddingDate.value.getTime() - now.getTime();
    
    if (diff <= 0) {
        countdown.value = { days: 0, hours: 0, minutes: 0, seconds: 0 };
        if (countdownInterval) {
            clearInterval(countdownInterval);
            countdownInterval = null;
        }
        return;
    }
    
    countdown.value = {
        days: Math.floor(diff / (1000 * 60 * 60 * 24)),
        hours: Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
        minutes: Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60)),
        seconds: Math.floor((diff % (1000 * 60)) / 1000),
    };
};

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

// Start countdown timer
onMounted(() => {
    if (weddingDate.value) {
        calculateCountdown();
        countdownInterval = setInterval(calculateCountdown, 1000);
    }
});

onUnmounted(() => {
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
});

// Format wedding date
const formattedDate = computed(() => {
    if (!weddingDate.value) return 'Data a definir';
    
    return weddingDate.value.toLocaleDateString('pt-BR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
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
    
    // Mapbox static map (would need API key in production)
    return `https://api.mapbox.com/styles/v1/mapbox/streets-v11/static/${lng},${lat},14,0/400x300@2x?access_token=placeholder`;
});

// Google Maps directions URL
const directionsUrl = computed(() => {
    if (!mapCoordinates.value.lat || !mapCoordinates.value.lng) return null;
    return `https://www.google.com/maps/dir/?api=1&destination=${mapCoordinates.value.lat},${mapCoordinates.value.lng}`;
});

// Layout classes
const layoutClasses = computed(() => {
    switch (style.value.layout) {
        case 'inline':
            return 'flex flex-col lg:flex-row items-start gap-12';
        case 'modal':
            return 'max-w-xl mx-auto bg-white rounded-2xl shadow-2xl p-8 md:p-12';
        default: // card
            return 'max-w-3xl mx-auto bg-white rounded-lg shadow-lg p-8';
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
    lineHeight: '1.6',
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
    return {
        fontFamily: countdownNumbersTypography.value.fontFamily || 'Playfair Display',
        color: countdownNumbersTypography.value.fontColor || '#d4a574',
        fontWeight: countdownNumbersTypography.value.fontWeight || 700,
        fontStyle: countdownNumbersTypography.value.fontItalic ? 'italic' : 'normal',
        textDecoration: countdownNumbersTypography.value.fontUnderline ? 'underline' : 'none',
    };
});

// Countdown numbers font size - mantém tamanho original
const countdownNumbersFontSize = computed(() => {
    const baseFontSize = countdownNumbersTypography.value.fontSize || 48;
    return {
        mobile: `${baseFontSize}px`,
        tablet: `${baseFontSize}px`,
        desktop: `${baseFontSize}px`,
    };
});

// Countdown labels style
const countdownLabelsStyle = computed(() => {
    return {
        fontFamily: countdownLabelsTypography.value.fontFamily || 'Montserrat',
        color: countdownLabelsTypography.value.fontColor || '#999999',
        fontWeight: countdownLabelsTypography.value.fontWeight || 400,
        fontStyle: countdownLabelsTypography.value.fontItalic ? 'italic' : 'normal',
        textDecoration: countdownLabelsTypography.value.fontUnderline ? 'underline' : 'none',
    };
});

// Countdown labels font size - mantém tamanho original
const countdownLabelsFontSize = computed(() => {
    const baseFontSize = countdownLabelsTypography.value.fontSize || 12;
    return {
        mobile: `${baseFontSize}px`,
        tablet: `${baseFontSize}px`,
        desktop: `${baseFontSize}px`,
    };
});

// Generate calendar file URL (would be handled by backend)
const calendarUrl = computed(() => {
    if (!props.wedding.id) return '#';
    return `/sites/${props.wedding.site_slug || 'site'}/calendar`;
});
</script>

<template>
    <section 
        class="py-20 px-4"
        :style="{ backgroundColor: style.backgroundColor || '#f8f6f4' }"
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
                        class="text-3xl md:text-4xl font-bold mb-6"
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
                        class="mb-3 font-medium capitalize"
                        :style="sectionElementsStyle"
                    >
                        {{ formattedDate }}
                    </p>
                    
                    <!-- Location Name with icon -->
                    <div 
                        v-if="wedding.venue" 
                        class="flex items-center mb-2"
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
                        class="mb-6"
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
                    
                    <!-- Description -->
                    <p 
                        v-if="content.description"
                        class="mb-8 max-w-lg leading-relaxed"
                        :class="style.layout === 'inline' ? 'mx-0' : 'mx-auto'"
                        :style="descriptionStyle"
                    >
                        {{ content.description }}
                    </p>

                    <!-- Countdown -->
                    <div 
                        v-if="content.showCountdown && weddingDate"
                        class="mb-8"
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
                                    class="font-bold leading-none countdown-number"
                                    :style="{
                                        ...countdownNumbersStyle,
                                        fontSize: countdownNumbersFontSize.mobile
                                    }"
                                >
                                    {{ String(item.value).padStart(2, '0') }}
                                </div>
                                <div 
                                    class="uppercase tracking-wider mt-1 md:mt-2 countdown-label"
                                    :style="{
                                        ...countdownLabelsStyle,
                                        fontSize: countdownLabelsFontSize.mobile
                                    }"
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
                            class="rounded-xl overflow-hidden shadow-lg"
                        >
                            <iframe
                                :src="mapEmbedUrl"
                                width="100%"
                                height="350"
                                style="border:0;"
                                allowfullscreen=""
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                            />
                        </div>
                        <div 
                            v-else
                            class="bg-gray-200 rounded-xl h-[350px] flex items-center justify-center"
                        >
                            <div class="text-center text-gray-500 p-6">
                                <svg class="w-16 h-16 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <p class="text-sm">Mapa não disponível</p>
                            </div>
                        </div>

                        <!-- Directions Link -->
                        <a
                            v-if="directionsUrl"
                            :href="directionsUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-4 inline-flex items-center text-sm font-medium hover:underline transition-colors"
                            :style="{ color: theme.primaryColor }"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                <a
                    :href="calendarUrl"
                    class="inline-flex items-center px-6 py-3 text-sm font-semibold rounded-lg text-white transition-all duration-200 hover:scale-105 hover:shadow-lg"
                    :style="{ backgroundColor: theme.primaryColor }"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Adicionar ao Calendário
                </a>
            </div>
        </div>
    </section>
</template>

<style scoped>
/* Section styling */
</style>
