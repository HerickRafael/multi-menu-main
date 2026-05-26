import { useEffect, useMemo, useState } from 'react'
import {
  CalendarDays,
  CircleDollarSign,
  Pencil,
  Plus,
  Receipt,
  Tag,
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

type Expense = {
  id: number
  category_id: number | null
  category_name: string
  category_type: string
  description: string
  amount: number
  expense_date: string
  reference_month: string
  payment_method: string
  notes: string
}

type Payload = {
  month: string
  month_label: string
  available_months: Array<{ value: string; label: string }>
  expenses: Expense[]
  summary: { total: number; fixed_total: number; variable_total: number; count: number }
  categories: Array<{ id: number; name: string; type: string; description: string }>
  flash: { success: string | null; error: string | null }
  urls: {
    list: string
    create: string
    store: string
    edit_base: string
    destroy_base: string
    categories: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_EXPENSES__?: Payload
  }
}

function formatDateBR(iso: string): string {
  if (!iso) return ''
  const d = new Date(iso.replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return iso
  return d.toLocaleDateString('pt-BR')
}

export default function AdminStoreExpensesPage() {
  const ctx = useStoreContext()
  const payload = (typeof window !== 'undefined' && window.__ADMIN_STORE_EXPENSES__) || ({} as Payload)
  const urls = payload.urls
  const [expenses, setExpenses] = useState<Expense[]>(payload.expenses ?? [])
  const [confirmDelete, setConfirmDelete] = useState<Expense | null>(null)
  const [filterType, setFilterType] = useState<'all' | 'fixed' | 'variable'>('all')

  useEffect(() => {
    if (payload.flash?.error) showToast(payload.flash.error, 'error')
    if (payload.flash?.success) {
      const msg =
        payload.flash.success === 'created'
          ? 'Despesa criada com sucesso!'
          : payload.flash.success === 'updated'
            ? 'Despesa atualizada!'
            : payload.flash.success === 'deleted'
              ? 'Despesa removida!'
              : 'Operação realizada.'
      showToast(msg, 'success')
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  function changeMonth(month: string) {
    if (typeof window !== 'undefined') {
      const url = new URL(window.location.href)
      url.searchParams.set('month', month)
      window.location.href = url.toString()
    }
  }

  async function handleDelete() {
    if (!confirmDelete) return
    const fd = new FormData()
    fd.append('csrf_token', getCsrfToken())
    try {
      const res = await fetch(`${urls.destroy_base}${confirmDelete.id}/delete`, {
        method: 'GET',
        credentials: 'same-origin',
        redirect: 'manual',
      })
      if (res.type === 'opaqueredirect' || res.ok || res.status === 302 || res.status === 0) {
        setExpenses((prev) => prev.filter((e) => e.id !== confirmDelete.id))
        showToast('Despesa removida.', 'success')
      } else {
        showToast('Falha ao remover.', 'error')
      }
    } catch {
      setExpenses((prev) => prev.filter((e) => e.id !== confirmDelete.id))
      showToast('Despesa removida.', 'success')
    }
  }

  const filtered = useMemo(() => {
    if (filterType === 'all') return expenses
    return expenses.filter((e) => e.category_type === filterType)
  }, [expenses, filterType])

  const columns = useMemo<DataTableColumn<Expense>[]>(
    () => [
      {
        key: 'description',
        header: 'Descrição',
        cell: (row) => (
          <div className="min-w-0">
            <a
              href={`${urls.edit_base}${row.id}/edit`}
              className="font-medium text-zinc-800 hover:text-zinc-950 hover:underline truncate block"
            >
              {row.description}
            </a>
            {row.payment_method && <p className="text-xs text-zinc-500 truncate">{row.payment_method}</p>}
          </div>
        ),
        accessor: (row) => row.description.toLowerCase(),
        sortable: true,
      },
      {
        key: 'category',
        header: 'Categoria',
        cell: (row) => (
          <Badge
            className={`${
              row.category_type === 'fixed'
                ? 'bg-indigo-100 text-indigo-700 border-indigo-200'
                : 'bg-amber-100 text-amber-800 border-amber-200'
            } border text-[10px] h-5`}
          >
            <Tag className="h-2.5 w-2.5 mr-1" />
            {row.category_name || 'Sem categoria'}
          </Badge>
        ),
        accessor: (row) => row.category_name.toLowerCase(),
        sortable: true,
        className: 'w-48',
      },
      {
        key: 'date',
        header: 'Data',
        cell: (row) => <span className="text-xs text-zinc-600">{formatDateBR(row.expense_date)}</span>,
        accessor: (row) => row.expense_date,
        sortable: true,
        className: 'w-28',
      },
      {
        key: 'amount',
        header: 'Valor',
        cell: (row) => <span className="font-semibold text-red-600">−{formatCurrency(row.amount)}</span>,
        accessor: (row) => row.amount,
        sortable: true,
        className: 'w-32 text-right',
        headerClassName: 'text-right',
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
    <AdminStorePageShell section="financial">
      <AdminPageHeader
        title="Despesas"
        description={`${payload.month_label} · ${payload.summary?.count ?? 0} despesa${(payload.summary?.count ?? 0) === 1 ? '' : 's'} registrada${(payload.summary?.count ?? 0) === 1 ? '' : 's'}.`}
        icon={<Receipt className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <div className="flex flex-wrap items-center gap-2">
            <select
              value={payload.month}
              onChange={(e) => changeMonth(e.target.value)}
              className="flex h-9 rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
            >
              {payload.available_months?.map((m) => (
                <option key={m.value} value={m.value}>
                  {m.label}
                </option>
              ))}
            </select>
            <Button asChild variant="outline" size="sm" className="gap-1.5">
              <a href={urls.categories}>
                <Tag className="h-3.5 w-3.5" />
                Categorias
              </a>
            </Button>
            <Button asChild className="gap-2">
              <a href={urls.create}>
                <Plus className="h-4 w-4" />
                Nova despesa
              </a>
            </Button>
          </div>
        }
      />

      <section className="grid gap-3 sm:grid-cols-3">
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Total do mês</p>
          <p className="mt-1 text-2xl font-semibold text-red-600">−{formatCurrency(payload.summary?.total ?? 0)}</p>
        </div>
        <button
          type="button"
          onClick={() => setFilterType(filterType === 'fixed' ? 'all' : 'fixed')}
          className={`text-left rounded-2xl border bg-white p-4 shadow-sm transition ${
            filterType === 'fixed' ? 'border-indigo-400 ring-2 ring-indigo-100' : 'border-zinc-200'
          } hover:border-zinc-400`}
        >
          <p className="text-xs text-zinc-500 flex items-center gap-1">
            Despesas fixas
            {filterType === 'fixed' && <Badge className="bg-indigo-100 text-indigo-700 border-indigo-200 hover:bg-indigo-100 text-[9px] h-4">filtro</Badge>}
          </p>
          <p className="mt-1 text-2xl font-semibold text-indigo-600">{formatCurrency(payload.summary?.fixed_total ?? 0)}</p>
        </button>
        <button
          type="button"
          onClick={() => setFilterType(filterType === 'variable' ? 'all' : 'variable')}
          className={`text-left rounded-2xl border bg-white p-4 shadow-sm transition ${
            filterType === 'variable' ? 'border-amber-400 ring-2 ring-amber-100' : 'border-zinc-200'
          } hover:border-zinc-400`}
        >
          <p className="text-xs text-zinc-500 flex items-center gap-1">
            Despesas variáveis
            {filterType === 'variable' && <Badge className="bg-amber-100 text-amber-800 border-amber-200 hover:bg-amber-100 text-[9px] h-4">filtro</Badge>}
          </p>
          <p className="mt-1 text-2xl font-semibold text-amber-600">{formatCurrency(payload.summary?.variable_total ?? 0)}</p>
        </button>
      </section>

      {expenses.length === 0 ? (
        <EmptyState
          title="Sem despesas neste mês"
          description="Registre despesas fixas (aluguel, energia, etc.) e variáveis (matéria-prima, manutenção) para acompanhar a saúde financeira da loja."
          icon={<Receipt className="h-5 w-5" />}
          action={
            <Button asChild className="gap-2">
              <a href={urls.create}>
                <Plus className="h-4 w-4" />
                Primeira despesa
              </a>
            </Button>
          }
        />
      ) : (
        <DataTable
          data={filtered}
          columns={columns}
          rowKey={(row) => row.id}
          searchPlaceholder="Buscar descrição ou categoria..."
          searchAccessor={(row) => `${row.description} ${row.category_name} ${row.payment_method}`}
        />
      )}

      <ConfirmDialog
        open={!!confirmDelete}
        onOpenChange={(open) => !open && setConfirmDelete(null)}
        title="Remover despesa?"
        description={
          confirmDelete
            ? `A despesa "${confirmDelete.description}" (${formatCurrency(confirmDelete.amount)}) será removida.`
            : null
        }
        confirmLabel="Remover"
        destructive
        onConfirm={handleDelete}
      />
    </AdminStorePageShell>
  )
}
