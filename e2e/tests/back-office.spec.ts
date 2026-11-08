import { expect, test } from '@playwright/test'
import { readFileSync } from 'node:fs'
import { signInToBackOffice, uniqueSuffix } from './helpers'

test('guests are sent to the login page and come back after signing in', async ({ page }) => {
  await page.goto('/contracts')
  await expect(page).toHaveURL(/\/login\?redirect=/)

  await page.getByRole('button', { name: 'Courtier', exact: true }).click()

  await expect(page.getByRole('heading', { name: 'Contrats' })).toBeVisible()
})

test('wrong credentials are explained, and clients are sent to their portal', async ({ page }) => {
  await page.goto('/login')

  await page.getByLabel('Adresse e-mail').fill('nobody@example.be')
  await page.getByLabel('Mot de passe').fill('wrong')
  await page.getByRole('button', { name: 'Se connecter' }).click()
  await expect(page.getByRole('alert')).toBeVisible()

  await page.getByLabel('Adresse e-mail').fill('client@example.be')
  await page.getByLabel('Mot de passe').fill('password')
  await page.getByRole('button', { name: 'Se connecter' }).click()
  await expect(page.getByText('connectez-vous au portail client')).toBeVisible()
})

test('a broker works on a client, a contract, a document and a claim', async ({ page }) => {
  const suffix = uniqueSuffix()
  const lastName = `Lefèvre${suffix}`

  await signInToBackOffice(page, 'Courtier')
  await expect(page.getByText('Dans 30 jours')).toBeVisible()
  await expect(page.getByRole('heading', { name: 'Primes annuelles par branche' })).toBeVisible()
  await expect(page.getByRole('link', { name: 'Équipe' })).toHaveCount(0)

  await test.step('create a client, after fixing a validation error', async () => {
    await page.getByRole('link', { name: 'Clients', exact: true }).click()
    await page.getByRole('link', { name: 'Nouveau client' }).click()
    await page.getByLabel('Prénom').fill('Émilie')
    await page.getByLabel('Nom', { exact: true }).fill(lastName)
    await page.getByLabel('E-mail').fill('not-an-email')
    await page.getByLabel('Rue et numéro').fill('Rue de la Station 1')
    await page.getByLabel('Code postal').fill('4000')
    await page.getByLabel('Commune').fill('Liège')
    await page.getByRole('button', { name: 'Enregistrer' }).click()
    await expect(page.locator('#email-error')).toBeVisible()

    await page.getByLabel('E-mail').fill(`emilie.${suffix}@example.be`)
    await page.getByRole('button', { name: 'Enregistrer' }).click()
    await expect(page.getByRole('heading', { name: `Émilie ${lastName}` })).toBeVisible()
  })

  await test.step('find the client, ignoring accents', async () => {
    await page.getByRole('link', { name: 'Clients', exact: true }).click()
    await page.getByLabel('Rechercher un client').fill(`lefevre${suffix}`)
    await expect(page.getByRole('link', { name: `Émilie ${lastName}` })).toBeVisible()
    await expect(page.locator('tbody tr')).toHaveCount(1)
    await page.getByRole('link', { name: `Émilie ${lastName}` }).click()
  })

  const policyNumber = `E2E-${suffix}`

  await test.step('add a contract to the client', async () => {
    await page.getByRole('link', { name: 'Nouveau contrat' }).click()
    await expect(page.getByRole('combobox', { name: 'Client' })).toHaveValue(`Émilie ${lastName}`)
    await page.getByLabel('Assureur').selectOption({ label: 'Brabant Vie' })
    await page.getByLabel('N° de police').fill(policyNumber)
    await page.getByLabel('Date de début').fill('2026-01-01')
    await page.getByLabel("Date d'échéance").fill('2027-01-01')
    await page.getByLabel('Prime annuelle').fill('850,50')
    await page.getByRole('button', { name: 'Enregistrer' }).click()

    await expect(page.getByRole('heading', { name: policyNumber })).toBeVisible()
    await expect(page.getByText('01/10/2026')).toBeVisible()
    await expect(page.getByText(/850,50/)).toBeVisible()
  })

  await test.step('upload a PDF, refuse anything else, then download it', async () => {
    const input = page.getByLabel('Fichier PDF')

    await input.setInputFiles({
      name: 'fake.pdf',
      mimeType: 'application/pdf',
      buffer: Buffer.from('<?php echo 1;'),
    })
    await page.getByRole('button', { name: 'Ajouter le PDF' }).click()
    await expect(page.getByText('Seuls les fichiers PDF')).toBeVisible()

    await input.setInputFiles({
      name: 'conditions.pdf',
      mimeType: 'application/pdf',
      buffer: Buffer.from('%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer<<>>\n%%EOF\n'),
    })
    await page.getByRole('button', { name: 'Ajouter le PDF' }).click()

    const link = page.getByRole('link', { name: 'conditions.pdf' })
    await expect(link).toBeVisible()
    await expect(page.getByText('Visible par le client', { exact: false }).first()).toBeVisible()

    const download = page.waitForEvent('download')
    await link.click()
    expect((await download).suggestedFilename()).toBe('conditions.pdf')
  })

  await test.step('declare a claim, settle it, and see it frozen', async () => {
    await page.getByRole('link', { name: 'Déclarer un sinistre' }).click()
    await expect(page.getByRole('combobox', { name: 'Contrat' })).toHaveValue(
      new RegExp(policyNumber),
    )
    await page.getByLabel('Description').fill('Pare-chocs abîmé sur un parking.')
    await page.getByRole('button', { name: 'Déclarer' }).click()

    await expect(page.getByRole('heading', { name: /^SIN-/ })).toBeVisible()

    await page.getByLabel('Statut').selectOption({ label: 'Réglé' })
    await page.getByRole('button', { name: 'Enregistrer' }).click()
    await expect(page.getByText('Indiquez le montant réglé.')).toBeVisible()

    await page.getByLabel('Montant réglé').fill('420')
    await page.getByRole('button', { name: 'Enregistrer' }).click()
    await expect(page.getByText('il ne peut plus être modifié')).toBeVisible()
  })

  await test.step('find any client from the contract form, ignoring accents', async () => {
    await page.getByRole('link', { name: 'Contrats', exact: true }).click()
    await page.getByRole('link', { name: 'Nouveau contrat' }).click()
    const client = page.getByRole('combobox', { name: 'Client' })
    await client.fill(`lefevre${suffix}`)
    await page.getByRole('option', { name: `Émilie ${lastName}` }).click()
    await expect(client).toHaveValue(`Émilie ${lastName}`)
    await page.getByRole('link', { name: 'Annuler' }).click()
  })

  await test.step('export the filtered contracts as CSV', async () => {
    await page.getByRole('link', { name: 'Contrats', exact: true }).click()
    await page.getByLabel('Rechercher un contrat').fill(policyNumber)
    await expect(page.locator('tbody tr')).toHaveCount(1)

    const download = page.waitForEvent('download')
    await page.getByRole('link', { name: 'Exporter en CSV' }).click()
    const file = await (await download).path()
    const csv = readFileSync(file, 'utf8')

    expect(csv).toContain(policyNumber)
    expect(csv.trim().split('\n')).toHaveLength(2)
  })

  await test.step('read a notification', async () => {
    await page.getByRole('link', { name: /^Notifications/ }).click()
    await expect(page.getByText('arrive à échéance').first()).toBeVisible()
  })

  await test.step('sign out', async () => {
    await page.getByRole('button', { name: 'Se déconnecter' }).click()
    await expect(page).toHaveURL(/\/login/)
    await page.goto('/clients')
    await expect(page).toHaveURL(/\/login/)
  })
})

