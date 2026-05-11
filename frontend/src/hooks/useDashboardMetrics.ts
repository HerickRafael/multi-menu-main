import { useQuery } from '@tanstack/react-query'
import { get } from '@/lib/api'
import { STALE_TIMES } from '@/lib/constants'
import { queryKeyFactory } from '@/lib/queryKeyFactory'
import { useTenant } from '@/contexts/TenantContext'
import type { DashboardApiResponse, DashboardData } from '@/types/dashboard'

const FALLBACK_DASHBOARD_DATA: DashboardData = {
  kpis: {
    stores_online: 0,
    stores_total: 0,
    orders_active: 0,
    users_total: 0,
  },
  system: {
    cpu_percent: 0,
    ram_mb: 0,
    websocket_clients: 0,
    workers_online: 0,
  },
  orders_per_hour: [],
  recent_events: [],
  updated_at: new Date(0).toISOString(),
}

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
