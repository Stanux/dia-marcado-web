<script setup lang="ts">
/**
 * PurchaseModal Component
 * 
 * Modal for purchasing a gift item. Displays gift summary, final price,
 * payment method selection, and handles the payment flow.
 * 
 * @Requirements: 5.1, 5.2, 5.3, 4.7, 6.9
 */
import { ref, computed } from 'vue';
import CreditCardForm from './CreditCardForm.vue';
import PixPayment from './PixPayment.vue';
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
  fee_modality: string;
}

interface Props {
  gift: GiftItem;
  eventId: number;
  config: GiftRegistryConfig;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  close: [];
  success: [];
}>();

// State
const paymentMethod = ref<'credit_card' | 'pix' | null>(null);
const pixData = ref<{ qr_code: string; qr_code_base64: string; transaction_id: string } | null>(null);

// Use gift registry composable for API calls and error handling
const { loading, error, purchaseGift, clearError, retryLastOperation } = useGiftRegistry();

// Computed
const showPaymentForm = computed(() => {
  return paymentMethod.value !== null;
});

// Methods
function formatPrice(priceInCents: number): string {
  return (priceInCents / 100).toFixed(2).replace('.', ',');
}

function selectPaymentMethod(method: 'credit_card' | 'pix') {
  paymentMethod.value = method;
  clearError();
  pixData.value = null;
}

function handleClose() {
  if (!loading.value) {
    emit('close');
  }
}

function handleBackdropClick(event: MouseEvent) {
  if (event.target === event.currentTarget) {
    handleClose();
  }
}

async function handleCreditCardPayment(paymentData: any) {
  try {
    const result = await purchaseGift(props.eventId, props.gift.id, {
      payment_method: 'credit_card',
      ...paymentData
    });

    if (result.success) {
      emit('success');
    }
  } catch (err) {
    // Error is already handled by the composable
    console.error('Payment error:', err);
  }
}

async function handlePixPayment(payerData: any) {
  try {
    const result = await purchaseGift(props.eventId, props.gift.id, {
      payment_method: 'pix',
      ...payerData
    });

    if (result.success && result.qr_code) {
      pixData.value = {
        qr_code: result.qr_code,
        qr_code_base64: result.qr_code_base64 || '',
        transaction_id: result.transaction?.internal_id || ''
      };
    }
  } catch (err) {
    // Error is already handled by the composable
    console.error('PIX error:', err);
  }
}

function handlePixSuccess() {
  emit('success');
}

function handlePixCancel() {
  pixData.value = null;
  paymentMethod.value = null;
}

function resetError() {
  clearError();
}

async function handleRetry() {
  try {
    const result = await retryLastOperation();
    
    if (result.success) {
      if (result.qr_code) {
        // PIX payment
        pixData.value = {
          qr_code: result.qr_code,
          qr_code_base64: result.qr_code_base64 || '',
          transaction_id: result.transaction?.internal_id || ''
        };
      } else {
        // Credit card payment
        emit('success');
      }
    }
  } catch (err) {
    console.error('Retry error:', err);
  }
}
</script>

