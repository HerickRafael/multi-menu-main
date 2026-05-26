import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { get, post } from '@/js/lib/api'
import { STALE_TIMES } from '@/js/lib/constants'
import { queryKeyFactory } from '@/js/lib/queryKeyFactory'
import type { AnalyticsData, ApiEnvelope, SettingsData, SystemData } from '@/js/types/phase6'

function unwrap<T>(response: ApiEnvelope<T> | T): T {
  if (response && typeof response === 'object' && 'data' in response) {
    return (response as ApiEnvelope<T>).data
  }

  return response as T
}

/**
 * useAnalyticsData - Fetch analytics (platform-scoped, global)
 */
export function useAnalyticsData() {
  return useQuery({
    queryKey: queryKeyFactory.custom('analytics', 'platform'),
    queryFn: async (): Promise<AnalyticsData> => {
      const response = await get<ApiEnvelope<AnalyticsData> | AnalyticsData>('/analytics')
      return unwrap(response)
    },
    staleTime: STALE_TIMES.ANALYTICS,
  })
}

/**
 * useSettingsData - Fetch settings (platform-scoped, global)
 */
export function useSettingsData() {
  return useQuery({
    queryKey: queryKeyFactory.custom('settings', 'platform'),
    queryFn: async (): Promise<SettingsData> => {
      const response = await get<ApiEnvelope<SettingsData> | SettingsData>('/settings')
      return unwrap(response)
    },
    staleTime: STALE_TIMES.SETTINGS,
  })
}

/**
 * useSystemData - Fetch system health (platform-scoped, global)
 */
export function useSystemData() {
  return useQuery({
    queryKey: queryKeyFactory.custom('system', 'platform'),
    queryFn: async (): Promise<SystemData> => {
      const response = await get<ApiEnvelope<SystemData> | SystemData>('/system')
      return unwrap(response)
    },
    staleTime: STALE_TIMES.SYSTEM,
    refetchInterval: () => {
      if (typeof document !== 'undefined' && document.visibilityState !== 'visible') {
        return false
      }
      return STALE_TIMES.SYSTEM
    },
  })
}

export function useRunSystemChecksMutation() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: () => post<ApiEnvelope<{ message: string }>>('/system/run-checks'),
    onSuccess: (response) => {
      toast.success(response.message ?? 'Health checks executados com sucesso')
      queryClient.invalidateQueries({ queryKey: queryKeyFactory.custom('system', 'platform') })
    },
    onError: (error: { response?: { data?: { message?: string } } }) => {
      toast.error(error.response?.data?.message ?? 'Falha ao executar health checks')
    },
  })
}
