<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    message: String,
});
</script>

<template>
    <Head title="Convite Inválido" />

    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-8 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Convite Inválido</h1>
            <p class="text-gray-600 mb-6">{{ message }}</p>
            <a
                href="/"
                class="inline-block bg-gray-600 text-white py-2 px-6 rounded-lg font-semibold hover:bg-gray-700 transition"
            >
                Voltar ao Início
            </a>
        </div>
    </div>
</template>
