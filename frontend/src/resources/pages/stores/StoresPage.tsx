import { useCallback, useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQueryClient } from '@tanstack/react-query'
import { Download, Filter, Plus, RefreshCw, Search, Store } from 'lucide-react'
import { PageContainer } from '@/components/shared/PageHeader'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Separator } from '@/components/ui/separator'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet'
import { Progress } from '@/components/ui/progress'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb'
import { DEBOUNCE_MS } from '@/js/lib/constants'
import { formatNumber, formatRelativeTime } from '@/js/lib/utils'
import { useDebounce } from '@/js/hooks/useDebounce'
import { useStoresData } from '@/js/hooks/usePhase3Data'
import { useTenantSwitch } from '@/js/hooks/useTenantSwitch'
import { useCompanyFilterStore } from '@/js/stores/companyFilterStore'
import { del, post } from '@/js/lib/api'
import { invalidateResourceQueries } from '@/js/lib/queryKeyFactory'
import { StoreGridCard } from '@/modules/stores/components/StoreGridCard'
import { StoreGridSkeleton } from '@/modules/stores/components/StoreGridSkeleton'
import { toast } from 'sonner'
import type { PaginatedData, StoresStats, StoreItem } from '@/js/types/phase3'


export default function StoresPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { switchTenantContext, isSwitching } = useTenantSwitch()
  const setSelectedCompanyId = useCompanyFilterStore((state) => state.setSelectedCompanyId)

  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')
  const [active, setActive] = useState('')
  const [switchingStoreId, setSwitchingStoreId] = useState<number | null>(null)
  const [detailStore, setDetailStore] = useState<StoreItem | null>(null)
  const [detailOpen, setDetailOpen] = useState(false)
  const [deleteTarget, setDeleteTarget] = useState<StoreItem | null>(null)

  const debouncedSearch = useDebounce(search, DEBOUNCE_MS)
  const { data, isLoading, isFetching, isError, error } = useStoresData({
    page,
    per_page: 50,
    search: debouncedSearch,
    active,
  })

  const resolveErrorMessage = useCallback(
    (err: any) => err?.response?.data?.message || err?.message || 'Erro ao executar acao',
    [],
  )

  const handleOpenDetails = useCallback((store: StoreItem) => {
    setDetailStore(store)
    setDetailOpen(true)
  }, [])

  const handleEnterStore = useCallback(
    async (store: StoreItem) => {
      if (isSwitching) return
      setSwitchingStoreId(store.id)
      try {
        const data = await switchTenantContext(store.id, { navigateToTenantDashboard: true })
        toast.success(`Contexto alterado para: ${data.tenant_name}`)
      } catch (err: any) {
        toast.error(resolveErrorMessage(err))
      } finally {
        setSwitchingStoreId(null)
      }
    },
    [isSwitching, resolveErrorMessage, switchTenantContext],
  )

  const handleOpenTenantRoute = useCallback(
    async (store: StoreItem, route: string) => {
      if (isSwitching) return
      setSwitchingStoreId(store.id)
      try {
        const data = await switchTenantContext(store.id, { navigateToTenantDashboard: false })
        const slug = data.tenant_slug || store.slug
        navigate(route.replace(':slug', slug))
        toast.success(`Contexto alterado para: ${data.tenant_name}`)
      } catch (err: any) {
        toast.error(resolveErrorMessage(err))
      } finally {
        setSwitchingStoreId(null)
      }
    },
    [isSwitching, navigate, resolveErrorMessage, switchTenantContext],
  )

  const handleOpenDashboard = useCallback(
    async (store: StoreItem) => {
      await handleEnterStore(store)
    },
    [handleEnterStore],
  )

  const handleOpenAnalytics = useCallback(
    async (store: StoreItem) => {
      await handleOpenTenantRoute(store, '/superadmin/tenant/:slug/analytics')
    },
    [handleOpenTenantRoute],
  )

  const handleOpenConfig = useCallback((store: StoreItem) => {
    window.location.href = `/superadmin/companies/${store.id}`
  }, [])

  const handleOpenLogs = useCallback((store: StoreItem) => {
    setSelectedCompanyId(store.id)
    navigate('/superadmin/platform/logs')
  }, [navigate, setSelectedCompanyId])

  const handleOpenWebhooks = useCallback((store: StoreItem) => {
    setSelectedCompanyId(store.id)
    navigate('/superadmin/platform/webhooks')
  }, [navigate, setSelectedCompanyId])

  const handleResetCache = useCallback((store: StoreItem) => {
    post('/stores/reset-cache', { company_id: store.id })
      .then(() => {
        toast.success(`Cache de ${store.name} invalidado com sucesso`)
      })
      .catch((err: any) => {
        toast.error(resolveErrorMessage(err))
      })
  }, [resolveErrorMessage])

  const handleToggleActive = useCallback(async (store: StoreItem) => {
    try {
      await post('/stores/toggle-status', { company_id: store.id })
      await queryClient.invalidateQueries({ predicate: invalidateResourceQueries('stores') })
      toast.success(store.active ? `${store.name} suspensa com sucesso` : `${store.name} ativada com sucesso`)
    } catch (err: any) {
      toast.error(resolveErrorMessage(err))
    }
  }, [queryClient, resolveErrorMessage])

  const handleDelete = useCallback((store: StoreItem) => {
    setDeleteTarget(store)
  }, [])

  const paginatedData = data as PaginatedData<StoreItem, StoresStats> | undefined
  const storesList = paginatedData?.items ?? []
  const activeValue = active === '' ? 'all' : active
  const stats = paginatedData?.stats ?? { total: 0, active: 0, inactive: 0 }
  const pagination = paginatedData?.pagination
  const summaryLabel = `${formatNumber(stats.total)} tenants • ${formatNumber(stats.active)} ativa(s) • ${formatNumber(stats.inactive)} inativa(s)`

  const storeActions = useMemo(() => (
    {
      onSelect: handleEnterStore,
      onOpenDetails: handleOpenDetails,
      onOpenDashboard: handleOpenDashboard,
      onOpenAnalytics: handleOpenAnalytics,
      onOpenConfig: handleOpenConfig,
      onOpenLogs: handleOpenLogs,
      onOpenWebhooks: handleOpenWebhooks,
      onResetCache: handleResetCache,
      onToggleActive: handleToggleActive,
      onDelete: handleDelete,
    }
  ), [
    handleDelete,
    handleEnterStore,
    handleOpenAnalytics,
    handleOpenConfig,
    handleOpenDashboard,
    handleOpenDetails,
    handleOpenLogs,
    handleOpenWebhooks,
    handleResetCache,
    handleToggleActive,
  ])

  const handleRefresh = useCallback(async () => {
    await queryClient.invalidateQueries({ predicate: invalidateResourceQueries('stores') })
  }, [queryClient])

  const handleCreateStore = () => {
    window.location.href = '/superadmin/companies/create'
  }

  const handleExport = () => {
    toast.info('Exportacao em preparo. Disponivel em breve.')
  }

  const handleFocusSearch = () => {
    const searchInput = document.getElementById('stores-search') as HTMLInputElement | null
    searchInput?.focus()
  }

  const handleActiveChange = (value: string) => {
    setActive(value === 'all' ? '' : value)
    setPage(1)
  }

  const handleConfirmDelete = () => {
    if (!deleteTarget) {
      return
    }

    del('/stores', { company_id: deleteTarget.id, force_delete: true })
      .then(async () => {
        await queryClient.invalidateQueries({ predicate: invalidateResourceQueries('stores') })
        toast.success(`Loja ${deleteTarget.name} excluida com sucesso`)
      })
      .catch((err: any) => {
        const msg = err?.response?.data?.message || resolveErrorMessage(err)
        toast.error(msg)
      })
      .finally(() => {
        setDeleteTarget(null)
      })
  }

  const renderEmptyState = () => (
    <div className="py-12 text-center">
      <Store className="h-12 w-12 text-muted-foreground/40 mx-auto mb-4" />
      <p className="text-base font-medium text-muted-foreground">Nenhum workspace encontrado</p>
      <p className="text-sm text-muted-foreground/70 mt-1">
        Ajuste os filtros ou refine a busca
      </p>
    </div>
  )

  return (
    <PageContainer>
      <div className="space-y-4">
        <div className="flex flex-col gap-3">
          <div className="flex flex-wrap items-start justify-between gap-3">
            <div className="space-y-1">
              <Breadcrumb>
                <BreadcrumbList>
                  <BreadcrumbItem>
                    <BreadcrumbPage>Super Admin</BreadcrumbPage>
                  </BreadcrumbItem>
                  <BreadcrumbSeparator />
                  <BreadcrumbItem>
                    <BreadcrumbPage>Platform</BreadcrumbPage>
                  </BreadcrumbItem>
                  <BreadcrumbSeparator />
                  <BreadcrumbItem>
                    <BreadcrumbPage>Stores</BreadcrumbPage>
                  </BreadcrumbItem>
                </BreadcrumbList>
              </Breadcrumb>
              <h1 className="text-xl font-semibold tracking-tight">Control Center Multi-tenant</h1>
              <p className="text-sm text-muted-foreground">Operacao centralizada para tenants e workspaces ativos.</p>
            </div>
            <div className="flex flex-wrap items-center gap-2">
              <Button size="sm" className="gap-2" onClick={handleCreateStore}>
                <Plus className="h-4 w-4" />
                Nova loja
              </Button>
              <Popover>
                <PopoverTrigger asChild>
                  <Button variant="outline" size="sm" className="gap-2">
                    <Store className="h-4 w-4" />
                    Deploy status
                  </Button>
                </PopoverTrigger>
                <PopoverContent align="end">
                  <div className="space-y-2 text-sm">
                    <p className="font-semibold">Deploy status</p>
                    <div className="flex items-center justify-between">
                      <span className="text-muted-foreground">Release</span>
                      <Badge variant="secondary">Stable</Badge>
                    </div>
                    <div className="flex items-center justify-between">
                      <span className="text-muted-foreground">Workers</span>
                      <Badge variant="success">Online</Badge>
                    </div>
                    <div className="flex items-center justify-between">
                      <span className="text-muted-foreground">Queues</span>
                      <Badge variant="warning">Aguardando</Badge>
                    </div>
                  </div>
                </PopoverContent>
              </Popover>
              <Button variant="outline" size="sm" className="gap-2" onClick={handleFocusSearch}>
                <Filter className="h-4 w-4" />
                Filtrar
              </Button>
              <Button variant="outline" size="sm" className="gap-2" onClick={handleExport}>
                <Download className="h-4 w-4" />
                Exportar
              </Button>
            </div>
          </div>
        </div>

        <Separator />

        <Card className="border-border/60">
          <CardContent className="p-4 space-y-3">
            <div className="grid grid-cols-1 gap-3 md:grid-cols-[2fr_1fr_1fr_1fr_auto]">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                  id="stores-search"
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
                  <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Todos os status</SelectItem>
                  <SelectItem value="1">Ativas</SelectItem>
                  <SelectItem value="0">Inativas</SelectItem>
                </SelectContent>
              </Select>
              <Button size="sm" className="h-10" onClick={handleCreateStore}>
                Criar loja
              </Button>
            </div>
            <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
              <Badge variant="secondary" className="text-[11px]">{summaryLabel}</Badge>
              <Separator orientation="vertical" className="h-4" />
              <Badge variant={isFetching ? 'info' : 'secondary'} className="text-[11px]">
                {isFetching ? 'Sincronizando' : 'Atualizado'}
              </Badge>
              <Button
                variant="ghost"
                size="sm"
                className="h-7 px-2 text-xs"
                onClick={handleRefresh}
              >
                <RefreshCw className="h-3.5 w-3.5" />
                Atualizar
              </Button>
            </div>
          </CardContent>
        </Card>

        <div className="space-y-3">
          <div className="flex items-center justify-end gap-2 text-xs text-muted-foreground">
            <Badge variant="secondary" className="text-[11px]">
              {storesList.length} workspaces
            </Badge>
          </div>

          {isLoading ? (
            <StoreGridSkeleton count={12} />
          ) : isError ? (
            <div className="rounded-md border border-destructive/40 bg-destructive/5 p-6 text-sm text-destructive">
              {error instanceof Error ? error.message : 'Falha ao carregar lojas'}
            </div>
          ) : storesList.length > 0 ? (
            <div className="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 auto-rows-max">
              {storesList.map((store) => (
                <StoreGridCard
                  key={store.id}
                  store={store}
                  isLoading={switchingStoreId === store.id && isSwitching}
                  actions={storeActions}
                />
              ))}
            </div>
          ) : (
            renderEmptyState()
          )}
        </div>

        {pagination && (
          <div className="flex items-center justify-between text-xs text-muted-foreground">
            <p>{`Pagina ${pagination.page} de ${pagination.total_pages}`}</p>
            <div className="flex gap-2">
              <Button
                variant="outline"
                size="sm"
                disabled={!pagination.has_prev}
                onClick={() => setPage((p) => Math.max(1, p - 1))}
              >
                Anterior
              </Button>
              <Button
                variant="outline"
                size="sm"
                disabled={!pagination.has_next}
                onClick={() => setPage((p) => p + 1)}
              >
                Proxima
              </Button>
            </div>
          </div>
        )}
      </div>

      <Sheet open={detailOpen} onOpenChange={setDetailOpen}>
        <SheetContent className="sm:max-w-xl">
          <SheetHeader>
            <SheetTitle>{detailStore?.name ?? 'Detalhes da loja'}</SheetTitle>
            <SheetDescription>
              {detailStore ? `/${detailStore.slug}` : 'Carregando detalhes'}
            </SheetDescription>
          </SheetHeader>

          {detailStore && (
            <div className="mt-4 space-y-5">
              <div className="grid grid-cols-2 gap-3">
                <Card className="border-border/60">
                  <CardContent className="p-3">
                    <p className="text-xs text-muted-foreground">Status</p>
                    <Badge variant={detailStore.active ? 'success' : 'destructive'} className="mt-2">
                      {detailStore.active ? 'Ativa' : 'Inativa'}
                    </Badge>
                  </CardContent>
                </Card>
                <Card className="border-border/60">
                  <CardContent className="p-3">
                    <p className="text-xs text-muted-foreground">Ultima atividade</p>
                    <p className="mt-2 text-sm font-semibold">
                      {detailStore.created_at ? formatRelativeTime(detailStore.created_at) : 'Sem dados'}
                    </p>
                  </CardContent>
                </Card>
                <Card className="border-border/60">
                  <CardContent className="p-3">
                    <p className="text-xs text-muted-foreground">Pedidos</p>
                    <p className="mt-2 text-sm font-semibold">{formatNumber(detailStore.orders_count)}</p>
                  </CardContent>
                </Card>
                <Card className="border-border/60">
                  <CardContent className="p-3">
                    <p className="text-xs text-muted-foreground">Usuarios</p>
                    <p className="mt-2 text-sm font-semibold">{formatNumber(detailStore.users_count)}</p>
                  </CardContent>
                </Card>
              </div>

              <Separator />

              <div className="space-y-3">
                <div className="flex items-center justify-between">
                  <p className="text-sm font-semibold">Tenant health</p>
                  <Badge variant="secondary" className="text-[11px]">Sem telemetria</Badge>
                </div>
                <div className="space-y-2">
                  <div>
                    <div className="flex items-center justify-between text-xs text-muted-foreground">
                      <span>API</span>
                      <span>{detailStore.active ? 'Online' : 'Offline'}</span>
                    </div>
                    <Progress value={detailStore.active ? 80 : 20} />
                  </div>
                </div>
              </div>

              <Separator />

              <div className="space-y-2">
                <p className="text-sm font-semibold">Acoes rapidas</p>
                <div className="flex flex-wrap gap-2">
                  <Button size="sm" onClick={() => handleEnterStore(detailStore)}>
                    Entrar no tenant
                  </Button>
                  <Button size="sm" variant="outline" onClick={() => handleOpenConfig(detailStore)}>
                    Configurar loja
                  </Button>
                  <Button size="sm" variant="outline" onClick={() => handleOpenAnalytics(detailStore)}>
                    Analytics
                  </Button>
                </div>
              </div>
            </div>
          )}

          <SheetFooter>
            <Button variant="outline" onClick={() => setDetailOpen(false)}>Fechar</Button>
          </SheetFooter>
        </SheetContent>
      </Sheet>

      <AlertDialog open={!!deleteTarget} onOpenChange={(open: boolean) => !open && setDeleteTarget(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Excluir workspace</AlertDialogTitle>
            <AlertDialogDescription>
              Esta acao e permanente. Ao confirmar, a loja e TODOS os dados relacionados serao removidos,
              incluindo clientes, pedidos, usuarios, logs e demais registros do tenant. Deseja realmente excluir?
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancelar</AlertDialogCancel>
            <AlertDialogAction onClick={handleConfirmDelete}>Excluir permanentemente</AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </PageContainer>
  )
}
