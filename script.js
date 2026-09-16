/**
 * ==========================================================================
 * STUDENTHUB PORTAL - CORE JAVASCRIPT (script.js)
 * Comprehensive Integrated Suite for Practical 4, Practical 5 & Practical 6
 * ==========================================================================
 * Features Included:
 * 1. Global Light/Dark Theme Switcher with localStorage persistence
 * 2. Responsive Hamburger Menu & Sidebar Drawer (Zero Double-Trigger Race)
 * 3. PRACTICAL 5: Student Registration Form Regex Validation & Error Handling
 *    - Real-Time keyup/input/change Validation
 *    - Password Strength Meter (Weak / Medium / Strong)
 *    - HTML5 Canvas CAPTCHA with Visual Noise & Refresh
 *    - Screen-Reader & Keyboard Accessibility (ARIA attributes)
 *    - Balanced Form Actions & Clear Form Handler
 * 4. PRACTICAL 5: Enhanced Validation on Login, Forgot Password & Forgot Username
 * 5. PRACTICAL 6: External JSON Data Handling with Fetch API (async/await)
 *    - events.json: Dynamic Event Cards, Search, Category Filter, Sort, Pagination
 *    - faqs.json: Dynamic Accordion, Search, Category Filter, Sort
 *    - students.json: Dynamic Table, Dependent Dropdowns (Country -> State -> City),
 *      Multi-field Search, Course/Year Filter, CGPA Sort, Pagination
 * 6. PRACTICAL 6: Advanced localStorage Data Caching with Expiry & Offline Fallback
 * 7. Practical 4: Notice Modal, Content Slider, Notification Banner
 * 8. Zero Console Errors with Conditional Element Existence Checks
 * ==========================================================================
 */

/* --------------------------------------------------------------------------
   IMMEDIATE THEME INITIALIZATION (Prevents Flash of Unstyled Theme - FOUC)
   -------------------------------------------------------------------------- */
(function applyInitialTheme() {
    try {
        const savedTheme = localStorage.getItem("theme");
        if (savedTheme === "dark") {
            document.documentElement.classList.add("dark-mode");
            if (document.body) {
                document.body.classList.add("dark-mode");
            }
        }
    } catch (e) {
        console.warn("localStorage unavailable for theme:", e);
    }
})();

/* --------------------------------------------------------------------------
   GLOBAL SIDEBAR / HAMBURGER FUNCTIONS (Practical 4)
   -------------------------------------------------------------------------- */
function openMenu() {
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay");
    const menuBtn = document.querySelector(".menu-btn");

    if (sidebar) {
        sidebar.classList.add("active");
        sidebar.setAttribute("aria-hidden", "false");
    }
    if (overlay) {
        overlay.classList.add("active");
    }
    if (menuBtn) {
        menuBtn.setAttribute("aria-expanded", "true");
    }
    document.body.style.overflow = "hidden";
}

function closeMenu() {
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay");
    const menuBtn = document.querySelector(".menu-btn");

    if (sidebar) {
        sidebar.classList.remove("active");
        sidebar.setAttribute("aria-hidden", "true");
    }
    if (overlay) {
        overlay.classList.remove("active");
    }
    if (menuBtn) {
        menuBtn.setAttribute("aria-expanded", "false");
    }
    document.body.style.overflow = "";
}

function toggleMenu() {
    const sidebar = document.getElementById("sidebar");
    if (sidebar && sidebar.classList.contains("active")) {
        closeMenu();
    } else {
        openMenu();
    }
}

window.openMenu = openMenu;
window.closeMenu = closeMenu;
window.toggleMenu = toggleMenu;

/* --------------------------------------------------------------------------
   PRACTICAL 6 UTILITY: LOCALSTORAGE DATA CACHE HELPER
   -------------------------------------------------------------------------- */
async function fetchWithCache(url, cacheKey, expiryMinutes = 30) {
    const now = Date.now();
    const expiryMs = expiryMinutes * 60 * 1000;

    // 1. Check localStorage Cache
    try {
        const cachedRaw = localStorage.getItem(cacheKey);
        if (cachedRaw) {
            const cachedObj = JSON.parse(cachedRaw);
            if (cachedObj && cachedObj.timestamp && (now - cachedObj.timestamp < expiryMs)) {
                return { data: cachedObj.data, fromCache: true };
            }
        }
    } catch (err) {
        console.warn(`Cache read failed for ${cacheKey}:`, err);
    }

    // 2. Fetch from Network
    try {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`HTTP Error ${response.status}: ${response.statusText}`);
        }
        const data = await response.json();

        // Save to cache
        try {
            localStorage.setItem(cacheKey, JSON.stringify({
                timestamp: now,
                data: data
            }));
        } catch (storageErr) {
            console.warn(`Cache write failed for ${cacheKey}:`, storageErr);
        }

        return { data: data, fromCache: false };
    } catch (fetchErr) {
        // Fallback: If fetch fails but stale cache exists, use it
        try {
            const cachedRaw = localStorage.getItem(cacheKey);
            if (cachedRaw) {
                const cachedObj = JSON.parse(cachedRaw);
                if (cachedObj && cachedObj.data) {
                    console.warn(`Fetch failed for ${url}. Using stale cache.`);
                    return { data: cachedObj.data, fromCache: true, stale: true };
                }
            }
        } catch (e) {
            // ignore
        }
        throw fetchErr;
    }
}

/* ==========================================================================
   MAIN DOMCONTENTLOADED EVENT HANDLER
   ========================================================================== */
