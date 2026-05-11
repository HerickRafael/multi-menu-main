const { test, expect } = require('@playwright/test');
const {
  createTelemetryCollector,
  writeTelemetryArtifact,
  hasCriticalFrontendIssues,
} = require('./utils/telemetry');
const { gotoHome, clickIfVisible } = require('./utils/helpers');

test.describe('Regression - Navigation', () => {
  test('home, categories, search, mobile menu and banner render without js/network errors', async ({ page }, testInfo) => {
    const telemetry = createTelemetryCollector(page);

    await gotoHome(page);

    await expect(page.locator('body')).toContainText('WollBurger');
    await expect(page.locator('a[href*="#cat-"]').first()).toBeVisible();

    const searchInput = page.locator('input[type="search"], input[placeholder*="Buscar"], input[name="q"]').first();
    if (await searchInput.count()) {
      await searchInput.fill('smash');
      await searchInput.press('Enter');
      await page.waitForLoadState('networkidle');
    }

    await clickIfVisible(page.locator('[data-mobile-menu], .menu-toggle, button[aria-label*="menu" i]'));

    await page.mouse.wheel(0, 1500);
    await page.waitForTimeout(500);

    const banner = page.locator('img[alt*="banner" i], .hero img, img[src*="banner"]').first();
    if (await banner.count()) {
      await expect(banner).toBeVisible();
    }

    await writeTelemetryArtifact(testInfo, telemetry);
    expect(hasCriticalFrontendIssues(telemetry)).toBeFalsy();
  });
});
