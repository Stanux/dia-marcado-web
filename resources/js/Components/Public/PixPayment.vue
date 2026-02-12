<script setup lang="ts">
/**
 * PixPayment Component
 * 
 * Handles PIX payment flow. Can display either:
 * 1. Form to collect payer data (before QR code generation)
 * 2. QR Code display with polling for payment confirmation
 * 
 * @Requirements: 12.3, 12.4
 */
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import QRCode from 'qrcode';

interface GiftItem {
  id: number;
  name: string;
  display_price: number;
}

interface QRCodeData {
  qr_code: string;
  qr_code_base64: string;
  transaction_id: string;
}

interface Props {
  gift: GiftItem;
  loading?: boolean;
  qrCodeData?: QRCodeData | null;
  transactionId?: string;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  qrCodeData: null
});

const emit = defineEmits<{
  submit: [payerData: any];
  success: [];
  cancel: [];
}>();

const page = usePage();

// Form state (for collecting payer data)
const name = ref('');
const email = ref('');
const cpf = ref('');
const phone = ref('');

// Polling state
const pollingInterval = ref<number | null>(null);
const paymentStatus = ref<'pending' | 'confirmed' | 'failed'>('pending');
const elapsedTime = ref(0);
const timeInterval = ref<number | null>(null);

// Computed
const isFormValid = computed(() => {
  return (
    name.value.trim().length > 0 &&
    email.value.includes('@') &&
    cpf.value.length >= 11 &&
    phone.value.length >= 10
  );
});

const showQRCode = computed(() => {
  return props.qrCodeData !== null;
});

const isLocal = computed(() => page.props?.appEnv === 'local');

const generatedQrCode = ref<string | null>(null);

const qrCodeImageSrc = computed(() => {
  const raw = props.qrCodeData?.qr_code_base64 ?? '';
  const cleaned = raw.replace(/\s+/g, '');
  if (!cleaned) {
    return generatedQrCode.value;
  }
  if (!/^[A-Za-z0-9+/=]+$/.test(cleaned)) {
    return generatedQrCode.value;
  }
  return `data:image/png;base64,${cleaned}`;
});

const formattedTime = computed(() => {
  const minutes = Math.floor(elapsedTime.value / 60);
  const seconds = elapsedTime.value % 60;
  return `${minutes}:${seconds.toString().padStart(2, '0')}`;
});

// Methods
function formatCPF(value: string): string {
  const cleaned = value.replace(/\D/g, '');
  if (cleaned.length <= 11) {
    return cleaned
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
  }
  return cleaned.substring(0, 11);
}

function formatPhone(value: string): string {
  const cleaned = value.replace(/\D/g, '');
  if (cleaned.length <= 11) {
    return cleaned
      .replace(/(\d{2})(\d)/, '($1) $2')
      .replace(/(\d{5})(\d)/, '$1-$2');
  }
  return cleaned.substring(0, 11);
}

function handleCPFInput(event: Event) {
  const input = event.target as HTMLInputElement;
  cpf.value = formatCPF(input.value);
}

function handlePhoneInput(event: Event) {
  const input = event.target as HTMLInputElement;
  phone.value = formatPhone(input.value);
}

function handleSubmit() {
  if (!isFormValid.value || props.loading) {
    return;
  }

  const payerData = {
    payer: {
      name: name.value,
      email: email.value,
      document: cpf.value.replace(/\D/g, ''),
      phone: phone.value.replace(/\D/g, '')
    },
    idempotency_key: generateIdempotencyKey()
  };

  emit('submit', payerData);
}

function generateIdempotencyKey(): string {
  return `${Date.now()}_${Math.random().toString(36).substring(7)}`;
}

async function copyQRCode() {
  if (props.qrCodeData?.qr_code) {
    try {
      await navigator.clipboard.writeText(props.qrCodeData.qr_code);
      // Could show a toast notification here
      alert('Código PIX copiado!');
    } catch (err) {
      console.error('Failed to copy:', err);
    }
  }
}

async function checkPaymentStatus() {
  if (!props.transactionId) return;

  try {
    // In production, this would call an API endpoint to check status
    // const response = await fetch(`/api/transactions/${props.transactionId}/status`);
    // const data = await response.json();
    
    // In real implementation, the webhook would update the transaction status
    // and we'd poll to check if it's been confirmed
  } catch (error) {
    console.error('Error checking payment status:', error);
  }
}

