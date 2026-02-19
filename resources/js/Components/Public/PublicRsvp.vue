<script setup>
/**
 * PublicRsvp Component
 * 
 * Renders the RSVP section with real submission.
 */
import { computed, ref, reactive, watch } from 'vue';
import axios from 'axios';

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
    inviteTokenState: {
        type: String,
        default: null,
    },
});

const style = computed(() => props.content.style || {});
const events = computed(() => (props.wedding?.guest_events || []).filter((event) => event.is_active));
const selectedEventId = ref('');
const token = ref('');
const isTokenLimitReached = computed(() => props.inviteTokenState === 'limit_reached');

const isSubmitting = ref(false);
const successMessage = ref('');
const errorMessage = ref('');
const tokenLimitMessage = 'Este token já atingiu o limite de uso. Solicite um novo link para alterar sua confirmação.';

const form = reactive({
    name: '',
    email: '',
    phone: '',
    status: 'confirmed',
    responses: {},
});

const selectedEvent = computed(() => events.value.find((event) => event.id === selectedEventId.value));
const questions = computed(() => selectedEvent.value?.questions || []);

const getQuestionKey = (question, index) => {
    return question.key || question.id || `q_${index}`;
};

const submit = async () => {
    successMessage.value = '';
    errorMessage.value = '';

    if (!form.name.trim()) {
        errorMessage.value = 'Informe seu nome.';
        return;
    }

    if (!selectedEventId.value) {
        errorMessage.value = 'Selecione um evento.';
        return;
    }

    if (isTokenLimitReached.value) {
        errorMessage.value = tokenLimitMessage;
        return;
    }

    isSubmitting.value = true;

    try {
        const payload = {
            token: token.value || null,
            event_id: selectedEventId.value,
            status: form.status,
            responses: form.responses,
            guest: {
                name: form.name,
                email: form.email || null,
                phone: form.phone || null,
            },
        };

        await axios.post('/api/public/rsvp', payload);
        successMessage.value = 'RSVP enviado com sucesso!';
    } catch (err) {
        errorMessage.value = err?.response?.data?.message || 'Erro ao enviar RSVP.';
    } finally {
        isSubmitting.value = false;
    }
};

watch(
    () => events.value,
    (next) => {
        if (!selectedEventId.value && next.length > 0) {
            selectedEventId.value = next[0].id;
        }
    },
    { immediate: true }
);

if (typeof window !== 'undefined') {
    const params = new URLSearchParams(window.location.search);
    token.value = params.get('token') || '';
}

if (isTokenLimitReached.value) {
    errorMessage.value = tokenLimitMessage;
}
</script>

<template>
    <section
        class="py-20 px-4"
        :style="{ backgroundColor: style.backgroundColor || '#f8f6f4' }"
        id="rsvp"
    >
        <div class="max-w-xl mx-auto">
            <div class="text-center mb-10">
                <h2
                    class="text-3xl md:text-4xl font-bold mb-4 break-words [overflow-wrap:anywhere]"
                    :style="{ color: theme.primaryColor, fontFamily: theme.fontFamily }"
                >
                    {{ content.title || 'Confirme sua Presença' }}
                </h2>
                <p class="text-gray-600 text-lg break-words [overflow-wrap:anywhere]">
                    {{ content.description || 'Por favor, confirme sua presença para que possamos preparar tudo com carinho.' }}
                </p>
            </div>

            <div class="bg-white rounded-2xl shadow-xl p-8 md:p-10">
                <form class="space-y-6" @submit.prevent="submit">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nome completo</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg"
                            placeholder="Seu nome"
                        />
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input
                                v-model="form.email"
                                type="email"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg"
                                placeholder="seu@email.com"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                            <input
                                v-model="form.phone"
                                type="tel"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg"
                                placeholder="(00) 00000-0000"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Evento</label>
                        <select
                            v-model="selectedEventId"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg"
                        >
                            <option value="" disabled>Selecione um evento</option>
                            <option v-for="event in events" :key="event.id" :value="event.id">
                                {{ event.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirmação</label>
                        <select
                            v-model="form.status"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg"
                        >
                            <option value="confirmed">Confirmo presença</option>
                            <option value="declined">Não poderei comparecer</option>
                            <option value="maybe">Talvez</option>
                        </select>
                    </div>

                    <div v-if="questions.length" class="space-y-4">
                        <div v-for="(question, index) in questions" :key="index">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                {{ question.label || question.title || 'Pergunta' }}
                            </label>

                            <input
                                v-if="question.type === 'text'"
                                v-model="form.responses[getQuestionKey(question, index)]"
                                type="text"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg"
                            />

                            <textarea
                                v-else-if="question.type === 'textarea'"
                                v-model="form.responses[getQuestionKey(question, index)]"
                                rows="4"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg"
                            ></textarea>

                            <select
                                v-else-if="question.type === 'select'"
                                v-model="form.responses[getQuestionKey(question, index)]"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg"
                            >
                                <option value="" disabled>Selecione uma opção</option>
                                <option v-for="opt in (question.options || [])" :key="opt" :value="opt">
                                    {{ opt }}
                                </option>
                            </select>

                            <input
                                v-else-if="question.type === 'number'"
                                v-model.number="form.responses[getQuestionKey(question, index)]"
                                type="number"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg"
                            />

                            <input
                                v-else
                                v-model="form.responses[getQuestionKey(question, index)]"
                                type="text"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg"
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="isSubmitting || isTokenLimitReached"
                        class="w-full px-6 py-4 text-white font-semibold rounded-lg"
                        :style="{ backgroundColor: theme.primaryColor, opacity: (isSubmitting || isTokenLimitReached) ? 0.7 : 1 }"
                    >
                        {{ isTokenLimitReached ? 'Token sem novos usos' : (isSubmitting ? 'Enviando...' : 'Confirmar Presença') }}
                    </button>
                </form>

                <div v-if="successMessage" class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
                    {{ successMessage }}
                </div>
                <div v-if="errorMessage" class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
                    {{ errorMessage }}
                </div>
            </div>
        </div>
    </section>
</template>
