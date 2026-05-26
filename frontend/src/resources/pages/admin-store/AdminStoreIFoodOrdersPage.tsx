import { useMemo, useState } from 'react'
import {
  Bike,
  CheckCheck,
  CheckCircle2,
  Clock,
  Eye,
  Loader2,
  MoreVertical,
  Package,
  PackageOpen,
  RefreshCw,
  Settings as SettingsIcon,
  ShoppingBag,
  Truck,
  Utensils,
  XCircle,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  AdminPageHeader,
  AdminStorePageShell,
  DataTable,
  EmptyState,
  formatCurrency,
  getCsrfToken,
  showToast,
  type DataTableColumn,
  useStoreContext,
} from '@/components/admin-store'

type IFoodOrder = {
  id: number
  ifood_order_id: string
  ifood_display_id: string
  local_order_id: number | null
  status: string
  order_type: string
  delivered_by: string
  customer_name: string
  customer_phone: string
  total_amount: number
  created_at: string
}

type Payload = {
  orders: IFoodOrder[]
  current_status: string | null
  urls: {
    list: string
    detail_base: string
    config: string
    poll: string
    confirm_base: string
    local_order_base: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_IFOOD_ORDERS__?: Payload
  }
}

const STATUS_FILTERS: Array<{ value: string; label: string; tone: string }> = [
  { value: 'PLACED', label: 'Novo', tone: 'bg-amber-100 text-amber-700 border-amber-200' },
  { value: 'CONFIRMED', label: 'Confirmado', tone: 'bg-blue-100 text-blue-700 border-blue-200' },
  { value: 'READY_TO_PICKUP', label: 'Pronto', tone: 'bg-indigo-100 text-indigo-700 border-indigo-200' },
  { value: 'DISPATCHED', label: 'Em Entrega', tone: 'bg-purple-100 text-purple-700 border-purple-200' },
  { value: 'CONCLUDED', label: 'Concluído', tone: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
  { value: 'CANCELLED', label: 'Cancelado', tone: 'bg-red-100 text-red-700 border-red-200' },
]

const STATUS_BADGE: Record<string, { label: string; cls: string; icon: typeof Clock }> = {
  PLACED: { label: 'Novo', cls: 'bg-amber-100 text-amber-700 border-amber-200', icon: Clock },
  CONFIRMED: { label: 'Confirmado', cls: 'bg-blue-100 text-blue-700 border-blue-200', icon: CheckCircle2 },
  READY_TO_PICKUP: { label: 'Pronto', cls: 'bg-indigo-100 text-indigo-700 border-indigo-200', icon: Package },
  DISPATCHED: { label: 'Em Entrega', cls: 'bg-purple-100 text-purple-700 border-purple-200', icon: Bike },
  CONCLUDED: { label: 'Concluído', cls: 'bg-emerald-100 text-emerald-700 border-emerald-200', icon: CheckCheck },
  CANCELLED: { label: 'Cancelado', cls: 'bg-red-100 text-red-700 border-red-200', icon: XCircle },
}

function formatDateBr(s: string): string {
  if (!s) return '—'
  const d = new Date(s.replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return s
  const dd = String(d.getDate()).padStart(2, '0')
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const hh = String(d.getHours()).padStart(2, '0')
  const mi = String(d.getMinutes()).padStart(2, '0')
  return `${dd}/${mm} ${hh}:${mi}`
}

export default function AdminStoreIFoodOrdersPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_IFOOD_ORDERS__) || ({} as Payload)
  const urls = payload.urls
  const initial = payload.orders || []
  const currentStatus = payload.current_status || null

  const [orders] = useState<IFoodOrder[]>(initial)
  const [polling, setPolling] = useState(false)
  const [openMenu, setOpenMenu] = useState<number | null>(null)
  const [busyAction, setBusyAction] = useState<string | null>(null)

  async function pollEvents() {
    setPolling(true)
    try {
      const res = await fetch(urls.poll, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-Token': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
      })
      const data = (await res.json().catch(() => null)) as
        | { success?: boolean; events?: number; processed?: number; error?: string }
        | null
      const found = Number(data?.events ?? data?.processed ?? 0)
      if (data?.success && found > 0) {
        showToast(`${found} evento(s) processado(s) — recarregando...`, 'success')
        setTimeout(() => window.location.reload(), 800)
      } else if (data?.success) {
        showToast('Nenhum pedido novo no momento.', 'info')
      } else {
        showToast(data?.error || 'Falha ao buscar pedidos.', 'error')
      }
    } catch {
      showToast('Falha de rede ao consultar iFood.', 'error')
    } finally {
      setPolling(false)
    }
  }

  async function performAction(orderId: number, action: 'confirm' | 'ready' | 'dispatch', confirmText: string) {
    if (!window.confirm(confirmText)) return
    setBusyAction(`${orderId}:${action}`)
    setOpenMenu(null)
    try {
      const url = `${urls.confirm_base}${orderId}/${action}`
      const res = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-Token': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
      })
      const data = (await res.json().catch(() => null)) as
        | { success?: boolean; message?: string; error?: string }
        | null
      if (data?.success) {
        showToast(data.message || 'Operação concluída.', 'success')
        setTimeout(() => window.location.reload(), 600)
      } else {
        showToast(data?.error || data?.message || 'Falha na operação.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setBusyAction(null)
    }
  }

  async function cancelOrder(orderId: number) {
    setOpenMenu(null)
    setBusyAction(`${orderId}:cancel-reasons`)
    let reasons: Array<{ code: string; label: string }> = []
    try {
      const res = await fetch(`${urls.confirm_base}${orderId}/cancel-reasons`, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      })
      const data = (await res.json().catch(() => null)) as
        | { success?: boolean; reasons?: Array<Record<string, unknown>>; error?: string }
        | null
      if (data?.reasons && Array.isArray(data.reasons)) {
        reasons = data.reasons.map((r) => ({
          code: String(
            (r.cancelCodeId as string | undefined) ||
              (r.code as string | undefined) ||
              (r.cancellationCode as string | undefined) ||
              '',
          ),
          label: String(
            (r.description as string | undefined) ||
              (r.cancelCodeId as string | undefined) ||
              (r.code as string | undefined) ||
              '',
          ),
        }))
      }
    } catch {
      // Continue with free text fallback
    } finally {
      setBusyAction(null)
    }

    let reasonCode: string | null = null
    if (reasons.length > 0) {
      const labels = reasons.map((r, i) => `${i + 1}. ${r.label}`).join('\n')
      const choice = window.prompt(
        `Selecione o motivo do cancelamento (digite o número):\n\n${labels}`,
        '1',
      )
      if (!choice) return
      const idx = Number(choice) - 1
      if (Number.isNaN(idx) || idx < 0 || idx >= reasons.length) {
        showToast('Motivo inválido.', 'error')
        return
      }
      reasonCode = reasons[idx].code
    } else {
      const txt = window.prompt('Motivo do cancelamento:', '')
      if (txt === null) return
      reasonCode = txt.trim() || 'OUTROS'
    }

    setBusyAction(`${orderId}:cancel`)
    try {
      const res = await fetch(`${urls.confirm_base}${orderId}/cancel`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-Token': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ reason_code: reasonCode }),
      })
      const data = (await res.json().catch(() => null)) as
        | { success?: boolean; message?: string; error?: string }
        | null
      if (data?.success) {
        showToast(data.message || 'Pedido cancelado.', 'success')
        setTimeout(() => window.location.reload(), 600)
      } else {
        showToast(data?.error || data?.message || 'Falha ao cancelar.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setBusyAction(null)
    }
  }

  const columns: DataTableColumn<IFoodOrder>[] = useMemo(
    () => [
      {
        header: 'Pedido',
        key: 'ifood_display_id',
        cell: (o) => (
          <div className="flex items-center gap-2">
            <Utensils className="h-3.5 w-3.5 text-red-500 shrink-0" />
            <div className="min-w-0">
              <p className="font-mono text-sm font-medium text-zinc-900 truncate">
                #{o.ifood_display_id || o.ifood_order_id.slice(0, 8)}
              </p>
              {o.local_order_id != null && (
                <p className="text-[11px] text-zinc-500">Local: #{o.local_order_id}</p>
              )}
            </div>
          </div>
        ),
      },
      {
        header: 'Cliente',
        key: 'customer_name',
        cell: (o) => (
          <div className="min-w-0">
            <p className="text-sm font-medium text-zinc-800 truncate">{o.customer_name || '—'}</p>
            {o.customer_phone && (
              <p className="text-[11px] text-zinc-500 font-mono">{o.customer_phone}</p>
            )}
          </div>
        ),
      },
      {
        header: 'Status',
        key: 'status',
        cell: (o) => {
          const info = STATUS_BADGE[o.status] ?? {
            label: o.status,
            cls: 'bg-zinc-100 text-zinc-700 border-zinc-200',
            icon: Clock,
          }
          const Icon = info.icon
          return (
            <span
              className={`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium ${info.cls}`}
            >
              <Icon className="h-3 w-3" />
              {info.label}
            </span>
          )
        },
      },
      {
        header: 'Valor',
        key: 'total_amount',
        cell: (o) => (
          <span className="text-sm font-semibold text-zinc-900">
            {formatCurrency(o.total_amount)}
          </span>
        ),
      },
      {
        header: 'Tipo',
        key: 'order_type',
        cell: (o) => {
          if (o.order_type === 'DELIVERY') {
            return (
              <Badge variant="secondary" className="gap-1.5">
                <Bike className="h-3 w-3" />
                Entrega
              </Badge>
            )
          }
          if (o.order_type === 'TAKEOUT') {
            return (
              <Badge variant="secondary" className="gap-1.5">
                <ShoppingBag className="h-3 w-3" />
                Retirada
              </Badge>
            )
          }
          return <Badge variant="outline">{o.order_type || '—'}</Badge>
        },
      },
      {
        header: 'Data',
        key: 'created_at',
        cell: (o) => (
          <span className="text-xs text-zinc-500 font-mono">{formatDateBr(o.created_at)}</span>
        ),
      },
      {
        header: '',
        key: 'actions',
        cell: (o) => {
          const isOpen = openMenu === o.id
          const isBusy = busyAction?.startsWith(`${o.id}:`)
          return (
            <div className="relative flex justify-end">
              <Button
                type="button"
                variant="ghost"
                size="sm"
                onClick={() => setOpenMenu(isOpen ? null : o.id)}
                disabled={isBusy}
                className="h-8 w-8 p-0"
              >
                {isBusy ? (
                  <Loader2 className="h-4 w-4 animate-spin" />
                ) : (
                  <MoreVertical className="h-4 w-4" />
                )}
              </Button>
              {isOpen && (
                <>
                  <div className="fixed inset-0 z-30" onClick={() => setOpenMenu(null)} />
                  <div className="absolute right-0 top-9 z-40 w-56 rounded-lg border border-zinc-200 bg-white shadow-xl py-1 text-sm">
                    <a
                      href={`${urls.detail_base}${o.id}`}
                      className="flex items-center gap-2 px-3 py-2 hover:bg-zinc-50"
                    >
                      <Eye className="h-3.5 w-3.5 text-zinc-500" />
                      Ver detalhes
                    </a>
                    {o.status === 'PLACED' && (
                      <button
                        type="button"
                        onClick={() => performAction(o.id, 'confirm', 'Confirmar pedido?')}
                        className="flex w-full items-center gap-2 px-3 py-2 hover:bg-zinc-50 text-left"
                      >
                        <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600" />
                        Confirmar
                      </button>
                    )}
                    {o.status === 'CONFIRMED' && (
                      <button
                        type="button"
                        onClick={() => performAction(o.id, 'ready', 'Marcar pedido como pronto?')}
                        className="flex w-full items-center gap-2 px-3 py-2 hover:bg-zinc-50 text-left"
                      >
                        <Package className="h-3.5 w-3.5 text-indigo-600" />
                        Marcar como pronto
                      </button>
                    )}
                    {o.status === 'READY_TO_PICKUP' && o.delivered_by === 'MERCHANT' && (
                      <button
                        type="button"
                        onClick={() => performAction(o.id, 'dispatch', 'Despachar pedido para entrega?')}
                        className="flex w-full items-center gap-2 px-3 py-2 hover:bg-zinc-50 text-left"
                      >
                        <Truck className="h-3.5 w-3.5 text-purple-600" />
                        Despachar
                      </button>
                    )}
                    {(o.status === 'PLACED' || o.status === 'CONFIRMED') && (
                      <>
                        <div className="my-1 border-t border-zinc-100" />
                        <button
                          type="button"
                          onClick={() => cancelOrder(o.id)}
                          className="flex w-full items-center gap-2 px-3 py-2 hover:bg-red-50 text-red-600 text-left"
                        >
                          <XCircle className="h-3.5 w-3.5" />
                          Cancelar
                        </button>
                      </>
                    )}
                    {o.local_order_id != null && (
                      <>
                        <div className="my-1 border-t border-zinc-100" />
                        <a
                          href={`${urls.local_order_base}${o.local_order_id}`}
                          className="flex items-center gap-2 px-3 py-2 hover:bg-zinc-50"
                        >
                          <PackageOpen className="h-3.5 w-3.5 text-zinc-500" />
                          Ver no sistema
                        </a>
                      </>
                    )}
                  </div>
                </>
              )}
            </div>
          )
        },
        className: 'w-12',
      },
    ],
    [openMenu, busyAction, urls],
  )

  return (
    <AdminStorePageShell section="ifood">
      <AdminPageHeader
        title="Pedidos iFood"
        description="Acompanhe e gerencie os pedidos recebidos via integração iFood."
        icon={<Utensils className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <div className="flex flex-wrap gap-2">
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={pollEvents}
              disabled={polling}
              className="gap-1.5"
            >
              <RefreshCw className={`h-3.5 w-3.5 ${polling ? 'animate-spin' : ''}`} />
              {polling ? 'Buscando...' : 'Atualizar'}
            </Button>
            <Button asChild variant="outline" size="sm" className="gap-1.5">
              <a href={urls.config}>
                <SettingsIcon className="h-3.5 w-3.5" />
                Configurações
              </a>
            </Button>
          </div>
        }
      />

      <div className="flex flex-wrap gap-2">
        <a
          href={urls.list}
          className={`inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium transition-colors ${
            !currentStatus
              ? 'bg-zinc-900 text-white border-zinc-900'
              : 'bg-white text-zinc-700 border-zinc-200 hover:bg-zinc-50'
          }`}
        >
          Todos
        </a>
        {STATUS_FILTERS.map((f) => {
          const active = currentStatus === f.value
          return (
            <a
              key={f.value}
              href={`${urls.list}?status=${encodeURIComponent(f.value)}`}
              className={`inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium transition-colors ${
                active ? f.tone : 'bg-white text-zinc-700 border-zinc-200 hover:bg-zinc-50'
              }`}
            >
              {f.label}
            </a>
          )
        })}
      </div>

      {orders.length === 0 ? (
        <EmptyState
          icon={<Utensils className="h-8 w-8 text-zinc-300" />}
          title="Nenhum pedido encontrado"
          description="Sincronize com o iFood para buscar novos pedidos."
          action={
            <Button onClick={pollEvents} disabled={polling} className="gap-2">
              <RefreshCw className={`h-4 w-4 ${polling ? 'animate-spin' : ''}`} />
              {polling ? 'Buscando...' : 'Buscar pedidos'}
            </Button>
          }
        />
      ) : (
        <DataTable<IFoodOrder>
          columns={columns}
          data={orders}
          rowKey={(o) => o.id}
          searchAccessor={(o) =>
            `${o.ifood_display_id} ${o.ifood_order_id} ${o.customer_name} ${o.customer_phone}`
          }
          searchPlaceholder="Buscar por pedido, cliente ou telefone"
        />
      )}
    </AdminStorePageShell>
  )
}
