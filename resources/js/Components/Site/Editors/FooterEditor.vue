<script setup>
/**
 * FooterEditor Component
 * 
 * Editor for the Footer section of the wedding site.
 * Supports social links, copyright text, privacy policy, and back to top button.
 * 
 * @Requirements: 14.1, 14.3, 14.4, 14.5
 */
import { ref, watch, computed } from 'vue';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['change']);

// Local copy of content for editing (deep clone to avoid reference issues)
const localContent = ref(JSON.parse(JSON.stringify(props.content)));

// Watch for external content changes
watch(() => props.content, (newContent) => {
    localContent.value = JSON.parse(JSON.stringify(newContent));
}, { deep: true });

/**
 * Emit changes to parent
 */
const emitChange = () => {
    emit('change', JSON.parse(JSON.stringify(localContent.value)));
};

/**
 * Update a field and emit change
 */
const updateField = (field, value) => {
    localContent.value[field] = value;
    emitChange();
};

/**
 * Update style field
 */
const updateStyle = (field, value) => {
    if (!localContent.value.style) {
        localContent.value.style = {};
    }
    localContent.value.style[field] = value;
    emitChange();
};

/**
 * Add social link
 */
const addSocialLink = () => {
    if (!localContent.value.socialLinks) {
        localContent.value.socialLinks = [];
    }
    localContent.value.socialLinks.push({
        platform: 'instagram',
        url: '',
        icon: 'instagram',
    });
    emitChange();
};

/**
 * Update social link
 */
const updateSocialLink = (index, field, value) => {
    if (localContent.value.socialLinks && localContent.value.socialLinks[index]) {
        localContent.value.socialLinks[index][field] = value;
        // Auto-update icon when platform changes
        if (field === 'platform') {
            localContent.value.socialLinks[index].icon = value;
        }
        emitChange();
    }
};

/**
 * Remove social link
 */
const removeSocialLink = (index) => {
    if (localContent.value.socialLinks) {
        localContent.value.socialLinks.splice(index, 1);
        emitChange();
    }
};

/**
 * Move social link up
 */
const moveSocialLinkUp = (index) => {
    if (index > 0 && localContent.value.socialLinks) {
        const item = localContent.value.socialLinks.splice(index, 1)[0];
        localContent.value.socialLinks.splice(index - 1, 0, item);
        emitChange();
    }
};

/**
 * Move social link down
 */
const moveSocialLinkDown = (index) => {
    if (localContent.value.socialLinks && index < localContent.value.socialLinks.length - 1) {
        const item = localContent.value.socialLinks.splice(index, 1)[0];
        localContent.value.socialLinks.splice(index + 1, 0, item);
        emitChange();
    }
};

// Computed properties
const socialLinks = computed(() => localContent.value.socialLinks || []);
const style = computed(() => localContent.value.style || {});

// Available social platforms
const socialPlatforms = [
    { value: 'instagram', label: 'Instagram' },
    { value: 'facebook', label: 'Facebook' },
    { value: 'twitter', label: 'Twitter/X' },
    { value: 'tiktok', label: 'TikTok' },
    { value: 'youtube', label: 'YouTube' },
    { value: 'pinterest', label: 'Pinterest' },
    { value: 'linkedin', label: 'LinkedIn' },
    { value: 'whatsapp', label: 'WhatsApp' },
    { value: 'telegram', label: 'Telegram' },
    { value: 'website', label: 'Website' },
];
</script>

