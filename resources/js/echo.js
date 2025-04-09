import Echo from "laravel-echo";

import Pusher from "pusher-js";
window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'reverb',
//     key: import.meta.env.VITE_REVERB_APP_KEY,
//     wsHost: import.meta.env.VITE_REVERB_HOST,
//     wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
//     wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
//     forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
//     enabledTransports: ['ws', 'wss'],
// });
window.Echo = new Echo({
    broadcaster: "pusher",
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
});

// Listen for notifications
if (window.userId) {
    window.Echo.private(`App.Models.User.${window.userId}`).notification(
        (notification) => {
            console.log(notification);
            // Update the notification counter
            let counter = document.getElementById("notification-counter");
            if (counter) {
                let count = parseInt(counter.innerText || "0");
                counter.innerText = count + 1;
                counter.classList.remove("hidden");
            }

            // Show a toast notification
            showToast(`New message: ${notification.subject}`);
        }
    );
}

function showToast(message) {
    // Create a toast notification
    const toast = document.createElement("div");
    toast.className =
        "fixed bottom-4 right-4 bg-gray-800 text-white px-4 py-2 rounded shadow-lg transform transition-all duration-500 translate-y-full";
    toast.innerText = message;
    document.body.appendChild(toast);

    // Animate it in
    setTimeout(() => {
        toast.classList.remove("translate-y-full");
    }, 10);

    // Remove after 5 seconds
    setTimeout(() => {
        toast.classList.add("translate-y-full");
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 500);
    }, 5000);
}
