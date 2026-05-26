import {
  LayoutDashboard,
  Activity,
  Store,
  ShoppingBag,
  Users,
  MessageSquare,
  ListFilter,
  ScrollText,
  ShieldCheck,
  Webhook,
  KeyRound,
  Flag,
  BarChart3,
  Settings,
  Server,
  type LucideIcon,
} from 'lucide-react'

export interface NavItem {
  title: string
  href: string
  icon: LucideIcon
  badge?: string
  badgeVariant?: 'default' | 'destructive' | 'warning' | 'success'
  description?: string
  permission?: string
  children?: NavItem[]
}

export interface NavGroup {
  label: string
  items: NavItem[]
}

export const PLATFORM_NAV_GROUPS: NavGroup[] = [
  {
    label: 'Tenant Management',
    items: [
      {
        title: 'Lojas',
        href: '/superadmin/platform/stores',
        icon: Store,
        description: 'Gestão de tenants',
        permission: 'stores.view',
      },
      {
        title: 'Monitoramento',
        href: '/superadmin/platform/monitoring',
        icon: Activity,
        description: 'Status em tempo real',
        permission: 'monitoring.view',
      },
    ],
  },
  {
    label: 'Platform Config',
    items: [
      {
        title: 'Logs',
        href: '/superadmin/platform/logs',
        icon: ScrollText,
        description: 'Logs do sistema',
        permission: 'logs.view',
      },
      {
        title: 'Auditoria',
        href: '/superadmin/platform/audit',
        icon: ShieldCheck,
        description: 'Trilha de auditoria',
        permission: 'audit.view',
      },
      {
        title: 'Webhooks',
        href: '/superadmin/platform/webhooks',
        icon: Webhook,
        description: 'Endpoints e entregas',
        permission: 'webhooks.view',
      },
      {
        title: 'Permissões',
        href: '/superadmin/platform/permissions',
        icon: KeyRound,
        description: 'RBAC e papéis',
        permission: 'permissions.view',
      },
      {
        title: 'Feature Flags',
        href: '/superadmin/platform/feature-flags',
        icon: Flag,
        description: 'Flags por tenant',
        permission: 'feature_flags.view',
      },
      {
        title: 'Analytics',
        href: '/superadmin/platform/analytics',
        icon: BarChart3,
        description: 'Relatórios e métricas',
        permission: 'analytics.view',
      },
      {
        title: 'Configurações',
        href: '/superadmin/platform/settings',
        icon: Settings,
        description: 'Configurações globais',
        permission: 'settings.view',
      },
      {
        title: 'Sistema',
        href: '/superadmin/platform/system',
        icon: Server,
        description: 'Health checks e recursos',
        permission: 'system.view',
      },
    ],
  },
]

export const TENANT_NAV_GROUPS: NavGroup[] = [
  {
    label: 'Dashboard',
    items: [
      {
        title: 'Dashboard',
        href: '/superadmin/tenant/:slug/dashboard',
        icon: LayoutDashboard,
        description: 'Métricas do tenant',
      },
    ],
  },
  {
    label: 'Operação',
    items: [
      {
        title: 'Pedidos',
        href: '/superadmin/tenant/:slug/orders',
        icon: ShoppingBag,
        description: 'Monitor de pedidos',
        permission: 'orders.view',
      },
      {
        title: 'Usuários',
        href: '/superadmin/tenant/:slug/users',
        icon: Users,
        description: 'Gestão de usuários',
        permission: 'users.view',
      },
      {
        title: 'WhatsApp',
        href: '/superadmin/tenant/:slug/whatsapp',
        icon: MessageSquare,
        description: 'Status das instâncias',
        permission: 'whatsapp.view',
      },
      {
        title: 'Filas',
        href: '/superadmin/tenant/:slug/queues',
        icon: ListFilter,
        description: 'Jobs e workers',
        permission: 'queues.view',
      },
    ],
  },
]

/**
 * @deprecated Use PLATFORM_NAV_GROUPS or TENANT_NAV_GROUPS instead
 * Kept for backward compatibility
 */
