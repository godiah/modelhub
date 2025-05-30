import "flowbite";
import "./bootstrap";
import "./templates";
import "./search-history";

/**
 * Creating a new project/job
 * Checking validity of project title immediately
 */
document.addEventListener("DOMContentLoaded", function () {
    const titleInput = document.getElementById("title");
    const titleValidationMessage = document.getElementById(
        "title-validation-message"
    );

    titleInput.addEventListener("input", function () {
        const title = titleInput.value.trim();

        if (title.length > 0) {
            fetch(`/check-title?title=${encodeURIComponent(title)}`)
                .then((response) => response.json())
                .then((data) => {
                    if (data.exists) {
                        titleValidationMessage.textContent =
                            "This title is already in use.";
                        titleValidationMessage.classList.remove("hidden");
                        titleInput.setCustomValidity(
                            "This title is already in use."
                        );
                    } else {
                        titleValidationMessage.classList.add("hidden");
                        titleInput.setCustomValidity("");
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);
                });
        } else {
            titleValidationMessage.classList.add("hidden");
            titleInput.setCustomValidity("");
        }
    });
});

/**
 * Creating a new project/job
 * Required 3D Skills
 */
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("searchInput");
    const skillsDropdown = document.getElementById("skillsDropdown");
    const dropdownContent = document.getElementById("dropdownContent");
    const selectedOptionsContainer = document.getElementById(
        "selectedOptionsContainer"
    );
    const hiddenSelect = document.getElementById("hiddenSelect");

    // Populate dropdown with checkboxes
    function populateDropdown() {
        dropdownContent.innerHTML = "";
        Array.from(hiddenSelect.options).forEach((option) => {
            const dropdownItem = document.createElement("div");
            dropdownItem.classList.add(
                "flex",
                "items-center",
                "hover:bg-neutral-100",
                "p-2",
                "rounded",
                "transition",
                "duration-200"
            );

            const checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.value = option.value;
            checkbox.id = `skill-${option.value}`;
            checkbox.classList.add(
                "h-4",
                "w-4",
                "text-secondary",
                "focus:ring-secondary",
                "border-neutral-300",
                "rounded"
            );

            const label = document.createElement("label");
            label.htmlFor = `skill-${option.value}`;
            label.classList.add("ml-2", "text-sm", "text-neutral-700");
            label.textContent = option.value;

            dropdownItem.appendChild(checkbox);
            dropdownItem.appendChild(label);

            // Add event listener to checkbox
            checkbox.addEventListener("change", function () {
                updateSelectedOptions();
            });

            dropdownContent.appendChild(dropdownItem);
        });

        // Restore selected skills from hidden select
        Array.from(hiddenSelect.options).forEach((option) => {
            const checkbox = document.querySelector(
                `input[value="${option.value}"]`
            );
            if (checkbox) {
                checkbox.checked = option.selected;
            }
        });

        updateSelectedOptions();
    }

    // Update selected options display
    function updateSelectedOptions() {
        selectedOptionsContainer.innerHTML = "";
        const selectedOptions = Array.from(
            dropdownContent.querySelectorAll("input:checked")
        );

        selectedOptions.forEach((checkbox) => {
            const selectedOptionEl = document.createElement("div");
            selectedOptionEl.classList.add(
                "text-sm",
                "text-white",
                "bg-accent",
                "px-2",
                "py-1",
                "rounded",
                "inline-flex",
                "items-center",
                "ml-2",
                "mb-2",
                "whitespace-nowrap"
            );

            const removeBtn = document.createElement("button");
            removeBtn.innerHTML = "&times;";
            removeBtn.classList.add(
                "ml-2",
                "text-white",
                "hover:text-neutral-700"
            );
            removeBtn.addEventListener("click", () => {
                checkbox.checked = false;
                updateSelectedOptions();
            });

            selectedOptionEl.textContent = checkbox.value;
            selectedOptionEl.appendChild(removeBtn);

            selectedOptionsContainer.appendChild(selectedOptionEl);

            // Update hidden select
            const option = Array.from(hiddenSelect.options).find(
                (opt) => opt.value === checkbox.value
            );
            option.selected = checkbox.checked;
        });

        // Show/hide dropdown based on search
        filterDropdown(searchInput.value);
    }

    // Filter dropdown items
    function filterDropdown(searchTerm) {
        const dropdownItems = dropdownContent.querySelectorAll("div");
        searchTerm = searchTerm.toLowerCase();

        dropdownItems.forEach((item) => {
            const label = item.querySelector("label");
            const isVisible = label.textContent
                .toLowerCase()
                .includes(searchTerm);
            item.style.display = isVisible ? "flex" : "none";
        });

        // Toggle dropdown visibility
        const visibleItems = Array.from(dropdownItems).filter(
            (item) => item.style.display !== "none"
        );

        if (visibleItems.length > 0) {
            skillsDropdown.classList.remove("hidden");
        } else {
            skillsDropdown.classList.add("hidden");
        }
    }

    // Search input event
    searchInput.addEventListener("input", function () {
        filterDropdown(this.value);
    });

    // Click outside to close dropdown
    document.addEventListener("click", function (event) {
        if (!event.target.closest(".relative")) {
            skillsDropdown.classList.add("hidden");
        }
    });

    // Focus on search input to show dropdown
    searchInput.addEventListener("focus", function () {
        skillsDropdown.classList.remove("hidden");
    });

    // Initial population
    populateDropdown();
    skillsDropdown.classList.add("hidden");
});

