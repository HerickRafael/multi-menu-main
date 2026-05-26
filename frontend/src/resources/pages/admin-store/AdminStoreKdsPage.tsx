import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import {
  AlertTriangle,
  Bell,
  BellOff,
  Check,
  CheckCircle2,
  ChefHat,
  Clock,
  ExternalLink,
  Maximize2,
  Pause,
  Play,
  Truck,
  X,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  AdminStorePageShell,
  AdminPageHeader,
  formatCurrency,
  getCsrfToken,
  showToast,
  useStoreContext,
} from '@/components/admin-store'

type Order = {
  id: number
  order_number: number | null
  customer_name: string
  customer_phone: string
  status: string
  total: number
  items_count: number | null
  created_at: string
  sla_deadline: string | null
  source: string
  items?: Array<{
    name?: string
    quantity?: number
    notes?: string
  }>
}

type KdsPayload = {
  initial_snapshot: Order[]
  has_canceled: boolean
  config: Record<string, unknown>
  columns: Array<{ id: string; label: string }>
  refresh_ms: number
  sla_minutes: number
  urls: {
    data: string
    status: string
    order_detail_base: string
    bell: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_KDS__?: KdsPayload
  }
}

const STATUS_FLOW: Record<string, { next: string; label: string; icon: typeof Check }> = {
  pending: { next: 'paid', label: 'Iniciar preparo', icon: ChefHat },
  paid: { next: 'completed', label: 'Marcar pronto', icon: CheckCircle2 },
  completed: { next: 'delivered', label: 'Marcar entregue', icon: Truck },
}

function parseDate(s: string): Date | null {
  if (!s) return null
  const d = new Date(s.replace(' ', 'T'))
  return Number.isNaN(d.getTime()) ? null : d
}

function elapsedMinutes(created: string): number {
  const d = parseDate(created)
  if (!d) return 0
  return Math.max(0, Math.floor((Date.now() - d.getTime()) / 60000))
}

function isOverSla(order: Order, slaMinutes: number): boolean {
  return elapsedMinutes(order.created_at) >= slaMinutes
}

function OrderCard({
  order,
  slaMinutes,
  orderDetailBase,
  onAdvance,
  onCancel,
  busy,
}: {
  order: Order
  slaMinutes: number
  orderDetailBase: string
  onAdvance: (id: number, nextStatus: string) => void
  onCancel: (id: number) => void
  busy: boolean
}) {
  const elapsed = elapsedMinutes(order.created_at)
  const over = isOverSla(order, slaMinutes)
  const flow = STATUS_FLOW[order.status]
  const Icon = flow?.icon ?? Check

  return (
    <div
      className={`rounded-xl border bg-white p-3 shadow-sm transition ${
        over
          ? 'border-red-300 ring-2 ring-red-100 animate-pulse'
          : elapsed >= slaMinutes * 0.7
            ? 'border-amber-300'
            : 'border-zinc-200'
      }`}
    >
      <div className="flex items-start justify-between gap-2 mb-2">
        <div className="min-w-0 flex-1">
          <a
            href={`${orderDetailBase}${order.id}`}
            className="text-base font-bold text-zinc-900 hover:underline"
          >
            #{order.order_number ?? order.id}
          </a>
          {order.customer_name && (
            <p className="text-xs text-zinc-600 truncate">{order.customer_name}</p>
          )}
        </div>
        <div className="flex flex-col items-end gap-0.5">
          <Badge
            className={`gap-1 text-[10px] h-5 ${
              over
                ? 'bg-red-100 text-red-700 border border-red-200 hover:bg-red-100'
                : elapsed >= slaMinutes * 0.7
                  ? 'bg-amber-100 text-amber-800 border border-amber-200 hover:bg-amber-100'
                  : 'bg-zinc-100 text-zinc-700 border border-zinc-200 hover:bg-zinc-100'
            }`}
          >
            <Clock className="h-2.5 w-2.5" />
            {elapsed}min
          </Badge>
          {order.source === 'ifood' && (
            <Badge className="text-[9px] h-4 bg-red-50 text-red-600 border border-red-100 hover:bg-red-50">
              iFood
            </Badge>
          )}
        </div>
      </div>

      {order.items && order.items.length > 0 && (
        <ul className="mb-2 space-y-0.5 text-xs text-zinc-700">
          {order.items.slice(0, 5).map((it, i) => (
            <li key={i} className="flex items-baseline gap-1.5">
              <span className="font-mono text-zinc-500">{it.quantity ?? 1}×</span>
              <span className="flex-1 truncate">{it.name ?? '—'}</span>
            </li>
          ))}
          {order.items.length > 5 && (
            <li className="text-[10px] text-zinc-400">+ {order.items.length - 5} item(s)</li>
          )}
        </ul>
      )}

      <div className="flex items-center justify-between gap-2 border-t border-zinc-100 pt-2">
        <span className="text-xs font-semibold text-zinc-700">{formatCurrency(order.total)}</span>
        <div className="flex items-center gap-1">
          {flow && (
            <Button
              size="sm"
              className="h-7 px-2 gap-1 text-[11px]"
              disabled={busy}
              onClick={() => onAdvance(order.id, flow.next)}
            >
              <Icon className="h-3 w-3" />
              {flow.label}
            </Button>
          )}
          <Button
            size="sm"
            variant="ghost"
            className="h-7 w-7 p-0 text-red-500 hover:bg-red-50 hover:text-red-700"
            disabled={busy}
            onClick={() => onCancel(order.id)}
            aria-label="Cancelar"
          >
            <X className="h-3.5 w-3.5" />
          </Button>
        </div>
      </div>
    </div>
  )
}

