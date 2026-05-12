import React, { useState, useMemo, useCallback } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Search, AlertCircle, Zap } from 'lucide-react'
import { api } from '@/js/lib/api'
import { useTenant } from '@/js/contexts/TenantContext'
import { queryKeyFactory } from '@/js/lib/queryKeyFactory'
import { useTenantSwitch } from '@/js/hooks/useTenantSwitch'
import { TenantCard, type TenantCardData } from './TenantCard'
import { TenantCardSkeleton } from './TenantCardSkeleton'
import { toast } from 'sonner'

interface TenantSwitcherProps {
  open: boolean
  onOpenChange: (open: boolean) => void
}

type SortOption = 'recent' | 'name' | 'orders' | 'status'
type FilterOption = 'all' | 'active' | 'inactive'

/**
 * TenantSwitcher - Modal component for selecting and switching between tenants
 * 
 * Features:
 * - Search (debounced)
 * - Filter by status
 * - Sort by name, recent, orders count
 * - Grid layout with responsive columns
 * - Loading states with skeleton cards
 * - Empty/error states with CTA
 * - Memoized cards for performance
 * - Context switch with validation
 */
export function TenantSwitcher({ open, onOpenChange }: TenantSwitcherProps) {
  const { setError } = useTenant()
  const { switchTenantContext, isSwitching } = useTenantSwitch()

  // Local state
  const [searchQuery, setSearchQuery] = useState('')
  const [debouncedSearch, setDebouncedSearch] = useState('')
  const [sortBy, setSortBy] = useState<SortOption>('recent')
  const [filterBy, setFilterBy] = useState<FilterOption>('all')
  const [switchingTenantId, setSwitchingTenantId] = useState<number | null>(null)

  // Debounce search
  React.useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearch(searchQuery)
    }, 300)
    return () => clearTimeout(timer)
  }, [searchQuery])

  // Fetch tenants list
  const { data: tenantsResponse, isLoading, isError, error } = useQuery({
    queryKey: queryKeyFactory.tenant.list(),
    queryFn: async () => {
      const response = await api.get('/api/superadmin/stores', {
        params: { limit: 100 },
      })
      return response.data?.data?.items || []
    },
    staleTime: 5 * 60 * 1000, // 5 minutes
    gcTime: 10 * 60 * 1000, // 10 minutes
  })

  // Filter and sort tenants
  const filteredTenants = useMemo(() => {
    if (!tenantsResponse) return []

    let results = [...tenantsResponse]

    // Filter by status
    if (filterBy === 'active') {
      results = results.filter((t) => t.active === true)
    } else if (filterBy === 'inactive') {
      results = results.filter((t) => t.active === false)
    }

    // Filter by search query
    if (debouncedSearch) {
      const query = debouncedSearch.toLowerCase()
      results = results.filter(
        (t) =>
          t.name.toLowerCase().includes(query) ||
          t.slug?.toLowerCase().includes(query),
      )
    }

    // Sort
    switch (sortBy) {
      case 'name':
        results.sort((a, b) => a.name.localeCompare(b.name))
        break
      case 'orders':
        results.sort(
          (a, b) => (b.orders_count || 0) - (a.orders_count || 0),
        )
        break
      case 'status':
        results.sort((a, b) => {
          if (a.active === b.active) return 0
          return a.active ? -1 : 1
        })
        break
      case 'recent':
      default:
        results.sort(
          (a, b) =>
            new Date(b.last_activity_at || 0).getTime() -
            new Date(a.last_activity_at || 0).getTime(),
        )
        break
    }

    return results
  }, [tenantsResponse, filterBy, debouncedSearch, sortBy])

  // Handle tenant selection
  const handleSelectTenant = useCallback(
    async (tenantId: number) => {
      setSwitchingTenantId(tenantId)

      try {
        const data = await switchTenantContext(tenantId, {
          navigateToTenantDashboard: true,
        })
        toast.success(`Conectado a ${data.tenant_name}`)
        onOpenChange(false)
      } catch (err) {
        const message =
          err instanceof Error ? err.message : 'Erro desconhecido'
        setError(message)
        toast.error(`Erro: ${message}`)
      } finally {
        setSwitchingTenantId(null)
      }
    },
    [onOpenChange, setError, switchTenantContext],
  )

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-3xl max-h-[80vh] overflow-hidden flex flex-col">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Zap className="w-5 h-5 text-amber-500" />
            Selecionar Empresa
          </DialogTitle>
        </DialogHeader>

        {/* Search and Filters */}
        <div className="space-y-3 px-6">
          {/* Search Input */}
          <div className="relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
            <Input
              placeholder="Buscar por nome ou slug..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="pl-10"
              disabled={isLoading || isSwitching}
            />
          </div>

          {/* Filter and Sort Controls */}
          <div className="flex gap-2 flex-wrap">
            {/* Status Filter */}
            <div className="flex gap-1">
              {['all', 'active', 'inactive'].map((status) => (
                <Button
                  key={status}
                  variant={filterBy === status ? 'default' : 'outline'}
                  size="sm"
                  onClick={() => setFilterBy(status as FilterOption)}
                  disabled={isLoading || isSwitching}
                >
                  {status === 'all' && 'Todos'}
                  {status === 'active' && 'Ativos'}
                  {status === 'inactive' && 'Inativos'}
                </Button>
              ))}
            </div>

            {/* Sort Dropdown */}
            <div className="flex gap-1">
              {['recent', 'name', 'orders', 'status'].map((sort) => (
                <Button
                  key={sort}
                  variant={sortBy === sort ? 'default' : 'outline'}
                  size="sm"
                  onClick={() => setSortBy(sort as SortOption)}
                  disabled={isLoading || isSwitching}
                >
                  {sort === 'recent' && 'Recente'}
                  {sort === 'name' && 'A-Z'}
                  {sort === 'orders' && 'Pedidos'}
                  {sort === 'status' && 'Status'}
                </Button>
              ))}
            </div>

            {/* Results count */}
            {tenantsResponse && (
              <Badge variant="secondary" className="ml-auto">
                {filteredTenants.length} resultado(s)
              </Badge>
            )}
          </div>
        </div>

        {/* Content Area */}
        <ScrollArea className="flex-1 px-6">
          <div className="pr-4">
            {isLoading ? (
              // Loading skeleton
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                {Array.from({ length: 6 }).map((_, i) => (
                  <TenantCardSkeleton key={i} />
                ))}
              </div>
            ) : isError ? (
              // Error state
              <div className="flex flex-col items-center justify-center py-8 text-center">
                <AlertCircle className="w-12 h-12 text-red-500 mb-3" />
                <h3 className="font-semibold mb-1">Erro ao carregar empresas</h3>
                <p className="text-sm text-muted-foreground mb-4">
                  {error instanceof Error
                    ? error.message
                    : 'Tente novamente mais tarde'}
                </p>
                <Button
                  variant="outline"
                  onClick={() => window.location.reload()}
                >
                  Recarregar
                </Button>
              </div>
            ) : filteredTenants.length === 0 ? (
              // Empty state
              <div className="flex flex-col items-center justify-center py-8 text-center">
                <Search className="w-12 h-12 text-muted-foreground mb-3 opacity-50" />
                <h3 className="font-semibold mb-1">Nenhuma empresa encontrada</h3>
                <p className="text-sm text-muted-foreground">
                  Tente ajustar seus filtros ou busca
                </p>
              </div>
            ) : (
              // Tenants grid
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                {filteredTenants.map((tenant) => (
                  <TenantCard
                    key={tenant.id}
                    tenant={tenant as TenantCardData}
                    isLoading={switchingTenantId === tenant.id}
                    onSelect={handleSelectTenant}
                  />
                ))}
              </div>
            )}
          </div>
        </ScrollArea>

        {/* Footer info */}
        {!isLoading && filteredTenants.length > 0 && (
          <div className="border-t px-6 py-3 text-xs text-muted-foreground">
            Clique em uma empresa para conectar ou pressione Enter
          </div>
        )}
      </DialogContent>
    </Dialog>
  )
}
