import { expect, test } from '@playwright/test'

const PORTAL = 'http://localhost:3000'

// The portal sends a strict content security policy: any script or style it blocks fails the test.
test.beforeEach(({ page }, testInfo) => {
  page.on('console', (message) => {
    if (/Content Security Policy/i.test(message.text())) {
      testInfo.annotations.push({ type: 'csp-violation', description: message.text() })
    }
  })
})

test.afterEach(({}, testInfo) => {
  expect(testInfo.annotations.filter((annotation) => annotation.type === 'csp-violation')).toEqual(
    [],
  )
})

async function signIn(page: import('@playwright/test').Page, account: string) {
  await page.goto(`${PORTAL}/login`)
  await page.getByRole('button', { name: account, exact: true }).click()
  await expect(page.getByRole('heading', { name: 'Mes contrats' })).toBeVisible()
}

test('the public pages are made to be found', async ({ page, request }) => {
  await page.goto(PORTAL)

  await expect(page).toHaveTitle(/BrokerDesk/)
  await expect(page.locator('html')).toHaveAttribute('lang', 'fr')
  await expect(page.locator('meta[name="description"]')).toHaveAttribute('content', /contrats/)
  await expect(page.locator('meta[property="og:title"]')).toHaveCount(1)
  await expect(page.getByRole('heading', { level: 1 })).toBeVisible()

  // Server-rendered: the content is in the HTML, before any JavaScript runs.
  const html = await (await request.get(PORTAL)).text()
  expect(html).toContain('Vos assurances, au même endroit')

  const headers = (await request.get(PORTAL)).headers()
  expect(headers['x-frame-options']).toBe('DENY')
  expect(headers['x-content-type-options']).toBe('nosniff')
  expect(headers['x-powered-by']).toBeUndefined()
  expect(headers['content-security-policy']).toMatch(/script-src 'self' 'nonce-/)

  const robots = await (await request.get(`${PORTAL}/robots.txt`)).text()
  expect(robots).toContain('Disallow: /contracts')

  const sitemap = await (await request.get(`${PORTAL}/sitemap.xml`)).text()
  expect(sitemap).toContain('<loc>')
  expect(sitemap).not.toContain('/contracts')
})

test('private pages need a session', async ({ page, request }) => {
  for (const path of ['/contracts', '/claims', '/contracts/1', '/documents/1']) {
    await page.goto(`${PORTAL}${path}`)
    await expect(page).toHaveURL(/\/login/)
  }

  const response = await request.get(`${PORTAL}/contracts`, { maxRedirects: 0 })
  expect(response.status()).toBe(307)
})

test('wrong credentials are explained, and brokers go to their own application', async ({
  page,
}) => {
  await page.goto(`${PORTAL}/login`)

  await page.getByLabel('Adresse e-mail').fill('nobody@example.be')
  await page.getByLabel('Mot de passe').fill('wrong')
  await page.getByRole('button', { name: 'Se connecter' }).click()
  await expect(page.locator('#email-error')).toBeVisible()

  await page.getByLabel('Adresse e-mail').fill('broker@example.be')
  await page.getByLabel('Mot de passe').fill('password')
  await page.getByRole('button', { name: 'Se connecter' }).click()
  await expect(page.getByText("connectez-vous à l'espace courtier")).toBeVisible()
})

test('a client reads contracts, downloads a document and declares a claim', async ({
  page,
  context,
}) => {
  await signIn(page, 'Client particulier')

  await test.step('the token stays out of reach of scripts, and pages stay private', async () => {
    const cookie = (await context.cookies()).find((item) => item.name === 'bd_session')

    expect(cookie?.httpOnly).toBe(true)
    expect(cookie?.sameSite).toBe('Lax')
    expect(await page.evaluate(() => document.cookie)).not.toContain('bd_session')
    await expect(page.locator('meta[name="robots"]')).toHaveAttribute('content', /noindex/)
  })

  await test.step('see only the three contracts of this client', async () => {
    await expect(page.getByText('3 contrats')).toBeVisible()
    await expect(page.getByRole('heading', { name: 'Auto' })).toBeVisible()
  })

  await test.step('see and download shared documents, but not internal notes', async () => {
    await page.getByRole('link', { name: /Auto/ }).click()
    await expect(page.getByRole('heading', { name: 'Documents' })).toBeVisible()
    await expect(page.getByText('note-interne.pdf')).toHaveCount(0)

    const download = page.waitForEvent('download')
    await page.getByRole('link', { name: 'conditions-particulieres.pdf' }).click()
    expect((await download).suggestedFilename()).toBe('conditions-particulieres.pdf')
  })

  await test.step('get validation errors, then declare a claim', async () => {
    await page.getByRole('link', { name: 'Déclarer un sinistre' }).click()
    await page.getByRole('button', { name: 'Envoyer ma déclaration' }).click()
    await expect(page.locator('#description-error')).toBeVisible()
    await expect(page.locator('#occurred_on-error')).toBeVisible()

    await page.getByLabel('Date du sinistre').fill('2026-10-01')
    await page.getByLabel("Que s'est-il passé ?").fill('Rétroviseur arraché sur un parking.')
    await page.getByRole('button', { name: 'Envoyer ma déclaration' }).click()

    await expect(page.getByText(/bien été déclaré sous la référence SIN-/)).toBeVisible()
    await expect(page.getByText('Rétroviseur arraché sur un parking.').first()).toBeVisible()
  })

  await test.step('cannot reach the contract of someone else', async () => {
    const response = await page.goto(`${PORTAL}/contracts/999999`)

    expect(response?.status()).toBe(404)
    await expect(page.getByRole('heading', { name: 'Page introuvable' })).toBeVisible()

    expect((await page.goto(`${PORTAL}/contracts/1%2F..%2F..%2Fusers`))?.status()).toBe(404)
  })

  await test.step('signing out really ends the session', async () => {
    await page.goto(`${PORTAL}/contracts`)
    await page.getByRole('button', { name: 'Se déconnecter' }).click()
    await expect(page.getByRole('heading', { level: 1 })).toContainText('Vos assurances')

    await page.goto(`${PORTAL}/contracts`)
    await expect(page).toHaveURL(/\/login/)
  })
})

test('a company client has the same portal, with its own contracts', async ({ page }) => {
  await signIn(page, 'Client société')

  await expect(page.getByText('3 contrats')).toBeVisible()
  await expect(page.getByRole('heading', { name: 'Entreprise' })).toBeVisible()
  await page.getByRole('link', { name: 'Sinistres' }).click()
  await expect(page.getByRole('heading', { name: 'Mes sinistres' })).toBeVisible()
  await expect(page.getByText('En cours').first()).toBeVisible()
})

test('an invalid session cookie leads back to the login page', async ({ page, context }) => {
  await context.addCookies([{ name: 'bd_session', value: 'forged', url: PORTAL }])

  await page.goto(`${PORTAL}/contracts`)

  await expect(page).toHaveURL(/\/login\?expired=1/)
  await expect(page.getByText('Votre session a expiré')).toBeVisible()
})

test('takes the portal screenshots', async ({ page }) => {
  test.skip(!process.env.SCREENSHOTS, 'Only on demand: SCREENSHOTS=1')

  const shot = async (name: string) => {
    await page.mouse.move(0, 0)
    await page.screenshot({ path: `../docs/screenshots/${name}.png` })
  }

  await page.goto(PORTAL)
  await shot('portal-home')

  await page.goto(`${PORTAL}/login`)
  await expect(page.getByText("Vous évaluez l'application ?")).toBeVisible()
  await shot('portal-login')

  await page.getByRole('button', { name: 'Client société', exact: true }).click()
  await expect(page.getByRole('heading', { name: 'Mes contrats' })).toBeVisible()
  await shot('portal-contracts')

  await page.getByRole('link', { name: /Entreprise/ }).click()
  await expect(page.getByRole('heading', { name: 'Documents' })).toBeVisible()
  await shot('portal-contract')

  await page.goto(`${PORTAL}/claims`)
  await expect(page.getByRole('heading', { name: 'Mes sinistres' })).toBeVisible()
  await shot('portal-claims')
})

test('evaluators see how the application is built, from the login page and from their account', async ({
  page,
}) => {
  await page.goto(`${PORTAL}/login`)
  await expect(page.getByRole('region', { name: "Comment c'est construit" })).toContainText(
    'Next.js 16',
  )

  await signIn(page, 'Client particulier')
  await page.getByRole('contentinfo').getByRole('link', { name: "Comment c'est construit" }).click()
  await expect(
    page.getByRole('heading', { level: 1, name: "Comment c'est construit" }),
  ).toBeVisible()
  await expect(page.getByRole('link', { name: 'Code source sur GitHub' })).toHaveAttribute(
    'href',
    'https://github.com/DonkeyhTv/brokerdesk',
  )
})

test('the portal speaks Dutch, from the login page to the API validation messages', async ({
  page,
}) => {
  await page.goto(`${PORTAL}/login`)
  await page.getByRole('group', { name: 'Langue' }).getByRole('button', { name: 'nl' }).click()
  await expect(page.getByRole('heading', { name: 'Klantenomgeving' })).toBeVisible()
  await expect(page.locator('html')).toHaveAttribute('lang', 'nl')

  await page.getByRole('button', { name: 'Particuliere klant', exact: true }).click()
  await expect(page.getByRole('heading', { name: 'Mijn contracten' })).toBeVisible()
  await expect(page.getByText('Actief').first()).toBeVisible()

  await page.getByRole('link', { name: 'Schadegevallen' }).click()
  await page.getByRole('link', { name: 'Een schadegeval aangeven' }).click()
  await page.getByRole('button', { name: 'Mijn aangifte verzenden' }).click()
  await expect(page.getByText('Het veld beschrijving is verplicht.')).toBeVisible()

  // The choice is a cookie: it holds on the next visit, until changed back.
  await page.goto(`${PORTAL}/`)
  await expect(page.getByRole('heading', { name: 'Uw verzekeringen op één plek' })).toBeVisible()
  await page.getByRole('group', { name: 'Taal' }).getByRole('button', { name: 'fr' }).click()
  await expect(page.getByRole('heading', { name: 'Vos assurances, au même endroit' })).toBeVisible()
})
