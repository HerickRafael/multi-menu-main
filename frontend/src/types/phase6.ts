export interface ApiEnvelope<T> {
  success: boolean
  data: T
  message?: string
}

export interface AnalyticsKpis {
  orders_total: number
  revenue_total: number
  average_ticket: number
  stores_total: number
  active_users: number
}

export interface RevenuePoint {
  date: string
  orders: number
  revenue: number
}

export interface TopStorePoint {
  id: number
  name: string
  orders: number
  revenue: number
}

export interface StatusPoint {
  status: string
  total: number
}

export interface AnalyticsData {
  kpis: AnalyticsKpis
  revenue_by_day: RevenuePoint[]
  top_stores: TopStorePoint[]
  status_breakdown: StatusPoint[]
  updated_at: string
}

export interface SettingsGeneral {
  app_name: string
  env: string
  base_url: string
  timezone: string
  debug: boolean
}

export interface SettingsSecurity {
  session_name: string
  csrf_ttl: number
  session_lifetime_seconds: number
  login_required: boolean
}

export interface SettingsFeatures {
  novidades_days: number
  kds_refresh_ms: number
  kds_sla_minutes: number
}

export interface SettingsIntegrations {
  redis_enabled: boolean
  redis_host: string
  redis_port: number
  vapid_subject: string
}

export interface SettingsData {
  general: SettingsGeneral
  security: SettingsSecurity
  features: SettingsFeatures
  integrations: SettingsIntegrations
}

export interface HealthSummary {
  total: number
  ok: number
  warning: number
  critical: number
}

export interface HealthCheckItem {
  component: string
  status: string
  message: string
  checked_at?: string
  metadata_json?: string | null
}

export interface SystemRuntime {
  php_version: string
  app_env: string
  timezone: string
  memory_usage_mb: number
  disk_free_gb: number
  disk_total_gb: number
  logs_writable: boolean
}

export interface SystemData {
  summary: HealthSummary
  checks: HealthCheckItem[]
  runtime: SystemRuntime
  updated_at: string
}
