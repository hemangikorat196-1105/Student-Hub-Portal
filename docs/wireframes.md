# Student Hub Portal — Wireframe Specifications

This document outlines the low-fidelity structural layouts (wireframes) for desktop (>768px) and mobile (<480px) viewports across core portal pages.

---

## 1. Welcome Landing Page (`index.html`)

### Desktop Layout
```text
+-----------------------------------------------------------------------------+
|  [Logo] STUDENT HUB PORTAL                 [Dark Mode Toggle]               |
+-----------------------------------------------------------------------------+
|                                                                             |
|                       Welcome to Student Hub Portal                         |
|     Your one-stop digital companion for managing attendance, assignments... |
|                                                                             |
|   +---------------------+   +---------------------+   +-----------------+   |
|   |   New Student?      |   |   Student Login     |   | Explore Campus  |   |
|   | Create an account   |   | Access your portal  |   | Browse events   |   |
|   |   [Register Now]    |   |    [Login Now]      |   |  [View Events]  |   |
|   +---------------------+   +---------------------+   +-----------------+   |
|                                                                             |
+-----------------------------------------------------------------------------+
|  (c) 2026 StudentHub Portal | Developed By Hemangi Korat | All Rights Reserved|
+-----------------------------------------------------------------------------+
```

### Mobile Layout
```text
+---------------------------------------+
| [Logo] STUDENT HUB PORTAL    [Theme]  |
+---------------------------------------+
| Welcome to Student Hub Portal         |
+---------------------------------------+
| +-----------------------------------+ |
| | New Student?                      | |
| | [Register Now]                    | |
| +-----------------------------------+ |
| +-----------------------------------+ |
| | Student Login                     | |
| | [Login Now]                       | |
| +-----------------------------------+ |
| +-----------------------------------+ |
| | Explore Campus                    | |
| | [View Events]                     | |
| +-----------------------------------+ |
+---------------------------------------+
| (c) 2026 StudentHub Portal            |
+---------------------------------------+
```

---

## 2. Student Registration Form (`Register.html`)

### Desktop Layout
```text
+-----------------------------------------------------------------------------+
| [Logo] STUDENT HUB PORTAL                                  [Theme Toggle]   |
+-----------------------------------------------------------------------------+
|  [Badge: HTML5 + JS Validation]                                             |
|  Student Registration (All * fields are mandatory)                          |
|  [ Features: Regex Validation | Password Strength | Accessible Controls ]   |
|                                                                             |
|  +-- 1. Personal Information ---------------------------------------------+ |
|  | Full Name*: [.....................]   Email*: [.......................] | |
|  | Mobile*:    [.....................]   Gender: (o) Male (o) Female (o)Other|
|  +------------------------------------------------------------------------+ |
|                                                                             |
|  +-- 2. Academic Information ---------------------------------------------+ |
|  | Course*:    [Select Course v......]   Year*:  [Select Year v..........] | |
|  +------------------------------------------------------------------------+ |
|                                                                             |
|  +-- 3. Account Security -------------------------------------------------+ |
|  | Password*:  [.....................]   Confirm Password*: [............] | |
|  | [Strength Meter: [====      ] Medium]                                   | |
|  +------------------------------------------------------------------------+ |
|                                                                             |
|  +-- 4. Verification & Terms ---------------------------------------------+ |
|  | [Canvas CAPTCHA: W K 9 4 X] [Refresh]  Enter Code*: [.................] | |
|  | [x] I agree to the Terms and Conditions & Campus Policies.*             | |
|  +------------------------------------------------------------------------+ |
|                                                                             |
|            [ Register Now ]             [ Clear Form ]                      |
|                                                                             |
|  Already have an account? [Login here]                                      |
+-----------------------------------------------------------------------------+
| (c) 2026 StudentHub Portal | Developed By Hemangi Korat                     |
+-----------------------------------------------------------------------------+
```

---

## 3. Campus Events Directory (`events.html`)

