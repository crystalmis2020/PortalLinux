import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const runtimeConfig = window.portalRealtimeConfig || {};
const reverbScheme = runtimeConfig.reverbScheme || import.meta.env.VITE_REVERB_SCHEME || (window.location.protocol === 'https:' ? 'https' : 'http');
const reverbHost = runtimeConfig.reverbHost || import.meta.env.VITE_REVERB_HOST || window.location.hostname;
const reverbPort = Number(runtimeConfig.reverbPort || import.meta.env.VITE_REVERB_PORT || (reverbScheme === 'https' ? 443 : 8080));
const authEndpoint = runtimeConfig.broadcastAuthEndpoint || '/broadcasting/auth';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: runtimeConfig.reverbKey || import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: reverbHost,
    wsPort: reverbPort,
    wssPort: reverbPort,
    forceTLS: reverbScheme === 'https',
    authEndpoint,
    enabledTransports: ['ws', 'wss'],
});
