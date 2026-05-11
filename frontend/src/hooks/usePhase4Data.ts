/**
 * FASE 4 Data Hooks - Monitoring, Webhooks, Queues & WhatsApp
 */

import { useMutation, useQuery, useQueryClient, type UseQueryResult } from '@tanstack/react-query';
import { toast } from 'sonner';
import { get, post } from '@/lib/api';
import { STALE_TIMES } from '@/lib/constants';
import { queryKey, queryKeyFactory } from '@/lib/queryKeyFactory';
import { useTenant } from '@/contexts/TenantContext';
import type {
  ApiEnvelope,
  QueueData,
  QueueListFilters,
  MonitoringData,
  WebhookData,
  WebhookListFilters,
  WhatsAppData,
  WhatsAppFilters,
} from '@/types/phase4';

function unwrap<T>(response: ApiEnvelope<T> | T): T {
  if (response && typeof response === 'object' && 'data' in response) {
    return (response as ApiEnvelope<T>).data;
  }

  return response as T;
}

function resolveTenantCompanyId(
  mode: string,
  selectedTenantId: number | null,
  companyId?: number | string | null,
): number | string | null | undefined {
  if (mode !== 'tenant' || !selectedTenantId) {
    return companyId;
  }

  return selectedTenantId;
}

/**
 * Fetch monitoring metrics (CPU, RAM, DB connections, job queue)
 * Auto-refetch every 30s
 * Platform-scoped (global metrics only)
 */
export function useMonitoring(): UseQueryResult<MonitoringData, Error> {
  return useQuery({
    queryKey: queryKeyFactory.monitoring.all('platform'),
    queryFn: async () => {
      const response = await get<ApiEnvelope<MonitoringData> | MonitoringData>('/monitoring');
      return unwrap(response);
    },
    staleTime: STALE_TIMES.MONITORING,
    refetchInterval: STALE_TIMES.MONITORING,
    retry: 1,
  });
}

/**
 * Fetch webhooks list with filters and pagination
 * Tenant-scoped query key
 */
export function useWebhooks(filters: WebhookListFilters = {}): UseQueryResult<WebhookData, Error> {
  const { selectedTenantId, mode } = useTenant();
  const scope = mode === 'platform' ? 'platform' : 'tenant';
  
  const {
    page = 1,
    per_page = 15,
    status = '',
  } = filters;

  return useQuery({
    queryKey: queryKey(scope as any, 'webhooks', { page, per_page, status }, { tenantId: selectedTenantId ?? undefined }),
    queryFn: async () => {
      const response = await get<ApiEnvelope<WebhookData> | WebhookData>('/webhooks', {
        page,
        per_page,
        status,
      });

      return unwrap(response);
    },
    staleTime: STALE_TIMES.WEBHOOKS,
    retry: 1,
  });
}

/**
 * Fetch queue jobs with filters and pagination
 * Tenant-scoped query key
 */
export function useQueues(filters: QueueListFilters = {}): UseQueryResult<QueueData, Error> {
  const { selectedTenantId, mode } = useTenant();
  const scope = mode === 'platform' ? 'platform' : 'tenant';
  
  const {
    page = 1,
    per_page = 50,
    status = '',
    job_type = '',
    company_id,
  } = filters;

  const scopedCompanyId = resolveTenantCompanyId(mode, selectedTenantId, company_id ?? '');

  return useQuery({
    queryKey: queryKey(scope as any, 'queues', { page, per_page, status, job_type, company_id: scopedCompanyId }, { tenantId: selectedTenantId ?? undefined }),
    queryFn: async () => {
      const response = await get<ApiEnvelope<QueueData> | QueueData>('/queues', {
        page,
        per_page,
        status,
        job_type,
        company_id: scopedCompanyId,
      });

      return unwrap(response);
    },
    staleTime: STALE_TIMES.QUEUES,
    placeholderData: (previousData) => previousData,
    retry: 1,
  });
}

export function useRetryQueueJobMutation() {
  const queryClient = useQueryClient();
  const { selectedTenantId, mode } = useTenant();
  const scope = mode === 'platform' ? 'platform' : 'tenant';

  return useMutation({
    mutationFn: (jobId: number) => post<ApiEnvelope<{ message: string }>>(`/queues/${jobId}/retry`),
    onSuccess: (response) => {
      toast.success(response.message ?? 'Job reagendado com sucesso');
      // Invalidate queue queries for current scope
      queryClient.invalidateQueries({
        predicate: (query) => {
          const key = query.queryKey;
          return (
            Array.isArray(key) &&
            String(key[0]).startsWith(scope === 'platform' ? 'platform' : `tenant:${selectedTenantId}`)
          );
        },
      });
    },
    onError: (error: { response?: { data?: { message?: string } } }) => {
      toast.error(error.response?.data?.message ?? 'Falha ao reagendar job');
    },
  });
}

/**
 * Fetch WhatsApp instances with filters and pagination
 * Tenant-scoped query key
 */
export function useWhatsApp(filters: WhatsAppFilters = {}): UseQueryResult<WhatsAppData, Error> {
  const { selectedTenantId, mode } = useTenant();
  const scope = mode === 'platform' ? 'platform' : 'tenant';
  
  const {
    page = 1,
    per_page = 10,
    search = '',
    status = '',
    company_id,
  } = filters;

  const scopedCompanyId = resolveTenantCompanyId(mode, selectedTenantId, company_id);

  return useQuery({
    queryKey: queryKey(scope as any, 'whatsapp', { page, per_page, search, status, company_id: scopedCompanyId }, { tenantId: selectedTenantId ?? undefined }),
    queryFn: async () => {
      const response = await get<ApiEnvelope<WhatsAppData> | WhatsAppData>('/whatsapp', {
        page,
        per_page,
        search,
        status,
        company_id: scopedCompanyId,
      });

      return unwrap(response);
    },
    staleTime: STALE_TIMES.WHATSAPP,
    refetchInterval: STALE_TIMES.WHATSAPP,
    retry: 1,
  });
}
