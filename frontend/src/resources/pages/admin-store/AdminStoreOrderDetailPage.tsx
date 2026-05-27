import { useMemo, useState } from 'react'
import {
  ArrowLeft,
  CheckCheck,
  ClipboardList,
  Clock,
  CreditCard,
  DollarSign,
  Edit3,
  Globe,
  History,
  Loader2,
  MapPin,
  MessageSquare,
  Package,
  Phone,
  Printer,
  ShoppingCart,
  Smartphone,
  Trash2,
  Truck,
  User2,
  Utensils,
  XCircle,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  AdminStorePageShell,
  formatCurrency,
  getCsrfToken,
  showToast,
  useStoreContext,
} from '@/components/admin-store'

// ── Types ─────────────────────────────────────────────────────────────────────

type ComboItem = {
  simple_id?: number
  simple_name?: string
  name?: string
  qty?: number
  default_qty?: number
  delta?: number
  delta_price?: number
}

type CustomItem = {
  name?: string
  qty?: number
  default_qty?: number
  delta_qty?: number
  price?: number
  unit_price?: number
  paid_qty?: number
  selected?: boolean
  removed?: boolean
}

type ComponentCustomizationUnit = {
  groups?: Array<{
    items?: CustomItem[]
  }>
}

type ComponentCustomization = {
  unit_customizations?: Record<string, ComponentCustomizationUnit>
}

type ComboData = {
  selected_items?: ComboItem[]
  component_customizations?: Record<string, ComponentCustomization>
}

type CustomGroup = {
  name?: string
  type?: 'extra' | 'addon' | 'single' | 'choice' | 'pool' | string
  items?: CustomItem[]
}

type CustomData = {
  groups?: CustomGroup[]
  total_delta?: number
}

type OrderItem = {
  id: number | null
  product_name: string
  quantity: number
  unit_price: number
  line_total: number
  notes: string
  combo_data: ComboData | null
  customization_data: CustomData | null
}

type IFoodAddress = {
  streetName?: string
  streetNumber?: string
  complement?: string
  neighborhood?: string
  city?: string
  state?: string
  postalCode?: string
  reference?: string
}

type IFoodPaymentMethod = {
  method?: string
  type?: string
  value?: number
  prepaid?: boolean
  brand?: string
  card?: { brand?: string }
  cash?: { changeFor?: number }
}

type IFoodPayments = { methods?: IFoodPaymentMethod[] }

type IFoodBenefit = {
  value?: number
  target?: string
  sponsorshipValues?: Record<string, number>
}

type IFoodData = {
  ifood_order_id: string
  ifood_display_id: string
  status: string
  order_type: string
  order_timing: string
  scheduled_datetime: string | null
  customer_document: string | null
  pickup_code: string | null
  delivered_by: string | null
  delivery_address: IFoodAddress | null
  payments: IFoodPayments | null
  benefits: IFoodBenefit[] | null
  raw_data: Record<string, unknown> | null
  cancellation_reason: string | null
  created_at: string | null
}

type OrderEvent = {
  event_type?: string
  status?: string
  created_at?: string
  payload?: {
    meta?: {
      status_change?: { from?: string; to?: string }
      order_update?: {
        before?: Record<string, unknown>
        after?: Record<string, unknown>
      }
    }
  }
}

type Order = {
  id: number
  order_number: number
  status: string
  source: string
  customer_name: string
  customer_phone: string
  customer_address: string
  notes: string
  subtotal: number
  delivery_fee: number
  discount: number
  loyalty_discount: number
  total: number
  payment_method_id: number | null
  created_at: string
  ifood_order_id: string | null
  items: OrderItem[]
}

