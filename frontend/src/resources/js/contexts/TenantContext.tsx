import React, { createContext, useContext, useMemo } from 'react'
import { create } from 'zustand'
import { persist, createJSONStorage } from 'zustand/middleware'
import { useAuthStore } from '@/js/stores/authStore'

/**
 * Tenant Context - Enterprise multi-tenant context for Super Admin
 * Manages tenant selection, mode (platform/tenant), permissions, and features
 */

export type TenantMode = 'platform' | 'tenant'

export interface TenantContextData {
  // Current tenant selection
  selectedTenantId: number | null
  selectedTenantSlug: string | null
  
  // Operation mode: 'platform' for global admin, 'tenant' for company-scoped
  mode: TenantMode
  
  // Tenant metadata (cached from backend)
  tenantName: string | null
  tenantLogo: string | null
  
  // Tenant capabilities and features
  permissions: string[]
  features: string[]
  
  // Last activity timestamp
  lastSwitchAt: number | null
  
  // Loading state
  isLoading: boolean
  error: string | null
}

interface TenantContextStore extends TenantContextData {
  // Actions
  switchTenant: (tenantId: number, slug: string, name?: string | null, logo?: string | null) => void
  setMode: (mode: TenantMode) => void
  setPermissions: (permissions: string[]) => void
  setFeatures: (features: string[]) => void
  setLoading: (loading: boolean) => void
  setError: (error: string | null) => void
  reset: () => void
}

// Zustand store for persistent tenant context state
const useTenantStore = create<TenantContextStore>()(
  persist(
    (set) => ({
      selectedTenantId: null,
      selectedTenantSlug: null,
      mode: 'platform',
      tenantName: null,
      tenantLogo: null,
      permissions: [],
      features: [],
      lastSwitchAt: null,
      isLoading: false,
      error: null,

      switchTenant: (tenantId, slug, name = null, logo = null) =>
        set({
          selectedTenantId: tenantId,
          selectedTenantSlug: slug,
          tenantName: name ?? null,
          tenantLogo: logo ?? null,
          mode: 'tenant',
          lastSwitchAt: Date.now(),
          permissions: [],
          features: [],
          error: null,
        }),

      setMode: (mode) => set({ mode }),

      setPermissions: (permissions) => set({ permissions }),

      setFeatures: (features) => set({ features }),

      setLoading: (loading) => set({ isLoading: loading }),

      setError: (error) => set({ error }),

      reset: () =>
        set({
          selectedTenantId: null,
          selectedTenantSlug: null,
          mode: 'platform',
          tenantName: null,
          tenantLogo: null,
          permissions: [],
          features: [],
          lastSwitchAt: null,
          isLoading: false,
          error: null,
        }),
    }),
    {
      name: 'super-admin-tenant-context',
      version: 1,
      storage: createJSONStorage(() => localStorage),
      partialize: (state) => ({
        selectedTenantId: state.selectedTenantId,
        selectedTenantSlug: state.selectedTenantSlug,
        mode: state.mode,
        tenantName: state.tenantName,
        tenantLogo: state.tenantLogo,
        lastSwitchAt: state.lastSwitchAt,
      }),
    },
  ),
)

// React Context for immediate updates (synced with Zustand store)
interface TenantContextValue extends TenantContextData {
  switchTenant: (tenantId: number, slug: string, name?: string, logo?: string) => void
  setMode: (mode: TenantMode) => void
  setPermissions: (permissions: string[]) => void
  setFeatures: (features: string[]) => void
  setLoading: (loading: boolean) => void
  setError: (error: string | null) => void
  reset: () => void
}

const TenantContext = createContext<TenantContextValue | undefined>(undefined)

/**
 * TenantContextProvider - Wraps application with tenant context
 * Should be placed inside AuthProvider, outside page routes
 */
export function TenantContextProvider({ children }: { children: React.ReactNode }) {
  const storeState = useTenantStore()
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated)

  React.useEffect(() => {
    if (!isAuthenticated) {
      useTenantStore.getState().reset()
    }
  }, [isAuthenticated])

  const value: TenantContextValue = useMemo(
    () => ({
      selectedTenantId: storeState.selectedTenantId,
      selectedTenantSlug: storeState.selectedTenantSlug,
      mode: storeState.mode,
      tenantName: storeState.tenantName,
      tenantLogo: storeState.tenantLogo,
      permissions: storeState.permissions,
      features: storeState.features,
      lastSwitchAt: storeState.lastSwitchAt,
      isLoading: storeState.isLoading,
      error: storeState.error,
      switchTenant: storeState.switchTenant,
      setMode: storeState.setMode,
      setPermissions: storeState.setPermissions,
      setFeatures: storeState.setFeatures,
      setLoading: storeState.setLoading,
      setError: storeState.setError,
      reset: storeState.reset,
    }),
    [storeState],
  )

  return <TenantContext.Provider value={value}>{children}</TenantContext.Provider>
}

/**
 * useTenant - Hook to access tenant context from anywhere in the app
 * Throws if used outside TenantContextProvider
 */
export function useTenant(): TenantContextValue {
  const context = useContext(TenantContext)
  if (context === undefined) {
    throw new Error('useTenant must be used within TenantContextProvider')
  }
  return context
}

/**
 * useTenantStore - Direct access to Zustand store for advanced usage
 * Used internally by components that need direct store access
 */
export function useTenantStoreOnly() {
  return useTenantStore()
}

/**
 * Helper: Check if currently in tenant mode with a selected tenant
 */
export function useTenantMode(): { isPlatform: boolean; isTenant: boolean } {
  const { mode, selectedTenantId } = useTenant()
  return useMemo(
    () => ({
      isPlatform: mode === 'platform',
      isTenant: mode === 'tenant' && selectedTenantId !== null,
    }),
    [mode, selectedTenantId],
  )
}

/**
 * Helper: Get tenant identifier for scoped queries (e.g., 'platform' or 'tenant:123')
 */
export function useTenantScope(): string {
  const { mode, selectedTenantId } = useTenant()
  return useMemo(() => {
    if (mode === 'platform' || !selectedTenantId) {
      return 'platform'
    }
    return `tenant:${selectedTenantId}`
  }, [mode, selectedTenantId])
}
