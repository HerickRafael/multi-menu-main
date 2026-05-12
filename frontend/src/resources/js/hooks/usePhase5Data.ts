import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { get, post } from '@/js/lib/api'
import { STALE_TIMES } from '@/js/lib/constants'
import { queryKey, queryKeyFactory } from '@/js/lib/queryKeyFactory'
import { useTenant } from '@/js/contexts/TenantContext'
import type {
  ApiEnvelope,
  AssignRolePayload,
  AuditData,
  AuditFilters,
  FeatureFlagFilters,
  FeatureFlagsData,
  PermissionsData,
  ToggleFeatureFlagPayload,
} from '@/js/types/phase5'

function unwrap<T>(response: ApiEnvelope<T> | T): T {
  if (response && typeof response === 'object' && 'data' in response) {
    return (response as ApiEnvelope<T>).data
  }

  return response as T
}

function toParams(filters: AuditFilters | FeatureFlagFilters): Record<string, unknown> {
  return { ...filters }
}

/**
 * useAuditData - Fetch audit logs with tenant-scoped query key
 * Automatically scoped: platform vs tenant:ID
 */
export function useAuditData(filters: AuditFilters) {
  const { selectedTenantId, mode } = useTenant()
  const scope = mode === 'platform' ? 'platform' : 'tenant'

  return useQuery({
    queryKey: queryKeyFactory.audit.list(scope as any, filters, {
      tenantId: selectedTenantId ?? undefined,
    }),
    queryFn: async (): Promise<AuditData> => {
      const response = await get<ApiEnvelope<AuditData> | AuditData>('/audit', toParams(filters))
      return unwrap(response)
    },
    placeholderData: (previousData) => previousData,
    staleTime: STALE_TIMES.AUDIT,
  })
}

/**
 * usePermissionsData - Fetch permissions (platform-scoped, global)
 */
export function usePermissionsData() {
  return useQuery({
    queryKey: queryKeyFactory.custom('permissions', 'platform'),
    queryFn: async (): Promise<PermissionsData> => {
      const response = await get<ApiEnvelope<PermissionsData> | PermissionsData>('/permissions')
      return unwrap(response)
    },
    staleTime: STALE_TIMES.PERMISSIONS,
  })
}

export function useAssignRoleMutation() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (payload: AssignRolePayload) => post<ApiEnvelope<{ message: string }>>('/permissions/assign-role', payload),
    onSuccess: (response) => {
      toast.success(response.message ?? 'Role atribuída com sucesso')
      queryClient.invalidateQueries({ queryKey: queryKeyFactory.custom('permissions', 'platform') })
    },
    onError: (error: { response?: { data?: { message?: string } } }) => {
      toast.error(error.response?.data?.message ?? 'Falha ao atribuir role')
    },
  })
}

/**
 * useFeatureFlagsData - Fetch feature flags with tenant-scoped query key
 */
export function useFeatureFlagsData(filters: FeatureFlagFilters) {
  const { selectedTenantId, mode } = useTenant()
  const scope = mode === 'platform' ? 'platform' : 'tenant'

  return useQuery({
    queryKey: queryKey(scope as any, 'feature_flags', filters, {
      tenantId: selectedTenantId ?? undefined,
    }),
    queryFn: async (): Promise<FeatureFlagsData> => {
      const response = await get<ApiEnvelope<FeatureFlagsData> | FeatureFlagsData>('/feature-flags', toParams(filters))
      return unwrap(response)
    },
    placeholderData: (previousData) => previousData,
    staleTime: STALE_TIMES.FEATURE_FLAGS,
  })
}

export function useToggleFeatureFlagMutation() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (payload: ToggleFeatureFlagPayload) => post<ApiEnvelope<{ message: string }>>('/feature-flags/toggle', payload),
    onSuccess: (response) => {
      toast.success(response.message ?? 'Flag atualizada com sucesso')
      // Invalidate all feature flags queries
      queryClient.invalidateQueries({
        predicate: (query) => {
          const key = query.queryKey
          return Array.isArray(key) && String(key[1]).includes('feature_flags')
        },
      })
    },
    onError: (error: { response?: { data?: { message?: string } } }) => {
      toast.error(error.response?.data?.message ?? 'Falha ao atualizar flag')
    },
  })
}