<template>
    <div class="space-y-6">
        <!-- Social Links -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Redes Sociais</h3>
                <button
                    @click="addSocialLink"
                    class="text-sm text-wedding-600 hover:text-wedding-700 font-medium"
                >
                    + Adicionar rede
                </button>
            </div>

            <div v-if="socialLinks.length === 0" class="p-4 bg-gray-50 rounded-lg text-center text-sm text-gray-500">
                Nenhuma rede social adicionada. Clique em "Adicionar rede" para começar.
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="(link, index) in socialLinks"
                    :key="index"
                    class="p-4 bg-gray-50 rounded-lg space-y-3"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">Rede {{ index + 1 }}</span>
                        <div class="flex items-center space-x-2">
                            <button
                                @click="moveSocialLinkUp(index)"
                                :disabled="index === 0"
                                class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-30"
                                title="Mover para cima"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                </svg>
                            </button>
                            <button
                                @click="moveSocialLinkDown(index)"
                                :disabled="index === socialLinks.length - 1"
                                class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-30"
                                title="Mover para baixo"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <button
                                @click="removeSocialLink(index)"
                                class="p-1 text-red-400 hover:text-red-600"
                                title="Remover"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Plataforma</label>
                            <select
                                :value="link.platform"
                                @change="updateSocialLink(index, 'platform', $event.target.value)"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                            >
                                <option v-for="platform in socialPlatforms" :key="platform.value" :value="platform.value">
                                    {{ platform.label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">URL</label>
                            <input
                                type="text"
                                :value="link.url"
                                @input="updateSocialLink(index, 'url', $event.target.value)"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                                placeholder="https://instagram.com/..."
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Copyright -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Copyright</h3>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Texto de Copyright</label>
                <input
                    type="text"
                    :value="localContent.copyrightText"
                    @input="updateField('copyrightText', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    placeholder="Ex: Todos os direitos reservados"
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ano</label>
                <input
                    type="number"
                    :value="localContent.copyrightYear"
                    @input="updateField('copyrightYear', $event.target.value ? parseInt($event.target.value) : null)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    placeholder="Deixe vazio para usar o ano atual"
                    min="2000"
                    max="2100"
                />
                <p class="mt-1 text-xs text-gray-500">Deixe vazio para preencher automaticamente com o ano atual</p>
            </div>
        </div>

        <!-- Privacy Policy -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Política de Privacidade</h3>
            
            <div class="flex items-center">
                <input
                    type="checkbox"
                    :checked="localContent.showPrivacyPolicy"
                    @change="updateField('showPrivacyPolicy', $event.target.checked)"
                    class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                />
                <label class="ml-2 text-sm text-gray-700">Exibir link de política de privacidade</label>
            </div>

            <div v-if="localContent.showPrivacyPolicy">
                <label class="block text-sm font-medium text-gray-700 mb-1">URL da Política de Privacidade</label>
                <input
                    type="text"
                    :value="localContent.privacyPolicyUrl"
                    @input="updateField('privacyPolicyUrl', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    placeholder="https://exemplo.com/privacidade"
                />
            </div>
        </div>

        <!-- Back to Top -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Navegação</h3>
            
            <div class="flex items-center">
                <input
                    type="checkbox"
                    :checked="localContent.showBackToTop"
                    @change="updateField('showBackToTop', $event.target.checked)"
                    class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                />
                <label class="ml-2 text-sm text-gray-700">Exibir botão "Voltar ao topo"</label>
            </div>
        </div>

        <!-- Style Settings -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Estilo</h3>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cor de Fundo</label>
                    <div class="flex items-center space-x-2">
                        <input
                            type="color"
                            :value="style.backgroundColor || '#333333'"
                            @input="updateStyle('backgroundColor', $event.target.value)"
                            class="h-10 w-14 border border-gray-300 rounded cursor-pointer"
                        />
                        <input
                            type="text"
                            :value="style.backgroundColor || '#333333'"
                            @input="updateStyle('backgroundColor', $event.target.value)"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500 text-sm"
                        />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cor do Texto</label>
                    <div class="flex items-center space-x-2">
                        <input
                            type="color"
                            :value="style.textColor || '#ffffff'"
                            @input="updateStyle('textColor', $event.target.value)"
                            class="h-10 w-14 border border-gray-300 rounded cursor-pointer"
                        />
                        <input
                            type="text"
                            :value="style.textColor || '#ffffff'"
                            @input="updateStyle('textColor', $event.target.value)"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500 text-sm"
                        />
                    </div>
                </div>
            </div>

            <div class="flex items-center">
                <input
                    type="checkbox"
                    :checked="style.borderTop"
                    @change="updateStyle('borderTop', $event.target.checked)"
                    class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                />
                <label class="ml-2 text-sm text-gray-700">Exibir borda superior</label>
            </div>
        </div>
    </div>
</template>

<style scoped>
.focus\:ring-wedding-500:focus {
    --tw-ring-color: #b8998a;
}
.focus\:border-wedding-500:focus {
    border-color: #b8998a;
}
.text-wedding-600 {
    color: #a18072;
}
.text-wedding-700 {
    color: #8b6b5d;
}
</style>
