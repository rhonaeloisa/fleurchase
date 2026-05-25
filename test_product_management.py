from playwright.sync_api import sync_playwright
from pathlib import Path
import os
import time

SCREENSHOT_DIR = "playwright_screenshots"
os.makedirs(SCREENSHOT_DIR, exist_ok=True)

# DUMMY IMAGE
IMAGE_PATH = Path("test_flower.png").resolve()

if not IMAGE_PATH.exists():
    from PIL import Image
    Image.new("RGB", (300, 300), "pink").save(IMAGE_PATH)

with sync_playwright() as p:

    browser = p.chromium.launch(
        headless=False,
        slow_mo=700
    )

    page = browser.new_page()

    # ====================================
    # LOGIN
    # ====================================
    page.goto("http://localhost/fleurchase/login.html")

    page.wait_for_timeout(3000)

    page.locator("input").nth(0).fill(
        "kathleen.borromeo@gmail.com"
    )

    page.locator("input").nth(1).fill(
        "admin123"
    )

    page.locator(
        "#panel-login button.btn-green"
    ).click()

    page.wait_for_timeout(5000)

    page.screenshot(
        path=f"{SCREENSHOT_DIR}/01_after_login.png"
    )

    print("LOGIN SUCCESS")

    # ====================================
    # OPEN ADD BOUQUET PAGE
    # ====================================
    page.goto(
        "http://localhost/fleurchase/add_bouquet.php"
    )

    page.wait_for_timeout(5000)

    page.screenshot(
        path=f"{SCREENSHOT_DIR}/02_add_bouquet_page.png"
    )

    print("ADD BOUQUET PAGE OPENED")

    # ====================================
    # FILL BOUQUET FORM
    # ====================================
    page.select_option(
        "#f-cat",
        "ready-made"
    )

    page.select_option(
        "#f-variation",
        "small"
    )

    page.fill(
        "#f-name",
        "Playwright Bouquet"
    )

    page.fill(
        "#f-desc",
        "Automated CRUD testing bouquet"
    )

    page.fill(
        "#f-stock",
        "1"
    )

    page.select_option(
        "#f-status",
        "Active"
    )

    page.select_option(
        "#f-btype",
        "bouquet"
    )

    page.select_option(
        "#f-iscustom",
        "0"
    )

    page.fill(
        "#f-arrived",
        "2026-05-20"
    )

    page.fill(
        "#f-bestbefore",
        "2026-05-30"
    )

    # ====================================
    # UPLOAD IMAGE
    # ====================================
    page.set_input_files(
        "#f-image",
        str(IMAGE_PATH)
    )

    # ====================================
    # SELECT FLOWER
    # ====================================
    flower_buttons = page.locator(
        "#flower-picker .pk-btn:not(.rem)"
    )

    page.wait_for_timeout(2000)

    print(
        "Flower buttons found:",
        flower_buttons.count()
    )

    if flower_buttons.count() >= 1:

        flower_buttons.first.click()

        print("Flower selected successfully.")

    else:

        page.screenshot(
            path=f"{SCREENSHOT_DIR}/no_flower_buttons_found.png"
        )

        print("No active flower buttons found.")

        browser.close()

        exit()

    page.screenshot(
        path=f"{SCREENSHOT_DIR}/03_flower_selected.png"
    )

    # ====================================
    # SAVE BOUQUET
    # ====================================
    page.locator(
        'button:has-text("Save Bouquet")'
    ).click()

    page.wait_for_timeout(7000)

    page.screenshot(
        path=f"{SCREENSHOT_DIR}/04_bouquet_added.png"
    )

    print("ADD BOUQUET SUCCESS")

    # ====================================
    # OPEN PRODUCTS ADMIN
    # ====================================
    page.goto(
        "http://localhost/fleurchase/products-admin.php"
    )

    page.wait_for_timeout(5000)

    page.screenshot(
        path=f"{SCREENSHOT_DIR}/05_products_page.png"
    )

    # ====================================
    # FIND BOUQUET CARD
    # ====================================
    bouquet_card = page.locator(
        ".bc-card"
    ).filter(
        has_text="Playwright Bouquet"
    ).first

    page.wait_for_timeout(3000)

    page.screenshot(
        path=f"{SCREENSHOT_DIR}/06_bouquet_visible.png"
    )

    print("BOUQUET FOUND")

    # ====================================
    # OPEN EDIT DETAILS
    # ====================================
    bouquet_card.locator(
        ".bc-act.edit"
    ).click()

    page.wait_for_timeout(4000)

    page.screenshot(
        path=f"{SCREENSHOT_DIR}/07_edit_details_opened.png"
    )

    print("EDIT DETAILS OPENED")

    # ====================================
    # BACK TO PRODUCTS PAGE
    # ====================================
    page.goto(
        "http://localhost/fleurchase/products-admin.php"
    )

    page.wait_for_timeout(5000)

    bouquet_card = page.locator(
        ".bc-card"
    ).filter(
        has_text="Playwright Bouquet"
    ).first

    # ====================================
    # DELETE BOUQUET
    # ====================================
    bouquet_card.locator(
        ".bc-act.del"
    ).click()

    page.wait_for_timeout(3000)

    page.screenshot(
        path=f"{SCREENSHOT_DIR}/08_delete_confirmation.png"
    )

    page.locator(
        "#del-modal button.btn-danger"
    ).click()

    page.wait_for_timeout(6000)

    page.screenshot(
        path=f"{SCREENSHOT_DIR}/09_bouquet_deleted.png"
    )

    print("DELETE BOUQUET SUCCESS")

    time.sleep(5)

    browser.close()