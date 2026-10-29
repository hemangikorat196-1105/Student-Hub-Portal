"""Static and optional XAMPP integration checks for Student Hub Portal."""

import http.cookiejar
import json
import os
from pathlib import Path
import re
import urllib.error
import urllib.parse
import urllib.request

PROJECT_DIR = Path(__file__).resolve().parents[1]
BASE_URL = os.environ.get(
    "STUDENTHUB_BASE_URL",
    "http://localhost/Student%20Hub%20Portal",
).rstrip("/")
STUDENT_ID = os.environ.get("STUDENTHUB_TEST_STUDENT_ID", "25CS035")
STUDENT_PASSWORD = os.environ.get("STUDENTHUB_TEST_STUDENT_PASSWORD", "Student@123")
TABLES = ("users", "students", "events", "registrations", "audit_logs")


def report(name, passed, detail):
    print(f"[{'PASS' if passed else 'FAIL'}] {name}: {detail}")
    return passed


def test_required_files():
    required = (
        "index.html", "Register.html", "Login.html", "admin-login.html",
        "Attendance.php", "Assignment.php", "Timetable.php",
        "php/auth/login.php", "php/auth/admin_login.php",
        "php/auth/admin_logout.php", "php/auth/register.php",
        "php/students/dashboard.php", "php/admin/dashboard.php",
        "php/includes/auth_guard.php", "php/includes/csrf.php",
        "php/api/students.php", "php/api/events.php", "php/api/faqs.php",
        "sql/schema.sql", "sql/seed_data.sql", "docs/setup_guide.md",
        "docs/practical_checklist.md", "docs/testing_report.md",
    )
    missing = [item for item in required if not (PROJECT_DIR / item).is_file()]
    return report("Required project files", not missing, f"missing={missing or 'none'}")


def test_json_and_sql_counts():
    passed = True
    data_sets = {}
    for entity in ("students", "events", "faqs"):
        canonical = json.loads((PROJECT_DIR / "data" / f"{entity}.json").read_text(encoding="utf-8"))
        compatibility = json.loads((PROJECT_DIR / f"{entity}.json").read_text(encoding="utf-8"))
        count_ok = isinstance(canonical, list) and len(canonical) == 15
        copy_ok = canonical == compatibility
        passed &= report(
            f"JSON {entity}",
            count_ok and copy_ok,
            f"records={len(canonical)}; root copy identical={copy_ok}",
        )
        data_sets[entity] = canonical

    seed = (PROJECT_DIR / "sql" / "seed_data.sql").read_text(encoding="utf-8")
    insert_positions = {
        table: seed.find(f"INSERT INTO `{table}`") for table in TABLES
    }
    for table in TABLES:
        start = insert_positions[table]
        next_positions = [position for position in insert_positions.values() if position > start]
        section = seed[start:min(next_positions)] if start >= 0 and next_positions else seed[start:]
        count = len(re.findall(r"^\s*\(\d+,", section, re.MULTILINE)) if start >= 0 else 0
        passed &= report(f"SQL seed {table}", count == 15, f"records={count}; required=15")
    return passed


def test_role_separation_source():
    student_login = (PROJECT_DIR / "php/auth/login.php").read_text(encoding="utf-8")
    admin_login = (PROJECT_DIR / "php/auth/admin_login.php").read_text(encoding="utf-8")
    register = (PROJECT_DIR / "php/auth/register.php").read_text(encoding="utf-8")
    guard = (PROJECT_DIR / "php/includes/auth_guard.php").read_text(encoding="utf-8")
    checks = {
        "fixed student login role": "$requiredRole = defined('AUTH_REQUIRED_ROLE') ? AUTH_REQUIRED_ROLE : 'student';" in student_login,
        "admin login fixes admin role": "define('AUTH_REQUIRED_ROLE', 'admin')" in admin_login,
        "registration hard-codes student role": "'student', 'active'" in register and "role']" not in register,
        "protected role checked in database": "SELECT role, status FROM users WHERE id = ?" in guard,
    }
    return all(report(name, passed, "source assertion") for name, passed in checks.items())


def opener_with_session():
    cookies = http.cookiejar.CookieJar()
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cookies))


def get_json(opener, path):
    request = urllib.request.Request(
        f"{BASE_URL}{path}",
        headers={"Accept": "application/json", "User-Agent": "StudentHubVerification/1.0"},
    )
    try:
        with opener.open(request, timeout=5) as response:
            return response.status, response.headers.get("Content-Type", ""), json.loads(response.read())
    except urllib.error.HTTPError as response:
        return response.code, response.headers.get("Content-Type", ""), json.loads(response.read())


def post_form(opener, path, values):
    body = urllib.parse.urlencode(values).encode("utf-8")
    request = urllib.request.Request(
        f"{BASE_URL}{path}",
        data=body,
        headers={"Content-Type": "application/x-www-form-urlencoded"},
    )
    return opener.open(request, timeout=5)


