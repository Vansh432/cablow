const ws = new WebSocket('ws://127.0.0.1:6001/app/your-app-key?protocol=7&client=js&version=5.3.0&flash=false');

ws.onopen = () => console.log('WebSocket connection opened');
ws.onmessage = (event) => console.log('Message received:', event.data);
ws.onerror = (error) => console.log('WebSocket error:', error);
ws.onclose = () => console.log('WebSocket connection closed');
