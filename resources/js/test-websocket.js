var socket = new WebSocket('ws://localhost:6001');

socket.onmessage = function(event) {
    console.log('Message from server:', event.data);
};

socket.onopen = function() {
    console.log('WebSocket connection established');
};

socket.onclose = function() {
    console.log('WebSocket connection closed');
};
