import { useEffect, useState } from 'react'
import {
  Activity,
  AlertTriangle,
  Bike,
  Clock,
  Package,
  RefreshCw,
  ShieldAlert,
  Truck,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  AdminPageHeader,
  AdminStorePageShell,
  showToast,
  useStoreContext,
} from '@/components/admin-store'

// ── Types ─────────────────────────────────────────────────────────────────────

type Summary = {
  ifood_orders_active: number
  ifood_orders_by_status: Record<string, number>
  drivers_in_route: number
  drivers_waiting: number
  shipping_active: number
  shipping_by_status: Record<string, number>
  stock_drift: number
}

type ActiveIFoodOrder = {
  ifood_order_id: string
  ifood_display_id: string | null
  status: string
  delivered_by: string | null
  confirmed_at: string | null
  ready_at: string | null
  dispatched_at: string | null
  minutes_in_state: number
  sla_breach: boolean
}

type ActiveShippingOrder = {
  external_reference: string
  ifood_shipping_id: string | null
  status: string
  submitted_at: string | null
  accepted_at: string | null
  picked_up_at: string | null
  minutes_in_state: number
  sla_breach: boolean
}

type Active = {
  ifood: ActiveIFoodOrder[]
  shipping: ActiveShippingOrder[]
}

type Metrics = {
  window_hours: number
  orders_received: number
  orders_completed: number
  orders_cancelled: number
  cancellation_rate: number
  avg_kitchen_minutes: number | null
  avg_pickup_minutes: number | null
  avg_delivery_minutes: number | null
  driver_acceptance_rate: number | null
  shipping_success_rate: number | null
  no_driver_events: number
}

type Alert = {
  level: 'info' | 'warning' | 'critical'
  type: string
  message: string
  count: number
  ids?: string[]
}

type QueueHealth = {
  pending: number
  processing: number
  retrying: number
  dead: number
  dead_24h: number
}

type Dashboard = {
  company_id: number
  generated_at: string
  sla: Record<string, number>
  summary: Summary
  active: Active
  metrics_24h: Metrics
  alerts: Alert[]
  queue_health: QueueHealth
}

