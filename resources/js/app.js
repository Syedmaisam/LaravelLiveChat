import './bootstrap';
import Pusher from 'pusher-js';

// Initialize Pusher for real-time updates
window.Pusher = Pusher;

// Get Reverb configuration from meta tag or window
const reverbAppKey = document.querySelector('meta[name="reverb-app-key"]')?.content ||
                     window.REVERB_APP_KEY ||
                     import.meta.env.VITE_REVERB_APP_KEY;
const reverbHost = document.querySelector('meta[name="reverb-host"]')?.content ||
                   window.REVERB_HOST ||
                   import.meta.env.VITE_REVERB_HOST ||
                   window.location.hostname;
const reverbPort = document.querySelector('meta[name="reverb-port"]')?.content ||
                   window.REVERB_PORT ||
                   import.meta.env.VITE_REVERB_PORT ||
                   '8080';
const reverbScheme = document.querySelector('meta[name="reverb-scheme"]')?.content ||
                     window.REVERB_SCHEME ||
                     import.meta.env.VITE_REVERB_SCHEME ||
                     'http';

if (reverbAppKey && reverbHost) {
    window.reverbClient = new Pusher(reverbAppKey, {
        wsHost: reverbHost,
        wsPort: reverbPort,
        wssPort: reverbPort,
        forceTLS: reverbScheme === 'https',
        enabledTransports: ['ws', 'wss'],
        cluster: 'mt1',
    });

    console.log('Pusher client initialized');
} else {
    console.warn('Reverb configuration not found. Real-time features may not work.');
}
