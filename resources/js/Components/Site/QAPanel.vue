<script setup>
/**
 * QAPanel Component
 * 
 * Displays QA checklist results with visual status indicators.
 * Shows pass/fail/warning status for each check with links to problem items.
 * 
 * @Requirements: 17.5, 17.6
 */
import { computed } from 'vue';

const props = defineProps({
    checks: {
        type: Array,
        default: () => [],
    },
    showAll: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['navigate-to-section']);

// Computed
const failedChecks = computed(() => {
    return props.checks.filter(check => check.status === 'fail');
});

const warningChecks = computed(() => {
    return props.checks.filter(check => check.status === 'warning');
});

const passedChecks = computed(() => {
    return props.checks.filter(check => check.status === 'pass');
});

const displayedChecks = computed(() => {
    if (props.showAll) {
        return props.checks;
    }
    // Show failed first, then warnings, then passed
    return [...failedChecks.value, ...warningChecks.value, ...passedChecks.value];
});

const CHECK_PRESENTATION = {
    images_alt_text: {
        title: 'Descrição das imagens',
        pass: 'As imagens importantes já têm descrição para acessibilidade.',
        fail: 'Algumas imagens ainda não têm descrição. Adicione um texto curto para melhorar acessibilidade e SEO.',
        warning: 'Revise as descrições das imagens para melhorar a leitura por tecnologias assistivas.',
    },
    valid_links: {
        title: 'Links do site',
        pass: 'Todos os links estão válidos e funcionando.',
        fail: 'Encontramos link(s) inválido(s) ou incompleto(s). Revise botões e menus.',
        warning: 'Existem links que precisam de revisão.',
    },
    required_fields: {
        title: 'Informações obrigatórias',
        pass: 'Os dados essenciais para publicação estão preenchidos.',
        fail: 'Faltam informações obrigatórias para publicar com segurança.',
        warning: 'Revise os campos obrigatórios antes de publicar.',
    },
    wcag_contrast: {
        title: 'Legibilidade das cores',
        pass: 'As combinações de cor atendem ao contraste recomendado.',
        fail: 'As cores estão com contraste baixo para leitura.',
        warning: 'Algumas combinações de cor dificultam a leitura. Ajuste cor de fundo e texto.',
    },
    resource_size: {
        title: 'Peso do site',
        pass: 'O tamanho do site está dentro do recomendado.',
        fail: 'O tamanho do site está acima do limite.',
        warning: 'O site está mais pesado que o recomendado. Otimize imagens e vídeos para carregar mais rápido.',
    },
};

const getDisplayName = (check) => {
    const presentation = CHECK_PRESENTATION[check.name];
    return presentation?.title || check.name;
};

const getDisplayMessage = (check) => {
    const presentation = CHECK_PRESENTATION[check.name];
    const friendly = presentation?.[check.status];

    if (check.name === 'resource_size' && check.status === 'warning' && check.message) {
        return `${friendly}\n\nDetalhes técnicos:\n${check.message}`;
    }

    return friendly || check.message || '';
};

// Methods
const getStatusIcon = (status) => {
    switch (status) {
        case 'pass':
            return 'check';
        case 'fail':
            return 'x';
        case 'warning':
            return 'exclamation';
        default:
            return 'question';
    }
};

const getStatusColor = (status) => {
    switch (status) {
        case 'pass':
            return 'text-green-500 bg-green-100';
        case 'fail':
            return 'text-red-500 bg-red-100';
        case 'warning':
            return 'text-amber-500 bg-amber-100';
        default:
            return 'text-gray-500 bg-gray-100';
    }
};

const navigateToSection = (section) => {
    if (section) {
        emit('navigate-to-section', section);
    }
};

const getSectionLabel = (section) => {
    const labels = {
        header: 'Cabeçalho',
        hero: 'Destaque',
        saveTheDate: 'Save the Date',
        giftRegistry: 'Lista de Presentes',
        rsvp: 'RSVP',
        photoGallery: 'Galeria de Fotos',
        footer: 'Rodapé',
        meta: 'Meta Tags',
        theme: 'Tema',
        general: 'Geral',
    };
    return labels[section] || section;
};
</script>

<template>
    <div class="qa-panel">
        <!-- Summary Stats -->
        <div class="flex items-center justify-between mb-4 text-sm">
            <div class="flex items-center space-x-4">
                <span class="flex items-center text-green-600">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ passedChecks.length }} OK
                </span>
                <span v-if="warningChecks.length > 0" class="flex items-center text-amber-600">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    {{ warningChecks.length }} Avisos
                </span>
                <span v-if="failedChecks.length > 0" class="flex items-center text-red-600">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    {{ failedChecks.length }} Erros
                </span>
            </div>
        </div>

        <!-- Checks List -->
        <div class="space-y-2 max-h-64 overflow-y-auto">
            <div
                v-for="(check, index) in displayedChecks"
                :key="index"
                class="flex items-start p-3 rounded-lg border transition-colors"
                :class="{
                    'border-red-200 bg-red-50': check.status === 'fail',
                    'border-amber-200 bg-amber-50': check.status === 'warning',
                    'border-green-200 bg-green-50': check.status === 'pass',
                    'border-gray-200 bg-gray-50': !['fail', 'warning', 'pass'].includes(check.status),
                }"
            >
                <!-- Status Icon -->
                <div 
                    class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center mr-3"
                    :class="getStatusColor(check.status)"
                >
                    <!-- Check Icon -->
                    <svg v-if="check.status === 'pass'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <!-- X Icon -->
                    <svg v-else-if="check.status === 'fail'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <!-- Exclamation Icon -->
                    <svg v-else-if="check.status === 'warning'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01" />
                    </svg>
                    <!-- Question Icon -->
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <!-- Check Content -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <p 
                            class="text-sm font-medium"
                            :class="{
                                'text-red-800': check.status === 'fail',
                                'text-amber-800': check.status === 'warning',
                                'text-green-800': check.status === 'pass',
                                'text-gray-800': !['fail', 'warning', 'pass'].includes(check.status),
                            }"
                        >
                            {{ getDisplayName(check) }}
                        </p>
                        <span 
                            v-if="check.section"
                            class="ml-2 px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-600"
                        >
                            {{ getSectionLabel(check.section) }}
                        </span>
                    </div>
                    <p 
                        v-if="check.message"
                        class="mt-1 text-sm whitespace-pre-line"
                        :class="{
                            'text-red-600': check.status === 'fail',
                            'text-amber-600': check.status === 'warning',
                            'text-green-600': check.status === 'pass',
                            'text-gray-600': !['fail', 'warning', 'pass'].includes(check.status),
                        }"
                    >
                        {{ getDisplayMessage(check) }}
                    </p>

                    <!-- Navigate to Section Button -->
                    <button
                        v-if="check.section && check.status !== 'pass'"
                        @click="navigateToSection(check.section)"
                        class="mt-2 text-xs font-medium text-wedding-600 hover:text-wedding-700 underline"
                    >
                        Ir para {{ getSectionLabel(check.section) }}
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="displayedChecks.length === 0" class="text-center py-8 text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <p class="mt-2 text-sm">Nenhuma verificação disponível</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.text-wedding-600 {
    color: #a18072;
}
.text-wedding-700 {
    color: #8b6b5d;
}
</style>
