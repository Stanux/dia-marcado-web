<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    token: String,
    inviterName: String,
    inviteeName: String,
    email: String,
});

const form = useForm({
    name: props.inviteeName || '',
    password: '',
    password_confirmation: '',
});

const page = usePage();
const flashError = page.props.flash?.error;

const submit = () => {
    form.post(`/convite/${props.token}/aceitar-novo`);
};

const googleAuthUrl = `/auth/google/redirect?invite=${encodeURIComponent(props.token)}`;
</script>

<template>
    <Head title="Aceitar Convite" />

    <div class="min-h-screen bg-gradient-to-br from-wedding-50 to-wedding-100 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-8">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-wedding-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-wedding-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Você foi convidado(a)!</h1>
                <p class="text-gray-600">
                    <span class="font-semibold">{{ inviterName }}</span> convidou você para participar do planejamento do casamento.
                </p>
            </div>

            <div
                v-if="flashError"
                class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-sm text-red-700"
            >
                {{ flashError }}
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                    <input
                        type="email"
                        id="email"
                        :value="email"
                        disabled
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500"
                    />
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nome Completo</label>
                    <input
                        type="text"
                        id="name"
                        v-model="form.name"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-wedding-500 focus:border-wedding-500"
                        :class="{ 'border-red-500': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                    <input
                        type="password"
                        id="password"
                        v-model="form.password"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-wedding-500 focus:border-wedding-500"
                        :class="{ 'border-red-500': form.errors.password }"
                    />
                    <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmar Senha</label>
                    <input
                        type="password"
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-wedding-500 focus:border-wedding-500"
                    />
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full bg-wedding-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-wedding-700 focus:ring-2 focus:ring-wedding-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition"
                >
                    <span v-if="form.processing">Criando conta...</span>
                    <span v-else>Criar Conta e Aceitar Convite</span>
                </button>

                <div class="flex items-center gap-3">
                    <div class="h-px flex-1 bg-gray-200"></div>
                    <span class="text-xs uppercase text-gray-400">ou</span>
                    <div class="h-px flex-1 bg-gray-200"></div>
                </div>

                <a
                    :href="googleAuthUrl"
                    class="w-full inline-flex items-center justify-center gap-2 border border-gray-300 text-gray-700 py-3 px-4 rounded-lg font-semibold hover:bg-gray-50 transition"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="#EA4335" d="M12 10.2v3.9h5.4c-.2 1.3-1.6 3.9-5.4 3.9-3.2 0-5.8-2.7-5.8-6s2.6-6 5.8-6c1.8 0 3 .8 3.7 1.4l2.5-2.4C16.6 3.4 14.5 2.6 12 2.6 6.8 2.6 2.6 6.8 2.6 12s4.2 9.4 9.4 9.4c5.4 0 9-3.8 9-9.2 0-.6-.1-1.1-.2-1.6H12z" />
                        <path fill="#34A853" d="M2.6 7.9l3.2 2.4c.8-2.4 3-4.2 6.2-4.2 1.8 0 3 .8 3.7 1.4l2.5-2.4C16.6 3.4 14.5 2.6 12 2.6c-3.7 0-6.9 2.1-8.4 5.3z" />
                        <path fill="#FBBC05" d="M12 21.4c2.4 0 4.5-.8 6-2.1l-2.8-2.3c-.8.6-1.9 1-3.2 1-3.7 0-5.1-2.6-5.4-3.9l-3.2 2.5c1.5 3.2 4.7 5.4 8.6 5.4z" />
                        <path fill="#4285F4" d="M21 12.2c0-.6-.1-1.1-.2-1.6H12v3.9h5.4c-.3 1.3-1.1 2.3-2.2 3l2.8 2.3c1.6-1.5 3-3.8 3-7.6z" />
                    </svg>
                    Continuar com Google
                </a>
            </form>
        </div>
    </div>
</template>
