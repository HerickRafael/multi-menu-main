const { test, expect } = require('@playwright/test');
const {
  createTelemetryCollector,
  writeTelemetryArtifact,
  hasCriticalFrontendIssues,
  findPotentialDuplicateRequests,
} = require('./utils/telemetry');
const {
  gotoHome,
  gotoCart,
  gotoCheckout,
  clickIfVisible,
  fillInputIfPresent,
  hasCartItems,
  ensureLoggedInIfRequired,
} = require('./utils/helpers');

test.describe('Regression - Product, Cart and Checkout', () => {
  async function clickAddToCartWithRetry(page, options = {}) {
    const attempts = options.attempts || 3;
    let addPostCount = 0;

    const onRequest = (request) => {
      if (request.method() === 'POST' && /\/cart\/add(\?|$)/.test(request.url())) {
        addPostCount += 1;
      }
    };

    page.on('request', onRequest);

    try {
      for (let i = 0; i < attempts; i += 1) {
        await ensureLoggedInIfRequired(page);

        const clicked = await clickIfVisible(page.locator('button:has-text("Adicionar à Sacola"), button:has-text("Adicionar")'));
        if (!clicked) {
          continue;
        }

        await page.waitForTimeout(1200);

        if (addPostCount > 0 || /\/cart(\?|$)/.test(page.url())) {
          return { success: true, addPostCount };
        }
      }

      return { success: false, addPostCount };
    } finally {
      page.off('request', onRequest);
    }
  }

  test('simple product add to cart and persistence after refresh', async ({ page }, testInfo) => {
    const telemetry = createTelemetryCollector(page);
    let addedSuccessfully = false;

    await gotoHome(page);

    const productLinks = page.locator('a[href*="/produto/"]');
    const totalProducts = await productLinks.count();
    const attemptsOnProducts = Math.min(totalProducts, 5);

    for (let idx = 0; idx < attemptsOnProducts; idx += 1) {
      await gotoHome(page);

      const candidate = page.locator('a[href*="/produto/"]').nth(idx);
      await expect(candidate).toBeVisible();
      await candidate.click();
      await expect(page).toHaveURL(/\/produto\//);

      await clickIfVisible(page.locator('.qty .plus, [data-act="inc"], .st-btn[data-act="inc"]'));

      const addResult = await clickAddToCartWithRetry(page, { attempts: 3 });
      if (addResult.success) {
        addedSuccessfully = true;
        break;
      }
    }

    expect(addedSuccessfully).toBeTruthy();

    const reachedCart = await gotoCart(page);
    test.skip(!reachedCart, 'Nao foi possivel acessar /cart neste ambiente (login/redirect).');
    expect(await hasCartItems(page)).toBeTruthy();

    await page.reload({ waitUntil: 'domcontentloaded' });
    expect(await hasCartItems(page)).toBeTruthy();

    await writeTelemetryArtifact(testInfo, telemetry);
    expect(hasCriticalFrontendIssues(telemetry)).toBeFalsy();
  });

  test('cross-sell selected + customization must keep cross_sell payload', async ({ page }, testInfo) => {
    const telemetry = createTelemetryCollector(page);
    let seenCrossSellPayload = false;

    page.on('request', (request) => {
      if (request.method() === 'POST' && /\/cart\/add/.test(request.url())) {
        const payload = request.postData() || '';
        if (payload.includes('cross_sell')) {
          seenCrossSellPayload = true;
        }
      }
    });

    await gotoHome(page);

    const productWithCrossSell = page.locator('a[href*="/produto/"]').first();
    await productWithCrossSell.click();
    await expect(page).toHaveURL(/\/produto\//);

    const crossSellItem = page.locator('.cross-sell-item').first();
    test.skip(!(await crossSellItem.count()), 'Sem cross-sell disponivel para validar neste ambiente.');

    await crossSellItem.click();

    const customizeCrossSellLink = page.locator('[data-cross-sell-id], a[href*="customizar"]').first();
    if (await customizeCrossSellLink.count() && await customizeCrossSellLink.isVisible()) {
      await customizeCrossSellLink.click();
      await expect(page).toHaveURL(/customizar/);

      await clickIfVisible(page.locator('.st-btn[data-act="inc"], .qty .plus').first());

      const customForm = page.locator('#customForm, form[action*="customizar"]').first();
      await expect(customForm).toBeVisible();
      await customForm.locator('button[type="submit"], .cta[type="submit"]').first().click();
      await page.waitForLoadState('domcontentloaded');
    }

    const addResult = await clickAddToCartWithRetry(page, { attempts: 3 });

    const reachedCart = await gotoCart(page);
    test.skip(!reachedCart, 'Nao foi possivel acessar /cart neste ambiente (login/redirect).');
    expect(await hasCartItems(page)).toBeTruthy();
    expect(addResult.success).toBeTruthy();
    expect(seenCrossSellPayload).toBeTruthy();

    await writeTelemetryArtifact(testInfo, telemetry);
    expect(hasCriticalFrontendIssues(telemetry)).toBeFalsy();
  });

  test('checkout dynamic behavior and anti-double-click guard', async ({ page }, testInfo) => {
    const telemetry = createTelemetryCollector(page);
    let checkoutPostCount = 0;

    page.on('request', (request) => {
      if (request.method() === 'POST' && /\/checkout(\?|$)/.test(request.url())) {
        checkoutPostCount += 1;
      }
    });

    const reachedCart = await gotoCart(page);
    test.skip(!reachedCart, 'Nao foi possivel acessar /cart neste ambiente (login/redirect).');

    const hasItems = await hasCartItems(page);
    test.skip(!hasItems, 'Carrinho vazio no ambiente de execucao.');

    const reachedCheckout = await gotoCheckout(page);
    test.skip(!reachedCheckout, 'Nao foi possivel acessar /checkout neste ambiente (login/redirect).');

    await fillInputIfPresent(page, '#checkout-phone, input[name="address[phone]"]', '51994035717');
    await fillInputIfPresent(page, 'input[name="address[name]"]', 'Teste Regressao');
    await fillInputIfPresent(page, 'input[name="address[street]"]', 'Rua Teste');
    await fillInputIfPresent(page, 'input[name="address[number]"]', '123');

    const citySelect = page.locator('#checkout-city, select[name="address[city_id]"]').first();
    if (await citySelect.count()) {
      const options = citySelect.locator('option');
      const optionCount = await options.count();
      if (optionCount > 1) {
        const optionValue = await options.nth(1).getAttribute('value');
        if (optionValue) {
          await citySelect.selectOption(optionValue);
          await page.waitForTimeout(600);
        }
      }
    }

    const zoneSelect = page.locator('#checkout-zone, select[name="address[zone_id]"]').first();
    if (await zoneSelect.count()) {
      const options = zoneSelect.locator('option');
      const optionCount = await options.count();
      if (optionCount > 1) {
        const optionValue = await options.nth(1).getAttribute('value');
        if (optionValue) {
          await zoneSelect.selectOption(optionValue);
          await page.waitForTimeout(600);
        }
      }
    }

    await clickIfVisible(page.locator('.pay-method, .brand-btn[data-brand-select="1"]').first());

    const submit = page.locator('button.cta[type="submit"], button:has-text("Confirmar pedido")').first();
    await expect(submit).toBeVisible();

    if (process.env.MM_E2E_PLACE_ORDER === 'true') {
      await submit.dblclick();
      await page.waitForTimeout(2500);
      expect(checkoutPostCount).toBeLessThanOrEqual(1);
    } else {
      testInfo.annotations.push({
        type: 'note',
        description: 'MM_E2E_PLACE_ORDER != true: envio final bloqueado para evitar pedido real.',
      });
    }

    const duplicateCandidates = findPotentialDuplicateRequests(telemetry, 4);
    await writeTelemetryArtifact(testInfo, {
      ...telemetry,
      duplicateCandidates,
      checkoutPostCount,
    });

    expect(hasCriticalFrontendIssues(telemetry)).toBeFalsy();
  });
});