/**
 * Creating a new project/job
 * Required 3D Software
 */
document.addEventListener("DOMContentLoaded", function () {
    // Software Skills Multiselect
    const softwareSearchInput = document.querySelector(
        '#software-skills-dropdown input[type="text"]'
    );
    const softwareSkillsDropdown = document
        .getElementById("software-skills-dropdown")
        .querySelector("#skillsDropdown");
    const softwareDropdownContent = document
        .getElementById("software-skills-dropdown")
        .querySelector("#dropdownContent");
    const softwareSelectedOptionsContainer = document
        .getElementById("software-skills-dropdown")
        .querySelector("#selectedOptionsContainer");
    const softwareHiddenSelect = document.getElementById(
        "software-hidden-select"
    );

    // Populate dropdown with checkboxes
    function populateSoftwareDropdown() {
        softwareDropdownContent.innerHTML = "";
        Array.from(softwareHiddenSelect.options).forEach((option) => {
            const dropdownItem = document.createElement("div");
            dropdownItem.classList.add(
                "flex",
                "items-center",
                "hover:bg-neutral-100",
                "p-2",
                "rounded",
                "transition",
                "duration-200"
            );

            const checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.value = option.value;
            checkbox.id = `software-${option.value}`;
            checkbox.classList.add(
                "h-4",
                "w-4",
                "text-secondary",
                "focus:ring-secondary",
                "border-neutral-300",
                "rounded"
            );

            const label = document.createElement("label");
            label.htmlFor = `software-${option.value}`;
            label.classList.add("ml-2", "text-sm", "text-neutral-700");
            label.textContent = option.value;

            dropdownItem.appendChild(checkbox);
            dropdownItem.appendChild(label);

            // Add event listener to checkbox
            checkbox.addEventListener("change", function () {
                updateSoftwareSelectedOptions();
            });

            softwareDropdownContent.appendChild(dropdownItem);
        });

        // Restore selected software from hidden select
        Array.from(softwareHiddenSelect.options).forEach((option) => {
            const checkbox = document.querySelector(
                `input[value="${option.value}"]`
            );
            if (checkbox) {
                checkbox.checked = option.selected;
            }
        });

        updateSoftwareSelectedOptions();
    }

    // Update selected options display
    function updateSoftwareSelectedOptions() {
        softwareSelectedOptionsContainer.innerHTML = "";
        const selectedOptions = Array.from(
            softwareDropdownContent.querySelectorAll("input:checked")
        );

        selectedOptions.forEach((checkbox) => {
            const selectedOptionEl = document.createElement("div");
            selectedOptionEl.classList.add(
                "text-sm",
                "text-white",
                "bg-accent",
                "px-2",
                "py-1",
                "rounded",
                "inline-flex",
                "items-center",
                "ml-2",
                "mb-2",
                "whitespace-nowrap"
            );

            const removeBtn = document.createElement("button");
            removeBtn.innerHTML = "&times;";
            removeBtn.classList.add(
                "ml-2",
                "text-white",
                "hover:text-neutral-700"
            );
            removeBtn.addEventListener("click", () => {
                checkbox.checked = false;
                updateSoftwareSelectedOptions();
            });

            selectedOptionEl.textContent = checkbox.value;
            selectedOptionEl.appendChild(removeBtn);

            softwareSelectedOptionsContainer.appendChild(selectedOptionEl);

            // Update hidden select
            const option = Array.from(softwareHiddenSelect.options).find(
                (opt) => opt.value === checkbox.value
            );
            option.selected = checkbox.checked;
        });

        // Show/hide dropdown based on search
        filterSoftwareDropdown(softwareSearchInput.value);
    }

    // Filter dropdown items
    function filterSoftwareDropdown(searchTerm) {
        const dropdownItems = softwareDropdownContent.querySelectorAll("div");
        searchTerm = searchTerm.toLowerCase();

        dropdownItems.forEach((item) => {
            const label = item.querySelector("label");
            const isVisible = label.textContent
                .toLowerCase()
                .includes(searchTerm);
            item.style.display = isVisible ? "flex" : "none";
        });

        // Toggle dropdown visibility
        const visibleItems = Array.from(dropdownItems).filter(
            (item) => item.style.display !== "none"
        );

        if (visibleItems.length > 0) {
            softwareSkillsDropdown.classList.remove("hidden");
        } else {
            softwareSkillsDropdown.classList.add("hidden");
        }
    }

    // Search input event
    softwareSearchInput.addEventListener("input", function () {
        filterSoftwareDropdown(this.value);
    });

    // Click outside to close dropdown
    document.addEventListener("click", function (event) {
        const softwareDropdownContainer = document.getElementById(
            "software-skills-dropdown"
        );
        if (!event.target.closest("#software-skills-dropdown")) {
            softwareSkillsDropdown.classList.add("hidden");
        }
    });

    // Focus on search input to show dropdown
    softwareSearchInput.addEventListener("focus", function () {
        softwareSkillsDropdown.classList.remove("hidden");
    });

    // Initial population
    populateSoftwareDropdown();
    softwareSkillsDropdown.classList.add("hidden");
});

