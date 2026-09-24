document.addEventListener("DOMContentLoaded", function () {
    // Get references to the form and submit button
    const form = document.getElementById("projectForm");
    const submitBtn = document.getElementById("submit-btn");
    const noDeadlineCheckbox = document.getElementById("no_deadline");
    const deadlineInput = document.getElementById("deadline");
    const deadlineDisplay = document.getElementById("deadline-display");
    const selectedDateText = document.getElementById("selected-date-text");

    // Set up the no_deadline checkbox behavior
    if (noDeadlineCheckbox) {
        // Toggle deadline field visibility based on no_deadline checkbox
        noDeadlineCheckbox.addEventListener("change", function () {
            if (this.checked) {
                deadlineDisplay.classList.add("opacity-50");
                deadlineInput.value = ""; // Clear date input
                selectedDateText.textContent = "No Fixed Deadline";
            } else {
                deadlineDisplay.classList.remove("opacity-50");
                if (!deadlineInput.value) {
                    selectedDateText.textContent = "Select a date";
                }
            }
            checkFormChanges();
        });
    }

    // Update displayed date when date input changes
    deadlineInput.addEventListener("change", function () {
        if (this.value) {
            const dateObj = new Date(this.value);
            const options = { year: "numeric", month: "long", day: "numeric" };
            selectedDateText.textContent = dateObj.toLocaleDateString(
                "en-US",
                options
            );

            // Uncheck no deadline if a date was selected
            if (noDeadlineCheckbox) {
                noDeadlineCheckbox.checked = false;
                deadlineDisplay.classList.remove("opacity-50");
            }
        } else {
            if (noDeadlineCheckbox && noDeadlineCheckbox.checked) {
                selectedDateText.textContent = "No Fixed Deadline";
            } else {
                selectedDateText.textContent = "Select a date";
            }
        }
        checkFormChanges();
    });

    // Initial setup to ensure consistency between deadline and no_deadline checkbox
    if (deadlineInput && noDeadlineCheckbox) {
        console.log("Initial deadline value:", deadlineInput.value); // Debug line

        // Check if deadline input has a non-empty value
        if (deadlineInput.value && deadlineInput.value.trim() !== "") {
            noDeadlineCheckbox.checked = false;
            deadlineDisplay.classList.remove("opacity-50");

            // Format and display the date
            const dateObj = new Date(deadlineInput.value);
            const options = { year: "numeric", month: "long", day: "numeric" };
            selectedDateText.textContent = dateObj.toLocaleDateString(
                "en-US",
                options
            );
        } else {
            noDeadlineCheckbox.checked = true;
            deadlineDisplay.classList.add("opacity-50");
            selectedDateText.textContent = "No Fixed Deadline";
        }
    }

    // Function to check if form has changes
    function checkFormChanges() {
        // Check if any input, textarea, or select has changes
        let hasChanges = false;

        // Check regular inputs
        Array.from(form.elements).forEach((element) => {
            // Skip buttons and submit elements
            if (element.type === "submit" || element.type === "button") {
                return;
            }

            // Skip checkboxes for now - we'll handle them separately
            if (element.type === "checkbox") {
                return;
            }

            // Check if the element has a value and if it's different from the default
            const defaultValue = element.dataset.defaultValue;
            if (defaultValue !== undefined && element.value !== defaultValue) {
                hasChanges = true;
            }
        });

        // Check checkboxes
        const checkboxes = form.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach((checkbox) => {
            const defaultChecked = checkbox.dataset.defaultChecked === "true";
            if (checkbox.checked !== defaultChecked) {
                hasChanges = true;
            }
        });

        // Enable or disable the submit button based on changes
        submitBtn.disabled = !hasChanges;
    }

    // Initialize the form by storing default values and disabling the submit button
    function initializeForm() {
        // Store default values for each form element
        Array.from(form.elements).forEach((element) => {
            // Skip buttons and submit elements
            if (element.type === "submit" || element.type === "button") {
                return;
            }

            // Handle checkboxes separately
            if (element.type === "checkbox") {
                // Store the default checked state
                element.dataset.defaultChecked = element.checked;
                return;
            }

            // Store the default value for other elements
            element.dataset.defaultValue = element.value;
        });

        // Disable the submit button initially
        submitBtn.disabled = true;

        // Add event listeners to form elements
        const formInputs = form.querySelectorAll("input, select, textarea");
        formInputs.forEach((input) => {
            // Add input event for most elements
            input.addEventListener("input", checkFormChanges);

            // Add change event specifically for checkboxes
            if (input.type === "checkbox") {
                input.addEventListener("change", checkFormChanges);
            }
        });
    }

    // Initialize the form
    initializeForm();
});
