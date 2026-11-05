export type ContractType =
  "motor" | "home" | "liability" | "life" | "health" | "legal" | "business";
export type ContractStatus = "active" | "suspended" | "cancelled" | "expired";
export type PaymentFrequency =
  "annual" | "semiannual" | "quarterly" | "monthly";
export type ClaimStatus = "open" | "in_review" | "settled" | "rejected";

export interface NamedRef {
  id: number;
  name: string;
}

export interface User {
  id: number;
  name: string;
  email: string;
  role: "admin" | "broker" | "client";
  brokerage: NamedRef;
  client_id: number | null;
}

export interface Contract {
  id: number;
  policy_number: string;
  type: ContractType;
  status: ContractStatus;
  starts_on: string;
  expires_on: string;
  notice_deadline: string;
  annual_premium_cents: number;
  payment_frequency: PaymentFrequency;
  client?: NamedRef;
  insurer?: NamedRef;
}

export interface Claim {
  id: number;
  reference: string;
  occurred_on: string;
  description: string;
  status: ClaimStatus;
  estimated_amount_cents: number | null;
  settled_amount_cents: number | null;
  closed_at: string | null;
  contract?: Contract;
}

export interface ContractDocument {
  id: number;
  contract_id: number;
  original_name: string;
  size_bytes: number;
  mime_type: string;
  visible_to_client: boolean;
  created_at: string;
}

export interface Paginated<T> {
  data: T[];
  meta: { current_page: number; last_page: number; total: number };
}

export interface Resource<T> {
  data: T;
}

export interface DemoAccounts {
  password: string;
  accounts: { label: string; app: "broker" | "portal"; email: string }[];
}
