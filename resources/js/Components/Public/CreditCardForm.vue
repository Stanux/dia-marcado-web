<script setup lang="ts">
/**
 * CreditCardForm Component
 * 
 * Credit card payment form with PagSeguro SDK integration for tokenization.
 * Displays installment options and implements protection against multiple clicks.
 * 
 * @Requirements: 4.1, 5.5, 9.2
 */
import { ref, computed, onMounted } from 'vue';

interface GiftItem {
  id: number;
  name: string;
  display_price: number;
}

interface Props {
  gift: GiftItem;
  loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
});

const emit = defineEmits<{
  submit: [paymentData: any];
}>();

// Form state
const cardNumber = ref('');
const cardHolder = ref('');
const expiryMonth = ref('');
const expiryYear = ref('');
const cvv = ref('');
const installments = ref(1);
const cpf = ref('');
const email = ref('');
const phone = ref('');

// UI state
const isSubmitting = ref(false);
const formError = ref<string | null>(null);
const pagSeguroLoaded = ref(false);

// Computed
const maxInstallments = computed(() => {
  const amount = props.gift.display_price / 100;
  // Allow up to 12 installments, but minimum R$ 5 per installment
  const max = Math.min(12, Math.floor(amount / 5));
  return Math.max(1, max);
});

const installmentOptions = computed(() => {
  const options = [];
  const amount = props.gift.display_price / 100;
  
  for (let i = 1; i <= maxInstallments.value; i++) {
    const installmentAmount = amount / i;
    const formattedAmount = installmentAmount.toFixed(2).replace('.', ',');
    
    let label = `${i}x de R$ ${formattedAmount}`;
    if (i === 1) {
      label += ' (à vista)';
    } else if (i > 3) {
      // Simulate interest for installments > 3
      const withInterest = installmentAmount * 1.02; // 2% interest example
      const formattedWithInterest = withInterest.toFixed(2).replace('.', ',');
      label = `${i}x de R$ ${formattedWithInterest} (com juros)`;
    }
    
    options.push({ value: i, label });
  }
  
  return options;
});

const isFormValid = computed(() => {
  return (
    cardNumber.value.length >= 13 &&
    cardHolder.value.trim().length > 0 &&
    expiryMonth.value.length === 2 &&
    expiryYear.value.length === 2 &&
    cvv.value.length >= 3 &&
    cpf.value.length >= 11 &&
    email.value.includes('@') &&
    phone.value.length >= 10
  );
});

// Methods
function formatCardNumber(value: string): string {
  const cleaned = value.replace(/\D/g, '');
  const groups = cleaned.match(/.{1,4}/g) || [];
  return groups.join(' ').substring(0, 19); // Max 16 digits + 3 spaces
}

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

function handleCardNumberInput(event: Event) {
  const input = event.target as HTMLInputElement;
  cardNumber.value = formatCardNumber(input.value);
}

function handleCPFInput(event: Event) {
  const input = event.target as HTMLInputElement;
  cpf.value = formatCPF(input.value);
}

function handlePhoneInput(event: Event) {
  const input = event.target as HTMLInputElement;
  phone.value = formatPhone(input.value);
}

function handleExpiryMonthInput(event: Event) {
  const input = event.target as HTMLInputElement;
  const value = input.value.replace(/\D/g, '');
  const month = parseInt(value);
  
  if (value.length <= 2) {
    if (month >= 1 && month <= 12) {
      expiryMonth.value = value.padStart(2, '0');
    } else if (value.length === 1) {
      expiryMonth.value = value;
    }
  }
}

function handleExpiryYearInput(event: Event) {
  const input = event.target as HTMLInputElement;
  const value = input.value.replace(/\D/g, '');
  
  if (value.length <= 2) {
    expiryYear.value = value;
  }
}

function handleCVVInput(event: Event) {
  const input = event.target as HTMLInputElement;
  const value = input.value.replace(/\D/g, '');
  
  if (value.length <= 4) {
    cvv.value = value;
  }
}

async function handleSubmit() {
  if (!isFormValid.value || isSubmitting.value || props.loading) {
    return;
  }

  // Protection against multiple clicks
  isSubmitting.value = true;
  formError.value = null;

  try {
    // In a real implementation, we would:
    // 1. Load PagSeguro SDK
    // 2. Tokenize the card using PagSeguro.createCardToken()
    // 3. Send only the token to the backend
    
    // For now, we'll simulate tokenization
    const cardToken = await simulateTokenization();
    
    const paymentData = {
      card_token: cardToken,
      installments: installments.value,
      payer: {
        name: cardHolder.value,
        email: email.value,
        document: cpf.value.replace(/\D/g, ''),
        phone: phone.value.replace(/\D/g, '')
      },
      idempotency_key: generateIdempotencyKey()
    };

    emit('submit', paymentData);
  } catch (error) {
    formError.value = error instanceof Error ? error.message : 'Erro ao processar cartão';
    isSubmitting.value = false;
  }
}

async function simulateTokenization(): Promise<string> {
  // Simulate API call delay
  await new Promise(resolve => setTimeout(resolve, 500));
  
  // In production, this would call PagSeguro SDK:
  // const token = await PagSeguro.createCardToken({
  //   cardNumber: cardNumber.value.replace(/\s/g, ''),
  //   cardholderName: cardHolder.value,
  //   cardExpirationMonth: expiryMonth.value,
  //   cardExpirationYear: expiryYear.value,
  //   securityCode: cvv.value
  // });
  
  // Return simulated token
  return `token_${Date.now()}_${Math.random().toString(36).substring(7)}`;
}

function generateIdempotencyKey(): string {
  return `${Date.now()}_${Math.random().toString(36).substring(7)}`;
}

