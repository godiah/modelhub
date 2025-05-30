function togglePassword() {
    const passwordInput = document.getElementById("password");
    const eyeOpen = document.getElementById("eye-open");
    const eyeClosed = document.getElementById("eye-closed");

    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        eyeOpen.classList.add("hidden");
        eyeClosed.classList.remove("hidden");
    } else {
        passwordInput.type = "password";
        eyeOpen.classList.remove("hidden");
        eyeClosed.classList.add("hidden");
    }
}

// Form validation and animation effects
document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("form");
    const inputs = form.querySelectorAll(
        'input[type="email"], input[type="password"]'
    );

    // Add focus effects
    inputs.forEach((input) => {
        input.addEventListener("focus", function () {
            this.parentElement.classList.add("scale-105");
        });

        input.addEventListener("blur", function () {
            this.parentElement.classList.remove("scale-105");
        });
    });
});
