import type { QueryClient } from '@tanstack/react-query'
import { get } from '@/js/lib/api'
import { queryKeyFactory } from '@/js/lib/queryKeyFactory'
import { STALE_TIMES } from '@/js/lib/constants'
import type { DashboardApiResponse, DashboardData } from '@/js/types/dashboard'
import type {
  ApiEnvelope,
  OrderItem,
  OrdersStats,
  PaginatedData,
  UserItem,
  UsersStats,
} from '@/js/types/phase3'

function unwrap<T>(response: ApiEnvelope<T> | T): T {
  if (response && typeof response === 'object' && 'data' in response) {
    return (response as ApiEnvelope<T>).data
  }
  return response as T
}

function withTimeout<T>(promise: Promise<T>, timeoutMs: number): Promise<T> {
  return new Promise((resolve, reject) => {
    const timer = setTimeout(() => {
      reject(new Error('prefetch-timeout'))
    }, timeoutMs)

    promise
      .then((value) => {
        clearTimeout(timer)
        resolve(value)
      })
      .catch((error) => {
        clearTimeout(timer)
        reject(error)
      })
  })
}

/**
 * Warm up tenant critical queries before redirecting to tenant routes.
 * - Awaits dashboard prefetch for perceived instant load on landing page.
 * - Starts orders/users prefetch in background.
 */
export async function prefetchTenantBootstrap(
  queryClient: QueryClient,
  tenantId: number,
): Promise<void> {
  const scope = 'tenant' as const
  const context = { tenantId }

  const dashboardPrefetch = queryClient.prefetchQuery({
    queryKey: queryKeyFactory.dashboard.metrics(scope, context),
    queryFn: async ({ signal }) => {
      const response = await get<DashboardApiResponse | DashboardData>(
        '/dashboard',
        undefined,
        { signal, timeout: 8000 },
      )

      if (response && typeof response === 'object' && 'data' in response && response.data) {
        return response.data
      }

      return response as DashboardData
    },
    staleTime: STALE_TIMES.DASHBOARD,
  })

  // Do not block UX too long; 800ms cap keeps switch responsive.
  await withTimeout(dashboardPrefetch, 800).catch(() => undefined)

  void queryClient.prefetchQuery({
    queryKey: queryKeyFactory.orders.list(
      scope,
      { page: 1, per_page: 20 },
      context,
    ),
    queryFn: async () => {
      const response = await get<
        ApiEnvelope<PaginatedData<OrderItem, OrdersStats>> | PaginatedData<OrderItem, OrdersStats>
      >('/orders', { page: 1, per_page: 20 })
      return unwrap(response)
    },
    staleTime: STALE_TIMES.ORDERS,
  })

  void queryClient.prefetchQuery({
    queryKey: queryKeyFactory.users.list(
      scope,
      { page: 1, per_page: 20 },
      context,
    ),
    queryFn: async () => {
      const response = await get<
        ApiEnvelope<PaginatedData<UserItem, UsersStats>> | PaginatedData<UserItem, UsersStats>
      >('/users', { page: 1, per_page: 20 })
      return unwrap(response)
    },
    staleTime: STALE_TIMES.USERS,
  })
}