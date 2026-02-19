import { onMounted, ref } from 'vue';

export const normalizeHexColor = (color, fallback = '#ffffff') => {
    if (typeof color !== 'string' || !color.trim()) {
        return fallback;
    }

    const value = color.trim();

    if (/^#[0-9a-f]{6}$/i.test(value)) {
        return value;
    }

    if (/^#[0-9a-f]{3}$/i.test(value)) {
        const [r, g, b] = value.slice(1).split('');
        return `#${r}${r}${g}${g}${b}${b}`;
    }

    const rgbaMatch = value.match(/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})(?:\s*,\s*[\d.]+\s*)?\)$/i);

    if (!rgbaMatch) {
        return fallback;
    }

    const toHex = (raw) => {
        const n = Number.parseInt(raw, 10);

        if (Number.isNaN(n)) {
            return '00';
        }

        return Math.max(0, Math.min(255, n)).toString(16).padStart(2, '0');
    };

    return `#${toHex(rgbaMatch[1])}${toHex(rgbaMatch[2])}${toHex(rgbaMatch[3])}`;
};

export const useColorField = () => {
    const isEyeDropperSupported = ref(false);

    onMounted(() => {
        isEyeDropperSupported.value = typeof window !== 'undefined' && 'EyeDropper' in window;
    });

    const pickColorFromScreen = async (onPick) => {
        if (!isEyeDropperSupported.value) {
            return null;
        }

        try {
            const eyeDropper = new window.EyeDropper();
            const { sRGBHex } = await eyeDropper.open();
            const hex = normalizeHexColor(sRGBHex, '').toLowerCase();

            if (!hex) {
                return null;
            }

            if (typeof onPick === 'function') {
                onPick(hex);
            }

            return hex;
        } catch (error) {
            if (error?.name !== 'AbortError') {
                console.warn('EyeDropper falhou:', error);
            }

            return null;
        }
    };

    return {
        isEyeDropperSupported,
        normalizeHexColor,
        pickColorFromScreen,
    };
};
