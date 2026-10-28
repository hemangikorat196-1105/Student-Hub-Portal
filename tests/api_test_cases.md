# Student Hub Portal — REST API Test Specifications
## ITUE203 WDF — Practical 13 API Documentation & Test Cases

This document details the RESTful JSON API specifications, request structures, expected status codes, response contracts, and test cases implemented under `php/api/`.

---

## 1. Global API Standards & Conventions

- **Base URL**: `http://localhost/Student%20Hub%20Portal/php/api/`
- **Content-Type**: `application/json; charset=UTF-8`
- **Standard Response Envelope**:
```json
{
  "status": 200,
  "success": true,
  "message": "Human readable status message",
  "data": [],
  "timestamp": "2026-10-27T11:30:00+05:30"
}
```
- **HTTP Status Codes Used**:
  - `200 OK`: Successful retrieval or update.
  - `201 Created`: Resource successfully created.
  - `400 Bad Request`: Validation failure or malformed payload.
  - `401 Unauthorized`: Missing or invalid session / credentials.
  - `403 Forbidden`: Insufficient role permissions.
  - `404 Not Found`: Requested resource does not exist.
  - `405 Method Not Allowed`: HTTP verb not supported by endpoint.
  - `500 Internal Server Error`: Server exception (details logged, not leaked).

---

## 2. API Test Cases

### 2.1 Students API (`/php/api/students.php`)

#### TC-API-STU-01: GET All Students
- **Method**: `GET`
- **Endpoint**: `/php/api/students.php`
- **Query Params**: `?page=1&limit=10&course=B.Tech+CSE` (Optional)
- **Expected Status**: `200 OK`
- **Expected Response**:
```json
{
  "status": 200,
  "success": true,
  "message": "Students retrieved successfully",
  "data": [
    {
      "id": 1,
      "student_id": "25CS035",
      "full_name": "Hemangi Korat",
      "email": "hemangi.korat@university.edu",
      "mobile": "9876543210",
      "course": "B.Tech Computer Science",
      "semester": 3,
      "cgpa": "9.45",
      "status": "active"
    }
  ],
  "meta": {
    "total": 15,
    "page": 1,
    "limit": 10
  }
}
```

#### TC-API-STU-02: GET Single Student by ID
- **Method**: `GET`
- **Endpoint**: `/php/api/students.php?id=1`
- **Expected Status**: `200 OK`
- **Expected Response**:
```json
{
  "status": 200,
  "success": true,
  "message": "Student found",
  "data": {
    "id": 1,
    "student_id": "25CS035",
    "full_name": "Hemangi Korat",
    "email": "hemangi.korat@university.edu"
  }
}
```

#### TC-API-STU-03: GET Non-Existent Student (404)
- **Method**: `GET`
- **Endpoint**: `/php/api/students.php?id=99999`
- **Expected Status**: `404 Not Found`
- **Expected Response**:
```json
{
  "status": 404,
  "success": false,
  "message": "Student record not found",
  "data": null
}
```

#### TC-API-STU-04: POST Create Student (201)
- **Method**: `POST`
- **Endpoint**: `/php/api/students.php`
- **Headers**: `Content-Type: application/json`
- **Payload**:
```json
{
  "student_id": "25CS099",
  "full_name": "Aditya Verma",
  "email": "aditya.v@university.edu",
  "mobile": "9876500001",
  "gender": "Male",
  "course": "B.Tech Computer Science",
  "semester": 3
}
```
- **Expected Status**: `201 Created`
- **Expected Response**:
```json
{
  "status": 201,
  "success": true,
  "message": "Student created successfully",
  "data": {
    "id": 16,
    "student_id": "25CS099"
  }
}
```

#### TC-API-STU-05: PUT Update Student
- **Method**: `PUT`
- **Endpoint**: `/php/api/students.php`
- **Payload**:
```json
{
  "id": 1,
  "cgpa": "9.50",
  "semester": 4
}
```
- **Expected Status**: `200 OK`
- **Expected Response**:
```json
{
  "status": 200,
  "success": true,
  "message": "Student updated successfully",
  "data": {
    "id": 1,
    "updated_fields": ["cgpa", "semester"]
  }
}
```

#### TC-API-STU-06: DELETE Student (Soft Delete)
- **Method**: `DELETE`
- **Endpoint**: `/php/api/students.php?id=16`
- **Expected Status**: `200 OK`
- **Expected Response**:
```json
{
  "status": 200,
  "success": true,
  "message": "Student successfully deactivated",
  "data": { "id": 16, "status": "inactive" }
}
```

---

### 2.2 Events API (`/php/api/events.php`)

#### TC-API-EVT-01: GET All Events
- **Method**: `GET`
- **Endpoint**: `/php/api/events.php`
- **Query Params**: `?category=Technical` (Optional)
- **Expected Status**: `200 OK`
- **Expected Response**:
```json
{
  "status": 200,
  "success": true,
  "message": "Events retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Hackathon 2026: CodeFest",
      "category": "Technical",
      "venue": "Central Auditorium & Computer Labs",
      "max_capacity": 200,
      "status": "upcoming"
    }
  ]
}
```

#### TC-API-EVT-02: Invalid HTTP Method (405)
- **Method**: `PATCH`
- **Endpoint**: `/php/api/events.php`
- **Expected Status**: `405 Method Not Allowed`
- **Expected Response**:
```json
{
  "status": 405,
  "success": false,
  "message": "Method PATCH not allowed on this endpoint",
  "data": null
}
```

---

### 2.3 FAQs API (`/php/api/faqs.php`)

#### TC-API-FAQ-01: GET FAQs with Keyword Search
- **Method**: `GET`
- **Endpoint**: `/php/api/faqs.php?q=password`
- **Expected Status**: `200 OK`
- **Expected Response**: Array of FAQ questions and answers matching keyword "password".
