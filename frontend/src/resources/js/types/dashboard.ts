export interface DashboardKpis {
  stores_online: number
  stores_total: number
  orders_active: number
  users_total: number
}

export interface DashboardSystemMetrics {
  cpu_percent: number
  ram_mb: number
  websocket_clients: number
  workers_online: number
}

export interface OrdersPerHourPoint {
  hour: string
  orders: number
}

export interface RecentEvent {
  title: string
  at: string
}

export interface DashboardData {
  kpis: DashboardKpis
  system: DashboardSystemMetrics
  orders_per_hour: OrdersPerHourPoint[]
  recent_events: RecentEvent[]
  updated_at: string
}

export interface DashboardApiResponse {
  success: boolean
  data: DashboardData
}
