const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.route('**/api/v1/**', async (route) => {
    const req = route.request();
    const url = req.url();
    const method = req.method();

    if (url.endsWith('/api/v1/auth/login') && method === 'POST') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          ok: true,
          data: {
            tokens: {
              accessToken: 'e2e-access-token',
              refreshToken: 'e2e-refresh-token'
            }
          }
        })
      });
      return;
    }

    if (url.includes('/api/v1/cities/rankings') && method === 'GET') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          ok: true,
          data: [
            { id: 1, name: 'Istanbul', country_code: 'TR' },
            { id: 2, name: 'Ankara', country_code: 'TR' }
          ]
        })
      });
      return;
    }

    if (url.endsWith('/api/v1/travel/quote') && method === 'POST') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          ok: true,
          data: {
            travelType: 'local_travel',
            distanceKm: 450,
            durationSeconds: 18000,
            ticketCost: 120,
            permitStatus: 'approved'
          }
        })
      });
      return;
    }

    if (url.endsWith('/api/v1/travel/start') && method === 'POST') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          ok: true,
          data: { started: true }
        })
      });
      return;
    }

    if (url.endsWith('/api/v1/travel/active') && method === 'GET') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          ok: true,
          data: {
            travelStatus: 'in_progress',
            travelType: 'local_travel',
            remainingSeconds: 3600,
            departureCityId: 1,
            arrivalCityId: 2
          }
        })
      });
      return;
    }

    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ ok: true, data: {} })
    });
  });
});

test('root path redirects to map and renders title', async ({ page }) => {
  await page.goto('/');
  await expect(page).toHaveURL(/\/map$/);
  await expect(page.getByRole('heading', { name: 'World Map' })).toBeVisible();
});

test('login form submits and navigates to map', async ({ page }) => {
  await page.goto('/login');
  await page.getByPlaceholder('email or username').fill('demo-user');
  await page.getByPlaceholder('password').fill('Password123!');
  await page.getByRole('button', { name: 'Sign in' }).click();
  await expect(page).toHaveURL(/\/map$/);
  await expect(page.getByRole('heading', { name: 'World Map' })).toBeVisible();
});

test('travel quote and active-travel card are shown', async ({ page }) => {
  await page.goto('/travel');

  await page.selectOption('select:first-of-type', '1');
  await page.selectOption('select:nth-of-type(2)', '2');

  await page.getByRole('button', { name: 'Quote' }).click();
  await expect(page.getByText('Type: local_travel')).toBeVisible();

  await page.getByRole('button', { name: 'Start Travel' }).click();
  await expect(page.getByRole('heading', { name: 'Active Travel' })).toBeVisible();
  await expect(page.getByText('Remaining: 3600s')).toBeVisible();
});
