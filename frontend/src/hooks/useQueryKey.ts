import { useMemo } from 'react'
import { useTenant, useTenantScope } from '@/contexts/TenantContext'
import { queryKey, type QueryScope } from '@/lib/queryKeyFactory'

/**
 * Hook to generate scoped query keys automatically based on tenant context
 * 
 * Automatically includes tenantId from context, so callers don't need to pass it
 */
export function useQueryKey() {
  const { selectedTenantId } = useTenant()
  const scope = useTenantScope() as QueryScope

  return useMemo(
    () => ({
      /**
       * Generate a query key with automatic tenant context
       * @param resource - Resource name
       * @param filters - Optional filters
       * @returns Scoped query key
       */
      key: (resource: string, filters?: Record<string, any>) =>
        queryKey(scope === 'platform' ? 'platform' : 'tenant', resource, filters, {
          tenantId: selectedTenantId ?? undefined,
        }),

      /**
       * Get platform-scoped key (ignores tenant context)
       */
      platformKey: (resource: string, filters?: Record<string, any>) =>
        queryKey('platform', resource, filters),

      /**
       * Get tenant-scoped key (requires tenantId)
       */
      tenantKey: (resource: string, filters?: Record<string, any>) =>
        queryKey('tenant', resource, filters, { tenantId: selectedTenantId ?? undefined }),

      /**
       * Get raw scope string for query key
       */
      scope,
    }),
    [scope, selectedTenantId],
  )
}

/**
 * Hook to get both scope and tenantId for conditional logic
 */
export function useQueryScope() {
  const { selectedTenantId, mode } = useTenant()

  return useMemo(
    () => ({
      isPlatform: mode === 'platform',
      isTenant: mode === 'tenant',
      tenantId: selectedTenantId,
      scopePrefix: mode === 'platform' ? 'platform' : `tenant:${selectedTenantId}`,
    }),
    [mode, selectedTenantId],
  )
}
