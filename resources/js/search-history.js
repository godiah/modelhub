document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("search-input");
    const searchHistory = document.getElementById("search-history");
    const searchHistoryItems = document.getElementById("search-history-items");
    const clearHistoryBtn = document.getElementById("clear-history");

    // Max number of searches to store
    const MAX_HISTORY_ITEMS = 5;

    // Check if localStorage is available
    const isLocalStorageAvailable = () => {
        try {
            const test = "test";
            localStorage.setItem(test, test);
            localStorage.removeItem(test);
            return true;
        } catch (e) {
            console.warn("localStorage is not available:", e);
            return false;
        }
    };

    // Show search history when input is focused
    searchInput.addEventListener("focus", function () {
        displaySearchHistory();
        searchHistory.classList.remove("hidden");
    });

    // Hide search history when clicking outside
    document.addEventListener("click", function (e) {
        if (
            !searchInput.contains(e.target) &&
            !searchHistory.contains(e.target)
        ) {
            searchHistory.classList.add("hidden");
        }
    });

    // Handle search form submission to save history
    const searchForm = searchInput.closest("form");
    if (searchForm) {
        searchForm.addEventListener("submit", function (e) {
            const searchTerm = searchInput.value.trim();
            if (searchTerm) {
                // Save the search term before form submission
                saveSearchTerm(searchTerm);

                // If you're using AJAX for form submission, you would handle that here
                // Otherwise, the form will submit normally after saving the search term
            }
        });
    }

    // Also save on enter key press to ensure the term is saved
    searchInput.addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
            const searchTerm = searchInput.value.trim();
            if (searchTerm) {
                saveSearchTerm(searchTerm);
            }
        }
    });

    // Clear history button
    clearHistoryBtn.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        localStorage.removeItem("search_history");
        displaySearchHistory();
    });

    // Save search term to localStorage
    function saveSearchTerm(term) {
        if (!isLocalStorageAvailable()) return;

        let history = getSearchHistory();

        // Remove the term if it already exists (to avoid duplicates)
        history = history.filter(
            (item) => item.toLowerCase() !== term.toLowerCase()
        );

        // Add the new term at the beginning
        history.unshift(term);

        // Keep only the most recent MAX_HISTORY_ITEMS
        if (history.length > MAX_HISTORY_ITEMS) {
            history = history.slice(0, MAX_HISTORY_ITEMS);
        }

        try {
            localStorage.setItem("search_history", JSON.stringify(history));
            console.log("Search term saved:", term);
        } catch (e) {
            console.error("Failed to save search history:", e);
        }
    }

    // Get search history from localStorage
    function getSearchHistory() {
        if (!isLocalStorageAvailable()) return [];

        try {
            const history = localStorage.getItem("search_history");
            return history ? JSON.parse(history) : [];
        } catch (e) {
            console.error("Failed to retrieve search history:", e);
            return [];
        }
    }

    // Display search history in the dropdown
    function displaySearchHistory() {
        const history = getSearchHistory();
        searchHistoryItems.innerHTML = "";

        if (history.length === 0) {
            const emptyItem = document.createElement("div");
            emptyItem.className = "px-4 py-2 text-gray-500 italic";
            emptyItem.textContent = "No recent searches";
            searchHistoryItems.appendChild(emptyItem);
        } else {
            history.forEach((term) => {
                const historyItem = document.createElement("a");
                historyItem.href = `?search=${encodeURIComponent(term)}`;
                historyItem.className =
                    "block px-4 py-2 hover:bg-gray-100 flex justify-between items-center";

                // Create text span
                const textSpan = document.createElement("span");
                textSpan.textContent = term;
                historyItem.appendChild(textSpan);

                // Add click event to use the search term
                textSpan.addEventListener("click", function (e) {
                    e.preventDefault();
                    searchInput.value = term;
                    searchHistory.classList.add("hidden");
                    // Optional: auto-submit the form
                    if (searchForm) {
                        searchForm.submit();
                    }
                });

                // Create delete button
                const deleteBtn = document.createElement("button");
                deleteBtn.innerHTML = "×";
                deleteBtn.className = "text-gray-400 hover:text-red-500";
                deleteBtn.addEventListener("click", function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    removeSearchTerm(term);
                    displaySearchHistory();
                });
                historyItem.appendChild(deleteBtn);

                searchHistoryItems.appendChild(historyItem);
            });
        }
    }

    // Remove a specific search term
    function removeSearchTerm(term) {
        if (!isLocalStorageAvailable()) return;

        let history = getSearchHistory();
        history = history.filter((item) => item !== term);
        localStorage.setItem("search_history", JSON.stringify(history));
    }

    // Initialize the search history display
    displaySearchHistory();
});