def csrf_for(opener):
    _, _, payload = get_json(opener, "/php/auth/check_session.php")
    return payload["data"]["csrfToken"]


def test_http_security_flow():
    try:
        with urllib.request.urlopen(f"{BASE_URL}/index.html", timeout=3) as response:
            if response.status != 200:
                return report("XAMPP HTTP availability", False, f"HTTP {response.status}")
    except Exception as error:
        return report("XAMPP HTTP availability", False, f"server unavailable: {error}")

    results = []
    anonymous = opener_with_session()
    try:
        status, content_type, payload = get_json(anonymous, "/php/api/students.php")
        results.append(report(
            "Anonymous student API denied",
            status == 401 and "application/json" in content_type and payload.get("success") is False,
            f"HTTP {status}, JSON={content_type}",
        ))
    except Exception as error:
        results.append(report("Anonymous student API denied", False, str(error)))

    student = opener_with_session()
    try:
        token = csrf_for(student)
        response = post_form(student, "/php/auth/login.php", {
            "studentId": STUDENT_ID,
            "password": STUDENT_PASSWORD,
            "csrf_token": token,
        })
        results.append(report(
            "Student login destination",
            response.geturl().endswith("/php/students/dashboard.php"),
            response.geturl(),
        ))

        try:
            student.open(f"{BASE_URL}/php/admin/dashboard.php", timeout=5)
            results.append(report("Student denied admin dashboard", False, "request was allowed"))
        except urllib.error.HTTPError as error:
            results.append(report("Student denied admin dashboard", error.code == 403, f"HTTP {error.code}"))

        status, content_type, payload = get_json(student, "/php/students/get.php?id=2")
        actual_id = (payload.get("data") or {}).get("student_id")
        results.append(report(
            "Student lookup is account-scoped",
            status == 200 and actual_id == STUDENT_ID,
            f"HTTP {status}; returned student_id={actual_id}",
        ))

        token = csrf_for(student)
        post_form(student, "/php/auth/logout.php", {"csrf_token": token})
        results.append(report("Student logout", True, "POST with session CSRF token accepted"))
    except Exception as error:
        results.append(report("Student authentication flow", False, str(error)))

    wrong_role = opener_with_session()
    try:
        token = csrf_for(wrong_role)
        try:
            post_form(wrong_role, "/php/auth/admin_login.php", {
                "username": STUDENT_ID,
                "password": STUDENT_PASSWORD,
                "csrf_token": token,
            })
            results.append(report("Student credentials rejected by admin login", False, "request was allowed"))
        except urllib.error.HTTPError as error:
            results.append(report(
                "Student credentials rejected by admin login",
                error.code == 403,
                f"HTTP {error.code}",
            ))
    except Exception as error:
        results.append(report("Student credentials rejected by admin login", False, str(error)))

    admin_password = os.environ.get("STUDENTHUB_ADMIN_PASSWORD")
    if admin_password:
        admin = opener_with_session()
        try:
            token = csrf_for(admin)
            response = post_form(admin, "/php/auth/admin_login.php", {
                "username": os.environ.get("STUDENTHUB_ADMIN_USERNAME", "admin"),
                "password": admin_password,
                "csrf_token": token,
            })
            results.append(report(
                "Configured admin login destination",
                response.geturl().endswith("/php/admin/dashboard.php"),
                response.geturl(),
            ))
        except Exception as error:
            results.append(report("Configured admin login", False, str(error)))
    else:
        print("[SKIP] Admin login: set STUDENTHUB_ADMIN_PASSWORD locally to test")

    return all(results)


def test_database_connection():
    try:
        _, _, payload = get_json(opener_with_session(), "/test_connection.php")
        actual = payload.get("tables", {})
        checks = [actual.get(table) == 15 for table in TABLES]
        return report("Live MySQL seed counts", all(checks), str(actual))
    except Exception as error:
        return report("Live MySQL seed counts", False, f"unavailable: {error}")


def main():
    print("Student Hub Portal — static and optional XAMPP verification")
    checks = [
        test_required_files(),
        test_json_and_sql_counts(),
        test_role_separation_source(),
    ]
    http_ok = test_http_security_flow()
    database_ok = test_database_connection() if http_ok else False
    if http_ok:
        checks.extend((http_ok, database_ok))
    else:
        print("[SKIP] Database checks: start Apache and MySQL and import the SQL files")
    print(f"\nStatic checks: {'PASS' if all(checks[:3]) else 'FAIL'}")
    print(f"Live integration: {'PASS' if http_ok and database_ok else 'NOT PASSED'}")
    return 0 if all(checks[:3]) and http_ok and database_ok else 1


if __name__ == "__main__":
    raise SystemExit(main())