/**
 * Creating a new project/job
 * Preview Image Upload
 */
document.addEventListener("DOMContentLoaded", () => {
    const imageUpload = document.getElementById("imageUpload");
    const selectPreviewBtn = document.getElementById("selectPreviewBtn");
    const previewContainer = document.getElementById("preview-container");
    const previewEmptyState = document.getElementById("previewEmptyState");
    const previewFileStatus = document.getElementById("previewFileStatus");
    const oldImageInput = document.getElementById("old-image-input");

    // Trigger file input when the select button is clicked
    selectPreviewBtn.addEventListener("click", () => {
        imageUpload.click();
    });

    // Handle selected files
    imageUpload.addEventListener("change", handleFiles, false);

    function handleFiles(e) {
        const files = e.target.files;
        if (!files || files.length === 0) return;

        const file = files[0];

        // Validate file type
        if (!file.type.startsWith("image/")) {
            alert("Please select a valid image file (JPG, JPEG, PNG)");
            return;
        }

        // Validate file size (max 5MB)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            alert("File size should not exceed 5MB");
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            renderPreview(e.target.result, file);
            oldImageInput.value = e.target.result; // Store image data URL
        };
        reader.readAsDataURL(file);
    }

    // Function to render the preview
    function renderPreview(src, file = null) {
        // Clear previous preview
        previewContainer.innerHTML = "";

        // Create preview wrapper
        const previewWrapper = document.createElement("div");
        previewWrapper.className = "relative border rounded-lg overflow-hidden";
        previewWrapper.style.width = "150px";
        previewWrapper.style.height = "150px";

        // Image preview
        const previewImage = document.createElement("img");
        previewImage.src = src;
        previewImage.className = "w-full h-full object-cover";
        previewImage.alt = "Preview Image";

        // Remove button
        const removeButton = document.createElement("button");
        removeButton.type = "button";
        removeButton.className =
            "absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-colors";
        removeButton.innerHTML =
            '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>';

        removeButton.addEventListener("click", () => {
            previewContainer.innerHTML = ""; // Clear preview
            imageUpload.value = ""; // Clear file input
            oldImageInput.value = ""; // Clear old image input
            previewEmptyState.style.display = "flex";
            previewFileStatus.textContent = "No image selected";
        });

        // File details (optional, under the image)
        if (file) {
            // Update file status
            previewFileStatus.textContent = `${file.name} (${(
                file.size / 1024
            ).toFixed(1)} KB)`;
        } else {
            previewFileStatus.textContent = "Existing image";
        }

        // Assemble the preview
        previewWrapper.appendChild(previewImage);
        previewWrapper.appendChild(removeButton);
        previewContainer.appendChild(previewWrapper);

        // Show preview, hide empty state
        previewEmptyState.style.display = "none";
    }

    // Initialize with any existing image if present
    const oldImage = oldImageInput.value;
    if (oldImage && oldImage.trim() !== "") {
        renderPreview(oldImage);
    } else {
        // Make sure empty state is visible
        previewEmptyState.style.display = "flex";
    }
});

