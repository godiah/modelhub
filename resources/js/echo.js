import Echo from "laravel-echo";

import Pusher from "pusher-js";
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: "pusher",
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
});

// Notification handler system
const NotificationHandler = {
    // Define notification types and their handlers
    handlers: {
        NewApplicationMessage: {
            getMessage: (notification) =>
                `New message: ${notification.subject}`,
            getIcon: () => "message",
            getColor: () => "info",
        },
        HiredNotification: {
            getMessage: (notification) =>
                `Congratulations! You've been hired for ${notification.job_title}`,
            getIcon: () => "success",
            getColor: () => "success",
        },
        EngagementResponseNotification: {
            getMessage: (notification) =>
                `New engagement response: ${notification.subject}`,
            getIcon: () => "info",
            getColor: () => "info",
        },
        EngagementCancelledNotification: {
            getMessage: (notification) =>
                `Engagement cancelled: ${notification.job_title}`,
            getIcon: () => "warning",
            getColor: () => "warning",
        },
        // Add new notification types here as needed
        default: {
            getMessage: () => "New notification received",
            getIcon: () => "info",
            getColor: () => "info",
        },
    },

    // Get handler for notification type
    getHandler(notificationType) {
        if (!notificationType) return this.handlers.default;

        // Extract the class name from the full namespace
        const typeParts = notificationType.split("\\");
        const className = typeParts[typeParts.length - 1];

        return this.handlers[className] || this.handlers.default;
    },

    // Process a notification
    process(notification) {
        // Update notification counter
        this.updateCounter();

        // Get the appropriate handler
        const handler = this.getHandler(notification.type);

        // Show the notification
        this.showNotification(
            handler.getMessage(notification),
            handler.getIcon(),
            handler.getColor()
        );
    },

    // Update notification counter
    updateCounter() {
        let counter = document.getElementById("notification-counter");
        if (counter) {
            let count = parseInt(counter.innerText || "0");
            counter.innerText = count + 1;
            counter.classList.remove("hidden");
        }
    },

    // Show notification using SweetAlert
    showNotification(message, icon, color) {
        // Check if SweetAlert is available
        if (typeof Swal !== "undefined") {
            Swal.fire({
                title: "New Notification",
                text: message,
                icon: icon,
                toast: true,
                position: "bottom-end",
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
            });
        } else {
            // Fallback to console if SweetAlert isn't available
            console.warn("SweetAlert not found, using fallback notification");
            console.log(`Notification: ${message}`);

            // Basic toast fallback
            this.showToastFallback(message);
        }
    },

    // Fallback toast if SweetAlert isn't available
    showToastFallback(message) {
        // Create a toast notification
        const toast = document.createElement("div");
        toast.className =
            "fixed bottom-4 right-4 bg-gray-800 text-white px-4 py-2 rounded shadow-lg transform transition-all duration-500 translate-y-full z-50";
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
    },
};

// Listen for notifications
if (window.userId) {
    window.Echo.private(`App.Models.User.${window.userId}`).notification(
        (notification) => {
            console.log("Received notification:", notification);
            NotificationHandler.process(notification);
        }
    );
}

// Export the handler for potential use elsewhere
window.NotificationHandler = NotificationHandler;
