function updateServerTime() {
    const now = new Date();

    const time = now.toLocaleTimeString('en-US', {
        timeZone: 'Asia/Manila',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    });

    const date = now.toLocaleDateString('en-US', {
        timeZone: 'Asia/Manila',
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric'
    });

    const serverTime = document.getElementById('serverTime');
    const serverDate = document.getElementById('serverDate');

    if (serverTime) {
        serverTime.textContent = time;
    }

    if (serverDate) {
        serverDate.textContent = date;
    }
}

updateServerTime();
setInterval(updateServerTime, 1000);