export const NAV_GROUPS: NavGroup[] = [
  {
    label: 'Visão Geral',
    items: [
      {
        title: 'Dashboard',
        href: '/superadmin/dashboard',
        icon: LayoutDashboard,
        description: 'Métricas globais da plataforma',
      },
      {
        title: 'Monitoramento',
        href: '/superadmin/monitoring',
        icon: Activity,
        description: 'Status em tempo real',
        permission: 'monitoring.view',
      },
    ],
  },
  {
    label: 'Operação',
    items: [
      {
        title: 'Lojas',
        href: '/superadmin/stores',
        icon: Store,
        description: 'Gestão de tenants',
        permission: 'stores.view',
      },
      {
        title: 'Pedidos',
        href: '/superadmin/orders',
        icon: ShoppingBag,
        description: 'Monitor de pedidos',
        permission: 'orders.view',
      },
      {
        title: 'Usuários',
        href: '/superadmin/users',
        icon: Users,
        description: 'Gestão de usuários',
        permission: 'users.view',
      },
      {
        title: 'WhatsApp',
        href: '/superadmin/whatsapp',
        icon: MessageSquare,
        description: 'Status das instâncias',
        permission: 'whatsapp.view',
      },
    ],
  },
  {
    label: 'Infraestrutura',
    items: [
      {
        title: 'Filas',
        href: '/superadmin/queues',
        icon: ListFilter,
        description: 'Jobs e workers',
        permission: 'queues.view',
      },
      {
        title: 'Logs',
        href: '/superadmin/logs',
        icon: ScrollText,
        description: 'Logs do sistema',
        permission: 'logs.view',
      },
      {
        title: 'Webhooks',
        href: '/superadmin/webhooks',
        icon: Webhook,
        description: 'Endpoints e entregas',
        permission: 'webhooks.view',
      },
      {
        title: 'Sistema',
        href: '/superadmin/system',
        icon: Server,
        description: 'Health checks e recursos',
        permission: 'system.view',
      },
    ],
  },
  {
    label: 'Segurança & Config',
    items: [
      {
        title: 'Auditoria',
        href: '/superadmin/audit',
        icon: ShieldCheck,
        description: 'Trilha de auditoria',
        permission: 'audit.view',
      },
      {
        title: 'Permissões',
        href: '/superadmin/permissions',
        icon: KeyRound,
        description: 'RBAC e papéis',
        permission: 'permissions.view',
      },
      {
        title: 'Feature Flags',
        href: '/superadmin/feature-flags',
        icon: Flag,
        description: 'Flags por tenant',
        permission: 'feature_flags.view',
      },
      {
        title: 'Analytics',
        href: '/superadmin/analytics',
        icon: BarChart3,
        description: 'Relatórios e métricas',
        permission: 'analytics.view',
      },
      {
        title: 'Configurações',
        href: '/superadmin/settings',
        icon: Settings,
        description: 'Configurações globais',
        permission: 'settings.view',
      },
    ],
  },
]

export const PERMISSIONS = {
  MONITORING_VIEW: 'monitoring.view',
  STORES_VIEW: 'stores.view',
  STORES_MANAGE: 'stores.manage',
  ORDERS_VIEW: 'orders.view',
  USERS_VIEW: 'users.view',
  USERS_MANAGE: 'users.manage',
  WHATSAPP_VIEW: 'whatsapp.view',
  QUEUES_VIEW: 'queues.view',
  QUEUES_MANAGE: 'queues.manage',
  LOGS_VIEW: 'logs.view',
  WEBHOOKS_VIEW: 'webhooks.view',
  WEBHOOKS_MANAGE: 'webhooks.manage',
  SYSTEM_VIEW: 'system.view',
  AUDIT_VIEW: 'audit.view',
  PERMISSIONS_VIEW: 'permissions.view',
  PERMISSIONS_MANAGE: 'permissions.manage',
  FEATURE_FLAGS_VIEW: 'feature_flags.view',
  FEATURE_FLAGS_MANAGE: 'feature_flags.manage',
  ANALYTICS_VIEW: 'analytics.view',
  SETTINGS_VIEW: 'settings.view',
  SETTINGS_MANAGE: 'settings.manage',
  IMPERSONATE: 'impersonate',
} as const

export const QUERY_KEYS = {
  DASHBOARD: 'dashboard',
  STORES: 'stores',
  ORDERS: 'orders',
  USERS: 'users',
  LOGS: 'logs',
  AUDIT: 'audit',
  WEBHOOKS: 'webhooks',
  QUEUES: 'queues',
  WHATSAPP: 'whatsapp',
  FEATURE_FLAGS: 'feature-flags',
  PERMISSIONS: 'permissions',
  ANALYTICS: 'analytics',
  SYSTEM: 'system',
  SETTINGS: 'settings',
  MONITORING: 'monitoring',
} as const

export const DEFAULT_PAGE_SIZE = 25
export const MAX_PAGE_SIZE = 100
export const DEBOUNCE_MS = 400
export const STALE_TIME_MS = 45_000        // 45s
export const REFETCH_INTERVAL_MS = 60_000  // 60s for dashboard

export const STALE_TIMES = {
  DASHBOARD: 120_000,     // 2min
  STORES: 180_000,        // 3min
  ORDERS: 120_000,        // 2min
  USERS: 180_000,         // 3min
  MONITORING: 60_000,     // 1min
  WHATSAPP: 120_000,      // 2min
  QUEUES: 120_000,        // 2min
  WEBHOOKS: 180_000,      // 3min
  LOGS: 120_000,          // 2min
  AUDIT: 300_000,         // 5min
  PERMISSIONS: 600_000,   // 10min
  FEATURE_FLAGS: 600_000, // 10min
  ANALYTICS: 300_000,     // 5min
  SETTINGS: 600_000,      // 10min
  SYSTEM: 60_000,         // 1min
} as const
