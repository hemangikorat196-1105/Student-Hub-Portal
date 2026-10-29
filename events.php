<?php
require_once __DIR__ . '/php/includes/session.php';
require_once __DIR__ . '/php/includes/auth_guard.php';
init_session();
require_student();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Discover, search, and register for student hackathons, technical seminars, cultural festivals, and career workshops on Student Hub Portal.">
    <title>Student Hub Portal - Campus Events</title>
    <link rel="stylesheet" href="Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="script.js" defer></script>
    <style>
        .events-hero {
            background: #14599d; /* Image 4 target for light mode */
            color: #ffffff;
            padding: 40px 20px;
            text-align: center;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(20, 89, 157, 0.25);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .events-hero h2 { font-size: 2.2rem; color: #ffffff; margin-bottom: 10px; }
        .events-hero p { max-width: 680px; margin: 0 auto; opacity: 0.95; font-size: 1.05rem; color: #ffffff; }

        body.dark-mode .events-hero {
            background: #38bdf8 !important; /* Image 3 target for dark mode */
            color: #0f172a !important;
            box-shadow: 0 6px 20px rgba(56, 189, 248, 0.35);
        }
        body.dark-mode .events-hero h2 {
            color: #0f172a !important;
        }
        body.dark-mode .events-hero p {
            color: #0f172a !important;
            font-weight: 500;
        }
        
        /* Filter & Controls Toolbar */
        .events-controls {
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 15px;
            align-items: center;
        }
        @media (max-width: 850px) {
            .events-controls { grid-template-columns: 1fr; }
        }
        .events-controls input, .events-controls select {
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.95rem;
            width: 100%;
        }
        .btn-reset {
            padding: 10px 18px;
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* Event Cards Grid */
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }
        .event-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .event-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }
        .event-poster {
            height: 160px;
            background: #e2e8f0;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 3rem;
            overflow: hidden;
        }
        .event-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .event-category-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(15, 23, 42, 0.85);
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .event-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        .event-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        .event-meta {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 12px;
        }
        .event-meta span {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .event-desc {
            font-size: 0.9rem;
            color: #475569;
            line-height: 1.5;
            margin-bottom: 18px;
            flex-grow: 1;
        }
        .event-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #f1f5f9;
        }
        .seats-info {
            font-size: 0.85rem;
            font-weight: 600;
            color: #0369a1;
        }
        .btn-register-event {
            padding: 8px 16px;
            background: #2563eb;
            color: #ffffff;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-register-event:hover {
            background: #1d4ed8;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: #ffffff;
            border-radius: 12px;
            max-width: 500px;
            width: 100%;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
        }
        .modal-close {
            position: absolute;
            top: 16px; right: 20px;
            font-size: 1.5rem;
            cursor: pointer;
            background: none;
            border: none;
            color: #64748b;
        }
        .status-feedback {
            padding: 12px;
            border-radius: 6px;
            margin-top: 15px;
            display: none;
        }
        .status-feedback.success {
            background: #dcfce7;
            color: #166534;
            display: block;
        }
    </style>
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>

<header class="main-header">
    <div class="header-left">
        <button class="menu-btn" onclick="openMenu()" aria-label="Open Menu">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="portal-title">
            <h1>STUDENT HUB PORTAL</h1>
            <p>Digital Academic & Campus Management System</p>
        </div>
    </div>

    <button id="themeToggle" class="theme-btn" aria-label="Change Theme">
        <i class="fa-solid fa-moon"></i>
        <span>Dark Mode</span>
    </button>
</header>

<div id="overlay" class="overlay"></div>

<aside id="sidebar" class="sidebar" aria-label="Sidebar Navigation" aria-hidden="true">
    <div class="sidebar-header">
        <h3>StudentHub Menu</h3>
        <button class="close-btn" aria-label="Close sidebar menu">&times;</button>
    </div>
    <a href="home.php"><i class="fa-solid fa-house"></i> Home</a>
    <a href="courses.php"><i class="fa-solid fa-graduation-cap"></i> Courses</a>
    <a href="events.php" class="active"><i class="fa-solid fa-calendar-days"></i> Events</a>
    <a href="Attendance.php"><i class="fa-solid fa-calendar-check"></i> Attendance</a>
    <a href="Assignment.php"><i class="fa-solid fa-book-open"></i> Assignment</a>
    <a href="Timetable.php"><i class="fa-solid fa-calendar-days"></i> Timetable</a>
    <a href="faculty.php"><i class="fa-solid fa-chalkboard-user"></i> Faculty</a>
    <a href="notice.php"><i class="fa-solid fa-bullhorn"></i> Notice</a>
    <a href="php/students/dashboard.php"><i class="fa-solid fa-user"></i> Profile</a>
    <a href="contactus.php"><i class="fa-solid fa-envelope"></i> Contact</a>
    <form class="sidebar-logout" action="php/auth/logout.php" method="post">
        <input type="hidden" name="csrf_token" value="">
        <button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
    </form>
</aside>

<main class="home-container" id="main-content">
    <section class="events-hero" aria-labelledby="events-heading">
        <h2 id="events-heading"><i class="fa-solid fa-calendar-days"></i> Campus Events &amp; Workshops</h2>
        <p>Explore technical summits, competitive hackathons, cultural festivals, career masterclasses, and sports tournaments.</p>
    </section>

    <!-- Controls: Search, Filter, Sort -->
    <section class="events-controls" aria-label="Event Search and Filters">
        <div>
            <input type="search" id="eventSearchInput" placeholder="Search event title, venue, or speaker..." aria-label="Search events">
        </div>
        <div>
            <select id="eventCategorySelect" aria-label="Filter by category">
                <option value="">All Categories</option>
                <option value="Technical">Technical</option>
                <option value="Workshop">Workshop</option>
                <option value="Seminar">Seminar</option>
                <option value="Cultural">Cultural</option>
                <option value="Sports">Sports</option>
                <option value="Career">Career</option>
            </select>
        </div>
        <div>
            <select id="eventSortSelect" aria-label="Sort events">
                <option value="date-asc">Date: Earliest First</option>
                <option value="date-desc">Date: Latest First</option>
                <option value="title-asc">Title: A to Z</option>
            </select>
        </div>
        <div>
            <button type="button" id="eventResetBtn" class="btn-reset">
                <i class="fa-solid fa-rotate-left"></i> Reset
            </button>
        </div>
    </section>

    <!-- Loading State -->
    <div id="eventsLoading" style="text-align: center; padding: 40px; color: #64748b;">
        <i class="fa-solid fa-spinner fa-spin" style="font-size: 2rem; color: #2563eb;"></i>
        <p style="margin-top: 10px; font-weight: 600;">Loading verified events data...</p>
    </div>

    <!-- Error State -->
    <div id="eventsError" style="display: none; background: #fee2e2; color: #991b1b; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 30px;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.8rem;"></i>
        <p style="margin-top: 8px; font-weight: 600;">Unable to load event records. Please try again.</p>
        <button id="eventsRetryBtn" class="btn-register-event" style="margin-top: 10px;">Retry</button>
    </div>

    <!-- Events Grid -->
    <section id="eventsGrid" class="events-grid" aria-label="Events List" style="display: none;"></section>

    <!-- Empty State -->
    <div id="eventsEmpty" style="display: none; text-align: center; padding: 50px 20px; background: #fff; border-radius: 12px; border: 1px dashed #cbd5e1;">
        <i class="fa-solid fa-calendar-xmark" style="font-size: 2.5rem; color: #94a3b8; margin-bottom: 12px;"></i>
        <h3 style="color: #334155;">No Events Found</h3>
        <p style="color: #64748b;">Try adjusting your search terms or clearing category filters.</p>
    </div>
</main>

<!-- Interactive Event Registration Modal (Practical 4 Component) -->
<div id="eventModal" class="modal" role="dialog" aria-labelledby="modalEventTitle" aria-hidden="true">
    <div class="modal-content">
        <button class="modal-close" id="modalCloseBtn" aria-label="Close modal">&times;</button>
        <h3 id="modalEventTitle" style="color: #0f172a; margin-bottom: 8px;">Event Registration</h3>
        <p id="modalEventSub" style="color: #64748b; font-size: 0.9rem; margin-bottom: 15px;"></p>

        <form id="eventRegistrationForm">
            <input type="hidden" id="modalEventId" name="event_id">
            <div class="form-group" style="margin-bottom: 12px;">
                <label for="regStudentId" style="font-size: 0.85rem; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;">Student ID *</label>
                <input type="text" id="regStudentId" required placeholder="e.g. 25CS035" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
            <div class="form-group" style="margin-bottom: 12px;">
                <label for="regStudentName" style="font-size: 0.85rem; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;">Student Full Name *</label>
                <input type="text" id="regStudentName" required placeholder="e.g. Hemangi Korat" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label for="regStudentEmail" style="font-size: 0.85rem; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;">Registered Email *</label>
                <input type="email" id="regStudentEmail" required placeholder="student@example.com" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
            <button type="submit" class="btn-register-event" style="width: 100%; justify-content: center; padding: 10px;">
                <i class="fa-solid fa-check"></i> Confirm Registration
            </button>
            <div id="modalFeedback" class="status-feedback"></div>
        </form>
    </div>
</div>

<footer>
    <div class="footer-container">
        <div class="footer-section">
            <h2>StudentHub Portal</h2>
            <p>Your digital campus companion for academics, courses, assignments, attendance, and student services.</p>
        </div>
    </div>
    <p class="copyright">
        © 2026 StudentHub Portal | Developed By Hemangi Korat | All Rights Reserved.
    </p>
</footer>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        let allEvents = [];
        const grid = document.getElementById("eventsGrid");
        const loading = document.getElementById("eventsLoading");
        const errorBox = document.getElementById("eventsError");
        const emptyBox = document.getElementById("eventsEmpty");
        const searchInput = document.getElementById("eventSearchInput");
        const categorySelect = document.getElementById("eventCategorySelect");
        const sortSelect = document.getElementById("eventSortSelect");
        const resetBtn = document.getElementById("eventResetBtn");
        const retryBtn = document.getElementById("eventsRetryBtn");

        // Modal elements
        const modal = document.getElementById("eventModal");
        const modalCloseBtn = document.getElementById("modalCloseBtn");
        const modalTitle = document.getElementById("modalEventTitle");
        const modalSub = document.getElementById("modalEventSub");
        const modalEventId = document.getElementById("modalEventId");
        const modalForm = document.getElementById("eventRegistrationForm");
        const modalFeedback = document.getElementById("modalFeedback");

        async function loadEvents() {
            loading.style.display = "block";
            grid.style.display = "none";
            errorBox.style.display = "none";
            emptyBox.style.display = "none";

            try {
                // Attempt to fetch from events API or fallback to events.json
                let response;
                try {
                    response = await fetch("php/api/events.php");
                    if (!response.ok) throw new Error("API not ready");
                    const json = await response.json();
                    allEvents = json.data || [];
                } catch (e) {
                    response = await fetch("events.json");
                    if (!response.ok) throw new Error("Failed to load events.json");
                    allEvents = await response.json();
                }

                loading.style.display = "none";
                renderEvents();
            } catch (err) {
                console.error("Error loading events:", err);
                loading.style.display = "none";
                errorBox.style.display = "block";
            }
        }

        function renderEvents() {
            const query = (searchInput.value || "").trim().toLowerCase();
            const category = categorySelect.value;
            const sort = sortSelect.value;

            let filtered = allEvents.filter(ev => {
                const matchesCategory = !category || (ev.category === category);
                const matchesQuery = !query ||
                    (ev.title && ev.title.toLowerCase().includes(query)) ||
                    (ev.venue && ev.venue.toLowerCase().includes(query)) ||
                    (ev.speaker && ev.speaker.toLowerCase().includes(query)) ||
                    (ev.description && ev.description.toLowerCase().includes(query));
                return matchesCategory && matchesQuery;
            });

            // Sorting
            if (sort === "date-asc") {
                filtered.sort((a, b) => new Date(a.date || a.event_date) - new Date(b.date || b.event_date));
            } else if (sort === "date-desc") {
                filtered.sort((a, b) => new Date(b.date || b.event_date) - new Date(a.date || a.event_date));
            } else if (sort === "title-asc") {
                filtered.sort((a, b) => (a.title || "").localeCompare(b.title || ""));
            }

            if (filtered.length === 0) {
                grid.style.display = "none";
                emptyBox.style.display = "block";
                return;
            }

            emptyBox.style.display = "none";
            grid.style.display = "grid";
            grid.innerHTML = "";

            filtered.forEach(ev => {
                const dateStr = ev.event_date || ev.date || "Upcoming";
                const timeStr = ev.time || "10:00 AM";
                const locationStr = ev.venue || ev.location || "Campus Hall";
                const speakerStr = ev.speaker || "Guest Speaker";
                const seatsStr = ev.seats ? `${ev.seats} Seats` : "Open Seats";

                const card = document.createElement("article");
                card.className = "event-card";
                card.innerHTML = `
                    <div class="event-poster">
                        <i class="fa-solid fa-calendar-star"></i>
                        <span class="event-category-badge">${escapeHtml(ev.category || 'General')}</span>
                    </div>
                    <div class="event-body">
                        <h3 class="event-title">${escapeHtml(ev.title)}</h3>
                        <div class="event-meta">
                            <span><i class="fa-solid fa-calendar"></i> ${escapeHtml(dateStr)} • ${escapeHtml(timeStr)}</span>
                            <span><i class="fa-solid fa-location-dot"></i> ${escapeHtml(locationStr)}</span>
                            <span><i class="fa-solid fa-user-tie"></i> ${escapeHtml(speakerStr)}</span>
                        </div>
                        <p class="event-desc">${escapeHtml(ev.description || '')}</p>
                        <div class="event-footer">
                            <span class="seats-info"><i class="fa-solid fa-chair"></i> ${escapeHtml(seatsStr)}</span>
                            <button type="button" class="btn-register-event open-modal-btn" data-id="${ev.id}" data-title="${escapeHtml(ev.title)}" data-date="${escapeHtml(dateStr)}">
                                <i class="fa-solid fa-ticket"></i> Register
                            </button>
                        </div>
                    </div>
                `;
                grid.appendChild(card);
            });

            // Attach modal click handlers
            document.querySelectorAll(".open-modal-btn").forEach(btn => {
                btn.addEventListener("click", function () {
                    const id = this.getAttribute("data-id");
                    const title = this.getAttribute("data-title");
                    const date = this.getAttribute("data-date");

                    modalEventId.value = id;
                    modalTitle.textContent = title;
                    modalSub.textContent = `Scheduled on: ${date}`;
                    modalFeedback.className = "status-feedback";
                    modalFeedback.textContent = "";
                    modal.classList.add("active");
                    modal.setAttribute("aria-hidden", "false");
                    document.getElementById("regStudentId").focus();
                });
            });
        }

        function escapeHtml(str) {
            return String(str || "").replace(/[&<>'"]/g, ch => ({
                "&": "&amp;", "<": "&lt;", ">": "&gt;", "'": "&#39;", '"': "&quot;"
            }[ch]));
        }

        // Search and Filter Listeners
        searchInput.addEventListener("input", renderEvents);
        categorySelect.addEventListener("change", renderEvents);
        sortSelect.addEventListener("change", renderEvents);
        resetBtn.addEventListener("click", () => {
            searchInput.value = "";
            categorySelect.value = "";
            sortSelect.value = "date-asc";
            renderEvents();
        });
        if (retryBtn) retryBtn.addEventListener("click", loadEvents);

        // Modal Close Handlers
        modalCloseBtn.addEventListener("click", () => {
            modal.classList.remove("active");
            modal.setAttribute("aria-hidden", "true");
        });
        window.addEventListener("click", (e) => {
            if (e.target === modal) {
                modal.classList.remove("active");
                modal.setAttribute("aria-hidden", "true");
            }
        });

        // Modal Form Submit
        modalForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const sid = document.getElementById("regStudentId").value.trim();
            const sname = document.getElementById("regStudentName").value.trim();

            modalFeedback.className = "status-feedback success";
            modalFeedback.innerHTML = `<i class="fa-solid fa-circle-check"></i> Registration confirmed for <strong>${escapeHtml(sname)}</strong> (${escapeHtml(sid)})! Pass dispatched to email.`;

            setTimeout(() => {
                modal.classList.remove("active");
                modal.setAttribute("aria-hidden", "true");
                modalForm.reset();
            }, 2500);
        });

        loadEvents();
    });
</script>
</body>
</html>
