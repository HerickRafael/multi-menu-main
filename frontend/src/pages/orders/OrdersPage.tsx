import { memo, useEffect, useState } from 'react'
import { ShoppingBag } from 'lucide-react'
import { PageContainer, PageHeader } from '@/components/shared/PageHeader'
import { TableSkeleton } from '@/components/shared/Skeletons'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { DEBOUNCE_MS } from '@/lib/constants'
import { formatCurrency, formatDate, formatNumber } from '@/lib/utils'
import { useDebounce } from '@/hooks/useDebounce'
import { useOrdersData } from '@/hooks/usePhase3Data'
import type { OrderItem } from '@/types/phase3'
import { useCompanyFilterStore } from '@/stores/companyFilterStore'
import { useTenant } from '@/contexts/TenantContext'
import { Link } from 'react-router-dom'

function statusVariant(status: string): 'default' | 'success' | 'warning' | 'destructive' {
  if (status === 'completed' || status === 'paid') return 'success'
  if (status === 'pending') return 'warning'
  if (status === 'canceled') return 'destructive'
  return 'default'
}

const OrderRow = memo(function OrderRow({ order, detailsHref }: { order: OrderItem; detailsHref: string }) {
  return (
    <tr className="border-t">
      <td className="px-4 py-3 font-medium">#{order.id}</td>
      <td className="px-4 py-3">{order.customer_name || 'N/A'}</td>
      <td className="px-4 py-3 text-muted-foreground">{order.customer_phone || '-'}</td>
      <td className="px-4 py-3">{order.company_name}</td>
      <td className="px-4 py-3"><Badge variant={statusVariant(order.status)}>{order.status}</Badge></td>
      <td className="px-4 py-3 font-medium">{formatCurrency(order.total)}</td>
      <td className="px-4 py-3 text-muted-foreground">{formatDate(order.created_at)}</td>
      <td className="px-4 py-3">
        <Button asChild variant="outline" size="sm">
          <Link to={`${detailsHref}/${order.id}`}>Detalhes</Link>
        </Button>
      </td>
    </tr>
  )
})

export default function OrdersPage() {
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const selectedCompanyId = useCompanyFilterStore((state) => state.selectedCompanyId)
  const { mode, selectedTenantSlug } = useTenant()
  const tenantSlug = selectedTenantSlug || 'select-tenant'
  const detailsHref = mode === 'tenant'
    ? `/superadmin/tenant/${tenantSlug}/orders`
    : '/superadmin/platform/stores'

  useEffect(() => {
    setPage(1)
  }, [selectedCompanyId])

  const debouncedSearch = useDebounce(search, DEBOUNCE_MS)
  const { data, isLoading, isFetching, isError, error } = useOrdersData({
    page,
    per_page: 10,
    search: debouncedSearch,
    company_id: selectedCompanyId ?? undefined,
    status,
  })

  const pagination = data?.pagination

  if (isError) {
    return (
      <PageContainer>
        <PageHeader title="Pedidos" description="Monitor operacional de pedidos" />
        <Card>
          <CardHeader>
            <CardTitle className="text-base text-destructive">Falha ao carregar pedidos</CardTitle>
          </CardHeader>
          <CardContent className="text-sm text-muted-foreground">{error?.message ?? 'Tente novamente em instantes.'}</CardContent>
        </Card>
      </PageContainer>
    )
  }

  return (
    <PageContainer>
      <PageHeader title="Pedidos" description="Monitor operacional de pedidos">
        <Badge variant="secondary" className="gap-1">
          <ShoppingBag className="h-3.5 w-3.5" />
          Fase 3
        </Badge>
      </PageHeader>

      <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Total</CardTitle></CardHeader><CardContent className="text-2xl font-bold">{formatNumber(data?.stats.total ?? 0)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Pendentes</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-amber-600">{formatNumber(data?.stats.pending ?? 0)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Pagos</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-blue-600">{formatNumber(data?.stats.paid ?? 0)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Concluídos</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-emerald-600">{formatNumber(data?.stats.completed ?? 0)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Cancelados</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-rose-600">{formatNumber(data?.stats.canceled ?? 0)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Faturamento</CardTitle></CardHeader><CardContent className="text-lg font-bold">{formatCurrency(data?.stats.gross_total ?? 0)}</CardContent></Card>
      </div>

      <Card>
        <CardHeader className="pb-3"><CardTitle className="text-base">Lista de Pedidos</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 gap-3 md:grid-cols-4">
            <Input
              placeholder="Buscar por cliente, telefone ou loja"
              value={search}
              onChange={(e) => {
                setSearch(e.target.value)
                setPage(1)
              }}
              className="md:col-span-3"
            />
            <select
              value={status}
              onChange={(e) => {
                setStatus(e.target.value)
                setPage(1)
              }}
              className="h-10 rounded-md border border-input bg-background px-3 text-sm"
            >
              <option value="">Todos os status</option>
              <option value="pending">Pendente</option>
              <option value="paid">Pago</option>
              <option value="completed">Concluído</option>
              <option value="canceled">Cancelado</option>
            </select>
          </div>

          {isLoading ? (
            <TableSkeleton rows={8} cols={8} />
          ) : (
            <div className="overflow-x-auto rounded-md border">
              <table className="w-full min-w-[980px] text-sm">
                <thead className="bg-muted/50 text-left">
                  <tr>
                    <th className="px-4 py-3 font-medium">Pedido</th>
                    <th className="px-4 py-3 font-medium">Cliente</th>
                    <th className="px-4 py-3 font-medium">Telefone</th>
                    <th className="px-4 py-3 font-medium">Loja</th>
                    <th className="px-4 py-3 font-medium">Status</th>
                    <th className="px-4 py-3 font-medium">Total</th>
                    <th className="px-4 py-3 font-medium">Data</th>
                    <th className="px-4 py-3 font-medium">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  {(data?.items ?? []).map((order) => (
                    <OrderRow key={order.id} order={order} detailsHref={detailsHref} />
                  ))}
                </tbody>
              </table>
              {(data?.items.length ?? 0) === 0 && (
                <p className="p-6 text-center text-sm text-muted-foreground">Nenhum pedido encontrado para os filtros atuais.</p>
              )}
            </div>
          )}

          <div className="flex items-center justify-between">
            <p className="text-xs text-muted-foreground">
              {isFetching ? 'Atualizando...' : `Página ${pagination?.page ?? 1} de ${pagination?.total_pages ?? 1}`}
            </p>
            <div className="flex gap-2">
              <Button variant="outline" size="sm" disabled={!pagination?.has_prev} onClick={() => setPage((p) => Math.max(1, p - 1))}>Anterior</Button>
              <Button variant="outline" size="sm" disabled={!pagination?.has_next} onClick={() => setPage((p) => p + 1)}>Próxima</Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </PageContainer>
  )
}
