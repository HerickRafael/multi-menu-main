import { useEffect, useMemo, useState } from 'react'
import { Layers, Pencil, Plus, Sparkles, Trash2, Wand2 } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  AdminStorePageShell,
  AdminPageHeader,
  ConfirmDialog,
  DataTable,
  type DataTableColumn,
  EmptyState,
  showToast,
  getCsrfToken,
  useStoreContext,
} from '@/components/admin-store'

type Template = {
  id: number
  name: string
  type: 'single' | 'extra' | 'pool'
  min_qty: number
  max_qty: number
  active: boolean
  hide_duplicates: boolean
  items_count: number
  usage_count: number
  updated_at: string
}

type Payload = {
  templates: Template[]
  flash: { error: string | null; success: string | null }
  urls: {
    list: string
    create: string
    edit_base: string
    toggle_base: string
    destroy_base: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_CT_LIST__?: Payload
  }
}

const TYPE_LABELS: Record<Template['type'], { label: string; color: string }> = {
  single: { label: 'Escolha única', color: 'bg-blue-100 text-blue-700 border-blue-200' },
  extra: { label: 'Adicionais', color: 'bg-amber-100 text-amber-800 border-amber-200' },
  pool: { label: 'Pool', color: 'bg-violet-100 text-violet-700 border-violet-200' },
}

export default function AdminStoreCustomizationTemplatesPage() {
  const ctx = useStoreContext()
  const payload = (typeof window !== 'undefined' && window.__ADMIN_STORE_CT_LIST__) || ({} as Payload)
  const urls = payload.urls
  const [templates, setTemplates] = useState<Template[]>(payload.templates ?? [])
  const [confirmDelete, setConfirmDelete] = useState<Template | null>(null)
  const [toggling, setToggling] = useState<number | null>(null)

  useEffect(() => {
    if (payload.flash?.error) showToast(payload.flash.error, 'error')
    if (payload.flash?.success) showToast(payload.flash.success, 'success')
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  async function handleToggle(t: Template) {
    setToggling(t.id)
    try {
      const formData = new FormData()
      formData.append('csrf_token', getCsrfToken())
      const res = await fetch(`${urls.toggle_base}${t.id}/toggle`, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean; error?: string } | null
      if (data?.success) {
        setTemplates((prev) => prev.map((row) => (row.id === t.id ? { ...row, active: !row.active } : row)))
        showToast(`Template ${t.active ? 'desativado' : 'ativado'}.`, 'success')
      } else {
        showToast(data?.error || 'Falha ao alternar status.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setToggling(null)
    }
  }

  async function handleDelete() {
    if (!confirmDelete) return
    const formData = new FormData()
    formData.append('csrf_token', getCsrfToken())
    try {
      const res = await fetch(`${urls.destroy_base}${confirmDelete.id}/del`, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        redirect: 'manual',
      })
      if (res.type === 'opaqueredirect' || res.ok || res.status === 302 || res.status === 0) {
        setTemplates((prev) => prev.filter((r) => r.id !== confirmDelete.id))
        showToast(`Template "${confirmDelete.name}" removido.`, 'success')
      } else {
        showToast('Falha ao remover template.', 'error')
      }
    } catch {
      setTemplates((prev) => prev.filter((r) => r.id !== confirmDelete.id))
      showToast(`Template "${confirmDelete.name}" removido.`, 'success')
    }
  }

  const columns = useMemo<DataTableColumn<Template>[]>(
    () => [
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
            <p className="text-xs text-zinc-500">{row.items_count} item{row.items_count === 1 ? '' : 's'}</p>
          </div>
        ),
        accessor: (row) => row.name.toLowerCase(),
        sortable: true,
      },
      {
        key: 'type',
        header: 'Tipo',
        cell: (row) => {
          const t = TYPE_LABELS[row.type] || { label: row.type, color: 'bg-zinc-100 text-zinc-700 border-zinc-200' }
          return (
            <Badge className={`${t.color} border hover:${t.color.split(' ').slice(0, 2).join(' ')}`}>
              {t.label}
            </Badge>
          )
        },
        className: 'w-32',
      },
      {
        key: 'qty',
        header: 'Min/Max',
        cell: (row) => (
          <span className="font-mono text-xs text-zinc-600">
            {row.min_qty} / {row.max_qty}
          </span>
        ),
        className: 'w-24',
      },
      {
        key: 'usage',
        header: 'Em uso',
        cell: (row) => (
          <span className="text-zinc-700">
            {row.usage_count === 0 ? (
              <span className="text-zinc-400">—</span>
            ) : (
              <Badge className="bg-zinc-100 text-zinc-700 border border-zinc-200 hover:bg-zinc-100">
                {row.usage_count} produto{row.usage_count === 1 ? '' : 's'}
              </Badge>
            )}
          </span>
        ),
        accessor: (row) => row.usage_count,
        sortable: true,
        className: 'w-32',
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
              <Badge className="bg-emerald-100 text-emerald-700 border border-emerald-200 hover:bg-emerald-200">
                Ativo
              </Badge>
            ) : (
              <Badge className="bg-zinc-100 text-zinc-700 border border-zinc-200 hover:bg-zinc-200">
                Oculto
              </Badge>
            )}
          </button>
        ),
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
              disabled={row.usage_count > 0}
              title={row.usage_count > 0 ? 'Remova dos produtos antes de excluir' : 'Remover'}
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
        title="Grupos de Personalização"
        description="Templates reutilizáveis de grupos de ingredientes — aplique vários produtos sem refazer a configuração."
        icon={<Wand2 className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <Button asChild className="gap-2">
            <a href={urls.create}>
              <Plus className="h-4 w-4" />
              Novo template
            </a>
          </Button>
        }
      />

      {templates.length === 0 ? (
        <EmptyState
          title="Sem templates cadastrados"
          description="Crie um template de personalização e aplique a vários produtos. Útil para grupos repetidos como 'Adicionais clássicos' ou 'Pães disponíveis'."
          icon={<Sparkles className="h-5 w-5" />}
          action={
            <Button asChild className="gap-2">
              <a href={urls.create}>
                <Plus className="h-4 w-4" />
                Criar primeiro template
              </a>
            </Button>
          }
        />
      ) : (
        <DataTable
          data={templates}
          columns={columns}
          rowKey={(row) => row.id}
          searchPlaceholder="Buscar templates..."
          searchAccessor={(row) => row.name}
        />
      )}

      <ConfirmDialog
        open={!!confirmDelete}
        onOpenChange={(open) => !open && setConfirmDelete(null)}
        title="Remover template?"
        description={
          confirmDelete
            ? `O template "${confirmDelete.name}" será removido permanentemente.`
            : null
        }
        confirmLabel="Remover"
        destructive
        onConfirm={handleDelete}
      />
    </AdminStorePageShell>
  )
}
