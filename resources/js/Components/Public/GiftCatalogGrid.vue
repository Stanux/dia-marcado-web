<script setup lang="ts">
/**
 * GiftCatalogGrid Component
 * 
 * Displays a responsive grid of gift items with customizable title typography.
 * Supports sorting by price and integrates with the gift registry system.
 * 
 * @Requirements: 2.1, 2.4, 3.1, 3.2, 15.2
 */
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import GiftCard from './GiftCard.vue';
import PurchaseModal from './PurchaseModal.vue';
import { useGiftRegistry } from '@/Composables/useGiftRegistry';

interface GiftItem {
  id: number;
  name: string;
  description: string;
  photo_url: string;
  display_price: number;
  quantity_available: number;
  is_enabled: boolean;
  is_sold_out: boolean;
}

interface GiftRegistryConfig {
  section_title: string;
  title_font_family?: string;
  title_font_size?: number;
  title_color?: string;
  title_style?: string;
  fee_modality: string;
}

interface Props {
  eventId: number;
  config?: GiftRegistryConfig | null;
  isPreview?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  config: null,
  isPreview: false
});

// State
const gifts = ref<GiftItem[]>([]);
const sortBy = ref<'price_asc' | 'price_desc'>('price_asc');
const selectedGift = ref<GiftItem | null>(null);

// Use gift registry composable for API calls and error handling
const { loading, error, fetchGifts: fetchGiftsAPI, clearError, retryLastOperation } = useGiftRegistry();

// Default config if not provided
const giftConfig = computed(() => {
  return props.config || {
    section_title: 'Lista de Presentes',
    fee_modality: 'couple_pays',
    title_font_family: null,
    title_font_size: null,
    title_color: null,
    title_style: 'normal',
  };
});

// Computed
const titleStyles = computed(() => {
  const styles: Record<string, string> = {};
  const config = giftConfig.value;
  
  if (config.title_font_family) {
    styles.fontFamily = config.title_font_family;
  }
  
  if (config.title_font_size) {
    styles.fontSize = `${config.title_font_size}px`;
  }
  
  if (config.title_color) {
    styles.color = config.title_color;
  }
  
  if (config.title_style) {
    const style = config.title_style;
    if (style === 'bold' || style === 'bold_italic') {
      styles.fontWeight = 'bold';
    }
    if (style === 'italic' || style === 'bold_italic') {
      styles.fontStyle = 'italic';
    }
  }
  
  return styles;
});

const sortedGifts = computed(() => {
  const sorted = [...gifts.value];
  
  if (sortBy.value === 'price_asc') {
    sorted.sort((a, b) => a.display_price - b.display_price);
  } else {
    sorted.sort((a, b) => b.display_price - a.display_price);
  }
  
  return sorted;
});

// Methods
async function fetchGifts() {
  try {
    gifts.value = await fetchGiftsAPI(props.eventId);
  } catch (err) {
    // Error is already handled by the composable
    console.error('Error fetching gifts:', err);
  }
}

async function handleRetry() {
  try {
    gifts.value = await retryLastOperation();
  } catch (err) {
    console.error('Error retrying:', err);
  }
}

function openPurchaseModal(gift: GiftItem) {
  if (props.isPreview) {
    // In preview mode, don't allow actual purchases
    return;
  }
  selectedGift.value = gift;
}

function closePurchaseModal() {
  selectedGift.value = null;
}

function handlePurchaseSuccess() {
  closePurchaseModal();
  // Refresh the gift list to update quantities
  fetchGifts();
}

function formatPrice(priceInCents: number): string {
  return (priceInCents / 100).toFixed(2).replace('.', ',');
}

// Lifecycle
onMounted(() => {
  if (props.isPreview) {
    gifts.value = [];
    return;
  }

  fetchGifts();
});
</script>

<template>
  <div class="gift-catalog">
    <!-- Section Title -->
    <div class="catalog-header">
      <h2 :style="titleStyles" class="catalog-title">
        {{ giftConfig.section_title }}
      </h2>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Carregando presentes...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <svg class="error-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="error-message">{{ error }}</p>
      <button @click="handleRetry" class="retry-button">
        Tentar Novamente
      </button>
    </div>

    <!-- Empty State -->
    <div v-else-if="sortedGifts.length === 0" class="empty-state">
      <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
      </svg>
      <h3 class="empty-title">Nenhum presente disponível</h3>
      <p class="empty-message">
        A lista de presentes ainda não foi configurada.
      </p>
    </div>

    <!-- Gift Grid -->
    <div v-else class="gift-content">
      <!-- Filters -->
      <div class="filters">
        <label for="sort-select" class="filter-label">Ordenar por:</label>
        <select 
          id="sort-select"
          v-model="sortBy" 
          class="sort-select"
        >
          <option value="price_asc">Menor Preço</option>
          <option value="price_desc">Maior Preço</option>
        </select>
      </div>

      <!-- Grid -->
      <div class="gift-grid">
        <GiftCard
          v-for="gift in sortedGifts"
          :key="gift.id"
          :gift="gift"
          :is-preview="isPreview"
          @purchase="openPurchaseModal"
        />
      </div>
    </div>

    <!-- Purchase Modal -->
    <PurchaseModal
      v-if="selectedGift"
      :gift="selectedGift"
      :event-id="eventId"
      :config="giftConfig"
      @close="closePurchaseModal"
      @success="handlePurchaseSuccess"
    />
  </div>
</template>

<style scoped>
.gift-catalog {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1rem;
}

/* Header */
.catalog-header {
  text-align: center;
  margin-bottom: 3rem;
}

.catalog-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

/* Loading State */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 1rem;
  text-align: center;
}

.spinner {
  width: 3rem;
  height: 3rem;
  border: 4px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Error State */
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 1rem;
  text-align: center;
}

.error-icon {
  width: 4rem;
  height: 4rem;
  color: #ef4444;
  margin-bottom: 1rem;
}

.error-message {
  font-size: 1rem;
  color: #6b7280;
  margin-bottom: 1.5rem;
}

.retry-button {
  padding: 0.75rem 1.5rem;
  background-color: #3b82f6;
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.retry-button:hover {
  background-color: #2563eb;
}

/* Empty State */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 1rem;
  text-align: center;
}

.empty-icon {
  width: 4rem;
  height: 4rem;
  color: #9ca3af;
  margin-bottom: 1rem;
}

.empty-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
}

.empty-message {
  font-size: 1rem;
  color: #6b7280;
  max-width: 28rem;
}

/* Content */
.gift-content {
  width: 100%;
}

/* Filters */
.filters {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-bottom: 2rem;
  padding: 0 0.5rem;
}

.filter-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #6b7280;
}

.sort-select {
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  color: #374151;
  background-color: white;
  cursor: pointer;
  transition: border-color 0.2s;
}

.sort-select:hover {
  border-color: #9ca3af;
}

.sort-select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Grid */
.gift-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 2rem;
  padding: 0.5rem;
}

/* Responsive */
@media (max-width: 768px) {
  .gift-catalog {
    padding: 1.5rem 1rem;
  }

  .catalog-title {
    font-size: 2rem;
  }

  .catalog-header {
    margin-bottom: 2rem;
  }

  .gift-grid {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1.5rem;
  }

  .filters {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
}

@media (max-width: 480px) {
  .catalog-title {
    font-size: 1.75rem;
  }

  .gift-grid {
    grid-template-columns: 1fr;
    gap: 1.25rem;
  }
}
</style>
