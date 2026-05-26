import { useMemo, useState } from 'react'
import { Pencil, Plus, Tag, Trash2 } from 'lucide-react'
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
  useStoreContext,
} from '@/components/admin-store'

type Category = {
  id: number
  name: string
  sort_order: number
  active: boolean
}

type CategoriesPayload = {
  categories: Category[]
  show_most_ordered: boolean
  urls: {
    list: string
    create: string
    store: string
    edit_base: string
    update_base: string
    destroy_base: string
    most_ordered: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_CATEGORIES__?: CategoriesPayload
  }
}

export default function AdminStoreCategoriesPage() {
  const ctx = useStoreContext()
  const payload = (typeof window !== 'undefined' && window.__ADMIN_STORE_CATEGORIES__) || ({} as CategoriesPayload)
  const urls = payload.urls
  const [categories, setCategories] = useState<Category[]>(payload.categories ?? [])
  const [confirmDelete, setConfirmDelete] = useState<Category | null>(null)
  const [showMostOrdered, setShowMostOrdered] = useState<boolean>(!!payload.show_most_ordered)

  const columns = useMemo<DataTableColumn<Category>[]>(
    () => [
      {
        key: 'sort_order',
        header: 'Ordem',
        cell: (row) => <span className="font-mono text-zinc-500">{row.sort_order}</span>,
        accessor: (row) => row.sort_order,
        sortable: true,
        className: 'w-20',
      },
      {
        key: 'name',
        header: 'Nome',
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
        key: 'active',
        header: 'Status',
        cell: (row) =>
          row.active ? (
            <Badge className="bg-emerald-100 text-emerald-700 border border-emerald-200 hover:bg-emerald-100">Ativa</Badge>
          ) : (
            <Badge className="bg-zinc-100 text-zinc-700 border border-zinc-200 hover:bg-zinc-100">Inativa</Badge>
          ),
        accessor: (row) => (row.active ? 1 : 0),
        sortable: true,
        className: 'w-32',
      },
      {
        key: 'actions',
        header: <span className="sr-only">Ações</span>,
        className: 'w-32 text-right',
        cell: (row) => (
          <div className="flex justify-end gap-1">
            <Button
              asChild
              size="sm"
              variant="ghost"
              className="h-8 px-2 text-zinc-600 hover:text-zinc-900"
            >
              <a href={`${urls.edit_base}${row.id}/edit`}>
                <Pencil className="h-3.5 w-3.5" />
              </a>
            </Button>
            <Button
              size="sm"
              variant="ghost"
              className="h-8 px-2 text-red-600 hover:text-red-700 hover:bg-red-50"
              onClick={() => setConfirmDelete(row)}
            >
              <Trash2 className="h-3.5 w-3.5" />
            </Button>
          </div>
        ),
      },
    ],
    [urls.edit_base],
  )

  async function handleDelete() {
    if (!confirmDelete) return
    const formData = new FormData()
    const csrf = getCsrfToken()
    if (csrf) formData.append('csrf_token', csrf)

    try {
      const response = await fetch(`${urls.destroy_base}${confirmDelete.id}/del`, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        redirect: 'manual',
      })
      // PHP responds with redirect — treat any non-error as success
      if (response.type === 'opaqueredirect' || response.ok || response.status === 302 || response.status === 0) {
        setCategories((rows) => rows.filter((r) => r.id !== confirmDelete.id))
        showToast(`Categoria "${confirmDelete.name}" removida.`, 'success')
      } else {
        showToast('Falha ao remover categoria.', 'error')
      }
    } catch {
      // opaqueredirect resolves but no body — also treat as success
      setCategories((rows) => rows.filter((r) => r.id !== confirmDelete.id))
      showToast(`Categoria "${confirmDelete.name}" removida.`, 'success')
    }
  }

  async function handleToggleMostOrdered(value: boolean) {
    setShowMostOrdered(value)
    const formData = new FormData()
    formData.append('show_most_ordered', value ? '1' : '0')
    const csrf = getCsrfToken()
    if (csrf) formData.append('csrf_token', csrf)

    try {
      await fetch(urls.most_ordered, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        redirect: 'manual',
      })
      showToast(value ? 'Seção "Mais pedidos" ativada.' : 'Seção "Mais pedidos" desativada.', 'success')
    } catch {
      showToast('Falha ao atualizar preferência.', 'error')
      setShowMostOrdered(!value)
    }
  }

  return (
    <AdminStorePageShell section="categories">
      <AdminPageHeader
        title="Categorias"
        description={`Organize seu cardápio em grupos. Total: ${categories.length}.`}
        icon={<Tag className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <Button asChild className="gap-2">
            <a href={urls.create}>
              <Plus className="h-4 w-4" />
              Nova categoria
            </a>
          </Button>
        }
      />

      <section className="flex items-center justify-between gap-3 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
        <div>
          <p className="text-sm font-semibold text-zinc-800">Mostrar "Mais pedidos" no cardápio</p>
          <p className="text-xs text-zinc-500">Exibe automaticamente os produtos mais vendidos como uma categoria virtual.</p>
        </div>
        <label className="relative inline-flex cursor-pointer items-center">
          <input
            type="checkbox"
            checked={showMostOrdered}
            onChange={(e) => handleToggleMostOrdered(e.target.checked)}
            className="peer sr-only"
          />
          <span className="h-5 w-9 rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500" />
          <span className="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
        </label>
      </section>

      {categories.length === 0 ? (
        <EmptyState
          title="Sem categorias ainda"
          description="Crie a primeira categoria para começar a organizar seu cardápio."
          icon={<Tag className="h-5 w-5" />}
          action={
            <Button asChild>
              <a href={urls.create}>Nova categoria</a>
            </Button>
          }
        />
      ) : (
        <DataTable
          data={categories}
          columns={columns}
          rowKey={(row) => row.id}
          searchPlaceholder="Buscar categorias..."
          searchAccessor={(row) => row.name}
        />
      )}

      <ConfirmDialog
        open={!!confirmDelete}
        onOpenChange={(open) => !open && setConfirmDelete(null)}
        title="Remover categoria?"
        description={
          confirmDelete
            ? `A categoria "${confirmDelete.name}" será removida permanentemente. Produtos vinculados podem ficar sem categoria.`
            : null
        }
        confirmLabel="Remover"
        destructive
        onConfirm={handleDelete}
      />
    </AdminStorePageShell>
  )
}
