import type { Messages } from "./fr";

export const nl: Messages = {
  app: {
    title: "BrokerDesk – Klantenportaal",
    description:
      "Bekijk uw verzekeringscontracten, download uw documenten en geef online een schadegeval aan, bij uw makelaar.",
    openGraphLocale: "nl_BE",
    language: "Taal",
  },
  labels: {
    contractTypes: {
      motor: "Auto",
      home: "Woning",
      liability: "BA Familie",
      life: "Leven",
      health: "Hospitalisatie",
      legal: "Rechtsbijstand",
      business: "Onderneming",
    },
    paymentFrequencies: {
      annual: "Jaarlijks",
      semiannual: "Halfjaarlijks",
      quarterly: "Driemaandelijks",
      monthly: "Maandelijks",
    },
    statuses: {
      active: "Actief",
      suspended: "Geschorst",
      cancelled: "Opgezegd",
      expired: "Vervallen",
      open: "Open",
      in_review: "In behandeling",
      settled: "Vergoed",
      rejected: "Geweigerd",
    },
  },
  home: {
    metaTitle: "BrokerDesk – Uw verzekeringen op één plek",
    signIn: "Aanmelden",
    headline: "Uw verzekeringen op één plek",
    intro:
      "Bekijk uw contracten, download uw documenten en geef online een schadegeval aan, in rechtstreeks contact met uw makelaar.",
    cta: "Naar mijn omgeving",
    featuresLabel: "Wat u kunt doen",
    features: [
      {
        title: "Uw contracten",
        text: "Auto, woning, BA Familie… Vind uw waarborgen, premies en vervaldagen terug.",
      },
      {
        title: "Uw documenten",
        text: "Download op elk moment de documenten die uw makelaar voor u klaarzet.",
      },
      {
        title: "Uw schadegevallen",
        text: "Geef een schadegeval aan in enkele minuten en volg de afhandeling op.",
      },
    ],
    footer:
      "BrokerDesk is een demonstratieproject: de kantoren, de verzekeraars en de gegevens zijn fictief.",
    howBuilt: "Hoe het gebouwd is",
    homeLabel: "Startpagina",
  },
  login: {
    metaTitle: "Aanmelden",
    metaDescription:
      "Meld u aan op uw BrokerDesk-klantenomgeving om uw contracten en documenten te bekijken.",
    title: "Klantenomgeving",
    subtitle: "Uw contracten, uw documenten en uw schadegevallen.",
    email: "E-mailadres",
    password: "Wachtwoord",
    submit: "Aanmelden",
    submitting: "Bezig met aanmelden…",
    expired: "Uw sessie is verlopen: meld u opnieuw aan.",
    unavailable: "De dienst antwoordt niet. Probeer het zo meteen opnieuw.",
    brokerAccount:
      "Dit is een makelaarsaccount: meld u aan op de makelaarsomgeving.",
    tooManyAttempts: "Te veel pogingen. Probeer het over een minuut opnieuw.",
    failed: "Het aanmelden is mislukt. Probeer het opnieuw.",
    demoTitle: "Evalueert u de toepassing?",
    demoText:
      "Meld u aan met één klik. De gegevens zijn fictief en worden elke nacht hersteld.",
    backOffice: "Makelaarsomgeving →",
    stackTitle: "Hoe het gebouwd is",
    seeDecisions: "De technische keuzes bekijken →",
  },
  account: {
    contractsLabel: "Mijn contracten",
    mainNavigation: "Hoofdnavigatie",
    contracts: "Contracten",
    claims: "Schadegevallen",
    logout: "Afmelden",
    yourBroker: (name: string) => `Uw makelaar: ${name}`,
    howBuilt: "Hoe het gebouwd is",
  },
  contracts: {
    title: "Mijn contracten",
    count: (count: number) => `${count} contract${count > 1 ? "en" : ""}`,
    empty: "U hebt nog geen contract.",
    expiry: "Vervaldag",
    annualPremium: "Jaarpremie",
    detailTitle: "Mijn contract",
    back: "← Mijn contracten",
    start: "Begin",
    noticeDeadline: "Uiterste opzegdatum",
    paymentFrequency: "Betalingsfrequentie",
    documents: "Documenten",
    noDocuments: "Geen documenten beschikbaar.",
    claims: "Schadegevallen",
    declareClaim: "Een schadegeval aangeven",
    noClaims: "Geen schadegevallen op dit contract.",
  },
  claims: {
    title: "Mijn schadegevallen",
    count: (count: number) => `${count} schadegeval${count > 1 ? "len" : ""}`,
    declare: "Een schadegeval aangeven",
    declared: (reference: string) =>
      `Uw schadegeval werd aangegeven onder referentie ${reference}. Uw makelaar neemt contact met u op.`,
    empty: "U hebt nog geen schadegeval aangegeven.",
    occurredOn: (date: string) => `gebeurd op ${date}`,
    settledAmount: (amount: string) => `Uitbetaald bedrag: ${amount}`,
    back: "← Mijn schadegevallen",
    newIntro: "Uw makelaar ontvangt uw aangifte en neemt contact met u op.",
    noActiveContract:
      "Een schadegeval wordt aangegeven op een actief contract, en u hebt er momenteel geen.",
    contract: "Betrokken contract",
    chooseContract: "Kies een contract",
    date: "Schadedatum",
    whatHappened: "Wat is er gebeurd?",
    submitting: "Bezig met verzenden…",
    submit: "Mijn aangifte verzenden",
    failed: "De aangifte is mislukt. Probeer het opnieuw.",
  },
  errors: {
    title: "Er is een fout opgetreden",
    text: "De dienst antwoordt op dit moment niet.",
    retry: "Opnieuw proberen",
    notFoundTitle: "Pagina niet gevonden",
    notFoundText: "Deze pagina bestaat niet, of u hebt er geen toegang toe.",
    notFoundBack: "Terug naar mijn contracten",
  },
  about: {
    title: "Hoe het gebouwd is",
    metaDescription:
      "De technologieën en technische keuzes achter BrokerDesk: Laravel, Vue, Next.js en PostgreSQL.",
    subtitle: "De technologieën en keuzes achter deze demonstratie.",
    source: "Broncode op GitHub",
    backOffice: "Makelaarsomgeving →",
    decisionsTitle: "Technische keuzes",
    youAreHere: "u bent hier",
    layers: {
      broker: {
        name: "Makelaarsomgeving",
        role: "Een single-page-applicatie voor een tool die de hele dag gebruikt wordt: tabellen, filters en formulieren.",
      },
      portal: {
        name: "Klantenportaal",
        role: "Pagina’s die op de server worden opgebouwd: de aanmeldsleutel van de klant verlaat de server nooit.",
      },
      api: {
        name: "API",
        role: "De bedrijfsregels, de toegangsrechten, de geplande meldingen en de e-mails, in het Frans, het Nederlands of het Engels.",
      },
      database: {
        name: "Database",
        role: "Beperkingen en samengestelde vreemde sleutels die verhinderen dat een kantoor de gegevens van een ander ziet.",
      },
      infrastructure: {
        name: "Infrastructuur",
        role: "Eén commando om alles te starten, en HTTPS in productie.",
      },
      quality: {
        name: "Kwaliteit",
        role: "Tests op elk niveau, tot in een echte browser, bij elke wijziging opnieuw uitgevoerd.",
      },
    },
    decisions: [
      {
        title: "Elk kantoor is dubbel afgeschermd",
        text: "De queries en de toegangsregels filteren per kantoor, en de database weigert zelf een contract dat aan de klant van een ander kantoor gekoppeld is.",
      },
      {
        title: "Twee aanmeldingen voor twee doelgroepen",
        text: "Dit portaal houdt de sleutel van de klant op zijn server, buiten bereik van scripts in de browser. De makelaarsomgeving gebruikt een sessie die tegen CSRF beschermd is.",
      },
      {
        title: "Wat verboden is, blijft onzichtbaar",
        text: "Een dossier waartoe u geen toegang hebt, antwoordt “niet gevonden” in plaats van “verboden”, om niet te verraden dat het bestaat.",
      },
      {
        title: "De Belgische regels zitten in de code",
        text: "Ondernemingsnummers gecontroleerd via hun controlegetal, een opzegtermijn van drie maanden voor de stilzwijgende verlenging, bedragen opgeslagen in centen.",
      },
    ],
  },
};
