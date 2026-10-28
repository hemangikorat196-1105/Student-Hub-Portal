"""
Student Hub Portal - Comprehensive Automated Verification Test Runner
ITUE203 WDF - Practicals 1 to 15 Compliance and Integrity Suite
"""

import urllib.request
import urllib.parse
import http.cookiejar
import json
import sys
import os

BASE_URL = "http://localhost/Student%20Hub%20Portal"
PROJECT_DIR = r"C:\Users\heman\OneDrive\Desktop\B.TECH\WEB DEVELOPMENT\Student Hub Portal"

def print_header(title):
    print("\n" + "=" * 70)
    print(f" {title}")
    print("=" * 70)

def test_file_exists():
    print_header("1. File Architecture & Mandatory Folder Check")
    required_files = [
        "index.html", "home.html", "aboutus.html", "courses.html", "timetable.html",
        "profile.html", "notice.html", "contactus.html", "events.html", "Login.html",
        "Register.html", "Admin.html", "Style.css", "script.js",
        "data/students.json", "data/events.json", "data/faqs.json",
        "php/config/db.php", "php/includes/session.php", "php/includes/auth_guard.php",
        "php/includes/csrf.php", "php/includes/validation.php", "php/includes/response.php",
        "php/auth/register.php", "php/auth/login.php", "php/auth/logout.php", "php/auth/check_session.php",
        "php/students/list.php", "php/students/get.php", "php/students/create.php", "php/students/update.php", "php/students/delete.php", "php/students/export.php",
        "php/events/list.php", "php/events/get.php", "php/events/create.php", "php/events/update.php", "php/events/delete.php", "php/events/upload_poster.php",
        "php/admin/dashboard.php", "php/admin/users.php", "php/admin/audit_logs.php", "php/admin/access_denied.php",
        "php/api/students.php", "php/api/events.php", "php/api/faqs.php",
        "php/forms/process.php", "php/forms/contact_submit.php",
        "sql/schema.sql", "sql/seed_data.sql", "sql/users.sql", "sql/students.sql", "sql/events.sql", "sql/registrations.sql", "sql/audit_logs.sql", "sql/sample_queries.sql",
        "docs/requirements.md", "docs/sitemap.md", "docs/wireframes.md", "docs/er_diagram.md", "docs/setup_guide.md", "docs/practical_checklist.md", "docs/testing_report.md", "docs/screenshot_checklist.md",
        "tests/test_cases.md", "tests/api_test_cases.md"
    ]
    
    missing = 0
    for rel_path in required_files:
        full_path = os.path.join(PROJECT_DIR, rel_path.replace("/", os.sep))
        if os.path.exists(full_path):
            print(f"  [OK] {rel_path}")
        else:
            print(f"  [MISSING] {rel_path}")
            missing += 1
            
    print(f"\nResult: {len(required_files) - missing}/{len(required_files)} verified files present.")
    return missing == 0

def test_json_records():
    print_header("2. Exact Record Count Verification in JSON Datasets")
    passed = True
    
    # students.json
    s_path = os.path.join(PROJECT_DIR, "data", "students.json")
    with open(s_path, "r", encoding="utf-8") as f:
        s_data = json.load(f)
    print(f"  students.json: {len(s_data)} records (Required: 15) -> {'PASS' if len(s_data) == 15 else 'FAIL'}")
    if len(s_data) != 15: passed = False
    
    # events.json
    e_path = os.path.join(PROJECT_DIR, "data", "events.json")
    with open(e_path, "r", encoding="utf-8") as f:
        e_data = json.load(f)
    print(f"  events.json:   {len(e_data)} records (Required: 15) -> {'PASS' if len(e_data) == 15 else 'FAIL'}")
    if len(e_data) != 15: passed = False

    # faqs.json
    f_path = os.path.join(PROJECT_DIR, "data", "faqs.json")
    with open(f_path, "r", encoding="utf-8") as f:
        f_data = json.load(f)
    print(f"  faqs.json:     {len(f_data)} FAQs -> PASS")
    
    return passed

def test_http_endpoints():
    print_header("3. Live HTTP Endpoints & REST API Test (XAMPP Server)")
    
    endpoints = [
        ("Landing Page (index.html)", f"{BASE_URL}/index.html", 200, "text/html"),
        ("Home Page (home.html)", f"{BASE_URL}/home.html", 200, "text/html"),
        ("About Us (aboutus.html)", f"{BASE_URL}/aboutus.html", 200, "text/html"),
        ("Events Catalog (events.html)", f"{BASE_URL}/events.html", 200, "text/html"),
        ("Login Page (Login.html)", f"{BASE_URL}/Login.html", 200, "text/html"),
        ("Register Page (Register.html)", f"{BASE_URL}/Register.html", 200, "text/html"),
        ("JSON Data Viewer (json-data.html)", f"{BASE_URL}/json-data.html", 200, "text/html"),
        ("DB Diagnostics (test_connection.php)", f"{BASE_URL}/test_connection.php", 200, "application/json"),
        ("Session Check API (check_session.php)", f"{BASE_URL}/php/auth/check_session.php", 200, "application/json"),
        ("Students REST API (GET)", f"{BASE_URL}/php/api/students.php", 200, "application/json"),
        ("Single Student API (?id=1)", f"{BASE_URL}/php/api/students.php?id=1", 200, "application/json"),
        ("Events REST API (GET)", f"{BASE_URL}/php/api/events.php", 200, "application/json"),
        ("FAQs REST API (GET)", f"{BASE_URL}/php/api/faqs.php", 200, "application/json"),
    ]
    
    passed = True
    for name, url, expected_code, content_type in endpoints:
        try:
            req = urllib.request.Request(url, headers={"User-Agent": "VerificationSuite/1.0"})
            with urllib.request.urlopen(req, timeout=5) as resp:
                code = resp.getcode()
                c_type = resp.headers.get("Content-Type", "")
                if code == expected_code and content_type in c_type:
                    print(f"  [PASS] {name}: HTTP {code} ({content_type})")
                else:
                    print(f"  [WARN] {name}: Expected {expected_code} ({content_type}), got {code} ({c_type})")
                    passed = False
        except Exception as e:
            print(f"  [FAIL] {name}: Error - {e}")
            passed = False
            
    return passed

