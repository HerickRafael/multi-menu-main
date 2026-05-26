import { useEffect, useMemo, useState } from 'react'
import {
  AlertTriangle,
  CircleDollarSign,
  Pencil,
  Receipt,
  RefreshCw,
  TrendingDown,
  TrendingUp,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  AdminStorePageShell,
  AdminPageHeader,
  DataTable,
  type DataTableColumn,
  EmptyState,
  formatCurrency,
  getCsrfToken,
  showToast,
  useStoreContext,
} from '@/components/admin-store'

type Product = {
  id: number
  name: string
  price: number
  ingredient_cost: number
  packaging_cost: number
  labor_cost: number
  waste_cost: number
  tax_cost: number
  platform_fee_cost: number
  other_costs: number
  total_cost: number
  profit: number
  profit_margin: number
}

type Payload = {
  products: Product[]
  flash: { success: string | null; error: string | null }
  urls: {
    list: string
    edit_base: string
    bulk_update: string
    recalculate: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_PRODUCT_COSTS__?: Payload
  }
}

function marginColor(margin: number): string {
  if (margin >= 30) return 'text-emerald-700'
  if (margin >= 15) return 'text-amber-600'
  if (margin >= 0) return 'text-orange-600'
  return 'text-red-600'
}

function marginBadgeClass(margin: number): string {
  if (margin >= 30) return 'bg-emerald-100 text-emerald-700 border-emerald-200'
  if (margin >= 15) return 'bg-amber-100 text-amber-800 border-amber-200'
  if (margin >= 0) return 'bg-orange-100 text-orange-700 border-orange-200'
  return 'bg-red-100 text-red-700 border-red-200'
}

function marginLabel(margin: number): string {
  if (margin >= 30) return 'Saudável'
  if (margin >= 15) return 'Atenção'
  if (margin >= 0) return 'Baixa'
  return 'Prejuízo'
}