document.addEventListener("DOMContentLoaded", function () {

    /* ----------------------------------------------------------------------
       1. LIGHT / DARK THEME SWITCHER
       ---------------------------------------------------------------------- */
    const themeButtons = document.querySelectorAll("#themeToggle, .theme-btn, .theme-toggle-btn");

    function updateThemeButtons(isDark) {
        themeButtons.forEach(btn => {
            if (isDark) {
                btn.innerHTML = '<i class="fa-solid fa-sun"></i> <span>Light Mode</span>';
                btn.setAttribute("aria-label", "Switch to Light Mode");
                btn.setAttribute("title", "Switch to Light Mode");
            } else {
                btn.innerHTML = '<i class="fa-solid fa-moon"></i> <span>Dark Mode</span>';
                btn.setAttribute("aria-label", "Switch to Dark Mode");
                btn.setAttribute("title", "Switch to Dark Mode");
            }
        });
    }

    const savedTheme = localStorage.getItem("theme");
    const isDarkActive = savedTheme === "dark";

    if (isDarkActive) {
        document.body.classList.add("dark-mode");
        document.documentElement.classList.add("dark-mode");
    } else {
        document.body.classList.remove("dark-mode");
        document.documentElement.classList.remove("dark-mode");
    }
    updateThemeButtons(isDarkActive);

    if (themeButtons.length > 0) {
        themeButtons.forEach(button => {
            button.addEventListener("click", function (e) {
                e.preventDefault();
                document.body.classList.toggle("dark-mode");
                document.documentElement.classList.toggle("dark-mode");

                const currentIsDark = document.body.classList.contains("dark-mode");
                localStorage.setItem("theme", currentIsDark ? "dark" : "light");
                updateThemeButtons(currentIsDark);
            });
        });
    }

    /* ----------------------------------------------------------------------
       2. HAMBURGER MENU & SIDEBAR DRAWER EVENT LISTENERS
       ---------------------------------------------------------------------- */
    const menuButtons = document.querySelectorAll(".menu-btn");
    const closeBtn = document.querySelector(".sidebar .close-btn");
    const overlay = document.getElementById("overlay");
    const sidebar = document.getElementById("sidebar");

    if (menuButtons.length > 0) {
        menuButtons.forEach(btn => {
            btn.removeAttribute("onclick");
            btn.setAttribute("role", "button");
            btn.setAttribute("aria-expanded", "false");
            btn.addEventListener("click", function (e) {
                e.preventDefault();
                e.stopPropagation();
                toggleMenu();
            });
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            closeMenu();
        });
    }

    if (overlay) {
        overlay.addEventListener("click", function (e) {
            e.preventDefault();
            closeMenu();
        });
    }

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && sidebar && sidebar.classList.contains("active")) {
            closeMenu();
        }
    });

    if (sidebar) {
        const navLinks = sidebar.querySelectorAll("a");
        navLinks.forEach(link => {
            link.addEventListener("click", function () {
                closeMenu();
            });
        });
    }

    /* ----------------------------------------------------------------------
       3. PRACTICAL 5: STUDENT REGISTRATION FORM VALIDATION & CANVAS CAPTCHA
       ---------------------------------------------------------------------- */
    const regForm = document.getElementById("registrationForm");

    if (regForm) {
        const fullNameInput = document.getElementById("regFullName");
        const emailInput = document.getElementById("regEmail");
        const mobileInput = document.getElementById("regMobile");
        const passwordInput = document.getElementById("regPassword");
        const confirmPasswordInput = document.getElementById("regConfirmPassword");
        const courseSelect = document.getElementById("regCourse");
        const yearSelect = document.getElementById("regYear");
        const termsCheckbox = document.getElementById("regTerms");
        const captchaInput = document.getElementById("regCaptcha");
        const captchaCanvas = document.getElementById("captchaCanvas");
        const refreshCaptchaBtn = document.getElementById("refreshCaptchaBtn");
        const successBanner = document.getElementById("registrationSuccess");

        // Error message containers
        const nameError = document.getElementById("nameError");
        const emailError = document.getElementById("emailError");
        const mobileError = document.getElementById("mobileError");
        const passwordError = document.getElementById("passwordError");
        const confirmPasswordError = document.getElementById("confirmPasswordError");
        const courseError = document.getElementById("courseError");
        const yearError = document.getElementById("yearError");
        const genderError = document.getElementById("genderError");
        const termsError = document.getElementById("termsError");
        const captchaError = document.getElementById("captchaError");

        // Password strength elements
        const passwordStrengthBox = document.getElementById("passwordStrengthBox");
        const strengthBar = document.getElementById("strengthBar");
        const strengthText = document.getElementById("strengthText");

        // Regular Expressions
        const nameRegex = /^[A-Za-z\s]{2,50}$/;
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        const mobileRegex = /^[6-9]\d{9}$/;
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#^_\-~])[A-Za-z\d@$!%*?&#^_\-~]{8,}$/;

        let generatedCaptchaCode = "";

        // --- HTML5 Canvas CAPTCHA Generator ---
        function generateCaptcha() {
            if (!captchaCanvas) return;
            const ctx = captchaCanvas.getContext("2d");
            const width = captchaCanvas.width;
            const height = captchaCanvas.height;

            // Character pool (exclude ambiguous 0/O, 1/I/l)
            const chars = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789";
            generatedCaptchaCode = "";
            for (let i = 0; i < 6; i++) {
                generatedCaptchaCode += chars.charAt(Math.floor(Math.random() * chars.length));
            }

            // Clear canvas background
            ctx.clearRect(0, 0, width, height);

            // Background Gradient
            const isDark = document.body.classList.contains("dark-mode");
            const grad = ctx.createLinearGradient(0, 0, width, height);
            if (isDark) {
                grad.addColorStop(0, "#1e293b");
                grad.addColorStop(1, "#0f172a");
            } else {
                grad.addColorStop(0, "#e8f1fc");
                grad.addColorStop(1, "#f1f5f9");
            }
            ctx.fillStyle = grad;
            ctx.fillRect(0, 0, width, height);

            // Draw noise lines
            for (let i = 0; i < 4; i++) {
                ctx.strokeStyle = `rgba(${Math.floor(Math.random()*150)}, ${Math.floor(Math.random()*150)}, ${Math.floor(Math.random()*200)}, 0.45)`;
                ctx.lineWidth = Math.random() * 2 + 1;
                ctx.beginPath();
                ctx.moveTo(Math.random() * width, Math.random() * height);
                ctx.lineTo(Math.random() * width, Math.random() * height);
                ctx.stroke();
            }

            // Draw noise dots
            for (let i = 0; i < 30; i++) {
                ctx.fillStyle = `rgba(${Math.floor(Math.random()*200)}, ${Math.floor(Math.random()*200)}, ${Math.floor(Math.random()*200)}, 0.5)`;
                ctx.beginPath();
                ctx.arc(Math.random() * width, Math.random() * height, Math.random() * 2, 0, Math.PI * 2);
                ctx.fill();
            }

            // Draw each CAPTCHA character with random rotation and styling
            ctx.font = "bold 22px 'Courier New', monospace";
            ctx.textBaseline = "middle";

            const letterSpacing = width / 7;
            for (let i = 0; i < generatedCaptchaCode.length; i++) {
                const char = generatedCaptchaCode[i];
                ctx.save();
                const x = (i + 1) * letterSpacing - 5;
                const y = height / 2 + (Math.random() * 6 - 3);
                const angle = (Math.random() * 30 - 15) * (Math.PI / 180);

                ctx.translate(x, y);
                ctx.rotate(angle);
                ctx.fillStyle = isDark ? "#38bdf8" : "#14599d";
                ctx.fillText(char, 0, 0);
                ctx.restore();
            }

            // Clear input and error on regenerate
            if (captchaInput) {
                captchaInput.value = "";
                clearFieldError(captchaInput, captchaError);
            }
        }

        // Initialize CAPTCHA
        generateCaptcha();

        if (refreshCaptchaBtn) {
            refreshCaptchaBtn.addEventListener("click", generateCaptcha);
        }
        if (captchaCanvas) {
            captchaCanvas.addEventListener("click", generateCaptcha);
        }

        // --- Password Strength Meter ---
        function evaluatePasswordStrength(password) {
            if (!password || password.length === 0) {
                return { score: 0, label: "None", class: "" };
            }
            let score = 0;
            if (password.length >= 8) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/\d/.test(password)) score++;
            if (/[@$!%*?&#^_\-~]/.test(password)) score++;

            if (score <= 2 || password.length < 8) {
                return { score: 1, label: "Weak (add letters, numbers & symbols)", class: "weak" };
            } else if (score === 3 || score === 4) {
                return { score: 2, label: "Medium (good, add special characters)", class: "medium" };
            } else {
                return { score: 3, label: "Strong (secure password)", class: "strong" };
            }
        }

        // --- Error Helpers ---
        function setFieldError(inputElement, errorElement, message) {
            if (errorElement) {
                errorElement.textContent = message;
            }
            if (inputElement && inputElement.classList) {
                inputElement.classList.add("input-error");
                inputElement.setAttribute("aria-invalid", "true");
            }
        }

        function clearFieldError(inputElement, errorElement) {
            if (errorElement) {
                errorElement.textContent = "";
            }
            if (inputElement && inputElement.classList) {
                inputElement.classList.remove("input-error");
                inputElement.setAttribute("aria-invalid", "false");
            }
        }

        function clearAllErrors() {
            const errorSpans = regForm.querySelectorAll(".error-msg");
            errorSpans.forEach(span => span.textContent = "");

            const errorInputs = regForm.querySelectorAll(".input-error");
            errorInputs.forEach(input => {
                input.classList.remove("input-error");
                input.setAttribute("aria-invalid", "false");
            });

            if (successBanner) {
                successBanner.style.display = "none";
            }
            if (passwordStrengthBox) {
                passwordStrengthBox.style.display = "none";
            }
        }

        // --- Real-Time Validation Listeners (keyup, input, change) ---
        if (fullNameInput) {
            fullNameInput.addEventListener("input", () => {
                const val = fullNameInput.value.trim();
                if (val !== "" && nameRegex.test(val)) {
                    clearFieldError(fullNameInput, nameError);
                }
            });
        }

        if (emailInput) {
            emailInput.addEventListener("input", () => {
                const val = emailInput.value.trim();
                if (val !== "" && emailRegex.test(val)) {
                    clearFieldError(emailInput, emailError);
                }
            });
        }

        if (mobileInput) {
            mobileInput.addEventListener("input", () => {
                const val = mobileInput.value.trim();
                if (mobileRegex.test(val)) {
                    clearFieldError(mobileInput, mobileError);
                }
            });
        }

        if (passwordInput) {
            passwordInput.addEventListener("input", () => {
                const val = passwordInput.value;
                if (passwordStrengthBox) {
                    if (val.length > 0) {
                        passwordStrengthBox.style.display = "block";
                        const strength = evaluatePasswordStrength(val);
                        strengthBar.className = "strength-bar-fill " + strength.class;
                        strengthText.className = "strength-text " + strength.class;
                        strengthText.textContent = "Strength: " + strength.label;
                    } else {
                        passwordStrengthBox.style.display = "none";
                    }
                }

                if (passwordRegex.test(val)) {
                    clearFieldError(passwordInput, passwordError);
                }
                if (confirmPasswordInput && confirmPasswordInput.value.length > 0) {
                    if (confirmPasswordInput.value === val) {
                        clearFieldError(confirmPasswordInput, confirmPasswordError);
                    }
                }
            });
        }

        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener("input", () => {
                if (confirmPasswordInput.value === passwordInput.value && confirmPasswordInput.value.trim() !== "") {
                    clearFieldError(confirmPasswordInput, confirmPasswordError);
                }
            });
        }

        if (courseSelect) {
            courseSelect.addEventListener("change", () => {
                if (courseSelect.value !== "") {
                    clearFieldError(courseSelect, courseError);
                }
            });
        }

        if (yearSelect) {
            yearSelect.addEventListener("change", () => {
                if (yearSelect.value !== "") {
                    clearFieldError(yearSelect, yearError);
                }
            });
        }

        const genderRadios = regForm.querySelectorAll("input[name='gender']");
        genderRadios.forEach(radio => {
            radio.addEventListener("change", () => {
                clearFieldError(null, genderError);
            });
        });

        if (termsCheckbox) {
            termsCheckbox.addEventListener("change", () => {
                if (termsCheckbox.checked) {
                    clearFieldError(null, termsError);
                }
            });
        }

        if (captchaInput) {
            captchaInput.addEventListener("input", () => {
                if (captchaInput.value.trim().toUpperCase() === generatedCaptchaCode.toUpperCase()) {
                    clearFieldError(captchaInput, captchaError);
                }
            });
        }

        // --- Form Submit Validation Handler ---
        regForm.addEventListener("submit", function (e) {
            e.preventDefault();
            let isValid = true;
            let firstInvalidElem = null;

            // 1. Full Name Validation
            const nameVal = fullNameInput ? fullNameInput.value.trim() : "";
            if (nameVal === "") {
                setFieldError(fullNameInput, nameError, "Please enter your full name.");
                if (!firstInvalidElem) firstInvalidElem = fullNameInput;
                isValid = false;
            } else if (!nameRegex.test(nameVal)) {
                setFieldError(fullNameInput, nameError, "Please enter a valid name (minimum 2 letters, no numbers or special symbols).");
                if (!firstInvalidElem) firstInvalidElem = fullNameInput;
                isValid = false;
            } else {
                clearFieldError(fullNameInput, nameError);
            }

            // 2. Email Address Validation
            const emailVal = emailInput ? emailInput.value.trim() : "";
            if (emailVal === "") {
                setFieldError(emailInput, emailError, "Please enter your email address.");
                if (!firstInvalidElem) firstInvalidElem = emailInput;
                isValid = false;
            } else if (!emailRegex.test(emailVal)) {
                setFieldError(emailInput, emailError, "Please enter a valid email address (e.g. student@example.com).");
                if (!firstInvalidElem) firstInvalidElem = emailInput;
                isValid = false;
            } else {
                clearFieldError(emailInput, emailError);
            }

            // 3. Mobile Number Validation
            const mobileVal = mobileInput ? mobileInput.value.trim() : "";
            if (mobileVal === "") {
                setFieldError(mobileInput, mobileError, "Please enter your mobile number.");
                if (!firstInvalidElem) firstInvalidElem = mobileInput;
                isValid = false;
            } else if (!mobileRegex.test(mobileVal)) {
                setFieldError(mobileInput, mobileError, "Please enter a valid 10-digit Indian mobile number starting with 6, 7, 8, or 9.");
                if (!firstInvalidElem) firstInvalidElem = mobileInput;
                isValid = false;
            } else {
                clearFieldError(mobileInput, mobileError);
            }

            // 4. Password Validation
            const passVal = passwordInput ? passwordInput.value : "";
            if (passVal === "") {
                setFieldError(passwordInput, passwordError, "Please enter a password.");
                if (!firstInvalidElem) firstInvalidElem = passwordInput;
                isValid = false;
            } else if (!passwordRegex.test(passVal)) {
                setFieldError(passwordInput, passwordError, "Password must be at least 8 characters with uppercase, lowercase, number, and special character.");
                if (!firstInvalidElem) firstInvalidElem = passwordInput;
                isValid = false;
            } else {
                clearFieldError(passwordInput, passwordError);
            }

            // 5. Confirm Password Validation
            const confirmVal = confirmPasswordInput ? confirmPasswordInput.value : "";
            if (confirmVal === "") {
                setFieldError(confirmPasswordInput, confirmPasswordError, "Please confirm your password.");
                if (!firstInvalidElem) firstInvalidElem = confirmPasswordInput;
                isValid = false;
            } else if (confirmVal !== passVal) {
                setFieldError(confirmPasswordInput, confirmPasswordError, "Passwords do not match.");
                if (!firstInvalidElem) firstInvalidElem = confirmPasswordInput;
                isValid = false;
            } else {
                clearFieldError(confirmPasswordInput, confirmPasswordError);
            }

            // 6. Course Validation
            const courseVal = courseSelect ? courseSelect.value : "";
            if (courseVal === "") {
                setFieldError(courseSelect, courseError, "Please select your course.");
                if (!firstInvalidElem) firstInvalidElem = courseSelect;
                isValid = false;
            } else {
                clearFieldError(courseSelect, courseError);
            }

            // 7. Year Validation
            const yearVal = yearSelect ? yearSelect.value : "";
            if (yearVal === "") {
                setFieldError(yearSelect, yearError, "Please select your year of study.");
                if (!firstInvalidElem) firstInvalidElem = yearSelect;
                isValid = false;
            } else {
                clearFieldError(yearSelect, yearError);
            }

            // 8. Gender Validation
            const selectedGender = regForm.querySelector("input[name='gender']:checked");
            if (!selectedGender) {
                setFieldError(null, genderError, "Please select your gender.");
                if (!firstInvalidElem) firstInvalidElem = genderRadios[0];
                isValid = false;
            } else {
                clearFieldError(null, genderError);
            }

            // 9. Canvas CAPTCHA Validation
            const enteredCaptcha = captchaInput ? captchaInput.value.trim() : "";
            if (enteredCaptcha === "") {
                setFieldError(captchaInput, captchaError, "Please enter the CAPTCHA code shown above.");
                if (!firstInvalidElem) firstInvalidElem = captchaInput;
                isValid = false;
            } else if (enteredCaptcha.toUpperCase() !== generatedCaptchaCode.toUpperCase()) {
                setFieldError(captchaInput, captchaError, "Incorrect CAPTCHA code. Please try the new code.");
                generateCaptcha();
                if (!firstInvalidElem) firstInvalidElem = captchaInput;
                isValid = false;
            } else {
                clearFieldError(captchaInput, captchaError);
            }

            // 10. Terms and Conditions Validation
            if (termsCheckbox && !termsCheckbox.checked) {
                setFieldError(null, termsError, "Please accept the Terms and Conditions to proceed.");
                if (!firstInvalidElem) firstInvalidElem = termsCheckbox;
                isValid = false;
            } else {
                clearFieldError(null, termsError);
            }

            // Focus management for accessibility
            if (!isValid && firstInvalidElem) {
                firstInvalidElem.focus();
                return;
            }

            // Successful Submission Handling
            if (isValid) {
                if (successBanner) {
                    successBanner.innerHTML = `<i class="fa-solid fa-circle-check"></i> Registration successful for <strong>${nameVal}</strong>! Redirecting to login...`;
                    successBanner.style.display = "block";
                    successBanner.scrollIntoView({ behavior: "smooth", block: "center" });
                }

                regForm.reset();
                clearAllErrors();
                generateCaptcha();
                if (successBanner) successBanner.style.display = "block";

                setTimeout(() => {
                    window.location.href = "Login.html";
                }, 2200);
            }
        });

        // Form Reset Handler
        regForm.addEventListener("reset", function () {
            setTimeout(() => {
                clearAllErrors();
                generateCaptcha();
            }, 10);
        });
    }

    /* ----------------------------------------------------------------------
       4. PRACTICAL 5: LOGIN, FORGOT PASSWORD & FORGOT USERNAME VALIDATION
       ---------------------------------------------------------------------- */
    // --- Login Form ---
    const loginForm = document.getElementById("loginForm");
    if (loginForm) {
        const idInput = document.getElementById("loginStudentId");
        const passInput = document.getElementById("loginPassword");
        const idError = document.getElementById("loginIdError");
        const passError = document.getElementById("loginPasswordError");
        const loginStatus = document.getElementById("loginStatus");

        if (idInput) {
            idInput.addEventListener("input", () => {
                if (idInput.value.trim() !== "") {
                    idError.textContent = "";
                    idInput.classList.remove("input-error");
                }
            });
        }
        if (passInput) {
            passInput.addEventListener("input", () => {
                if (passInput.value !== "") {
                    passError.textContent = "";
                    passInput.classList.remove("input-error");
                }
            });
        }

        loginForm.addEventListener("submit", function (e) {
            e.preventDefault();
            let valid = true;

            if (!idInput || idInput.value.trim() === "") {
                if (idError) idError.textContent = "Please enter your Student ID or Username.";
                if (idInput) idInput.classList.add("input-error");
                valid = false;
            }
            if (!passInput || passInput.value === "") {
                if (passError) passError.textContent = "Please enter your password.";
                if (passInput) passInput.classList.add("input-error");
                valid = false;
            }

            if (valid) {
                if (loginStatus) {
                    loginStatus.innerHTML = '<i class="fa-solid fa-circle-check"></i> Login verified! Redirecting to dashboard...';
                    loginStatus.style.display = "block";
                }
                setTimeout(() => {
                    window.location.href = "home.html";
                }, 1200);
            }
        });
    }

    // --- Forgot Password Form ---
    const fpForm = document.getElementById("forgotPasswordForm");
    if (fpForm) {
        const fpIdInput = document.getElementById("fpStudentId");
        const fpEmailInput = document.getElementById("fpEmail");
        const fpIdError = document.getElementById("fpStudentIdError");
        const fpEmailError = document.getElementById("fpEmailError");
        const fpStatus = document.getElementById("forgotPasswordStatus");
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

        fpForm.addEventListener("submit", function (e) {
            e.preventDefault();
            let valid = true;

            if (!fpIdInput || fpIdInput.value.trim() === "") {
                if (fpIdError) fpIdError.textContent = "Please enter your Student ID.";
                if (fpIdInput) fpIdInput.classList.add("input-error");
                valid = false;
            } else {
                if (fpIdError) fpIdError.textContent = "";
                if (fpIdInput) fpIdInput.classList.remove("input-error");
            }

            const emailVal = fpEmailInput ? fpEmailInput.value.trim() : "";
            if (emailVal === "" || !emailRegex.test(emailVal)) {
                if (fpEmailError) fpEmailError.textContent = "Please enter a valid registered email address.";
                if (fpEmailInput) fpEmailInput.classList.add("input-error");
                valid = false;
            } else {
                if (fpEmailError) fpEmailError.textContent = "";
                if (fpEmailInput) fpEmailInput.classList.remove("input-error");
            }

            if (valid) {
                if (fpStatus) {
                    fpStatus.innerHTML = `<i class="fa-solid fa-circle-check"></i> Password reset link sent to <strong>${emailVal}</strong>! Check your inbox.`;
                    fpStatus.style.display = "block";
                }
                fpForm.reset();
            }
        });
    }

    // --- Forgot Username Form ---
    const fuForm = document.getElementById("forgotUsernameForm");
    if (fuForm) {
        const fuEmailInput = document.getElementById("fuEmail");
        const fuPhoneInput = document.getElementById("fuPhone");
        const fuEmailError = document.getElementById("fuEmailError");
        const fuPhoneError = document.getElementById("fuPhoneError");
        const fuStatus = document.getElementById("forgotUsernameStatus");
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        const mobileRegex = /^[6-9]\d{9}$/;

        fuForm.addEventListener("submit", function (e) {
            e.preventDefault();
            let valid = true;

            const emailVal = fuEmailInput ? fuEmailInput.value.trim() : "";
            if (emailVal === "" || !emailRegex.test(emailVal)) {
                if (fuEmailError) fuEmailError.textContent = "Please enter a valid registered email address.";
                if (fuEmailInput) fuEmailInput.classList.add("input-error");
                valid = false;
            } else {
                if (fuEmailError) fuEmailError.textContent = "";
                if (fuEmailInput) fuEmailInput.classList.remove("input-error");
            }

            const phoneVal = fuPhoneInput ? fuPhoneInput.value.trim() : "";
            if (phoneVal === "" || !mobileRegex.test(phoneVal)) {
                if (fuPhoneError) fuPhoneError.textContent = "Enter a valid 10-digit mobile number starting with 6, 7, 8, or 9.";
                if (fuPhoneInput) fuPhoneInput.classList.add("input-error");
                valid = false;
            } else {
                if (fuPhoneError) fuPhoneError.textContent = "";
                if (fuPhoneInput) fuPhoneInput.classList.remove("input-error");
            }

            if (valid) {
                if (fuStatus) {
                    fuStatus.innerHTML = `<i class="fa-solid fa-circle-check"></i> Your Student ID is: <strong>25CS035</strong>. SMS confirmation sent!`;
                    fuStatus.style.display = "block";
                }
                fuForm.reset();
            }
        });
    }

    /* ----------------------------------------------------------------------
       5. PRACTICAL 6: EVENTS DIRECTORY (events.json on notice.html)
       ---------------------------------------------------------------------- */
    const eventsSection = document.getElementById("eventsSection");

    if (eventsSection) {
        const searchInput = document.getElementById("eventSearchInput");
        const categoryFilter = document.getElementById("eventCategoryFilter");
        const sortSelect = document.getElementById("eventSortSelect");
        const resetBtn = document.getElementById("eventResetFilterBtn");
        const grid = document.getElementById("eventsGrid");
        const loadingBox = document.getElementById("eventsLoading");
        const errorBox = document.getElementById("eventsError");
        const emptyBox = document.getElementById("eventsEmpty");
        const resultsCount = document.getElementById("eventResultsCount");
        const cacheBadge = document.getElementById("eventCacheBadge");
        const paginationBox = document.getElementById("eventsPagination");
        const paginationControls = document.getElementById("eventsPaginationControls");
        const paginationSummary = document.getElementById("eventsPaginationSummary");
        const retryBtn = document.getElementById("eventsRetryBtn");

        let allEvents = [];
        let currentPage = 1;
        const itemsPerPage = 6;

        async function loadEventsData() {
            if (loadingBox) loadingBox.style.display = "block";
            if (errorBox) errorBox.style.display = "none";
            if (emptyBox) emptyBox.style.display = "none";
            if (grid) grid.innerHTML = "";
            if (paginationBox) paginationBox.style.display = "none";

            try {
                const { data, fromCache, stale } = await fetchWithCache("events.json", "studenthub_events_cache", 30);
                allEvents = Array.isArray(data) ? data : [];

                if (loadingBox) loadingBox.style.display = "none";
                if (cacheBadge) {
                    if (stale) {
                        cacheBadge.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Offline Cache';
                    } else if (fromCache) {
                        cacheBadge.innerHTML = '<i class="fa-solid fa-box-archive"></i> Cached';
                    } else {
                        cacheBadge.innerHTML = '<i class="fa-solid fa-bolt"></i> Live Feed';
                    }
                }

                processAndRenderEvents();
            } catch (err) {
                console.error("Error fetching events.json:", err);
                if (loadingBox) loadingBox.style.display = "none";
                if (errorBox) errorBox.style.display = "block";
                if (resultsCount) resultsCount.textContent = "Error loading events.";
            }
        }

        function processAndRenderEvents() {
            if (!grid) return;

            // 1. Search
            const keyword = searchInput ? searchInput.value.trim().toLowerCase() : "";
            let filtered = allEvents.filter(ev => {
                if (!keyword) return true;
                const matchTitle = (ev.title || "").toLowerCase().includes(keyword);
                const matchCat = (ev.category || "").toLowerCase().includes(keyword);
                const matchLoc = (ev.location || "").toLowerCase().includes(keyword);
                const matchSpeaker = (ev.speaker || "").toLowerCase().includes(keyword);
                const matchDesc = (ev.description || "").toLowerCase().includes(keyword);
                return matchTitle || matchCat || matchLoc || matchSpeaker || matchDesc;
            });

            // 2. Category Filter
            const catVal = categoryFilter ? categoryFilter.value : "";
            if (catVal) {
                filtered = filtered.filter(ev => ev.category === catVal);
            }

            // 3. Sort
            const sortVal = sortSelect ? sortSelect.value : "date-asc";
            filtered.sort((a, b) => {
                if (sortVal === "date-asc") return new Date(a.date) - new Date(b.date);
                if (sortVal === "date-desc") return new Date(b.date) - new Date(a.date);
                if (sortVal === "title-asc") return (a.title || "").localeCompare(b.title || "");
                if (sortVal === "title-desc") return (b.title || "").localeCompare(a.title || "");
                return 0;
            });

            // Update result count
            if (resultsCount) {
                resultsCount.textContent = `Showing ${filtered.length} of ${allEvents.length} events`;
            }

            // Handle empty state
            if (filtered.length === 0) {
                grid.innerHTML = "";
                if (emptyBox) emptyBox.style.display = "block";
                if (paginationBox) paginationBox.style.display = "none";
                return;
            } else {
                if (emptyBox) emptyBox.style.display = "none";
            }

            // 4. Pagination
            const totalPages = Math.ceil(filtered.length / itemsPerPage) || 1;
            if (currentPage > totalPages) currentPage = 1;

            const startIndex = (currentPage - 1) * itemsPerPage;
            const paginatedItems = filtered.slice(startIndex, startIndex + itemsPerPage);

            // 5. Dynamic HTML Rendering
            grid.innerHTML = paginatedItems.map(ev => `
                <article class="event-card">
                    <div>
                        <div class="event-card-header">
                            <span class="event-category-badge">${ev.category || "Event"}</span>
                            <span class="event-date-badge"><i class="fa-regular fa-calendar"></i> ${ev.date}</span>
                        </div>
                        <h3>${ev.title}</h3>
                        <p>${ev.description}</p>
                    </div>
                    <div>
                        <div class="event-card-meta">
                            <span><i class="fa-regular fa-clock"></i> ${ev.time || "TBA"}</span>
                            <span><i class="fa-solid fa-location-dot"></i> ${ev.location || "Campus Venue"}</span>
                            <span><i class="fa-solid fa-chalkboard-user"></i> ${ev.speaker || "Faculty/Speaker"}</span>
                        </div>
                        <div class="event-card-footer">
                            <span style="font-size: 12px; font-weight: 700; color: #15803d;"><i class="fa-solid fa-users"></i> ${ev.seats || 50} Seats</span>
                            <button type="button" class="small-btn view-notice-btn"
                                    data-title="${ev.title}"
                                    data-date="${ev.date} (${ev.time})"
                                    data-content="Location: ${ev.location} | Speaker: ${ev.speaker} | Description: ${ev.description}">
                                <i class="fa-solid fa-circle-info"></i> Details
                            </button>
                        </div>
                    </div>
                </article>
            `).join("");

            // Attach modal click listeners to newly rendered detail buttons
            const newNoticeBtns = grid.querySelectorAll(".view-notice-btn");
            newNoticeBtns.forEach(btn => {
                btn.addEventListener("click", function (e) {
                    e.preventDefault();
                    const title = this.getAttribute("data-title");
                    const date = this.getAttribute("data-date");
                    const content = this.getAttribute("data-content");
                    const noticeModal = document.getElementById("noticeModal");
                    if (noticeModal) {
                        document.getElementById("modalNoticeTitle").textContent = title;
                        document.getElementById("modalNoticeDate").textContent = date;
                        document.getElementById("modalNoticeBody").textContent = content;
                        noticeModal.classList.add("active");
                        document.body.style.overflow = "hidden";
                    }
                });
            });

            // 6. Render Pagination Controls
            if (paginationBox && paginationControls) {
                if (totalPages > 1) {
                    paginationBox.style.display = "flex";
                    if (paginationSummary) {
                        paginationSummary.textContent = `Page ${currentPage} of ${totalPages} (${filtered.length} total)`;
                    }

                    let btnsHtml = `
                        <button type="button" class="pagination-btn prev-page" ${currentPage === 1 ? 'disabled' : ''} title="Previous Page">&laquo; Prev</button>
                    `;
                    for (let i = 1; i <= totalPages; i++) {
                        btnsHtml += `
                            <button type="button" class="pagination-btn page-num ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>
                        `;
                    }
                    btnsHtml += `
                        <button type="button" class="pagination-btn next-page" ${currentPage === totalPages ? 'disabled' : ''} title="Next Page">Next &raquo;</button>
                    `;

                    paginationControls.innerHTML = btnsHtml;

                    paginationControls.querySelector(".prev-page")?.addEventListener("click", () => {
                        if (currentPage > 1) {
                            currentPage--;
                            processAndRenderEvents();
                        }
                    });

                    paginationControls.querySelector(".next-page")?.addEventListener("click", () => {
                        if (currentPage < totalPages) {
                            currentPage++;
                            processAndRenderEvents();
                        }
                    });

                    paginationControls.querySelectorAll(".page-num").forEach(numBtn => {
                        numBtn.addEventListener("click", function () {
                            currentPage = parseInt(this.getAttribute("data-page"), 10);
                            processAndRenderEvents();
                        });
                    });
                } else {
                    paginationBox.style.display = "none";
                }
            }
        }

        // Search & Filter event listeners
        if (searchInput) {
            searchInput.addEventListener("input", () => {
                currentPage = 1;
                processAndRenderEvents();
            });
        }
        if (categoryFilter) {
            categoryFilter.addEventListener("change", () => {
                currentPage = 1;
                processAndRenderEvents();
            });
        }
        if (sortSelect) {
            sortSelect.addEventListener("change", () => {
                currentPage = 1;
                processAndRenderEvents();
            });
        }
        if (resetBtn) {
            resetBtn.addEventListener("click", () => {
                if (searchInput) searchInput.value = "";
                if (categoryFilter) categoryFilter.value = "";
                if (sortSelect) sortSelect.value = "date-asc";
                currentPage = 1;
                processAndRenderEvents();
            });
        }
        if (retryBtn) {
            retryBtn.addEventListener("click", loadEventsData);
        }

        loadEventsData();
    }

    /* ----------------------------------------------------------------------
       6. PRACTICAL 6: FAQS DIRECTORY (faqs.json on contactus.html)
       ---------------------------------------------------------------------- */
    const faqSection = document.getElementById("faqSection");

    if (faqSection) {
        const searchInput = document.getElementById("faqSearchInput");
        const categoryFilter = document.getElementById("faqCategoryFilter");
        const sortSelect = document.getElementById("faqSortSelect");
        const resetBtn = document.getElementById("faqResetBtn");
        const container = document.getElementById("dynamicFaqContainer");
        const loadingBox = document.getElementById("faqLoading");
        const errorBox = document.getElementById("faqError");
        const emptyBox = document.getElementById("faqEmpty");
        const resultsCount = document.getElementById("faqResultsCount");
        const cacheBadge = document.getElementById("faqCacheBadge");
        const retryBtn = document.getElementById("faqRetryBtn");

        let allFaqs = [];

        async function loadFaqsData() {
            if (loadingBox) loadingBox.style.display = "block";
            if (errorBox) errorBox.style.display = "none";
            if (emptyBox) emptyBox.style.display = "none";
            if (container) container.innerHTML = "";

            try {
                const { data, fromCache, stale } = await fetchWithCache("faqs.json", "studenthub_faqs_cache", 30);
                allFaqs = Array.isArray(data) ? data : [];

                if (loadingBox) loadingBox.style.display = "none";
                if (cacheBadge) {
                    if (stale) {
                        cacheBadge.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Offline Cache';
                    } else if (fromCache) {
                        cacheBadge.innerHTML = '<i class="fa-solid fa-box-archive"></i> Cached';
                    } else {
                        cacheBadge.innerHTML = '<i class="fa-solid fa-bolt"></i> Live Feed';
                    }
                }

                processAndRenderFaqs();
            } catch (err) {
                console.error("Error fetching faqs.json:", err);
                if (loadingBox) loadingBox.style.display = "none";
                if (errorBox) errorBox.style.display = "block";
                if (resultsCount) resultsCount.textContent = "Error loading FAQs.";
            }
        }

        function processAndRenderFaqs() {
            if (!container) return;

            // 1. Search
            const keyword = searchInput ? searchInput.value.trim().toLowerCase() : "";
            let filtered = allFaqs.filter(item => {
                if (!keyword) return true;
                const matchQ = (item.question || "").toLowerCase().includes(keyword);
                const matchA = (item.answer || "").toLowerCase().includes(keyword);
                const matchC = (item.category || "").toLowerCase().includes(keyword);
                return matchQ || matchA || matchC;
            });

            // 2. Category Filter
            const catVal = categoryFilter ? categoryFilter.value : "";
            if (catVal) {
                filtered = filtered.filter(item => item.category === catVal);
            }

            // 3. Sort
            const sortVal = sortSelect ? sortSelect.value : "default";
            if (sortVal === "question-asc") {
                filtered.sort((a, b) => (a.question || "").localeCompare(b.question || ""));
            } else if (sortVal === "question-desc") {
                filtered.sort((a, b) => (b.question || "").localeCompare(a.question || ""));
            }

            // Update count
            if (resultsCount) {
                resultsCount.textContent = `Showing ${filtered.length} of ${allFaqs.length} questions`;
            }

            if (filtered.length === 0) {
                container.innerHTML = "";
                if (emptyBox) emptyBox.style.display = "block";
                return;
            } else {
                if (emptyBox) emptyBox.style.display = "none";
            }

            // Render Dynamic Collapsible Accordion
            container.innerHTML = filtered.map(item => `
                <div class="faq-item">
                    <button class="faq-question" type="button" aria-expanded="false">
                        <span><span class="faq-category-tag">${item.category || "General"}</span> ${item.question}</span>
                        <i class="fa-solid fa-chevron-down faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        <p>${item.answer}</p>
                    </div>
                </div>
            `).join("");

            // Re-attach toggle listeners
            container.querySelectorAll(".faq-question").forEach(button => {
                button.addEventListener("click", function () {
                    const parent = this.closest(".faq-item");
                    const isActive = parent.classList.contains("active");

                    if (isActive) {
                        parent.classList.remove("active");
                        this.setAttribute("aria-expanded", "false");
                    } else {
                        parent.classList.add("active");
                        this.setAttribute("aria-expanded", "true");
                    }
                });
            });
        }

        if (searchInput) searchInput.addEventListener("input", processAndRenderFaqs);
        if (categoryFilter) categoryFilter.addEventListener("change", processAndRenderFaqs);
        if (sortSelect) sortSelect.addEventListener("change", processAndRenderFaqs);
        if (resetBtn) {
            resetBtn.addEventListener("click", () => {
                if (searchInput) searchInput.value = "";
                if (categoryFilter) categoryFilter.value = "";
                if (sortSelect) sortSelect.value = "default";
                processAndRenderFaqs();
            });
        }
        if (retryBtn) retryBtn.addEventListener("click", loadFaqsData);

        loadFaqsData();
    }

    /* ----------------------------------------------------------------------
       7. PRACTICAL 6: STUDENT DIRECTORY & DEPENDENT DROPDOWNS (Admin.html)
       ---------------------------------------------------------------------- */
    const studentsSection = document.getElementById("studentsSection");

    if (studentsSection) {
        const searchInput = document.getElementById("studentSearchInput");
        const courseFilter = document.getElementById("studentCourseFilter");
        const yearFilter = document.getElementById("studentYearFilter");
        const countryFilter = document.getElementById("studentCountryFilter");
        const stateFilter = document.getElementById("studentStateFilter");
        const cityFilter = document.getElementById("studentCityFilter");
        const sortSelect = document.getElementById("studentSortSelect");
        const resetBtn = document.getElementById("studentResetBtn");
        const table = document.getElementById("studentsTable");
        const tbody = document.getElementById("studentsTableBody");
        const loadingBox = document.getElementById("studentsLoading");
        const errorBox = document.getElementById("studentsError");
        const emptyBox = document.getElementById("studentsEmpty");
        const resultsCount = document.getElementById("studentResultsCount");
        const cacheBadge = document.getElementById("studentCacheBadge");
        const paginationBox = document.getElementById("studentsPagination");
        const paginationControls = document.getElementById("studentsPaginationControls");
        const paginationSummary = document.getElementById("studentsPaginationSummary");
        const retryBtn = document.getElementById("studentsRetryBtn");

        let allStudents = [];
        let currentPage = 1;
        const itemsPerPage = 6;

        // --- Dependent Dropdown Data Model (Country -> State -> City) ---
        const locationData = {
            "India": {
                "Gujarat": ["Ahmedabad", "Surat", "Vadodara", "Rajkot"],
                "Maharashtra": ["Mumbai", "Pune", "Nagpur"],
                "Rajasthan": ["Jaipur", "Udaipur"],
                "Karnataka": ["Bangalore", "Mysore"],
                "Delhi": ["New Delhi"]
            },
            "USA": {
                "California": ["San Francisco", "Los Angeles"],
                "Texas": ["Austin", "Houston"]
            },
            "Canada": {
                "Ontario": ["Toronto", "Ottawa"],
                "British Columbia": ["Vancouver", "Victoria"]
            }
        };

        // Populate State Dropdown based on Country
        function handleCountryChange() {
            const selectedCountry = countryFilter ? countryFilter.value : "";
            if (!stateFilter || !cityFilter) return;

            stateFilter.innerHTML = '<option value="">-- All States --</option>';
            cityFilter.innerHTML = '<option value="">-- All Cities --</option>';

            if (selectedCountry && locationData[selectedCountry]) {
                stateFilter.disabled = false;
                cityFilter.disabled = true;

                const states = Object.keys(locationData[selectedCountry]);
                states.forEach(st => {
                    const opt = document.createElement("option");
                    opt.value = st;
                    opt.textContent = st;
                    stateFilter.appendChild(opt);
                });
            } else {
                stateFilter.disabled = true;
                cityFilter.disabled = true;
            }
        }

        // Populate City Dropdown based on State
        function handleStateChange() {
            const selectedCountry = countryFilter ? countryFilter.value : "";
            const selectedState = stateFilter ? stateFilter.value : "";
            if (!cityFilter) return;

            cityFilter.innerHTML = '<option value="">-- All Cities --</option>';

            if (selectedCountry && selectedState && locationData[selectedCountry] && locationData[selectedCountry][selectedState]) {
                cityFilter.disabled = false;
                const cities = locationData[selectedCountry][selectedState];
                cities.forEach(ct => {
                    const opt = document.createElement("option");
                    opt.value = ct;
                    opt.textContent = ct;
                    cityFilter.appendChild(opt);
                });
            } else {
                cityFilter.disabled = true;
            }
        }

        if (countryFilter) {
            countryFilter.addEventListener("change", () => {
                handleCountryChange();
                currentPage = 1;
                processAndRenderStudents();
            });
        }

        if (stateFilter) {
            stateFilter.addEventListener("change", () => {
                handleStateChange();
                currentPage = 1;
                processAndRenderStudents();
            });
        }

        if (cityFilter) {
            cityFilter.addEventListener("change", () => {
                currentPage = 1;
                processAndRenderStudents();
            });
        }

        async function loadStudentsData() {
            if (loadingBox) loadingBox.style.display = "block";
            if (errorBox) errorBox.style.display = "none";
            if (emptyBox) emptyBox.style.display = "none";
            if (table) table.style.display = "none";
            if (paginationBox) paginationBox.style.display = "none";

            try {
                const { data, fromCache, stale } = await fetchWithCache("students.json", "studenthub_students_cache", 30);
                allStudents = Array.isArray(data) ? data : [];

                if (loadingBox) loadingBox.style.display = "none";
                if (cacheBadge) {
                    if (stale) {
                        cacheBadge.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Offline Cache';
                    } else if (fromCache) {
                        cacheBadge.innerHTML = '<i class="fa-solid fa-box-archive"></i> Cached';
                    } else {
                        cacheBadge.innerHTML = '<i class="fa-solid fa-bolt"></i> Live Feed';
                    }
                }

                processAndRenderStudents();
            } catch (err) {
                console.error("Error fetching students.json:", err);
                if (loadingBox) loadingBox.style.display = "none";
                if (errorBox) errorBox.style.display = "block";
                if (resultsCount) resultsCount.textContent = "Error loading students.";
            }
        }

        function processAndRenderStudents() {
            if (!tbody || !table) return;

            // 1. Search Keyword Match
            const keyword = searchInput ? searchInput.value.trim().toLowerCase() : "";
            let filtered = allStudents.filter(st => {
                if (!keyword) return true;
                const matchName = (st.name || "").toLowerCase().includes(keyword);
                const matchId = (st.studentId || "").toLowerCase().includes(keyword);
                const matchEmail = (st.email || "").toLowerCase().includes(keyword);
                const matchCity = (st.city || "").toLowerCase().includes(keyword);
                const matchDept = (st.department || "").toLowerCase().includes(keyword);
                return matchName || matchId || matchEmail || matchCity || matchDept;
            });

            // 2. Course Filter
            const courseVal = courseFilter ? courseFilter.value : "";
            if (courseVal) {
                filtered = filtered.filter(st => st.course === courseVal);
            }

            // 3. Year Filter
            const yearVal = yearFilter ? yearFilter.value : "";
            if (yearVal) {
                filtered = filtered.filter(st => st.year === yearVal);
            }

            // 4. Dependent Location Filters
            const countryVal = countryFilter ? countryFilter.value : "";
            if (countryVal) {
                filtered = filtered.filter(st => st.country === countryVal);
            }

            const stateVal = stateFilter ? stateFilter.value : "";
            if (stateVal) {
                filtered = filtered.filter(st => st.state === stateVal);
            }

            const cityVal = cityFilter ? cityFilter.value : "";
            if (cityVal) {
                filtered = filtered.filter(st => st.city === cityVal);
            }

            // 5. Sort
            const sortVal = sortSelect ? sortSelect.value : "name-asc";
            filtered.sort((a, b) => {
                if (sortVal === "name-asc") return (a.name || "").localeCompare(b.name || "");
                if (sortVal === "name-desc") return (b.name || "").localeCompare(a.name || "");
                if (sortVal === "cgpa-desc") return (b.cgpa || 0) - (a.cgpa || 0);
                if (sortVal === "cgpa-asc") return (a.cgpa || 0) - (b.cgpa || 0);
                if (sortVal === "id-asc") return (a.studentId || "").localeCompare(b.studentId || "");
                return 0;
            });

            // Update count badge
            if (resultsCount) {
                resultsCount.textContent = `Showing ${filtered.length} of ${allStudents.length} student records`;
            }

            // Handle empty state
            if (filtered.length === 0) {
                table.style.display = "none";
                if (emptyBox) emptyBox.style.display = "block";
                if (paginationBox) paginationBox.style.display = "none";
                return;
            } else {
                table.style.display = "table";
                if (emptyBox) emptyBox.style.display = "none";
            }

            // 6. Pagination
            const totalPages = Math.ceil(filtered.length / itemsPerPage) || 1;
            if (currentPage > totalPages) currentPage = 1;

            const startIndex = (currentPage - 1) * itemsPerPage;
            const paginatedItems = filtered.slice(startIndex, startIndex + itemsPerPage);

            // 7. Dynamic Table Rows Rendering
            tbody.innerHTML = paginatedItems.map(st => `
                <tr>
                    <td><strong>${st.id}</strong></td>
                    <td style="text-align: left; font-weight: 600; color: var(--primary-color);">
                        ${st.name}<br>
                        <span style="font-size: 12px; font-weight: normal; color: var(--text-muted);">${st.email}</span>
                    </td>
                    <td><code>${st.studentId}</code></td>
                    <td>${st.course}<br><small style="color: var(--text-muted);">${st.year}</small></td>
                    <td>${st.department}</td>
                    <td>${st.city}, ${st.state}<br><small style="color: var(--text-muted);">${st.country}</small></td>
                    <td><span style="font-weight: 700; color: ${st.cgpa >= 9.0 ? '#15803d' : '#1d4ed8'};">${Number(st.cgpa).toFixed(2)}</span></td>
                    <td><span style="background: var(--success-bg); color: var(--success-text); padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 700;">${st.status || "Active"}</span></td>
                </tr>
            `).join("");

            // 8. Pagination Controls
            if (paginationBox && paginationControls) {
                if (totalPages > 1) {
                    paginationBox.style.display = "flex";
                    if (paginationSummary) {
                        paginationSummary.textContent = `Page ${currentPage} of ${totalPages} (${filtered.length} total records)`;
                    }

                    let btnsHtml = `
                        <button type="button" class="pagination-btn prev-page" ${currentPage === 1 ? 'disabled' : ''} title="Previous Page">&laquo; Prev</button>
                    `;
                    for (let i = 1; i <= totalPages; i++) {
                        btnsHtml += `
                            <button type="button" class="pagination-btn page-num ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>
                        `;
                    }
                    btnsHtml += `
                        <button type="button" class="pagination-btn next-page" ${currentPage === totalPages ? 'disabled' : ''} title="Next Page">Next &raquo;</button>
                    `;

                    paginationControls.innerHTML = btnsHtml;

                    paginationControls.querySelector(".prev-page")?.addEventListener("click", () => {
                        if (currentPage > 1) {
                            currentPage--;
                            processAndRenderStudents();
                        }
                    });

                    paginationControls.querySelector(".next-page")?.addEventListener("click", () => {
                        if (currentPage < totalPages) {
                            currentPage++;
                            processAndRenderStudents();
                        }
                    });

                    paginationControls.querySelectorAll(".page-num").forEach(numBtn => {
                        numBtn.addEventListener("click", function () {
                            currentPage = parseInt(this.getAttribute("data-page"), 10);
                            processAndRenderStudents();
                        });
                    });
                } else {
                    paginationBox.style.display = "none";
                }
            }
        }

        if (searchInput) {
            searchInput.addEventListener("input", () => {
                currentPage = 1;
                processAndRenderStudents();
            });
        }
        if (courseFilter) {
            courseFilter.addEventListener("change", () => {
                currentPage = 1;
                processAndRenderStudents();
            });
        }
        if (yearFilter) {
            yearFilter.addEventListener("change", () => {
                currentPage = 1;
                processAndRenderStudents();
            });
        }
        if (sortSelect) {
            sortSelect.addEventListener("change", () => {
                currentPage = 1;
                processAndRenderStudents();
            });
        }
        if (resetBtn) {
            resetBtn.addEventListener("click", () => {
                if (searchInput) searchInput.value = "";
                if (courseFilter) courseFilter.value = "";
                if (yearFilter) yearFilter.value = "";
                if (countryFilter) countryFilter.value = "";
                handleCountryChange();
                if (sortSelect) sortSelect.value = "name-asc";
                currentPage = 1;
                processAndRenderStudents();
            });
        }
        if (retryBtn) retryBtn.addEventListener("click", loadStudentsData);

        loadStudentsData();
    }

    /* ----------------------------------------------------------------------
       8. PRACTICAL 4: NOTICE MODAL POPUP
       ---------------------------------------------------------------------- */
    const noticeModal = document.getElementById("noticeModal");
    const modalTriggers = document.querySelectorAll(".view-notice-btn");
    const modalCloseButtons = document.querySelectorAll(".modal-close-btn, .modal-action-btn");
    const modalTitleElem = document.getElementById("modalNoticeTitle");
    const modalDateElem = document.getElementById("modalNoticeDate");
    const modalBodyElem = document.getElementById("modalNoticeBody");

    function openNoticeModal(title, date, content) {
        if (!noticeModal) return;
        if (modalTitleElem && title) modalTitleElem.textContent = title;
        if (modalDateElem && date) modalDateElem.textContent = date;
        if (modalBodyElem && content) modalBodyElem.textContent = content;

        noticeModal.classList.add("active");
        noticeModal.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden";
    }

    function closeNoticeModal() {
        if (!noticeModal) return;
        noticeModal.classList.remove("active");
        noticeModal.setAttribute("aria-hidden", "true");
        document.body.style.overflow = "";
    }

    if (modalTriggers.length > 0) {
        modalTriggers.forEach(trigger => {
            trigger.addEventListener("click", function (e) {
                e.preventDefault();
                const title = this.getAttribute("data-title") || "College Notice Details";
                const date = this.getAttribute("data-date") || "Official Notice";
                const content = this.getAttribute("data-content") || "Please check with administration for further details.";
                openNoticeModal(title, date, content);
            });
        });
    }

    if (modalCloseButtons.length > 0) {
        modalCloseButtons.forEach(btn => {
            btn.addEventListener("click", function (e) {
                e.preventDefault();
                closeNoticeModal();
            });
        });
    }

    if (noticeModal) {
        noticeModal.addEventListener("click", function (e) {
            if (e.target === noticeModal) {
                closeNoticeModal();
            }
        });
    }

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && noticeModal && noticeModal.classList.contains("active")) {
            closeNoticeModal();
        }
    });

    /* ----------------------------------------------------------------------
       9. PRACTICAL 4: CONTENT SLIDER (home.html)
       ---------------------------------------------------------------------- */
    const sliderContainer = document.querySelector(".slider-container");

    if (sliderContainer) {
        const slides = sliderContainer.querySelectorAll(".slide");
        const prevBtn = sliderContainer.querySelector(".slider-btn.prev");
        const nextBtn = sliderContainer.querySelector(".slider-btn.next");
        const dotsContainer = sliderContainer.querySelector(".slider-dots");
        let currentSlide = 0;
        let autoSlideTimer = null;

        if (slides.length > 0) {
            if (dotsContainer && dotsContainer.children.length === 0) {
                slides.forEach((_, idx) => {
                    const dot = document.createElement("button");
                    dot.classList.add("slider-dot");
                    dot.setAttribute("type", "button");
                    dot.setAttribute("aria-label", `Slide ${idx + 1}`);
                    if (idx === 0) dot.classList.add("active");
                    dot.addEventListener("click", () => {
                        showSlide(idx);
                        resetTimer();
                    });
                    dotsContainer.appendChild(dot);
                });
            }

            const dots = sliderContainer.querySelectorAll(".slider-dot");

            function showSlide(index) {
                if (index >= slides.length) currentSlide = 0;
                else if (index < 0) currentSlide = slides.length - 1;
                else currentSlide = index;

                slides.forEach((slide, i) => {
                    if (i === currentSlide) slide.classList.add("active");
                    else slide.classList.remove("active");
                });

                dots.forEach((dot, i) => {
                    if (i === currentSlide) dot.classList.add("active");
                    else dot.classList.remove("active");
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener("click", () => {
                    showSlide(currentSlide + 1);
                    resetTimer();
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener("click", () => {
                    showSlide(currentSlide - 1);
                    resetTimer();
                });
            }

            function startTimer() {
                autoSlideTimer = setInterval(() => {
                    showSlide(currentSlide + 1);
                }, 5000);
            }

            function resetTimer() {
                if (autoSlideTimer) {
                    clearInterval(autoSlideTimer);
                    startTimer();
                }
            }

            sliderContainer.addEventListener("mouseenter", () => {
                if (autoSlideTimer) clearInterval(autoSlideTimer);
            });

            sliderContainer.addEventListener("mouseleave", () => {
                startTimer();
            });

            startTimer();
        }
    }

    /* ----------------------------------------------------------------------
       10. PRACTICAL 4: NOTIFICATION BANNER DISMISSAL
       ---------------------------------------------------------------------- */
    const bannerCloseButtons = document.querySelectorAll(".banner-close-btn");

    if (bannerCloseButtons.length > 0) {
        bannerCloseButtons.forEach(btn => {
            btn.addEventListener("click", function () {
                const banner = this.closest(".notification-banner");
                if (banner) {
                    banner.classList.add("hidden");
                    setTimeout(() => {
                        banner.style.display = "none";
                    }, 350);
                }
            });
        });
    }

});
