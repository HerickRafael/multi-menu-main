import { useState } from 'react'
import {
  AlertTriangle,
  ArrowLeft,
  Ban,
  Bike,
  Calendar,
  CheckCheck,
  CheckCircle2,
  Clock,
  CreditCard,
  ExternalLink,
  HelpCircle,
  Loader2,
  MapPin,
  Package,
  PackageOpen,
  Phone,
  RefreshCw,
  ShoppingCart,
  StickyNote,
  Truck,
  UserCheck,
  Utensils,
  XCircle,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  AdminPageHeader,
  AdminStorePageShell,
  formatCurrency,
  getCsrfToken,
  showToast,
  useStoreContext,
} from '@/components/admin-store'

type OrderItemOption = {
  groupName?: string
  name?: string
  quantity?: number
}

type OrderItem = {
  name?: string
  imageUrl?: string
  quantity?: number
  totalPrice?: number
  observations?: string
  options?: OrderItemOption[]
}

type PaymentCard = { brand?: string }
type PaymentMethod = {
  type?: string
  method?: string
  value?: number
  card?: PaymentCard
  cash?: { changeFor?: number }
}

type PaymentInfo = {
  methods?: PaymentMethod[]
}

type DeliveryAddress = {
  streetName?: string
  streetNumber?: string
  complement?: string
  neighborhood?: string
  city?: string
  state?: string
  postalCode?: string
  reference?: string
  coordinates?: { latitude?: number; longitude?: number }
}

type Order = {
  id: number
  ifood_order_id: string
  ifood_display_id: string
  local_order_id: number | null
  status: string
  order_type: string
  order_timing: string
  delivered_by: string
  pickup_code: string
  customer_name: string
  customer_phone: string
  subtotal: number
  delivery_fee: number
  additional_fees: number
  benefits_total: number
  total_amount: number
  cancellation_reason: string
  ifood_created_at: string | null
  created_at: string
  confirmed_at: string | null
  ready_at: string | null
  dispatched_at: string | null
  concluded_at: string | null
  cancelled_at: string | null
  items: OrderItem[]
  payments: PaymentInfo
  delivery_address: DeliveryAddress
  benefits: unknown[]
}

type DriverState = {
  environment: 'sandbox' | 'production'
  ifood_order_id: string
  order_display_id: string | null
  request_status: 'PENDING' | 'REQUESTED' | 'NO_DRIVER' | 'ASSIGNED' | 'COMPLETED' | 'CANCELLED' | 'FAILED'
  driver_id: string | null
  driver_name: string | null
  driver_phone: string | null
  vehicle_type: string | null
  requested_at: string | null
  assigned_at: string | null
  picked_up_at: string | null
  delivered_at: string | null
  cancelled_at: string | null
  cancel_reason: string | null
  retries: number
  last_error: string | null
  last_response_status: number | null
  updated_at: string
}

type Payload = {
  order: Order
  driver_state?: DriverState | null
  urls: {
    list: string
    confirm: string
    ready: string
    dispatch: string
    cancel: string
    cancel_reasons: string
    local_order: string | null
    driver_state?: string
    driver_request?: string
    driver_cancel?: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_IFOOD_ORDER__?: Payload
  }
}

const STATUS_INFO: Record<string, { label: string; pill: string; icon: typeof Clock }> = {
  PLACED: { label: 'Novo Pedido', pill: 'bg-amber-100 text-amber-700 border-amber-200', icon: Clock },
  CONFIRMED: { label: 'Confirmado', pill: 'bg-blue-100 text-blue-700 border-blue-200', icon: CheckCircle2 },
  READY_TO_PICKUP: { label: 'Pronto para Retirada', pill: 'bg-indigo-100 text-indigo-700 border-indigo-200', icon: Package },
  DISPATCHED: { label: 'Em Entrega', pill: 'bg-purple-100 text-purple-700 border-purple-200', icon: Bike },
  CONCLUDED: { label: 'Concluído', pill: 'bg-emerald-100 text-emerald-700 border-emerald-200', icon: CheckCheck },
  CANCELLED: { label: 'Cancelado', pill: 'bg-red-100 text-red-700 border-red-200', icon: XCircle },
}

