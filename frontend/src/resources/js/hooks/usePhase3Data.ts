import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { del, get, post } from '@/js/lib/api'
import { STALE_TIMES } from '@/js/lib/constants'
import { invalidateResourceQueries, queryKeyFactory } from '@/js/lib/queryKeyFactory'
import { useTenant } from '@/js/contexts/TenantContext'
import type {
  ApiEnvelope,
  LogItem,
  LogsStats,
  OrderItem,
  OrdersStats,
  PaginatedData,
  StoreItem,
  StoresStats,
  UserItem,
  UserDeletePayload,
  UserPasswordPayload,
  UserUpsertPayload,
  UsersStats,
} from '@/js/types/phase3'

export interface ListFilters {
  page: number
  per_page: number
  search?: string
  company_id?: number
  status?: string
  level?: string
  module?: string
  date_from?: string
  date_to?: string
  role?: string
  active?: string
  plan?: string
  region?: string
}

function toParams(filters: ListFilters): Record<string, unknown> {
  return { ...filters }
}

function unwrap<T>(response: ApiEnvelope<T> | T): T {
  if (response && typeof response === 'object' && 'data' in response) {
    return (response as ApiEnvelope<T>).data
  }
  return response as T
}

function applyTenantCompanyScope(
  filters: ListFilters,
  selectedTenantId: number | null,
  mode: string,
): ListFilters {
  if (mode !== 'tenant' || !selectedTenantId) {
    return filters
  }

  return {
    ...filters,
    company_id: selectedTenantId,
  }
}

/**
 * useStoresData - Fetch stores with tenant-scoped query key
 * Automatically scoped: platform vs tenant:ID
 */
export function useStoresData(filters: ListFilters) {
  const { selectedTenantId, mode } = useTenant()
  const scope = mode === 'platform' ? 'platform' : 'tenant'
  const scopedFilters = applyTenantCompanyScope(filters, selectedTenantId, mode)

  return useQuery({
    queryKey: queryKeyFactory.stores.list(scope as any, scopedFilters, {
      tenantId: selectedTenantId ?? undefined,
    }),
    queryFn: async (): Promise<PaginatedData<StoreItem, StoresStats>> => {
      const response = await get<ApiEnvelope<PaginatedData<StoreItem, StoresStats>> | PaginatedData<StoreItem, StoresStats>>('/stores', toParams(scopedFilters))
      return unwrap(response)
    },
    placeholderData: (previousData) => previousData,
    staleTime: STALE_TIMES.STORES,
  })
}

/**
 * useOrdersData - Fetch orders with tenant-scoped query key
 * Automatically scoped: platform vs tenant:ID
 */
export function useOrdersData(filters: ListFilters) {
  const { selectedTenantId, mode } = useTenant()
  const scope = mode === 'platform' ? 'platform' : 'tenant'
  const scopedFilters = applyTenantCompanyScope(filters, selectedTenantId, mode)

  return useQuery({
    queryKey: queryKeyFactory.orders.list(scope as any, scopedFilters, {
      tenantId: selectedTenantId ?? undefined,
    }),
    queryFn: async (): Promise<PaginatedData<OrderItem, OrdersStats>> => {
      const response = await get<ApiEnvelope<PaginatedData<OrderItem, OrdersStats>> | PaginatedData<OrderItem, OrdersStats>>('/orders', toParams(scopedFilters))
      return unwrap(response)
    },
    placeholderData: (previousData) => previousData,
    staleTime: STALE_TIMES.ORDERS,
    refetchInterval: () => {
      if (typeof document !== 'undefined' && document.visibilityState !== 'visible') {
        return false
      }
      return STALE_TIMES.ORDERS
    },
  })
}

/**
 * useUsersData - Fetch users with tenant-scoped query key
 * Automatically scoped: platform vs tenant:ID
 */
