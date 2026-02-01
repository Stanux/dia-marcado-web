<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const weddingId = computed(() => page.props.wedding_id);
const permissions = computed(() => page.props.permissions || []);
</script>

<template>
    <Head title="Dashboard" />

    <div class="min-h-screen bg-wedding-50">
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold text-wedding-900">
                    Dashboard
                </h1>
            </div>
        </header>

        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                <p class="text-wedding-700">
                    Bem-vindo, {{ user?.name }}!
                </p>
                <p v-if="weddingId" class="text-sm text-wedding-500 mt-2">
                    Casamento atual: {{ weddingId }}
                </p>
            </div>
        </main>
    </div>
</template>