export default function AdminStoreProductCostsPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_PRODUCT_COSTS__) || ({} as Payload)
  const urls = payload.urls
  const [products, setProducts] = useState<Product[]>(payload.products ?? [])
  const [recalculating, setRecalculating] = useState(false)
  const [filterMargin, setFilterMargin] = useState<'all' | 'low' | 'loss'>('all')

  useEffect(() => {
    if (payload.flash?.error) showToast(payload.flash.error, 'error')
    if (payload.flash?.success) {
      const msg =
        payload.flash.success === 'updated'
          ? 'Custos atualizados!'
          : payload.flash.success === 'recalculated'
            ? 'Snapshots recalculados.'
            : 'Operação realizada.'
      showToast(msg, 'success')
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  async function recalculate() {
    if (!window.confirm('Recalcular custos de todos os produtos? Isso atualiza os snapshots no banco.')) return
    setRecalculating(true)
    try {
      const fd = new FormData()
      fd.append('csrf_token', getCsrfToken())
      const res = await fetch(urls.recalculate, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean; updated?: number; message?: string } | null
      if (data?.success) {
        showToast(data.message || `${data.updated ?? 0} produtos recalculados.`, 'success')
        // Reload to fetch fresh costs
        setTimeout(() => window.location.reload(), 600)
      } else {
        showToast(data?.message || 'Falha ao recalcular.', 'error')
      }
    } catch {
      showToast('Falha de rede ao recalcular.', 'error')
    } finally {
      setRecalculating(false)
    }
  }

  const filtered = useMemo(() => {
    if (filterMargin === 'all') return products
    if (filterMargin === 'loss') return products.filter((p) => p.profit_margin < 0)
    if (filterMargin === 'low') return products.filter((p) => p.profit_margin >= 0 && p.profit_margin < 20)
    return products
  }, [products, filterMargin])

  // Summary stats
  const totalProducts = products.length
  const avgMargin =
    totalProducts > 0
      ? products.reduce((acc, p) => acc + p.profit_margin, 0) / totalProducts
      : 0
  const lowMarginCount = products.filter((p) => p.profit_margin >= 0 && p.profit_margin < 20).length
  const lossCount = products.filter((p) => p.profit_margin < 0).length

  const columns = useMemo<DataTableColumn<Product>[]>(
    () => [
      {
        key: 'name',
        header: 'Produto',
        cell: (row) => (
          <a
            href={`${urls.edit_base}${row.id}/edit`}
            className="font-medium text-zinc-800 hover:text-zinc-950 hover:underline"
          >
            {row.name}
          </a>
        ),
        accessor: (row) => row.name.toLowerCase(),
        sortable: true,
      },
      {
        key: 'price',
        header: 'Preço',
        cell: (row) => <span className="font-mono text-zinc-700">{formatCurrency(row.price)}</span>,
        accessor: (row) => row.price,
        sortable: true,
        className: 'w-24 text-right',
        headerClassName: 'text-right',
      },
      {
        key: 'ingredient_cost',
        header: 'Ingredientes',
        cell: (row) => (
          <span className="font-mono text-xs text-zinc-600">{formatCurrency(row.ingredient_cost)}</span>
        ),
        accessor: (row) => row.ingredient_cost,
        sortable: true,
        className: 'w-28 text-right',
        headerClassName: 'text-right',
      },
      {
        key: 'packaging_cost',
        header: 'Embalagem',
        cell: (row) => (
          <span className="font-mono text-xs text-zinc-600">{formatCurrency(row.packaging_cost)}</span>
        ),
        accessor: (row) => row.packaging_cost,
        sortable: true,
        className: 'w-24 text-right',
        headerClassName: 'text-right',
      },
      {
        key: 'other',
        header: 'Outros',
        cell: (row) => {
          const other =
            row.labor_cost + row.waste_cost + row.tax_cost + row.platform_fee_cost + row.other_costs
          return <span className="font-mono text-xs text-zinc-500">{formatCurrency(other)}</span>
        },
        accessor: (row) =>
          row.labor_cost + row.waste_cost + row.tax_cost + row.platform_fee_cost + row.other_costs,
        sortable: true,
        className: 'w-24 text-right',
        headerClassName: 'text-right',
      },
      {
        key: 'total_cost',
        header: 'CMV total',
        cell: (row) => (
          <span className="font-mono text-sm font-semibold text-red-600">−{formatCurrency(row.total_cost)}</span>
        ),
        accessor: (row) => row.total_cost,
        sortable: true,
        className: 'w-28 text-right',
        headerClassName: 'text-right',
      },
      {
        key: 'profit',
        header: 'Lucro',
        cell: (row) => (
          <span className={`font-mono text-sm font-semibold ${row.profit >= 0 ? 'text-emerald-700' : 'text-red-600'}`}>
            {row.profit >= 0 ? '' : '−'}
            {formatCurrency(Math.abs(row.profit))}
          </span>
        ),
        accessor: (row) => row.profit,
        sortable: true,
        className: 'w-28 text-right',
        headerClassName: 'text-right',
      },
      {
        key: 'margin',
        header: 'Margem',
        cell: (row) => (
          <Badge className={`${marginBadgeClass(row.profit_margin)} border font-mono`}>
            {row.profit_margin.toFixed(1)}%
          </Badge>
        ),
        accessor: (row) => row.profit_margin,
        sortable: true,
        className: 'w-24',
      },
      {
        key: 'actions',
        header: <span className="sr-only">Ações</span>,
        className: 'w-16 text-right',
        cell: (row) => (
          <Button asChild size="sm" variant="ghost" className="h-8 px-2 text-zinc-600 hover:text-zinc-900">
            <a href={`${urls.edit_base}${row.id}/edit`} aria-label="Editar custos">
              <Pencil className="h-3.5 w-3.5" />
            </a>
          </Button>
        ),
      },
    ],
    [urls.edit_base],
  )

  return (
    <AdminStorePageShell section="financial">
      <AdminPageHeader
        title="Custos de produtos"
        description={`Análise de CMV (custo da mercadoria vendida) e margem de lucro por produto.`}
        icon={<Receipt className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <Button
            type="button"
            variant="outline"
            onClick={recalculate}
            disabled={recalculating}
            className="gap-2"
          >
            <RefreshCw className={`h-4 w-4 ${recalculating ? 'animate-spin' : ''}`} />
            {recalculating ? 'Recalculando...' : 'Recalcular snapshots'}
          </Button>
        }
      />

      <section className="grid gap-3 sm:grid-cols-4">
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Produtos analisados</p>
          <p className="mt-1 text-2xl font-semibold text-zinc-800">{totalProducts}</p>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Margem média</p>
          <p className={`mt-1 text-2xl font-semibold ${marginColor(avgMargin)}`}>{avgMargin.toFixed(1)}%</p>
          <Badge className={`mt-1 border text-[10px] h-4 ${marginBadgeClass(avgMargin)}`}>
            {marginLabel(avgMargin)}
          </Badge>
        </div>
        <button
          type="button"
          onClick={() => setFilterMargin(filterMargin === 'low' ? 'all' : 'low')}
          className={`text-left rounded-2xl border bg-white p-4 shadow-sm transition ${
            filterMargin === 'low' ? 'border-amber-400 ring-2 ring-amber-100' : 'border-zinc-200'
          } hover:border-zinc-400`}
        >
          <p className="text-xs text-zinc-500 flex items-center gap-1">
            <TrendingDown className="h-3.5 w-3.5" />
            Margem baixa (&lt; 20%)
            {filterMargin === 'low' && (
              <Badge className="bg-amber-100 text-amber-800 border-amber-200 hover:bg-amber-100 text-[9px] h-4">filtro</Badge>
            )}
          </p>
          <p className="mt-1 text-2xl font-semibold text-amber-600">{lowMarginCount}</p>
          <p className="mt-1 text-[11px] text-zinc-500">produtos atenção</p>
        </button>
        <button
          type="button"
          onClick={() => setFilterMargin(filterMargin === 'loss' ? 'all' : 'loss')}
          className={`text-left rounded-2xl border bg-white p-4 shadow-sm transition ${
            filterMargin === 'loss' ? 'border-red-400 ring-2 ring-red-100' : 'border-zinc-200'
          } hover:border-zinc-400`}
        >
          <p className="text-xs text-zinc-500 flex items-center gap-1">
            <AlertTriangle className="h-3.5 w-3.5" />
            Prejuízo
            {filterMargin === 'loss' && (
              <Badge className="bg-red-100 text-red-700 border-red-200 hover:bg-red-100 text-[9px] h-4">filtro</Badge>
            )}
          </p>
          <p className="mt-1 text-2xl font-semibold text-red-600">{lossCount}</p>
          <p className="mt-1 text-[11px] text-zinc-500">vendendo no vermelho</p>
        </button>
      </section>

      {products.length === 0 ? (
        <EmptyState
          title="Sem produtos para analisar"
          description="Cadastre produtos com ingredientes para começar a ver custos e margens. Os custos são calculados automaticamente a partir do CMV dos ingredientes."
          icon={<CircleDollarSign className="h-5 w-5" />}
        />
      ) : (
        <DataTable
          data={filtered}
          columns={columns}
          rowKey={(row) => row.id}
          searchPlaceholder="Buscar produto..."
          searchAccessor={(row) => row.name}
          pageSize={50}
        />
      )}
    </AdminStorePageShell>
  )
}
