const sendHeartbeat = () => {
    if (window.axios) {
        window.axios.post('/telemetria/heartbeat')
            .catch(error => {
                console.debug('Heartbeat failed', error);
            });
    } else {
        fetch('/telemetria/heartbeat', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        }).catch(error => {
            console.debug('Heartbeat failed', error);
        });
    }
};

// Ping every 30 seconds
setInterval(sendHeartbeat, 30000);

// Ping on visibility change
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        sendHeartbeat();
    }
});
