import { useCallback, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQueryClient } from '@tanstack/react-query'
import { post } from '@/lib/api'
import { useTenant } from '@/contexts/TenantContext'
import { prefetchTenantBootstrap } from '@/lib/tenantPrefetch'

interface TenantSwitchResponse {
  tenant_id: number
  tenant_slug: string
  tenant_name: string
  tenant_logo?: string
  permissions?: string[]
  features?: string[]
}

interface TenantSwitchEnvelope {
  success?: boolean
  data?: TenantSwitchResponse
  message?: string
}

let globalSwitchInFlight = false

function normalizeSwitchPayload(payload: TenantSwitchEnvelope | TenantSwitchResponse): TenantSwitchResponse {
  if ('data' in (payload as TenantSwitchEnvelope)) {
    const envelope = payload as TenantSwitchEnvelope
    if (envelope.success === false) {
      throw new Error(envelope.message || 'Falha ao trocar contexto do tenant')
    }

    if (!envelope.data) {
      throw new Error('Resposta inválida ao trocar contexto do tenant')
    }

    return envelope.data
  }

  return payload as TenantSwitchResponse
}

interface SwitchTenantOptions {
  navigateToTenantDashboard?: boolean
}

function isRetryableError(error: any): boolean {
  const status = error?.response?.status

  if (!status) {
    // Network/CORS/timeout errors usually have no HTTP status.
    return true
  }

  return status === 408 || status === 429 || status === 500 || status === 502 || status === 503 || status === 504
}

function mapSwitchErrorMessage(error: any): string {
  const status = error?.response?.status
  if (status === 404) return 'Endpoint de troca de contexto não encontrado no ambiente atual'
  if (status === 401) return 'Sessão expirada, faça login novamente'
  if (status === 403) return 'Você não tem permissão para trocar o contexto'
  if (status === 500) return 'Falha interna ao trocar o contexto do tenant'
  return error?.response?.data?.message || error?.message || 'Erro ao trocar contexto do tenant'
}

async function requestTenantSwitch(
  tenantId: number,
): Promise<TenantSwitchEnvelope | TenantSwitchResponse> {
  const body = {
    tenant_id: tenantId,
    company_id: tenantId,
  }

  const endpoints = ['/tenant-context/switch', '/api/superadmin/tenant-context/switch']
  let lastError: any

  for (const endpoint of endpoints) {
    for (let attempt = 1; attempt <= 2; attempt += 1) {
      try {
        return await post<TenantSwitchEnvelope | TenantSwitchResponse>(endpoint, body)
      } catch (error: any) {
        lastError = error

        const shouldRetry = attempt === 1 && isRetryableError(error)
        if (shouldRetry) {
          continue
        }

        // For non-404 errors, stop trying alternate endpoints immediately.
        if (error?.response?.status && error.response.status !== 404) {
          throw error
        }
      }
    }
  }

  throw lastError
}

/**
 * Centralized tenant context switch flow with concurrency lock.
 */
export function useTenantSwitch() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { switchTenant, setPermissions, setFeatures, setLoading, setError } = useTenant()
  const [isSwitching, setIsSwitching] = useState(false)

  const switchTenantContext = useCallback(async (
    tenantId: number,
    options: SwitchTenantOptions = {},
  ): Promise<TenantSwitchResponse> => {
    const { navigateToTenantDashboard = true } = options

    if (globalSwitchInFlight) {
      throw new Error('Troca de tenant já em andamento')
    }

    globalSwitchInFlight = true
    setIsSwitching(true)
    setLoading(true)

    try {
      const payload = await requestTenantSwitch(tenantId)

      const data = normalizeSwitchPayload(payload)

      if (!data.tenant_id || !data.tenant_slug) {
        throw new Error('Resposta inválida ao trocar contexto do tenant')
      }

      // Remove stale tenant cache before hydrating the next tenant context.
      queryClient.removeQueries({
        predicate: (query) => {
          const key = query.queryKey
          return Array.isArray(key) && typeof key[0] === 'string' && key[0].startsWith('tenant:')
        },
      })

      switchTenant(
        data.tenant_id,
        data.tenant_slug,
        data.tenant_name,
        data.tenant_logo,
      )

      setPermissions(data.permissions ?? [])
      setFeatures(data.features ?? [])
      setError(null)

      await prefetchTenantBootstrap(queryClient, data.tenant_id)

      if (navigateToTenantDashboard) {
        navigate(`/superadmin/tenant/${data.tenant_slug}/dashboard`)
      }

      return data
    } catch (error) {
      const message = mapSwitchErrorMessage(error)
      setError(message)
      throw error
    } finally {
      setLoading(false)
      setIsSwitching(false)
      globalSwitchInFlight = false
    }
  }, [navigate, queryClient, setError, setFeatures, setLoading, setPermissions, switchTenant])

  return {
    isSwitching,
    switchTenantContext,
  }
}