type Payload = {
  order: Order
  payment: {
    name: string | null
    type: string | null
    meta: Record<string, unknown> | null
    instructions: string | null
  }
  ifood: IFoodData | null
  events: OrderEvent[]
  status_labels: Record<string, string>
  whatsapp_url: string | null
  urls: {
    list: string
    set_status: string
    edit: string
    delete: string
    print: string
    legacy: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_ORDER__?: Payload
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────────

const STATUS_RANK: Record<string, number> = {
  pending: 0,
  paid: 1, confirmed: 1, ready: 1, dispatched: 1,
  completed: 2, concluded: 2,
  canceled: -1, cancelled: -1,
}

type TimelineStep = {
  key: string
  label: string
  Icon: typeof Clock
  activeColor: string
  activeBg: string
  activeBorder: string
  activeText: string
  ringColor: string
}

const ORDER_STEPS: TimelineStep[] = [
  { key: 'pending',   label: 'Novo',             Icon: Clock,      activeColor: 'bg-amber-500',   activeBg: 'bg-amber-50',   activeBorder: 'border-amber-400',   activeText: 'text-amber-700',   ringColor: 'ring-amber-200'   },
  { key: 'paid',      label: 'Saiu para entrega', Icon: Truck,      activeColor: 'bg-violet-500',  activeBg: 'bg-violet-50',  activeBorder: 'border-violet-400',  activeText: 'text-violet-700',  ringColor: 'ring-violet-200'  },
  { key: 'completed', label: 'Concluído',          Icon: CheckCheck, activeColor: 'bg-emerald-500', activeBg: 'bg-emerald-50', activeBorder: 'border-emerald-400', activeText: 'text-emerald-700', ringColor: 'ring-emerald-200' },
]

function StatusTimeline({ status }: { status: string }) {
  const rank = STATUS_RANK[status] ?? 0

  return (
    <div className="flex items-start">
      {ORDER_STEPS.map((step, i) => {
        const stepRank = STATUS_RANK[step.key] ?? 0
        const done    = rank > stepRank
        const current = rank === stepRank
        const active  = done || current
        const Icon    = step.Icon

        return (
          <div key={step.key} className="flex flex-1 flex-col items-center">
            <div className="flex w-full items-center">
              <div className={`h-0.5 flex-1 transition-all duration-500 ${
                i === 0 ? 'invisible' : done ? step.activeColor : 'bg-zinc-200'
              }`} />
              <div className={`relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 transition-all duration-300 ${
                done    ? `${step.activeColor} border-transparent text-white shadow-md` :
                current ? `${step.activeBg} ${step.activeBorder} ${step.activeText} shadow-lg ring-4 ring-offset-2 ${step.ringColor}` :
                          'border-zinc-200 bg-white text-zinc-300'
              }`}>
                {done ? <CheckCheck className="h-4 w-4" /> : <Icon className="h-4 w-4" />}
                {current && (
                  <span className={`absolute inset-0 animate-ping rounded-full opacity-30 ${step.activeBg}`} />
                )}
              </div>
              <div className={`h-0.5 flex-1 transition-all duration-500 ${
                i === ORDER_STEPS.length - 1 ? 'invisible' : done ? step.activeColor : 'bg-zinc-200'
              }`} />
            </div>
            <p className={`mt-2.5 text-center text-[11px] font-semibold leading-tight tracking-wide ${
              active ? step.activeText : 'text-zinc-300'
            }`}>
              {step.label}
            </p>
            {current && (
              <span className={`mt-0.5 text-[10px] font-medium ${step.activeText} opacity-70`}>Atual</span>
            )}
          </div>
        )
      })}
    </div>
  )
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

function formatPhoneBr(raw: string): string {
  const digits = raw.replace(/\D+/g, '')
  if (digits.length === 11) return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`
  if (digits.length === 10) return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`
  return raw || '—'
}

function parseUserNotesAndInstructions(notes: string, paymentInstructions: string | null) {
  const userLines: string[] = []
  let inlineInstructions = ''
  notes.split(/\r?\n/).forEach((rawLine) => {
    const line = rawLine.trim()
    if (!line) return
    const pm = line.match(/^Pagamento:\s*.+?(?:\s+[—-]\s+(.+))?$/u)
    if (pm) {
      if (pm[1] && !inlineInstructions) inlineInstructions = pm[1].trim()
      return
    }
    if (/^Troco para:/i.test(line)) return
    const obs = line.match(/^Observações:\s*(.+)$/is)
    if (obs) {
      userLines.push(obs[1].trim())
      return
    }
    userLines.push(line)
  })
  return {
    userNotes: userLines.join('\n'),
    displayInstructions: paymentInstructions || inlineInstructions || null,
  }
}

function parseTrocoFromNotes(notes: string): { changeFor: string; change: string } | null {
  const m = notes.match(/Troco para: R\$ ([\d.,]+)(?:\s+\(Troco: R\$ ([\d.,]+)\))?/i)
  if (!m) return null
  return { changeFor: m[1], change: m[2] ?? '' }
}

// Item display helpers --------------------------------------------------------

type LineEntry = { text: string; status: string }

function buildComboLines(combo: ComboData): Array<{ name: string; delta: number; unitLines?: LineEntry[] }> {
  const result: Array<{ name: string; delta: number; unitLines?: LineEntry[] }> = []
  const components = combo.component_customizations ?? {}
  for (const item of combo.selected_items ?? []) {
    const name = item.simple_name || item.name || ''
    if (!name) continue
    const simpleId = item.simple_id ?? 0
    let qty = item.qty != null ? Number(item.qty) : (item.default_qty != null ? Number(item.default_qty) : 1)
    if (qty <= 0) qty = 1
    const delta = Number(item.delta ?? item.delta_price ?? 0)
    const unitCusts = simpleId > 0 ? components[simpleId]?.unit_customizations : undefined
    const hasUnitCusts = unitCusts && Object.keys(unitCusts).length > 0
    if (hasUnitCusts && qty > 1) {
      for (const [unitNum, unitCust] of Object.entries(unitCusts!)) {
        const unitLines: LineEntry[] = []
        for (const g of unitCust.groups ?? []) {
          for (const ci of g.items ?? []) {
            const ciName = ci.name ?? ''
            if (!ciName) continue
            const ciDefaultQty = ci.default_qty != null ? Number(ci.default_qty) : null
            const ciQty = ci.qty != null ? Number(ci.qty) : null
            let ciDelta = ci.delta_qty != null ? Number(ci.delta_qty) : null
            const ciRemoved = !!ci.removed || (ciDefaultQty != null && ciDefaultQty > 0 && (ciQty === 0 || ciQty == null))
            if (ciDelta == null && ciDefaultQty != null && ciQty != null) ciDelta = ciQty - ciDefaultQty
            const ciPrice = Number(ci.price ?? 0)
            if (ciRemoved) {
              unitLines.push({ text: `Sem ${ciName}`, status: 'Removido' })
            } else if (ciDelta != null && ciDelta > 0) {
              unitLines.push({ text: `+${ciDelta}x ${ciName}`, status: ciPrice > 0 ? `+ ${formatCurrency(ciPrice)}` : 'Extra' })
            } else if (ciDelta != null && ciDelta < 0) {
              unitLines.push({ text: `Sem ${ciName}`, status: 'Removido' })
            }
          }
        }
        result.push({ name: `${name} (${unitNum}º)`, delta, unitLines })
      }
    } else {
      result.push({ name: qty > 1 ? `${qty}x ${name}` : name, delta })
    }
  }
  return result
}

function buildCustomLines(custom: CustomData): LineEntry[] {
  const out: LineEntry[] = []
  for (const group of custom.groups ?? []) {
    const gType = group.type ?? 'extra'
    const isChoice = gType === 'single' || gType === 'addon' || gType === 'choice'
    const isPool = gType === 'pool'
    for (const ci of group.items ?? []) {
      const name = ci.name ?? ''
      if (!name) continue
      const qty = ci.qty != null ? Number(ci.qty) : null
      const defaultQty = ci.default_qty != null ? Number(ci.default_qty) : null
      let deltaQty = ci.delta_qty != null ? Number(ci.delta_qty) : null
      const price = Number(ci.price ?? 0)
      const selected = !!ci.selected || (qty != null && qty > 0)
      const removed = !!ci.removed || (defaultQty != null && defaultQty > 0 && (qty === 0 || qty == null))
      if (removed) {
        out.push({ text: `Sem ${name}`, status: 'Removido' })
        continue
      }
      const effQty = qty ?? 0
      if (deltaQty == null && defaultQty != null && qty != null) deltaQty = qty - defaultQty
      if (isPool && effQty > 0) {
        const text = effQty > 1 ? `${effQty}x ${name}` : name
        const paidQty = Number(ci.paid_qty ?? 0)
        const paidPrice = Number(ci.unit_price ?? price)
        const status = paidQty > 0 && paidPrice > 0.009 ? `+ ${formatCurrency(paidQty * paidPrice)}` : 'Incluso'
        out.push({ text, status })
        continue
      }
      if (isChoice && selected && effQty > 0) {
        out.push({ text: name, status: price > 0.009 ? `+ ${formatCurrency(price)}` : 'Incluso' })
        continue
      }
      if (deltaQty != null && deltaQty !== 0) {
        if (deltaQty > 0) {
          out.push({
            text: `+${deltaQty > 1 ? `${deltaQty}x ` : ''}${name}`,
            status: price > 0 ? `+ ${formatCurrency(price)}` : 'Extra',
          })
        } else if (deltaQty < 0) {
          out.push({ text: `Sem ${name}`, status: 'Removido' })
        }
      } else if (!isChoice && price > 0.009 && effQty > 0) {
        out.push({
          text: `${effQty > 1 ? `${effQty}x ` : ''}${name}`,
          status: `+ ${formatCurrency(price)}`,
        })
      }
    }
  }
  return out
}

// ── Badges ────────────────────────────────────────────────────────────────────

function SourceBadge({ source }: { source: string }) {
  const s = (source || 'manual').toLowerCase()
  if (s === 'ifood') return (
    <Badge className="gap-1.5 border-transparent bg-red-100 text-red-700 hover:bg-red-100">
      <Utensils className="h-3 w-3" />iFood
    </Badge>
  )
  if (s === 'website') return (
    <Badge className="gap-1.5 border-transparent bg-blue-100 text-blue-700 hover:bg-blue-100">
      <Globe className="h-3 w-3" />Site
    </Badge>
  )
  if (s === 'whatsapp') return (
    <Badge className="gap-1.5 border-transparent bg-emerald-100 text-emerald-700 hover:bg-emerald-100">
      <Smartphone className="h-3 w-3" />WhatsApp
    </Badge>
  )
  return (
    <Badge className="gap-1.5 border-transparent bg-slate-100 text-slate-700 hover:bg-slate-100">
      <ClipboardList className="h-3 w-3" />Manual
    </Badge>
  )
}

// ── Main component ────────────────────────────────────────────────────────────

export default function AdminStoreOrderDetailPage() {
  useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_ORDER__) || ({} as Payload)

  const order = payload.order
  const urls = payload.urls
  const statusLabels = payload.status_labels ?? {}

  const [busy, setBusy] = useState<string | null>(null)
  const [currentStatus, setCurrentStatus] = useState<string>(order?.status ?? 'pending')

  const orderNumber = order?.order_number ?? order?.id ?? 0
  const isIFood = (order?.source ?? '') === 'ifood'
  const canEdit = order?.status === 'pending' && !isIFood
  const totalDiscount = (order?.discount ?? 0) + (order?.loyalty_discount ?? 0)
  const STATUS_LABEL_MAP: Record<string, string> = {
    pending: 'Novo', paid: 'Saiu para entrega', completed: 'Concluído', canceled: 'Cancelado',
  }

  const { userNotes, displayInstructions } = useMemo(() => {
    return parseUserNotesAndInstructions(order?.notes ?? '', payload.payment?.instructions ?? null)
  }, [order?.notes, payload.payment?.instructions])

  const troco = useMemo(() => parseTrocoFromNotes(order?.notes ?? ''), [order?.notes])

  const historyEvents = useMemo(() => {
    const allowed = new Set(['order.created', 'order.updated', 'order.status_changed', 'order.canceled'])
    return (payload.events ?? []).filter((e) => allowed.has(e.event_type ?? ''))
  }, [payload.events])

  const isCanceled = currentStatus === 'canceled' || currentStatus === 'cancelled'
  const isCompleted = currentStatus === 'completed' || currentStatus === 'concluded'
  const isActive = !isCanceled && !isCompleted

  if (!order || !urls) {
    return (
      <AdminStorePageShell section="orders">
        <div className="rounded-2xl border border-zinc-200 bg-white p-12 text-center">
          <ShoppingCart className="mx-auto h-12 w-12 text-zinc-300" />
          <p className="mt-3 text-sm text-zinc-500">Pedido não encontrado.</p>
        </div>
      </AdminStorePageShell>
    )
  }

  async function applyStatusDirect(newStatus: string) {
    if (newStatus === 'canceled') {
      if (!window.confirm('Tem certeza que deseja cancelar este pedido?')) return
    }
    setBusy(newStatus)
    try {
      const fd = new FormData()
      fd.set('id', String(order.id))
      fd.set('status', newStatus)
      fd.set('csrf_token', getCsrfToken())
      const res = await fetch(urls.set_status, {
        method: 'POST',
        credentials: 'same-origin',
        body: fd,
        headers: {
          'X-CSRF-Token': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
      })
      const json = await res.json().catch(() => null) as { success?: boolean; message?: string } | null
      if (json?.success) {
        showToast(json.message ?? 'Status atualizado.', 'success')
        setCurrentStatus(newStatus)
        setTimeout(() => window.location.reload(), 600)
      } else {
        showToast(json?.message ?? 'Não foi possível atualizar o status.', 'error')
      }
    } catch {
      showToast('Falha de rede ao atualizar status.', 'error')
    } finally {
      setBusy(null)
    }
  }

  async function deleteOrder() {
    if (!window.confirm('Excluir pedido?')) return
    setBusy('delete')
    try {
      const fd = new FormData()
      fd.set('csrf_token', getCsrfToken())
      const res = await fetch(urls.delete, {
        method: 'POST',
        credentials: 'same-origin',
        body: fd,
        headers: {
          'X-CSRF-Token': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
      })
      if (res.redirected || res.ok) {
        showToast('Pedido excluído.', 'success')
        setTimeout(() => { window.location.href = urls.list }, 400)
      } else {
        showToast('Falha ao excluir.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setBusy(null)
    }
  }

  const statusLabel = statusLabels[order?.status ?? ''] ?? STATUS_LABEL_MAP[order?.status ?? ''] ?? (order?.status ?? '')

  return (
    <AdminStorePageShell section="orders">

      {/* ── Page Header ── */}
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div className="flex items-start gap-3">
          <Button asChild variant="outline" size="icon" className="h-9 w-9 shrink-0">
            <a href={urls.list}><ArrowLeft className="h-4 w-4" /></a>
          </Button>
          <div>
            <h1 className="text-2xl font-bold tracking-tight text-zinc-900">
              Pedido #{orderNumber}
            </h1>
            <div className="mt-1 flex flex-wrap items-center gap-2">
              <SourceBadge source={order.source} />
              {order.created_at && (
                <span className="text-xs text-zinc-400">{formatDateBr(order.created_at)}</span>
              )}
              <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ${
                isCanceled ? 'bg-red-100 text-red-700' :
                isCompleted ? 'bg-emerald-100 text-emerald-700' :
                'bg-amber-100 text-amber-700'
              }`}>
                {statusLabel}
              </span>
            </div>
          </div>
        </div>

        <div className="flex flex-wrap items-center gap-2">
          {payload.whatsapp_url && (
            <Button asChild variant="outline" size="sm" className="gap-1.5 text-emerald-700 border-emerald-200 hover:bg-emerald-50">
              <a href={payload.whatsapp_url} target="_blank" rel="noopener noreferrer">
                <Smartphone className="h-3.5 w-3.5" />
                WhatsApp
              </a>
            </Button>
          )}
          {canEdit && (
            <Button asChild variant="outline" size="sm" className="gap-1.5">
              <a href={urls.edit}>
                <Edit3 className="h-3.5 w-3.5" />
                Editar
              </a>
            </Button>
          )}
          <Button asChild variant="outline" size="sm" className="gap-1.5">
            <a href={urls.print} target="_blank" rel="noopener noreferrer">
              <Printer className="h-3.5 w-3.5" />
              Imprimir
            </a>
          </Button>
        </div>
      </div>

      {/* ── Status Card ── */}
      <section className="rounded-2xl border border-zinc-200 bg-white shadow-sm overflow-hidden">
        {isCanceled ? (
          <div className="flex items-center gap-5 p-6">
            <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-red-100">
              <XCircle className="h-7 w-7 text-red-500" />
            </div>
            <div>
              <p className="text-base font-bold text-red-700">Pedido Cancelado</p>
              <p className="mt-0.5 text-sm text-red-400">Este pedido foi cancelado e não pode ser reaberto.</p>
            </div>
          </div>
        ) : (
          <div className="p-6">
            <p className="mb-5 text-xs font-semibold uppercase tracking-widest text-zinc-400">Progresso do Pedido</p>
            <StatusTimeline status={currentStatus} />

            {isCompleted ? (
              <div className="mt-6 flex items-center gap-2.5 rounded-xl bg-emerald-50 border border-emerald-100 px-4 py-3">
                <CheckCheck className="h-4 w-4 text-emerald-600 shrink-0" />
                <span className="text-sm font-semibold text-emerald-700">Pedido concluído com sucesso!</span>
              </div>
            ) : isActive && !isIFood && (
              <div className="mt-6 border-t border-zinc-100 pt-5">
                <p className="mb-3 text-xs font-semibold uppercase tracking-widest text-zinc-400">Ações</p>
                <div className="flex flex-wrap items-center gap-3">
                  {currentStatus === 'pending' && (
                    <button
                      type="button"
                      disabled={busy !== null}
                      onClick={() => applyStatusDirect('paid')}
                      className="flex flex-1 min-w-[180px] items-center justify-center gap-2 rounded-xl bg-violet-600 hover:bg-violet-700 active:bg-violet-800 px-5 py-3 text-sm font-semibold text-white shadow-sm transition-all disabled:opacity-60"
                    >
                      {busy === 'paid'
                        ? <Loader2 className="h-4 w-4 animate-spin" />
                        : <Truck className="h-4 w-4" />}
                      {busy === 'paid' ? 'Registrando…' : 'Saiu para entrega'}
                    </button>
                  )}
                  <button
                    type="button"
                    disabled={busy !== null}
                    onClick={() => applyStatusDirect('completed')}
                    className="flex flex-1 min-w-[180px] items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 px-5 py-3 text-sm font-semibold text-white shadow-sm transition-all disabled:opacity-60"
                  >
                    {busy === 'completed'
                      ? <Loader2 className="h-4 w-4 animate-spin" />
                      : <CheckCheck className="h-4 w-4" />}
                    {busy === 'completed' ? 'Concluindo…' : 'Marcar Concluído'}
                  </button>
                  <button
                    type="button"
                    disabled={busy !== null}
                    onClick={() => applyStatusDirect('canceled')}
                    className="flex items-center gap-1.5 rounded-xl border border-red-200 bg-white hover:bg-red-50 active:bg-red-100 px-4 py-3 text-sm font-medium text-red-600 transition-all disabled:opacity-60"
                  >
                    {busy === 'canceled'
                      ? <Loader2 className="h-3.5 w-3.5 animate-spin" />
                      : <XCircle className="h-3.5 w-3.5" />}
                    Cancelar pedido
                  </button>
                </div>
              </div>
            )}
          </div>
        )}
      </section>

      {/* ── Customer + Summary grid ── */}
      <div className="grid gap-5 lg:grid-cols-[3fr_2fr]">

        {/* Customer Card */}
        <section className="rounded-2xl border border-zinc-200 bg-white shadow-sm overflow-hidden">
          <div className="flex items-center gap-3 border-b border-zinc-100 px-5 py-4">
            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50">
              <User2 className="h-4 w-4 text-blue-500" />
            </div>
            <h2 className="text-sm font-semibold text-zinc-800">Cliente</h2>
          </div>
          <div className="p-5 space-y-4">
            <div>
              <p className="text-xl font-bold text-zinc-900">{order.customer_name || '—'}</p>
              {order.customer_phone && (
                <div className="mt-1.5 flex items-center gap-2 text-zinc-500">
                  <Phone className="h-3.5 w-3.5 shrink-0" />
                  <span className="text-sm">{formatPhoneBr(order.customer_phone)}</span>
                </div>
              )}
            </div>

            {order.customer_address ? (
              <div className="flex items-start gap-2.5">
                <MapPin className="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                <p className="text-sm text-zinc-600 whitespace-pre-line leading-relaxed">{order.customer_address}</p>
              </div>
            ) : !isIFood && (
              <div className="flex items-center gap-2 text-zinc-400">
                <MapPin className="h-4 w-4 shrink-0" />
                <p className="text-sm italic">Retirada no local</p>
              </div>
            )}

            {userNotes && (
              <div className="rounded-xl border border-amber-100 bg-amber-50 p-3.5">
                <p className="mb-1 text-[11px] font-bold uppercase tracking-wide text-amber-600">Observações</p>
                <p className="text-sm text-amber-900 whitespace-pre-line">{userNotes}</p>
              </div>
            )}

            {displayInstructions && (
              <div className="rounded-xl border border-sky-100 bg-sky-50 p-3.5">
                <p className="mb-1 text-[11px] font-bold uppercase tracking-wide text-sky-600">Instrução de Pagamento</p>
                <p className="text-sm text-sky-900">{displayInstructions}</p>
              </div>
            )}
          </div>
        </section>

        {/* Summary Card */}
        <section className="rounded-2xl border border-zinc-200 bg-white shadow-sm overflow-hidden">
          <div className="flex items-center gap-3 border-b border-zinc-100 px-5 py-4">
            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50">
              <DollarSign className="h-4 w-4 text-emerald-500" />
            </div>
            <h2 className="text-sm font-semibold text-zinc-800">Resumo Financeiro</h2>
          </div>
          <div className="p-5">
            <dl className="space-y-3">
              <div className="flex items-center justify-between">
                <dt className="text-sm text-zinc-500">Subtotal</dt>
                <dd className="text-sm font-medium text-zinc-800">{formatCurrency(order.subtotal)}</dd>
              </div>
              <div className="flex items-center justify-between">
                <dt className="text-sm text-zinc-500">Taxa de entrega</dt>
                <dd className="text-sm font-medium text-zinc-800">{formatCurrency(order.delivery_fee)}</dd>
              </div>
              {totalDiscount > 0 && (
                <div className="flex items-center justify-between">
                  <dt className="text-sm text-zinc-500">Desconto</dt>
                  <dd className="text-sm font-semibold text-emerald-600">– {formatCurrency(totalDiscount)}</dd>
                </div>
              )}
            </dl>

            <div className="mt-4 flex items-center justify-between rounded-xl bg-zinc-900 px-4 py-3.5">
              <span className="text-sm font-medium text-zinc-400">Total</span>
              <span className="text-2xl font-bold text-white">{formatCurrency(order.total)}</span>
            </div>

            {payload.payment?.name && (
              <div className="mt-4 pt-4 border-t border-zinc-100">
                <p className="mb-3 text-[11px] font-bold uppercase tracking-wide text-zinc-400">Pagamento</p>
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100">
                    <CreditCard className="h-4 w-4 text-zinc-500" />
                  </div>
                  <div className="min-w-0">
                    {payload.payment.type && (
                      <p className="text-[11px] text-zinc-400 leading-none">
                        {payload.payment.type === 'credit' ? 'Crédito'
                          : payload.payment.type === 'debit' ? 'Débito'
                          : payload.payment.type === 'cash' ? 'Dinheiro'
                          : payload.payment.type === 'pix' ? 'PIX'
                          : payload.payment.type === 'voucher' ? 'Vale'
                          : payload.payment.type}
                      </p>
                    )}
                    <p className="mt-0.5 text-sm font-semibold text-zinc-800">{payload.payment.name}</p>
                  </div>
                </div>

                {troco && (
                  <div className="mt-3 flex items-center justify-between rounded-lg border border-zinc-100 bg-zinc-50 px-3 py-2.5">
                    <span className="text-xs text-zinc-500">Troco para R$ {troco.changeFor}</span>
                    {troco.change && (
                      <span className="text-xs font-semibold text-zinc-700">Troco: R$ {troco.change}</span>
                    )}
                  </div>
                )}
              </div>
            )}
          </div>
        </section>
      </div>

      {/* ── iFood info ── */}
      {isIFood && payload.ifood && (
        <IFoodInfoCard data={payload.ifood} />
      )}

      {/* ── Items ── */}
      <section className="rounded-2xl border border-zinc-200 bg-white shadow-sm overflow-hidden">
        <div className="flex items-center gap-3 border-b border-zinc-100 px-5 py-4">
          <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-orange-50">
            <Package className="h-4 w-4 text-orange-500" />
          </div>
          <h2 className="flex-1 text-sm font-semibold text-zinc-800">Itens do Pedido</h2>
          <span className="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-semibold text-zinc-600">
            {order.items.length} {order.items.length === 1 ? 'item' : 'itens'}
          </span>
        </div>
        <div className="divide-y divide-zinc-100">
          {order.items.length === 0 ? (
            <div className="flex flex-col items-center py-12">
              <ShoppingCart className="h-12 w-12 text-zinc-200" />
              <p className="mt-3 text-sm text-zinc-400">Nenhum item neste pedido.</p>
            </div>
          ) : (
            order.items.map((item, idx) => (
              <OrderItemRow key={item.id ?? idx} item={item} />
            ))
          )}
        </div>
        {order.items.length > 0 && (
          <div className="border-t border-zinc-100 bg-zinc-50 px-5 py-3 flex justify-end">
            <span className="text-sm text-zinc-500">Total dos itens: <span className="font-semibold text-zinc-800">{formatCurrency(order.subtotal)}</span></span>
          </div>
        )}
      </section>

      {/* ── History ── */}
      {historyEvents.length > 0 && (
        <HistorySection events={historyEvents} statusLabels={statusLabels} />
      )}

      {/* ── Danger zone ── */}
      <div className="flex items-center justify-between rounded-2xl border border-red-100 bg-red-50/50 px-5 py-4">
        <div>
          <p className="text-sm font-semibold text-red-700">Zona de perigo</p>
          <p className="text-xs text-red-400 mt-0.5">Esta ação é irreversível e remove o pedido permanentemente.</p>
        </div>
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={deleteOrder}
          disabled={busy === 'delete'}
          className="gap-1.5 border-red-200 bg-white text-red-700 hover:bg-red-100 hover:border-red-300"
        >
          {busy === 'delete' ? <Loader2 className="h-3.5 w-3.5 animate-spin" /> : <Trash2 className="h-3.5 w-3.5" />}
          Excluir pedido
        </Button>
      </div>
    </AdminStorePageShell>
  )
}

// ── Sub-components ────────────────────────────────────────────────────────────

function OrderItemRow({ item }: { item: OrderItem }) {
  const totalDelta = Number(item.customization_data?.total_delta ?? 0)
  const baseUnit = item.unit_price - totalDelta
  const showItemNotes = !!item.notes && !!item.product_name
  const comboLines = item.combo_data ? buildComboLines(item.combo_data) : []
  const customLines = item.customization_data ? buildCustomLines(item.customization_data) : []
  const hasExtras = comboLines.length > 0 || customLines.length > 0 || showItemNotes

  return (
    <div className="px-5 py-4 transition-colors hover:bg-zinc-50/70">
      {/* Main row */}
      <div className="flex items-start gap-3.5">
        <div className="flex h-8 min-w-[2rem] shrink-0 items-center justify-center rounded-lg bg-zinc-900 px-2.5 text-xs font-bold text-white">
          {item.quantity}×
        </div>
        <div className="min-w-0 flex-1">
          <p className="text-sm font-semibold text-zinc-900">{item.product_name || item.notes || '—'}</p>
          {totalDelta > 0.009 && (
            <p className="mt-0.5 text-xs text-zinc-400">
              Base: {formatCurrency(baseUnit)}
              <span className="ml-1 text-emerald-600 font-medium">+ personalizações</span>
            </p>
          )}
        </div>
        <div className="shrink-0 text-right">
          <p className="text-base font-bold text-zinc-900">{formatCurrency(item.line_total)}</p>
          {item.quantity > 1 && (
            <p className="text-xs text-zinc-400">{formatCurrency(item.unit_price)} cada</p>
          )}
        </div>
      </div>

      {/* Extras / customizations */}
      {hasExtras && (
        <div className="mt-3 ml-11 space-y-3">
          {comboLines.length > 0 && (
            <div className="rounded-lg bg-zinc-50 border border-zinc-100 px-3.5 py-3">
              <p className="mb-2 text-[10px] font-bold uppercase tracking-widest text-zinc-400">Opções do combo</p>
              <div className="space-y-1.5">
                {comboLines.map((line, i) => (
                  <div key={i}>
                    <div className="flex items-start justify-between gap-3 text-xs">
                      <span className="text-zinc-700 font-medium">• {line.name}</span>
                      <span className="shrink-0 text-zinc-400">
                        {Math.abs(line.delta) > 0.009 ? `+ ${formatCurrency(line.delta)}` : 'Incluso'}
                      </span>
                    </div>
                    {line.unitLines?.map((sub, j) => (
                      <div key={j} className="flex items-start justify-between gap-3 pl-3 text-xs text-zinc-500">
                        <span>{sub.text}</span>
                        <span className={sub.status === 'Removido' ? 'text-red-400' : 'text-zinc-400'}>{sub.status}</span>
                      </div>
                    ))}
                  </div>
                ))}
              </div>
            </div>
          )}

          {customLines.length > 0 && (
            <div className="rounded-lg bg-zinc-50 border border-zinc-100 px-3.5 py-3">
              <p className="mb-2 text-[10px] font-bold uppercase tracking-widest text-zinc-400">Personalizações</p>
              <div className="space-y-1.5">
                {customLines.map((line, i) => (
                  <div key={i} className="flex items-start justify-between gap-3 text-xs">
                    <span className="text-zinc-700 font-medium">• {line.text}</span>
                    <span className={`shrink-0 font-medium ${
                      line.status === 'Removido' ? 'text-red-500' :
                      line.status.startsWith('+') ? 'text-emerald-600' :
                      'text-zinc-400'
                    }`}>{line.status}</span>
                  </div>
                ))}
              </div>
            </div>
          )}

          {showItemNotes && (
            <div className="rounded-lg border border-amber-100 bg-amber-50 px-3.5 py-2.5">
              <p className="text-xs text-amber-700">
                <span className="font-bold">Obs:</span> {item.notes}
              </p>
            </div>
          )}
        </div>
      )}
    </div>
  )
}

function IFoodInfoCard({ data }: { data: IFoodData }) {
  const rawDelivery = (data.raw_data?.delivery ?? {}) as { observations?: string }
  const deliveryObservations = rawDelivery.observations ?? ''
  const addr = data.delivery_address ?? null
  const paymentMethods = data.payments?.methods ?? []

  return (
    <section className="rounded-2xl border border-red-200 bg-white shadow-sm overflow-hidden">
      <div className="flex items-center gap-3 border-b border-red-100 bg-red-50 px-5 py-4">
        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-100">
          <Utensils className="h-4 w-4 text-red-600" />
        </div>
        <h2 className="text-sm font-semibold text-red-800">Informações iFood</h2>
      </div>
      <div className="p-5">
        <div className="grid gap-3 text-sm sm:grid-cols-2">
          <div className="flex flex-col gap-0.5">
            <span className="text-[11px] font-bold uppercase tracking-wide text-zinc-400">ID iFood</span>
            <span className="font-mono text-xs text-zinc-700">{data.ifood_order_id || '—'}</span>
          </div>
          {data.ifood_display_id && (
            <div className="flex flex-col gap-0.5">
              <span className="text-[11px] font-bold uppercase tracking-wide text-zinc-400">Código</span>
              <span className="font-bold text-zinc-900">#{data.ifood_display_id}</span>
            </div>
          )}
          {data.status && (
            <div className="flex flex-col gap-0.5">
              <span className="text-[11px] font-bold uppercase tracking-wide text-zinc-400">Status iFood</span>
              <span className="text-zinc-700">{data.status}</span>
            </div>
          )}
          {data.order_type && (
            <div className="flex flex-col gap-0.5">
              <span className="text-[11px] font-bold uppercase tracking-wide text-zinc-400">Tipo</span>
              <span className="text-zinc-700">
                {data.order_type === 'DELIVERY' ? 'Entrega' : data.order_type === 'TAKEOUT' ? 'Retirada' : data.order_type}
              </span>
            </div>
          )}
          {data.scheduled_datetime && (
            <div className="flex flex-col gap-0.5 sm:col-span-2">
              <span className="text-[11px] font-bold uppercase tracking-wide text-zinc-400">Agendado para</span>
              <span className="font-semibold text-amber-700">{formatDateBr(data.scheduled_datetime)}</span>
            </div>
          )}
          {data.customer_document && (
            <div className="flex flex-col gap-0.5">
              <span className="text-[11px] font-bold uppercase tracking-wide text-zinc-400">CPF/CNPJ</span>
              <span className="text-zinc-700">{data.customer_document}</span>
            </div>
          )}
          {data.pickup_code && (
            <div className="flex flex-col gap-0.5">
              <span className="text-[11px] font-bold uppercase tracking-wide text-zinc-400">Código de Retirada</span>
              <span className="inline-flex items-center rounded-lg bg-zinc-900 px-3 py-1.5 font-mono text-lg font-bold tracking-widest text-white w-fit">
                {data.pickup_code}
              </span>
            </div>
          )}
          {data.delivered_by && (
            <div className="flex flex-col gap-0.5">
              <span className="text-[11px] font-bold uppercase tracking-wide text-zinc-400">Entrega por</span>
              <span className="text-zinc-700">
                {data.delivered_by === 'IFOOD' ? 'iFood' : data.delivered_by === 'MERCHANT' ? 'Loja' : data.delivered_by}
              </span>
            </div>
          )}
          {addr && (addr.streetName || addr.city) && (
            <div className="flex flex-col gap-1 sm:col-span-2">
              <span className="text-[11px] font-bold uppercase tracking-wide text-zinc-400">Endereço de Entrega</span>
              <div className="flex items-start gap-2 rounded-lg bg-zinc-50 border border-zinc-100 p-3">
                <MapPin className="h-4 w-4 shrink-0 text-zinc-400 mt-0.5" />
                <div className="text-sm text-zinc-700 leading-relaxed">
                  {[addr.streetName, addr.streetNumber].filter(Boolean).join(', ')}
                  {addr.complement && ` — ${addr.complement}`}
                  <br />
                  {[addr.neighborhood, [addr.city, addr.state].filter(Boolean).join('/')].filter(Boolean).join(' · ')}
                  {addr.postalCode && <><br />CEP: {addr.postalCode}</>}
                  {addr.reference && <><br /><em className="text-zinc-500">Ref: {addr.reference}</em></>}
                </div>
              </div>
            </div>
          )}
          {deliveryObservations && (
            <div className="flex flex-col gap-1 sm:col-span-2">
              <span className="text-[11px] font-bold uppercase tracking-wide text-zinc-400">Obs. de Entrega</span>
              <p className="rounded-lg bg-zinc-50 border border-zinc-100 p-3 text-sm text-zinc-700 whitespace-pre-line">{deliveryObservations}</p>
            </div>
          )}
          {data.cancellation_reason && (
            <div className="flex flex-col gap-1 sm:col-span-2">
              <span className="text-[11px] font-bold uppercase tracking-wide text-red-400">Motivo do Cancelamento</span>
              <p className="rounded-lg bg-red-50 border border-red-100 p-3 text-sm text-red-700">{data.cancellation_reason}</p>
            </div>
          )}
        </div>

        {paymentMethods.length > 0 && (
          <div className="mt-5 border-t border-zinc-100 pt-5">
            <p className="mb-3 text-[11px] font-bold uppercase tracking-wide text-zinc-400">Pagamento iFood</p>
            <div className="space-y-3">
              {paymentMethods.map((pm, i) => {
                const labels: Record<string, string> = {
                  CREDIT: 'Crédito', DEBIT: 'Débito',
                  MEAL_VOUCHER: 'Vale Refeição', FOOD_VOUCHER: 'Vale Alimentação',
                  PIX: 'PIX', CASH: 'Dinheiro',
                }
                const methodName = pm.method ?? ''
                const display = labels[methodName.toUpperCase()] ?? methodName
                const brand = pm.card?.brand ?? pm.brand
                const prepaid = !!pm.prepaid || pm.type === 'ONLINE'
                const value = Number(pm.value ?? 0)
                const changeFor = Number(pm.cash?.changeFor ?? 0)
                return (
                  <div key={i} className="rounded-lg border border-zinc-100 bg-zinc-50 p-3">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-2">
                        <span className="text-sm font-semibold text-zinc-800">{display}{brand ? ` (${brand})` : ''}</span>
                        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold ${
                          prepaid ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'
                        }`}>
                          {prepaid ? 'Pago Online' : 'Pagar na Entrega'}
                        </span>
                      </div>
                      <span className="text-sm font-bold text-zinc-900">{formatCurrency(value)}</span>
                    </div>
                    {methodName.toUpperCase() === 'CASH' && changeFor > 0 && (
                      <div className="mt-2 flex items-center justify-between pl-2 text-xs text-zinc-500">
                        <span>Troco para: {formatCurrency(changeFor)}</span>
                        <span className="font-medium">Troco: {formatCurrency(changeFor - value)}</span>
                      </div>
                    )}
                  </div>
                )
              })}
            </div>
          </div>
        )}

        {data.benefits && data.benefits.length > 0 && (
          <div className="mt-5 border-t border-zinc-100 pt-5">
            <p className="mb-3 text-[11px] font-bold uppercase tracking-wide text-zinc-400">Cupons / Benefícios</p>
            <div className="space-y-2">
              {data.benefits.map((b, i) => {
                const value = Number(b.value ?? 0)
                const targetLabels: Record<string, string> = { DELIVERY_FEE: 'Frete', ITEM: 'Item', CART: 'Carrinho' }
                const target = targetLabels[b.target ?? ''] ?? b.target
                const ifoodSponsor = b.sponsorshipValues?.IFOOD
                const merchantSponsor = b.sponsorshipValues?.MERCHANT
                return (
                  <div key={i} className="flex items-center justify-between rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2.5 text-sm">
                    <div>
                      <span className="font-semibold text-emerald-800">Desconto {target}</span>
                      {ifoodSponsor != null && merchantSponsor != null ? (
                        <span className="ml-1.5 text-xs text-emerald-600">
                          (iFood: {formatCurrency(Number(ifoodSponsor))} · Loja: {formatCurrency(Number(merchantSponsor))})
                        </span>
                      ) : ifoodSponsor != null ? (
                        <span className="ml-1.5 text-xs text-emerald-600">(Pago pelo iFood)</span>
                      ) : merchantSponsor != null ? (
                        <span className="ml-1.5 text-xs text-emerald-600">(Pago pela Loja)</span>
                      ) : null}
                    </div>
                    <span className="font-bold text-emerald-700">– {formatCurrency(value)}</span>
                  </div>
                )
              })}
            </div>
          </div>
        )}
      </div>
    </section>
  )
}