export function useUsersData(filters: ListFilters) {
  const { selectedTenantId, mode } = useTenant()
  const scope = mode === 'platform' ? 'platform' : 'tenant'
  const scopedFilters = applyTenantCompanyScope(filters, selectedTenantId, mode)

  return useQuery({
    queryKey: queryKeyFactory.users.list(scope as any, scopedFilters, {
      tenantId: selectedTenantId ?? undefined,
    }),
    queryFn: async (): Promise<PaginatedData<UserItem, UsersStats>> => {
      const response = await get<ApiEnvelope<PaginatedData<UserItem, UsersStats>> | PaginatedData<UserItem, UsersStats>>('/users', toParams(scopedFilters))
      return unwrap(response)
    },
    placeholderData: (previousData) => previousData,
    staleTime: STALE_TIMES.USERS,
  })
}

function invalidateUserManagementQueries(queryClient: ReturnType<typeof useQueryClient>) {
  queryClient.invalidateQueries({ predicate: invalidateResourceQueries('users') })
  queryClient.invalidateQueries({ predicate: invalidateResourceQueries('stores') })
}

export function useCreateUserMutation() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (payload: UserUpsertPayload) => post<ApiEnvelope<{ message: string }>>('/users/create', payload),
    onSuccess: (response) => {
      toast.success(response.message ?? 'Usuário criado com sucesso')
      invalidateUserManagementQueries(queryClient)
    },
    onError: (error: { response?: { data?: { message?: string } } }) => {
      toast.error(error.response?.data?.message ?? 'Falha ao criar usuário')
    },
  })
}

export function useUpdateUserMutation() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (payload: UserUpsertPayload & { user_id: number }) => post<ApiEnvelope<{ message: string }>>('/users/update', payload),
    onSuccess: (response) => {
      toast.success(response.message ?? 'Usuário atualizado com sucesso')
      invalidateUserManagementQueries(queryClient)
    },
    onError: (error: { response?: { data?: { message?: string } } }) => {
      toast.error(error.response?.data?.message ?? 'Falha ao atualizar usuário')
    },
  })
}

export function useChangeUserPasswordMutation() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (payload: UserPasswordPayload) => post<ApiEnvelope<{ message: string }>>('/users/change-password', payload),
    onSuccess: (response) => {
      toast.success(response.message ?? 'Senha alterada com sucesso')
      invalidateUserManagementQueries(queryClient)
    },
    onError: (error: { response?: { data?: { message?: string } } }) => {
      toast.error(error.response?.data?.message ?? 'Falha ao alterar senha')
    },
  })
}

export function useDeleteUserMutation() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (payload: UserDeletePayload) => del<ApiEnvelope<{ message: string }>>('/users', payload),
    onSuccess: (response) => {
      toast.success(response.message ?? 'Usuário excluído com sucesso')
      invalidateUserManagementQueries(queryClient)
    },
    onError: (error: { response?: { data?: { message?: string } } }) => {
      toast.error(error.response?.data?.message ?? 'Falha ao excluir usuário')
    },
  })
}

/**
 * useLogsData - Fetch logs with tenant-scoped query key
 * Automatically scoped: platform vs tenant:ID
 */
export function useLogsData(filters: ListFilters) {
  const { selectedTenantId, mode } = useTenant()
  const scope = mode === 'platform' ? 'platform' : 'tenant'
  const scopedFilters = applyTenantCompanyScope(filters, selectedTenantId, mode)

  return useQuery({
    queryKey: queryKeyFactory.audit.list(scope as any, scopedFilters, {
      tenantId: selectedTenantId ?? undefined,
    }),
    queryFn: async (): Promise<PaginatedData<LogItem, LogsStats>> => {
      const response = await get<ApiEnvelope<PaginatedData<LogItem, LogsStats>> | PaginatedData<LogItem, LogsStats>>('/logs', toParams(scopedFilters))
      return unwrap(response)
    },
    placeholderData: (previousData) => previousData,
    staleTime: STALE_TIMES.LOGS,
    refetchInterval: STALE_TIMES.LOGS,
  })
}
