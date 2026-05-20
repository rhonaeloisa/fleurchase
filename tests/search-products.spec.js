const { test, expect } = require('@playwright/test');

const BASE_URL = "http://localhost/fleurchase/login.html";

const VALID_ADMIN_EMAIL = "hershey.hestiada@gmail.com";
const VALID_ADMIN_PASSWORD = "admin123";

test('Admin login and search products', async ({ page }) => {
  // Step 1: Login
  await page.goto(BASE_URL);
  await page.fill('#l-email', VALID_ADMIN_EMAIL);
  await page.fill('#l-pass', VALID_ADMIN_PASSWORD);
  await page.click('button.btn-green');

  // Step 2: Wait for redirect to products-admin.php
  await expect(page).toHaveURL(/products-admin\.php/);

  // Step 3: Perform search
  await page.fill('#bc-search', 'Pink Roses Bouquet');

  // The admin page opens in card view, so switch to list view before
  // asserting table rows inside #bc-list.
  await page.click('#vbtn-table');
  await expect(page.locator('#bc-list')).toBeVisible();

  // Now assert visible matching rows.
  const rows = page.locator('#bc-list .bc-list-row:not(.hidden)');
  await expect.poll(async () => rows.count()).toBeGreaterThan(0);

  await expect(rows.first()).toContainText(/Pink Roses Bouquet/i);
});
