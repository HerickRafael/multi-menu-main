import { useMemo, useState } from 'react'
import {
  ArrowLeft,
  Bike,
  CheckCheck,
  CheckCircle2,
  ClipboardList,
  Clock,
  CreditCard,
  Edit3,
  Globe,
  History,
  MapPin,
  MessageSquare,
  Package,
  Printer,
  ShoppingBag,
  ShoppingCart,
  Smartphone,
  Trash2,
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
  paid: 1, confirmed: 1,
  ready: 2,
  dispatched: 3,
  completed: 4, concluded: 4,
  canceled: -1, cancelled: -1,
}

type TimelineStep = {
  key: string
  normKeys: string[]  // all DB/iFood values that map to this step
  label: string
  Icon: typeof Clock
  activeColor: string
  activeBg: string
  activeBorder: string
  activeText: string
}

const REGULAR_STEPS: TimelineStep[] = [
  { key: 'pending',   normKeys: ['pending'],          label: 'Aguardando', Icon: Clock,          activeColor: 'bg-amber-500',   activeBg: 'bg-amber-50',   activeBorder: 'border-amber-200', activeText: 'text-amber-700' },
  { key: 'paid',      normKeys: ['paid','confirmed'],  label: 'Confirmado', Icon: CheckCircle2,   activeColor: 'bg-blue-500',    activeBg: 'bg-blue-50',    activeBorder: 'border-blue-200',  activeText: 'text-blue-700' },
  { key: 'completed', normKeys: ['completed'],         label: 'Concluído',  Icon: CheckCheck,     activeColor: 'bg-emerald-500', activeBg: 'bg-emerald-50', activeBorder: 'border-emerald-200', activeText: 'text-emerald-700' },
]

const IFOOD_STEPS: TimelineStep[] = [
  { key: 'pending',    normKeys: ['pending'],              label: 'Aguardando', Icon: Clock,        activeColor: 'bg-amber-500',   activeBg: 'bg-amber-50',   activeBorder: 'border-amber-200',   activeText: 'text-amber-700'  },
  { key: 'confirmed',  normKeys: ['confirmed','paid'],      label: 'Confirmado', Icon: CheckCircle2, activeColor: 'bg-blue-500',    activeBg: 'bg-blue-50',    activeBorder: 'border-blue-200',    activeText: 'text-blue-700'   },
  { key: 'ready',      normKeys: ['ready'],                 label: 'Pronto',     Icon: Package,      activeColor: 'bg-indigo-500',  activeBg: 'bg-indigo-50',  activeBorder: 'border-indigo-200',  activeText: 'text-indigo-700' },
  { key: 'dispatched', normKeys: ['dispatched'],            label: 'Em Entrega', Icon: Bike,         activeColor: 'bg-purple-500',  activeBg: 'bg-purple-50',  activeBorder: 'border-purple-200',  activeText: 'text-purple-700' },
  { key: 'completed',  normKeys: ['completed','concluded'], label: 'Concluído',  Icon: CheckCheck,   activeColor: 'bg-emerald-500', activeBg: 'bg-emerald-50', activeBorder: 'border-emerald-200', activeText: 'text-emerald-700'},
]

