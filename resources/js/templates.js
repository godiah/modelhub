document.addEventListener("DOMContentLoaded", function () {
    const showTemplatesBtn = document.getElementById("showTemplates");
    let currentSearchTerm = "";

    // Debounce function for search input
    function debounce(func, timeout = 300) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => func.apply(this, args), timeout);
        };
    }

    if (showTemplatesBtn) {
        showTemplatesBtn.addEventListener("click", function () {
            showTemplatesLoading();
            fetch("/my-jobs/message-templates")
                .then((response) => response.json())
                .then((templates) => {
                    showTemplatesModal(templates);
                })
                .catch((error) => {
                    console.error("Error fetching templates:", error);
                    showErrorMessage(
                        "Failed to load templates. Please try again."
                    );
                });
        });
    }

    function showTemplatesLoading() {
        const loadingModal = document.createElement("div");
        loadingModal.id = "templatesLoadingModal";
        loadingModal.className =
            "fixed inset-0 bg-neutral-900 bg-opacity-50 z-30 flex items-center justify-center";
        loadingModal.innerHTML = `
            <div class="bg-white rounded-lg shadow-xl p-6 flex flex-col items-center">
                <div class="animate-spin rounded-full h-10 w-10 border-t-2 border-b-2 border-primary"></div>
                <p class="mt-3 font-main text-neutral-700">Loading templates...</p>
            </div>`;
        document.body.appendChild(loadingModal);
    }

    function showErrorMessage(message) {
        const loadingModal = document.getElementById("templatesLoadingModal");
        if (loadingModal) document.body.removeChild(loadingModal);

        const toast = document.createElement("div");
        toast.className =
            "fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-30 font-main";
        toast.innerHTML = message;
        document.body.appendChild(toast);
        setTimeout(() => document.body.removeChild(toast), 3000);
    }

    function showSuccessMessage(message) {
        const toast = document.createElement("div");
        toast.className =
            "fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-30 font-main";
        toast.innerHTML = message;
        document.body.appendChild(toast);
        setTimeout(() => document.body.removeChild(toast), 3000);
    }

    function showTemplatesModal(templates) {
        const loadingModal = document.getElementById("templatesLoadingModal");
        if (loadingModal) document.body.removeChild(loadingModal);

        const globalTemplates = templates.filter((t) => t.user_id === null);
        const userTemplates = templates.filter((t) => t.user_id !== null);

        const modal = document.createElement("div");
        modal.id = "templateSelectionModal";
        modal.className =
            "fixed inset-0 bg-neutral-900 bg-opacity-50 z-30 flex items-center justify-center p-6";

        let modalContent = `
            <div class="bg-white rounded-lg shadow-xl w-full max-w-6xl flex flex-col" style="max-height: 80vh; margin: 1.5rem 0;">
                <div class="py-3 px-4 border-b border-neutral-200 flex justify-between items-center sticky top-0 bg-white z-10">
                    <div class="flex items-center space-x-2">
                        <div class="bg-primary rounded-full p-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold font-tertiary text-neutral-800">Message Templates</h3>
                    </div>
                    <div class="flex items-center">
                        <input type="text" id="templateSearch" placeholder="Search templates..." 
                               class="mr-3 border border-neutral-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-secondary font-main text-sm"
                               value="${currentSearchTerm}">
                        <button id="closeTemplateModal" class="text-neutral-500 hover:text-neutral-700 focus:outline-none transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="p-4 overflow-y-auto flex-grow">`;

        if (templates.length === 0) {
            modalContent += `
                <div class="flex flex-col items-center justify-center py-8">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-neutral-300" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 4a3 3 0 00-3 3v6a3 3 0 003 3h10a3 3 0 003-3V7a3 3 0 00-3-3H5zm-1 9v-1h5v2H5a1 1 0 01-1-1zm7 1h4a1 1 0 001-1v-1h-5v2z" clip-rule="evenodd" />
                    </svg>
                    <p class="text-neutral-500 mt-3 font-main">No templates found.</p>
                    <p class="text-neutral-400 font-main text-sm">Create new templates to see them here.</p>
                </div>`;
        } else {
            if (globalTemplates.length > 0) {
                modalContent += `
                    <div class="mb-6 template-section">
                        <h3 class="text-sm font-semibold font-tertiary text-primary border-b pb-2 mb-3">Global Templates</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 templates-container">
                            ${globalTemplates
                                .map((t) => createTemplateCard(t, true))
                                .join("")}
                        </div>
                    </div>`;
            }

            if (userTemplates.length > 0) {
                modalContent += `
                    <div class="template-section">
                        <h3 class="text-sm font-semibold font-tertiary text-secondary border-b pb-2 mb-3">Your Custom Templates</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 templates-container">
                            ${userTemplates
                                .map((t) => createTemplateCard(t, false))
                                .join("")}
                        </div>
                    </div>`;
            }
        }

        modalContent += `</div>
                <div class="py-3 px-4 border-t border-neutral-200 flex justify-between items-center bg-neutral-50 sticky bottom-0">
                    <p class="text-xs text-neutral-500 font-main">${
                        templates.length
                    } template${templates.length !== 1 ? "s" : ""} available</p>
                    <div class="flex space-x-2">
                        <button id="closeTemplateModalBtn" class="px-3 py-1.5 border border-neutral-300 rounded-md hover:bg-neutral-100 transition font-main text-sm text-neutral-700">Close</button>
                        <button id="createTemplateBtn" class="px-3 py-1.5 bg-primary text-white rounded-md hover:bg-primary/90 transition flex items-center font-main text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            New Template
                        </button>
                    </div>
                </div>
            </div>`;

        modal.innerHTML = modalContent;
        document.body.appendChild(modal);

        // Search functionality
        const searchInput = document.getElementById("templateSearch");
        const updateSearch = debounce(function (e) {
            currentSearchTerm = e.target.value.toLowerCase();
            performSearch();
        });

        searchInput.addEventListener("input", updateSearch);
        if (currentSearchTerm) performSearch();

        function performSearch() {
            const templateItems = modal.querySelectorAll(".template-item");
            let visibleCount = 0;

            templateItems.forEach((item) => {
                const matches = matchesSearch(item, currentSearchTerm);
                item.style.display = matches ? "block" : "none";
                if (matches) visibleCount++;
            });

            updateTemplateCount(visibleCount);
            toggleEmptySections();
        }

        function matchesSearch(item, term) {
            if (!term) return true;
            const fields = [
                item.querySelector("h4").textContent,
                item.querySelector(".template-subject").value,
                item.querySelector(".template-message").value,
            ];
            return fields.some((f) => f.toLowerCase().includes(term));
        }

        function updateTemplateCount(count) {
            const countElement = modal.querySelector(
                ".text-xs.text-neutral-500.font-main"
            );
            countElement.textContent = `${count} template${
                count !== 1 ? "s" : ""
            } available`;
        }

        function toggleEmptySections() {
            modal.querySelectorAll(".template-section").forEach((section) => {
                const hasVisible = section.querySelector(
                    ".template-item[style*='display: block']"
                );
                section.style.display = hasVisible ? "block" : "none";
            });
        }

        // Close modal handlers
        document
            .getElementById("closeTemplateModal")
            .addEventListener("click", () => document.body.removeChild(modal));
        document
            .getElementById("closeTemplateModalBtn")
            .addEventListener("click", () => document.body.removeChild(modal));
        modal.addEventListener(
            "click",
            (e) => e.target === modal && document.body.removeChild(modal)
        );

        // Create template button
        document
            .getElementById("createTemplateBtn")
            .addEventListener("click", showCreateTemplateModal);

        // Template interaction handlers
        const templateItems = modal.querySelectorAll(".template-item");
        templateItems.forEach((item) => {
            item.addEventListener("click", function (e) {
                if (!e.target.closest("button")) applyTemplate(this);
            });

            const useBtn = item.querySelector(".use-template-btn");
            useBtn &&
                useBtn.addEventListener("click", (e) => {
                    e.stopPropagation();
                    applyTemplate(item);
                });

            const deleteBtn = item.querySelector(".delete-template-btn");
            deleteBtn &&
                deleteBtn.addEventListener("click", (e) => {
                    e.stopPropagation();
                    const templateId = item.dataset.templateId;
                    confirm("Delete template?") &&
                        deleteTemplate(templateId, item);
                });
        });

        function deleteTemplate(templateId, element) {
            fetch(`/my-jobs/message-templates/${templateId}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                },
            })
                .then((response) => {
                    if (!response.ok) throw new Error("Delete failed");
                    element.remove();
                    updateTemplateCount(
                        modal.querySelectorAll(".template-item").length
                    );
                    showSuccessMessage("Template deleted!");
                    toggleEmptySections();
                })
                .catch((error) => {
                    console.error("Delete error:", error);
                    showErrorMessage("Failed to delete template");
                });
        }

        function applyTemplate(element) {
            const subject = element.querySelector(".template-subject").value;
            const message = element.querySelector(".template-message").value;

            document.getElementById("subject").value = subject;
            document.getElementById("message").value = message;

            document.body.removeChild(modal);
        }
    }

    function createTemplateCard(template, isGlobal) {
        return `
            <div class="border border-neutral-200 rounded-lg hover:border-secondary transition-all duration-200 cursor-pointer template-item group overflow-hidden shadow-sm hover:shadow-md ${
                isGlobal ? "relative" : ""
            }" data-template-id="${template.id}">
                <div class="p-3 border-b border-neutral-100">
                    <h4 class="font-semibold font-tertiary text-neutral-800 group-hover:text-primary transition-colors">${
                        template.name
                    }</h4>
                    ${
                        isGlobal
                            ? `<span class="absolute top-3 right-3 bg-blue-100 text-primary text-xs py-0.5 px-2 rounded-full font-main">Global</span>`
                            : ""
                    }
                </div>
                <div class="p-3 bg-neutral-50">
                    <div class="mb-2">
                        <p class="text-xs text-neutral-500 uppercase font-secondary tracking-wider">Subject</p>
                        <p class="text-sm text-neutral-700 font-main mt-0.5">${
                            template.subject
                        }</p>
                    </div>
                    <div>
                        <p class="text-xs text-neutral-500 uppercase font-secondary tracking-wider">Message Preview</p>
                        <p class="text-sm text-neutral-600 font-main mt-0.5 line-clamp-2">${template.message.substring(
                            0,
                            100
                        )}${template.message.length > 100 ? "..." : ""}</p>
                    </div>
                </div>
                <div class="p-2 bg-white flex justify-between opacity-0 group-hover:opacity-100 transition-opacity">
                    <div>
                        ${
                            !isGlobal
                                ? `<button class="delete-template-btn px-3 py-1 bg-red-500 text-white text-xs rounded-md hover:bg-red-600 transition font-main">Delete</button>`
                                : ""
                        }
                    </div>
                    <button class="use-template-btn px-3 py-1 bg-secondary text-white text-xs rounded-md hover:bg-secondary/80 transition font-main">
                        Use Template
                    </button>
                </div>
                <input type="hidden" class="template-subject" value="${
                    template.subject
                }">
                <input type="hidden" class="template-message" value="${
                    template.message
                }">
            </div>`;
    }

    function showCreateTemplateModal() {
        const createModal = document.createElement("div");
        createModal.id = "createTemplateModal";
        createModal.className =
            "fixed inset-0 bg-neutral-900 bg-opacity-70 backdrop-blur-sm z-50 flex items-center justify-center p-6";

        createModal.innerHTML = `
            <div class="bg-white rounded-lg shadow-xl w-full max-w-lg animate-fadeIn">
                <div class="py-3 px-4 border-b border-neutral-200 flex justify-between items-center">
                    <div class="flex items-center space-x-2">
                        <div class="bg-primary rounded-full p-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold font-tertiary text-neutral-800">Create New Template</h3>
                    </div>
                    <button id="closeCreateModal" class="text-neutral-500 hover:text-neutral-700 focus:outline-none transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-4">
                    <form id="createTemplateForm">
                        <div class="mb-4">
                            <label for="templateName" class="block text-sm font-medium text-neutral-700 mb-1">Template Name</label>
                            <input type="text" id="templateName" name="name" required class="w-full px-3 py-2 border border-neutral-300 rounded-md focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition">
                            <p class="text-xs text-neutral-500 mt-1">Give your template a descriptive name</p>
                        </div>
                        
                        <div class="mb-4">
                            <label for="templateSubject" class="block text-sm font-medium text-neutral-700 mb-1">Subject Line</label>
                            <input type="text" id="templateSubject" name="subject" required class="w-full px-3 py-2 border border-neutral-300 rounded-md focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition">
                        </div>
                        
                        <div class="mb-4">
                            <label for="templateMessage" class="block text-sm font-medium text-neutral-700 mb-1">Message Body</label>
                            <textarea id="templateMessage" name="message" required rows="8" class="w-full px-3 py-2 border border-neutral-300 rounded-md focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition"></textarea>                           
                        </div>
                        
                        <div id="formErrors" class="mb-4 hidden">
                            <div class="bg-red-50 border-l-4 border-red-500 p-3 rounded">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-red-700" id="errorsList"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-2 pt-2">
                            <button type="button" id="cancelCreateTemplate" class="px-4 py-2 border border-neutral-300 rounded-md hover:bg-neutral-100 transition font-main text-sm text-neutral-700">Cancel</button>
                            <button type="submit" id="saveTemplate" class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary/90 transition font-main text-sm flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                Save Template
                            </button>
                        </div>
                    </form>
                </div>

            </div>`;

        document.body.appendChild(createModal);

        // Form submission and close handlers (same as original)
        document
            .getElementById("closeCreateModal")
            .addEventListener("click", () =>
                document.body.removeChild(createModal)
            );
        document
            .getElementById("cancelCreateTemplate")
            .addEventListener("click", () =>
                document.body.removeChild(createModal)
            );
        createModal.addEventListener(
            "click",
            (e) =>
                e.target === createModal &&
                document.body.removeChild(createModal)
        );

        document
            .getElementById("createTemplateForm")
            .addEventListener("submit", function (e) {
                e.preventDefault();

                const saveBtn = document.getElementById("saveTemplate");
                const originalBtnContent = saveBtn.innerHTML;
                const formErrors = document.getElementById("formErrors");
                const errorsList = document.getElementById("errorsList");

                // Show loading state
                saveBtn.disabled = true;
                saveBtn.innerHTML = `
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Saving...
    `;

                // Get form data
                const formData = {
                    name: document.getElementById("templateName").value,
                    subject: document.getElementById("templateSubject").value,
                    message: document.getElementById("templateMessage").value,
                };

                // Send AJAX request
                fetch("/my-jobs/message-templates", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                    body: JSON.stringify(formData),
                })
                    .then((response) => {
                        if (!response.ok) {
                            return response.json().then((errors) => {
                                throw errors;
                            });
                        }
                        return response.json();
                    })
                    .then((data) => {
                        // Close create modal
                        document.body.removeChild(createModal);

                        // Show success message
                        showSuccessMessage("Template created successfully!");

                        // Refresh templates list
                        const existingModal = document.getElementById(
                            "templateSelectionModal"
                        );
                        if (existingModal)
                            document.body.removeChild(existingModal);

                        showTemplatesLoading();

                        // Fetch updated templates
                        fetch("/my-jobs/message-templates")
                            .then((response) => response.json())
                            .then((templates) => showTemplatesModal(templates))
                            .catch((error) => {
                                console.error(
                                    "Error fetching templates:",
                                    error
                                );
                                showErrorMessage("Failed to reload templates.");
                            });
                    })
                    .catch((error) => {
                        // Reset button state
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = originalBtnContent;

                        // Handle validation errors
                        formErrors.classList.remove("hidden");

                        if (error.errors) {
                            let errorMessages = [];
                            for (const field in error.errors) {
                                errorMessages.push(error.errors[field][0]);
                            }
                            errorsList.textContent = errorMessages.join(", ");
                        } else {
                            errorsList.textContent =
                                "An error occurred while saving the template. Please try again.";
                        }
                    });
            });
    }
});