function HistorySection({ events, statusLabels }: { events: OrderEvent[]; statusLabels: Record<string, string> }) {
  const eventLabelMap: Record<string, string> = {
    'order.created': 'Pedido criado',
    'order.updated': 'Pedido atualizado',
    'order.status_changed': 'Status alterado',
    'order.canceled': 'Pedido cancelado',
  }
  const formatStatus = (s: unknown) => statusLabels[String(s ?? '')] ?? String(s ?? '')

  return (
    <section className="rounded-2xl border border-zinc-200 bg-white shadow-sm overflow-hidden">
      <div className="flex items-center gap-3 border-b border-zinc-100 px-5 py-4">
        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-50">
          <History className="h-4 w-4 text-violet-500" />
        </div>
        <h2 className="text-sm font-semibold text-zinc-800">Histórico</h2>
      </div>

      <div className="p-5">
        <div className="relative">
          {events.length > 1 && (
            <div className="absolute left-[15px] top-5 bottom-5 w-px bg-zinc-200" />
          )}
          <div className="space-y-5">
            {events.map((event, idx) => {
              const label = eventLabelMap[event.event_type ?? ''] ?? event.event_type ?? 'Evento'
              const meta = event.payload?.meta ?? {}
              const statusChange = meta.status_change
              const orderUpdate = meta.order_update
              const before = orderUpdate?.before as Record<string, unknown> | undefined
              const after = orderUpdate?.after as Record<string, unknown> | undefined
              const isFirst = idx === 0

              return (
                <div key={idx} className="relative flex items-start gap-4">
                  <div className={`relative z-10 flex h-[30px] w-[30px] shrink-0 items-center justify-center rounded-full border-2 ${
                    isFirst ? 'border-violet-300 bg-violet-50' : 'border-zinc-200 bg-white'
                  }`}>
                    <div className={`h-2.5 w-2.5 rounded-full ${isFirst ? 'bg-violet-500' : 'bg-zinc-300'}`} />
                  </div>
                  <div className="flex-1 pb-1 min-w-0">
                    <div className="flex flex-wrap items-baseline gap-2">
                      <p className="text-sm font-semibold text-zinc-800">{label}</p>
                      {event.created_at && (
                        <p className="text-xs text-zinc-400">{formatDateBr(event.created_at)}</p>
                      )}
                    </div>
                    {statusChange ? (
                      <div className="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-zinc-500">
                        <span className="rounded-full bg-zinc-100 px-2 py-0.5 font-medium text-zinc-600">
                          {formatStatus(statusChange.from)}
                        </span>
                        <span className="text-zinc-300">→</span>
                        <span className="rounded-full bg-violet-100 px-2 py-0.5 font-medium text-violet-700">
                          {formatStatus(statusChange.to)}
                        </span>
                      </div>
                    ) : event.event_type === 'order.status_changed' && event.status ? (
                      <div className="mt-1">
                        <span className="rounded-full bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-700">
                          {formatStatus(event.status)}
                        </span>
                      </div>
                    ) : null}
                    {event.event_type === 'order.updated' && before && after && (
                      <UpdateDiff before={before} after={after} />
                    )}
                  </div>
                </div>
              )
            })}
          </div>
        </div>
      </div>
    </section>
  )
}