type Payload = {
  dashboard: Dashboard
  urls: {
    self: string
    dashboard: string
    config: string
    observability: string
    [k: string]: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_IFOOD_LOGISTICS__?: Payload
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function pct(v: number | null): string {
  if (v === null) return '—'
  return `${(v * 100).toFixed(1)}%`
}

function mins(v: number | null): string {
  if (v === null) return '—'
  return `${v.toFixed(1)} min`
}

function alertColor(level: Alert['level']): string {
  return level === 'critical'
    ? 'border-red-300 bg-red-50 text-red-800'
    : level === 'warning'
      ? 'border-amber-300 bg-amber-50 text-amber-800'
      : 'border-blue-300 bg-blue-50 text-blue-800'
}

function statusBadge(status: string, breach: boolean) {
  const color = breach
    ? 'bg-red-100 text-red-800 border-red-200'
    : 'bg-zinc-100 text-zinc-800 border-zinc-200'
  return <Badge className={`${color} border`} variant="outline">{status}</Badge>
}

// ── Main component ────────────────────────────────────────────────────────────

export default function AdminStoreIFoodLogisticsPage() {
  const ctx = useStoreContext()
  const initial =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_IFOOD_LOGISTICS__) || ({} as Payload)
  const urls = initial.urls ?? { self: '', dashboard: '', config: '', observability: '' }

  const [dashboard, setDashboard] = useState<Dashboard | null>(initial.dashboard ?? null)
  const [refreshing, setRefreshing] = useState(false)
  const [autoRefresh, setAutoRefresh] = useState(true)

  async function refresh() {
    if (!urls.dashboard) return
    setRefreshing(true)
    try {
      const res = await fetch(urls.dashboard, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const j = (await res.json().catch(() => null)) as { success?: boolean; data?: Dashboard } | null
      if (j?.success && j.data) {
        setDashboard(j.data)
      } else {
        showToast('Falha ao atualizar.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setRefreshing(false)
    }
  }

  useEffect(() => {
    if (!autoRefresh) return
    const id = window.setInterval(refresh, 30000)
    return () => window.clearInterval(id)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [autoRefresh])

  if (!dashboard) {
    return (
      <AdminStorePageShell section="ifood">
        <AdminPageHeader
          title="Central de Logística"
          description="Sem dados — confirme se a integração iFood está ativa."
          icon={<Activity className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        />
      </AdminStorePageShell>
    )
  }

  const s = dashboard.summary
  const m = dashboard.metrics_24h

  return (
    <AdminStorePageShell section="ifood">
      <AdminPageHeader
        title="Central de Logística"
        description={`Snapshot gerado em ${dashboard.generated_at}. SLA: cozinha ${dashboard.sla.kitchen_minutes}min, entrega ${dashboard.sla.delivery_minutes}min.`}
        icon={<Activity className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <div className="flex flex-wrap gap-2">
            <Button asChild variant="outline" size="sm">
              <a href={urls.observability}>DLQ / Saúde</a>
            </Button>
            <Button
              type="button"
              size="sm"
              variant="outline"
              onClick={() => setAutoRefresh((v) => !v)}
            >
              Auto-refresh: {autoRefresh ? 'on' : 'off'}
            </Button>
            <Button type="button" size="sm" onClick={refresh} disabled={refreshing} className="gap-1.5">
              <RefreshCw className={`h-3.5 w-3.5 ${refreshing ? 'animate-spin' : ''}`} />
              {refreshing ? 'Atualizando…' : 'Atualizar'}
            </Button>
          </div>
        }
      />

      {/* Summary cards */}
      <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <SummaryCard
          icon={<Package className="h-4 w-4" />}
          label="Pedidos iFood ativos"
          value={s.ifood_orders_active}
        />
        <SummaryCard
          icon={<Bike className="h-4 w-4" />}
          label="Entregadores em rota"
          value={s.drivers_in_route}
        />
        <SummaryCard
          icon={<Clock className="h-4 w-4" />}
          label="Aguardando driver"
          value={s.drivers_waiting}
        />
        <SummaryCard
          icon={<Truck className="h-4 w-4" />}
          label="Shipping ativos"
          value={s.shipping_active}
        />
        <SummaryCard
          icon={<ShieldAlert className="h-4 w-4" />}
          label="Drift de estoque"
          value={s.stock_drift}
        />
      </section>

      {/* Alerts */}
      {dashboard.alerts.length > 0 && (
        <section className="space-y-2">
          <h2 className="text-sm font-semibold text-zinc-700">Alertas</h2>
          {dashboard.alerts.map((a) => (
            <div key={a.type} className={`rounded-lg border p-3 ${alertColor(a.level)}`}>
              <div className="flex items-start gap-2">
                <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" />
                <div className="flex-1">
                  <p className="text-sm font-medium">{a.message}</p>
                  {a.ids && a.ids.length > 0 && (
                    <p className="mt-1 text-xs opacity-75">
                      {a.ids.slice(0, 5).join(', ')}
                      {a.ids.length > 5 ? ` +${a.ids.length - 5}` : ''}
                    </p>
                  )}
                </div>
              </div>
            </div>
          ))}
        </section>
      )}

      {/* Metrics 24h + Queue */}
      <section className="grid gap-4 lg:grid-cols-2">
        <div className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
          <h3 className="text-sm font-semibold text-zinc-700">Métricas 24h</h3>
          <dl className="mt-3 grid grid-cols-2 gap-3 text-sm">
            <Metric label="Pedidos recebidos" value={m.orders_received} />
            <Metric label="Concluídos" value={m.orders_completed} />
            <Metric label="Cancelados" value={m.orders_cancelled} />
            <Metric label="Taxa de cancel." value={pct(m.cancellation_rate)} />
            <Metric label="Tempo médio cozinha" value={mins(m.avg_kitchen_minutes)} />
            <Metric label="Tempo médio retirada" value={mins(m.avg_pickup_minutes)} />
            <Metric label="Tempo médio entrega" value={mins(m.avg_delivery_minutes)} />
            <Metric label="Taxa aceite driver" value={pct(m.driver_acceptance_rate)} />
            <Metric label="Taxa sucesso shipping" value={pct(m.shipping_success_rate)} />
            <Metric label="Eventos NO_DRIVER" value={m.no_driver_events} />
          </dl>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
          <h3 className="text-sm font-semibold text-zinc-700">Fila iFood (cross-company)</h3>
          <dl className="mt-3 grid grid-cols-2 gap-3 text-sm">
            <Metric label="pending" value={dashboard.queue_health.pending} />
            <Metric label="processing" value={dashboard.queue_health.processing} />
            <Metric label="retrying" value={dashboard.queue_health.retrying} />
            <Metric label="dead total" value={dashboard.queue_health.dead} />
            <Metric label="dead 24h" value={dashboard.queue_health.dead_24h} />
          </dl>
          <div className="mt-4 text-xs text-zinc-500">
            Para detalhes e retry, ver{' '}
            <a href={urls.observability} className="underline">DLQ / Observabilidade</a>.
          </div>
        </div>
      </section>

      {/* Active orders */}
      <section className="grid gap-4 lg:grid-cols-2">
        <div className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
          <h3 className="text-sm font-semibold text-zinc-700">
            Pedidos iFood ativos ({dashboard.active.ifood.length})
          </h3>
          <table className="mt-3 w-full text-xs">
            <thead className="text-left text-zinc-500">
              <tr>
                <th className="py-1 pr-2">ID</th>
                <th className="py-1 pr-2">Status</th>
                <th className="py-1 pr-2">Min no estado</th>
              </tr>
            </thead>
            <tbody>
              {dashboard.active.ifood.length === 0 && (
                <tr><td colSpan={3} className="py-3 text-zinc-400">Sem pedidos ativos</td></tr>
              )}
              {dashboard.active.ifood.map((o) => (
                <tr key={o.ifood_order_id} className="border-t border-zinc-100">
                  <td className="py-2 pr-2 font-mono">{o.ifood_display_id || o.ifood_order_id.slice(0, 12)}</td>
                  <td className="py-2 pr-2">{statusBadge(o.status, o.sla_breach)}</td>
                  <td className={`py-2 pr-2 ${o.sla_breach ? 'font-semibold text-red-700' : ''}`}>
                    {o.minutes_in_state} min
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
          <h3 className="text-sm font-semibold text-zinc-700">
            Shipping ativos ({dashboard.active.shipping.length})
          </h3>
          <table className="mt-3 w-full text-xs">
            <thead className="text-left text-zinc-500">
              <tr>
                <th className="py-1 pr-2">Ref</th>
                <th className="py-1 pr-2">Status</th>
                <th className="py-1 pr-2">Min no estado</th>
              </tr>
            </thead>
            <tbody>
              {dashboard.active.shipping.length === 0 && (
                <tr><td colSpan={3} className="py-3 text-zinc-400">Sem shipping ativos</td></tr>
              )}
              {dashboard.active.shipping.map((o) => (
                <tr key={o.external_reference} className="border-t border-zinc-100">
                  <td className="py-2 pr-2 font-mono">{o.external_reference.slice(0, 20)}</td>
                  <td className="py-2 pr-2">{statusBadge(o.status, o.sla_breach)}</td>
                  <td className={`py-2 pr-2 ${o.sla_breach ? 'font-semibold text-red-700' : ''}`}>
                    {o.minutes_in_state} min
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>
    </AdminStorePageShell>
  )
}

function SummaryCard({ icon, label, value }: { icon: React.ReactNode; label: string; value: number }) {
  return (
    <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
      <div className="flex items-center gap-2 text-zinc-500">
        {icon}
        <span className="text-xs uppercase tracking-wide">{label}</span>
      </div>
      <p className="mt-2 text-2xl font-semibold text-zinc-900">{value}</p>
    </div>
  )
}

function Metric({ label, value }: { label: string; value: string | number }) {
  return (
    <div className="rounded-lg bg-zinc-50 px-3 py-2">
      <dt className="text-xs text-zinc-500">{label}</dt>
      <dd className="mt-0.5 font-semibold text-zinc-900">{value}</dd>
    </div>
  )
}
