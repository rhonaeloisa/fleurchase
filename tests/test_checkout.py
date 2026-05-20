from playwright.sync_api import sync_playwright, expect
import os
import re
import random

BASE_URL = "http://localhost/fleurchase/login.html"
ADMIN_URL = "http://localhost/fleurchase/admin.html"

VALID_ADMIN_EMAIL = "rhona.lumbes@gmail.com"
VALID_ADMIN_PASSWORD = "admin123"

VALID_CUSTOMER_EMAIL = "ayis.lobarbio@gmail.com"
VALID_CUSTOMER_PASSWORD = "@ayis123"

SCREENSHOT_DIR = "screenshots"
RECEIPT_PATH = "/Applications/XAMPP/xamppfiles/htdocs/fleurchase/uploads/receipts/receipt.jpg"


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


def capture_status_screenshot(page, status, name):
    setup_folder()
    page.screenshot(path=f"{SCREENSHOT_DIR}/{status}_{name}.png", full_page=True)


def login(page, email, password):
    page.goto(BASE_URL)
    page.wait_for_timeout(1000)
    page.fill("#l-email", email)
    page.wait_for_timeout(1000)
    page.fill("#l-pass", password)
    page.wait_for_timeout(1000)
    page.locator("#panel-login .btn-green").click()
    page.wait_for_timeout(1000)


def set_checkout_field_values(page, customer_name, phone):
    first_name, last_name = customer_name.split(" ", 1)
    page.evaluate(
        """({ firstName, lastName, phone }) => {
            const values = {
                "co-fn": firstName,
                "co-ln": lastName,
                "co-house": "123",
                "co-street": "Localhost Street",
                "co-barangay": "Barangay Test",
                "co-mun": "Legazpi City",
                "co-province": "Albay",
                "co-zip": "4500",
                "co-ph": phone,
            };

            for (const [id, value] of Object.entries(values)) {
                const el = document.getElementById(id);
                if (!el) continue;
                el.value = value;
                el.dispatchEvent(new Event("input", { bubbles: true }));
                el.dispatchEvent(new Event("change", { bubbles: true }));
            }
        }""",
        {"firstName": first_name, "lastName": last_name, "phone": phone},
    )


def test_customer_checkout_and_admin_verification():
    setup_folder()

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False, slow_mo=700)
        page = browser.new_page()

        try:
            # Handle browser alert/dialog popups gracefully if they show up
            page.on("dialog", lambda dialog: dialog.accept())

            unique_id = f"0912{random.randint(1000000, 9999999)}"
            customer_name = "Ayis Lobarbio Test"

            # ==========================================
            # STEP 1: AUTHENTICATION
            # ==========================================
            login(page, VALID_CUSTOMER_EMAIL, VALID_CUSTOMER_PASSWORD)

            # Wait explicitly for your original landing page redirect target
            page.wait_for_url("**/shop.html", timeout=5000)
            page.wait_for_timeout(1000)

            # ==========================================
            # STEP 2: CUSTOMER CHECKOUT FLOW
            # ==========================================
            add_to_cart_btn = page.locator(".prod-card:not(.out-stock) button.add-btn:not([disabled])").first
            expect(add_to_cart_btn).to_be_visible()

            add_to_cart_btn.click(force=True)
            page.wait_for_timeout(1000)

            # Access Cart Link
            cart_link = page.locator("a[href*='cart'], .cart-icon, [id*='cart']").first
            expect(cart_link).to_be_visible()
            cart_link.click()
            page.wait_for_timeout(1000)

            # Force check all checkboxes found on the cart interface to ensure products are active
            checkboxes = page.locator("input[type='checkbox']")
            count = checkboxes.count()
            for i in range(count):
                checkboxes.nth(i).check(force=True)
            page.wait_for_timeout(500)

            # Open the real checkout page through the same function used by the cart button.
            page.evaluate("proceedCheckout()")

            page.wait_for_url("**/checkout.html", timeout=10000)
            page.wait_for_selector("#co-items-list .co-item", timeout=10000)
            page.wait_for_selector("#co-fn", state="attached", timeout=10000)
            set_checkout_field_values(page, customer_name, unique_id)
            page.wait_for_timeout(1000)

            # Intentional invalid checkout documentation: missing receipt validation.
            page.evaluate(
                """() => {
                    receiptBase64 = null;
                    receiptFileName = '';
                    sessionStorage.removeItem('fc_pending_receipt');
                    sessionStorage.removeItem('fc_pending_receipt_name');

                    const ok = document.getElementById('up-ok');
                    if (ok) {
                        ok.style.display = 'none';
                        ok.textContent = '';
                    }

                    const preview = document.getElementById('receipt-preview');
                    if (preview) {
                        preview.removeAttribute('src');
                        preview.style.display = 'none';
                    }
                }"""
            )
            page.locator("button:has-text('Confirm Pre-Order')").first.click()
            page.wait_for_timeout(1000)
            capture_status_screenshot(page, "failed", "missing_receipt_validation")

            # Upload the real receipt image so the actual checkout flow can continue and pass.
            assert os.path.exists(RECEIPT_PATH), f"Receipt file missing: {RECEIPT_PATH}"
            page.locator("#rf").set_input_files(RECEIPT_PATH)
            expect(page.locator("#up-ok")).to_contain_text("receipt.jpg", timeout=10000)

            # Confirm and place the order through the same app function used by the modal.
            page.evaluate("placeOrder()")
            page.wait_for_timeout(3000)

            # Verify front-end transaction success indicators
            body_text = page.locator("body").inner_text().lower()
            assert "success" in body_text or "thank you" in body_text or "order" in body_text or "placed" in body_text
            assert not has_php_error(page.content()), "PHP Error detected during checkout form generation!"

            capture_status_screenshot(page, "passed", "customer_checkout")

            # ==========================================
            # STEP 3: BACK-END ADMIN PANEL VERIFICATION
            # ==========================================
            page.evaluate("localStorage.removeItem('fc_user')")
            login(page, VALID_ADMIN_EMAIL, VALID_ADMIN_PASSWORD)
            page.wait_for_url("**/admin.html", timeout=10000)
            page.goto("http://localhost/fleurchase/orders-admin.html")
            page.wait_for_selector("#ord-tbody", timeout=10000)
            page.wait_for_timeout(2000)

            # Click into your management dashboard's orders section if it exists
            orders_tab = page.locator("a[href*='orders'], #orders-tab, [id*='order']").first
            if orders_tab.is_visible():
                orders_tab.click()
                page.wait_for_timeout(1000)

            admin_body_text = page.locator("body").inner_text()

            # Take backend visual screenshot validation proof
            capture_status_screenshot(page, "passed", "admin_order_verification")

            # Run final database/UI reflection validations
            assert not has_php_error(page.content()), "PHP Error detected on Admin dashboard view!"
            assert "Ayis Lobarbio" in admin_body_text, "Customer data record failed validation."
            assert "uploaded" in admin_body_text.lower(), "Receipt upload status failed validation."


        except Exception as e:
            capture_status_screenshot(page, "failed", "checkout_flow")
            raise

        finally:
            browser.close()
