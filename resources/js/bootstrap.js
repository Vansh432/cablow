import Echo from "laravel-echo";
window.Pusher = require('pusher-js');

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: '293ba2b410cf08305f33',
    cluster: 'ap2',
    forceTLS: true
});

Echo.channel(`driver-location.${driver_id}`)
    .listen('DriverLocationUpdated', (data) => {
        console.log('Driver location updated:', data.latitude, data.longitude);
        // Update the map or UI with the new location
    });
