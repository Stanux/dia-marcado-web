<script setup>
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    html: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="Política de Privacidade - Dia Marcado">
        <meta
            name="description"
            content="Leia a política de privacidade da plataforma Dia Marcado."
        />
    </Head>

    <div class="min-h-screen bg-[#fcfaf7] text-[#1f2937]">
        <div class="mx-auto max-w-4xl px-6 py-16 sm:py-20">
            <div class="mb-10">
                <a
                    href="/"
                    class="inline-flex items-center gap-2 text-sm font-medium text-[#c45a6f] hover:text-[#b9163a]"
                >
                    <span aria-hidden="true">←</span>
                    Voltar para o início
                </a>
            </div>

            <article class="rounded-[28px] border border-black/5 bg-white p-8 shadow-sm sm:p-12">
                <div class="privacy-content" v-html="props.html"></div>
            </article>
        </div>
    </div>
</template>

<style scoped>
.privacy-content :deep(h1) {
    font-size: 2.25rem;
    line-height: 1.1;
    font-weight: 700;
    margin: 0 0 1.25rem;
    color: #111827;
}

.privacy-content :deep(h2) {
    font-size: 1.25rem;
    line-height: 1.4;
    font-weight: 700;
    margin: 2rem 0 0.75rem;
    color: #111827;
}

.privacy-content :deep(p),
.privacy-content :deep(li) {
    font-size: 1rem;
    line-height: 1.8;
    color: #374151;
}

.privacy-content :deep(p) {
    margin: 0 0 1rem;
}

.privacy-content :deep(ul) {
    margin: 0 0 1rem 1.25rem;
    padding: 0;
}

.privacy-content :deep(li) {
    margin: 0.35rem 0;
}
</style>
