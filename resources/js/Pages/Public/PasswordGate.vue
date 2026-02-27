<script setup>
/**
 * PasswordGate Page
 * 
 * Password protection gate for private wedding sites.
 * Shows elegant password form with error and rate limit handling.
 * 
 * @Requirements: 6.2, 6.5
 */
import { ref, computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';

const props = defineProps({
    siteTitle: {
        type: String,
        default: 'Site de Casamento',
    },
    slug: {
        type: String,
        required: true,
    },
    error: {
        type: String,
        default: null,
    },
    rateLimited: {
        type: Boolean,
        default: false,
    },
    retryAfter: {
        type: Number,
        default: 0,
    },
});

// Form state
const form = useForm({
    password: '',
});

// Submit handler
const submit = () => {
    form.post(`/${props.slug}/auth`, {
        preserveScroll: true,
        onError: () => {
            form.password = '';
        },
    });
};

// Rate limit countdown
const remainingMinutes = computed(() => {
    return Math.ceil(props.retryAfter / 60);
});
</script>

<template>
    <div class="password-gate">
        <Head>
            <title>{{ siteTitle }} - Acesso Protegido</title>
        </Head>

        <div class="password-gate-container">
            <!-- Lock Icon -->
            <div class="lock-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>

            <!-- Title -->
            <h1 class="title">{{ siteTitle }}</h1>
            <p class="subtitle">
                Este site é protegido por senha. Digite a senha para acessar.
            </p>

            <!-- Rate Limit Message -->
            <div v-if="rateLimited" class="rate-limit-message">
                <svg class="rate-limit-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <p class="rate-limit-title">Muitas tentativas</p>
                    <p class="rate-limit-text">
                        Por favor, aguarde {{ remainingMinutes }} minuto{{ remainingMinutes > 1 ? 's' : '' }} antes de tentar novamente.
                    </p>
                </div>
            </div>

            <!-- Password Form -->
            <form v-else @submit.prevent="submit" class="password-form">
                <div class="form-group">
                    <label for="password" class="form-label">Senha</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        class="form-input"
                        :class="{ 'form-input-error': form.errors.password || error }"
                        placeholder="Digite a senha"
                        required
                        autofocus
                        :disabled="form.processing"
                    />
                    <p v-if="form.errors.password" class="form-error">
                        {{ form.errors.password }}
                    </p>
                    <p v-else-if="error" class="form-error">
                        {{ error }}
                    </p>
                </div>

                <button
                    type="submit"
                    class="submit-button"
                    :disabled="form.processing"
                >
                    <svg v-if="form.processing" class="spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ form.processing ? 'Verificando...' : 'Acessar' }}
                </button>
            </form>

            <!-- Decorative Element -->
            <div class="decorative-line"></div>
        </div>
    </div>
</template>

<style scoped>
.password-gate {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ed 100%);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.password-gate-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    padding: 48px 40px;
    max-width: 420px;
    width: 100%;
    text-align: center;
}

.lock-icon {
    width: 72px;
    height: 72px;
    margin: 0 auto 24px;
    background: linear-gradient(135deg, #f0e6df 0%, #F2E6EA 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.lock-icon svg {
    width: 36px;
    height: 36px;
    color: #c45a6f;
}

.title {
    font-size: 1.75rem;
    color: #333;
    margin-bottom: 8px;
    font-weight: 600;
}

.subtitle {
    color: #666;
    margin-bottom: 32px;
    font-size: 0.95rem;
    line-height: 1.5;
}

.rate-limit-message {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
    background: #fef3c7;
    border: 1px solid #fcd34d;
    border-radius: 8px;
    text-align: left;
}

.rate-limit-icon {
    width: 24px;
    height: 24px;
    color: #d97706;
    flex-shrink: 0;
}

.rate-limit-title {
    font-weight: 600;
    color: #92400e;
    margin-bottom: 4px;
}

.rate-limit-text {
    font-size: 0.875rem;
    color: #a16207;
}

.password-form {
    text-align: left;
}

.form-group {
    margin-bottom: 24px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    color: #555;
    font-weight: 500;
    font-size: 0.9rem;
}

.form-input {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: #f97373;
    box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.1);
}

.form-input-error {
    border-color: #ef4444;
}

.form-input-error:focus {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.form-error {
    color: #dc2626;
    font-size: 0.875rem;
    margin-top: 8px;
}

.submit-button {
    width: 100%;
    padding: 14px 24px;
    background: linear-gradient(135deg, #f97373 0%, #C27A92 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.submit-button:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(212, 165, 116, 0.4);
}

.submit-button:active:not(:disabled) {
    transform: translateY(0);
}

.submit-button:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.spinner {
    width: 20px;
    height: 20px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.decorative-line {
    margin-top: 32px;
    height: 4px;
    width: 60px;
    background: linear-gradient(90deg, #f97373, #F2E6EA);
    border-radius: 2px;
    margin-left: auto;
    margin-right: auto;
}

/* Responsive adjustments */
@media (max-width: 480px) {
    .password-gate-container {
        padding: 32px 24px;
    }
    
    .title {
        font-size: 1.5rem;
    }
}
</style>
