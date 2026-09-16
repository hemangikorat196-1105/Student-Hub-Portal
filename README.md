# 🎓 Student Hub Portal

> A modern, responsive student portal that brings academic services, campus information, and student resources together in one place.

## Overview

**Student Hub Portal** is a frontend web application created to centralize common student activities and demonstrate modern web-development concepts.

### Main areas

- Student registration and login
- Forgot-password and forgot-username flows
- Profile management
- Courses
- Timetable
- Attendance
- Assignments
- Notices
- Faculty information
- Admin/student views
- Contact and FAQ sections
- Responsive navigation and theme support
- Frontend form validation
- Dynamic JSON data using Fetch API
- Search, filter, sort, and pagination

## ✨ Practical Features

### Practical 5 — Registration Validation

The registration module demonstrates:

- HTML5 form input types and attributes
- Regular Expression validation
- Name, email, mobile, password, confirm-password validation
- Course, year, gender, and terms validation
- Field-specific error messages
- Password-strength feedback
- Real-time keyup/change validation
- Keyboard and screen-reader-friendly labels
- ARIA validation attributes
- Canvas-based CAPTCHA

### Practical 6 — External JSON + Fetch API

The portal demonstrates:

- events.json
- students.json
- faqs.json
- Fetch API
- JSON parsing
- Dynamic DOM rendering
- Loading and error states
- Search
- Filter
- Sort
- Pagination
- Modular JavaScript
- Country → State → City dependent dropdowns
- LocalStorage caching

## 🛠️ Technologies

| Technology | Use |
|---|---|
| HTML5 | Structure and forms |
| CSS3 | Styling and responsive design |
| JavaScript ES6+ | Interactivity and logic |
| Regular Expressions | Validation |
| Fetch API | Loading JSON |
| JSON | Structured data |
| DOM API | Dynamic rendering |
| LocalStorage | Client-side caching |
| Canvas API | CAPTCHA |
| Font Awesome | Icons |

## 📁 Typical Structure


Student-Hub-Portal/
├── index.html
├── home.html
├── login.html
├── register.html
├── forgot-password.html
├── forgot-username.html
├── profile.html
├── courses.html
├── timetable.html
├── attendance.html
├── assignment.html
├── notice.html
├── faculty.html
├── contactus.html
├── admin.html
├── style.css
├── script.js
├── validation.js
├── events.json
├── students.json
└── faqs.json


> The actual repository structure is the source of truth if filenames differ.

## 🚀 Run Locally

git clone https://github.com/hemangikorat196-1105/Student-Hub-Portal

Open the project in VS Code and run it using a local server such as **Live Server**.

A local server is recommended because Fetch API requests for local JSON can fail when HTML is opened directly with `file://`.

## 🔄 Data Flow


External JSON
     ↓
Fetch API
     ↓
JSON.parse / response.json()
     ↓
JavaScript arrays
     ↓
Search → Filter → Sort
     ↓
Pagination
     ↓
Dynamic rendering

## 🔐 Security Note

This is an educational frontend project. Client-side validation is not a security boundary. A production application should use secure server-side validation, authentication, authorization, password hashing, HTTPS, secure sessions/tokens, and appropriate input protection.

Do not store real passwords, tokens, or sensitive personal information in JSON files or LocalStorage.

## ♿ Accessibility

The project aims to provide:

- Semantic HTML
- Explicit form labels
- Keyboard navigation
- Visible validation feedback
- aria-invalid
- aria-describedby
- Accessible form controls

## 📱 Responsive Design

The portal is designed for:

- Desktop
- Tablet
- Mobile

## 🧪 Testing

### Registration

- Valid and invalid name
- Email format
- Mobile format
- Password requirements
- Confirm-password match
- Course/year/gender
- Terms acceptance
- CAPTCHA
- Real-time validation
- Keyboard navigation

### Fetch/JSON

- Successful JSON loading
- Loading state
- Fetch error handling
- Dynamic rendering
- Search
- Filter
- Sort
- Pagination
- Combined controls
- Dependent dropdowns
- LocalStorage cache

## 🎯 Learning Outcomes

This project demonstrates HTML5, CSS3, JavaScript, DOM manipulation, events, Regular Expressions, form validation, Fetch API, JSON, array operations, pagination, LocalStorage, Canvas, modular JavaScript, accessibility, and responsive UI design.

## 🔮 Future Enhancements

- Backend REST API
- Database integration
- Secure authentication
- Role-based access control
- Real-time notifications
- Results management
- Attendance analytics
- Faculty dashboard
- Admin dashboard
- PWA support
- Automated testing
- CI/CD deployment

## 👥 Contributors

| Name | Role |
|---|---|
| Hemangi | Frontend / Project |

## 📄 License

Developed for educational and academic purposes.
