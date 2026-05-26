export interface PaginationMeta {
  page: number
  per_page: number
  total: number
  total_pages: number
  has_next: boolean
  has_prev: boolean
}

export interface PaginatedData<T, TStats> {
  items: T[]
  pagination: PaginationMeta
  stats: TStats
}

export interface ApiEnvelope<T> {
  success: boolean
  data: T
  message?: string
}

export interface StoreItem {
  id: number
  name: string
  slug: string
  active: boolean
  orders_count: number
  users_count: number
  created_at: string
}

export interface StoresStats {
  total: number
  active: number
  inactive: number
}

export interface OrderItem {
  id: number
  customer_name: string
  customer_phone: string
  status: string
  total: number
  company_id: number
  company_name: string
  created_at: string
}

export interface OrdersStats {
  total: number
  pending: number
  paid: number
  completed: number
  canceled: number
  gross_total: number
}

export type LogLevel = 'debug' | 'info' | 'warning' | 'error' | 'critical'

export interface LogItem {
  id: number
  level: LogLevel
  module: string
  message: string
  context: Record<string, unknown>
  logged_at: string
}

export interface LogsStats {
  total: number
  debug: number
  info: number
  warning: number
  error: number
  critical: number
}

export interface UserItem {
  id: number
  name: string
  email: string
  role: string
  active: boolean
  company_id: number | null
  company_name: string
  created_at: string
}

export interface UsersStats {
  total: number
  active: number
  inactive: number
  root: number
  owner: number
  staff: number
}

export type ManagedUserRole = 'root' | 'owner' | 'staff'

export interface UserUpsertPayload {
  company_id: number
  name: string
  email: string
  password?: string
  role: ManagedUserRole
  active: boolean
}

export interface UserPasswordPayload {
  user_id: number
  password: string
}

export interface UserDeletePayload {
  user_id: number
}
