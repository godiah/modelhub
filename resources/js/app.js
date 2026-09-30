import "flowbite";
import "./bootstrap";

/**
 * Switch Post and Find Jobs Tabs
 */
document.addEventListener("DOMContentLoaded", function () {
    function showTab(tabName) {
        // Hide all tab contents
        const tabContents = document.querySelectorAll(".tab-content");
        tabContents.forEach((tab) => {
            tab.classList.add("hidden");
        });

        // Show the selected tab content
        const selectedTab = document.getElementById(tabName + "-content");
        if (selectedTab) {
            selectedTab.classList.remove("hidden");
        }

        // Update button styles
        const buttons = document.querySelectorAll('button[id$="-btn"]');
        buttons.forEach((button) => {
            button.classList.remove("border-secondary", "text-secondary");
            button.classList.add("border-transparent", "text-neutral-600");
        });

        // Update active button
        const activeButton = document.getElementById(tabName + "-btn");
        if (activeButton) {
            activeButton.classList.remove(
                "border-transparent",
                "text-neutral-600"
            );
            activeButton.classList.add("border-secondary", "text-secondary");
        }
    }
    window.showTab = showTab;
});

// // Function to fetch unread message counts
// function fetchUnreadMessageCounts() {
//     fetch("/chat/messages/unread-count")
//         .then((response) => response.json())
//         .then((data) => {
//             // Dispatch event with unread counts
//             window.dispatchEvent(
//                 new CustomEvent("update-unread-counts", {
//                     detail: { unreadCounts: data.unread_counts },
//                 })
//             );
//         })
//         .catch((error) => {
//             console.error("Error fetching unread counts:", error);
//         });
// }

// // Fetch unread counts when page loads
// document.addEventListener("DOMContentLoaded", () => {
//     fetchUnreadMessageCounts();

//     // Fetch unread counts periodically
//     setInterval(fetchUnreadMessageCounts, 60000); // Every minute
// });
