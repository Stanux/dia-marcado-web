<script setup lang="ts">
/**
 * GiftCatalogGrid Component
 * 
 * Displays a responsive grid of gift items with customizable title typography.
 * Supports sorting by price and integrates with the gift registry system.
 * 
 * @Requirements: 2.1, 2.4, 3.1, 3.2, 15.2
 */
import { ref, computed, onMounted, type CSSProperties } from 'vue';
import GiftCard from './GiftCard.vue';
import PurchaseModal from './PurchaseModal.vue';
import { useGiftRegistry } from '@/Composables/useGiftRegistry';

interface GiftItem {
  id: string;
  name: string;
  description: string;
  photo_url: string | null;
  display_price: number;
  quantity_available: number;
  is_sold_out: boolean;
  registry_mode: 'quantity' | 'quota';
  quota_total: number | null;
  quota_sold: number | null;
  quota_progress_percent: number | null;
  is_fallback_donation: boolean;
  minimum_custom_amount: number | null;
  allows_custom_amount: boolean;
}

interface GiftRegistryConfig {
  section_title: string;
  title_font_family?: string;
  title_font_size?: number;
  title_color?: string;
  title_style?: string;
  title_underline?: boolean;
  fee_modality: string;
  registry_mode?: 'quantity' | 'quota';
}

interface TypographyConfig {
  fontFamily?: string | null;
  fontColor?: string | null;
  fontSize?: number | null;
  fontWeight?: number | string | null;
  fontItalic?: boolean;
  fontUnderline?: boolean;
}

interface GiftCatalogAppearance {
  cardBackgroundColor?: string | null;
  cardBorderColor?: string | null;
  buttonBackgroundColor?: string | null;
  cardTitleTypography?: TypographyConfig | null;
  cardDescriptionTypography?: TypographyConfig | null;
  cardPriceTypography?: TypographyConfig | null;
  buttonTypography?: TypographyConfig | null;
}

interface Props {
  eventId: string;
  config?: GiftRegistryConfig | null;
  appearance?: GiftCatalogAppearance | null;
  isPreview?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  config: null,
  appearance: null,
  isPreview: false
});

// State
const gifts = ref<GiftItem[]>([]);
const sortBy = ref<'price_asc' | 'price_desc'>('price_desc');
const viewMode = ref<'grid' | 'list'>('grid');
const selectedGift = ref<GiftItem | null>(null);

// Use gift registry composable for API calls and error handling
const { loading, error, fetchGifts: fetchGiftsAPI, clearError, retryLastOperation } = useGiftRegistry();

// Default config if not provided
const giftConfig = computed(() => {
  return props.config || {
    section_title: 'Lista de Presentes',
    registry_mode: 'quantity',
    fee_modality: 'couple_pays',
    title_font_family: null,
    title_font_size: null,
    title_color: null,
    title_style: 'normal',
    title_underline: false,
  };
});

const appearance = computed<GiftCatalogAppearance>(() => props.appearance || {});

const resolveTypographyStyle = (typography?: TypographyConfig | null): CSSProperties => {
  if (!typography) {
    return {};
  }

  const styles: CSSProperties = {};

  if (typography.fontFamily) {
    styles.fontFamily = typography.fontFamily;
  }

  if (typography.fontColor) {
    styles.color = typography.fontColor;
  }

  if (typography.fontSize !== null && typography.fontSize !== undefined && typography.fontSize !== '') {
    styles.fontSize = `${typography.fontSize}px`;
  }

  if (typography.fontWeight !== null && typography.fontWeight !== undefined && typography.fontWeight !== '') {
    styles.fontWeight = String(typography.fontWeight) as CSSProperties['fontWeight'];
  }

  if (typography.fontItalic) {
    styles.fontStyle = 'italic';
  }

  if (typography.fontUnderline) {
    styles.textDecoration = 'underline';
  }

  return styles;
};

const cardBackgroundColor = computed(() => appearance.value.cardBackgroundColor || '#ffffff');
const cardBorderColor = computed(() => appearance.value.cardBorderColor || '#e5e7eb');
const buttonBackgroundColor = computed(() => appearance.value.buttonBackgroundColor || '#3b82f6');
const cardTitleStyle = computed(() => resolveTypographyStyle(appearance.value.cardTitleTypography));
const cardDescriptionStyle = computed(() => resolveTypographyStyle(appearance.value.cardDescriptionTypography));
const cardPriceStyle = computed(() => resolveTypographyStyle(appearance.value.cardPriceTypography));
const buttonTypographyStyle = computed(() => resolveTypographyStyle(appearance.value.buttonTypography));

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

  if (config.title_underline) {
    styles.textDecoration = 'underline';
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

const sortButtonLabel = computed(() => (
  sortBy.value === 'price_desc' ? 'Preço ↑' : 'Preço ↓'
));

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

function toggleSortDirection() {
  sortBy.value = sortBy.value === 'price_desc' ? 'price_asc' : 'price_desc';
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
        <div class="view-mode-toggle" role="group" aria-label="Modo de visualização">
          <button
            type="button"
            class="toolbar-button"
            :class="{ active: viewMode === 'grid' }"
            @click="viewMode = 'grid'"
            aria-label="Exibir em grade"
          >
            <svg class="toolbar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z" />
            </svg>
          </button>
          <button
            type="button"
            class="toolbar-button"
            :class="{ active: viewMode === 'list' }"
            @click="viewMode = 'list'"
            aria-label="Exibir em lista"
          >
            <svg class="toolbar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01" />
            </svg>
          </button>
        </div>

        <button
          type="button"
          class="toolbar-button sort-button"
          @click="toggleSortDirection"
        >
          {{ sortButtonLabel }}
        </button>
      </div>

      <!-- Grid -->
      <div class="gift-grid" :class="{ 'gift-grid-list': viewMode === 'list' }">
        <GiftCard
          v-for="gift in sortedGifts"
          :key="gift.id"
          :gift="gift"
          :card-background-color="cardBackgroundColor"
          :card-border-color="cardBorderColor"
          :button-background-color="buttonBackgroundColor"
          :title-style="cardTitleStyle"
          :description-style="cardDescriptionStyle"
          :price-style="cardPriceStyle"
          :button-typography-style="buttonTypographyStyle"
          :view-mode="viewMode"
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
  padding: 0;
}

/* Header */
.catalog-header {
  text-align: center;
  margin-bottom: 2rem;
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
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 2rem;
  padding: 0 0.5rem;
  flex-wrap: wrap;
}

.view-mode-toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.toolbar-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  background-color: white;
  cursor: pointer;
  transition: border-color 0.2s, background-color 0.2s, color 0.2s;
}

.toolbar-button:hover {
  border-color: #9ca3af;
}

.toolbar-button:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.toolbar-button.active {
  background-color: #111827;
  border-color: #111827;
  color: white;
}

.toolbar-icon {
  width: 1rem;
  height: 1rem;
}

.sort-button {
  min-width: 7rem;
}

/* Grid */
.gift-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 2rem;
  padding: 0.5rem;
}

.gift-grid.gift-grid-list {
  grid-template-columns: 1fr;
}

/* Responsive */
@media (max-width: 768px) {
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
    align-items: stretch;
    gap: 0.5rem;
  }

  .sort-button {
    justify-content: center;
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