test('an admin manages the team and reads the activity log', async ({ page }) => {
  const suffix = uniqueSuffix()

  await signInToBackOffice(page, 'Administrateur du cabinet')
  await expect(page.getByText('Tout le cabinet Cabinet Démo Meuse')).toBeVisible()

  await page.getByRole('link', { name: 'Équipe' }).click()
  const demoBroker = page.getByRole('row', { name: /Demo Broker/ })
  await expect(demoBroker.getByText('Compte de démo')).toBeVisible()
  await expect(demoBroker.getByRole('button')).toHaveCount(0)

  await page.getByRole('button', { name: 'Ajouter un membre' }).click()
  await page.getByLabel('Nom').fill(`Nora ${suffix}`)
  await page.getByLabel('E-mail').fill(`nora.${suffix}@example.be`)
  await page.getByLabel('Mot de passe').fill('short')
  await page.getByRole('button', { name: 'Enregistrer' }).click()
  await expect(page.locator('#password-error')).toBeVisible()

  await page.getByLabel('Mot de passe').fill('long-enough-password')
  await page.getByRole('button', { name: 'Enregistrer' }).click()
  const row = page.getByRole('row', { name: new RegExp(`Nora ${suffix}`) })
  await expect(row).toBeVisible()

  page.once('dialog', (dialog) => dialog.accept())
  await row.getByRole('button', { name: 'Retirer' }).click()
  await expect(row).toHaveCount(0)

  await page.getByRole('link', { name: 'Journal' }).click()
  await expect(page.getByRole('heading', { name: "Journal d'activité" })).toBeVisible()
  await expect(page.getByText(/a (créé|modifié)/).first()).toBeVisible()
})