function startPolling() {
  // Poll every 3 seconds
  pollingInterval.value = window.setInterval(() => {
    checkPaymentStatus();
  }, 3000);

  // Track elapsed time
  timeInterval.value = window.setInterval(() => {
    elapsedTime.value++;
  }, 1000);
}

function stopPolling() {
  if (pollingInterval.value) {
    clearInterval(pollingInterval.value);
    pollingInterval.value = null;
  }
  if (timeInterval.value) {
    clearInterval(timeInterval.value);
    timeInterval.value = null;
  }
}

function handleCancel() {
  stopPolling();
  emit('cancel');
}

async function handleSimulatePayment() {
  if (!props.transactionId || !isLocal.value) {
    return;
  }

  try {
    const response = await fetch(`/api/dev/transactions/${props.transactionId}/confirm`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
      },
    });

    if (!response.ok) {
      const data = await response.json().catch(() => ({}));
      throw new Error(data.message || 'Falha ao simular pagamento');
    }

    paymentStatus.value = 'confirmed';
    stopPolling();
    emit('success');
  } catch (error) {
    console.error('Simulate payment failed:', error);
  }
}

// Lifecycle
onMounted(() => {
  if (showQRCode.value) {
    startPolling();
  }
});

onUnmounted(() => {
  stopPolling();
});

watch(
  () => props.qrCodeData?.qr_code,
  async (code) => {
    generatedQrCode.value = null;
    if (!code) {
      return;
    }
    try {
      generatedQrCode.value = await QRCode.toDataURL(code, {
        width: 260,
        margin: 1,
      });
    } catch (error) {
      console.error('Failed to generate QR code image:', error);
    }
  },
  { immediate: true }
);
</script>

<template>
  <div class="pix-payment">
    <!-- Payer Data Form (before QR code) -->
    <form v-if="!showQRCode" @submit.prevent="handleSubmit" class="payer-form">
      <div class="form-group">
        <label for="pix-name" class="form-label">Nome Completo</label>
        <input
          id="pix-name"
          v-model="name"
          type="text"
          placeholder="Seu nome completo"
          class="form-input"
          :disabled="loading"
          required
        />
      </div>

      <div class="form-group">
        <label for="pix-email" class="form-label">E-mail</label>
        <input
          id="pix-email"
          v-model="email"
          type="email"
          placeholder="seu@email.com"
          class="form-input"
          :disabled="loading"
          required
        />
      </div>

      <div class="form-group">
        <label for="pix-cpf" class="form-label">CPF</label>
        <input
          id="pix-cpf"
          type="text"
          :value="cpf"
          @input="handleCPFInput"
          placeholder="000.000.000-00"
          class="form-input"
          :disabled="loading"
          required
        />
      </div>

      <div class="form-group">
        <label for="pix-phone" class="form-label">Telefone</label>
        <input
          id="pix-phone"
          type="text"
          :value="phone"
          @input="handlePhoneInput"
          placeholder="(00) 00000-0000"
          class="form-input"
          :disabled="loading"
          required
        />
      </div>

      <button
        type="submit"
        class="submit-button"
        :disabled="!isFormValid || loading"
      >
        <span v-if="loading">Gerando QR Code...</span>
        <span v-else>Gerar QR Code PIX</span>
      </button>
    </form>

    <!-- QR Code Display -->
    <div v-else class="qr-code-container">
      <!-- Payment Confirmed -->
      <div v-if="paymentStatus === 'confirmed'" class="payment-confirmed">
        <div class="success-icon">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h3 class="success-title">Pagamento Confirmado!</h3>
        <p class="success-message">
          Seu presente foi registrado com sucesso. Obrigado!
        </p>
      </div>

      <!-- Waiting for Payment -->
      <div v-else class="qr-code-content">
        <h3 class="qr-title">Escaneie o QR Code para pagar</h3>
        
        <div class="qr-code-image">
          <img 
            v-if="qrCodeImageSrc"
            :src="qrCodeImageSrc"
            alt="QR Code PIX"
          />
          <div v-else class="qr-placeholder">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
            </svg>
          </div>
        </div>

        <div class="qr-instructions">
          <p class="instruction-step">
            <strong>1.</strong> Abra o app do seu banco
          </p>
          <p class="instruction-step">
            <strong>2.</strong> Escolha pagar com PIX
          </p>
          <p class="instruction-step">
            <strong>3.</strong> Escaneie o QR Code acima
          </p>
        </div>

        <button @click="copyQRCode" class="copy-button">
          <svg class="copy-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
          </svg>
          Copiar código PIX
        </button>

        <button v-if="isLocal" @click="handleSimulatePayment" class="simulate-button">
          Simular pagamento confirmado
        </button>

        <div class="waiting-status">
          <div class="status-spinner"></div>
          <p class="status-text">
            Aguardando pagamento... {{ formattedTime }}
          </p>
        </div>

        <button @click="handleCancel" class="cancel-button">
          Cancelar
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pix-payment {
  width: 100%;
}

