export type UserRole = 'admin' | 'broker' | 'client'
export type ClientType = 'individual' | 'company'
export type ContractType = 'motor' | 'home' | 'liability' | 'life' | 'health' | 'legal' | 'business'
export type ContractStatus = 'active' | 'suspended' | 'cancelled' | 'expired'
export type PaymentFrequency = 'annual' | 'semiannual' | 'quarterly' | 'monthly'
export type ClaimStatus = 'open' | 'in_review' | 'settled' | 'rejected'

export interface NamedRef {
  id: number
  name: string
}

export interface User {
  id: number
  name: string
  email: string
  role: UserRole
  brokerage: NamedRef
  client_id: number | null
}

export interface Client {
  id: number
  type: ClientType
  name: string
  first_name: string | null
  last_name: string | null
  company_name: string | null
  enterprise_number: string | null
  email: string
  phone: string | null
  street: string
  postal_code: string
  city: string
  broker?: NamedRef
  contracts_count?: number
}

export interface Contract {
  id: number
  policy_number: string
  type: ContractType
  status: ContractStatus
  starts_on: string
  expires_on: string
  notice_deadline: string
  annual_premium_cents: number
  payment_frequency: PaymentFrequency
  client?: NamedRef
  insurer?: NamedRef
}

export interface Claim {
  id: number
  reference: string
  occurred_on: string
  description: string
  status: ClaimStatus
  estimated_amount_cents: number | null
  settled_amount_cents: number | null
  closed_at: string | null
  contract?: Contract
  declared_by?: NamedRef
}

export interface ContractDocument {
  id: number
  contract_id: number
  original_name: string
  size_bytes: number
  mime_type: string
  visible_to_client: boolean
  created_at: string
}

export interface AppNotification {
  id: string
  type: string
  data: {
    contract_id: number
    policy_number: string
    client_name: string
    expires_on: string
    notice_deadline: string
    threshold_days: number
  }
  read_at: string | null
  created_at: string
}

export interface ActivityLog {
  id: number
  action: 'created' | 'updated' | 'deleted'
  subject_type: 'client' | 'contract' | 'claim' | 'document'
  subject_id: number
  subject_label: string
  changes: Record<string, { old: unknown; new: unknown }> | null
  user: NamedRef | null
  created_at: string
}

export interface Dashboard {
  expiring: { within_30: number; from_31_to_60: number; from_61_to_90: number }
  open_claims: number
  active_contracts: number
  annual_premium_cents: number
  premium_by_type: { type: ContractType; contracts: number; annual_premium_cents: number }[]
  next_expiries: Contract[]
}

export interface PageMeta {
  current_page: number
  last_page: number
  from: number | null
  to: number | null
  total: number
}

export interface Paginated<T> {
  data: T[]
  meta: PageMeta
}

export interface Resource<T> {
  data: T
}

export interface DemoAccounts {
  password: string
  accounts: { label: string; app: 'broker' | 'portal'; email: string }[]
}
