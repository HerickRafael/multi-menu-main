const { test, expect } = require('@playwright/test');
const {
  createTelemetryCollector,
  writeTelemetryArtifact,
  hasCriticalFrontendIssues,
} = require('./utils/telemetry');
const {
  gotoCheckout,
  gotoCart,
  fillInputIfPresent,
  hasCartItems,
} = require('./utils/helpers');

test.describe('Regression - Critical cases', () => {
  test('refresh during checkout should preserve key state', async ({ page }, testInfo) => {
    const telemetry = createTelemetryCollector(page);

    const reachedCart = await gotoCart(page);
    test.skip(!reachedCart, 'Nao foi possivel acessar /cart neste ambiente (login/redirect).');
    const hasItems = await hasCartItems(page);
    test.skip(!hasItems, 'Carrinho vazio no ambiente de execucao.');

    const reachedCheckout = await gotoCheckout(page);
    test.skip(!reachedCheckout, 'Nao foi possivel acessar /checkout neste ambiente (login/redirect).');

    await fillInputIfPresent(page, '#checkout-phone, input[name="address[phone]"]', '51994035717');
    await fillInputIfPresent(page, 'input[name="address[name]"]', 'Usuario Regressao');

    await page.reload({ waitUntil: 'domcontentloaded' });

    const phoneField = page.locator('#checkout-phone, input[name="address[phone]"]').first();
    if (await phoneField.count()) {
      const value = await phoneField.inputValue();
      expect(value.replace(/\D/g, '').length).toBeGreaterThanOrEqual(10);
    }

    await writeTelemetryArtifact(testInfo, telemetry);
    expect(hasCriticalFrontendIssues(telemetry)).toBeFalsy();
  });

  test('multi-tab cart state consistency baseline', async ({ context, page }, testInfo) => {
    const telemetry = createTelemetryCollector(page);

    const reachedCart = await gotoCart(page);
    test.skip(!reachedCart, 'Nao foi possivel acessar /cart neste ambiente (login/redirect).');

    const hasItems = await hasCartItems(page);
    test.skip(!hasItems, 'Carrinho vazio no ambiente de execucao.');

    const page2 = await context.newPage();
    const telemetry2 = createTelemetryCollector(page2);
    await page2.goto(page.url(), { waitUntil: 'domcontentloaded' });

    const plusButton = page.locator('form[action*="/cart/update"] button, .qty .plus, .st-btn[data-act="inc"]').first();
    if (await plusButton.count()) {
      await plusButton.click();
      await page.waitForTimeout(1200);
    }

    await page2.reload({ waitUntil: 'domcontentloaded' });
    expect(await hasCartItems(page2)).toBeTruthy();

    await writeTelemetryArtifact(testInfo, {
      tab1: telemetry,
      tab2: telemetry2,
    });

    expect(hasCriticalFrontendIssues(telemetry)).toBeFalsy();
    expect(hasCriticalFrontendIssues(telemetry2)).toBeFalsy();

    await page2.close();
  });

  test('slow network checkout path smoke', async ({ browserName, page }, testInfo) => {
    test.skip(browserName !== 'chromium', 'Throttling via CDP suportado apenas no Chromium neste teste.');

    const telemetry = createTelemetryCollector(page);

    const client = await page.context().newCDPSession(page);
    await client.send('Network.enable');
    await client.send('Network.emulateNetworkConditions', {
      offline: false,
      latency: 400,
      downloadThroughput: (1.5 * 1024 * 1024) / 8,
      uploadThroughput: (750 * 1024) / 8,
      connectionType: 'cellular3g',
    });

    const reachedCart = await gotoCart(page);
    test.skip(!reachedCart, 'Nao foi possivel acessar /cart neste ambiente (login/redirect).');
    const hasItems = await hasCartItems(page);
    test.skip(!hasItems, 'Carrinho vazio no ambiente de execucao.');

    const reachedCheckout = await gotoCheckout(page);
    test.skip(!reachedCheckout, 'Nao foi possivel acessar /checkout neste ambiente (login/redirect).');
    await expect(page.locator('form#checkout-form, #checkout-form')).toBeVisible();

    await client.send('Network.emulateNetworkConditions', {
      offline: false,
      latency: 0,
      downloadThroughput: -1,
      uploadThroughput: -1,
      connectionType: 'none',
    });

    await writeTelemetryArtifact(testInfo, telemetry);
    expect(hasCriticalFrontendIssues(telemetry)).toBeFalsy();
  });
});