### Desktop Layout
```text
+-----------------------------------------------------------------------------+
| [=] [Logo] STUDENT HUB PORTAL                              [Theme Toggle]   |
+-----------------------------------------------------------------------------+
| +-- Hero -----------------------------------------------------------------+ |
| |  Campus Events & Workshops (Discover, filter, and register)            | |
| +-------------------------------------------------------------------------+ |
|                                                                             |
| [ Search keywords...... ] [ Category v ] [ Sort by Date v ] [ Reset Button ]|
|                                                                             |
| +-----------------------+ +-----------------------+ +---------------------+ |
| | [Poster / Thumbnail]  | | [Poster / Thumbnail]  | | [Poster / Thumbnail]| |
| | Tech Summit 2026      | | Web Dev Bootcamp      | | Code Marathon       | |
| | Date: 2026-09-25      | | Date: 2026-09-28      | | Date: 2026-10-05    | |
| | Venue: Main Aud       | | Venue: Lab 3          | | Venue: Lab 1        | |
| | Speaker: Dr. Thorne   | | Speaker: Prof. Patel  | | Speaker: Mentors    | |
| | [350 Seats] [Register]| | [60 Seats] [Register] | | [120 Seats][Register| |
| +-----------------------+ +-----------------------+ +---------------------+ |
+-----------------------------------------------------------------------------+
```

---

## 4. Live Administrator Dashboard (`php/admin/dashboard.php`)

### Desktop Layout
```text
+-----------------------------------------------------------------------------+
| [Logo] Administrator Dashboard                 [Admin Pill] [Logout Button] |
+-----------------------------------------------------------------------------+
| [ Quick Tools: (Student CRUD) (Event Manager) (Users) (Audit Logs) (JSON) ] |
+-----------------------------------------------------------------------------+
|                                                                             |
| +---------------+ +---------------+ +---------------+ +---------------+     |
| | STUDENTS      | | EVENTS        | | REGISTRATIONS | | USERS         |     |
| | 15 Total      | | 15 Total      | | 15 Confirmed  | | 15 Registered |     |
| | 15 Active     | | 6 Open        | | Verified      | | Admin/Student |     |
| +---------------+ +---------------+ +---------------+ +---------------+     |
|                                                                             |
| +-- Recent Registrations (5) --------+ +-- Course Enrollment Analytics ----+ |
| | Student   Event       Category Date| | B.Tech CSE     [==========] (47%) | |
| | Hemangi   Tech Summit Tech     Aug | | B.Tech IT      [======    ] (27%) | |
| | Aarav S   Tech Summit Tech     Aug | | B.Tech Mech    [====      ] (13%) | |
| | Diya P    Web Boot    Workshop Aug | | B.Tech Civil   [===       ] (13%) | |
| +------------------------------------+ +-----------------------------------+ |
|                                                                             |
| +-- Recent Security Audit Trail ------------------------------------------+ |
| | #15 | Admin    | LOGIN        | Authenticated session id regenerated    | |
| | #14 | 25CS035  | LOGOUT       | Student logged out securely             | |
| | #13 | Guest    | UNAUTH_ACC   | Attempted to access restricted admin    | |
| +-------------------------------------------------------------------------+ |
+-----------------------------------------------------------------------------+
```

---

## 5. Student Management CRUD Table (`php/students/list.php`)

### Desktop Layout
```text
+-----------------------------------------------------------------------------+
| Student Management System           [+ Add New Student] [Export CSV] [Dash] |
+-----------------------------------------------------------------------------+
| [ Search Keyword ] [ Filter Course v ] [ Filter Status v ] [ Filter Button ]|
+-----------------------------------------------------------------------------+
| ID | Student ID | Name          | Email           | Course     | Status | Act|
|----+------------+---------------+-----------------+------------+--------+----|
| 1  | 25CS035    | Hemangi Korat | hemangi@char... | B.Tech CSE | ACTIVE | E/D|
| 2  | 25CS001    | Aarav Sharma  | aarav@char...   | B.Tech CSE | ACTIVE | E/D|
| 3  | 25IT012    | Diya Patel    | diya@char...    | B.Tech IT  | ACTIVE | E/D|
+----+------------+---------------+-----------------+------------+--------+----|
| Showing 1 to 10 of 15 records                        [ < Prev ] [1] [2] [Next>|
+-----------------------------------------------------------------------------+
```
