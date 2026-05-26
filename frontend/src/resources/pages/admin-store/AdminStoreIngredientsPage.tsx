import { useEffect, useMemo, useState } from 'react'
import { ImageOff, Pencil, Plus, Trash2, Utensils } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
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

type Ingredient = {
  id: number
  name: string
  internal_name: string
  cost: number | null
  sale_price: number | null
  unit: string
  unit_value: number | null
  image_path: string
  active: boolean
}

type IngredientsPayload = {
  ingredients: Ingredient[]
  products: Array<{ id: number; name: string }>
  filters: { product_id: number | null; q: string | null }
  flash: { error: string | null; success: string | null }
  urls: {
    list: string
    create: string
    edit_base: string
    destroy_base: string
    toggle_base: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_INGREDIENTS__?: IngredientsPayload
  }
}

const UNIT_LABEL: Record<string, string> = {
  un: 'un',
  kg: 'kg',
  g: 'g',
  mg: 'mg',
  l: 'L',
  ml: 'mL',
  pc: 'pc',
}

function formatUnit(unit: string, unitValue: number | null): string {
  if (!unit) return '—'
  const label = UNIT_LABEL[unit.toLowerCase()] ?? unit
  if (unitValue == null) return label
  const value = Number.isInteger(unitValue) ? String(unitValue) : unitValue.toFixed(3).replace(/\.?0+$/, '').replace('.', ',')
  return `${value} ${label}`
}

function resolveImage(path: string): string {
  if (!path) return ''
  if (/^(https?:)?\/\//i.test(path) || path.startsWith('data:')) return path
  return path.startsWith('/') ? path : `/${path}`
}

export default function AdminStoreIngredientsPage() {
  const ctx = useStoreContext()
  const payload = (typeof window !== 'undefined' && window.__ADMIN_STORE_INGREDIENTS__) || ({} as IngredientsPayload)
  const urls = payload.urls

  const [ingredients, setIngredients] = useState<Ingredient[]>(payload.ingredients ?? [])
  const [confirmDelete, setConfirmDelete] = useState<Ingredient | null>(null)
  const [toggling, setToggling] = useState<number | null>(null)

  // Show flash messages on mount
  useEffect(() => {
    if (payload.flash?.error) showToast(payload.flash.error, 'error')
    if (payload.flash?.success) showToast(payload.flash.success, 'success')
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  async function handleToggle(row: Ingredient) {
    setToggling(row.id)
    try {
      const formData = new FormData()
      const csrf = getCsrfToken()
      if (csrf) formData.append('csrf_token', csrf)

      const res = await fetch(`${urls.toggle_base}${row.id}/toggle`, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean; active?: boolean; message?: string } | null
      if (data?.success) {
        setIngredients((rows) => rows.map((r) => (r.id === row.id ? { ...r, active: !!data.active } : r)))
        showToast(data.message || (data.active ? 'Ingrediente ativado.' : 'Ingrediente ocultado.'), 'success')
      } else {
        showToast(data?.message || 'Falha ao atualizar status.', 'error')
      }
    } catch {
      showToast('Falha de rede ao atualizar status.', 'error')
    } finally {
      setToggling(null)
    }
  }

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
        setIngredients((rows) => rows.filter((r) => r.id !== confirmDelete.id))
        showToast(`Ingrediente "${confirmDelete.name}" removido.`, 'success')
      } else {
        showToast('Falha ao remover ingrediente.', 'error')
      }
    } catch {
      setIngredients((rows) => rows.filter((r) => r.id !== confirmDelete.id))
      showToast(`Ingrediente "${confirmDelete.name}" removido.`, 'success')
    }
  }

  const columns = useMemo<DataTableColumn<Ingredient>[]>(
    () => [
      {
        key: 'image',
        header: '',
        className: 'w-12',
        cell: (row) => {
          const src = resolveImage(row.image_path)
          if (src) {
            return <img src={src} alt="" className="h-9 w-9 rounded-md border border-zinc-200 object-cover" />
          }
          return (
            <span className="flex h-9 w-9 items-center justify-center rounded-md border border-zinc-200 bg-zinc-50 text-zinc-400">
              <ImageOff className="h-4 w-4" />
            </span>
          )
        },
      },
      {
        key: 'name',
        header: 'Nome',
        cell: (row) => (
          <div className="min-w-0">
            <a
              href={`${urls.edit_base}${row.id}/edit`}
              className="font-medium text-zinc-800 hover:text-zinc-950 hover:underline"
            >
              {row.name}
            </a>
            {row.internal_name && <p className="text-xs text-zinc-500 truncate">{row.internal_name}</p>}
          </div>
        ),
        accessor: (row) => row.name.toLowerCase(),
        sortable: true,
      },
      {
        key: 'cost',
        header: 'Custo',
        cell: (row) => <span className="text-zinc-700">{row.cost != null ? formatCurrency(row.cost) : '—'}</span>,
        accessor: (row) => row.cost ?? 0,
        sortable: true,
        className: 'w-28',
      },
      {
        key: 'sale_price',
        header: 'Venda',
        cell: (row) => <span className="text-zinc-700">{row.sale_price != null ? formatCurrency(row.sale_price) : '—'}</span>,
        accessor: (row) => row.sale_price ?? 0,
        sortable: true,
        className: 'w-28',
      },
      {
        key: 'unit',
        header: 'Unidade',
        cell: (row) => <span className="text-zinc-600 font-mono text-xs">{formatUnit(row.unit, row.unit_value)}</span>,
        className: 'w-28',
      },
      {
        key: 'active',
        header: 'Status',
        cell: (row) => (
          <button
            type="button"
            onClick={() => handleToggle(row)}
            disabled={toggling === row.id}
            className="inline-flex items-center disabled:opacity-50"
          >
            {row.active ? (
              <Badge className="bg-emerald-100 text-emerald-700 border border-emerald-200 hover:bg-emerald-200">Ativo</Badge>
            ) : (
              <Badge className="bg-zinc-100 text-zinc-700 border border-zinc-200 hover:bg-zinc-200">Oculto</Badge>
            )}
          </button>
        ),
        className: 'w-28',
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
    [urls.edit_base, toggling],
  )

  return (
    <AdminStorePageShell section="catalog">
      <AdminPageHeader
        title="Ingredientes"
        description={`Cadastre os ingredientes vinculados aos seus produtos. Total: ${ingredients.length}.`}
        icon={<Utensils className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <Button asChild className="gap-2">
            <a href={urls.create}>
              <Plus className="h-4 w-4" />
              Novo ingrediente
            </a>
          </Button>
        }
      />

      {ingredients.length === 0 ? (
        <EmptyState
          title="Sem ingredientes ainda"
          description="Cadastre seu primeiro ingrediente para começar a montar produtos personalizáveis."
          icon={<Utensils className="h-5 w-5" />}
          action={
            <Button asChild>
              <a href={urls.create}>Novo ingrediente</a>
            </Button>
          }
        />
      ) : (
        <DataTable
          data={ingredients}
          columns={columns}
          rowKey={(row) => row.id}
          searchPlaceholder="Buscar por nome..."
          searchAccessor={(row) => `${row.name} ${row.internal_name}`}
        />
      )}

      <ConfirmDialog
        open={!!confirmDelete}
        onOpenChange={(open) => !open && setConfirmDelete(null)}
        title="Remover ingrediente?"
        description={
          confirmDelete
            ? `O ingrediente "${confirmDelete.name}" será removido. Produtos que o utilizam podem ser afetados.`
            : null
        }
        confirmLabel="Remover"
        destructive
        onConfirm={handleDelete}
      />
    </AdminStorePageShell>
  )
}