/* Payer Form */
.payer-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

.form-input {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 1rem;
  color: #1f2937;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input:disabled {
  background-color: #f3f4f6;
  cursor: not-allowed;
}

.form-input::placeholder {
  color: #9ca3af;
}

.submit-button {
  padding: 1rem;
  background-color: #00b8d4;
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s, transform 0.1s;
}

.submit-button:hover:not(:disabled) {
  background-color: #0097a7;
}

.submit-button:active:not(:disabled) {
  transform: scale(0.98);
}

.submit-button:disabled {
  background-color: #9ca3af;
  cursor: not-allowed;
}

/* QR Code Container */
.qr-code-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1rem 0;
}

/* Payment Confirmed */
.payment-confirmed {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 2rem;
  text-align: center;
}

.success-icon {
  width: 4rem;
  height: 4rem;
  background-color: #d1fae5;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.5rem;
}

.success-icon svg {
  width: 2.5rem;
  height: 2.5rem;
  color: #059669;
}

.success-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #059669;
  margin: 0 0 0.5rem 0;
}

.success-message {
  font-size: 1rem;
  color: #6b7280;
  margin: 0;
}

/* QR Code Content */
.qr-code-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  max-width: 400px;
}

.qr-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  text-align: center;
  margin: 0 0 1.5rem 0;
}

.qr-code-image {
  width: 250px;
  height: 250px;
  background-color: white;
  border: 2px solid #e5e7eb;
  border-radius: 0.75rem;
  padding: 1rem;
  margin-bottom: 1.5rem;
}

.qr-code-image img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.qr-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f3f4f6;
  border-radius: 0.5rem;
}

.qr-placeholder svg {
  width: 4rem;
  height: 4rem;
  color: #9ca3af;
}

/* Instructions */
.qr-instructions {
  width: 100%;
  background-color: #f9fafb;
  border-radius: 0.5rem;
  padding: 1rem;
  margin-bottom: 1rem;
}

.instruction-step {
  font-size: 0.875rem;
  color: #374151;
  margin: 0.5rem 0;
  line-height: 1.5;
}

.instruction-step strong {
  color: #1f2937;
}

/* Copy Button */
.copy-button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background-color: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
  margin-bottom: 1.5rem;
}

.copy-button:hover {
  background-color: #e5e7eb;
}

.copy-icon {
  width: 1.25rem;
  height: 1.25rem;
}

/* Simulate Button (dev only) */
.simulate-button {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.75rem 1.5rem;
  background-color: #111827;
  color: #ffffff;
  border: none;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
  margin-bottom: 1.5rem;
}

.simulate-button:hover {
  background-color: #0f172a;
}

/* Waiting Status */
.waiting-status {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 1.5rem;
  margin-bottom: 1rem;
}

.status-spinner {
  width: 2rem;
  height: 2rem;
  border: 3px solid #e5e7eb;
  border-top-color: #00b8d4;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.status-text {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0;
}

/* Cancel Button */
.cancel-button {
  padding: 0.75rem 1.5rem;
  background-color: transparent;
  color: #6b7280;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s, border-color 0.2s;
}

.cancel-button:hover {
  background-color: #f3f4f6;
  border-color: #9ca3af;
}

/* Responsive */
@media (max-width: 640px) {
  .qr-code-image {
    width: 200px;
    height: 200px;
  }
}
</style>
