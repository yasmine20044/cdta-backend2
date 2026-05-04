# 🔒 CDTA Security Tests Documentation

This document explains the **automated security testing suite** for the CDTA Backend API. The tests are designed to verify that all security measures implemented in the application are working correctly.

---

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [How to Run the Tests](#how-to-run-the-tests)
4. [Test Categories](#test-categories)
   - [1. Security Headers](#1-security-headers)
   - [2. Authentication](#2-authentication)
   - [3. Authorization (RBAC)](#3-authorization-rbac)
   - [4. Input Validation & XSS Prevention](#4-input-validation--xss-prevention)
   - [5. SQL Injection Prevention](#5-sql-injection-prevention)
   - [6. Rate Limiting](#6-rate-limiting)
   - [7. IDOR Protection](#7-idor-protection)
   - [8. File Upload Security](#8-file-upload-security)
5. [How PHPUnit Works with Laravel](#how-phpunit-works-with-laravel)
6. [Test Architecture](#test-architecture)
7. [Understanding Test Results](#understanding-test-results)
8. [Adding New Tests](#adding-new-tests)
9. [OWASP Reference](#owasp-reference)

---

## Overview

The CDTA security test suite contains **~40 automated tests** organized into **8 test files**, each targeting a specific security domain from the **OWASP Top 10**. These tests run against an **in-memory SQLite database** (no real data is affected) and verify that:

- HTTP security headers are present on all responses
- Authentication (login, OTP, tokens) cannot be bypassed
- Role-based access control (admin, editor, user) is enforced
- User input is sanitized against XSS attacks
- SQL injection attacks are blocked
- Brute-force attacks are rate-limited
- Users cannot access resources they shouldn't (IDOR)
- Only valid image files can be uploaded

---

## Prerequisites

- **PHP 8.3+** installed and available in your PATH
- **Composer** dependencies installed (`composer install`)
- The `.env` file configured (the tests use their own SQLite database, but Laravel still needs the `.env` file to boot)

> **Note:** The tests do NOT require MySQL, a running server, or any external service. They use Laravel's built-in testing framework with an in-memory SQLite database.

---

## How to Run the Tests

### Option 1: Run All Security Tests (Recommended)

Double-click the batch file or run from the terminal:

```bash
# From the cdta-backend2 directory
.\run_security_tests.bat
```

### Option 2: Run via Artisan

```bash
php artisan test --filter Security
```

### Option 3: Run a Specific Test File

```bash
# Run only the XSS tests
php artisan test --filter InputValidationXssTest

# Run only the authentication tests
php artisan test --filter AuthenticationTest

# Run only the file upload tests
php artisan test --filter FileUploadSecurityTest
```

### Option 4: Run a Single Test Method

```bash
php artisan test --filter test_xss_script_tag_in_event_title_is_stripped
```

### Option 5: Run via PHPUnit Directly

```bash
./vendor/bin/phpunit tests/Feature/Security/
```

---

## Test Categories

### 1. Security Headers
**File:** `tests/Feature/Security/SecurityHeadersTest.php`

| Test | What It Verifies |
|------|-----------------|
| `test_response_has_x_frame_options_header` | `X-Frame-Options: SAMEORIGIN` is set (prevents clickjacking) |
| `test_response_has_x_xss_protection_header` | `X-XSS-Protection: 1; mode=block` is set (browser XSS filter) |
| `test_response_has_x_content_type_options_header` | `X-Content-Type-Options: nosniff` is set (prevents MIME sniffing) |
| `test_response_has_referrer_policy_header` | `Referrer-Policy` is set (controls referrer leakage) |
| `test_response_has_content_security_policy_header` | `Content-Security-Policy` with `default-src 'self'` is set |

**Security middleware tested:** `App\Http\Middleware\SecureHeaders`

---

### 2. Authentication
**File:** `tests/Feature/Security/AuthenticationTest.php`

| Test | What It Verifies |
|------|-----------------|
| `test_unauthenticated_user_cannot_access_protected_routes` | No token → 401 Unauthorized |
| `test_login_with_invalid_credentials_returns_401` | Wrong password → rejected |
| `test_login_with_nonexistent_email_returns_401` | Fake email → rejected |
| `test_otp_verification_with_wrong_code_returns_401` | Wrong OTP → rejected |
| `test_otp_verification_with_correct_code_returns_token` | Correct OTP → Sanctum token issued |
| `test_logout_invalidates_token` | After logout, old token is rejected |

**Components tested:** `AuthController`, Laravel Sanctum, OTP cache logic

---

### 3. Authorization (RBAC)
**File:** `tests/Feature/Security/AuthorizationTest.php`

| Test | What It Verifies |
|------|-----------------|
| `test_user_role_cannot_access_admin_routes` | `user` → POST `/users` = 403 |
| `test_editor_role_cannot_access_admin_routes` | `editor` → POST `/users` = 403 |
| `test_admin_role_can_access_admin_routes` | `admin` → POST `/users` = 200 ✅ |
| `test_editor_can_create_events` | `editor` → POST `/events` = 201 ✅ |
| `test_user_role_cannot_create_events` | `user` → POST `/events` = 403 |
| `test_user_role_cannot_delete_pages` | `user` → DELETE `/pages/1` = 403 |
| `test_user_role_cannot_login_to_dashboard` | `user` role login → 403 "Unauthorized" |

**Components tested:** `RoleMiddleware`, route middleware groups

---

### 4. Input Validation & XSS Prevention
**File:** `tests/Feature/Security/InputValidationXssTest.php`

| Test | What It Verifies |
|------|-----------------|
| `test_xss_script_tag_in_event_title_is_stripped` | `<script>` in title → removed by `strip_tags()` |
| `test_xss_script_tag_in_event_description_is_sanitized` | `<script>` in description → removed by HTMLPurifier |
| `test_xss_event_handler_attributes_are_sanitized` | `onmouseover`, `onerror` → removed by HTMLPurifier |
| `test_xss_in_page_content_is_sanitized` | `<script>`, `<iframe>` in page content → removed |
| `test_xss_in_news_content_is_sanitized` | `<script>` in news content → removed |
| `test_validation_rejects_missing_required_fields` | Missing `title` → 422 validation error |
| `test_validation_rejects_title_exceeding_max_length` | Title > 255 chars → 422 validation error |
| `test_xss_in_location_field_is_stripped` | `<img onerror=...>` in location → stripped |

**Components tested:** `strip_tags()`, `Mews\Purifier`, Laravel validation rules

---

### 5. SQL Injection Prevention
**File:** `tests/Feature/Security/SqlInjectionTest.php`

| Test | What It Verifies |
|------|-----------------|
| `test_sql_injection_in_login_email_is_blocked` | `' OR '1'='1' --` in email → rejected |
| `test_sql_injection_in_login_password_is_blocked` | SQL payload in password → rejected |
| `test_sql_injection_in_route_parameter_returns_404` | `1 OR 1=1` in URL → 404, not 200 |
| `test_sql_injection_union_select_in_route_parameter` | `UNION SELECT * FROM users` → no data leak |

**Why this works:** Laravel's Eloquent ORM uses **parameterized queries** (prepared statements), which inherently prevent SQL injection. These tests confirm that protection is in place.

---

### 6. Rate Limiting
**File:** `tests/Feature/Security/RateLimitingTest.php`

| Test | What It Verifies |
|------|-----------------|
| `test_login_rate_limit_blocks_after_5_requests` | 6th login attempt → 429 Too Many Requests |
| `test_otp_verification_rate_limit` | 6th OTP attempt → 429 Too Many Requests |

**Configuration tested:** `throttle:5,1` middleware (5 requests per 1 minute)

---

### 7. IDOR Protection
**File:** `tests/Feature/Security/IdorTest.php`

| Test | What It Verifies |
|------|-----------------|
| `test_accessing_nonexistent_resource_returns_404` | Fake ID → 404 (no information leak) |
| `test_user_role_cannot_delete_event_by_id` | `user` tries to delete admin's event → 403 |
| `test_user_role_cannot_update_service_by_id` | `user` tries to update admin's service → 403 |

**IDOR = Insecure Direct Object Reference:** An attacker modifies an ID in the URL to access someone else's data.

---

### 8. File Upload Security
**File:** `tests/Feature/Security/FileUploadSecurityTest.php`

| Test | What It Verifies |
|------|-----------------|
| `test_uploading_php_file_is_rejected` | `.php` file upload → 422 rejected |
| `test_uploading_executable_file_is_rejected` | `.exe` file upload → 422 rejected |
| `test_uploading_oversized_file_is_rejected` | 11MB file (limit is 10MB) → 422 rejected |
| `test_uploading_valid_jpeg_image_is_accepted` | Valid `.jpg` → 201 accepted ✅ |
| `test_double_extension_file_gets_uuid_filename` | `shell.php.jpg` → stored as UUID, not original name |

**Components tested:** Laravel validation rules (`image|mimes:jpg,jpeg,png|max:10240`), UUID filename storage

---

## How PHPUnit Works with Laravel

### The Testing Stack

```
┌──────────────────────────────────┐
│          PHPUnit 11.x            │  ← Test runner framework
├──────────────────────────────────┤
│     Laravel Testing Helpers      │  ← getJson(), postJson(), assertStatus()
├──────────────────────────────────┤
│   In-Memory SQLite Database      │  ← Fresh DB for each test (RefreshDatabase)
├──────────────────────────────────┤
│     Laravel Application          │  ← Full app boots for each test
└──────────────────────────────────┘
```

### Key Concepts

1. **`RefreshDatabase` trait**: Before each test, Laravel runs all migrations on the in-memory SQLite database, creating fresh tables. After each test, the database is rolled back. This means every test starts with a clean slate.

2. **`TestCase` class**: Our tests extend `Tests\TestCase`, which extends Laravel's base test case. This boots the full Laravel application for each test.

3. **HTTP test methods**: Laravel provides helper methods to simulate HTTP requests:
   - `$this->getJson('/api/...')` — simulates a GET request
   - `$this->postJson('/api/...')` — simulates a POST request
   - `$this->putJson('/api/...')` — simulates a PUT request
   - `$this->deleteJson('/api/...')` — simulates a DELETE request

4. **Assertions**: After making a request, we assert the expected behavior:
   - `->assertStatus(200)` — check HTTP status code
   - `->assertJson([...])` — check response body contains specific JSON
   - `->assertHeader('X-Frame-Options', 'SAMEORIGIN')` — check response headers
   - `->assertJsonValidationErrors(['field'])` — check validation errors

5. **`phpunit.xml` configuration**: Located in the project root, this file tells PHPUnit to:
   - Use SQLite in-memory database (no MySQL needed)
   - Use array cache (not Redis/Memcached)
   - Use array mail driver (no real emails sent)
   - Disable broadcasting, Pulse, Telescope, etc.

### How a Single Test Runs (Step by Step)

```
1. PHPUnit discovers the test method (e.g., test_login_with_invalid_credentials_returns_401)
2. Laravel boots the application using the testing environment (phpunit.xml)
3. RefreshDatabase runs all migrations on a fresh SQLite in-memory DB
4. The test creates a User directly in the database (test setup)
5. The test sends a POST request to /api/v1/login with wrong credentials
6. Laravel processes the request through middleware → controller → response
7. The test asserts the response status is 401
8. The database is rolled back (clean slate for the next test)
```

---

## Test Architecture

```
tests/
├── TestCase.php                          ← Base test class
├── Feature/
│   ├── ExampleTest.php                   ← Default Laravel test
│   └── Security/                         ← 🔒 Our security tests
│       ├── SecurityHeadersTest.php       ← HTTP headers (5 tests)
│       ├── AuthenticationTest.php        ← Login/OTP/Token (6 tests)
│       ├── AuthorizationTest.php         ← Role-based access (7 tests)
│       ├── InputValidationXssTest.php    ← XSS & validation (8 tests)
│       ├── SqlInjectionTest.php          ← SQL injection (4 tests)
│       ├── RateLimitingTest.php          ← Brute-force protection (2 tests)
│       ├── IdorTest.php                  ← Object reference (3 tests)
│       └── FileUploadSecurityTest.php    ← File upload (5 tests)
└── Unit/
    └── ExampleTest.php                   ← Default Laravel test
```

---

## Understanding Test Results

When you run the tests, you'll see output like this:

```
   PASS  Tests\Feature\Security\SecurityHeadersTest
  ✓ response has x frame options header
  ✓ response has x xss protection header
  ✓ response has x content type options header
  ✓ response has referrer policy header
  ✓ response has content security policy header

   PASS  Tests\Feature\Security\AuthenticationTest
  ✓ unauthenticated user cannot access protected routes
  ✓ login with invalid credentials returns 401
  ...

  Tests:    40 passed (80 assertions)
  Duration: 2.45s
```

- **✓ (green)**: Test passed — security measure is working
- **✗ (red)**: Test failed — a security vulnerability may exist!
- **Duration**: How long the full suite took to run

### If a Test Fails

A failing test means a security control is not working as expected. For example:

```
FAIL  Tests\Feature\Security\SecurityHeadersTest
✗ response has x frame options header
  Expected header [X-Frame-Options] to be [SAMEORIGIN] but it was missing.
```

This would mean the `SecureHeaders` middleware is not being applied — a security regression!

---

## Adding New Tests

To add a new security test:

1. Create a new test file in `tests/Feature/Security/`:

```php
<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyNewSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_security_check(): void
    {
        $response = $this->getJson('/api/v1/some-endpoint');
        $response->assertStatus(200);
    }
}
```

2. Run it:
```bash
php artisan test --filter MyNewSecurityTest
```

---

## OWASP Reference

These tests map to the [OWASP Top 10 (2021)](https://owasp.org/www-project-top-ten/) categories:

| OWASP Category | Test File |
|----------------|-----------|
| **A01** Broken Access Control | `AuthorizationTest.php`, `IdorTest.php` |
| **A02** Cryptographic Failures | Verified via encrypted model fields (Crypt) |
| **A03** Injection (XSS, SQLi) | `InputValidationXssTest.php`, `SqlInjectionTest.php` |
| **A04** Insecure Design | `FileUploadSecurityTest.php` (UUID filenames) |
| **A05** Security Misconfiguration | `SecurityHeadersTest.php` |
| **A07** Identification & Auth Failures | `AuthenticationTest.php`, `RateLimitingTest.php` |

---

## Summary

| Metric | Value |
|--------|-------|
| Total test files | 8 |
| Total test methods | ~40 |
| Test database | In-memory SQLite (no real data affected) |
| External dependencies | None |
| Run time | ~2-5 seconds |
| Runner command | `php artisan test --filter Security` |
