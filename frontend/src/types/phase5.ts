export interface ApiEnvelope<T> {
  success: boolean
  data: T
  message?: string
}

export interface AuditItem {
  id: number
  admin_name: string
  admin_email: string
  module: string
  action: string
  entity_type: string | null
  entity_id: number | null
  company_name: string | null
  description: string
  ip_address: string | null
  created_at: string
}

export interface AuditStats {
  total: number
  last_24h: number
  modules: number
  admins: number
}

export interface PaginationMeta {
  page: number
  per_page: number
  total: number
  total_pages: number
  has_next: boolean
  has_prev: boolean
}

export interface AuditData {
  items: AuditItem[]
  pagination: PaginationMeta
  stats: AuditStats
}

export interface AuditFilters {
  page: number
  per_page: number
  search?: string
  module?: string
  action?: string
}

export interface PermissionRole {
  id: number
  slug: string
  name: string
  is_system: boolean
  users_count: number
  permissions_count: number
}

export interface PermissionItem {
  id: number
  permission_key: string
  module: string
  action: string
  description: string | null
  roles: string[]
}

export interface AdminAssignment {
  id: number
  name: string
  email: string
  base_role: string
  assigned_roles: string[]
  assigned_role_ids: number[]
}

export interface PermissionsStats {
  roles_total: number
  permissions_total: number
  admins_total: number
  assignments_total: number
}

export interface PermissionsData {
  roles: PermissionRole[]
  permissions: PermissionItem[]
  admins: AdminAssignment[]
  stats: PermissionsStats
}

export interface AssignRolePayload {
  user_id: number
  role_id: number
}

export interface CompanyOption {
  id: number
  name: string
  slug: string
}

export interface FeatureFlagItem {
  id: number
  flag_key: string
  name: string
  description: string | null
  default_enabled: boolean
  is_active: boolean
  company_enabled: boolean
  customized: boolean
  updated_at: string
}

export interface FeatureFlagStats {
  total: number
  active: number
  enabled_for_company: number
  customized: number
}

export interface FeatureFlagsData {
  items: FeatureFlagItem[]
  companies: CompanyOption[]
  selected_company_id: number
  pagination: PaginationMeta
  stats: FeatureFlagStats
}

export interface FeatureFlagFilters {
  page: number
  per_page: number
  company_id: number
  search?: string
  active?: string
}

export interface ToggleFeatureFlagPayload {
  company_id: number
  feature_flag_id: number
  enabled: boolean
}
