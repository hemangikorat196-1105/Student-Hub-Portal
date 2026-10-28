# Student Hub Portal — Complete Setup & Deployment Guide
## ITUE203 WDF — XAMPP, Apache, MariaDB, and Testing Environment

This guide provides exhaustive, step-by-step instructions for running, configuring, and testing the **Student Hub Portal** in a standard XAMPP environment on Windows.

---

## 1. System Requirements & Prerequisites

- **Operating System**: Windows 10/11 (or macOS / Linux with appropriate path adjustments)
- **Web Server**: Apache 2.4.x (bundled with XAMPP)
- **Database**: MariaDB 10.4.x or MySQL 8.0+
- **PHP**: PHP 8.1 or higher (PHP 8.2 recommended)
  - Required Extensions: `pdo_mysql`, `mysqli`, `fileinfo`, `session`, `json`, `mbstring`
- **Modern Web Browser**: Google Chrome, Mozilla Firefox, Microsoft Edge, or Safari

---

## 2. Directory Architecture & Installation

### Option A: Standard Direct Installation (Recommended)
Place the project directory directly inside the XAMPP `htdocs` directory:
```text
C:\xampp\htdocs\Student Hub Portal\
```

### Option B: Windows NTFS Junction (Development Mode)
If your active git development workspace is located in another folder (e.g., `OneDrive\Desktop\B.TECH\WEB DEVELOPMENT\Student Hub Portal`), create an NTFS Directory Junction so Apache serves it directly without manual file copying:

Open **PowerShell** or **Command Prompt as Administrator** and execute:
```cmd
mklink /J "C:\xampp\htdocs\Student Hub Portal" "C:\Users\heman\OneDrive\Desktop\B.TECH\WEB DEVELOPMENT\Student Hub Portal"
```

Verify the link responds:
```powershell
Get-Item "C:\xampp\htdocs\Student Hub Portal"
```

---

## 3. Starting Apache and MariaDB Services

1. Launch **XAMPP Control Panel** (`C:\xampp\xampp-control.exe`).
2. Click **Start** next to **Apache** (Default Port: 80 / 443).
3. Click **Start** next to **MySQL** (Default Port: 3306).
4. Verify both show green "Running" status.

*Alternative via Command Line (PowerShell):*
```powershell
# Start Apache
Start-Process "C:\xampp\apache\bin\httpd.exe" -ArgumentList "-d C:\xampp\apache"

# Start MySQL / MariaDB (if not running as a Windows service)
Start-Process "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini"
```

---

## 4. Database Setup & SQL Import

### Method 1: Automated Command Line (Fastest)
Run the following commands in PowerShell from the project root:

```powershell
# 1. Create the database and import 3NF schema
Get-Content "sql\schema.sql" | C:\xampp\mysql\bin\mysql.exe -u root

# 2. Populate verified 15-record dataset across all tables
Get-Content "sql\seed_data.sql" | C:\xampp\mysql\bin\mysql.exe -u root student_portal
```

### Method 2: phpMyAdmin Web Interface
1. Open your browser and navigate to: `http://localhost/phpmyadmin/`
2. Click on **Databases** tab in the top navigation.
3. Enter `student_portal` in the "Create database" field, select `utf8mb4_unicode_ci` as collation, and click **Create**.
4. Select the newly created `student_portal` database from the left sidebar.
5. Click on the **Import** tab.
6. Click **Choose File** and browse to:
   `C:\xampp\htdocs\Student Hub Portal\sql\schema.sql`
7. Click **Import** (or **Go**) at the bottom.
8. Once imported, click on the **Import** tab again.
9. Choose `C:\xampp\htdocs\Student Hub Portal\sql\seed_data.sql` and click **Import**.
10. Verify that 5 tables are listed (`audit_logs`, `events`, `registrations`, `students`, `users`) and each displays **15 records**.

---

## 5. Verifying Database Connection

Test the database configuration by opening the diagnostic connection script in your browser:
```text
http://localhost/Student%20Hub%20Portal/test_connection.php
```

**Expected JSON Response:**
```json
{
  "status": "success",
  "message": "Database connected successfully via PDO and MySQLi!",
  "server_info": {
    "php_version": "8.2.12",
    "db_host": "localhost",
    "db_name": "student_portal"
  },
  "tables": {
    "users": 15,
    "students": 15,
    "events": 15,
    "registrations": 15,
    "audit_logs": 15
  }
}
```

---

## 6. Default Test Credentials

All passwords in `sql/seed_data.sql` are securely hashed using PHP `password_hash($pwd, PASSWORD_BCRYPT)`.

| Role | Username | Password | Access Level / Destination |
|---|---|---|---|
| **Administrator** | `admin` | `Admin@123` | Full access: Admin Dashboard, Users, Audit Logs, CRUD operations |
| **Student (Demo)** | `25CS035` | `Student@123` | Student Profile, Event Registration, View Courses & Timetable |
| **Student 2** | `25CS001` | `Student@123` | Student Profile & Event Registration |
| **Student 3** | `25CS002` | `Student@123` | Student Profile & Event Registration |

---

## 7. Running the Web Application

Open any modern browser and visit:
```text
http://localhost/Student%20Hub%20Portal/
```
Or start directly from the landing page:
```text
http://localhost/Student%20Hub%20Portal/index.html
```

### Key Application Routes:
- **Landing Page**: `http://localhost/Student%20Hub%20Portal/index.html`
- **Main Home**: `http://localhost/Student%20Hub%20Portal/home.html`
- **About Us**: `http://localhost/Student%20Hub%20Portal/aboutus.html`
- **Student Registration**: `http://localhost/Student%20Hub%20Portal/Register.html`
- **Login Portal**: `http://localhost/Student%20Hub%20Portal/Login.html`
- **Admin Dashboard**: `http://localhost/Student%20Hub%20Portal/php/admin/dashboard.php`
- **Student Management**: `http://localhost/Student%20Hub%20Portal/php/students/list.php`
- **Event Management**: `http://localhost/Student%20Hub%20Portal/php/events/list.php`
- **Interactive JSON Data**: `http://localhost/Student%20Hub%20Portal/json-data.html`
- **Events Catalog**: `http://localhost/Student%20Hub%20Portal/events.html`

---

## 8. Dual Execution Mode (`file://` vs `http://`)

The Student Hub Portal supports dual-mode operation:
1. **Live Web Server (`http://localhost/...`)**: Full functionality with PHP processing, database read/write, session state, image uploads, and JSON REST APIs.
2. **Offline File Mode (`file:///...`)**: Interactive client-side features, DOM manipulation, and dynamic validations run seamlessly without breaking.

---

## 9. Troubleshooting Common Issues

### Issue 1: Apache Port Conflict (Port 80 occupied)
- **Cause**: Skype, IIS, or VMware using port 80.
- **Fix**: In XAMPP, click **Config** on Apache -> `httpd.conf`. Change `Listen 80` to `Listen 8080`. Update URL to `http://localhost:8080/Student%20Hub%20Portal/`.

### Issue 2: MySQL "Access denied for user 'root'@'localhost'"
- **Cause**: Root user has a password set.
- **Fix**: Open `php/config/db.php` and set `'pass' => 'your_password'`.

### Issue 3: Upload Permissions Error
- **Cause**: Windows write permissions restricted on `assets/uploads/event_posters/`.
- **Fix**: Ensure the folder exists. The script automatically sets `chmod 0755` and generates unique filenames.
