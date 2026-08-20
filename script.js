/**
 * ==========================================================================
 * STUDENTHUB PORTAL - CORE JAVASCRIPT (script.js)
 * Practical 4: JavaScript DOM Manipulation, Event Handling, and UI Interactivity
 * ==========================================================================
 * Features:
 * 1. Global Light/Dark Theme Switcher with localStorage persistence
 * 2. Responsive Hamburger Menu & Accessible Sidebar Drawer (No Double-Trigger)
 * 3. Complete Student Registration Form Validation with Regular Expressions (Regex)
 * 4. Collapsible FAQ Accordion with smooth CSS transitions
 * 5. Interactive Modal Popup for Notice Details
 * 6. Dynamic Content Slider with Prev/Next & Dots
 * 7. Dismissible Notification Banner
 * 8. Zero Console Errors with conditional existence checks
 * ==========================================================================
 */

// Immediate theme execution to prevent Flash of Unstyled Theme (FOUC)
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
        console.warn("localStorage is unavailable:", e);
    }
})();

/* ==========================================================================
   GLOBAL SIDEBAR / HAMBURGER FUNCTIONS
   ========================================================================== */
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

// Expose globally for inline onclick handlers if needed
window.openMenu = openMenu;
window.closeMenu = closeMenu;
window.toggleMenu = toggleMenu;