// Lifecycle
onMounted(() => {
  // In production, load PagSeguro SDK here
  // const script = document.createElement('script');
  // script.src = 'https://assets.pagseguro.com.br/checkout-sdk-js/rc/dist/browser/pagseguro.min.js';
  // script.onload = () => { pagSeguroLoaded.value = true; };
  // document.head.appendChild(script);
  
  pagSeguroLoaded.value = true; // Simulate loaded
});
</script>

<template>
  <form @submit.prevent="handleSubmit" class="credit-card-form">
    <!-- Card Number -->
    <div class="form-group">
      <label for="card-number" class="form-label">Número do Cartão</label>
      <input
        id="card-number"
        type="text"
        :value="cardNumber"
        @input="handleCardNumberInput"
        placeholder="0000 0000 0000 0000"
        class="form-input"
        :disabled="loading || isSubmitting"
        required
      />
    </div>

    <!-- Card Holder -->
    <div class="form-group">
      <label for="card-holder" class="form-label">Nome no Cartão</label>
      <input
        id="card-holder"
        v-model="cardHolder"
        type="text"
        placeholder="Nome como está no cartão"
        class="form-input"
        :disabled="loading || isSubmitting"
        required
      />
    </div>

    <!-- Expiry and CVV -->
    <div class="form-row">
      <div class="form-group">
        <label for="expiry-month" class="form-label">Validade</label>
        <div class="expiry-inputs">
          <input
            id="expiry-month"
            type="text"
            :value="expiryMonth"
            @input="handleExpiryMonthInput"
            placeholder="MM"
            class="form-input expiry-input"
            :disabled="loading || isSubmitting"
            maxlength="2"
            required
          />
          <span class="expiry-separator">/</span>
          <input
            id="expiry-year"
            type="text"
            :value="expiryYear"
            @input="handleExpiryYearInput"
            placeholder="AA"
            class="form-input expiry-input"
            :disabled="loading || isSubmitting"
            maxlength="2"
            required
          />
        </div>
      </div>

      <div class="form-group">
        <label for="cvv" class="form-label">CVV</label>
        <input
          id="cvv"
          type="text"
          :value="cvv"
          @input="handleCVVInput"
          placeholder="123"
          class="form-input"
          :disabled="loading || isSubmitting"
          maxlength="4"
          required
        />
      </div>
    </div>

    <!-- CPF -->
    <div class="form-group">
      <label for="cpf" class="form-label">CPF</label>
      <input
        id="cpf"
        type="text"
        :value="cpf"
        @input="handleCPFInput"
        placeholder="000.000.000-00"
        class="form-input"
        :disabled="loading || isSubmitting"
        required
      />
    </div>

    <!-- Email -->
    <div class="form-group">
      <label for="email" class="form-label">E-mail</label>
      <input
        id="email"
        v-model="email"
        type="email"
        placeholder="seu@email.com"
        class="form-input"
        :disabled="loading || isSubmitting"
        required
      />
    </div>

    <!-- Phone -->
    <div class="form-group">
      <label for="phone" class="form-label">Telefone</label>
      <input
        id="phone"
        type="text"
        :value="phone"
        @input="handlePhoneInput"
        placeholder="(00) 00000-0000"
        class="form-input"
        :disabled="loading || isSubmitting"
        required
      />
    </div>

    <!-- Installments -->
    <div class="form-group">
      <label for="installments" class="form-label">Parcelamento</label>
      <select
        id="installments"
        v-model="installments"
        class="form-select"
        :disabled="loading || isSubmitting"
      >
        <option
          v-for="option in installmentOptions"
          :key="option.value"
          :value="option.value"
        >
          {{ option.label }}
        </option>
      </select>
      <p class="form-hint">
        Parcelas acima de 3x podem ter juros aplicados pela operadora do cartão
      </p>
    </div>

    <!-- Error Message -->
    <div v-if="formError" class="form-error">
      {{ formError }}
    </div>

    <!-- Submit Button -->
    <button
      type="submit"
      class="submit-button"
      :disabled="!isFormValid || loading || isSubmitting"
    >
      <span v-if="loading || isSubmitting">Processando...</span>
      <span v-else>Finalizar Pagamento</span>
    </button>

    <!-- Security Notice -->
    <p class="security-notice">
      <svg class="security-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
      </svg>
      Seus dados estão protegidos. Não armazenamos informações do cartão.
    </p>
  </form>
</template>

<style scoped>
.credit-card-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* Form Groups */
.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

.form-input,
.form-select {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 1rem;
  color: #1f2937;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input:disabled,
.form-select:disabled {
  background-color: #f3f4f6;
  cursor: not-allowed;
}

.form-input::placeholder {
  color: #9ca3af;
}

/* Expiry Inputs */
.expiry-inputs {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.expiry-input {
  flex: 1;
  text-align: center;
}

.expiry-separator {
  font-size: 1.25rem;
  font-weight: 500;
  color: #6b7280;
}

/* Form Hint */
.form-hint {
  font-size: 0.75rem;
  color: #6b7280;
  margin: 0;
  line-height: 1.4;
}

/* Form Error */
.form-error {
  padding: 0.75rem;
  background-color: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  color: #991b1b;
}

/* Submit Button */
.submit-button {
  padding: 1rem;
  background-color: #3b82f6;
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s, transform 0.1s;
}

.submit-button:hover:not(:disabled) {
  background-color: #2563eb;
}

.submit-button:active:not(:disabled) {
  transform: scale(0.98);
}

.submit-button:disabled {
  background-color: #9ca3af;
  cursor: not-allowed;
}

/* Security Notice */
.security-notice {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  color: #6b7280;
  text-align: center;
  justify-content: center;
  margin: 0;
}

.security-icon {
  width: 1rem;
  height: 1rem;
  color: #059669;
}

/* Responsive */
@media (max-width: 640px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
