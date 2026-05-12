import { useQuery } from '@tanstack/react-query'
import { get } from '@/js/lib/api'
import { STALE_TIMES } from '@/js/lib/constants'
import { queryKeyFactory } from '@/js/lib/queryKeyFactory'
import { useTenant } from '@/js/contexts/TenantContext'
import type { DashboardApiResponse, DashboardData } from '@/js/types/dashboard'

export function useDashboardMetrics() {
  const { selectedTenantId, mode } = useTenant()
  const scope = mode === 'platform' ? 'platform' : 'tenant'
  const companyId = mode === 'tenant' && selectedTenantId ? selectedTenantId : undefined

  return useQuery({
    queryKey: queryKeyFactory.dashboard.metrics(scope as any, {
      tenantId: selectedTenantId ?? undefined,
    }),
    queryFn: async ({ signal }): Promise<DashboardData> => {
      const response = await get<DashboardApiResponse | DashboardData>(
        '/dashboard',
        companyId ? { company_id: companyId } : undefined,
        { signal, timeout: 8000 },
      )

      // Supports both envelope ({ success, data }) and direct payload.
      if ('data' in response && response.data) {
        return response.data
      }

      return response as DashboardData
    },
    retry: 0,
    staleTime: STALE_TIMES.DASHBOARD,
    placeholderData: (previousData) => previousData,
    refetchInterval: () => {
      if (typeof document !== 'undefined' && document.visibilityState !== 'visible') {
        return false
      }

      return STALE_TIMES.DASHBOARD
    },
    notifyOnChangeProps: ['data', 'error', 'isLoading', 'isFetching'],
  })
}
