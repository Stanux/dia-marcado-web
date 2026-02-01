<script setup>
/**
 * Countdown Component
 * 
 * Displays a countdown timer to a target date.
 * Updates every second with configurable format.
 * 
 * @Requirements: 10.5
 */
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    targetDate: {
        type: [Date, String],
        required: true,
    },
    format: {
        type: String,
        default: 'full', // 'days', 'hours', 'minutes', 'full'
        validator: (value) => ['days', 'hours', 'minutes', 'full'].includes(value),
    },
    theme: {
        type: Object,
        default: () => ({}),
    },
    size: {
        type: String,
        default: 'medium', // 'small', 'medium', 'large'
    },
});

// Countdown state
const countdown = ref({ days: 0, hours: 0, minutes: 0, seconds: 0 });
const isExpired = ref(false);
let countdownInterval = null;

// Parse target date
const targetDateTime = computed(() => {
    if (props.targetDate instanceof Date) {
        return props.targetDate;
    }
    return new Date(props.targetDate);
});

// Calculate countdown
const calculateCountdown = () => {
    const now = new Date();
    const diff = targetDateTime.value.getTime() - now.getTime();
    
    if (diff <= 0) {
        countdown.value = { days: 0, hours: 0, minutes: 0, seconds: 0 };
        isExpired.value = true;
        if (countdownInterval) {
            clearInterval(countdownInterval);
            countdownInterval = null;
        }
        return;
    }
    
    isExpired.value = false;
    countdown.value = {
        days: Math.floor(diff / (1000 * 60 * 60 * 24)),
        hours: Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
        minutes: Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60)),
        seconds: Math.floor((diff % (1000 * 60)) / 1000),
    };
};

// Format countdown based on format setting
const formattedCountdown = computed(() => {
    const { days, hours, minutes, seconds } = countdown.value;
    
    switch (props.format) {
        case 'days':
            return [{ value: days, label: 'dias', singular: 'dia' }];
        case 'hours':
            return [
                { value: days, label: 'dias', singular: 'dia' },
                { value: hours, label: 'horas', singular: 'hora' },
            ];
        case 'minutes':
            return [
                { value: days, label: 'dias', singular: 'dia' },
                { value: hours, label: 'horas', singular: 'hora' },
                { value: minutes, label: 'minutos', singular: 'minuto' },
            ];
        default: // full
            return [
                { value: days, label: 'dias', singular: 'dia' },
                { value: hours, label: 'horas', singular: 'hora' },
                { value: minutes, label: 'minutos', singular: 'minuto' },
                { value: seconds, label: 'segundos', singular: 'segundo' },
            ];
    }
});

// Size classes
const sizeClasses = computed(() => {
    switch (props.size) {
        case 'small':
            return {
                container: 'gap-3',
                number: 'text-2xl md:text-3xl',
                label: 'text-xs',
            };
        case 'large':
            return {
                container: 'gap-6 md:gap-8',
                number: 'text-5xl md:text-6xl lg:text-7xl',
                label: 'text-sm md:text-base',
            };
        default: // medium
            return {
                container: 'gap-4 md:gap-6',
                number: 'text-3xl md:text-4xl lg:text-5xl',
                label: 'text-xs md:text-sm',
            };
    }
});

// Get label (singular or plural)
const getLabel = (item) => {
    return item.value === 1 ? item.singular : item.label;
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

// Watch for target date changes
watch(() => props.targetDate, () => {
    calculateCountdown();
});
</script>

<template>
    <div class="countdown-container">
        <!-- Expired State -->
        <div v-if="isExpired" class="text-center">
            <p class="text-xl font-medium text-gray-600">
                🎉 O grande dia chegou!
            </p>
        </div>

        <!-- Countdown Display -->
        <div 
            v-else
            class="flex justify-center items-center"
            :class="sizeClasses.container"
        >
            <div 
                v-for="(item, index) in formattedCountdown"
                :key="index"
                class="text-center"
            >
                <!-- Number -->
                <div 
                    class="font-bold tabular-nums leading-none"
                    :class="sizeClasses.number"
                    :style="{ color: theme.primaryColor }"
                >
                    {{ String(item.value).padStart(2, '0') }}
                </div>
                
                <!-- Label -->
                <div 
                    class="uppercase tracking-wider text-gray-500 mt-2"
                    :class="sizeClasses.label"
                >
                    {{ getLabel(item) }}
                </div>
            </div>

            <!-- Separator (optional visual element between items) -->
            <template v-if="format === 'full'">
                <div 
                    v-for="(item, index) in formattedCountdown.slice(0, -1)"
                    :key="`sep-${index}`"
                    class="hidden"
                />
            </template>
        </div>
    </div>
</template>

<style scoped>
.countdown-container {
    font-variant-numeric: tabular-nums;
}

/* Smooth number transitions */
.tabular-nums {
    font-variant-numeric: tabular-nums;
    transition: transform 0.1s ease-out;
}

/* Subtle pulse animation on seconds */
@keyframes pulse-subtle {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}

.countdown-container:last-child .tabular-nums {
    animation: pulse-subtle 1s ease-in-out infinite;
}
</style>
