<script setup lang="ts">
/**
 * GiftCard Component
 * 
 * Displays an individual gift item card with photo, description, price,
 * and purchase button. Handles sold out state appropriately.
 * 
 * @Requirements: 2.2, 2.3
 */
import { computed } from 'vue';

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

interface Props {
  gift: GiftItem;
  isPreview?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  isPreview: false
});

const emit = defineEmits<{
  purchase: [gift: GiftItem];
}>();

// Computed
const isSoldOut = computed(() => {
  return props.gift.is_sold_out || props.gift.quantity_available === 0;
});

const buttonText = computed(() => {
  if (isSoldOut.value) {
    return 'Esgotado';
  }
  return 'Presentear';
});

const buttonDisabled = computed(() => {
  return isSoldOut.value || props.isPreview;
});

// Methods
function formatPrice(priceInCents: number): string {
  return (priceInCents / 100).toFixed(2).replace('.', ',');
}

function handlePurchaseClick() {
  if (!buttonDisabled.value) {
    emit('purchase', props.gift);
  }
}
</script>

<template>
  <div class="gift-card">
    <!-- Gift Image -->
    <div class="gift-image-container">
      <img 
        v-if="gift.photo_url" 
        :src="gift.photo_url" 
        :alt="gift.name"
        class="gift-image"
      />
      <div v-else class="gift-image-placeholder">
        <svg class="placeholder-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
        </svg>
      </div>
      
      <!-- Sold Out Badge -->
      <div v-if="isSoldOut" class="sold-out-badge">
        Esgotado
      </div>
    </div>

    <!-- Gift Info -->
    <div class="gift-info">
      <h3 class="gift-name">{{ gift.name }}</h3>
      
      <p class="gift-description">{{ gift.description }}</p>
      
      <div class="gift-footer">
        <div class="gift-price">
          R$ {{ formatPrice(gift.display_price) }}
        </div>
        
        <button
          @click="handlePurchaseClick"
          :disabled="buttonDisabled"
          :class="[
            'purchase-button',
            { 'sold-out': isSoldOut },
            { 'preview': isPreview }
          ]"
        >
          {{ buttonText }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.gift-card {
  display: flex;
  flex-direction: column;
  background-color: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
}

.gift-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

/* Image Container */
.gift-image-container {
  position: relative;
  width: 100%;
  aspect-ratio: 1 / 1;
  overflow: hidden;
  background-color: #f3f4f6;
}

.gift-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.gift-image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
}

.placeholder-icon {
  width: 4rem;
  height: 4rem;
  color: #9ca3af;
}

/* Sold Out Badge */
.sold-out-badge {
  position: absolute;
  top: 1rem;
  right: 1rem;
  padding: 0.5rem 1rem;
  background-color: rgba(239, 68, 68, 0.95);
  color: white;
  font-size: 0.875rem;
  font-weight: 600;
  border-radius: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* Gift Info */
.gift-info {
  display: flex;
  flex-direction: column;
  padding: 1.25rem;
  flex: 1;
}

.gift-name {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 0.5rem 0;
  line-height: 1.4;
}

.gift-description {
  font-size: 0.875rem;
  color: #6b7280;
  line-height: 1.5;
  margin: 0 0 1rem 0;
  flex: 1;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Footer */
.gift-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-top: auto;
}

.gift-price {
  font-size: 1.5rem;
  font-weight: 700;
  color: #059669;
}

/* Purchase Button */
.purchase-button {
  padding: 0.75rem 1.5rem;
  background-color: #3b82f6;
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s, transform 0.1s;
  white-space: nowrap;
}

.purchase-button:hover:not(:disabled) {
  background-color: #2563eb;
  transform: scale(1.02);
}

.purchase-button:active:not(:disabled) {
  transform: scale(0.98);
}

.purchase-button:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.purchase-button.sold-out {
  background-color: #9ca3af;
}

.purchase-button.sold-out:hover {
  background-color: #9ca3af;
  transform: none;
}

.purchase-button.preview {
  background-color: #6b7280;
}

.purchase-button.preview:hover {
  background-color: #6b7280;
  transform: none;
}

/* Responsive */
@media (max-width: 768px) {
  .gift-info {
    padding: 1rem;
  }

  .gift-name {
    font-size: 1rem;
  }

  .gift-description {
    font-size: 0.8125rem;
  }

  .gift-price {
    font-size: 1.25rem;
  }

  .purchase-button {
    padding: 0.625rem 1.25rem;
    font-size: 0.8125rem;
  }
}

@media (max-width: 480px) {
  .gift-footer {
    flex-direction: column;
    align-items: stretch;
    gap: 0.75rem;
  }

  .purchase-button {
    width: 100%;
  }
}
</style>