/**
 * Upload additional images
 */
document.addEventListener("DOMContentLoaded", function () {
    // Configuration
    const MAX_FILES = 5;
    const MAX_SIZE_MB = 5;

    // Elements
    const toggleCheckbox = document.getElementById("toggleAdditional");
    const uploadSection = document.getElementById("additionalImagesSection");
    const fileInput = document.getElementById("additionalImages");
    const selectButton = document.getElementById("selectImagesBtn");
    const fileCounter = document.getElementById("fileCounter");
    const previewGrid = document.getElementById("imagePreviewGrid");
    const emptyState = document.getElementById("emptyStateMessage");

    // State
    let files = [];

    // Event Listeners
    toggleCheckbox.addEventListener("change", function () {
        uploadSection.style.display = this.checked ? "block" : "none";
    });

    selectButton.addEventListener("click", function () {
        fileInput.click();
    });

    fileInput.addEventListener("change", function (event) {
        const newFiles = Array.from(event.target.files);
        const dataTransfer = new DataTransfer();

        // Add existing files to DataTransfer
        files.forEach((f) => dataTransfer.items.add(f.file));

        // Check file limit
        if (dataTransfer.items.length + newFiles.length > MAX_FILES) {
            alert(`Maximum ${MAX_FILES} images allowed`);
            fileInput.files = dataTransfer.files;
            return;
        }

        newFiles.forEach((file) => {
            // Validate file type
            if (!file.type.startsWith("image/")) {
                alert(`${file.name} is not an image`);
                return;
            }

            // Validate file size
            const fileSizeMB = file.size / 1024;
            if (fileSizeMB > MAX_SIZE_MB * 1024) {
                alert(
                    `${file.name} exceeds ${MAX_SIZE_MB}MB (${
                        MAX_SIZE_MB * 1024
                    } KB)`
                );
                return;
            }

            // Add to DataTransfer and state
            dataTransfer.items.add(file);
            files.push({
                id: Date.now() + Math.random().toString(36).substr(2, 9),
                name: file.name,
                size: fileSizeMB.toFixed(2),
                url: URL.createObjectURL(file),
                file: file,
            });
        });

        // Update previews and sync input
        fileInput.files = dataTransfer.files;
        updatePreviews();
        updateFileCounter();
        toggleEmptyState();
    });

    // Functions
    function updatePreviews() {
        previewGrid.innerHTML = "";
        files.forEach((file) => {
            const thumbnail = document.createElement("div");
            thumbnail.className = "relative group";
            thumbnail.innerHTML = `
                <div class="rounded-lg overflow-hidden border border-neutral-200 bg-white">
                    <div class="aspect-square bg-neutral-100 overflow-hidden flex items-center justify-center">
                        <img src="${file.url}" alt="${file.name}" class="h-full w-full object-cover">
                    </div>
                    <div class="p-2 text-xs">
                        <div class="truncate font-medium">${file.name}</div>
                        <div class="text-neutral-500">${file.size} KB</div>
                    </div>
                </div>
                <button data-id="${file.id}" class="remove-btn absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 shadow-sm opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            `;

            thumbnail
                .querySelector(".remove-btn")
                .addEventListener("click", () => removeFile(file.id));
            previewGrid.appendChild(thumbnail);
        });
    }

    function removeFile(id) {
        // Update files array
        files = files.filter((f) => f.id !== id);

        // Sync with file input
        const dataTransfer = new DataTransfer();
        files.forEach((f) => dataTransfer.items.add(f.file));
        fileInput.files = dataTransfer.files;

        updatePreviews();
        updateFileCounter();
        toggleEmptyState();
    }

    function updateFileCounter() {
        fileCounter.textContent = `${files.length}/${MAX_FILES} images`;
        const isMaxReached = files.length >= MAX_FILES;
        selectButton.disabled = isMaxReached;
        selectButton.classList.toggle("opacity-50", isMaxReached);
        selectButton.classList.toggle("cursor-not-allowed", isMaxReached);
    }

    function toggleEmptyState() {
        if (files.length === 0) {
            emptyState.style.display = "flex";
        } else {
            emptyState.style.setProperty("display", "none", "important");
        }
    }

    // Form submission handler
    const form = document.querySelector("form");
    if (form) {
        form.addEventListener("submit", function () {
            // Final sync before submission
            const dataTransfer = new DataTransfer();
            files.forEach((f) => dataTransfer.items.add(f.file));
            fileInput.files = dataTransfer.files;
        });
    }
});

