import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const tripId = process.argv[2];

if (!tripId) {
    console.error(
        'usage: node scripts/testRealTimeForClient <tripId>'
    );
    process.exit(1);
}

const key = 'aagvcjsetaztymgbyzli';

const pusher = new Pusher(key, {
    cluster: 'mt1',
    wsHost: 'localhost',
    wsPort: 8080,
    wssPort: 8080,
    forceTLS: false,
    enabledTransports: ['ws'],
});

const echo = new Echo({
    broadcaster: 'reverb',
    client: pusher,
});

const channel = echo.channel(`trip.${tripId}`);

channel.subscribed(() => {
    console.log(`[ok] subscribed to channel trip.${tripId}`);
});

channel.error((error) => {
    console.error('[auth failed]', error);
});

channel.listen('.trip.status.updated', (payload) => {
    console.log(`\n[${new Date().toISOString()}] trip.status.updated`);
    console.log(JSON.stringify(payload, null, 2));
});

console.log(
    `listening on channel trip.${tripId}…`
);