test('takes the README screenshots', async ({ page }) => {
  test.skip(!process.env.SCREENSHOTS, 'Only on demand: SCREENSHOTS=1')

  const shot = async (name: string) => {
    await page.mouse.move(0, 0)
    await page.screenshot({ path: `../docs/screenshots/${name}.png` })
  }

  await page.goto('/login')
  await expect(page.getByText("Vous évaluez l'application ?")).toBeVisible()
  await shot('back-office-login')

  await page.getByRole('button', { name: 'Administrateur du cabinet', exact: true }).click()
  await expect(page.getByRole('heading', { name: 'Primes annuelles par branche' })).toBeVisible()
  await shot('back-office-dashboard')

  await page.getByRole('link', { name: 'Contrats', exact: true }).click()
  await expect(page.locator('tbody tr').first()).toBeVisible()
  await shot('back-office-contracts')

  await page.locator('tbody tr').first().getByRole('link').first().click()
  await expect(page.getByRole('heading', { name: 'Documents' })).toBeVisible()
  await shot('back-office-contract')

  await page.getByRole('link', { name: 'Journal' }).click()
  await expect(page.getByText(/a (créé|modifié)/).first()).toBeVisible()
  await shot('back-office-activity')
})

test('evaluators see how the application is built, from the login page and from the menu', async ({
  page,
}) => {
  await page.goto('/login')
  await expect(page.getByRole('region', { name: "Comment c'est construit" })).toContainText(
    'Laravel 13',
  )

  await signInToBackOffice(page, 'Courtier')
  await page.getByRole('link', { name: 'Comment c’est construit' }).click()
  await expect(page.getByRole('heading', { name: "Comment c'est construit" })).toBeVisible()
  await expect(page.getByText('Chaque cabinet est isolé deux fois')).toBeVisible()

  const docs = await page.request.get('/docs/api')
  expect(docs.ok()).toBe(true)
})

