import { useEffect, useMemo, useRef, useState } from 'react'
import {
  Bike,
  CheckCheck,
  CheckCircle2,
  ClipboardList,
  Clock,
  Eye,
  Globe,
  Loader2,
  MoreVertical,
  Package,
  PackageOpen,
  PlusCircle,
  RefreshCw,
  ShoppingBag,
  ShoppingBag as EmptyIcon,
  Truck,
  XCircle,
} from 'lucide-react'

function IFoodLogo({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" className={className}>
      <path d="M8.428 1.67c-4.65 0-7.184 4.149-7.184 6.998 0 2.294 2.2 3.299 4.25 3.299l-.006-.006c4.244 0 7.184-3.854 7.184-6.998 0-2.29-2.175-3.293-4.244-3.293zm11.328 0c-4.65 0-7.184 4.149-7.184 6.998 0 2.294 2.2 3.299 4.25 3.299l-.006-.006C21.061 11.96 24 8.107 24 4.963c0-2.29-2.18-3.293-4.244-3.293zM14.172 14.52l2.435 1.834c-2.17 2.07-6.124 3.525-9.353 3.17A8.913 8.913 0 01.23 14.541H0a9.598 9.598 0 008.828 7.758c3.814.24 7.323-.905 9.947-3.13l-.004.007 1.08 2.988 1.555-7.623-7.234-.02Z" />
    </svg>
  )
}

function SourceIcon({ source, className }: { source: string; className?: string }) {
  const s = (source || 'manual').toLowerCase()
  if (s === 'ifood') return <IFoodLogo className={className ?? 'h-3.5 w-3.5 text-red-500'} />
  if (s === 'website') return <Globe className={className ?? 'h-3.5 w-3.5 text-blue-500'} />
  return <ClipboardList className={className ?? 'h-3.5 w-3.5 text-slate-400'} />
}
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
import { NewOrderToastContainer, type NewOrderToastData, ORDER_TOAST_AUTO_DISMISS_MS } from '@/components/admin-store/new-order-toast'

// ── Types ────────────────────────────────────────────────────────────────────

type UnifiedOrder = {
  id: number
  display_id: string
  source: string
  is_ifood: boolean
  customer_name: string
  customer_phone: string
  status: string        // normalized: pending/confirmed/ready/dispatched/completed/canceled
  status_raw: string    // original DB value
  total: number
  created_at: string
  items_count: number
  order_type?: string   // DELIVERY/TAKEOUT (iFood only)
  delivered_by?: string // IFOOD/MERCHANT (iFood only)
  ifood_row_id?: number | null
  local_order_id?: number | null
}

type Payload = {
  orders: UnifiedOrder[]
  current_status: string | null
  current_source: string | null
  status_labels: Record<string, string>
  urls: {
    list: string
    create: string
    show_base: string
    edit_base: string
    ifood_action_base: string
    ifood_poll: string
    ifood_config: string
    local_order_base: string
    poll?: string
    notification_sound?: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_ORDERS__?: Payload
  }
}

// ── Status tabs ───────────────────────────────────────────────────────────────

