// The reference catalog: Dutch must provide every key, or the type check fails.

export const fr = {
  app: {
    title: "BrokerDesk – Portail client",
    description:
      "Consultez vos contrats d'assurance, téléchargez vos documents et déclarez un sinistre en ligne, auprès de votre courtier.",
    openGraphLocale: "fr_BE",
    language: "Langue",
  },
  labels: {
    contractTypes: {
      motor: "Auto",
      home: "Habitation",
      liability: "RC familiale",
      life: "Vie",
      health: "Hospitalisation",
      legal: "Protection juridique",
      business: "Entreprise",
    },
    paymentFrequencies: {
      annual: "Annuelle",
      semiannual: "Semestrielle",
      quarterly: "Trimestrielle",
      monthly: "Mensuelle",
    },
    statuses: {
      active: "Actif",
      suspended: "Suspendu",
      cancelled: "Résilié",
      expired: "Expiré",
      open: "Ouvert",
      in_review: "En cours",
      settled: "Réglé",
      rejected: "Refusé",
    },
  },
  home: {
    metaTitle: "BrokerDesk – Vos assurances, au même endroit",
    signIn: "Se connecter",
    headline: "Vos assurances, au même endroit",
    intro:
      "Consultez vos contrats, téléchargez vos documents et déclarez un sinistre en ligne, en lien direct avec votre courtier.",
    cta: "Accéder à mon espace",
    featuresLabel: "Ce que vous pouvez faire",
    features: [
      {
        title: "Vos contrats",
        text: "Auto, habitation, RC familiale… Retrouvez vos garanties, vos primes et vos échéances.",
      },
      {
        title: "Vos documents",
        text: "Téléchargez à tout moment les documents que votre courtier met à votre disposition.",
      },
      {
        title: "Vos sinistres",
        text: "Déclarez un sinistre en quelques minutes et suivez son avancement.",
      },
    ],
    footer:
      "BrokerDesk est un projet de démonstration : les cabinets, les assureurs et les données sont fictifs.",
    howBuilt: "Comment c'est construit",
    homeLabel: "Accueil",
  },
  login: {
    metaTitle: "Connexion",
    metaDescription:
      "Connectez-vous à votre espace client BrokerDesk pour consulter vos contrats et vos documents.",
    title: "Espace client",
    subtitle: "Vos contrats, vos documents et vos sinistres.",
    email: "Adresse e-mail",
    password: "Mot de passe",
    submit: "Se connecter",
    submitting: "Connexion…",
    expired: "Votre session a expiré : reconnectez-vous.",
    unavailable: "Le service ne répond pas. Réessayez dans un instant.",
    brokerAccount:
      "Ce compte est un compte courtier : connectez-vous à l'espace courtier.",
    tooManyAttempts: "Trop de tentatives. Réessayez dans une minute.",
    failed: "La connexion a échoué. Réessayez.",
    demoTitle: "Vous évaluez l'application ?",
    demoText:
      "Connectez-vous en un clic. Les données sont fictives et réinitialisées chaque nuit.",
    backOffice: "Espace courtier →",
    stackTitle: "Comment c'est construit",
    seeDecisions: "Voir les choix techniques →",
    forgot: "Mot de passe oublié ?",
    resetDone:
      "Votre mot de passe a été changé : connectez-vous avec le nouveau.",
  },
  password: {
    invitationTitle: "Bienvenue sur votre espace client",
    invitationIntro: (email: string) =>
      `Choisissez le mot de passe du compte ${email}.`,
    activate: "Activer mon espace",
    forgotTitle: "Mot de passe oublié",
    forgotIntro:
      "Indiquez votre adresse e-mail : si elle correspond à un compte, vous recevrez un lien pour choisir un nouveau mot de passe.",
    email: "Adresse e-mail",
    send: "Envoyer le lien",
    sent: "Si un compte utilise cette adresse, un lien vient d'être envoyé. Pensez à vérifier vos courriers indésirables.",
    resetTitle: "Nouveau mot de passe",
    resetIntro: (email: string) =>
      `Choisissez un nouveau mot de passe pour ${email}.`,
    password: "Mot de passe",
    confirmation: "Confirmer le mot de passe",
    hint: "10 caractères minimum.",
    save: "Enregistrer mon mot de passe",
    saving: "Enregistrement…",
    sending: "Envoi…",
    incompleteLink: "Ce lien est incomplet : utilisez celui reçu par mail.",
    failed: "L'enregistrement a échoué. Réessayez.",
    backToLogin: "← Retour à la connexion",
  },
  account: {
    contractsLabel: "Mes contrats",
    mainNavigation: "Navigation principale",
    contracts: "Contrats",
    claims: "Sinistres",
    logout: "Se déconnecter",
    yourBroker: (name: string) => `Votre courtier : ${name}`,
    howBuilt: "Comment c'est construit",
  },
  contracts: {
    title: "Mes contrats",
    count: (count: number) => `${count} contrat${count > 1 ? "s" : ""}`,
    empty: "Vous n'avez pas encore de contrat.",
    expiry: "Échéance",
    annualPremium: "Prime annuelle",
    detailTitle: "Mon contrat",
    back: "← Mes contrats",
    start: "Début",
    noticeDeadline: "Date limite de résiliation",
    paymentFrequency: "Fractionnement",
    documents: "Documents",
    noDocuments: "Aucun document disponible.",
    claims: "Sinistres",
    declareClaim: "Déclarer un sinistre",
    noClaims: "Aucun sinistre sur ce contrat.",
  },
  claims: {
    title: "Mes sinistres",
    count: (count: number) => `${count} sinistre${count > 1 ? "s" : ""}`,
    declare: "Déclarer un sinistre",
    declared: (reference: string) =>
      `Votre sinistre a bien été déclaré sous la référence ${reference}. Votre courtier revient vers vous.`,
    empty: "Vous n'avez déclaré aucun sinistre.",
    occurredOn: (date: string) => `survenu le ${date}`,
    settledAmount: (amount: string) => `Montant réglé : ${amount}`,
    back: "← Mes sinistres",
    newIntro: "Votre courtier reçoit votre déclaration et revient vers vous.",
    noActiveContract:
      "Un sinistre se déclare sur un contrat actif, et vous n'en avez aucun pour le moment.",
    contract: "Contrat concerné",
    chooseContract: "Choisir un contrat",
    date: "Date du sinistre",
    whatHappened: "Que s'est-il passé ?",
    submitting: "Envoi…",
    submit: "Envoyer ma déclaration",
    failed: "La déclaration a échoué. Réessayez.",
  },
  errors: {
    title: "Une erreur est survenue",
    text: "Le service ne répond pas pour le moment.",
    retry: "Réessayer",
    notFoundTitle: "Page introuvable",
    notFoundText: "Cette page n'existe pas, ou vous n'y avez pas accès.",
    notFoundBack: "Retour à mes contrats",
  },
  about: {
    title: "Comment c'est construit",
    metaDescription:
      "Les technologies et les choix techniques derrière BrokerDesk : Laravel, Vue, Next.js et PostgreSQL.",
    subtitle: "Les technologies et les choix derrière cette démonstration.",
    source: "Code source sur GitHub",
    backOffice: "Espace courtier →",
    decisionsTitle: "Choix techniques",
    youAreHere: "vous êtes ici",
    layers: {
      broker: {
        name: "Espace courtier",
        role: "Une application monopage pour un outil utilisé toute la journée : tableaux, filtres et formulaires.",
      },
      portal: {
        name: "Portail client",
        role: "Des pages rendues sur le serveur : la clé de connexion du client ne quitte jamais le serveur.",
      },
      api: {
        name: "API",
        role: "Les règles métier, les droits d’accès, les alertes planifiées et les envois de mails, en français, en néerlandais ou en anglais.",
      },
      database: {
        name: "Base de données",
        role: "Des contraintes et des clés étrangères composites qui empêchent un cabinet de voir les données d’un autre.",
      },
      infrastructure: {
        name: "Infrastructure",
        role: "Une commande pour tout lancer, et le HTTPS en production.",
      },
      quality: {
        name: "Qualité",
        role: "Des tests à chaque niveau, jusqu’au vrai navigateur, rejoués à chaque modification.",
      },
    },
    decisions: [
      {
        title: "Chaque cabinet est isolé deux fois",
        text: "Les requêtes et les règles d’accès filtrent par cabinet, et la base de données refuse elle-même un contrat rattaché au client d’un autre cabinet.",
      },
      {
        title: "Deux connexions pour deux publics",
        text: "Ce portail garde la clé du client sur son serveur, hors de portée des scripts du navigateur. L’espace courtier utilise une session protégée contre le CSRF.",
      },
      {
        title: "Ce qui est interdit reste invisible",
        text: "Un dossier auquel vous n’avez pas accès répond « introuvable » plutôt que « interdit », pour ne pas révéler qu’il existe.",
      },
      {
        title: "Les règles belges sont dans le code",
        text: "Numéros d’entreprise vérifiés par leur chiffre de contrôle, préavis de trois mois avant la reconduction tacite, montants stockés en centimes.",
      },
    ],
  },
};

/** The shape every catalog follows: the same keys, any wording. */
export type Messages = Widen<typeof fr>;

type Widen<T> = T extends string
  ? string
  : T extends (...args: infer A) => string
    ? (...args: A) => string
    : T extends readonly (infer U)[]
      ? Widen<U>[]
      : { [K in keyof T]: Widen<T[K]> };