/**
 * Creating a new project/job
 * Date Picker
 */
document.addEventListener("DOMContentLoaded", () => {
    const deadlineInput = document.getElementById("deadline");
    const deadlineDisplay = document.getElementById("deadline-display");
    const selectedDateText = document.getElementById("selected-date-text");
    const noDeadlineCheckbox = document.getElementById("no_deadline");

    // Set min date to today
    const today = new Date().toISOString().split("T")[0];
    deadlineInput.min = today;

    // No Deadline Checkbox Logic
    noDeadlineCheckbox.addEventListener("change", () => {
        if (noDeadlineCheckbox.checked) {
            deadlineInput.disabled = true;
            selectedDateText.textContent = "No Fixed Deadline";
            deadlineDisplay.classList.add("text-neutral-400");
        } else {
            deadlineInput.disabled = false;
            selectedDateText.textContent = "Select Deadline";
            deadlineDisplay.classList.remove("text-neutral-400");
        }
    });

    // Date Selection Logic
    deadlineInput.addEventListener("change", (e) => {
        const selectedDate = new Date(e.target.value);
        const formattedDate = selectedDate.toLocaleDateString("en-US", {
            year: "numeric",
            month: "long",
            day: "numeric",
        });

        selectedDateText.textContent = formattedDate;
        noDeadlineCheckbox.checked = false;
        deadlineInput.disabled = false;
        deadlineDisplay.classList.remove("text-neutral-400");
    });

    // Display Custom Date Picker on Click
    deadlineDisplay.addEventListener("click", () => {
        if (!noDeadlineCheckbox.checked) {
            deadlineInput.showPicker();
        }
    });
});

