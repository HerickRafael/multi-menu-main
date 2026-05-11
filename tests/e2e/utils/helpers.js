const { expect } = require('@playwright/test');

const configuredBaseUrl = process.env.MM_E2E_BASE_URL || 'https://wollburger.online/wollburger';
const parsedBaseUrl = new URL(configuredBaseUrl);
const storeBasePath = parsedBaseUrl.pathname.replace(/\/+$/, '');

function storeUrl(pathSuffix = '') {
  const suffix = String(pathSuffix || '').replace(/^\/+/, '');
  const joinedPath = suffix ? `${storeBasePath}/${suffix}` : storeBasePath;
  return `${parsedBaseUrl.origin}${joinedPath}`;
}

async function gotoHome(page) {
  await page.goto(storeUrl());
  await expect(page).toHaveURL(/wollburger/);
}

async function gotoRouteHandlingLogin(page, pathSuffix, expectedUrlRegex) {
  for (let attempt = 0; attempt < 2; attempt += 1) {
    try {
      await page.goto(storeUrl(pathSuffix), { waitUntil: 'domcontentloaded' });
    } catch (error) {
      const message = String(error && error.message ? error.message : '');
      const isInterrupt = /ERR_ABORTED|interrupted by another navigation/i.test(message);
      if (!isInterrupt || !expectedUrlRegex.test(page.url())) {
        throw error;
      }
    }

    if (expectedUrlRegex.test(page.url())) {
      return true;
    }

    if (/\blogin=1\b/.test(page.url())) {
      await ensureLoggedInIfRequired(page);
      continue;
    }

    break;
  }

  return expectedUrlRegex.test(page.url());
}

async function gotoCart(page) {
  return gotoRouteHandlingLogin(page, 'cart', /\/cart/);
}

async function gotoCheckout(page) {
  return gotoRouteHandlingLogin(page, 'checkout', /\/checkout/);
}

async function openFirstProductFromHome(page) {
  const productLink = page.locator('a[href*="/produto/"]').first();
  await expect(productLink).toBeVisible();
  await productLink.click();
  await expect(page).toHaveURL(/\/produto\//);
}

async function clickIfVisible(locator) {
  if (await locator.count()) {
    if (await locator.first().isVisible()) {
      await locator.first().click();
      return true;
    }
  }
  return false;
}

async function fillInputIfPresent(page, selector, value) {
  const node = page.locator(selector).first();
  if (await node.count()) {
    if (await node.isVisible()) {
      await node.fill(value);
      return true;
    }
  }
  return false;
}

async function hasCartItems(page) {
  const emptyState = page.locator('.empty-title, .empty').first();
  if (await emptyState.count() && await emptyState.isVisible()) {
    return false;
  }

  const addMoreButton = page.locator('.add-more-btn, a:has-text("Adicionar mais itens")').first();
  if (await addMoreButton.count() && await addMoreButton.isVisible()) {
    return true;
  }

  const checkoutLink = page.locator('a.cta[href*="checkout"]').first();
  if (await checkoutLink.count() && await checkoutLink.isVisible()) {
    return true;
  }

  return (await page.locator('.item, [data-cart-item], .cart-item, [id^="card-"]').count()) > 0;
}

async function ensureLoggedInIfRequired(page) {
  const loginConfig = await page.evaluate(() => {
    if (!window.__LOGIN_CONFIG) {
      return { requiresLogin: false, userLogged: false };
    }
    return {
      requiresLogin: Boolean(window.__LOGIN_CONFIG.requiresLogin),
      userLogged: Boolean(window.__LOGIN_CONFIG.userLogged),
    };
  });

  if (loginConfig.userLogged) {
    return false;
  }

  const modal = page.locator('#login-modal');
  if (!(await modal.count())) {
    return false;
  }

  await page.evaluate(() => {
    if (typeof window.openLoginModal === 'function') {
      window.openLoginModal();
    }
  });

  const enterButton = page.locator('button:has-text("Entrar")').first();
  if (await modal.first().isHidden() && await enterButton.count() && await enterButton.isVisible()) {
    await enterButton.click();
  }

  await expect(modal).toBeVisible();

  const uniqueSuffix = String(Date.now()).slice(-7);
  await fillInputIfPresent(page, '#login-whatsapp, input[name="whatsapp"]', `51999${uniqueSuffix}`);
  await fillInputIfPresent(page, '#login-name, input[name="name"]', `E2E ${uniqueSuffix}`);

  const lgpdCheckbox = page.locator('#lgpd-consent-checkbox, input[name="lgpd_consent"]').first();
  if (await lgpdCheckbox.count()) {
    await lgpdCheckbox.check();
  }

  const submitButton = page.locator('#login-submit-btn, #login-form button[type="submit"]').first();
  await expect(submitButton).toBeVisible();
  await submitButton.click();

  await page.waitForLoadState('domcontentloaded');
  await page.waitForTimeout(1000);

  return true;
}

function parseBRL(text) {
  if (!text) return 0;
  const normalized = text
    .replace(/[^0-9,.-]/g, '')
    .replace(/\./g, '')
    .replace(',', '.');
  const num = Number(normalized);
  return Number.isFinite(num) ? num : 0;
}

module.exports = {
  gotoHome,
  gotoCart,
  gotoCheckout,
  openFirstProductFromHome,
  clickIfVisible,
  fillInputIfPresent,
  hasCartItems,
  ensureLoggedInIfRequired,
  parseBRL,
};
