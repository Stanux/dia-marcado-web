<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    token: String,
    inviterName: String,
    inviteeName: String,
    email: String,
    hasPreviousWedding: Boolean,
});

const page = usePage();
const isLoggedIn = !!page.props.auth?.user;
const loggedInEmail = page.props.auth?.user?.email;
const isCorrectUser = loggedInEmail === props.email;

const acceptForm = useForm({});
const declineForm = useForm({});

const accept = () => {
    acceptForm.post(`/convite/${props.token}/aceitar`);
};

const decline = () => {
    declineForm.post(`/convite/${props.token}/recusar`);
};
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

            <!-- Warning for users with previous wedding -->
            <div v-if="hasPreviousWedding" class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <svg class="w-5 h-5 text-amber-600 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <h3 class="text-sm font-semibold text-amber-800">Atenção</h3>
                        <p class="text-sm text-amber-700 mt-1">
                            Ao aceitar este convite, você será desvinculado(a) do casamento atual e passará a fazer parte do novo casamento.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Not logged in message -->
            <div v-if="!isLoggedIn" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-blue-700">
                    Para aceitar este convite, você precisa fazer login com o e-mail <span class="font-semibold">{{ email }}</span>.
                </p>
                <a
                    href="/admin/login"
                    class="mt-3 inline-block bg-blue-600 text-white py-2 px-4 rounded-lg text-sm font-semibold hover:bg-blue-700 transition"
                >
                    Fazer Login
                </a>
            </div>

            <!-- Wrong user logged in -->
            <div v-else-if="!isCorrectUser" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-red-700">
                    Você está logado como <span class="font-semibold">{{ loggedInEmail }}</span>, mas este convite foi enviado para <span class="font-semibold">{{ email }}</span>.
                </p>
                <p class="text-sm text-red-700 mt-2">
                    Por favor, faça logout e entre com a conta correta.
                </p>
            </div>

            <!-- Action buttons for correct user -->
            <div v-else class="space-y-4">
                <button
                    @click="accept"
                    :disabled="acceptForm.processing"
                    class="w-full bg-wedding-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-wedding-700 focus:ring-2 focus:ring-wedding-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition"
                >
                    <span v-if="acceptForm.processing">Aceitando...</span>
                    <span v-else>Aceitar Convite</span>
                </button>

                <button
                    @click="decline"
                    :disabled="declineForm.processing"
                    class="w-full bg-gray-100 text-gray-700 py-3 px-4 rounded-lg font-semibold hover:bg-gray-200 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition"
                >
                    <span v-if="declineForm.processing">Recusando...</span>
                    <span v-else>Recusar Convite</span>
                </button>
            </div>
        </div>
    </div>
</template>
