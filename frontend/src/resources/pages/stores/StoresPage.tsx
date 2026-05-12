import { useState, useCallback } from 'react'
import { Store, Search, Plus, RefreshCw } from 'lucide-react'
import { PageContainer, PageHeader } from '@/components/shared/PageHeader'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { StatCard } from '@/components/shared/StatCard'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { DEBOUNCE_MS } from '@/js/lib/constants'
import { formatNumber } from '@/js/lib/utils'
import { useDebounce } from '@/js/hooks/useDebounce'
import { useStoresData } from '@/js/hooks/usePhase3Data'
import { useTenantSwitch } from '@/js/hooks/useTenantSwitch'
import { StoreGridCard } from '@/modules/stores/components/StoreGridCard'
import { StoreGridSkeleton } from '@/modules/stores/components/StoreGridSkeleton'
import { toast } from 'sonner'
import type { PaginatedData, StoresStats, StoreItem } from '@/js/types/phase3'

export default function StoresPage() {
  const { switchTenantContext, isSwitching } = useTenantSwitch()

  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')
  const [active, setActive] = useState('')
  const [switchingStoreId, setSwitchingStoreId] = useState<number | null>(null)

  const debouncedSearch = useDebounce(search, DEBOUNCE_MS)
  const { data, isLoading } = useStoresData({
    page,
    per_page: 100,
    search: debouncedSearch,
    active,
  })

  const handleSelectStore = useCallback(
    async (storeId: number) => {
      if (isSwitching) return
      setSwitchingStoreId(storeId)
      try {
        const data = await switchTenantContext(storeId)
        toast.success(`Contexto alterado para: ${data.tenant_name}`)
      } catch (error: any) {
        toast.error(error?.response?.data?.message || error?.message || 'Erro ao trocar contexto')
      } finally {
        setSwitchingStoreId(null)
      }
    },
    [isSwitching, switchTenantContext],
  )

  const paginatedData = data as PaginatedData<StoreItem, StoresStats> | undefined
  const storesList = paginatedData?.items ?? []
  const activeValue = active === '' ? 'all' : active

  const handleRefresh = () => {
    window.location.href = `/superadmin/platform/stores?v=${Date.now()}`
  }

  const handleCreateStore = () => {
    window.location.href = '/superadmin/companies/create'
  }

  const handleActiveChange = (value: string) => {
    setActive(value === 'all' ? '' : value)
    setPage(1)
  }

  return (
    <PageContainer>
      <PageHeader title="Lojas & Tenants" description="Seletor de workspaces e gestão de tenants">
        <div className="flex flex-wrap items-center gap-2 justify-end">
          <Badge variant="secondary" className="gap-1">
            <Store className="h-3.5 w-3.5" />
            Workspace Selector
          </Badge>
          <Button
            variant="outline"
            size="sm"
            className="gap-2"
            onClick={handleRefresh}
          >
            <RefreshCw className="h-4 w-4" />
            Atualizar
          </Button>
          <Button
            size="sm"
            className="gap-2"
            onClick={handleCreateStore}
          >
            <Plus className="h-4 w-4" />
            Nova loja
          </Button>
        </div>
      </PageHeader>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <StatCard
          title="Total de Tenants"
          value={formatNumber(paginatedData?.stats.total ?? 0)}
          className="bg-gradient-to-br from-blue-50/50 to-transparent dark:from-blue-950/20"
          valueClassName="text-blue-600 dark:text-blue-400"
        />
        <StatCard
          title="Ativas"
          value={formatNumber(paginatedData?.stats.active ?? 0)}
          className="bg-gradient-to-br from-emerald-50/50 to-transparent dark:from-emerald-950/20"
          valueClassName="text-emerald-600 dark:text-emerald-400"
        />
        <StatCard
          title="Inativas"
          value={formatNumber(paginatedData?.stats.inactive ?? 0)}
          className="bg-gradient-to-br from-amber-50/50 to-transparent dark:from-amber-950/20"
          valueClassName="text-amber-600 dark:text-amber-400"
        />
      </div>

      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="text-base flex items-center gap-2">
            <Search className="h-4 w-4" />
            Buscar Workspaces
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 gap-3 md:grid-cols-4">
            <div className="relative md:col-span-3">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Buscar por nome ou slug..."
                value={search}
                onChange={(e) => {
                  setSearch(e.target.value)
                  setPage(1)
                }}
                className="pl-10"
              />
            </div>
            <Select value={activeValue} onValueChange={handleActiveChange}>
              <SelectTrigger className="h-10">
                <SelectValue placeholder="Todos os status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Todos os status</SelectItem>
                <SelectItem value="1">Ativas</SelectItem>
                <SelectItem value="0">Inativas</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="text-base">
            {storesList.length > 0
              ? `${storesList.length} Workspace${storesList.length !== 1 ? 's' : ''} encontrado${storesList.length !== 1 ? 's' : ''}`
              : 'Nenhum workspace encontrado'}
          </CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <StoreGridSkeleton count={12} />
          ) : storesList.length > 0 ? (
            <div className="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 auto-rows-max">
              {storesList.map((store) => (
                <StoreGridCard
                  key={store.id}
                  store={store}
                  isLoading={switchingStoreId === store.id && isSwitching}
                  onSelect={handleSelectStore}
                />
              ))}
            </div>
          ) : (
            <div className="py-12 text-center">
              <Store className="h-12 w-12 text-muted-foreground/40 mx-auto mb-4" />
              <p className="text-base font-medium text-muted-foreground">Nenhum workspace encontrado</p>
              <p className="text-sm text-muted-foreground/70 mt-1">
                Tente ajustar seus filtros de busca
              </p>
            </div>
          )}
        </CardContent>
      </Card>
    </PageContainer>
  )
}
