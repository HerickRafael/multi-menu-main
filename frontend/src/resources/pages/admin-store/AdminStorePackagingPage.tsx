import { useEffect, useMemo, useState } from 'react'
import {
  AlertTriangle,
  Box,
  Building2,
  Package,
  Pencil,
  Plus,
  Trash2,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  AdminStorePageShell,
  AdminPageHeader,
  ConfirmDialog,
  DataTable,
  type DataTableColumn,
  EmptyState,
  formatCurrency,
  getCsrfToken,
  showToast,
  useStoreContext,
} from '@/components/admin-store'

type Supply = {
  id: number
  name: string
  description: string
  unit: string
  cost_per_unit: number
  stock_quantity: number
  min_stock_alert: number
  supplier: string
  active: boolean
  product_count: number
  low_stock: boolean
}

type Payload = {
  supplies: Supply[]
  flash: { success: string | null; error: string | null }
  urls: {
    list: string
    create: string
    store: string
    edit_base: string
    destroy_base: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_PACKAGING__?: Payload
  }
}

export default function AdminStorePackagingPage() {
  const ctx = useStoreContext()
  const payload = (typeof window !== 'undefined' && window.__ADMIN_STORE_PACKAGING__) || ({} as Payload)
  const urls = payload.urls
  const [supplies, setSupplies] = useState<Supply[]>(payload.supplies ?? [])
  const [confirmDelete, setConfirmDelete] = useState<Supply | null>(null)
  const [filter, setFilter] = useState<'all' | 'active' | 'inactive' | 'low_stock'>('all')

  useEffect(() => {
    if (payload.flash?.error) showToast(payload.flash.error, 'error')
    if (payload.flash?.success) {
      const msg =
        payload.flash.success === 'created'
          ? 'Insumo criado!'
          : payload.flash.success === 'updated'
            ? 'Insumo atualizado!'
            : payload.flash.success === 'deleted'
              ? 'Insumo removido!'
              : 'Operação realizada.'
      showToast(msg, 'success')
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  async function handleDelete() {
    if (!confirmDelete) return
    const fd = new FormData()
    fd.append('csrf_token', getCsrfToken())
    try {
      const res = await fetch(`${urls.destroy_base}${confirmDelete.id}/delete`, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        redirect: 'manual',
      })
      if (res.type === 'opaqueredirect' || res.ok || res.status === 302 || res.status === 0) {
        setSupplies((prev) => prev.filter((s) => s.id !== confirmDelete.id))
        showToast(`Insumo "${confirmDelete.name}" removido.`, 'success')
      } else {
        showToast('Falha ao remover.', 'error')
      }
    } catch {
      setSupplies((prev) => prev.filter((s) => s.id !== confirmDelete.id))
      showToast(`Insumo "${confirmDelete.name}" removido.`, 'success')
    }
  }

  const filtered = useMemo(() => {
    if (filter === 'all') return supplies
    if (filter === 'active') return supplies.filter((s) => s.active)
    if (filter === 'inactive') return supplies.filter((s) => !s.active)
    if (filter === 'low_stock') return supplies.filter((s) => s.low_stock)
    return supplies
  }, [supplies, filter])

  const totalValue = supplies.reduce((acc, s) => acc + s.stock_quantity * s.cost_per_unit, 0)
  const activeCount = supplies.filter((s) => s.active).length
  const lowStockCount = supplies.filter((s) => s.low_stock).length
  const totalProductsUsing = supplies.reduce((acc, s) => acc + s.product_count, 0)

  const columns = useMemo<DataTableColumn<Supply>[]>(
    () => [
      {
        key: 'name',
        header: 'Insumo',
        cell: (row) => (
          <div className="flex items-center gap-2 min-w-0">
            <Box className="h-4 w-4 text-zinc-400 shrink-0" />
            <div className="min-w-0">
              <a
                href={`${urls.edit_base}${row.id}/edit`}
                className="font-medium text-zinc-800 hover:text-zinc-950 hover:underline truncate block"
              >
                {row.name}
              </a>
              {row.supplier && (
                <p className="text-[11px] text-zinc-500 truncate flex items-center gap-1">
                  <Building2 className="h-2.5 w-2.5" />
                  {row.supplier}
                </p>
              )}
            </div>
          </div>
        ),
        accessor: (row) => row.name.toLowerCase(),
        sortable: true,
      },
      {
        key: 'cost',
        header: 'Custo',
        cell: (row) => (
          <div>
            <p className="font-mono text-sm text-zinc-800">{formatCurrency(row.cost_per_unit)}</p>
            <p className="text-[10px] text-zinc-500">por {row.unit}</p>
          </div>
        ),
        accessor: (row) => row.cost_per_unit,
        sortable: true,
        className: 'w-28 text-right',
        headerClassName: 'text-right',
      },
      {
        key: 'stock',
        header: 'Estoque',
        cell: (row) => (
          <div>
            <p
              className={`font-mono text-sm font-medium ${
                row.low_stock ? 'text-red-600' : row.stock_quantity > 0 ? 'text-zinc-800' : 'text-zinc-400'
              }`}
            >
              {row.stock_quantity.toFixed(row.stock_quantity % 1 === 0 ? 0 : 2)} {row.unit}
            </p>
            {row.min_stock_alert > 0 && (
              <p className="text-[10px] text-zinc-500">
                Min: {row.min_stock_alert.toFixed(row.min_stock_alert % 1 === 0 ? 0 : 2)}
              </p>
            )}
            {row.low_stock && (
              <Badge className="bg-red-100 text-red-700 border border-red-200 hover:bg-red-100 text-[9px] h-4 gap-0.5 mt-0.5">
                <AlertTriangle className="h-2 w-2" />
                Baixo
              </Badge>
            )}
          </div>
        ),
        accessor: (row) => row.stock_quantity,
        sortable: true,
        className: 'w-32 text-right',
        headerClassName: 'text-right',
      },
      {
        key: 'stock_value',
        header: 'Valor estoque',
        cell: (row) => (
          <span className="font-mono text-xs text-zinc-600">
            {formatCurrency(row.stock_quantity * row.cost_per_unit)}
          </span>
        ),
        accessor: (row) => row.stock_quantity * row.cost_per_unit,
        sortable: true,
        className: 'w-32 text-right',
        headerClassName: 'text-right',
      },
      {
        key: 'usage',
        header: 'Em uso',
        cell: (row) =>
          row.product_count > 0 ? (
            <Badge className="bg-zinc-100 text-zinc-700 border border-zinc-200 hover:bg-zinc-100 text-[10px]">
              {row.product_count} produto{row.product_count === 1 ? '' : 's'}
            </Badge>
          ) : (
            <span className="text-xs text-zinc-400">—</span>
          ),
        accessor: (row) => row.product_count,
        sortable: true,
        className: 'w-28',
      },
      {
        key: 'active',
        header: 'Status',
        cell: (row) =>
          row.active ? (
            <Badge className="bg-emerald-100 text-emerald-700 border border-emerald-200 hover:bg-emerald-100">Ativo</Badge>
          ) : (
            <Badge className="bg-zinc-100 text-zinc-700 border border-zinc-200 hover:bg-zinc-100">Oculto</Badge>
          ),
        accessor: (row) => (row.active ? 1 : 0),
        sortable: true,
        className: 'w-24',
      },
      {
        key: 'actions',
        header: <span className="sr-only">Ações</span>,
        className: 'w-24 text-right',
        cell: (row) => (
          <div className="flex justify-end gap-1">
            <Button asChild size="sm" variant="ghost" className="h-8 px-2 text-zinc-600 hover:text-zinc-900">
              <a href={`${urls.edit_base}${row.id}/edit`} aria-label="Editar">
                <Pencil className="h-3.5 w-3.5" />
              </a>
            </Button>
            <Button
              size="sm"
              variant="ghost"
              className="h-8 px-2 text-red-600 hover:text-red-700 hover:bg-red-50"
              onClick={() => setConfirmDelete(row)}
              aria-label="Remover"
              disabled={row.product_count > 0}
              title={row.product_count > 0 ? 'Remova dos produtos antes de excluir' : 'Remover'}
            >
              <Trash2 className="h-3.5 w-3.5" />
            </Button>
          </div>
        ),
      },
    ],
    [urls.edit_base],
  )

  return (
    <AdminStorePageShell section="financial">
      <AdminPageHeader
        title="Insumos / Embalagens"
        description={`${supplies.length} insumo${supplies.length === 1 ? '' : 's'} cadastrado${supplies.length === 1 ? '' : 's'}. Use para calcular custo de embalagem dos produtos.`}
        icon={<Package className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <Button asChild className="gap-2">
            <a href={urls.create}>
              <Plus className="h-4 w-4" />
              Novo insumo
            </a>
          </Button>
        }
      />

      <section className="grid gap-3 sm:grid-cols-4">
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Total insumos</p>
          <p className="mt-1 text-2xl font-semibold text-zinc-800">{supplies.length}</p>
          <p className="mt-1 text-[11px] text-zinc-500">{activeCount} ativos</p>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Valor em estoque</p>
          <p className="mt-1 text-2xl font-semibold text-zinc-800">{formatCurrency(totalValue)}</p>
        </div>
        <button
          type="button"
          onClick={() => setFilter(filter === 'low_stock' ? 'all' : 'low_stock')}
          className={`text-left rounded-2xl border bg-white p-4 shadow-sm transition ${
            filter === 'low_stock' ? 'border-red-400 ring-2 ring-red-100' : 'border-zinc-200'
          } hover:border-zinc-400`}
        >
          <p className="text-xs text-zinc-500 flex items-center gap-1">
            <AlertTriangle className="h-3.5 w-3.5" />
            Estoque baixo
            {filter === 'low_stock' && (
              <Badge className="bg-red-100 text-red-700 border-red-200 hover:bg-red-100 text-[9px] h-4">filtro</Badge>
            )}
          </p>
          <p className="mt-1 text-2xl font-semibold text-red-600">{lowStockCount}</p>
        </button>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Produtos vinculados</p>
          <p className="mt-1 text-2xl font-semibold text-zinc-800">{totalProductsUsing}</p>
          <p className="mt-1 text-[11px] text-zinc-500">usos totais</p>
        </div>
      </section>

      {supplies.length === 0 ? (
        <EmptyState
          title="Sem insumos cadastrados"
          description="Cadastre insumos (caixas, sacolas, papel filme, copos descartáveis, etc.) para vinculá-los aos produtos e calcular automaticamente o custo de embalagem."
          icon={<Package className="h-5 w-5" />}
          action={
            <Button asChild className="gap-2">
              <a href={urls.create}>
                <Plus className="h-4 w-4" />
                Primeiro insumo
              </a>
            </Button>
          }
        />
      ) : (
        <>
          <div className="flex flex-wrap items-center gap-2">
            <Button
              type="button"
              size="sm"
              variant={filter === 'all' ? 'default' : 'outline'}
              onClick={() => setFilter('all')}
            >
              Todos ({supplies.length})
            </Button>
            <Button
              type="button"
              size="sm"
              variant={filter === 'active' ? 'default' : 'outline'}
              onClick={() => setFilter('active')}
            >
              Ativos ({activeCount})
            </Button>
            <Button
              type="button"
              size="sm"
              variant={filter === 'inactive' ? 'default' : 'outline'}
              onClick={() => setFilter('inactive')}
            >
              Ocultos ({supplies.length - activeCount})
            </Button>
          </div>

          <DataTable
            data={filtered}
            columns={columns}
            rowKey={(row) => row.id}
            searchPlaceholder="Buscar insumo ou fornecedor..."
            searchAccessor={(row) => `${row.name} ${row.supplier} ${row.description}`}
          />
        </>
      )}

      <ConfirmDialog
        open={!!confirmDelete}
        onOpenChange={(open) => !open && setConfirmDelete(null)}
        title="Remover insumo?"
        description={
          confirmDelete
            ? `O insumo "${confirmDelete.name}" será removido permanentemente.`
            : null
        }
        confirmLabel="Remover"
        destructive
        onConfirm={handleDelete}
      />
    </AdminStorePageShell>
  )
}
