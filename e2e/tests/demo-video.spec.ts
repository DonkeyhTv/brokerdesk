import { expect, test } from '@playwright/test'

const PORTAL = 'http://localhost:3000'

test.use({
  viewport: { width: 1280, height: 720 },
  video: { mode: 'on', size: { width: 1280, height: 720 } },
})

test('records the README demo', async ({ page }) => {
  test.skip(!process.env.SCREENSHOTS, 'Only on demand: SCREENSHOTS=1')
  test.setTimeout(120_000)

  const pause = (milliseconds = 1800) => page.waitForTimeout(milliseconds)

  await page.goto('/login')
  await pause(1500)
  await page.getByRole('button', { name: 'Administrateur du cabinet', exact: true }).click()
  await expect(page.getByRole('heading', { name: 'Primes annuelles par branche' })).toBeVisible()
  await pause(3000)

  await page.getByRole('link', { name: 'Contrats', exact: true }).click()
  await expect(page.locator('tbody tr').first()).toBeVisible()
  await pause()
  await page.getByLabel('Filtrer par échéance').selectOption('30')
  await pause(2000)

  await page.getByRole('link', { name: 'Clients', exact: true }).click()
  await expect(page.locator('tbody tr').first()).toBeVisible()
  await page.getByLabel('Rechercher un client').pressSequentially('lefevre garage', { delay: 60 })
  await pause(800)
  await page.getByLabel('Rechercher un client').fill('demo')
  await pause(1500)

  await page.getByRole('link', { name: 'Journal' }).click()
  await expect(page.getByText(/a (créé|modifié)/).first()).toBeVisible()
  await pause()

  await page.goto(`${PORTAL}/login`)
  await pause(1200)
  await page.getByRole('button', { name: 'Client société', exact: true }).click()
  await expect(page.getByRole('heading', { name: 'Mes contrats' })).toBeVisible()
  await pause(2000)

  await page.getByRole('link', { name: /Entreprise/ }).click()
  await expect(page.getByRole('heading', { name: 'Documents' })).toBeVisible()
  await pause(2500)

  await page.getByRole('link', { name: 'Sinistres', exact: true }).click()
  await expect(page.getByRole('heading', { name: 'Mes sinistres' })).toBeVisible()
  await pause(2000)

  const video = page.video()
  await page.close()
  await video?.saveAs('../docs/demo.webm')
})
