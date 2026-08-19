/**
 * ==========================================================================
 * STUDENTHUB PORTAL - CORE JAVASCRIPT (script.js)
 * Practical 4: JavaScript DOM Manipulation, Event Handling, and UI Interactivity
 * ==========================================================================
 * Features Included:
 * 1. Light/Dark Theme Switcher with localStorage persistence
 * 2. Responsive Hamburger Menu & Accessible Sidebar Drawer
 * 3. Collapsible FAQ Accordion with smooth transitions and ARIA states
 * 4. Interactive Modal Popup with overlay click and Escape key dismissal
 * 5. Dynamic Content Slider with Next/Previous buttons and dot navigation
 * 6. Dismissible Notification Banner with smooth DOM collapse
 * 7. Zero Console Errors with robust element existence checking
 * ==========================================================================
 */

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
        console.warn("localStorage access unavailable:", e);
    }
})();

/* ========================================
   HAMBURGER MENU
======================================== */

function openMenu() {
    document
        .getElementById("sidebar")
        .classList.add("active");
    document
        .getElementById("overlay")
        .classList.add("active");
}


function closeMenu() {
    document
        .getElementById("sidebar")
        .classList.remove("active");
    document
        .getElementById("overlay")
        .classList.remove("active");
}

/* ========================================
   DARK / LIGHT MODE
======================================== */

const themeToggle =
    document.getElementById("themeToggle");

function updateThemeButton() {

    if (!themeToggle) return;

    if (document.body.classList.contains("dark-mode")) {
        themeToggle.innerHTML = `<i class="fa-solid fa-sun"></i>
            <span>Light Mode</span>`;
    }

    else {
        themeToggle.innerHTML = `<i class="fa-solid fa-moon"></i>
        <span>Dark Mode</span>`;
    }
}


/* Load saved theme */
if (localStorage.getItem("theme") === "dark") {
    document.body.classList.add("dark-mode");
}
updateThemeButton();

/* Change theme */
if (themeToggle) {
    themeToggle.addEventListener("click", function () {
        document.body.classList.toggle("dark-mode");
        if (document.body.classList.contains("dark-mode")) {
            localStorage.setItem("theme", "dark");
        }
        else {
            localStorage.setItem("theme", "light");
        }
        updateThemeButton();

    });
}

