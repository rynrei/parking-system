const dashboard = document.querySelector(".dashboard");
const themeToggle = document.getElementById("themeToggle");
const themeText = document.getElementById("themeText");

themeToggle.addEventListener("click", function () {

    dashboard.classList.toggle("dark-mode");

    if (dashboard.classList.contains("dark-mode")) {
        themeText.textContent = "Dark Mode";
    } else {
        themeText.textContent = "Light Mode";
    }

});

// =========================
// SERVER TIME
// =========================

const serverTime = document.getElementById("serverTime");
const serverDate = document.getElementById("serverDate");

if (serverTime && serverDate) {

    let currentTime = new Date();

    function updateServerTime() {

        currentTime.setSeconds(currentTime.getSeconds() + 1);

        serverTime.textContent = currentTime.toLocaleTimeString("en-US", {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit"
        });

        serverDate.textContent = currentTime.toLocaleDateString("en-US", {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric"
        });
    }

    setInterval(updateServerTime, 1000);
}