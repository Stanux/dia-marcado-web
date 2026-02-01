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
import Countdown from '@/Components/Public/Countdown.vue';

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

// Wedding date
const weddingDate = computed(() => {
    if (props.wedding.wedding_date) {
        return new Date(props.wedding.wedding_date);
    }
    return null;
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
            return 'flex flex-col lg:flex-row items-center gap-12';
        case 'modal':
            return 'max-w-xl mx-auto bg-white rounded-2xl shadow-2xl p-8 md:p-12';
        default: // card
            return 'max-w-3xl mx-auto';
    }
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
                <div class="flex-1 text-center lg:text-left">
                    <h2 
                        class="text-3xl md:text-4xl font-bold mb-6"
                        :style="{ color: theme.primaryColor, fontFamily: theme.fontFamily }"
                    >
                        Save the Date
                    </h2>
                    
                    <!-- Wedding Date -->
                    <p class="text-xl md:text-2xl text-gray-800 mb-3 font-medium capitalize">
                        {{ formattedDate }}
                    </p>
                    
                    <!-- Venue -->
                    <div v-if="wedding.venue" class="flex items-center justify-center lg:justify-start text-gray-600 mb-2">
                        <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>{{ wedding.venue }}</span>
                    </div>
                    
                    <!-- City/State -->
                    <p v-if="wedding.city" class="text-gray-500 mb-6">
                        {{ wedding.city }}<span v-if="wedding.state">, {{ wedding.state }}</span>
                    </p>
                    
                    <!-- Description -->
                    <p 
                        v-if="content.description"
                        class="text-gray-600 mb-8 max-w-lg mx-auto lg:mx-0 leading-relaxed"
                    >
                        {{ content.description }}
                    </p>

                    <!-- Countdown -->
                    <Countdown
                        v-if="content.showCountdown && weddingDate"
                        :target-date="weddingDate"
                        :format="content.countdownFormat || 'full'"
                        :theme="theme"
                        class="mb-8"
                    />

                    <!-- Calendar Button -->
                    <a
                        v-if="content.showCalendarButton"
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

                <!-- Map -->
                <div 
                    v-if="content.showMap"
                    class="flex-1 w-full lg:w-auto lg:max-w-md"
                >
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
    </section>
</template>

<style scoped>
/* Section styling */
</style>