test('a broker renews a contract that expires soon, with an indexed premium', async ({ page }) => {
  await signInToBackOffice(page, 'Courtier')

  await page.getByRole('link', { name: 'Contrats', exact: true }).click()
  await page.getByLabel('Filtrer par échéance').selectOption('30')
  await page.locator('tbody tr a').first().click()

  await page.getByRole('button', { name: 'Renouveler' }).click()
  const form = page.getByRole('form', { name: /Renouveler jusqu'au/ })
  const newExpiry = (await form.getByRole('heading').textContent())?.match(
    /\d{2}\/\d{2}\/\d{4}/,
  )?.[0]
  await form.getByLabel('Prime annuelle du nouveau terme').fill('999,90')
  await form.getByRole('button', { name: 'Confirmer le renouvellement' }).click()

  await expect(page.getByText(`Contrat renouvelé jusqu'au ${newExpiry}.`)).toBeVisible()
  await expect(page.getByText(/999,90/)).toBeVisible()
  await expect(page.getByRole('button', { name: 'Renouveler' })).toHaveCount(0)
})

test('the back office speaks Dutch, down to the API validation messages', async ({ page }) => {
  await page.goto('/login')
  await page.getByRole('group', { name: 'Langue' }).getByRole('button', { name: 'nl' }).click()
  await expect(page.getByRole('heading', { name: 'Makelaarsomgeving' })).toBeVisible()
  await expect(page.locator('html')).toHaveAttribute('lang', 'nl')

  await page.getByRole('button', { name: 'Makelaar', exact: true }).click()
  await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible()
  await expect(page.getByText('Binnen 30 dagen')).toBeVisible()

  await page.getByRole('link', { name: 'Klanten', exact: true }).click()
  await page.getByRole('link', { name: 'Nieuwe klant' }).click()
  await page.getByLabel('E-mail').fill('geen-adres')
  await page.getByRole('button', { name: 'Opslaan' }).click()
  await expect(page.locator('#email-error')).toHaveText(
    'Het veld e-mailadres moet een geldig e-mailadres zijn.',
  )

  await page.reload()
  await expect(page.getByRole('heading', { name: 'Nieuwe klant' })).toBeVisible()
})

test('a broker invites a client, who activates the portal and later resets the password', async ({
  page,
  request,
}) => {
  const suffix = uniqueSuffix()
  const email = `invited.${suffix}@example.be`
  const PORTAL = 'http://localhost:3000'

  await test.step('the broker creates the client and invites them', async () => {
    // The admin manages every client, and keeps the demo broker under its login limit.
    await signInToBackOffice(page, 'Administrateur du cabinet')
    await page.goto('/clients/new')
    await page.getByLabel('Prénom').fill('Inès')
    await page.getByLabel('Nom', { exact: true }).fill(`Invitée${suffix}`)
    await page.getByLabel('E-mail').fill(email)
    await page.getByLabel('Rue et numéro').fill('Rue Neuve 3')
    await page.getByLabel('Code postal').fill('1000')
    await page.getByLabel('Commune').fill('Bruxelles')
    await page.getByLabel('Courtier responsable').selectOption({ label: 'Demo Broker' })
    await page.getByRole('button', { name: 'Enregistrer' }).click()

    const portal = page.getByRole('region', { name: 'Espace client' })
    await expect(portal).toContainText('n’a pas encore accès au portail')
    await portal.getByRole('button', { name: 'Inviter au portail' }).click()
    await expect(portal.getByText(`Invitation envoyée à ${email}.`)).toBeVisible()
    await expect(portal).toContainText('elle attend que le client choisisse son mot de passe')
  })

  await test.step('the client chooses a password from the link and lands in the portal', async () => {
    const link = await page
      .getByRole('region', { name: 'Espace client' })
      .getByRole('link', { name: /\/invitation\?token=/ })
      .getAttribute('href')

    await page.goto(link!)
    await expect(
      page.getByRole('heading', { name: 'Bienvenue sur votre espace client' }),
    ).toBeVisible()
    await page.getByLabel('Mot de passe', { exact: true }).fill('premier-mot-de-passe')
    await page.getByLabel('Confirmer le mot de passe').fill('premier-mot-de-passe')
    await page.getByRole('button', { name: 'Activer mon espace' }).click()
    await expect(page.getByRole('heading', { name: 'Mes contrats' })).toBeVisible()
    await expect(page.getByText("Vous n'avez pas encore de contrat.")).toBeVisible()
  })

  await test.step('the client forgets the password and gets a link by mail', async () => {
    await page.getByRole('button', { name: 'Se déconnecter' }).click()
    await page.goto(`${PORTAL}/login`)
    await page.getByRole('link', { name: 'Mot de passe oublié ?' }).click()
    await expect(page.getByRole('heading', { name: 'Mot de passe oublié' })).toBeVisible()
    await page.getByLabel('Adresse e-mail').fill(email)
    await page.getByRole('button', { name: 'Envoyer le lien' }).click()
    await expect(page.getByText('Si un compte utilise cette adresse')).toBeVisible()
  })

  await test.step('the link from the mail sets a new password', async () => {
    let resetLink = ''

    // The mail leaves through the queue: Mailpit shows it a moment later.
    await expect
      .poll(
        async () => {
          const search = await request.get(
            `http://localhost:8025/api/v1/search?query=${encodeURIComponent(`to:"${email}" subject:"nouveau mot de passe"`)}`,
          )
          const id = (await search.json()).messages?.[0]?.ID

          if (!id) return ''

          const message = await (
            await request.get(`http://localhost:8025/api/v1/message/${id}`)
          ).json()
          resetLink = message.Text.match(/http\S+nouveau-mot-de-passe\S+/)?.[0] ?? ''

          return resetLink
        },
        { timeout: 30_000 },
      )
      .toContain('/nouveau-mot-de-passe?token=')

    await page.goto(resetLink.replace(/[\])]+$/, ''))
    await page.getByLabel('Mot de passe', { exact: true }).fill('nouveau-mot-de-passe')
    await page.getByLabel('Confirmer le mot de passe').fill('nouveau-mot-de-passe')
    await page.getByRole('button', { name: 'Enregistrer mon mot de passe' }).click()
    await expect(page.getByText('Votre mot de passe a été changé')).toBeVisible()

    await page.getByLabel('Adresse e-mail').fill(email)
    await page.getByLabel('Mot de passe').fill('nouveau-mot-de-passe')
    await page.getByRole('button', { name: 'Se connecter' }).click()
    await expect(page.getByRole('heading', { name: 'Mes contrats' })).toBeVisible()
  })
})
