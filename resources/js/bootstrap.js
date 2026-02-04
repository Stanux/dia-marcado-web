import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

// Set CSRF token from meta tag
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

// Set Sanctum token from cookie
const sanctumToken = document.cookie
    .split('; ')
    .find(row => row.startsWith('XSRF-TOKEN='));

if (sanctumToken) {
    const token = decodeURIComponent(sanctumToken.split('=')[1]);
    window.axios.defaults.headers.common['X-XSRF-TOKEN'] = token;
}

// Add wedding context header to all API requests
axios.interceptors.request.use((config) => {
    // Get wedding ID from global variable (set by Inertia app)
    const weddingId = window.__weddingId;
    
    if (weddingId && config.url?.startsWith('/api/')) {
        config.headers['X-Wedding-ID'] = weddingId;
    }
    
    return config;
});
