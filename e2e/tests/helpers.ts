import { expect, type Page } from '@playwright/test'

export async function signInToBackOffice(
  page: Page,
  account: 'Courtier' | 'Administrateur du cabinet',
) {
  await page.goto('/login')
  await page.getByRole('button', { name: account, exact: true }).click()
  await expect(page.getByRole('heading', { name: 'Tableau de bord' })).toBeVisible()
}

/** Different on every run, so that tests can repeat on the same database. */
export function uniqueSuffix(): string {
  return Date.now().toString().slice(-7)
}