function StatusTimeline({ status, isIfood }: { status: string; isIfood: boolean }) {
  const steps = isIfood ? IFOOD_STEPS : REGULAR_STEPS
  const rank = STATUS_RANK[status] ?? 0
  const isCanceled = status === 'canceled' || status === 'cancelled'

  if (isCanceled) {
    return (
      <div className="rounded-2xl border border-red-200 bg-red-50 p-4">
        <div className="flex items-center gap-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-full bg-red-500 text-white shadow-sm">
            <XCircle className="h-5 w-5" />
          </div>
          <div>
            <p className="text-sm font-semibold text-red-700">Pedido Cancelado</p>
            <p className="text-xs text-red-500">Este pedido foi cancelado</p>
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
      <p className="mb-4 text-xs font-semibold uppercase tracking-wider text-zinc-400">Progresso do Pedido</p>
      <div className="flex items-start">
        {steps.map((step, i) => {
          const stepRank = STATUS_RANK[step.key] ?? 0
          const done    = rank > stepRank
          const current = rank === stepRank
          const active  = done || current
          const Icon    = step.Icon

          return (
            <div key={step.key} className="flex flex-1 flex-col items-center">
              {/* connector line + circle row */}
              <div className="flex w-full items-center">
                {/* left line */}
                <div className={`h-0.5 flex-1 transition-colors ${i === 0 ? 'invisible' : done || current ? step.activeColor : 'bg-zinc-200'}`} />
                {/* circle */}
                <div className={`relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full border-2 transition-all ${
                  done    ? `${step.activeColor} border-transparent text-white shadow-sm` :
                  current ? `${step.activeBg} ${step.activeBorder} ${step.activeText} shadow-md ring-4 ring-offset-1 ${step.activeBorder.replace('border-', 'ring-')}` :
                            'border-zinc-200 bg-white text-zinc-300'
                }`}>
                  <Icon className="h-4 w-4" />
                  {current && (
                    <span className={`absolute inset-0 animate-ping rounded-full opacity-30 ${step.activeBg}`} />
                  )}
                </div>
                {/* right line */}
                <div className={`h-0.5 flex-1 transition-colors ${i === steps.length - 1 ? 'invisible' : done ? step.activeColor : 'bg-zinc-200'}`} />
              </div>
              {/* label */}
              <p className={`mt-2 text-center text-[11px] font-medium leading-tight ${
                active ? step.activeText : 'text-zinc-300'
              }`}>
                {step.label}
              </p>
            </div>
          )
        })}
      </div>
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
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_ORDER__) || ({} as Payload)

  const order = payload.order
  const urls = payload.urls
  const statusLabels = payload.status_labels ?? {}

  const [busy, setBusy] = useState<string | null>(null)

  const orderNumber = order?.order_number ?? order?.id ?? 0
  const isIFood = (order?.source ?? '') === 'ifood'
  const canEdit = order?.status === 'pending' && !isIFood
  const totalDiscount = (order?.discount ?? 0) + (order?.loyalty_discount ?? 0)
  const statusLabel = statusLabels[order?.status ?? ''] ?? (order?.status ?? '')

  const { userNotes, displayInstructions } = useMemo(() => {
    return parseUserNotesAndInstructions(order?.notes ?? '', payload.payment?.instructions ?? null)
  }, [order?.notes, payload.payment?.instructions])

  const troco = useMemo(() => parseTrocoFromNotes(order?.notes ?? ''), [order?.notes])

  const historyEvents = useMemo(() => {
    const allowed = new Set(['order.created', 'order.updated', 'order.status_changed', 'order.canceled'])
    return (payload.events ?? []).filter((e) => allowed.has(e.event_type ?? ''))
  }, [payload.events])

  if (!order || !urls) {
    return (
      <AdminStorePageShell section="orders">
        <AdminPageHeader title="Pedido" />
        <div className="rounded-2xl border border-zinc-200 bg-white p-12 text-center">
          <p className="text-sm text-zinc-500">Pedido não encontrado.</p>
        </div>
      </AdminStorePageShell>
    )
  }

  async function applyStatusDirect(newStatus: string) {
    setBusy('status')
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
      if (res.redirected || res.ok) {
        showToast('Status atualizado.', 'success')
        setTimeout(() => window.location.reload(), 500)
      } else {
        showToast('Falha ao atualizar status.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
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

  return (
    <AdminStorePageShell section="orders">
      <AdminPageHeader
        title={`Pedido #${orderNumber}`}
        description={statusLabel}
        icon={<ShoppingBag className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <div className="flex flex-wrap gap-2">
            <Button asChild variant="outline" size="sm" className="gap-1.5">
              <a href={urls.list}>
                <ArrowLeft className="h-3.5 w-3.5" />
                Voltar
              </a>
            </Button>
            {payload.whatsapp_url && (
              <Button asChild variant="outline" size="sm" className="gap-1.5 text-emerald-700 border-emerald-300 hover:bg-emerald-50">
                <a href={payload.whatsapp_url} target="_blank" rel="noopener noreferrer">
                  <MessageSquare className="h-3.5 w-3.5" />
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
        }
      />

      {/* Status timeline */}
      <StatusTimeline status={order.status} isIfood={isIFood} />

      {/* Meta row */}
      <div className="flex flex-wrap items-center gap-2 text-sm">
        <SourceBadge source={order.source} />
        {order.created_at && (
          <span className="ml-auto text-xs text-zinc-500">
            Criado em: {formatDateBr(order.created_at)}
          </span>
        )}
      </div>

      {/* Customer + Summary grid */}
      <div className="grid gap-4 md:grid-cols-2">
        {/* Customer */}
        <section className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <h2 className="mb-2 text-sm font-medium text-zinc-700">Cliente</h2>
          <div className="text-lg font-semibold text-zinc-900">{order.customer_name || '—'}</div>
          <div className="text-zinc-700">{order.customer_phone ? formatPhoneBr(order.customer_phone) : '—'}</div>
          {order.customer_address ? (
            <div className="mt-1 whitespace-pre-line text-sm text-zinc-700">{order.customer_address}</div>
          ) : !isIFood ? (
            <div className="mt-1 text-xs italic text-zinc-400">Endereço não informado</div>
          ) : null}

          {(userNotes || displayInstructions) && (
            <div className="mt-3 rounded-xl bg-zinc-50 p-3 text-sm">
              <div className="mb-1 text-xs font-medium text-zinc-500">Observações</div>
              {userNotes && <div className="whitespace-pre-line text-zinc-800">{userNotes}</div>}
              {displayInstructions && (
                <div className="mt-1 text-xs text-amber-700">{displayInstructions}</div>
              )}
            </div>
          )}
        </section>

        {/* Summary + Status update */}
        <section className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <h2 className="mb-2 text-sm font-medium text-zinc-700">Resumo</h2>
          <dl className="space-y-1 text-zinc-800">
            <div className="flex justify-between"><dt>Subtotal</dt><dd>{formatCurrency(order.subtotal)}</dd></div>
            <div className="flex justify-between"><dt>Entrega</dt><dd>{formatCurrency(order.delivery_fee)}</dd></div>
            <div className="flex justify-between">
              <dt>Desconto</dt>
              <dd>{totalDiscount > 0 ? `- ${formatCurrency(totalDiscount)}` : formatCurrency(0)}</dd>
            </div>
          </dl>
          <div className="mt-2 flex items-center justify-between rounded-xl bg-zinc-50 px-3 py-2">
            <div className="text-sm text-zinc-600">Total</div>
            <div className="text-xl font-semibold text-zinc-900">{formatCurrency(order.total)}</div>
          </div>

          {payload.payment?.name && (
            <div className="mt-3 flex items-center gap-2">
              <CreditCard className="h-5 w-5 shrink-0 text-zinc-500" />
              <div className="min-w-0">
                {payload.payment.type && (
                  <div className="mb-0.5 text-xs leading-none text-zinc-500">
                    {payload.payment.type === 'credit' ? 'Crédito'
                      : payload.payment.type === 'debit' ? 'Débito'
                      : payload.payment.type === 'cash' ? 'Dinheiro'
                      : payload.payment.type === 'pix' ? 'PIX'
                      : payload.payment.type === 'voucher' ? 'Vale'
                      : payload.payment.type}
                  </div>
                )}
                <div className="text-sm font-medium text-zinc-800">{payload.payment.name}</div>
              </div>
            </div>
          )}

          {troco && (
            <div className="mt-1 flex items-center gap-1 pl-1 text-sm text-zinc-600">
              <span>💰</span>
              <span>Troco para: R$ {troco.changeFor}</span>
              {troco.change && (
                <span className="text-xs text-zinc-400">(Troco: R$ {troco.change})</span>
              )}
            </div>
          )}

          {!isIFood && order.status !== 'completed' && order.status !== 'canceled' && (
            <div className="mt-3 flex flex-wrap gap-2 border-t border-zinc-100 pt-3">
              <p className="w-full text-xs font-medium text-zinc-500">Avançar status:</p>
              {order.status === 'pending' && (
                <Button
                  type="button"
                  size="sm"
                  disabled={busy === 'status'}
                  onClick={() => applyStatusDirect('paid')}
                  className="gap-1.5 bg-blue-600 hover:bg-blue-700 text-white border-0"
                >
                  <CheckCircle2 className="h-3.5 w-3.5" />
                  Confirmar pedido
                </Button>
              )}
              {order.status === 'paid' && (
                <Button
                  type="button"
                  size="sm"
                  disabled={busy === 'status'}
                  onClick={() => applyStatusDirect('completed')}
                  className="gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white border-0"
                >
                  <CheckCheck className="h-3.5 w-3.5" />
                  Marcar como concluído
                </Button>
              )}
              <Button
                type="button"
                size="sm"
                variant="outline"
                disabled={busy === 'status'}
                onClick={() => applyStatusDirect('canceled')}
                className="gap-1.5 border-red-200 text-red-600 hover:bg-red-50"
              >
                <XCircle className="h-3.5 w-3.5" />
                Cancelar pedido
              </Button>
            </div>
          )}
        </section>
      </div>

      {/* iFood info */}
      {isIFood && payload.ifood && (
        <IFoodInfoCard data={payload.ifood} />
      )}

      {/* Items */}
      <section className="rounded-2xl border border-zinc-200 bg-white shadow-sm">
        <div className="border-b border-zinc-200 px-4 py-3">
          <h2 className="text-sm font-medium text-zinc-700">Itens do Pedido</h2>
        </div>
        <div className="divide-y divide-zinc-100">
          {order.items.length === 0 ? (
            <div className="p-8 text-center">
              <ShoppingCart className="mx-auto h-12 w-12 text-zinc-300" />
              <p className="mt-2 text-sm text-zinc-500">Sem itens neste pedido.</p>
            </div>
          ) : (
            order.items.map((item, idx) => (
              <OrderItemRow key={item.id ?? idx} item={item} />
            ))
          )}
        </div>
      </section>

      {/* History */}
      {historyEvents.length > 0 && (
        <HistorySection events={historyEvents} statusLabels={statusLabels} />
      )}

      {/* Danger zone */}
      <div className="flex justify-end pt-2">
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={deleteOrder}
          disabled={busy === 'delete'}
          className="gap-1.5 border-red-300 text-red-700 hover:bg-red-50"
        >
          <Trash2 className="h-4 w-4" />
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

  return (
    <div className="p-4 transition-colors hover:bg-zinc-50/60">
      <div className="mb-3 flex items-start justify-between gap-4">
        <div className="flex-1">
          <div className="flex items-center gap-2">
            <span className="inline-flex items-center justify-center rounded-lg bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-700">
              {item.quantity}x
            </span>
            <h3 className="text-base font-semibold text-zinc-900">
              {item.product_name || item.notes || '—'}
            </h3>
          </div>
        </div>
        <div className="text-right">
          {totalDelta > 0.009 && (
            <div className="mb-0.5 text-xs text-zinc-400">
              {formatCurrency(baseUnit)}{' '}
              <span className="font-medium text-emerald-600">+{formatCurrency(totalDelta)}</span>
            </div>
          )}
          <div className="text-lg font-bold text-zinc-900">{formatCurrency(item.line_total)}</div>
        </div>
      </div>

      {comboLines.length > 0 && (
        <div className="mt-3 border-t border-zinc-100 pt-3">
          <div className="mb-2 text-xs font-semibold uppercase tracking-wider text-zinc-500">
            Opções do Combo
          </div>
          <div className="space-y-1.5">
            {comboLines.map((line, i) => (
              <div key={i}>
                <div className="flex items-center justify-between text-sm">
                  <span className={i % 2 === 0 ? 'font-medium text-zinc-700' : 'text-zinc-600'}>
                    {line.name}
                  </span>
                  <span className="text-zinc-400">
                    {Math.abs(line.delta) > 0.009 ? `+ ${formatCurrency(line.delta)}` : 'Incluso'}
                  </span>
                </div>
                {line.unitLines && line.unitLines.map((sub, j) => (
                  <div key={j} className="flex items-center justify-between pl-4 text-sm">
                    <span className="text-zinc-600">{sub.text}</span>
                    <span className="text-xs text-zinc-400">{sub.status}</span>
                  </div>
                ))}
              </div>
            ))}
          </div>
        </div>
      )}

      {customLines.length > 0 && (
        <div className="mt-3 border-t border-zinc-100 pt-3">
          <div className="mb-2 text-xs font-semibold uppercase tracking-wider text-zinc-500">
            Personalize os ingredientes
          </div>
          <div className="space-y-1.5">
            {customLines.map((line, i) => (
              <div key={i} className="flex items-center justify-between text-sm">
                <span className={i % 2 === 0 ? 'font-medium text-zinc-700' : 'text-zinc-600'}>
                  {line.text}
                </span>
                <span className="text-zinc-400">{line.status}</span>
              </div>
            ))}
          </div>
        </div>
      )}

      {showItemNotes && (
        <div className="mt-3 border-t border-zinc-100 pt-3">
          <div className="mb-2 text-xs font-semibold uppercase tracking-wider text-zinc-500">
            Observações
          </div>
          <p className="whitespace-pre-line text-sm text-zinc-600">{item.notes}</p>
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
    <section className="rounded-2xl border border-red-200 bg-red-50 p-4 shadow-sm">
      <div className="mb-3 flex items-center gap-2">
        <Utensils className="h-5 w-5 text-red-600" />
        <h2 className="text-sm font-semibold text-red-800">Informações do iFood</h2>
      </div>
      <div className="grid gap-3 text-sm sm:grid-cols-2">
        <div>
          <span className="font-medium text-red-700">ID iFood:</span>
          <span className="ml-1 font-mono text-xs text-red-900">{data.ifood_order_id || '—'}</span>
        </div>
        {data.ifood_display_id && (
          <div>
            <span className="font-medium text-red-700">Código:</span>
            <span className="ml-1 font-semibold text-red-900">#{data.ifood_display_id}</span>
          </div>
        )}
        {data.status && (
          <div>
            <span className="font-medium text-red-700">Status iFood:</span>
            <span className="ml-1 text-red-900">{data.status}</span>
          </div>
        )}
        {data.order_type && (
          <div>
            <span className="font-medium text-red-700">Tipo:</span>
            <span className="ml-1 text-red-900">
              {data.order_type === 'DELIVERY' ? 'Entrega' : data.order_type === 'TAKEOUT' ? 'Retirada' : data.order_type}
            </span>
          </div>
        )}
        {data.scheduled_datetime && (
          <div>
            <span className="font-medium text-red-700">📅 Agendado para:</span>
            <span className="ml-1 font-semibold text-red-900">{formatDateBr(data.scheduled_datetime)}</span>
          </div>
        )}
        {data.order_timing === 'SCHEDULED' && (
          <div>
            <span className="font-medium text-red-700">Timing:</span>
            <span className="ml-1 inline-flex items-center rounded bg-amber-200 px-2 py-0.5 text-xs font-semibold text-amber-900">
              AGENDADO
            </span>
          </div>
        )}
        {data.customer_document && (
          <div>
            <span className="font-medium text-red-700">CPF/CNPJ:</span>
            <span className="ml-1 text-red-900">{data.customer_document}</span>
          </div>
        )}
        {data.pickup_code && (
          <div>
            <span className="font-medium text-red-700">🔑 Código de Retirada:</span>
            <span className="ml-1 rounded bg-white px-2 py-0.5 font-mono text-lg font-bold text-red-900">
              {data.pickup_code}
            </span>
          </div>
        )}
        {data.delivered_by && (
          <div>
            <span className="font-medium text-red-700">Entrega por:</span>
            <span className="ml-1 text-red-900">
              {data.delivered_by === 'IFOOD' ? 'iFood' : data.delivered_by === 'MERCHANT' ? 'Loja' : data.delivered_by}
            </span>
          </div>
        )}
        {addr && (addr.streetName || addr.city) && (
          <div className="sm:col-span-2">
            <span className="font-medium text-red-700">
              <MapPin className="mr-1 inline-block h-3.5 w-3.5" />
              Endereço iFood:
            </span>
            <div className="mt-1 text-red-900">
              {[addr.streetName, addr.streetNumber].filter(Boolean).join(', ')}
              {addr.complement && ` - ${addr.complement}`}
              <br />
              {[addr.neighborhood, [addr.city, addr.state].filter(Boolean).join('/')].filter(Boolean).join(' - ')}
              {addr.postalCode && <><br />CEP: {addr.postalCode}</>}
              {addr.reference && <><br /><em>Ref: {addr.reference}</em></>}
            </div>
          </div>
        )}
        {deliveryObservations && (
          <div className="sm:col-span-2">
            <span className="font-medium text-red-700">📝 Obs. Entrega:</span>
            <div className="mt-1 whitespace-pre-line rounded-lg bg-white p-2 text-red-900">
              {deliveryObservations}
            </div>
          </div>
        )}
        {data.created_at && (
          <div>
            <span className="font-medium text-red-700">Recebido em:</span>
            <span className="ml-1 text-red-900">{data.created_at}</span>
          </div>
        )}
        {data.cancellation_reason && (
          <div className="sm:col-span-2">
            <span className="font-medium text-red-700">❌ Motivo Cancelamento:</span>
            <span className="ml-1 text-red-900">{data.cancellation_reason}</span>
          </div>
        )}
      </div>

      {paymentMethods.length > 0 && (
        <div className="mt-4 border-t border-red-200 pt-3">
          <div className="mb-2 text-xs font-semibold uppercase tracking-wider text-red-700">Pagamento</div>
          <div className="space-y-2">
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
                <div key={i}>
                  <div className="flex items-center justify-between text-sm">
                    <div>
                      <span className="font-medium text-red-900">{display}{brand ? ` (${brand})` : ''}</span>
                      <span className={`ml-1 inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium ${
                        prepaid ? 'bg-green-200 text-green-800' : 'bg-yellow-200 text-yellow-800'
                      }`}>
                        {prepaid ? 'Pago Online' : 'Pagar na Entrega'}
                      </span>
                    </div>
                    <span className="font-semibold text-red-900">{formatCurrency(value)}</span>
                  </div>
                  {methodName.toUpperCase() === 'CASH' && changeFor > 0 && (
                    <>
                      <div className="flex items-center justify-between pl-4 text-sm">
                        <span className="text-red-700">💰 Troco para:</span>
                        <span className="font-semibold text-red-900">{formatCurrency(changeFor)}</span>
                      </div>
                      <div className="flex items-center justify-between pl-4 text-sm">
                        <span className="text-red-700">Troco:</span>
                        <span className="font-semibold text-red-900">{formatCurrency(changeFor - value)}</span>
                      </div>
                    </>
                  )}
                </div>
              )
            })}
          </div>
        </div>
      )}

      {data.benefits && data.benefits.length > 0 && (
        <div className="mt-4 border-t border-red-200 pt-3">
          <div className="mb-2 text-xs font-semibold uppercase tracking-wider text-red-700">
            Cupons / Benefícios
          </div>
          <div className="space-y-1.5">
            {data.benefits.map((b, i) => {
              const value = Number(b.value ?? 0)
              const targetLabels: Record<string, string> = { DELIVERY_FEE: 'Frete', ITEM: 'Item', CART: 'Carrinho' }
              const target = targetLabels[b.target ?? ''] ?? b.target
              const ifoodSponsor = b.sponsorshipValues?.IFOOD
              const merchantSponsor = b.sponsorshipValues?.MERCHANT
              return (
                <div key={i} className="flex items-center justify-between text-sm">
                  <div>
                    <span className="font-medium text-red-900">🎫 Desconto {target}</span>
                    {ifoodSponsor != null && merchantSponsor != null ? (
                      <span className="ml-1 text-xs text-red-600">
                        (iFood: {formatCurrency(Number(ifoodSponsor))} | Loja: {formatCurrency(Number(merchantSponsor))})
                      </span>
                    ) : ifoodSponsor != null ? (
                      <span className="ml-1 text-xs text-red-600">(Pago pelo iFood)</span>
                    ) : merchantSponsor != null ? (
                      <span className="ml-1 text-xs text-red-600">(Pago pela Loja)</span>
                    ) : null}
                  </div>
                  <span className="font-semibold text-emerald-700">- {formatCurrency(value)}</span>
                </div>
              )
            })}
          </div>
        </div>
      )}
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
    <section className="rounded-2xl border border-zinc-200 bg-white shadow-sm">
      <div className="border-b border-zinc-200 px-4 py-3">
        <h2 className="inline-flex items-center gap-2 text-sm font-medium text-zinc-700">
          <History className="h-4 w-4 text-zinc-500" />
          Histórico
        </h2>
      </div>
      <div className="divide-y divide-zinc-100">
        {events.map((event, idx) => {
          const label = eventLabelMap[event.event_type ?? ''] ?? event.event_type ?? 'Evento'
          const meta = event.payload?.meta ?? {}
          const statusChange = meta.status_change
          const orderUpdate = meta.order_update
          const before = orderUpdate?.before as Record<string, unknown> | undefined
          const after = orderUpdate?.after as Record<string, unknown> | undefined
          return (
            <div key={idx} className="p-4 text-sm text-zinc-700">
              <div className="flex items-center justify-between gap-2">
                <div className="font-semibold text-zinc-900">{label}</div>
                {event.created_at && (
                  <div className="text-xs text-zinc-500">{formatDateBr(event.created_at)}</div>
                )}
              </div>
              {statusChange ? (
                <div className="mt-1 text-zinc-600">
                  Status: {formatStatus(statusChange.from)} → {formatStatus(statusChange.to)}
                </div>
              ) : event.event_type === 'order.status_changed' && event.status ? (
                <div className="mt-1 text-zinc-600">Status: {formatStatus(event.status)}</div>
              ) : null}
              {event.event_type === 'order.updated' && before && after && (
                <UpdateDiff before={before} after={after} />
              )}
            </div>
          )
        })}
      </div>
    </section>
  )
}

type UpdateValues = Record<string, unknown>

function UpdateDiff({ before, after }: { before: UpdateValues; after: UpdateValues }) {
  const num = (v: unknown) => Number(v ?? 0)
  const totalDiscount = (r: UpdateValues) => num(r.discount) + num(r.loyalty_discount)
  return (
    <>
      <div className="mt-3 grid gap-3 sm:grid-cols-2">
        {[before, after].map((row, idx) => (
          <div key={idx}>
            <div className="text-xs uppercase text-zinc-500">{idx === 0 ? 'Antes' : 'Depois'}</div>
            <div className="text-zinc-700">Subtotal: {formatCurrency(num(row.subtotal))}</div>
            <div className="text-zinc-700">Entrega: {formatCurrency(num(row.delivery_fee))}</div>
            <div className="text-zinc-700">
              Desconto: {totalDiscount(row) > 0 ? `- ${formatCurrency(totalDiscount(row))}` : formatCurrency(0)}
            </div>
            <div className="font-semibold text-zinc-900">Total: {formatCurrency(num(row.total))}</div>
          </div>
        ))}
      </div>
      {(Array.isArray(before.items) || Array.isArray(after.items)) && (
        <div className="mt-3 grid gap-3 sm:grid-cols-2">
          {[before.items, after.items].map((items, idx) => (
            <div key={idx}>
              <div className="text-xs uppercase text-zinc-500">{idx === 0 ? 'Itens antes' : 'Itens depois'}</div>
              {Array.isArray(items) && items.length > 0 ? (
                <ul className="mt-1 space-y-1 text-zinc-600">
                  {(items as Array<Record<string, unknown>>).map((it, i) => {
                    const qty = num(it.quantity)
                    const name = String(it.name ?? '')
                    const line = Number(it.line_total ?? num(it.unit_price) * qty)
                    return <li key={i}>{`${qty}x ${name}`} — {formatCurrency(line)}</li>
                  })}
                </ul>
              ) : (
                <div className="mt-1 text-xs text-zinc-400">Sem itens</div>
              )}
            </div>
          ))}
        </div>
      )}
    </>
  )
}
