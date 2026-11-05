import type {
  ActivityLog,
  ClaimStatus,
  ClientType,
  ContractStatus,
  ContractType,
  PaymentFrequency,
  UserRole,
} from '@/api/types'

export const CONTRACT_TYPES: Record<ContractType, string> = {
  motor: 'Auto',
  home: 'Habitation',
  liability: 'RC familiale',
  life: 'Vie',
  health: 'Hospitalisation',
  legal: 'Protection juridique',
  business: 'Entreprise',
}

export const CONTRACT_STATUSES: Record<ContractStatus, string> = {
  active: 'Actif',
  suspended: 'Suspendu',
  cancelled: 'Résilié',
  expired: 'Expiré',
}

export const PAYMENT_FREQUENCIES: Record<PaymentFrequency, string> = {
  annual: 'Annuelle',
  semiannual: 'Semestrielle',
  quarterly: 'Trimestrielle',
  monthly: 'Mensuelle',
}

export const CLAIM_STATUSES: Record<ClaimStatus, string> = {
  open: 'Ouvert',
  in_review: 'En cours',
  settled: 'Réglé',
  rejected: 'Refusé',
}

export const CLIENT_TYPES: Record<ClientType, string> = {
  individual: 'Particulier',
  company: 'Entreprise',
}

export const USER_ROLES: Record<UserRole, string> = {
  admin: 'Administrateur',
  broker: 'Courtier',
  client: 'Client',
}

export const ACTIVITY_ACTIONS: Record<ActivityLog['action'], string> = {
  created: 'a créé',
  updated: 'a modifié',
  deleted: 'a supprimé',
}

export const ACTIVITY_SUBJECTS: Record<ActivityLog['subject_type'], string> = {
  client: 'le client',
  contract: 'le contrat',
  claim: 'le sinistre',
  document: 'le document',
}