/* ==========================================================================
   DOM READY EVENT INITIALIZATION
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

    // Synchronize UI state with stored theme
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

    // Attach click listeners to all theme toggle buttons
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
       2. HAMBURGER MENU & SIDEBAR EVENT LISTENERS
       ---------------------------------------------------------------------- */
    const menuButtons = document.querySelectorAll(".menu-btn");
    const closeBtn = document.querySelector(".sidebar .close-btn");
    const overlay = document.getElementById("overlay");
    const sidebar = document.getElementById("sidebar");

    if (menuButtons.length > 0) {
        menuButtons.forEach(btn => {
            // Remove any inline onclick attribute to avoid double execution race conditions
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

    // Close sidebar on Escape key
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && sidebar && sidebar.classList.contains("active")) {
            closeMenu();
        }
    });

    // Close sidebar when clicking any navigation link inside sidebar on mobile
    if (sidebar) {
        const navLinks = sidebar.querySelectorAll("a");
        navLinks.forEach(link => {
            link.addEventListener("click", function () {
                closeMenu();
            });
        });
    }

    /* ----------------------------------------------------------------------
       3. STUDENT REGISTRATION FORM VALIDATION (REGEX & DOM MANIPULATION)
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
        const successBanner = document.getElementById("registrationSuccess");

        // Error message elements
        const nameError = document.getElementById("nameError");
        const emailError = document.getElementById("emailError");
        const mobileError = document.getElementById("mobileError");
        const passwordError = document.getElementById("passwordError");
        const confirmPasswordError = document.getElementById("confirmPasswordError");
        const courseError = document.getElementById("courseError");
        const yearError = document.getElementById("yearError");
        const genderError = document.getElementById("genderError");
        const termsError = document.getElementById("termsError");

        // Regular Expressions
        const nameRegex = /^[A-Za-z\s]{2,50}$/;
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        const mobileRegex = /^[6-9]\d{9}$/;
        // At least 8 characters, 1 uppercase, 1 lowercase, 1 digit, 1 special character
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#^_\-~])[A-Za-z\d@$!%*?&#^_\-~]{8,}$/;

        // Helper: Set Field Error
        function setError(inputElement, errorElement, message) {
            if (errorElement) {
                errorElement.textContent = message;
            }
            if (inputElement && inputElement.classList) {
                inputElement.classList.add("input-error");
            }
        }

        // Helper: Clear Field Error
        function clearFieldError(inputElement, errorElement) {
            if (errorElement) {
                errorElement.textContent = "";
            }
            if (inputElement && inputElement.classList) {
                inputElement.classList.remove("input-error");
            }
        }

        // Helper: Clear All Form Errors
        function clearAllErrors() {
            const errorSpans = regForm.querySelectorAll(".error-msg");
            errorSpans.forEach(span => span.textContent = "");

            const errorInputs = regForm.querySelectorAll(".input-error");
            errorInputs.forEach(input => input.classList.remove("input-error"));

            if (successBanner) {
                successBanner.style.display = "none";
            }
        }

        // Real-time error clearance on input/change events
        if (fullNameInput) {
            fullNameInput.addEventListener("input", () => {
                if (nameRegex.test(fullNameInput.value.trim())) {
                    clearFieldError(fullNameInput, nameError);
                }
            });
        }

        if (emailInput) {
            emailInput.addEventListener("input", () => {
                if (emailRegex.test(emailInput.value.trim())) {
                    clearFieldError(emailInput, emailError);
                }
            });
        }

        if (mobileInput) {
            mobileInput.addEventListener("input", () => {
                if (mobileRegex.test(mobileInput.value.trim())) {
                    clearFieldError(mobileInput, mobileError);
                }
            });
        }

        if (passwordInput) {
            passwordInput.addEventListener("input", () => {
                if (passwordRegex.test(passwordInput.value)) {
                    clearFieldError(passwordInput, passwordError);
                }
                if (confirmPasswordInput && confirmPasswordInput.value.length > 0) {
                    if (confirmPasswordInput.value === passwordInput.value) {
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

        // Form Submit Handler
        regForm.addEventListener("submit", function (e) {
            e.preventDefault();
            let isValid = true;

            // 1. Full Name Validation
            const fullNameVal = fullNameInput ? fullNameInput.value.trim() : "";
            if (fullNameVal === "") {
                setError(fullNameInput, nameError, "Full name is required.");
                isValid = false;
            } else if (!nameRegex.test(fullNameVal)) {
                setError(fullNameInput, nameError, "Please enter a valid name (minimum 2 letters, no numbers or symbols).");
                isValid = false;
            } else {
                clearFieldError(fullNameInput, nameError);
            }

            // 2. Email Validation
            const emailVal = emailInput ? emailInput.value.trim() : "";
            if (emailVal === "") {
                setError(emailInput, emailError, "Email address is required.");
                isValid = false;
            } else if (!emailRegex.test(emailVal)) {
                setError(emailInput, emailError, "Please enter a valid email address (e.g. student@example.com).");
                isValid = false;
            } else {
                clearFieldError(emailInput, emailError);
            }

            // 3. Mobile Number Validation
            const mobileVal = mobileInput ? mobileInput.value.trim() : "";
            if (mobileVal === "") {
                setError(mobileInput, mobileError, "Mobile number is required.");
                isValid = false;
            } else if (!mobileRegex.test(mobileVal)) {
                setError(mobileInput, mobileError, "Enter a valid 10-digit Indian mobile number starting with 6, 7, 8, or 9.");
                isValid = false;
            } else {
                clearFieldError(mobileInput, mobileError);
            }

            // 4. Password Validation
            const passwordVal = passwordInput ? passwordInput.value : "";
            if (passwordVal === "") {
                setError(passwordInput, passwordError, "Password is required.");
                isValid = false;
            } else if (!passwordRegex.test(passwordVal)) {
                setError(passwordInput, passwordError, "Password must be at least 8 characters and include uppercase, lowercase, number, and special character.");
                isValid = false;
            } else {
                clearFieldError(passwordInput, passwordError);
            }

            // 5. Confirm Password Validation
            const confirmPasswordVal = confirmPasswordInput ? confirmPasswordInput.value : "";
            if (confirmPasswordVal === "") {
                setError(confirmPasswordInput, confirmPasswordError, "Please confirm your password.");
                isValid = false;
            } else if (confirmPasswordVal !== passwordVal) {
                setError(confirmPasswordInput, confirmPasswordError, "Passwords do not match.");
                isValid = false;
            } else {
                clearFieldError(confirmPasswordInput, confirmPasswordError);
            }

            // 6. Course Selection Validation
            const courseVal = courseSelect ? courseSelect.value : "";
            if (courseVal === "") {
                setError(courseSelect, courseError, "Please select a course.");
                isValid = false;
            } else {
                clearFieldError(courseSelect, courseError);
            }

            // 7. Year Selection Validation
            const yearVal = yearSelect ? yearSelect.value : "";
            if (yearVal === "") {
                setError(yearSelect, yearError, "Please select your year.");
                isValid = false;
            } else {
                clearFieldError(yearSelect, yearError);
            }

            // 8. Gender Validation
            const selectedGender = regForm.querySelector("input[name='gender']:checked");
            if (!selectedGender) {
                setError(null, genderError, "Please select your gender.");
                isValid = false;
            } else {
                clearFieldError(null, genderError);
            }

            // 9. Terms and Conditions Validation
            if (termsCheckbox && !termsCheckbox.checked) {
                setError(null, termsError, "You must accept the terms and conditions to register.");
                isValid = false;
            } else {
                clearFieldError(null, termsError);
            }

            // If all validation passes:
            if (isValid) {
                if (successBanner) {
                    successBanner.innerHTML = `<i class="fa-solid fa-circle-check"></i> Registration successful for <strong>${fullNameVal}</strong>! Redirecting to login...`;
                    successBanner.style.display = "block";
                    successBanner.scrollIntoView({ behavior: "smooth", block: "center" });
                }

                // Reset the form fields safely
                regForm.reset();
                clearAllErrors();
                if (successBanner) successBanner.style.display = "block";

                // Redirect to Login page after 2.5 seconds
                setTimeout(() => {
                    window.location.href = "Login.html";
                }, 2500);
            }
        });

        // Form Reset / Clear Handler
        regForm.addEventListener("reset", function () {
            // Small timeout to allow native reset to clear values before cleaning errors
            setTimeout(() => {
                clearAllErrors();
            }, 10);
        });
    }

    /* ----------------------------------------------------------------------
       4. COLLAPSIBLE FAQ ACCORDION
       ---------------------------------------------------------------------- */
    const faqButtons = document.querySelectorAll(".faq-question");

    if (faqButtons.length > 0) {
        faqButtons.forEach(button => {
            button.addEventListener("click", function () {
                const parentItem = this.closest(".faq-item");
                const isActive = parentItem.classList.contains("active");

                if (isActive) {
                    parentItem.classList.remove("active");
                    this.setAttribute("aria-expanded", "false");
                } else {
                    parentItem.classList.add("active");
                    this.setAttribute("aria-expanded", "true");
                }
            });
        });
    }

    /* ----------------------------------------------------------------------
       5. MODAL POPUP (Notice Details)
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
       6. CONTENT SLIDER
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
            // Build pagination dots if container is empty
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
       7. NOTIFICATION BANNER DISMISSAL
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
