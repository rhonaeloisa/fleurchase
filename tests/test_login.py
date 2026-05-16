from playwright.sync_api import sync_playwright, expect
import os
import re

BASE_URL = "http://localhost/fleurchase/login.html"

VALID_ADMIN_EMAIL = "hershey.hestiada@gmail.com"
VALID_ADMIN_PASSWORD = "admin123"

INVALID_EMAIL = "wrong@email.com"
INVALID_PASSWORD = "wrongpass"

VALID_CUSTOMER_EMAIL = "ayis.lobarbio@gmail.com"
VALID_CUSTOMER_PASSWORD = "@ayis123"

SCREENSHOT_DIR = "screenshots"


def has_php_error(text):
    php_error_patterns = [
        "php warning",
        "php fatal error",
        "fatal error:",
        "parse error:",
        "notice:",
        "mysqli_sql_exception",
        "uncaught mysqli_sql_exception",
        "undefined variable",
        "undefined array key",
        "undefined index",
        "stack trace:"
    ]

    lower_text = text.lower()
    return any(pattern in lower_text for pattern in php_error_patterns)


def setup_folder():
    os.makedirs(SCREENSHOT_DIR, exist_ok=True)


def login(page, email, password):
    page.goto(BASE_URL)
    page.wait_for_timeout(1000)
    page.fill("#l-email", email)
    page.wait_for_timeout(1000)
    page.fill("#l-pass", password)
    page.wait_for_timeout(1000)
    page.locator("#panel-login .btn-green").click()
    page.wait_for_timeout(1000)


def test_successful_admin_login():
    setup_folder()

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False, slow_mo=700)
        page = browser.new_page()

        login(page, VALID_ADMIN_EMAIL, VALID_ADMIN_PASSWORD)

        page.wait_for_url("**/admin.html", timeout=5000)

        page.screenshot(
            path=f"{SCREENSHOT_DIR}/passed_valid_login.png", full_page=True)

        assert "admin.html" in page.url.lower()
        assert not has_php_error(page.content())

        browser.close()


def test_failed_login_invalid_credentials():
    setup_folder()

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False, slow_mo=700)
        page = browser.new_page()

        login(page, INVALID_EMAIL, INVALID_PASSWORD)

        page.wait_for_timeout(1000)

        page.screenshot(
            path=f"{SCREENSHOT_DIR}/passed_invalid_login.png", full_page=True)

        assert "login.html" in page.url.lower()

        body_text = page.locator("body").inner_text().lower()
        assert "invalid" in body_text or "failed" in body_text or "incorrect" in body_text

        assert not has_php_error(page.content())

        browser.close()


def test_successful_customer_login():
    setup_folder()

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False, slow_mo=700)
        page = browser.new_page()

        login(page, VALID_CUSTOMER_EMAIL, VALID_CUSTOMER_PASSWORD)

        page.wait_for_url("**/shop.html", timeout=5000)

        page.screenshot(
            path=f"{SCREENSHOT_DIR}/passed_valid_login.png", full_page=True)

        assert "shop.html" in page.url.lower()
        assert not has_php_error(page.content())

        browser.close()


def test_no_php_errors_on_login_page():
    setup_folder()

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False, slow_mo=700)
        page = browser.new_page()

        page.goto(BASE_URL)
        page.wait_for_timeout(1000)

        page.screenshot(
            path=f"{SCREENSHOT_DIR}/passed_no_php_errors.png", full_page=True)

        html = page.content()
        assert not has_php_error(
            html), "PHP warning/fatal error detected on login page"

        browser.close()