/**
 * Creating a new project/job
 * Markdown Editor
 */
document.addEventListener("DOMContentLoaded", function () {
    // Custom icons for toolbar
    const customIcons = {
        bold: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 12h9a4 4 0 0 1 0 8H6z"/><path d="M6 4h8a4 4 0 0 1 0 8H6z"/></svg>',
        italic: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><path d="M15 4L9 20"/></svg>',
        link: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
        quote: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.097-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 1-1 1s-1 0-1 1v1c0 1 1 1 1 1z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.097-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v2c1.75 0 2.75 1 2.75 3z"/></svg>',
        unordered:
            '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="3" cy="6" r="1"/><circle cx="3" cy="12" r="1"/><circle cx="3" cy="18" r="1"/></svg>',
        ordered:
            '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4v-2h2v2h-2"/></svg>',
    };

    // Initialize SimpleMDE
    const simplemde = new SimpleMDE({
        element: document.getElementById("description"),
        spellChecker: false,
        autofocus: false,
        placeholder: "Write your project description here...",
        toolbar: [
            {
                name: "bold",
                action: SimpleMDE.toggleBold,
                className: "fa fa-bold",
                title: "Bold",
                innerHTML: customIcons.bold,
            },
            {
                name: "italic",
                action: SimpleMDE.toggleItalic,
                className: "fa fa-italic",
                title: "Italic",
                innerHTML: customIcons.italic,
            },
            "|", // Separator
            {
                name: "link",
                action: SimpleMDE.drawLink,
                className: "fa fa-link",
                title: "Create Link",
                innerHTML: customIcons.link,
            },
            {
                name: "quote",
                action: SimpleMDE.toggleBlockquote,
                className: "fa fa-quote-left",
                title: "Quote",
                innerHTML: customIcons.quote,
            },
            "|", // Separator
            {
                name: "unordered-list",
                action: SimpleMDE.toggleUnorderedList,
                className: "fa fa-list-ul",
                title: "Unordered List",
                innerHTML: customIcons.unordered,
            },
            {
                name: "ordered-list",
                action: SimpleMDE.toggleOrderedList,
                className: "fa fa-list-ol",
                title: "Ordered List",
                innerHTML: customIcons.ordered,
            },
            "|", // Separator
            "preview",
            "fullscreen",
        ],
        status: false,
        renderingConfig: {
            codeSyntaxHighlighting: true,
        },
    });

    // Optional: Custom styling for SimpleMDE
    const style = document.createElement("style");
    style.innerHTML = `
        .CodeMirror {
            border: 1px solid #CBD5E1;            
            transition: all 0.3s ease;
        }
        .CodeMirror-focused {
            border-color: #10B981;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
        }
        .editor-toolbar {
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
            background-color: #F8FAFC;
            border-bottom: 1px solid #E2E8F0;
        }
        .editor-toolbar a {
            color: #64748B;
            opacity: 0.7;
            transition: all 0.3s ease;
        }
        .editor-toolbar a:hover {
            color: #10B981;
            opacity: 1;
        }
        .editor-toolbar a.active {
            color: #10B981;
            background: none;
        }
    `;
    document.head.appendChild(style);
});