function formatDateBr(s: string | null | undefined): string {
  if (!s) return '—'
  const d = new Date(String(s).replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return String(s)
  const dd = String(d.getDate()).padStart(2, '0')
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const yy = d.getFullYear()
  const hh = String(d.getHours()).padStart(2, '0')
  const mi = String(d.getMinutes()).padStart(2, '0')
  return `${dd}/${mm}/${yy} ${hh}:${mi}`
}

function formatTimeBr(s: string | null | undefined): string {
  if (!s) return '—'
  const d = new Date(String(s).replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return String(s)
  const dd = String(d.getDate()).padStart(2, '0')
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const hh = String(d.getHours()).padStart(2, '0')
  const mi = String(d.getMinutes()).padStart(2, '0')
  return `${dd}/${mm} ${hh}:${mi}`
}

export default function AdminStoreIFoodOrderDetailPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_IFOOD_ORDER__) || ({} as Payload)
  const order = payload.order
  const urls = payload.urls
  const [busy, setBusy] = useState<string | null>(null)
  const [driverState, setDriverState] = useState<DriverState | null>(payload.driver_state ?? null)
  const [driverBusy, setDriverBusy] = useState<'refresh' | 'request' | 'cancel' | null>(null)

  if (!order || !urls) {
    return (
      <AdminStorePageShell section="ifood">
        <AdminPageHeader title="Pedido iFood" />
        <div className="rounded-2xl border border-zinc-200 bg-white p-12 text-center">
          <p className="text-sm text-zinc-500">Pedido não encontrado.</p>
        </div>
      </AdminStorePageShell>
    )
  }

  async function performAction(url: string, label: string, confirmText: string) {
    if (!window.confirm(confirmText)) return
    setBusy(label)
    try {
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
      setBusy(null)
    }
  }

  // ── Driver tracking ─────────────────────────────────────────────────────────
  async function refreshDriverState() {
    if (!urls.driver_state) return
    setDriverBusy('refresh')
    try {
      const res = await fetch(urls.driver_state, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const j = (await res.json().catch(() => null)) as
        | { success?: boolean; state?: DriverState | null; message?: string }
        | null
      if (j?.success) {
        setDriverState(j.state ?? null)
      } else {
        showToast(j?.message || 'Falha ao buscar driver.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setDriverBusy(null)
    }
  }

  async function requestDriver() {
    if (!urls.driver_request) return
    if (!window.confirm('Solicitar entregador iFood para este pedido?')) return
    setDriverBusy('request')
    try {
      const res = await fetch(urls.driver_request, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'X-CSRF-Token': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
      })
      const j = (await res.json().catch(() => null)) as
        | { success?: boolean; message?: string; enqueued?: number }
        | null
      if (j?.success) {
        showToast(j.enqueued ? 'Solicitação enfileirada.' : (j.message || 'Sem ação necessária.'), 'success')
        setTimeout(refreshDriverState, 1500)
      } else {
        showToast(j?.message || 'Falha.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setDriverBusy(null)
    }
  }

  async function cancelDriver() {
    if (!urls.driver_cancel) return
    const reason = window.prompt('Motivo do cancelamento do entregador:', 'admin_cancel')
    if (reason === null) return
    setDriverBusy('cancel')
    try {
      const fd = new FormData()
      fd.append('csrf_token', getCsrfToken())
      fd.append('reason', reason)
      const res = await fetch(urls.driver_cancel, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const j = (await res.json().catch(() => null)) as
        | { success?: boolean; message?: string; enqueued?: number }
        | null
      if (j?.success) {
        showToast(j.enqueued ? 'Cancelamento enfileirado.' : (j.message || 'Sem ação.'), 'success')
        setTimeout(refreshDriverState, 1500)
      } else {
        showToast(j?.message || 'Falha.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setDriverBusy(null)
    }
  }

  async function cancelOrder() {
    setBusy('cancel-reasons')
    let reasons: Array<{ code: string; label: string }> = []
    try {
      const res = await fetch(urls.cancel_reasons, {
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
      // ignore — will fall back to free text
    } finally {
      setBusy(null)
    }

    let reasonCode: string | null = null
    if (reasons.length > 0) {
      const opts = reasons.map((r, i) => `${i + 1}. ${r.label}`).join('\n')
      const choice = window.prompt(`Selecione o motivo do cancelamento (digite o número):\n\n${opts}`, '1')
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

    setBusy('cancel')
    try {
      const res = await fetch(urls.cancel, {
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
      setBusy(null)
    }
  }

  const info = STATUS_INFO[order.status] ?? {
    label: order.status,
    pill: 'bg-zinc-100 text-zinc-700 border-zinc-200',
    icon: HelpCircle,
  }
  const StatusIcon = info.icon
  const isCancelled = order.status === 'CANCELLED'
  const displayId = order.ifood_display_id || order.ifood_order_id.slice(0, 8)
  const addr = order.delivery_address || {}
  const hasAddress = order.order_type === 'DELIVERY' && (addr.streetName || addr.streetNumber || addr.city)
  const paymentMethods = order.payments?.methods || []

  const timelineEntries: Array<{ key: string; label: string; at: string | null; icon: typeof Clock; color: string }> = [
    { key: 'created', label: 'Pedido recebido', at: order.ifood_created_at || order.created_at, icon: Clock, color: 'bg-amber-500' },
    { key: 'confirmed', label: 'Confirmado', at: order.confirmed_at, icon: CheckCircle2, color: 'bg-blue-500' },
    { key: 'ready', label: 'Pronto', at: order.ready_at, icon: Package, color: 'bg-indigo-500' },
    { key: 'dispatched', label: 'Despachado', at: order.dispatched_at, icon: Bike, color: 'bg-purple-500' },
    { key: 'concluded', label: 'Concluído', at: order.concluded_at, icon: CheckCheck, color: 'bg-emerald-500' },
    { key: 'cancelled', label: 'Cancelado', at: order.cancelled_at, icon: XCircle, color: 'bg-red-500' },
  ].filter((t) => t.at)

  return (
    <AdminStorePageShell section="ifood">
      <AdminPageHeader
        title={`Pedido iFood · #${displayId}`}
        description={`Recebido em ${formatDateBr(order.created_at)}.`}
        icon={<Utensils className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <Button asChild variant="outline" size="sm" className="gap-1.5">
            <a href={urls.list}>
              <ArrowLeft className="h-3.5 w-3.5" />
              Voltar
            </a>
          </Button>
        }
      />

      {/* Header card with status */}
      <section className="rounded-2xl border border-zinc-200 bg-gradient-to-r from-zinc-900 to-zinc-800 p-5 shadow-sm">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div className="flex items-center gap-3">
            <div className="rounded-xl bg-red-600/90 p-3 shadow">
              <Utensils className="h-6 w-6 text-white" />
            </div>
            <div className="min-w-0">
              <h2 className="text-lg font-bold text-white">Pedido iFood #{displayId}</h2>
              <p className="text-xs text-zinc-300 inline-flex items-center gap-1.5 mt-1">
                <Clock className="h-3 w-3" />
                {formatDateBr(order.created_at)}
                {order.order_timing === 'SCHEDULED' && (
                  <Badge variant="secondary" className="gap-1 ml-2">
                    <Calendar className="h-3 w-3" />
                    Agendado
                  </Badge>
                )}
              </p>
            </div>
          </div>
          <span className={`inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-medium ${info.pill}`}>
            <StatusIcon className="h-4 w-4" />
            {info.label}
          </span>
        </div>
      </section>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {/* Left column: items + payment */}
        <div className="lg:col-span-2 space-y-4">
          {/* Items */}
          <section className="rounded-2xl border border-zinc-200 bg-white shadow-sm">
            <div className="border-b border-zinc-100 px-5 py-3">
              <h3 className="text-sm font-semibold text-zinc-900 inline-flex items-center gap-2">
                <ShoppingCart className="h-4 w-4 text-zinc-500" />
                Itens do pedido
              </h3>
            </div>
            <div className="divide-y divide-zinc-100">
              {(order.items || []).length === 0 ? (
                <div className="p-5 text-center text-sm text-zinc-500">Sem itens cadastrados.</div>
              ) : (
                order.items.map((item, idx) => (
                  <div key={idx} className="flex items-start gap-3 px-5 py-3">
                    {item.imageUrl ? (
                      <img
                        src={item.imageUrl}
                        alt=""
                        className="h-12 w-12 rounded-lg border border-zinc-200 object-cover shrink-0"
                      />
                    ) : (
                      <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-zinc-100">
                        <ShoppingCart className="h-5 w-5 text-zinc-400" />
                      </div>
                    )}
                    <div className="flex-1 min-w-0">
                      <div className="flex flex-wrap items-baseline justify-between gap-2">
                        <div className="min-w-0">
                          <p className="text-sm font-medium text-zinc-900">
                            <span className="mr-1.5 text-zinc-500">{item.quantity ?? 1}×</span>
                            {item.name || 'Item'}
                          </p>
                          {item.observations && (
                            <p className="mt-1 inline-flex items-start gap-1 text-xs text-amber-700">
                              <StickyNote className="h-3 w-3 mt-0.5 shrink-0" />
                              <span>{item.observations}</span>
                            </p>
                          )}
                          {item.options && item.options.length > 0 && (
                            <div className="mt-1.5 flex flex-wrap gap-1">
                              {item.options.map((opt, i) => (
                                <Badge key={i} variant="outline" className="text-[10px] font-normal">
                                  {opt.groupName ? `${opt.groupName}: ` : ''}
                                  {opt.name}
                                  {opt.quantity && opt.quantity > 1 ? ` ×${opt.quantity}` : ''}
                                </Badge>
                              ))}
                            </div>
                          )}
                        </div>
                        <span className="text-sm font-semibold text-zinc-900 shrink-0">
                          {formatCurrency(Number(item.totalPrice ?? 0))}
                        </span>
                      </div>
                    </div>
                  </div>
                ))
              )}
            </div>

            {/* Totals */}
            <div className="border-t border-zinc-100 bg-zinc-50/50 px-5 py-4">
              <div className="ml-auto max-w-xs space-y-1.5 text-sm">
                <div className="flex justify-between">
                  <span className="text-zinc-600">Subtotal</span>
                  <span className="font-medium text-zinc-800">{formatCurrency(order.subtotal)}</span>
                </div>
                {order.delivery_fee > 0 && (
                  <div className="flex justify-between">
                    <span className="text-zinc-600">Taxa de entrega</span>
                    <span className="font-medium text-zinc-800">{formatCurrency(order.delivery_fee)}</span>
                  </div>
                )}
                {order.benefits_total > 0 && (
                  <div className="flex justify-between text-emerald-700">
                    <span>Descontos</span>
                    <span className="font-medium">- {formatCurrency(order.benefits_total)}</span>
                  </div>
                )}
                {order.additional_fees > 0 && (
                  <div className="flex justify-between">
                    <span className="text-zinc-600">Taxas adicionais</span>
                    <span className="font-medium text-zinc-800">{formatCurrency(order.additional_fees)}</span>
                  </div>
                )}
                <div className="border-t border-zinc-200 pt-1.5 flex justify-between text-base">
                  <span className="font-semibold text-zinc-900">Total</span>
                  <span className="font-bold text-zinc-900">{formatCurrency(order.total_amount)}</span>
                </div>
              </div>
            </div>
          </section>

          {/* Payment */}
          <section className="rounded-2xl border border-zinc-200 bg-white shadow-sm">
            <div className="border-b border-zinc-100 px-5 py-3">
              <h3 className="text-sm font-semibold text-zinc-900 inline-flex items-center gap-2">
                <CreditCard className="h-4 w-4 text-zinc-500" />
                Pagamento
              </h3>
            </div>
            <div className="p-5 space-y-3">
              {paymentMethods.length === 0 ? (
                <p className="text-sm text-zinc-500">Informações de pagamento não disponíveis.</p>
              ) : (
                paymentMethods.map((m, idx) => {
                  const isOnline = m.type === 'ONLINE'
                  return (
                    <div key={idx} className="flex items-start gap-3 rounded-xl border border-zinc-200 p-3">
                      <div
                        className={`rounded-lg p-2 shrink-0 ${
                          isOnline ? 'bg-emerald-100' : 'bg-amber-100'
                        }`}
                      >
                        <CreditCard className={`h-4 w-4 ${isOnline ? 'text-emerald-600' : 'text-amber-600'}`} />
                      </div>
                      <div className="flex-1 min-w-0">
                        <p className="text-sm font-medium text-zinc-900">
                          {isOnline ? 'Pago online' : 'Pagar na entrega'}
                        </p>
                        <p className="text-xs text-zinc-500">
                          {m.method || ''}
                          {m.card?.brand ? ` · ${m.card.brand}` : ''} · {formatCurrency(Number(m.value ?? 0))}
                        </p>
                        {m.cash?.changeFor != null && Number(m.cash.changeFor) > 0 && (
                          <p className="mt-0.5 text-xs text-amber-700">
                            Troco para: {formatCurrency(Number(m.cash.changeFor))}
                          </p>
                        )}
                      </div>
                    </div>
                  )
                })
              )}
            </div>
          </section>
        </div>

        {/* Right column: actions + customer + delivery + timeline */}
        <div className="space-y-4">
          {/* Actions */}
          <section className="rounded-2xl border border-zinc-200 bg-white shadow-sm">
            <div className="border-b border-zinc-100 px-5 py-3">
              <h3 className="text-sm font-semibold text-zinc-900">Ações</h3>
            </div>
            <div className="p-4 space-y-2">
              {order.status === 'PLACED' && (
                <Button
                  type="button"
                  onClick={() => performAction(urls.confirm, 'confirm', 'Confirmar pedido?')}
                  disabled={busy === 'confirm'}
                  className="w-full gap-2"
                >
                  {busy === 'confirm' ? <Loader2 className="h-4 w-4 animate-spin" /> : <CheckCircle2 className="h-4 w-4" />}
                  Confirmar pedido
                </Button>
              )}
              {order.status === 'CONFIRMED' && (
                <Button
                  type="button"
                  onClick={() => performAction(urls.ready, 'ready', 'Marcar pedido como pronto?')}
                  disabled={busy === 'ready'}
                  className="w-full gap-2"
                >
                  {busy === 'ready' ? <Loader2 className="h-4 w-4 animate-spin" /> : <Package className="h-4 w-4" />}
                  Marcar como pronto
                </Button>
              )}
              {order.status === 'READY_TO_PICKUP' && order.delivered_by === 'MERCHANT' && (
                <Button
                  type="button"
                  onClick={() => performAction(urls.dispatch, 'dispatch', 'Despachar pedido para entrega?')}
                  disabled={busy === 'dispatch'}
                  className="w-full gap-2"
                >
                  {busy === 'dispatch' ? <Loader2 className="h-4 w-4 animate-spin" /> : <Truck className="h-4 w-4" />}
                  Despachar para entrega
                </Button>
              )}
              {(order.status === 'PLACED' || order.status === 'CONFIRMED') && (
                <Button
                  type="button"
                  variant="outline"
                  onClick={cancelOrder}
                  disabled={busy === 'cancel' || busy === 'cancel-reasons'}
                  className="w-full gap-2 text-red-600 hover:bg-red-50"
                >
                  {busy === 'cancel' || busy === 'cancel-reasons' ? (
                    <Loader2 className="h-4 w-4 animate-spin" />
                  ) : (
                    <XCircle className="h-4 w-4" />
                  )}
                  Cancelar pedido
                </Button>
              )}
              {urls.local_order && (
                <Button asChild variant="outline" className="w-full gap-2">
                  <a href={urls.local_order}>
                    <PackageOpen className="h-4 w-4" />
                    Ver pedido local
                  </a>
                </Button>
              )}
              <Button asChild variant="ghost" className="w-full gap-2">
                <a href={urls.list}>
                  <ArrowLeft className="h-4 w-4" />
                  Voltar para lista
                </a>
              </Button>
            </div>
          </section>

          {/* Customer */}
          <section className="rounded-2xl border border-zinc-200 bg-white shadow-sm">
            <div className="border-b border-zinc-100 px-5 py-3">
              <h3 className="text-sm font-semibold text-zinc-900">Cliente</h3>
            </div>
            <div className="p-4 space-y-1.5">
              <p className="text-sm font-medium text-zinc-900">{order.customer_name || '—'}</p>
              {order.customer_phone && (
                <a
                  href={`tel:${order.customer_phone}`}
                  className="inline-flex items-center gap-1.5 text-xs text-zinc-600 hover:text-zinc-900"
                >
                  <Phone className="h-3 w-3" />
                  {order.customer_phone}
                </a>
              )}
            </div>
          </section>

          {/* Delivery address */}
          {hasAddress && (
            <section className="rounded-2xl border border-zinc-200 bg-white shadow-sm">
              <div className="border-b border-zinc-100 px-5 py-3">
                <h3 className="text-sm font-semibold text-zinc-900 inline-flex items-center gap-2">
                  <MapPin className="h-4 w-4 text-zinc-500" />
                  Endereço de entrega
                </h3>
              </div>
              <div className="p-4 space-y-1 text-sm">
                <p className="text-zinc-800">
                  {addr.streetName || ''}
                  {addr.streetNumber ? `, ${addr.streetNumber}` : ''}
                </p>
                {addr.complement && <p className="text-zinc-600 text-xs">{addr.complement}</p>}
                <p className="text-zinc-600 text-xs">
                  {addr.neighborhood || ''}
                  {addr.city ? ` · ${addr.city}` : ''}
                  {addr.state ? `/${addr.state}` : ''}
                </p>
                {addr.postalCode && <p className="text-zinc-500 text-xs">CEP: {addr.postalCode}</p>}
                {addr.reference && (
                  <p className="text-zinc-500 text-xs italic">Referência: {addr.reference}</p>
                )}
                {addr.coordinates?.latitude && addr.coordinates?.longitude && (
                  <Button asChild variant="outline" size="sm" className="mt-2 gap-1.5">
                    <a
                      href={`https://www.google.com/maps?q=${addr.coordinates.latitude},${addr.coordinates.longitude}`}
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      <ExternalLink className="h-3 w-3" />
                      Ver no Maps
                    </a>
                  </Button>
                )}
              </div>
            </section>
          )}

          {/* Delivery info */}
          {order.delivered_by && (
            <section className="rounded-2xl border border-zinc-200 bg-white shadow-sm">
              <div className="border-b border-zinc-100 px-5 py-3">
                <h3 className="text-sm font-semibold text-zinc-900 inline-flex items-center gap-2">
                  <Truck className="h-4 w-4 text-zinc-500" />
                  Entrega
                </h3>
              </div>
              <div className="p-4 space-y-2 text-sm">
                <Badge
                  variant="outline"
                  className={
                    order.delivered_by === 'IFOOD'
                      ? 'border-red-200 bg-red-50 text-red-700'
                      : 'border-blue-200 bg-blue-50 text-blue-700'
                  }
                >
                  {order.delivered_by === 'IFOOD' ? 'Logística iFood' : 'Entrega própria'}
                </Badge>
                {order.pickup_code && (
                  <p className="text-xs">
                    <span className="text-zinc-500">Código de retirada: </span>
                    <span className="font-mono font-semibold text-zinc-900 bg-zinc-100 px-2 py-0.5 rounded">
                      {order.pickup_code}
                    </span>
                  </p>
                )}
              </div>
            </section>
          )}

          {/* Driver tracking — só faz sentido pra delivered_by=MERCHANT */}
          {order.delivered_by === 'MERCHANT' && (
            <DriverSection
              state={driverState}
              busy={driverBusy}
              onRefresh={refreshDriverState}
              onRequest={requestDriver}
              onCancel={cancelDriver}
              orderStatus={order.status}
            />
          )}

          {/* Timeline */}
          <section className="rounded-2xl border border-zinc-200 bg-white shadow-sm">
            <div className="border-b border-zinc-100 px-5 py-3">
              <h3 className="text-sm font-semibold text-zinc-900 inline-flex items-center gap-2">
                <Clock className="h-4 w-4 text-zinc-500" />
                Histórico
              </h3>
            </div>
            <div className="p-4 space-y-3">
              {timelineEntries.map((t) => {
                const TIcon = t.icon
                return (
                  <div key={t.key} className="flex items-start gap-3">
                    <div className={`mt-0.5 h-7 w-7 rounded-full flex items-center justify-center shrink-0 ${t.color}`}>
                      <TIcon className="h-3.5 w-3.5 text-white" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="text-sm font-medium text-zinc-900">{t.label}</p>
                      <p className="text-xs text-zinc-500">{formatTimeBr(t.at)}</p>
                      {t.key === 'cancelled' && isCancelled && order.cancellation_reason && (
                        <p className="text-xs text-red-600 mt-0.5">{order.cancellation_reason}</p>
                      )}
                    </div>
                  </div>
                )
              })}
            </div>
          </section>
        </div>
      </div>
    </AdminStorePageShell>
  )
}

// ── DriverSection ─────────────────────────────────────────────────────────────

const DRIVER_STATUS_INFO: Record<DriverState['request_status'], { label: string; pill: string }> = {
  PENDING:   { label: 'Pendente (job enfileirado)', pill: 'bg-zinc-100 text-zinc-700 border-zinc-200' },
  REQUESTED: { label: 'Solicitado, aguardando atribuição', pill: 'bg-amber-100 text-amber-700 border-amber-200' },
  NO_DRIVER: { label: 'Sem entregador disponível (retry)', pill: 'bg-orange-100 text-orange-700 border-orange-200' },
  ASSIGNED:  { label: 'Entregador atribuído', pill: 'bg-blue-100 text-blue-700 border-blue-200' },
  COMPLETED: { label: 'Entrega concluída', pill: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
  CANCELLED: { label: 'Cancelado', pill: 'bg-red-100 text-red-700 border-red-200' },
  FAILED:    { label: 'Falha permanente', pill: 'bg-red-100 text-red-700 border-red-200' },
}

const DRIVER_TERMINAL: DriverState['request_status'][] = ['COMPLETED', 'CANCELLED', 'FAILED']

function DriverSection({
  state,
  busy,
  onRefresh,
  onRequest,
  onCancel,
  orderStatus,
}: {
  state: DriverState | null
  busy: 'refresh' | 'request' | 'cancel' | null
  onRefresh: () => void
  onRequest: () => void
  onCancel: () => void
  orderStatus: string
}) {
  const info = state ? DRIVER_STATUS_INFO[state.request_status] : null
  const isTerminal = state ? DRIVER_TERMINAL.includes(state.request_status) : false
  const canRequest = !state || isTerminal
  const canCancel = state && !isTerminal && state.request_status !== 'PENDING'
  const allowedOrderStatuses = ['CONFIRMED', 'READY_TO_PICKUP', 'DISPATCHED']
  const orderAllowsDriver = allowedOrderStatuses.includes(orderStatus)

  return (
    <section className="rounded-2xl border border-zinc-200 bg-white shadow-sm">
      <div className="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-100 px-5 py-3">
        <h3 className="text-sm font-semibold text-zinc-900 inline-flex items-center gap-2">
          <Bike className="h-4 w-4 text-zinc-500" />
          Entregador iFood
        </h3>
        <div className="flex flex-wrap items-center gap-2">
          <Button
            type="button"
            size="sm"
            variant="outline"
            onClick={onRefresh}
            disabled={busy === 'refresh'}
            className="gap-1.5"
          >
            <RefreshCw className={`h-3.5 w-3.5 ${busy === 'refresh' ? 'animate-spin' : ''}`} />
            Atualizar
          </Button>
          {canRequest && orderAllowsDriver && (
            <Button
              type="button"
              size="sm"
              onClick={onRequest}
              disabled={busy === 'request'}
              className="gap-1.5"
            >
              <UserCheck className="h-3.5 w-3.5" />
              {busy === 'request' ? 'Solicitando…' : 'Solicitar entregador'}
            </Button>
          )}
          {canCancel && (
            <Button
              type="button"
              size="sm"
              variant="destructive"
              onClick={onCancel}
              disabled={busy === 'cancel'}
              className="gap-1.5"
            >
              <Ban className="h-3.5 w-3.5" />
              {busy === 'cancel' ? 'Cancelando…' : 'Cancelar driver'}
            </Button>
          )}
        </div>
      </div>

      <div className="p-5 space-y-4">
        {!state ? (
          <p className="text-sm text-zinc-500">
            {orderAllowsDriver
              ? 'Nenhuma solicitação de entregador feita ainda. Use o botão acima para solicitar.'
              : 'Pedido precisa estar em CONFIRMED, READY_TO_PICKUP ou DISPATCHED para solicitar entregador.'}
          </p>
        ) : (
          <>
            <div className="flex flex-wrap items-center gap-3">
              <Badge className={`${info?.pill} border`} variant="outline">
                {info?.label ?? state.request_status}
              </Badge>
              <span className="text-xs text-zinc-500">
                Atualizado: <span className="font-mono">{state.updated_at}</span>
              </span>
              {state.retries > 0 && (
                <span className="inline-flex items-center gap-1 text-xs text-red-700">
                  <AlertTriangle className="h-3.5 w-3.5" />
                  {state.retries} tentativa(s)
                </span>
              )}
            </div>

            {(state.driver_name || state.driver_phone || state.vehicle_type) && (
              <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 rounded-lg bg-zinc-50 p-3">
                {state.driver_name && (
                  <Field icon={<UserCheck className="h-3.5 w-3.5" />} label="Entregador">
                    {state.driver_name}
                  </Field>
                )}
                {state.driver_phone && (
                  <Field icon={<Phone className="h-3.5 w-3.5" />} label="Telefone">
                    <span className="font-mono">{state.driver_phone}</span>
                  </Field>
                )}
                {state.vehicle_type && (
                  <Field icon={<Truck className="h-3.5 w-3.5" />} label="Veículo">
                    {state.vehicle_type}
                  </Field>
                )}
              </div>
            )}

            {/* Timeline driver */}
            <div className="grid gap-2 text-sm sm:grid-cols-2">
              {[
                { label: 'Solicitado',   at: state.requested_at },
                { label: 'Atribuído',    at: state.assigned_at  },
                { label: 'Retirou',      at: state.picked_up_at },
                { label: 'Entregue',     at: state.delivered_at },
                { label: 'Cancelado',    at: state.cancelled_at },
              ]
                .filter((t) => !!t.at)
                .map((t) => (
                  <div key={t.label} className="flex items-center gap-2 rounded bg-zinc-50 px-3 py-2">
                    <CheckCircle2 className="h-4 w-4 text-green-600 shrink-0" />
                    <span className="text-zinc-700">{t.label}</span>
                    <span className="ml-auto text-xs text-zinc-500 font-mono">{t.at}</span>
                  </div>
                ))}
            </div>

            {state.cancel_reason && (
              <p className="text-xs text-zinc-500">
                Motivo do cancelamento: <code className="font-mono">{state.cancel_reason}</code>
              </p>
            )}
            {state.last_error && (
              <p className="rounded bg-red-50 border border-red-200 px-3 py-2 text-xs text-red-700">
                <strong>Erro:</strong> {state.last_error}
              </p>
            )}
          </>
        )}
      </div>
    </section>
  )
}

function Field({
  icon,
  label,
  children,
}: {
  icon: React.ReactNode
  label: string
  children: React.ReactNode
}) {
  return (
    <div>
      <p className="inline-flex items-center gap-1 text-xs text-zinc-500">
        {icon}
        {label}
      </p>
      <p className="mt-0.5 text-sm font-medium text-zinc-900">{children}</p>
    </div>
  )
}
