import { useEffect, useMemo, useState } from 'react'
import { Box, ImageOff, Layers, Package, Pencil, Plus, Sparkles, Trash2 } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  AdminStorePageShell,
  AdminPageHeader,
  DataTable,
  type DataTableColumn,
  ConfirmDialog,
  EmptyState,
  showToast,
  getCsrfToken,
  formatCurrency,
  useStoreContext,
} from '@/components/admin-store'

type Product = {
  id: number
  name: string
  description: string
  sku: string
  price: number | null
  promo_price: number | null
  price_mode: 'fixed' | 'sum'
  type: 'simple' | 'combo'
  category_id: number | null
  category_name: string
  image: string
  sort_order: number
  active: boolean
  allow_customize: boolean
}

type ProductsPayload = {
  products: Product[]
  categories: Array<{ id: number; name: string }>
  stats: { total: number; active: number; inactive: number; combos: number }
  flash: { error: string | null }
  urls: {
    list: string
    create: string
    edit_base: string
    destroy_base: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_PRODUCTS__?: ProductsPayload
  }
}

function resolveImage(path: string): string {
  if (!path) return ''
  if (/^(https?:)?\/\//i.test(path) || path.startsWith('data:')) return path
  return path.startsWith('/') ? path : `/${path}`
}

export default function AdminStoreProductsPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_PRODUCTS__) || ({} as ProductsPayload)
  const urls = payload.urls

  const [products, setProducts] = useState<Product[]>(payload.products ?? [])
  const [confirmDelete, setConfirmDelete] = useState<Product | null>(null)
  const [categoryFilter, setCategoryFilter] = useState<string>('all')
  const [typeFilter, setTypeFilter] = useState<'all' | 'simple' | 'combo'>('all')
  const [statusFilter, setStatusFilter] = useState<'all' | 'active' | 'inactive'>('all')

  useEffect(() => {
    if (payload.flash?.error) showToast(payload.flash.error, 'error')
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  const filtered = useMemo(() => {
    return products.filter((p) => {
      if (categoryFilter !== 'all') {
        const target = categoryFilter === 'none' ? null : Number(categoryFilter)
        if (target === null) {
          if (p.category_id !== null) return false
        } else {
          if (p.category_id !== target) return false
        }
      }
      if (typeFilter !== 'all' && p.type !== typeFilter) return false
      if (statusFilter !== 'all') {
        if (statusFilter === 'active' && !p.active) return false
        if (statusFilter === 'inactive' && p.active) return false
      }
      return true
    })
  }, [products, categoryFilter, typeFilter, statusFilter])

  async function handleDelete() {
    if (!confirmDelete) return
    const formData = new FormData()
    const csrf = getCsrfToken()
    if (csrf) formData.append('csrf_token', csrf)

    try {
      const res = await fetch(`${urls.destroy_base}${confirmDelete.id}/del`, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        redirect: 'manual',
      })
      if (res.type === 'opaqueredirect' || res.ok || res.status === 302 || res.status === 0) {
        setProducts((rows) => rows.filter((r) => r.id !== confirmDelete.id))
        showToast(`Produto "${confirmDelete.name}" removido.`, 'success')
      } else {
        showToast('Falha ao remover produto.', 'error')
      }
    } catch {
      setProducts((rows) => rows.filter((r) => r.id !== confirmDelete.id))
      showToast(`Produto "${confirmDelete.name}" removido.`, 'success')
    }
  }

  const columns = useMemo<DataTableColumn<Product>[]>(
    () => [
      {
        key: 'image',
        header: '',
        className: 'w-12',
        cell: (row) => {
          const src = resolveImage(row.image)
          return src ? (
            <img src={src} alt="" className="h-9 w-9 rounded-md border border-zinc-200 object-cover" />
          ) : (
            <span className="flex h-9 w-9 items-center justify-center rounded-md border border-zinc-200 bg-zinc-50 text-zinc-400">
              <ImageOff className="h-4 w-4" />
            </span>
          )
        },
      },
      {
        key: 'name',
        header: 'Produto',
        cell: (row) => (
          <div className="min-w-0">
            <a
              href={`${urls.edit_base}${row.id}/edit`}
              className="font-medium text-zinc-800 hover:text-zinc-950 hover:underline truncate inline-flex items-center gap-1.5"
            >
              {row.name}
              {row.type === 'combo' && (
                <Badge className="bg-violet-100 text-violet-700 border border-violet-200 hover:bg-violet-100 text-[10px] h-4 gap-0.5">
                  <Layers className="h-2.5 w-2.5" />
                  Combo
                </Badge>
              )}
              {row.allow_customize && (
                <Badge className="bg-amber-100 text-amber-700 border border-amber-200 hover:bg-amber-100 text-[10px] h-4 gap-0.5">
                  <Sparkles className="h-2.5 w-2.5" />
                  Personalizável
                </Badge>
              )}
            </a>
            <p className="text-xs text-zinc-500 truncate">
              {row.sku ? `SKU: ${row.sku}` : 'Sem SKU'}
              {row.category_name && ` • ${row.category_name}`}
            </p>
          </div>
        ),
        accessor: (row) => row.name.toLowerCase(),
        sortable: true,
      },
      {
        key: 'price',
        header: 'Preço',
        cell: (row) => (
          <div className="text-right">
            {row.price_mode === 'sum' ? (
              <span className="text-xs text-zinc-500 italic">soma dos itens</span>
            ) : row.promo_price != null ? (
              <>
                <p className="text-xs text-zinc-400 line-through">{formatCurrency(row.price ?? 0)}</p>
                <p className="text-sm font-semibold text-emerald-600">{formatCurrency(row.promo_price)}</p>
              </>
            ) : (
              <p className="text-sm font-medium text-zinc-800">{formatCurrency(row.price ?? 0)}</p>
            )}
          </div>
        ),
        accessor: (row) => row.promo_price ?? row.price ?? 0,
        sortable: true,
        className: 'w-32 text-right',
        headerClassName: 'text-right',
      },
      {
        key: 'sort_order',
        header: 'Ordem',
        cell: (row) => <span className="font-mono text-xs text-zinc-500">{row.sort_order}</span>,
        accessor: (row) => row.sort_order,
        sortable: true,
        className: 'w-20',
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
    <AdminStorePageShell section="catalog">
      <AdminPageHeader
        title="Produtos"
        description={`Gerencie seu catálogo. Total: ${products.length} produto${products.length === 1 ? '' : 's'}.`}
        icon={<Package className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <Button asChild className="gap-2">
            <a href={urls.create}>
              <Plus className="h-4 w-4" />
              Novo produto
            </a>
          </Button>
        }
      />

      <section className="grid gap-3 sm:grid-cols-4">
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Total</p>
          <p className="mt-1 text-2xl font-semibold text-zinc-800">{payload.stats?.total ?? products.length}</p>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Ativos</p>
          <p className="mt-1 text-2xl font-semibold text-emerald-600">{payload.stats?.active ?? 0}</p>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Ocultos</p>
          <p className="mt-1 text-2xl font-semibold text-zinc-500">{payload.stats?.inactive ?? 0}</p>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Combos</p>
          <p className="mt-1 text-2xl font-semibold text-violet-600">{payload.stats?.combos ?? 0}</p>
        </div>
      </section>

      <section className="flex flex-wrap items-end gap-3 rounded-2xl border border-zinc-200 bg-white p-3 shadow-sm">
        <div className="min-w-[180px]">
          <label className="mb-1 block text-xs font-medium text-zinc-600">Categoria</label>
          <select
            value={categoryFilter}
            onChange={(e) => setCategoryFilter(e.target.value)}
            className="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
          >
            <option value="all">Todas</option>
            <option value="none">Sem categoria</option>
            {payload.categories?.map((c) => (
              <option key={c.id} value={c.id}>
                {c.name}
              </option>
            ))}
          </select>
        </div>
        <div className="min-w-[140px]">
          <label className="mb-1 block text-xs font-medium text-zinc-600">Tipo</label>
          <select
            value={typeFilter}
            onChange={(e) => setTypeFilter(e.target.value as 'all' | 'simple' | 'combo')}
            className="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
          >
            <option value="all">Todos</option>
            <option value="simple">Simples</option>
            <option value="combo">Combo</option>
          </select>
        </div>
        <div className="min-w-[140px]">
          <label className="mb-1 block text-xs font-medium text-zinc-600">Status</label>
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value as 'all' | 'active' | 'inactive')}
            className="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
          >
            <option value="all">Todos</option>
            <option value="active">Apenas ativos</option>
            <option value="inactive">Apenas ocultos</option>
          </select>
        </div>
        {(categoryFilter !== 'all' || typeFilter !== 'all' || statusFilter !== 'all') && (
          <Button
            type="button"
            size="sm"
            variant="ghost"
            className="h-9 text-xs"
            onClick={() => {
              setCategoryFilter('all')
              setTypeFilter('all')
              setStatusFilter('all')
            }}
          >
            Limpar filtros
          </Button>
        )}
        <span className="ml-auto text-xs text-zinc-500">
          {filtered.length} de {products.length} {filtered.length === 1 ? 'produto' : 'produtos'}
        </span>
      </section>

      {products.length === 0 ? (
        <EmptyState
          title="Sem produtos ainda"
          description="Cadastre seu primeiro produto para começar a vender no cardápio digital."
          icon={<Box className="h-5 w-5" />}
          action={
            <Button asChild className="gap-2">
              <a href={urls.create}>
                <Plus className="h-4 w-4" />
                Novo produto
              </a>
            </Button>
          }
        />
      ) : filtered.length === 0 ? (
        <EmptyState
          title="Nenhum produto com esses filtros"
          description="Ajuste a categoria, tipo ou status para ver mais resultados."
          icon={<Box className="h-5 w-5" />}
        />
      ) : (
        <DataTable
          data={filtered}
          columns={columns}
          rowKey={(row) => row.id}
          searchPlaceholder="Buscar por nome, SKU ou descrição..."
          searchAccessor={(row) => `${row.name} ${row.sku} ${row.description} ${row.category_name}`}
        />
      )}

      <ConfirmDialog
        open={!!confirmDelete}
        onOpenChange={(open) => !open && setConfirmDelete(null)}
        title="Remover produto?"
        description={
          confirmDelete
            ? `O produto "${confirmDelete.name}" será removido. Pedidos existentes mantêm o histórico desse item.`
            : null
        }
        confirmLabel="Remover"
        destructive
        onConfirm={handleDelete}
      />
    </AdminStorePageShell>
  )
}
