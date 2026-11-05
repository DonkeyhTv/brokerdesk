import type {
  ClaimStatus,
  ContractStatus,
  ContractType,
  PaymentFrequency,
} from "./types";

export const CONTRACT_TYPES: Record<ContractType, string> = {
  motor: "Auto",
  home: "Habitation",
  liability: "RC familiale",
  life: "Vie",
  health: "Hospitalisation",
  legal: "Protection juridique",
  business: "Entreprise",
};

export const PAYMENT_FREQUENCIES: Record<PaymentFrequency, string> = {
  annual: "Annuelle",
  semiannual: "Semestrielle",
  quarterly: "Trimestrielle",
  monthly: "Mensuelle",
};

export const STATUSES: Record<ContractStatus | ClaimStatus, string> = {
  active: "Actif",
  suspended: "Suspendu",
  cancelled: "Résilié",
  expired: "Expiré",
  open: "Ouvert",
  in_review: "En cours",
  settled: "Réglé",
  rejected: "Refusé",
};