/**
 * Ensuring all fields are filled in creating a job
 */
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("projectForm");
    const submitBtn = document.getElementById("submit-btn");
    const requiredFields = form.querySelectorAll("[required]");
    const imageUpload = document.getElementById("imageUpload");
    const noDeadlineCheckbox = document.getElementById("no_deadline");
    const deadlineInput = document.getElementById("deadline");

    function checkFormValidity() {
        let isValid = true;

        // Check regular required fields
        requiredFields.forEach((field) => {
            if (!field.value.trim()) {
                isValid = false;
            }
        });

        // Check image upload
        if (!imageUpload.files.length) {
            isValid = false;
        }

        // Check deadline
        if (!noDeadlineCheckbox.checked && !deadlineInput.value) {
            isValid = false;
        }

        // Check skills and software selections
        const skillsSelect = document.getElementById("hiddenSelect");
        const softwareSelect = document.getElementById(
            "software-hidden-select"
        );

        if (skillsSelect.selectedOptions.length === 0) {
            isValid = false;
        }

        if (softwareSelect.selectedOptions.length === 0) {
            isValid = false;
        }

        // Update button state
        submitBtn.disabled = !isValid;

        // Update button styling based on state
        if (isValid) {
            submitBtn.classList.remove("opacity-50", "cursor-not-allowed");
            submitBtn.classList.add("hover:bg-secondary/90");
        } else {
            submitBtn.classList.add("opacity-50", "cursor-not-allowed");
            submitBtn.classList.remove("hover:bg-secondary/90");
        }
    }

    // Add event listeners to all required fields
    requiredFields.forEach((field) => {
        field.addEventListener("input", checkFormValidity);
    });

    // Add event listener for image upload
    imageUpload.addEventListener("change", checkFormValidity);

    // Add event listeners for deadline
    noDeadlineCheckbox.addEventListener("change", checkFormValidity);
    deadlineInput.addEventListener("change", checkFormValidity);

    // Add event listeners for skills and software dropdowns
    const skillsDropdown = document.querySelector("#skillsDropdown");
    const softwareDropdown = document.querySelector(
        "#software-skills-dropdown #skillsDropdown"
    );

    function addDropdownListeners(dropdown, hiddenSelect) {
        dropdown.addEventListener("click", function (e) {
            const checkbox = e.target.closest('input[type="checkbox"]');
            if (checkbox) {
                checkFormValidity();
            }
        });
    }

    addDropdownListeners(
        skillsDropdown,
        document.getElementById("hiddenSelect")
    );
    addDropdownListeners(
        softwareDropdown,
        document.getElementById("software-hidden-select")
    );

    // Initial check
    submitBtn.disabled = true;
    submitBtn.classList.add("opacity-50", "cursor-not-allowed");
});

/**
 * Apply Filters/Search in browsing jobs
 */
document.addEventListener("DOMContentLoaded", function () {
    // Get all filter elements
    const skillsFilter = document.getElementById("skills-filter");
    const softwareFilter = document.getElementById("software-filter");
    const sortByFilter = document.getElementById("sort-by");
    const searchInput = document.querySelector('input[name="search"]');

    // Get buttons
    const applyFiltersBtn = document.getElementById("apply-filters");
    const resetFiltersBtn = document.getElementById("reset-filters");

    // Apply filters when button is clicked
    applyFiltersBtn.addEventListener("click", function () {
        applyFilters();
    });

    // Reset filters when button is clicked
    resetFiltersBtn.addEventListener("click", function () {
        skillsFilter.value = "";
        softwareFilter.value = "";
        sortByFilter.value = "newest";
        searchInput.value = "";
        applyFilters();
    });

    // Enable select filters to automatically submit on change (optional)
    // Uncomment if you want filters to apply immediately when changed
    /*
                [skillsFilter, softwareFilter, sortByFilter].forEach(filter => {
                    filter.addEventListener('change', function() {
                        applyFilters();
                    });
                });
                */

    // Function to apply all filters
    function applyFilters() {
        // Construct the URL with filter parameters
        const url = new URL(window.location.href);
        const params = url.searchParams;

        // Update or clear parameters based on filter values
        updateParam(params, "skills", skillsFilter.value);
        updateParam(params, "software", softwareFilter.value);
        updateParam(params, "sort", sortByFilter.value);
        updateParam(params, "search", searchInput.value);

        // Navigate to the filtered URL
        window.location.href = url.toString();
    }

    // Helper function to update or clear a URL parameter
    function updateParam(params, name, value) {
        if (value) {
            params.set(name, value);
        } else {
            params.delete(name);
        }
    }
});

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