const STATUS_TABS: Array<{ value: string; label: string; tone: string }> = [
  { value: 'pending',    label: 'Novo',       tone: 'bg-amber-100 text-amber-700 border-amber-200' },
  { value: 'confirmed',  label: 'Confirmado', tone: 'bg-blue-100 text-blue-700 border-blue-200' },
  { value: 'ready',      label: 'Pronto',     tone: 'bg-indigo-100 text-indigo-700 border-indigo-200' },
  { value: 'dispatched', label: 'Em Entrega', tone: 'bg-purple-100 text-purple-700 border-purple-200' },
  { value: 'completed',  label: 'Concluído',  tone: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
  { value: 'canceled',   label: 'Cancelado',  tone: 'bg-red-100 text-red-700 border-red-200' },
]

const SOURCE_TABS: Array<{ value: string | null; label: string }> = [
  { value: null,    label: 'Todas origens' },
  { value: 'ifood', label: 'iFood' },
  { value: 'website', label: 'Site' },
  { value: 'manual',  label: 'Manual' },
]

// ── Status config ─────────────────────────────────────────────────────────────

// Normalized order: maps every possible DB/iFood value to a canonical key
const STATUS_RANK: Record<string, number> = {
  pending: 0, placed: 0,
  paid: 1, confirmed: 1,
  ready: 2,
  dispatched: 3,
  completed: 4, concluded: 4,
  canceled: -1, cancelled: -1,
}

// ── Status pipeline (Origem column) ──────────────────────────────────────────

type PipelineStep = {
  key: string
  label: string
  activeColor: string   // dot / line active color (Tailwind bg class)
  activeText: string    // text active color (Tailwind text class)
  inactiveColor: string // dot inactive
}

const PIPELINE_STEPS: PipelineStep[] = [
  { key: 'confirmed', label: 'Confirmado', activeColor: 'bg-blue-500',    activeText: 'text-blue-600',   inactiveColor: 'bg-zinc-200' },
  { key: 'ready',     label: 'Pronto',     activeColor: 'bg-indigo-500',  activeText: 'text-indigo-600', inactiveColor: 'bg-zinc-200' },
  { key: 'dispatched',label: 'Em Entrega', activeColor: 'bg-purple-500',  activeText: 'text-purple-600', inactiveColor: 'bg-zinc-200' },
]

function StatusPipeline({ status }: { status: string }) {
  const rank = STATUS_RANK[status] ?? 0

  if (status === 'canceled' || status === 'cancelled') {
    return (
      <span className="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-600">
        <XCircle className="h-3 w-3 shrink-0" />Cancelado
      </span>
    )
  }
  if (status === 'completed' || status === 'concluded') {
    return (
      <span className="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
        <CheckCheck className="h-3 w-3 shrink-0" />Concluído
      </span>
    )
  }
  if (rank === 0) {
    return (
      <span className="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
        <Clock className="h-3 w-3 shrink-0" />Aguardando
      </span>
    )
  }

  return (
    <div className="flex items-center gap-1">
      {PIPELINE_STEPS.map((step, i) => {
        const stepRank = STATUS_RANK[step.key] ?? 0
        const done    = rank > stepRank
        const current = rank === stepRank
        const active  = done || current

        return (
          <div key={step.key} className="flex items-center gap-1">
            {i > 0 && (
              <div className={`h-px w-3 rounded-full transition-colors ${active ? step.activeColor : 'bg-zinc-200'}`} />
            )}
            <div className="flex flex-col items-center gap-0.5">
              <div className={`h-2 w-2 rounded-full ring-2 ring-offset-1 transition-colors ${
                done    ? `${step.activeColor} ring-transparent` :
                current ? `${step.activeColor} ring-current` :
                          `${step.inactiveColor} ring-transparent`
              } ${current ? step.activeText : ''}`} />
              <span className={`text-[9px] font-medium leading-none transition-colors ${
                active ? step.activeText : 'text-zinc-300'
              }`}>
                {step.label}
              </span>
            </div>
          </div>
        )
      })}
    </div>
  )
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

// ── Main component ────────────────────────────────────────────────────────────

export default function AdminStoreOrdersPage() {
  const ctx = useStoreContext()
  const payload = (typeof window !== 'undefined' && window.__ADMIN_STORE_ORDERS__) || ({} as Payload)

  const currentStatus = payload.current_status ?? null
  const currentSource = payload.current_source ?? null
  const statusLabels  = payload.status_labels  ?? {}
  const urls          = payload.urls ?? {} as Payload['urls']

  const [orders, setOrders]       = useState<UnifiedOrder[]>(payload.orders ?? [])
  const [openMenu, setOpenMenu]   = useState<number | null>(null)
  const [busyAction, setBusyAction] = useState<string | null>(null)
  const [polling, setPolling]     = useState(false)
  const [arrivedToasts, setArrivedToasts] = useState<NewOrderToastData[]>([])

  function dismissArrivedToast(uid: string) {
    setArrivedToasts((prev) => prev.filter((t) => t.uid !== uid))
  }

  // ── Real-time polling for new orders ────────────────────────────────────────

  const knownIdsRef = useRef<Set<string>>(new Set(orders.map((o) => `${o.source}:${o.id}`)))
  const audioRef    = useRef<HTMLAudioElement | null>(null)
  const audioUnlockedRef = useRef(false)

  useEffect(() => {
    const url: string | undefined = urls.poll
    if (!url) return

    let cancelled = false
    let timer: ReturnType<typeof setTimeout> | null = null
    const POLL_MS = 8000

    async function tick() {
      if (cancelled) return
      if (document.hidden) {
        timer = setTimeout(tick, POLL_MS)
        return
      }
      try {
        const qs = new URLSearchParams()
        if (currentStatus) qs.set('status', currentStatus)
        if (currentSource) qs.set('source', currentSource)
        const fullUrl = qs.toString() ? `${url as string}?${qs}` : (url as string)
        const res = await fetch(fullUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
        if (!res.ok) throw new Error('poll failed')
        const data = (await res.json()) as { orders?: UnifiedOrder[] } | null
        const next = data?.orders ?? []
        if (cancelled) return

        const known = knownIdsRef.current
        const arrived = next.filter((o) => !known.has(`${o.source}:${o.id}`))

        if (arrived.length > 0 && known.size > 0) {
          const slug = ctx.slug
          const newToasts: NewOrderToastData[] = arrived.map((o) => ({
            uid: `${o.source}:${o.id}:${Date.now()}:${Math.random().toString(36).slice(2, 6)}`,
            orderNumber: o.display_id,
            customerName: o.customer_name,
            customerPhone: o.customer_phone || undefined,
            total: o.total,
            mountedAt: Date.now(),
            orderUrl: o.is_ifood
              ? `/admin/${slug}/ifood/orders/${o.ifood_row_id ?? o.id}`
              : `${urls.show_base}${o.id}`,
            kdsUrl: `/admin/${slug}/kds`,
          }))
          setArrivedToasts((prev) => [...newToasts, ...prev])
          newToasts.forEach((t) => {
            setTimeout(() => dismissArrivedToast(t.uid), ORDER_TOAST_AUTO_DISMISS_MS + 400)
          })
          playNotification()
        }

        knownIdsRef.current = new Set(next.map((o) => `${o.source}:${o.id}`))
        setOrders(next)
      } catch {
        // network blip — ignore, try again next tick
      } finally {
        if (!cancelled) timer = setTimeout(tick, POLL_MS)
      }
    }

    timer = setTimeout(tick, POLL_MS)
    return () => {
      cancelled = true
      if (timer) clearTimeout(timer)
    }
  }, [urls.poll, currentStatus, currentSource])

  // Unlock audio on first user interaction (browsers block autoplay)
  useEffect(() => {
    const unlock = () => {
      if (audioUnlockedRef.current) return
      const src = urls.notification_sound || '/audio/notification.mp3'
      const a = new Audio(src)
      a.volume = 0.0001
      a.play().then(() => { a.pause(); a.currentTime = 0; audioUnlockedRef.current = true; audioRef.current = a })
        .catch(() => { /* still locked, will retry on next interaction */ })
    }
    window.addEventListener('click', unlock, { once: false })
    window.addEventListener('keydown', unlock, { once: false })
    return () => {
      window.removeEventListener('click', unlock)
      window.removeEventListener('keydown', unlock)
    }
  }, [urls.notification_sound])

  function playNotification() {
    try {
      const src = urls.notification_sound || '/audio/notification.mp3'
      const a = audioRef.current ?? new Audio(src)
      a.src = src
      a.volume = 1
      a.currentTime = 0
      a.play().catch(() => { /* autoplay blocked — user hasn't interacted yet */ })
    } catch { /* noop */ }
  }

  // ── URL helpers ─────────────────────────────────────────────────────────────

  function buildUrl(overrides: { status?: string | null; source?: string | null }) {
    const params = new URLSearchParams()
    const status = 'status' in overrides ? overrides.status : currentStatus
    const source = 'source' in overrides ? overrides.source : currentSource
    if (status) params.set('status', status)
    if (source) params.set('source', source)
    const qs = params.toString()
    return `${urls.list ?? '/'}${qs ? '?' + qs : ''}`
  }

  // ── iFood actions ────────────────────────────────────────────────────────────

  async function pollEvents() {
    setPolling(true)
    try {
      const res = await fetch(urls.ifood_poll, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-Token': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean; events?: number; processed?: number; error?: string } | null
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

  async function performIfoodAction(rowId: number, action: 'confirm' | 'ready' | 'dispatch', confirmText: string) {
    if (!window.confirm(confirmText)) return
    setBusyAction(`${rowId}:${action}`)
    setOpenMenu(null)
    try {
      const res = await fetch(`${urls.ifood_action_base}${rowId}/${action}`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-Token': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean; message?: string; error?: string } | null
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

  async function cancelIfoodOrder(rowId: number) {
    setOpenMenu(null)
    setBusyAction(`${rowId}:cancel-reasons`)
    let reasons: Array<{ code: string; label: string }> = []
    try {
      const res = await fetch(`${urls.ifood_action_base}${rowId}/cancel-reasons`, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean; reasons?: Array<Record<string, unknown>> } | null
      if (data?.reasons && Array.isArray(data.reasons)) {
        reasons = data.reasons.map((r) => ({
          code: String((r.cancelCodeId as string) || (r.code as string) || ''),
          label: String((r.description as string) || (r.cancelCodeId as string) || ''),
        }))
      }
    } catch {
      // fallback to free text
    } finally {
      setBusyAction(null)
    }

    let reasonCode: string | null = null
    if (reasons.length > 0) {
      const labels = reasons.map((r, i) => `${i + 1}. ${r.label}`).join('\n')
      const choice = window.prompt(`Selecione o motivo (número):\n\n${labels}`, '1')
      if (!choice) return
      const idx = Number(choice) - 1
      if (Number.isNaN(idx) || idx < 0 || idx >= reasons.length) { showToast('Motivo inválido.', 'error'); return }
      reasonCode = reasons[idx].code
    } else {
      const txt = window.prompt('Motivo do cancelamento:', '')
      if (txt === null) return
      reasonCode = txt.trim() || 'OUTROS'
    }

    setBusyAction(`${rowId}:cancel`)
    try {
      const res = await fetch(`${urls.ifood_action_base}${rowId}/cancel`, {
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
      const data = (await res.json().catch(() => null)) as { success?: boolean; message?: string; error?: string } | null
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

  // ── Columns ──────────────────────────────────────────────────────────────────

  const columns: DataTableColumn<UnifiedOrder>[] = useMemo(() => [
    {
      header: 'Pedido',
      key: 'display_id',
      cell: (o) => (
        <div className="flex items-center gap-2">
          <SourceIcon source={o.source} />
          <div className="min-w-0">
            <p className="font-mono text-sm font-medium text-zinc-900 truncate">{o.display_id}</p>
            {o.is_ifood && o.local_order_id != null && (
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
      header: 'Valor',
      key: 'total',
      cell: (o) => (
        <span className="text-sm font-semibold text-zinc-900">{formatCurrency(o.total)}</span>
      ),
    },
    {
      header: 'Etapa',
      key: 'source',
      cell: (o) => <StatusPipeline status={o.status} />,
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
      className: 'w-12',
      cell: (o) => {
        const isOpen = openMenu === o.id
        const menuKey = o.is_ifood ? `ifood-${o.ifood_row_id}` : `reg-${o.id}`
        const isBusy  = busyAction?.startsWith(`${o.ifood_row_id ?? o.id}:`)

        return (
          <div className="relative flex justify-end">
            <Button
              type="button"
              variant="ghost"
              size="sm"
              onClick={() => setOpenMenu(isOpen ? null : o.id)}
              disabled={!!isBusy}
              className="h-8 w-8 p-0"
            >
              {isBusy
                ? <Loader2 className="h-4 w-4 animate-spin" />
                : <MoreVertical className="h-4 w-4" />
              }
            </Button>

            {isOpen && (
              <>
                <div className="fixed inset-0 z-30" onClick={() => setOpenMenu(null)} />
                <div className="absolute right-0 top-9 z-40 w-56 rounded-lg border border-zinc-200 bg-white shadow-xl py-1 text-sm">

                  {/* ── Pedido regular ── */}
                  {!o.is_ifood && (
                    <>
                      <a href={`${urls.show_base}${o.id}`} className="flex items-center gap-2 px-3 py-2 hover:bg-zinc-50">
                        <Eye className="h-3.5 w-3.5 text-zinc-500" />Ver detalhes
                      </a>
                      <a href={`${urls.edit_base}${o.id}/edit`} className="flex items-center gap-2 px-3 py-2 hover:bg-zinc-50">
                        <Package className="h-3.5 w-3.5 text-zinc-500" />Editar pedido
                      </a>
                    </>
                  )}

                  {/* ── Pedido iFood ── */}
                  {o.is_ifood && (
                    <>
                      {o.status === 'pending' && (
                        <button type="button"
                          onClick={() => performIfoodAction(o.ifood_row_id!, 'confirm', 'Confirmar pedido?')}
                          className="flex w-full items-center gap-2 px-3 py-2 hover:bg-zinc-50 text-left">
                          <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600" />Confirmar
                        </button>
                      )}
                      {o.status === 'confirmed' && (
                        <button type="button"
                          onClick={() => performIfoodAction(o.ifood_row_id!, 'ready', 'Marcar como pronto?')}
                          className="flex w-full items-center gap-2 px-3 py-2 hover:bg-zinc-50 text-left">
                          <Package className="h-3.5 w-3.5 text-indigo-600" />Marcar como pronto
                        </button>
                      )}
                      {o.status === 'ready' && o.delivered_by === 'MERCHANT' && (
                        <button type="button"
                          onClick={() => performIfoodAction(o.ifood_row_id!, 'dispatch', 'Despachar para entrega?')}
                          className="flex w-full items-center gap-2 px-3 py-2 hover:bg-zinc-50 text-left">
                          <Truck className="h-3.5 w-3.5 text-purple-600" />Despachar
                        </button>
                      )}
                      {(o.status === 'pending' || o.status === 'confirmed') && (
                        <>
                          <div className="my-1 border-t border-zinc-100" />
                          <button type="button"
                            onClick={() => cancelIfoodOrder(o.ifood_row_id!)}
                            className="flex w-full items-center gap-2 px-3 py-2 hover:bg-red-50 text-red-600 text-left">
                            <XCircle className="h-3.5 w-3.5" />Cancelar
                          </button>
                        </>
                      )}
                      {o.local_order_id != null && (
                        <>
                          <div className="my-1 border-t border-zinc-100" />
                          <a href={`${urls.local_order_base}${o.local_order_id}`}
                            className="flex items-center gap-2 px-3 py-2 hover:bg-zinc-50">
                            <PackageOpen className="h-3.5 w-3.5 text-zinc-500" />Ver no sistema
                          </a>
                        </>
                      )}
                    </>
                  )}
                </div>
              </>
            )}
          </div>
        )
      },
    },
  ], [openMenu, busyAction, urls, statusLabels])

  // ── Render ───────────────────────────────────────────────────────────────────

  return (
    <AdminStorePageShell section="orders">
      <NewOrderToastContainer toasts={arrivedToasts} palette={ctx.palette} onDismiss={dismissArrivedToast} />
      <AdminPageHeader
        title="Pedidos"
        description="Gerencie todos os pedidos da loja em um só lugar."
        icon={<ShoppingBag className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <div className="flex flex-wrap gap-2">
            <Button type="button" variant="outline" size="sm" onClick={pollEvents} disabled={polling} className="gap-1.5">
              <RefreshCw className={`h-3.5 w-3.5 ${polling ? 'animate-spin' : ''}`} />
              {polling ? 'Buscando...' : 'Sincronizar iFood'}
            </Button>
            <Button asChild variant="outline" size="sm" className="gap-1.5">
              <a href={urls.create}>
                <PlusCircle className="h-3.5 w-3.5" />Novo pedido
              </a>
            </Button>
          </div>
        }
      />

      {/* ── Status tabs ── */}
      <div className="flex flex-wrap gap-2">
        <a href={buildUrl({ status: null })}
          className={`inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium transition-colors ${
            !currentStatus ? 'bg-zinc-900 text-white border-zinc-900' : 'bg-white text-zinc-700 border-zinc-200 hover:bg-zinc-50'
          }`}>
          Todos
        </a>
        {STATUS_TABS.map((tab) => (
          <a key={tab.value} href={buildUrl({ status: tab.value })}
            className={`inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium transition-colors ${
              currentStatus === tab.value ? tab.tone : 'bg-white text-zinc-700 border-zinc-200 hover:bg-zinc-50'
            }`}>
            {tab.label}
          </a>
        ))}
      </div>

      {/* ── Source tabs ── */}
      <div className="flex flex-wrap gap-2">
        {SOURCE_TABS.map((tab) => (
          <a key={tab.value ?? 'all'} href={buildUrl({ source: tab.value })}
            className={`inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium transition-colors ${
              currentSource === tab.value
                ? 'bg-zinc-800 text-white border-zinc-800'
                : 'bg-white text-zinc-700 border-zinc-200 hover:bg-zinc-50'
            }`}>
            {tab.label}
          </a>
        ))}
      </div>

      {/* ── Table ── */}
      {orders.length === 0 ? (
        <EmptyState
          icon={<EmptyIcon className="h-8 w-8 text-zinc-300" />}
          title="Nenhum pedido encontrado"
          description="Ajuste os filtros ou aguarde novos pedidos."
          action={
            <div className="flex gap-2">
              <Button onClick={pollEvents} disabled={polling} variant="outline" className="gap-2">
                <RefreshCw className={`h-4 w-4 ${polling ? 'animate-spin' : ''}`} />
                Sincronizar iFood
              </Button>
              <Button asChild>
                <a href={urls.create}><PlusCircle className="h-4 w-4 mr-2" />Novo pedido</a>
              </Button>
            </div>
          }
        />
      ) : (
        <DataTable<UnifiedOrder>
          columns={columns}
          data={orders}
          rowKey={(o) => `${o.is_ifood ? 'if' : 're'}-${o.id}`}
          searchAccessor={(o) =>
            `${o.display_id} ${o.customer_name} ${o.customer_phone} ${o.source}`
          }
          searchPlaceholder="Buscar por pedido, cliente ou telefone"
        />
      )}
    </AdminStorePageShell>
  )
}
