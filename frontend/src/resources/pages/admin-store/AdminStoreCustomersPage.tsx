import { useEffect, useMemo, useState } from 'react'
import { Pencil, Phone, Plus, Trash2, UserPlus, Users } from 'lucide-react'
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

type Customer = {
  id: number
  name: string
  whatsapp: string
  whatsapp_e164: string
  email: string
  created_at: string
  total_orders: number | null
  last_order_at: string | null
  total_spent: number | null
}

type CustomersPayload = {
  customers: Customer[]
  stats: { total: number; new_this_month: number; active: number }
  filters: { q: string }
  flash: { success: string | null; error: string | null }
  urls: {
    list: string
    create: string
    edit_base: string
    destroy_base: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_CUSTOMERS__?: CustomersPayload
  }
}

function formatPhone(raw: string): string {
  const d = (raw || '').replace(/\D/g, '')
  if (d.length === 0) return ''
  if (d.length === 11) return `(${d.slice(0, 2)}) ${d.slice(2, 7)}-${d.slice(7)}`
  if (d.length === 10) return `(${d.slice(0, 2)}) ${d.slice(2, 6)}-${d.slice(6)}`
  if (d.length === 13 && d.startsWith('55')) {
    const local = d.slice(2)
    return `+55 (${local.slice(0, 2)}) ${local.slice(2, 7)}-${local.slice(7)}`
  }
  return raw
}

function formatDate(iso: string): string {
  if (!iso) return '—'
  const d = new Date(iso.replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return iso
  return d.toLocaleDateString('pt-BR')
}

export default function AdminStoreCustomersPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_CUSTOMERS__) || ({} as CustomersPayload)
  const urls = payload.urls
  const [customers, setCustomers] = useState<Customer[]>(payload.customers ?? [])
  const [confirmDelete, setConfirmDelete] = useState<Customer | null>(null)

  useEffect(() => {
    if (payload.flash?.error) showToast(payload.flash.error, 'error')
    if (payload.flash?.success) showToast(payload.flash.success, 'success')
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  const columns = useMemo<DataTableColumn<Customer>[]>(
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
              {row.name || '(sem nome)'}
            </a>
            {row.email && <p className="text-xs text-zinc-500 truncate">{row.email}</p>}
          </div>
        ),
        accessor: (row) => row.name.toLowerCase(),
        sortable: true,
      },
      {
        key: 'whatsapp',
        header: 'WhatsApp',
        cell: (row) => (
          <a
            href={`https://wa.me/${row.whatsapp_e164 || row.whatsapp.replace(/\D/g, '')}`}
            target="_blank"
            rel="noreferrer"
            className="inline-flex items-center gap-1.5 text-zinc-700 hover:text-emerald-700"
            onClick={(e) => e.stopPropagation()}
          >
            <Phone className="h-3.5 w-3.5" />
            <span className="font-mono text-xs">{formatPhone(row.whatsapp)}</span>
          </a>
        ),
        accessor: (row) => row.whatsapp_e164 || row.whatsapp,
        sortable: true,
        className: 'w-48',
      },
      {
        key: 'total_orders',
        header: 'Pedidos',
        cell: (row) => <span className="font-mono text-zinc-700">{row.total_orders ?? 0}</span>,
        accessor: (row) => row.total_orders ?? 0,
        sortable: true,
        className: 'w-20',
      },
      {
        key: 'total_spent',
        header: 'Gasto total',
        cell: (row) => <span className="text-zinc-700">{row.total_spent ? formatCurrency(row.total_spent) : '—'}</span>,
        accessor: (row) => row.total_spent ?? 0,
        sortable: true,
        className: 'w-32',
      },
      {
        key: 'created_at',
        header: 'Cadastro',
        cell: (row) => <span className="text-xs text-zinc-500">{formatDate(row.created_at)}</span>,
        accessor: (row) => row.created_at,
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

  async function handleDelete() {
    if (!confirmDelete) return
    const formData = new FormData()
    const csrf = getCsrfToken()
    if (csrf) formData.append('csrf_token', csrf)
    try {
      const res = await fetch(`${urls.destroy_base}${confirmDelete.id}/delete`, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        redirect: 'manual',
      })
      if (res.type === 'opaqueredirect' || res.ok || res.status === 302 || res.status === 0) {
        setCustomers((rows) => rows.filter((r) => r.id !== confirmDelete.id))
        showToast(`Cliente "${confirmDelete.name}" removido.`, 'success')
      } else {
        showToast('Falha ao remover cliente.', 'error')
      }
    } catch {
      setCustomers((rows) => rows.filter((r) => r.id !== confirmDelete.id))
      showToast(`Cliente "${confirmDelete.name}" removido.`, 'success')
    }
  }

  return (
    <AdminStorePageShell section="customers">
      <AdminPageHeader
        title="Clientes"
        description={`Gerencie sua base de clientes. Total cadastrado: ${payload.stats?.total ?? customers.length}.`}
        icon={<Users className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <Button asChild className="gap-2">
            <a href={urls.create}>
              <UserPlus className="h-4 w-4" />
              Novo cliente
            </a>
          </Button>
        }
      />

      <section className="grid gap-3 sm:grid-cols-3">
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Total de clientes</p>
          <p className="mt-1 text-2xl font-semibold text-zinc-800">{payload.stats?.total ?? customers.length}</p>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Novos este mês</p>
          <p className="mt-1 text-2xl font-semibold text-emerald-700">{payload.stats?.new_this_month ?? 0}</p>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Ativos</p>
          <p className="mt-1 text-2xl font-semibold text-zinc-800">{payload.stats?.active ?? 0}</p>
        </div>
      </section>

      {customers.length === 0 ? (
        <EmptyState
          title="Sem clientes ainda"
          description="Cadastre seu primeiro cliente manualmente ou aguarde clientes se cadastrarem ao fazer pedidos."
          icon={<Users className="h-5 w-5" />}
          action={
            <Button asChild className="gap-2">
              <a href={urls.create}>
                <Plus className="h-4 w-4" />
                Novo cliente
              </a>
            </Button>
          }
        />
      ) : (
        <DataTable
          data={customers}
          columns={columns}
          rowKey={(row) => row.id}
          searchPlaceholder="Buscar por nome, telefone ou e-mail..."
          searchAccessor={(row) => `${row.name} ${row.whatsapp} ${row.whatsapp_e164} ${row.email}`}
        />
      )}

      <ConfirmDialog
        open={!!confirmDelete}
        onOpenChange={(open) => !open && setConfirmDelete(null)}
        title="Remover cliente?"
        description={
          confirmDelete
            ? `O cliente "${confirmDelete.name}" será removido. Pedidos existentes serão mantidos no histórico.`
            : null
        }
        confirmLabel="Remover"
        destructive
        onConfirm={handleDelete}
      />
    </AdminStorePageShell>
  )
}
