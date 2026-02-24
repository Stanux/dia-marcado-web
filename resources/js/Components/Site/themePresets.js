/**
 * Theme presets for the visual site editor.
 *
 * Each preset fully overrides `content.theme` and applies deterministic
 * section-level style/typography overrides.
 */

const clone = (value) => JSON.parse(JSON.stringify(value));

const isPlainObject = (value) => {
    return value !== null && typeof value === 'object' && !Array.isArray(value);
};

const mergeWithOverride = (target, override) => {
    const base = isPlainObject(target) ? { ...target } : {};

    Object.entries(override).forEach(([key, value]) => {
        if (isPlainObject(value) && isPlainObject(base[key])) {
            base[key] = mergeWithOverride(base[key], value);
            return;
        }

        base[key] = clone(value);
    });

    return base;
};

export const THEME_PRESETS = [
    {
        id: 'romance-classico',
        name: 'Romance Clássico',
        description: 'Paleta rosé com base clara e leitura suave para um visual elegante.',
        palette: [
            { key: 'primaryColor', label: 'Primária', color: '#d4a574' },
            { key: 'secondaryColor', label: 'Secundária', color: '#8b7355' },
            { key: 'baseBackgroundColor', label: 'Base', color: '#ffffff' },
            { key: 'surfaceBackgroundColor', label: 'Apoio', color: '#f5ebe4' },
        ],
        theme: {
            primaryColor: '#d4a574',
            secondaryColor: '#8b7355',
            baseBackgroundColor: '#ffffff',
            surfaceBackgroundColor: '#f5ebe4',
            fontFamily: 'Playfair Display',
            fontSize: '16px',
        },
        sections: {
            header: {
                style: {
                    height: '80px',
                    alignment: 'center',
                    backgroundColor: '#ffffff',
                    sticky: true,
                    overlay: {
                        enabled: false,
                        opacity: 0.3,
                    },
                },
                titleTypography: {
                    fontFamily: 'Playfair Display',
                    fontColor: '#6b5347',
                    fontSize: 32,
                    fontWeight: 600,
                    fontItalic: false,
                    fontUnderline: false,
                },
                subtitleTypography: {
                    fontFamily: 'Montserrat',
                    fontColor: '#8b7355',
                    fontSize: 18,
                    fontWeight: 400,
                    fontItalic: false,
                    fontUnderline: false,
                },
                menuTypography: {
                    fontFamily: 'Montserrat',
                    fontColor: '#5c4c45',
                    fontSize: 14,
                    fontWeight: 500,
                    fontItalic: false,
                    fontUnderline: false,
                },
                menuHoverTypography: {
                    fontFamily: 'Montserrat',
                    fontColor: '#6b5347',
                    fontSize: 14,
                    fontWeight: 600,
                    fontItalic: false,
                    fontUnderline: false,
                },
            },
            hero: {
                style: {
                    overlay: {
                        color: '#000000',
                        opacity: 0.28,
                    },
                    textAlign: 'center',
                    animation: 'fade',
                    animationDuration: 500,
                },
                titleTypography: {
                    fontFamily: 'Playfair Display',
                    fontColor: '#ffffff',
                    fontSize: 56,
                    fontWeight: 700,
                    fontItalic: false,
                    fontUnderline: false,
                },
                subtitleTypography: {
                    fontFamily: 'Montserrat',
                    fontColor: '#ffffff',
                    fontSize: 20,
                    fontWeight: 400,
                    fontItalic: false,
                    fontUnderline: false,
                },
            },
            saveTheDate: {
                style: {
                    backgroundColor: '#f8f2ee',
                    layout: 'modal',
                },
                sectionTypography: {
                    fontFamily: 'Playfair Display',
                    fontColor: '#a18072',
                    fontSize: 20,
                    fontWeight: 600,
                    fontItalic: false,
                    fontUnderline: false,
                },
                descriptionTypography: {
                    fontFamily: 'Montserrat',
                    fontColor: '#5c4c45',
                    fontSize: 16,
                    fontWeight: 400,
                    fontItalic: false,
                    fontUnderline: false,
                },
            },
            giftRegistry: {
                style: {
                    backgroundColor: '#ffffff',
                },
                titleTypography: {
                    fontFamily: 'Playfair Display',
                    fontColor: '#6b5347',
                    fontSize: 48,
                    fontWeight: 700,
                    fontItalic: false,
                    fontUnderline: false,
                },
            },
            rsvp: {
                style: {
                    backgroundColor: '#f5f5f5',
                    layout: 'card',
                    containerMaxWidth: 'max-w-xl',
                    showCard: true,
                },
            },
            photoGallery: {
                style: {
                    backgroundColor: '#ffffff',
                    columns: 3,
                },
            },
            footer: {
                style: {
                    backgroundColor: '#333333',
                    textColor: '#ffffff',
                    borderTop: false,
                },
            },
        },
    },
    {
        id: 'noite-sofisticada',
        name: 'Noite Sofisticada',
        description: 'Contraste forte com fundo escuro e acentos dourados para estilo premium.',
        palette: [
            { key: 'primaryColor', label: 'Primária', color: '#b78f56' },
            { key: 'secondaryColor', label: 'Secundária', color: '#3a2c20' },
            { key: 'baseBackgroundColor', label: 'Base', color: '#111827' },
            { key: 'surfaceBackgroundColor', label: 'Apoio', color: '#f9fafb' },
        ],
        theme: {
            primaryColor: '#b78f56',
            secondaryColor: '#3a2c20',
            baseBackgroundColor: '#111827',
            surfaceBackgroundColor: '#f9fafb',
            fontFamily: 'Cormorant Garamond',
            fontSize: '16px',
        },
        sections: {
            header: {
                style: {
                    height: '80px',
                    alignment: 'center',
                    backgroundColor: '#111827',
                    sticky: true,
                    overlay: {
                        enabled: false,
                        opacity: 0.3,
                    },
                },
                titleTypography: {
                    fontFamily: 'Cormorant Garamond',
                    fontColor: '#f9fafb',
                    fontSize: 32,
                    fontWeight: 600,
                    fontItalic: false,
                    fontUnderline: false,
                },
                subtitleTypography: {
                    fontFamily: 'Montserrat',
                    fontColor: '#d1d5db',
                    fontSize: 18,
                    fontWeight: 400,
                    fontItalic: false,
                    fontUnderline: false,
                },
                menuTypography: {
                    fontFamily: 'Montserrat',
                    fontColor: '#f3f4f6',
                    fontSize: 14,
                    fontWeight: 500,
                    fontItalic: false,
                    fontUnderline: false,
                },
                menuHoverTypography: {
                    fontFamily: 'Montserrat',
                    fontColor: '#f5d9a8',
                    fontSize: 14,
                    fontWeight: 600,
                    fontItalic: false,
                    fontUnderline: false,
                },
            },
            hero: {
                style: {
                    overlay: {
                        color: '#000000',
                        opacity: 0.45,
                    },
                    textAlign: 'center',
                    animation: 'fade',
                    animationDuration: 500,
                },
                titleTypography: {
                    fontFamily: 'Cormorant Garamond',
                    fontColor: '#f9fafb',
                    fontSize: 58,
                    fontWeight: 700,
                    fontItalic: false,
                    fontUnderline: false,
                },
                subtitleTypography: {
                    fontFamily: 'Montserrat',
                    fontColor: '#f3f4f6',
                    fontSize: 20,
                    fontWeight: 400,
                    fontItalic: false,
                    fontUnderline: false,
                },
            },
            saveTheDate: {
                style: {
                    backgroundColor: '#111827',
                    layout: 'modal',
                },
                sectionTypography: {
                    fontFamily: 'Cormorant Garamond',
                    fontColor: '#f9fafb',
                    fontSize: 20,
                    fontWeight: 600,
                    fontItalic: false,
                    fontUnderline: false,
                },
                descriptionTypography: {
                    fontFamily: 'Montserrat',
                    fontColor: '#d1d5db',
                    fontSize: 16,
                    fontWeight: 400,
                    fontItalic: false,
                    fontUnderline: false,
                },
            },
            giftRegistry: {
                style: {
                    backgroundColor: '#0f172a',
                },
                titleTypography: {
                    fontFamily: 'Cormorant Garamond',
                    fontColor: '#f9fafb',
                    fontSize: 48,
                    fontWeight: 700,
                    fontItalic: false,
                    fontUnderline: false,
                },
            },
            rsvp: {
                style: {
                    backgroundColor: '#111827',
                    layout: 'card',
                    containerMaxWidth: 'max-w-xl',
                    showCard: true,
                },
            },
            photoGallery: {
                style: {
                    backgroundColor: '#0f172a',
                    columns: 3,
                },
            },
            footer: {
                style: {
                    backgroundColor: '#020617',
                    textColor: '#f9fafb',
                    borderTop: true,
                },
            },
        },
    },
    {
        id: 'jardim-suave',
        name: 'Jardim Suave',
        description: 'Base clara com verde oliva e detalhes rosados para atmosfera leve.',
        palette: [
            { key: 'primaryColor', label: 'Primária', color: '#7c9a6d' },
            { key: 'secondaryColor', label: 'Secundária', color: '#d97b93' },
            { key: 'baseBackgroundColor', label: 'Base', color: '#f8faf6' },
            { key: 'surfaceBackgroundColor', label: 'Apoio', color: '#e5eddc' },
        ],
        theme: {
            primaryColor: '#7c9a6d',
            secondaryColor: '#d97b93',
            baseBackgroundColor: '#f8faf6',
            surfaceBackgroundColor: '#e5eddc',
            fontFamily: 'Lora',
            fontSize: '16px',
        },
        sections: {
            header: {
                style: {
                    height: '80px',
                    alignment: 'center',
                    backgroundColor: '#f8faf6',
                    sticky: true,
                    overlay: {
                        enabled: false,
                        opacity: 0.3,
                    },
                },
                titleTypography: {
                    fontFamily: 'Lora',
                    fontColor: '#334155',
                    fontSize: 32,
                    fontWeight: 600,
                    fontItalic: false,
                    fontUnderline: false,
                },
                subtitleTypography: {
                    fontFamily: 'Montserrat',
                    fontColor: '#4b5563',
                    fontSize: 18,
                    fontWeight: 400,
                    fontItalic: false,
                    fontUnderline: false,
                },
                menuTypography: {
                    fontFamily: 'Montserrat',
                    fontColor: '#334155',
                    fontSize: 14,
                    fontWeight: 500,
                    fontItalic: false,
                    fontUnderline: false,
                },
                menuHoverTypography: {
                    fontFamily: 'Montserrat',
                    fontColor: '#48603d',
                    fontSize: 14,
                    fontWeight: 600,
                    fontItalic: false,
                    fontUnderline: false,
                },
            },
            hero: {
                style: {
                    overlay: {
                        color: '#1f2937',
                        opacity: 0.26,
                    },
                    textAlign: 'center',
                    animation: 'fade',
                    animationDuration: 500,
                },
                titleTypography: {
                    fontFamily: 'Lora',
                    fontColor: '#ffffff',
                    fontSize: 56,
                    fontWeight: 700,
                    fontItalic: false,
                    fontUnderline: false,
                },
                subtitleTypography: {
                    fontFamily: 'Montserrat',
                    fontColor: '#f1f5f9',
                    fontSize: 20,
                    fontWeight: 400,
                    fontItalic: false,
                    fontUnderline: false,
                },
            },
            saveTheDate: {
                style: {
                    backgroundColor: '#eef5e8',
                    layout: 'modal',
                },
                sectionTypography: {
                    fontFamily: 'Lora',
                    fontColor: '#48603d',
                    fontSize: 20,
                    fontWeight: 600,
                    fontItalic: false,
                    fontUnderline: false,
                },
                descriptionTypography: {
                    fontFamily: 'Montserrat',
                    fontColor: '#3f4a42',
                    fontSize: 16,
                    fontWeight: 400,
                    fontItalic: false,
                    fontUnderline: false,
                },
            },
            giftRegistry: {
                style: {
                    backgroundColor: '#f8faf6',
                },
                titleTypography: {
                    fontFamily: 'Lora',
                    fontColor: '#334155',
                    fontSize: 48,
                    fontWeight: 700,
                    fontItalic: false,
                    fontUnderline: false,
                },
            },
            rsvp: {
                style: {
                    backgroundColor: '#eef5e8',
                    layout: 'card',
                    containerMaxWidth: 'max-w-xl',
                    showCard: true,
                },
            },
            photoGallery: {
                style: {
                    backgroundColor: '#f8faf6',
                    columns: 3,
                },
            },
            footer: {
                style: {
                    backgroundColor: '#24341f',
                    textColor: '#f8faf6',
                    borderTop: false,
                },
            },
        },
    },
];

export const DEFAULT_THEME_PRESET_ID = THEME_PRESETS[0]?.id ?? null;

export const getThemePresetById = (presetId) => {
    return THEME_PRESETS.find((preset) => preset.id === presetId) || null;
};

export const applyThemePresetToContent = (content, preset) => {
    const normalizedContent = isPlainObject(content) ? clone(content) : {};
    const safePreset = preset && isPlainObject(preset) ? preset : null;

    if (!safePreset) {
        return normalizedContent;
    }

    const nextContent = {
        ...normalizedContent,
    };

    if (isPlainObject(safePreset.theme)) {
        // Full replace for global theme settings.
        nextContent.theme = clone(safePreset.theme);
    }

    if (isPlainObject(safePreset.sections)) {
        const currentSections = isPlainObject(nextContent.sections) ? { ...nextContent.sections } : {};

        Object.entries(safePreset.sections).forEach(([sectionKey, sectionOverride]) => {
            if (!isPlainObject(sectionOverride)) {
                return;
            }

            currentSections[sectionKey] = mergeWithOverride(currentSections[sectionKey], sectionOverride);
        });

        nextContent.sections = currentSections;
    }

    return nextContent;
};
