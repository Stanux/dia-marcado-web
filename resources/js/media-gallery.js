import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import MediaGalleryWrapper from './Components/MediaScreen/MediaGalleryWrapper.vue';

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('media-gallery-app');
    
    if (!container) {
        console.error('Media gallery container not found');
        return;
    }

    // Get albums data from window object (set by Blade template)
    const albumsData = window.__mediaGalleryData?.albums || [];

    // Create and mount Vue app
    const app = createApp({
        render() {
            return h(MediaGalleryWrapper, {
                albums: albumsData
            });
        }
    });

    app.mount(container);
});