type UpdateValues = Record<string, unknown>

function UpdateDiff({ before, after }: { before: UpdateValues; after: UpdateValues }) {
  const num = (v: unknown) => Number(v ?? 0)
  const totalDiscount = (r: UpdateValues) => num(r.discount) + num(r.loyalty_discount)
  return (
    <div className="mt-3 grid gap-3 rounded-lg bg-zinc-50 border border-zinc-100 p-3 sm:grid-cols-2">
      {[before, after].map((row, idx) => (
        <div key={idx}>
          <div className={`mb-1.5 text-[10px] font-bold uppercase tracking-wide ${idx === 0 ? 'text-zinc-400' : 'text-violet-500'}`}>
            {idx === 0 ? 'Antes' : 'Depois'}
          </div>
          <div className="space-y-1 text-xs text-zinc-600">
            <div className="flex justify-between">
              <span>Subtotal</span>
              <span className="font-medium">{formatCurrency(num(row.subtotal))}</span>
            </div>
            <div className="flex justify-between">
              <span>Entrega</span>
              <span className="font-medium">{formatCurrency(num(row.delivery_fee))}</span>
            </div>
            {totalDiscount(row) > 0 && (
              <div className="flex justify-between">
                <span>Desconto</span>
                <span className="font-medium text-emerald-600">– {formatCurrency(totalDiscount(row))}</span>
              </div>
            )}
            <div className="flex justify-between border-t border-zinc-200 pt-1 mt-1">
              <span className="font-semibold text-zinc-800">Total</span>
              <span className="font-bold text-zinc-900">{formatCurrency(num(row.total))}</span>
            </div>
          </div>
        </div>
      ))}
      {(Array.isArray(before.items) || Array.isArray(after.items)) && (
        <>
          {[before.items, after.items].map((items, idx) => (
            <div key={`items-${idx}`}>
              <div className={`mb-1.5 text-[10px] font-bold uppercase tracking-wide ${idx === 0 ? 'text-zinc-400' : 'text-violet-500'}`}>
                {idx === 0 ? 'Itens antes' : 'Itens depois'}
              </div>
              {Array.isArray(items) && items.length > 0 ? (
                <ul className="space-y-1 text-xs text-zinc-600">
                  {(items as Array<Record<string, unknown>>).map((it, i) => {
                    const qty = num(it.quantity)
                    const name = String(it.name ?? '')
                    const line = Number(it.line_total ?? num(it.unit_price) * qty)
                    return (
                      <li key={i} className="flex justify-between">
                        <span>{qty}× {name}</span>
                        <span className="font-medium">{formatCurrency(line)}</span>
                      </li>
                    )
                  })}
                </ul>
              ) : (
                <p className="text-xs text-zinc-400 italic">Sem itens</p>
              )}
            </div>
          ))}
        </>
      )}
    </div>
  )
}