<template>
  <div class="modal-overlay" @click="handleBackdropClick">
    <div class="modal-content">
      <!-- Close Button -->
      <button 
        @click="handleClose" 
        class="close-button"
        :disabled="loading"
        aria-label="Fechar"
      >
        <svg class="close-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>

      <!-- Modal Header -->
      <div class="modal-header">
        <h3 class="modal-title">Presentear</h3>
      </div>

      <!-- Gift Summary -->
      <div class="gift-summary">
        <div class="summary-image">
          <img 
            v-if="gift.photo_url" 
            :src="gift.photo_url" 
            :alt="gift.name"
          />
          <div v-else class="image-placeholder">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
            </svg>
          </div>
        </div>
        
        <div class="summary-info">
          <h4 class="summary-name">{{ gift.name }}</h4>
          <p class="summary-description">{{ gift.description }}</p>
          <div class="summary-price">
            <span class="price-label">Valor:</span>
            <span class="price-value">R$ {{ formatPrice(gift.display_price) }}</span>
          </div>
        </div>
      </div>

      <!-- Error Message -->
      <div v-if="error" class="error-banner">
        <svg class="error-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div class="error-content">
          <p class="error-message">{{ error }}</p>
          <div class="error-actions">
            <button @click="handleRetry" class="error-retry">Tentar Novamente</button>
            <button @click="resetError" class="error-dismiss">Fechar</button>
          </div>
        </div>
      </div>

      <!-- Payment Method Selection (if not showing PIX QR code) -->
      <div v-if="!pixData" class="payment-section">
        <h4 class="section-title">Escolha a forma de pagamento</h4>
        
        <div class="payment-methods">
          <button
            @click="selectPaymentMethod('credit_card')"
            :class="['payment-method-button', { active: paymentMethod === 'credit_card' }]"
            :disabled="loading"
          >
            <svg class="method-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
            <span>Cartão de Crédito</span>
          </button>
          
          <button
            @click="selectPaymentMethod('pix')"
            :class="['payment-method-button', { active: paymentMethod === 'pix' }]"
            :disabled="loading"
          >
            <svg class="method-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>PIX</span>
          </button>
        </div>

        <!-- Payment Forms -->
        <div v-if="showPaymentForm" class="payment-form">
          <CreditCardForm
            v-if="paymentMethod === 'credit_card'"
            :gift="gift"
            :loading="loading"
            @submit="handleCreditCardPayment"
          />
          
          <PixPayment
            v-if="paymentMethod === 'pix' && !pixData"
            :gift="gift"
            :loading="loading"
            @submit="handlePixPayment"
          />
        </div>
      </div>

      <!-- PIX QR Code Display -->
      <PixPayment
        v-if="pixData"
        :gift="gift"
        :qr-code-data="pixData"
        :transaction-id="pixData.transaction_id"
        @success="handlePixSuccess"
        @cancel="handlePixCancel"
      />

      <!-- Loading Indicator -->
      <div v-if="loading && !pixData" class="loading-overlay">
        <div class="spinner"></div>
        <p class="loading-text">Processando pagamento...</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-content {
  position: relative;
  background-color: white;
  border-radius: 1rem;
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Close Button */
.close-button {
  position: absolute;
  top: 1rem;
  right: 1rem;
  padding: 0.5rem;
  background-color: transparent;
  border: none;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: background-color 0.2s;
  z-index: 10;
}

.close-button:hover:not(:disabled) {
  background-color: #f3f4f6;
}

.close-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.close-icon {
  width: 1.5rem;
  height: 1.5rem;
  color: #6b7280;
}

/* Modal Header */
.modal-header {
  padding: 2rem 2rem 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

/* Gift Summary */
.gift-summary {
  display: flex;
  gap: 1.5rem;
  padding: 2rem;
  border-bottom: 1px solid #e5e7eb;
}

.summary-image {
  flex-shrink: 0;
  width: 120px;
  height: 120px;
  border-radius: 0.75rem;
  overflow: hidden;
  background-color: #f3f4f6;
}

.summary-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
}

.image-placeholder svg {
  width: 3rem;
  height: 3rem;
  color: #9ca3af;
}

.summary-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.summary-name {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.summary-description {
  font-size: 0.875rem;
  color: #6b7280;
  line-height: 1.5;
  margin: 0;
}

.summary-price {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
  margin-top: auto;
}

.price-label {
  font-size: 0.875rem;
  color: #6b7280;
}

.price-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #059669;
}

/* Error Banner */
.error-banner {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem 2rem;
  background-color: #fef2f2;
  border-left: 4px solid #ef4444;
}

.error-icon {
  flex-shrink: 0;
  width: 1.5rem;
  height: 1.5rem;
  color: #ef4444;
}

.error-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.error-message {
  font-size: 0.875rem;
  color: #991b1b;
  margin: 0;
}

.error-actions {
  display: flex;
  gap: 0.5rem;
}

.error-retry,
.error-dismiss {
  padding: 0.25rem 0.75rem;
  border: 1px solid #991b1b;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.error-retry {
  background-color: #991b1b;
  color: white;
}

.error-retry:hover {
  background-color: #7f1d1d;
}

.error-dismiss {
  background-color: transparent;
  color: #991b1b;
}

.error-dismiss:hover {
  background-color: #fee2e2;
}

/* Payment Section */
.payment-section {
  padding: 2rem;
}

.section-title {
  font-size: 1rem;
  font-weight: 600;
  color: #374151;
  margin: 0 0 1rem 0;
}

.payment-methods {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 2rem;
}

.payment-method-button {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 1.5rem 1rem;
  background-color: white;
  border: 2px solid #e5e7eb;
  border-radius: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
}

.payment-method-button:hover:not(:disabled) {
  border-color: #3b82f6;
  background-color: #eff6ff;
}

.payment-method-button.active {
  border-color: #3b82f6;
  background-color: #eff6ff;
}

.payment-method-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.method-icon {
  width: 2rem;
  height: 2rem;
  color: #6b7280;
}

.payment-method-button.active .method-icon {
  color: #3b82f6;
}

.payment-method-button span {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

.payment-form {
  margin-top: 1.5rem;
}

/* Loading Overlay */
.loading-overlay {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 2rem;
  gap: 1rem;
}

.spinner {
  width: 3rem;
  height: 3rem;
  border: 4px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-text {
  font-size: 1rem;
  color: #6b7280;
  margin: 0;
}

/* Responsive */
@media (max-width: 640px) {
  .modal-content {
    max-height: 95vh;
  }

  .modal-header {
    padding: 1.5rem 1.5rem 1rem;
  }

  .modal-title {
    font-size: 1.25rem;
  }

  .gift-summary {
    flex-direction: column;
    padding: 1.5rem;
  }

  .summary-image {
    width: 100%;
    height: 200px;
  }

  .payment-section {
    padding: 1.5rem;
  }

  .payment-methods {
    grid-template-columns: 1fr;
  }
}
</style>