export default function AdminStoreKdsPage() {
  const ctx = useStoreContext()
  const payload = (typeof window !== 'undefined' && window.__ADMIN_STORE_KDS__) || ({} as KdsPayload)
  const urls = payload.urls
  const refreshMs = payload.refresh_ms || 1500
  const slaMinutes = payload.sla_minutes || 20

  const [orders, setOrders] = useState<Order[]>(payload.initial_snapshot ?? [])
  const [paused, setPaused] = useState(false)
  const [soundOn, setSoundOn] = useState(true)
  const [, forceTick] = useState(0)
  const [busyIds, setBusyIds] = useState<Set<number>>(new Set())
  const syncTokenRef = useRef<string>('')
  const previousIdsRef = useRef<Set<number>>(new Set(orders.map((o) => o.id)))
  const audioRef = useRef<HTMLAudioElement | null>(null)

  // Periodic tick to refresh elapsed times
  useEffect(() => {
    const id = setInterval(() => forceTick((n) => n + 1), 30000)
    return () => clearInterval(id)
  }, [])

  // Polling
  const fetchOrders = useCallback(async () => {
    try {
      const url = syncTokenRef.current
        ? `${urls.data}?since=${encodeURIComponent(syncTokenRef.current)}`
        : urls.data
      const res = await fetch(url, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      })
      const data = (await res.json().catch(() => null)) as
        | {
            orders?: Order[]
            removed_ids?: number[]
            full_refresh?: boolean
            sync_token?: string
          }
        | null
      if (!data) return

      if (data.sync_token) syncTokenRef.current = data.sync_token

      setOrders((prev) => {
        let next: Order[] = data.full_refresh ? [] : [...prev]
        if (data.orders) {
          for (const incoming of data.orders) {
            const idx = next.findIndex((o) => o.id === incoming.id)
            if (idx === -1) next.push(incoming)
            else next[idx] = incoming
          }
        }
        if (data.removed_ids && data.removed_ids.length > 0) {
          next = next.filter((o) => !data.removed_ids!.includes(o.id))
        }

        // Detect new "pending" orders to ring bell
        const currentIds = new Set(next.map((o) => o.id))
        const newPending = next.filter(
          (o) => !previousIdsRef.current.has(o.id) && o.status === 'pending',
        )
        if (newPending.length > 0 && soundOn && audioRef.current) {
          audioRef.current.currentTime = 0
          audioRef.current.play().catch(() => {})
        }
        previousIdsRef.current = currentIds

        return next
      })
    } catch {
      // silent — keep last state
    }
  }, [urls.data, soundOn])

  useEffect(() => {
    if (paused) return
    fetchOrders()
    const id = setInterval(fetchOrders, refreshMs)
    return () => clearInterval(id)
  }, [paused, fetchOrders, refreshMs])

  async function advanceStatus(id: number, nextStatus: string) {
    setBusyIds((prev) => new Set(prev).add(id))
    try {
      const formData = new FormData()
      formData.append('csrf_token', getCsrfToken())
      formData.append('id', String(id))
      formData.append('status', nextStatus)
      const res = await fetch(urls.status, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean; message?: string } | null
      if (data?.success) {
        if (nextStatus === 'delivered') {
          setOrders((prev) => prev.filter((o) => o.id !== id))
          showToast('Pedido entregue.', 'success')
        } else {
          setOrders((prev) => prev.map((o) => (o.id === id ? { ...o, status: nextStatus } : o)))
        }
        await fetchOrders()
      } else {
        showToast(data?.message || 'Falha ao atualizar status.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setBusyIds((prev) => {
        const n = new Set(prev)
        n.delete(id)
        return n
      })
    }
  }

  async function cancelOrder(id: number) {
    if (!window.confirm('Cancelar este pedido? Esta ação não pode ser desfeita.')) return
    await advanceStatus(id, 'canceled')
    setOrders((prev) => prev.filter((o) => o.id !== id))
  }

  function toggleFullscreen() {
    if (document.fullscreenElement) {
      document.exitFullscreen().catch(() => {})
    } else {
      document.documentElement.requestFullscreen().catch(() => {})
    }
  }

  const ordersByStatus = useMemo(() => {
    const map: Record<string, Order[]> = {}
    for (const col of payload.columns ?? []) map[col.id] = []
    for (const order of orders) {
      const status = order.status
      if (!map[status]) map[status] = []
      map[status].push(order)
    }
    // Sort each column by created_at (oldest first)
    for (const key of Object.keys(map)) {
      map[key].sort((a, b) => (a.created_at < b.created_at ? -1 : 1))
    }
    return map
  }, [orders, payload.columns])

  const overSlaCount = orders.filter((o) => isOverSla(o, slaMinutes)).length

  return (
    <AdminStorePageShell section="orders">
      <AdminPageHeader
        title="KDS — Cozinha"
        description={`Atualiza automaticamente a cada ${(refreshMs / 1000).toFixed(1)}s · SLA ${slaMinutes}min · ${orders.length} pedidos ativos.`}
        icon={<ChefHat className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <div className="flex items-center gap-2">
            {overSlaCount > 0 && (
              <Badge className="bg-red-100 text-red-700 border border-red-200 hover:bg-red-100 gap-1 animate-pulse">
                <AlertTriangle className="h-3 w-3" />
                {overSlaCount} atrasado{overSlaCount === 1 ? '' : 's'}
              </Badge>
            )}
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={() => setSoundOn((s) => !s)}
              className="gap-1.5"
            >
              {soundOn ? <Bell className="h-3.5 w-3.5" /> : <BellOff className="h-3.5 w-3.5" />}
              {soundOn ? 'Som ativo' : 'Som mudo'}
            </Button>
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={() => setPaused((p) => !p)}
              className="gap-1.5"
            >
              {paused ? <Play className="h-3.5 w-3.5" /> : <Pause className="h-3.5 w-3.5" />}
              {paused ? 'Retomar' : 'Pausar polling'}
            </Button>
            <Button type="button" variant="outline" size="sm" onClick={toggleFullscreen} className="gap-1.5">
              <Maximize2 className="h-3.5 w-3.5" />
              Tela cheia
            </Button>
          </div>
        }
      />

      {urls.bell && (
        <audio ref={audioRef} src={urls.bell} preload="auto" />
      )}

      <section className="grid gap-3 lg:grid-cols-3">
        {(payload.columns ?? []).map((col) => {
          const items = ordersByStatus[col.id] ?? []
          return (
            <div key={col.id} className="rounded-2xl border border-zinc-200 bg-zinc-50/50 p-3">
              <header className="mb-3 flex items-center justify-between gap-2 px-1">
                <h2 className="text-sm font-semibold text-zinc-800 flex items-center gap-2">
                  {col.id === 'pending' && <Clock className="h-4 w-4 text-amber-500" />}
                  {col.id === 'paid' && <ChefHat className="h-4 w-4 text-blue-500" />}
                  {col.id === 'completed' && <CheckCircle2 className="h-4 w-4 text-emerald-500" />}
                  {col.label}
                </h2>
                <Badge className="bg-white border border-zinc-200 text-zinc-700 hover:bg-white">
                  {items.length}
                </Badge>
              </header>

              <div className="space-y-2 max-h-[calc(100vh-280px)] overflow-y-auto pr-1">
                {items.length === 0 ? (
                  <p className="py-8 text-center text-xs text-zinc-400">
                    Sem pedidos
                  </p>
                ) : (
                  items.map((order) => (
                    <OrderCard
                      key={order.id}
                      order={order}
                      slaMinutes={slaMinutes}
                      orderDetailBase={urls.order_detail_base}
                      onAdvance={advanceStatus}
                      onCancel={cancelOrder}
                      busy={busyIds.has(order.id)}
                    />
                  ))
                )}
              </div>
            </div>
          )
        })}
      </section>

      <footer className="text-[11px] text-zinc-500 flex items-center justify-between">
        <span>
          {paused ? '⏸ Polling pausado' : `🔄 Sincronizando a cada ${(refreshMs / 1000).toFixed(1)}s`}
        </span>
        <a
          href={urls.order_detail_base.replace('?id=', '')}
          className="hover:text-zinc-800 inline-flex items-center gap-1"
        >
          Ver lista completa
          <ExternalLink className="h-3 w-3" />
        </a>
      </footer>
    </AdminStorePageShell>
  )
}