document.addEventListener("DOMContentLoaded", function () {
    
    /* 1. LIGHT / DARK THEME SWITCHER (localStorage + DOM manipulation)*/
    const themeButtons = document.querySelectorAll(".theme-toggle-btn, #themeToggle");

    // Helper to update button icon and label text
    function updateThemeButtonUI(isDark) {
        themeButtons.forEach(btn => {
            if (isDark) {
                btn.innerHTML = '<i class="fa-solid fa-sun"></i> <span>Light Mode</span>';
                btn.setAttribute("aria-label", "Switch to light mode");
                btn.setAttribute("title", "Switch to light mode");
            } else {
                btn.innerHTML = '<i class="fa-solid fa-moon"></i> <span>Dark Mode</span>';
                btn.setAttribute("aria-label", "Switch to dark mode");
                btn.setAttribute("title", "Switch to dark mode");
            }
        });
    }

    // Check stored theme and synchronize UI
    const currentSavedTheme = localStorage.getItem("theme");
    const isInitiallyDark = currentSavedTheme === "dark";
    if (isInitiallyDark) {
        document.body.classList.add("dark-mode");
        document.documentElement.classList.add("dark-mode");
    } else {
        document.body.classList.remove("dark-mode");
        document.documentElement.classList.remove("dark-mode");
    }
    updateThemeButtonUI(isInitiallyDark);

    // Attach click listeners to theme toggle buttons
    if (themeButtons.length > 0) {
        themeButtons.forEach(button => {
            button.addEventListener("click", function () {
                // DOM Manipulation: Toggle class on body
                document.body.classList.toggle("dark-mode");
                document.documentElement.classList.toggle("dark-mode");

                const isDark = document.body.classList.contains("dark-mode");

                // Storage handling
                if (isDark) {
                    localStorage.setItem("theme", "dark");
                } else {
                    localStorage.setItem("theme", "light");
                }

                // Update UI text & icons across buttons
                updateThemeButtonUI(isDark);
            });
        });
    }

    /*2. RESPONSIVE HAMBURGER MENU & SIDEBAR DRAWER */
    const menuBtn = document.querySelector(".menu-btn");
    const closeBtn = document.querySelector(".sidebar .close-btn");
    const overlay = document.querySelector(".overlay");
    const sidebar = document.querySelector(".sidebar");

    function openSidebar() {
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
        document.body.style.overflow = "hidden"; // Prevent background scroll
    }

    function closeSidebar() {
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
        document.body.style.overflow = ""; // Restore background scroll
    }

    // Expose global functions for backward compatibility with inline onclick
    window.openMenu = openSidebar;
    window.closeMenu = closeSidebar;

    if (menuBtn) {
        menuBtn.setAttribute("role", "button");
        menuBtn.setAttribute("aria-label", "Toggle navigation menu");
        menuBtn.setAttribute("aria-expanded", "false");
        menuBtn.addEventListener("click", function (e) {
            e.preventDefault();
            if (sidebar && sidebar.classList.contains("active")) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener("click", function (e) {
            e.preventDefault();
            closeSidebar();
        });
    }

    if (overlay) {
        overlay.addEventListener("click", closeSidebar);
    }

    // Close sidebar with Escape key
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && sidebar && sidebar.classList.contains("active")) {
            closeSidebar();
        }
    });

    /* 3. COLLAPSIBLE FAQ ACCORDION */
    const faqItems = document.querySelectorAll(".faq-item");
    const faqButtons = document.querySelectorAll(".faq-question");

    if (faqButtons.length > 0) {
        faqButtons.forEach((button) => {
            button.addEventListener("click", function () {
                const parentItem = this.closest(".faq-item");
                const answer = parentItem.querySelector(".faq-answer");
                const isCurrentlyActive = parentItem.classList.contains("active");

                // Toggle active state on the clicked question
                if (isCurrentlyActive) {
                    parentItem.classList.remove("active");
                    this.setAttribute("aria-expanded", "false");
                } else {
                    parentItem.classList.add("active");
                    this.setAttribute("aria-expanded", "true");
                }
            });
        });
    }

    /* 4. MODAL POPUP (NOTICE DETAILS)*/
    const noticeModal = document.getElementById("noticeModal");
    const modalTriggers = document.querySelectorAll(".view-notice-btn, [data-modal-target='noticeModal']");
    const modalCloseButtons = document.querySelectorAll(".modal-close-btn, .modal-action-btn");
    const modalTitleElem = document.getElementById("modalNoticeTitle");
    const modalDateElem = document.getElementById("modalNoticeDate");
    const modalBodyElem = document.getElementById("modalNoticeBody");

    function openModal(title, date, content) {
        if (!noticeModal) return;

        if (modalTitleElem && title) modalTitleElem.textContent = title;
        if (modalDateElem && date) modalDateElem.textContent = date;
        if (modalBodyElem && content) modalBodyElem.textContent = content;

        noticeModal.classList.add("active");
        noticeModal.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden";
    }

    function closeModal() {
        if (!noticeModal) return;
        noticeModal.classList.remove("active");
        noticeModal.setAttribute("aria-hidden", "true");
        document.body.style.overflow = "";
    }

    // Attach listeners to notice cards/buttons
    if (modalTriggers.length > 0) {
        modalTriggers.forEach(trigger => {
            trigger.addEventListener("click", function (e) {
                e.preventDefault();
                const title = this.getAttribute("data-title") || "College Notice Details";
                const date = this.getAttribute("data-date") || "Important Update";
                const content = this.getAttribute("data-content") || "Please contact administration for further information regarding this circular.";
                openModal(title, date, content);
            });
        });
    }

    // Attach listeners to modal close triggers
    if (modalCloseButtons.length > 0) {
        modalCloseButtons.forEach(btn => {
            btn.addEventListener("click", function (e) {
                e.preventDefault();
                closeModal();
            });
        });
    }

    // Close when clicking on backdrop overlay outside modal container
    if (noticeModal) {
        noticeModal.addEventListener("click", function (e) {
            if (e.target === noticeModal) {
                closeModal();
            }
        });
    }

    // Close modal on Escape key press
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && noticeModal && noticeModal.classList.contains("active")) {
            closeModal();
        }
    });

    /*  5. INTERACTIVE CONTENT SLIDER */
    const sliderContainer = document.querySelector(".slider-container");

    if (sliderContainer) {
        const slides = sliderContainer.querySelectorAll(".slide");
        const prevBtn = sliderContainer.querySelector(".slider-btn.prev");
        const nextBtn = sliderContainer.querySelector(".slider-btn.next");
        const dotsContainer = sliderContainer.querySelector(".slider-dots");
        let currentSlide = 0;
        let autoSlideInterval = null;

        if (slides.length > 0) {
            // Dynamically generate navigation dots if container exists and empty
            if (dotsContainer && dotsContainer.children.length === 0) {
                slides.forEach((_, idx) => {
                    const dot = document.createElement("button");
                    dot.classList.add("slider-dot");
                    dot.setAttribute("type", "button");
                    dot.setAttribute("aria-label", `Go to slide ${idx + 1}`);
                    if (idx === 0) dot.classList.add("active");
                    dot.addEventListener("click", () => {
                        goToSlide(idx);
                        resetAutoSlideTimer();
                    });
                    dotsContainer.appendChild(dot);
                });
            }

            const dots = sliderContainer.querySelectorAll(".slider-dot");

            function goToSlide(slideIndex) {
                // Ensure circular wrapping
                if (slideIndex >= slides.length) {
                    currentSlide = 0;
                } else if (slideIndex < 0) {
                    currentSlide = slides.length - 1;
                } else {
                    currentSlide = slideIndex;
                }

                // Update slides visibility with DOM classes
                slides.forEach((slide, idx) => {
                    if (idx === currentSlide) {
                        slide.classList.add("active");
                    } else {
                        slide.classList.remove("active");
                    }
                });

                // Update dots active state
                dots.forEach((dot, idx) => {
                    if (idx === currentSlide) {
                        dot.classList.add("active");
                    } else {
                        dot.classList.remove("active");
                    }
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener("click", function () {
                    goToSlide(currentSlide + 1);
                    resetAutoSlideTimer();
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener("click", function () {
                    goToSlide(currentSlide - 1);
                    resetAutoSlideTimer();
                });
            }

            // Auto-advance slides every 5 seconds
            function startAutoSlide() {
                autoSlideInterval = setInterval(() => {
                    goToSlide(currentSlide + 1);
                }, 5000);
            }

            function resetAutoSlideTimer() {
                if (autoSlideInterval) {
                    clearInterval(autoSlideInterval);
                    startAutoSlide();
                }
            }

            sliderContainer.addEventListener("mouseenter", () => {
                if (autoSlideInterval) clearInterval(autoSlideInterval);
            });

            sliderContainer.addEventListener("mouseleave", () => {
                startAutoSlide();
            });

            startAutoSlide();
        }
    }

    /*  6. NOTIFICATION BANNER DISMISSAL */
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