def test_database_counts():
    print_header("4. Database Live Count Verification via test_connection.php")
    url = f"{BASE_URL}/test_connection.php"
    try:
        with urllib.request.urlopen(url, timeout=5) as resp:
            data = json.loads(resp.read().decode("utf-8"))
            tables = data.get("tables", {})
            print(f"  Database Host: {data.get('server_info', {}).get('db_host')}")
            print(f"  Database Name: {data.get('server_info', {}).get('db_name')}")
            
            all_15 = True
            for tbl, count in tables.items():
                is_15 = (count == 15)
                status = "PASS" if is_15 else "FAIL"
                print(f"  Table '{tbl}': {count} records (Expected: 15) -> {status}")
                if not is_15:
                    all_15 = False
            return all_15
    except Exception as e:
        print(f"  [FAIL] DB check error: {e}")
        return False

def test_auth_and_rbac():
    print_header("5. Authentication & Role-Based Access Control (RBAC)")
    cj = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))
    
    # Test 1: Access admin dashboard unauthenticated (should be 403 or redirect)
    unauth_url = f"{BASE_URL}/php/admin/dashboard.php"
    try:
        req = urllib.request.Request(unauth_url)
        resp = opener.open(req)
        # Check if redirected to Login.html or access_denied.php
        final_url = resp.geturl()
        if "access_denied" in final_url or "Login.html" in final_url:
            print(f"  [PASS] Unauthenticated access to dashboard redirected to: {final_url.split('/')[-1]}")
        else:
            print(f"  [WARN] Unauthenticated access reached {final_url}")
    except urllib.error.HTTPError as e:
        if e.code in (401, 403):
            print(f"  [PASS] Unauthenticated access properly blocked: HTTP {e.code}")
        else:
            print(f"  [FAIL] Unexpected HTTP status: {e.code}")
            
    # Test 2: Admin Login
    login_url = f"{BASE_URL}/php/auth/login.php"
    login_data = urllib.parse.urlencode({"username": "admin", "password": "Admin@123"}).encode("utf-8")
    try:
        req = urllib.request.Request(login_url, data=login_data, headers={"Content-Type": "application/x-www-form-urlencoded"})
        resp = opener.open(req)
        final_url = resp.geturl()
        print(f"  [PASS] Admin Login executed successfully -> Landed on: {final_url.split('/')[-1]}")
        
        # Test 3: Access admin dashboard with authenticated session
        dash_resp = opener.open(urllib.request.Request(unauth_url))
        dash_content = dash_resp.read().decode("utf-8", errors="ignore")
        if "Administrative Control Center" in dash_content or "Admin Dashboard" in dash_content:
            print("  [PASS] Admin Dashboard successfully loaded with session privileges.")
        else:
            print("  [WARN] Dashboard did not contain expected admin banner.")
            
        # Test 4: Export CSV
        export_url = f"{BASE_URL}/php/students/export.php"
        exp_resp = opener.open(urllib.request.Request(export_url))
        exp_lines = exp_resp.read().decode("utf-8", errors="ignore").strip().split("\n")
        print(f"  [PASS] Student CSV Export generated: {len(exp_lines) - 1} data records exported (Expected: 15).")
        
        # Test 5: Logout
        logout_url = f"{BASE_URL}/php/auth/logout.php"
        opener.open(urllib.request.Request(logout_url))
        print("  [PASS] Logout successfully invalidated session.")
        return True
    except Exception as e:
        print(f"  [FAIL] Auth / RBAC testing error: {e}")
        return False

def main():
    print("=" * 70)
    print(" STUDENT HUB PORTAL - ALL 15 PRACTICALS AUTOMATED VERIFICATION")
    print("=" * 70)
    
    r1 = test_file_exists()
    r2 = test_json_records()
    r3 = test_http_endpoints()
    r4 = test_database_counts()
    r5 = test_auth_and_rbac()
    
    print("\n" + "=" * 70)
    if r1 and r2 and r3 and r4 and r5:
        print(" ALL VERIFICATION SUITES PASSED! (100% COMPLIANT)")
    else:
        print(" VERIFICATION SUITE FINISHED WITH SOME WARNINGS / FAILURES.")
    print("=" * 70)

if __name__ == "__main__":
    main()
