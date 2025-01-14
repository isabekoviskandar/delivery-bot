import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

window.Echo.channel('orders')
.listen('OrderEvent', (event) => {
        console.log('Order received:', event.order);
        const order = event.order;
        const table = document.getElementById('orders-table');
        // const row = table.insertRow();
        table.innerHTML = `
            <td>${order.id}</td>
            <td>${order.time}</td>
            <td>${order.status}</td>
        `;
    });
