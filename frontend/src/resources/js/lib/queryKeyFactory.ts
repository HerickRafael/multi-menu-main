/**
 * Query Key Factory for TanStack Query
 * 
 * Generates scoped query keys automatically based on tenant context.
 * Enables cache segregation: platform queries don't interfere with tenant queries.
 * 
 * Example usage:
 *   queryKey('platform', 'orders')  → ['platform:orders']
 *   queryKey('tenant', 'orders')    → ['tenant:123:orders'] (when tenant context exists)
 *   queryKey('tenant', 'orders', {status: 'pending'})  → ['tenant:123:orders', {status: 'pending'}]
 */

export type QueryScope = 'platform' | 'tenant' | 'store'

export interface QueryKeyFactoryOptions {
  scope?: QueryScope
  tenantId?: number
  storeId?: number
  resource: string
  filters?: Record<string, any>
}

/**
 * Build a scoped query key
 * 
 * @param scope - 'platform' for global, 'tenant' for company-scoped, 'store' for sub-scoped
 * @param resource - Resource name (e.g., 'orders', 'users', 'dashboard')
 * @param filters - Optional filters object
 * @param context - Optional context { tenantId?, storeId? }
 * @returns Query key array
 */
export function queryKey(
  scope: QueryScope,
  resource: string,
  filters?: Record<string, any>,
  context?: { tenantId?: number; storeId?: number },
): unknown[] {
  const key: unknown[] = []

  // Build scope prefix
  if (scope === 'platform') {
    key.push('platform')
  } else if (scope === 'tenant' && context?.tenantId) {
    key.push(`tenant:${context.tenantId}`)
  } else if (scope === 'store' && context?.tenantId && context?.storeId) {
    key.push(`store:${context.tenantId}:${context.storeId}`)
  } else {
    // Fallback to platform if scope cannot be built
    key.push('platform')
  }

  // Add resource
  key.push(resource)

  // Add filters if provided
  if (filters && Object.keys(filters).length > 0) {
    key.push(filters)
  }

  return key
}

/**
 * Query factory methods organized by domain
 * Each returns a query key with proper scoping
 */
export const queryKeyFactory = {
  // Orders domain
  orders: {
    all: (scope: QueryScope, context?: { tenantId?: number }) =>
      queryKey(scope, 'orders', undefined, context),
    list: (scope: QueryScope, filters?: Record<string, any>, context?: { tenantId?: number }) =>
      queryKey(scope, 'orders', filters, context),
    detail: (orderId: number | string, scope: QueryScope, context?: { tenantId?: number }) =>
      queryKey(scope, `orders:${orderId}`, undefined, context),
    stats: (scope: QueryScope, context?: { tenantId?: number }) =>
      queryKey(scope, 'orders:stats', undefined, context),
  },

  // Users domain
  users: {
    all: (scope: QueryScope, context?: { tenantId?: number }) =>
      queryKey(scope, 'users', undefined, context),
    list: (scope: QueryScope, filters?: Record<string, any>, context?: { tenantId?: number }) =>
      queryKey(scope, 'users', filters, context),
    detail: (userId: number | string, scope: QueryScope, context?: { tenantId?: number }) =>
      queryKey(scope, `users:${userId}`, undefined, context),
  },

  // WhatsApp domain
  whatsapp: {
    all: (scope: QueryScope, context?: { tenantId?: number }) =>
      queryKey(scope, 'whatsapp', undefined, context),
    list: (scope: QueryScope, filters?: Record<string, any>, context?: { tenantId?: number }) =>
      queryKey(scope, 'whatsapp', filters, context),
    queue: (scope: QueryScope, context?: { tenantId?: number }) =>
      queryKey(scope, 'whatsapp:queue', undefined, context),
    stats: (scope: QueryScope, context?: { tenantId?: number }) =>
      queryKey(scope, 'whatsapp:stats', undefined, context),
  },

  // Dashboard domain
  dashboard: {
    main: (scope: QueryScope, context?: { tenantId?: number }) =>
      queryKey(scope, 'dashboard:main', undefined, context),
    summary: (scope: QueryScope, filters?: Record<string, any>, context?: { tenantId?: number }) =>
      queryKey(scope, 'dashboard:summary', filters, context),
    metrics: (scope: QueryScope, context?: { tenantId?: number }) =>
      queryKey(scope, 'dashboard:metrics', undefined, context),
  },

  // Monitoring domain
  monitoring: {
    all: (scope: QueryScope, context?: { tenantId?: number }) =>
      queryKey(scope, 'monitoring', undefined, context),
    health: (scope: QueryScope, context?: { tenantId?: number }) =>
      queryKey(scope, 'monitoring:health', undefined, context),
    alerts: (scope: QueryScope, context?: { tenantId?: number }) =>
      queryKey(scope, 'monitoring:alerts', undefined, context),
  },

  // Stores domain
  stores: {
    all: (scope: QueryScope, context?: { tenantId?: number }) =>
      queryKey(scope, 'stores', undefined, context),
    list: (scope: QueryScope, filters?: Record<string, any>, context?: { tenantId?: number }) =>
      queryKey(scope, 'stores', filters, context),
    detail: (storeId: number | string, scope: QueryScope, context?: { tenantId?: number }) =>
      queryKey(scope, `stores:${storeId}`, undefined, context),
  },

  // Tenant switching and context
  tenant: {
    list: () => queryKey('platform', 'tenants:list'),
    context: (tenantId: number) =>
      queryKey('platform', `tenant:${tenantId}:context`),
    recent: () => queryKey('platform', 'tenants:recent'),
  },

  // Audit domain
  audit: {
    all: (scope: QueryScope, context?: { tenantId?: number }) =>
      queryKey(scope, 'audit', undefined, context),
    list: (scope: QueryScope, filters?: Record<string, any>, context?: { tenantId?: number }) =>
      queryKey(scope, 'audit', filters, context),
    contextSwitches: (scope: QueryScope, context?: { tenantId?: number }) =>
      queryKey(scope, 'audit:context-switches', undefined, context),
  },

  // Generic domain for custom queries
  custom: (resource: string, scope: QueryScope = 'platform', filters?: Record<string, any>) =>
    queryKey(scope, resource, filters),
}

/**
 * Predicate for invalidating all queries within a scope
 * Usage: queryClient.invalidateQueries({ predicate: invalidateScopeQueries('tenant:123') })
 */
export function invalidateScopeQueries(scope: string) {
  return (query: any) => {
    const queryKey = query.queryKey
    if (!Array.isArray(queryKey) || queryKey.length === 0) return false
    return String(queryKey[0]).startsWith(scope)
  }
}

/**
 * Predicate for invalidating specific resource within all scopes
 * Usage: queryClient.invalidateQueries({ predicate: invalidateResourceQueries('orders') })
 */
export function invalidateResourceQueries(resource: string) {
  return (query: any) => {
    const queryKey = query.queryKey
    if (!Array.isArray(queryKey) || queryKey.length < 2) return false
    return String(queryKey[1]).startsWith(resource)
  }
}

/**
 * Predicate for invalidating resource within a specific scope
 * Usage: queryClient.invalidateQueries({ 
 *   predicate: invalidateScopedResourceQueries('tenant:123', 'orders')
 * })
 */
export function invalidateScopedResourceQueries(scope: string, resource: string) {
  return (query: any) => {
    const queryKey = query.queryKey
    if (!Array.isArray(queryKey) || queryKey.length < 2) return false
    return (
      String(queryKey[0]).startsWith(scope) &&
      String(queryKey[1]).startsWith(resource)
    )
  }
}
