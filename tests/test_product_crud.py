import pytest
from playwright.sync_api import sync_playwright, expect
import os
import time

BASE_URL = "http://localhost/fleurchase/login.html"
INVENTORY_URL = "http://localhost/fleurchase/inventory-admin.php"

VALID_ADMIN_EMAIL = "hershey.hestiada@gmail.com"
VALID_ADMIN_PASSWORD = "admin123"

SCREENSHOT_DIR = os.path.join(os.path.dirname(os.path.dirname(__file__)), "screenshots")
TEST_IMAGE = os.path.join(os.path.dirname(__file__), "test_flower.png")


def setup_folder():
    os.makedirs(SCREENSHOT_DIR, exist_ok=True)

    if not os.path.exists(TEST_IMAGE):
        png_bytes = bytes.fromhex(
            "89504E470D0A1A0A0000000D4948445200000001000000010802000000907753DE"
            "0000000C4944415408D763F8FFFF3F0005FE02FEA73581E80000000049454E44AE426082"
        )
        with open(TEST_IMAGE, "wb") as f:
            f.write(png_bytes)

import re

def login_as_admin(page):
    page.goto(BASE_URL, wait_until="domcontentloaded")
    page.fill("#l-email", VALID_ADMIN_EMAIL)
    page.fill("#l-pass", VALID_ADMIN_PASSWORD)
    page.locator("#panel-login .btn-green").click()

    expect(page).to_have_url(re.compile(r".*/admin\.html$"), timeout=15000)


def open_inventory(page):
    page.goto(INVENTORY_URL)
    page.wait_for_selector("#inv-table", timeout=10000)


def save_ss(page, name):
    page.screenshot(path=os.path.join(SCREENSHOT_DIR, name), full_page=True)


def add_product(page, product_name):
    open_inventory(page)

    page.get_by_role("button", name="+ Add Product").click()
    page.wait_for_selector("#add-product-form", timeout=5000)

    page.fill("input[name='product_name']", product_name)
    page.select_option("select[name='product_type']", "flower")
    page.set_input_files("input[name='product_image']", TEST_IMAGE)
    page.fill("input[name='stock']", "25")
    page.fill("input[name='price']", "50")
    page.fill("input[name='date_arrived']", "2026-05-20")
    page.fill("input[name='best_before_date']", "2026-05-27")
    page.select_option("select[name='status']", "Active")

    page.locator("#add-product-form button[type='submit']").click()
    page.wait_for_load_state("networkidle")

    open_inventory(page)
    expect(page.locator("body")).to_contain_text(product_name)


def test_create_product_pass():
    setup_folder()
    product_name = f"Create Flower {int(time.time())}"

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False, slow_mo=500)
        page = browser.new_page()

        login_as_admin(page)
        add_product(page, product_name)

        save_ss(page, "inventory_create_pass.png")
        browser.close()


def test_create_product_fail():
    setup_folder()
    product_name = ""

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False, slow_mo=500)
        page = browser.new_page()

        try:
            login_as_admin(page)
            open_inventory(page)

            page.get_by_role("button", name="+ Add Product").click()
            page.wait_for_selector("#add-product-form", timeout=5000)

            page.fill("input[name='product_name']", product_name)
            page.select_option("select[name='product_type']", "flower")
            page.set_input_files("input[name='product_image']", TEST_IMAGE)
            page.fill("input[name='stock']", "25")
            page.fill("input[name='price']", "50")
            page.fill("input[name='date_arrived']", "2026-05-20")
            page.fill("input[name='best_before_date']", "2026-05-27")

            page.locator("#add-product-form button[type='submit']").click()
            page.wait_for_timeout(1000)

            expect(page.locator("body")).to_contain_text("This should not pass")

        except Exception:
            save_ss(page, "inventory_create_fail.png")
            raise

        finally:
            browser.close()


def test_update_product_pass():
    setup_folder()
    product_name = f"Update Flower {int(time.time())}"
    updated_name = f"Updated Flower {int(time.time())}"

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False, slow_mo=500)
        page = browser.new_page()

        login_as_admin(page)
        add_product(page, product_name)

        row = page.locator("#inv-table tbody tr", has_text=product_name).first
        row.locator("button", has_text="Edit").click()

        page.wait_for_selector("#edit-product-form", timeout=10000)

        page.fill("#edit-product-form input[name='product_name']", updated_name)
        page.fill("#edit-product-form input[name='stock']", "40")
        page.fill("#edit-product-form input[name='price']", "75")

        page.locator("#edit-product-form button[type='submit']").click()
        page.wait_for_load_state("networkidle")

        open_inventory(page)
        expect(page.locator("body")).to_contain_text(updated_name)

        save_ss(page, "inventory_update_pass.png")
        browser.close()


def test_update_product_fail():
    setup_folder()
    product_name = f"Update Fail Flower {int(time.time())}"

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False, slow_mo=500)
        page = browser.new_page()

        try:
            login_as_admin(page)
            add_product(page, product_name)

            row = page.locator("#inv-table tbody tr", has_text=product_name).first
            row.locator("button", has_text="Edit").click()

            page.wait_for_selector("#edit-product-form", timeout=10000)

            page.fill("#edit-product-form input[name='product_name']", "")
            page.locator("#edit-product-form button[type='submit']").click()
            page.wait_for_timeout(1000)

            expect(page.locator("body")).to_contain_text("This should not pass")

        except Exception:
            save_ss(page, "inventory_update_fail.png")
            raise

        finally:
            browser.close()



def test_delete_product_pass():
    setup_folder()
    product_name = f"Delete Flower {int(time.time())}"

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False, slow_mo=500)
        page = browser.new_page()

        login_as_admin(page)
        add_product(page, product_name)

        row = page.locator("#inv-table tbody tr", has_text=product_name).first
        row.locator("button", has_text="Delete").click()

        page.wait_for_selector("#delete-modal", state="visible", timeout=10000)
        page.locator("#delete-modal .btn-danger").click()
        page.wait_for_load_state("networkidle")

        open_inventory(page)
        expect(page.locator("body")).not_to_contain_text(product_name)

        save_ss(page, "inventory_delete_pass.png")
        browser.close()


def test_delete_product_fail():
    setup_folder()
    product_name = f"Delete Fail Flower {int(time.time())}"

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False, slow_mo=500)
        page = browser.new_page()

        try:
            login_as_admin(page)
            add_product(page, product_name)

            row = page.locator("#inv-table tbody tr", has_text=product_name).first
            row.locator("button", has_text="Delete").click()

            page.wait_for_selector("#delete-modal", state="visible", timeout=10000)
            page.locator("#delete-modal .btn-ghost").click()
            page.wait_for_timeout(1000)

            expect(page.locator("body")).not_to_contain_text(product_name)

        except Exception:
            save_ss(page, "inventory_delete_fail.png")
            raise

        finally:
            browser.close()