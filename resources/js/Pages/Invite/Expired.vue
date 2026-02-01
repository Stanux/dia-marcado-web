<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    inviterName: String,
});
</script>

<template>
    <Head title="Convite Expirado" />

    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-8 text-center">
            <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Convite Expirado</h1>
            <p class="text-gray-600 mb-6">
                O convite enviado por <span class="font-semibold">{{ inviterName }}</span> expirou.
                Por favor, peça para que um novo convite seja enviado.
            </p>
            <a
                href="/"
                class="inline-block bg-gray-600 text-white py-2 px-6 rounded-lg font-semibold hover:bg-gray-700 transition"
            >
                Voltar ao Início
            </a>
        </div>
    </div>
</template>
