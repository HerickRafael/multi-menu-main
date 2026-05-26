import { useEffect, useMemo, useState } from 'react'
import {
  BarChart3,
  CalendarDays,
  Clock,
  CreditCard,
  Eye,
  Filter,
  Package,
  ShoppingBag,
  TrendingDown,
  TrendingUp,
} from 'lucide-react'
import {
  Bar,
  BarChart,
  CartesianGrid,
  Cell,
  Legend,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  AdminStorePageShell,
  AdminPageHeader,
  EmptyState,
  formatCurrency,
  useStoreContext,
} from '@/components/admin-store'

type SummaryMetrics = {
  total_orders?: number
  total_revenue?: number
  avg_ticket?: number
  cancelled_orders?: number
  conversion_rate?: number
  orders_growth?: number
  revenue_growth?: number
}

type SalesByDay = Array<{ date: string; orders: number; revenue: number }>
type SalesByHour = Array<{ hour: number; orders: number; revenue: number }>
type SalesByWeekday = Array<{ weekday: number; weekday_name: string; orders: number; revenue: number }>
type PaymentMethod = { payment_type: string; orders: number; revenue: number }
type TopProduct = { id?: number; name?: string; total_sold?: number; total_revenue?: number; image?: string }
type RecentOrder = { id: number; total: number; status: string; created_at?: string; customer_name?: string }

type AnalyticsPayload = {
  period: string
  start_date: string
  end_date: string
  summary: SummaryMetrics
  today_sales: { orders?: number; revenue?: number }
  sales_by_day: SalesByDay
  sales_by_hour: SalesByHour
  sales_by_weekday: SalesByWeekday
  payment_methods: PaymentMethod[]
  top_products: TopProduct[]
  recent_orders: RecentOrder[]
  page_views: { total?: number; unique?: number }
  conversion_funnel: Array<{ stage: string; count: number }>
  urls: {
    list: string
    data: string
    orders_base: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_ANALYTICS__?: AnalyticsPayload
  }
}

const PERIOD_OPTIONS: Array<{ value: string; label: string }> = [
  { value: 'today', label: 'Hoje' },
  { value: '7', label: 'Últimos 7 dias' },
  { value: '30', label: 'Últimos 30 dias' },
  { value: 'month', label: 'Este mês' },
  { value: 'year', label: 'Este ano' },
]

const PAYMENT_LABELS: Record<string, string> = {
  cash: 'Dinheiro',
  pix: 'PIX',
  credit: 'Crédito',
  debit: 'Débito',
  outros: 'Outros',
}

const PAYMENT_COLORS: Record<string, string> = {
  cash: '#10b981',
  pix: '#06b6d4',
  credit: '#6366f1',
  debit: '#8b5cf6',
  outros: '#a1a1aa',
}

const WEEKDAY_LABELS = ['', 'Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb']

function formatDateBR(iso: string): string {
  if (!iso) return ''
  // accepts YYYY-MM-DD or YYYY-MM-DDTHH:MM:SS
  const d = new Date(iso.replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return iso
  return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })
}

function resolveImage(path?: string): string {
  if (!path) return ''
  if (/^(https?:)?\/\//i.test(path) || path.startsWith('data:')) return path
  return path.startsWith('/') ? path : `/${path}`
}

function getCurrentPeriod(): string {
  if (typeof window === 'undefined') return '30'
  const url = new URL(window.location.href)
  return url.searchParams.get('period') || (window.__ADMIN_STORE_ANALYTICS__?.period ?? '30')
}

function GrowthBadge({ value }: { value: number }) {
  if (!Number.isFinite(value) || value === 0) {
    return <Badge className="bg-zinc-100 text-zinc-600 border border-zinc-200 hover:bg-zinc-100 text-[10px] h-5">—</Badge>
  }
  const up = value > 0
  return (
    <Badge
      className={`text-[10px] h-5 gap-0.5 border ${
        up
          ? 'bg-emerald-100 text-emerald-700 border-emerald-200 hover:bg-emerald-100'
          : 'bg-red-100 text-red-700 border-red-200 hover:bg-red-100'
      }`}
    >
      {up ? <TrendingUp className="h-2.5 w-2.5" /> : <TrendingDown className="h-2.5 w-2.5" />}
      {up ? '+' : ''}
      {value.toFixed(1)}%
    </Badge>
  )
}

function OrderStatusBadge({ status }: { status: string }) {
  if (status === 'completed' || status === 'paid')
    return <Badge className="bg-emerald-100 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 text-[10px] h-4">Concluído</Badge>
  if (status === 'canceled')
    return <Badge className="bg-red-100 text-red-700 border border-red-200 hover:bg-red-100 text-[10px] h-4">Cancelado</Badge>
  return <Badge className="bg-amber-100 text-amber-700 border border-amber-200 hover:bg-amber-100 text-[10px] h-4">Pendente</Badge>
}

export default function AdminStoreAnalyticsPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_ANALYTICS__) || ({} as AnalyticsPayload)

  const [period] = useState<string>(getCurrentPeriod())

  function changePeriod(next: string) {
    if (typeof window === 'undefined') return
    const url = new URL(window.location.href)
    url.searchParams.set('period', next)
    window.location.href = url.toString()
  }

  const summary = payload.summary ?? {}
  const todaySales = payload.today_sales ?? {}
  const pageViews = payload.page_views ?? {}

  const salesByDayChart = useMemo(
    () =>
      (payload.sales_by_day ?? []).map((d) => ({
        date: formatDateBR(d.date),
        Pedidos: d.orders,
        Receita: d.revenue,
      })),
    [payload.sales_by_day],
  )

  const salesByHourChart = useMemo(
    () =>
      (payload.sales_by_hour ?? []).map((h) => ({
        hour: `${String(h.hour).padStart(2, '0')}h`,
        Pedidos: h.orders,
      })),
    [payload.sales_by_hour],
  )

  const salesByWeekdayChart = useMemo(
    () =>
      (payload.sales_by_weekday ?? []).map((w) => ({
        day: w.weekday_name || WEEKDAY_LABELS[w.weekday] || `Dia ${w.weekday}`,
        Pedidos: w.orders,
        Receita: w.revenue,
      })),
    [payload.sales_by_weekday],
  )

  const paymentPie = useMemo(
    () =>
      (payload.payment_methods ?? []).map((p) => ({
        name: PAYMENT_LABELS[p.payment_type] || p.payment_type,
        value: p.orders,
        revenue: p.revenue,
        color: PAYMENT_COLORS[p.payment_type] || '#a1a1aa',
      })),
    [payload.payment_methods],
  )

  const hasData =
    (payload.summary?.total_orders ?? 0) > 0 || (payload.sales_by_day ?? []).length > 0

  return (
    <AdminStorePageShell section="analytics">
      <AdminPageHeader
        title="Analytics"
        description="Visão consolidada de vendas, pedidos e comportamento dos clientes."
        icon={<BarChart3 className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <div className="flex items-center gap-2">
            <Filter className="h-3.5 w-3.5 text-zinc-500" />
            <select
              value={period}
              onChange={(e) => changePeriod(e.target.value)}
              className="flex h-9 rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
            >
              {PERIOD_OPTIONS.map((opt) => (
                <option key={opt.value} value={opt.value}>
                  {opt.label}
                </option>
              ))}
            </select>
          </div>
        }
      />

      {/* Summary cards */}
      <section className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <div className="flex items-center justify-between gap-2">
            <p className="text-xs text-zinc-500">Pedidos</p>
            <GrowthBadge value={Number(summary.orders_growth ?? 0)} />
          </div>
          <p className="mt-1 text-2xl font-semibold text-zinc-800">{summary.total_orders ?? 0}</p>
          {todaySales.orders != null && (
            <p className="mt-1 text-[11px] text-zinc-500">{todaySales.orders} hoje</p>
          )}
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <div className="flex items-center justify-between gap-2">
            <p className="text-xs text-zinc-500">Receita</p>
            <GrowthBadge value={Number(summary.revenue_growth ?? 0)} />
          </div>
          <p className="mt-1 text-2xl font-semibold text-zinc-800">{formatCurrency(summary.total_revenue ?? 0)}</p>
          {todaySales.revenue != null && (
            <p className="mt-1 text-[11px] text-zinc-500">{formatCurrency(todaySales.revenue)} hoje</p>
          )}
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Ticket médio</p>
          <p className="mt-1 text-2xl font-semibold text-zinc-800">{formatCurrency(summary.avg_ticket ?? 0)}</p>
          <p className="mt-1 text-[11px] text-zinc-500">
            {summary.cancelled_orders ?? 0} cancelados
          </p>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Visualizações do cardápio</p>
          <p className="mt-1 text-2xl font-semibold text-zinc-800">{pageViews.total ?? 0}</p>
          <p className="mt-1 text-[11px] text-zinc-500">{pageViews.unique ?? 0} únicos</p>
        </div>
      </section>

      {!hasData && (
        <EmptyState
          title="Ainda sem dados suficientes"
          description="Faça alguns pedidos para começar a ver gráficos e métricas detalhadas neste painel."
          icon={<BarChart3 className="h-5 w-5" />}
        />
      )}

      {/* Sales by day */}
      {salesByDayChart.length > 0 && (
        <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
          <div className="mb-4 flex items-center justify-between">
            <div>
              <h2 className="text-sm font-semibold text-zinc-800 flex items-center gap-2">
                <CalendarDays className="h-4 w-4 text-zinc-500" />
                Vendas por dia
              </h2>
              <p className="text-xs text-zinc-500">Pedidos e receita ao longo do período.</p>
            </div>
          </div>
          <div style={{ width: '100%', height: 280 }}>
            <ResponsiveContainer>
              <BarChart data={salesByDayChart}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e4e4e7" vertical={false} />
                <XAxis dataKey="date" stroke="#71717a" fontSize={11} />
                <YAxis stroke="#71717a" fontSize={11} />
                <Tooltip
                  contentStyle={{ border: '1px solid #e4e4e7', borderRadius: 8, fontSize: 12 }}
                  formatter={(value: number, name: string) =>
                    name === 'Receita' ? formatCurrency(value) : value
                  }
                />
                <Legend wrapperStyle={{ fontSize: 12 }} />
                <Bar dataKey="Pedidos" fill={ctx.palette.primaryColor} radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </section>
      )}

      <div className="grid gap-4 lg:grid-cols-2">
        {/* Sales by hour */}
        {salesByHourChart.length > 0 && (
          <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 className="mb-3 text-sm font-semibold text-zinc-800 flex items-center gap-2">
              <Clock className="h-4 w-4 text-zinc-500" />
              Pedidos por hora
            </h2>
            <div style={{ width: '100%', height: 220 }}>
              <ResponsiveContainer>
                <BarChart data={salesByHourChart}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#e4e4e7" vertical={false} />
                  <XAxis dataKey="hour" stroke="#71717a" fontSize={10} interval={2} />
                  <YAxis stroke="#71717a" fontSize={11} />
                  <Tooltip contentStyle={{ border: '1px solid #e4e4e7', borderRadius: 8, fontSize: 12 }} />
                  <Bar dataKey="Pedidos" fill="#0ea5e9" radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </section>
        )}

        {/* Sales by weekday */}
        {salesByWeekdayChart.length > 0 && (
          <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 className="mb-3 text-sm font-semibold text-zinc-800 flex items-center gap-2">
              <CalendarDays className="h-4 w-4 text-zinc-500" />
              Pedidos por dia da semana
            </h2>
            <div style={{ width: '100%', height: 220 }}>
              <ResponsiveContainer>
                <BarChart data={salesByWeekdayChart}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#e4e4e7" vertical={false} />
                  <XAxis dataKey="day" stroke="#71717a" fontSize={11} />
                  <YAxis stroke="#71717a" fontSize={11} />
                  <Tooltip contentStyle={{ border: '1px solid #e4e4e7', borderRadius: 8, fontSize: 12 }} />
                  <Bar dataKey="Pedidos" fill="#10b981" radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </section>
        )}

        {/* Payment methods pie */}
        {paymentPie.length > 0 && (
          <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 className="mb-3 text-sm font-semibold text-zinc-800 flex items-center gap-2">
              <CreditCard className="h-4 w-4 text-zinc-500" />
              Formas de pagamento
            </h2>
            <div style={{ width: '100%', height: 220 }}>
              <ResponsiveContainer>
                <PieChart>
                  <Pie
                    data={paymentPie}
                    dataKey="value"
                    nameKey="name"
                    cx="50%"
                    cy="50%"
                    outerRadius={70}
                    label={(entry: { name?: string; value?: number }) => `${entry.name ?? ''}: ${entry.value ?? 0}`}
                    labelLine={false}
                  >
                    {paymentPie.map((entry) => (
                      <Cell key={entry.name} fill={entry.color} />
                    ))}
                  </Pie>
                  <Tooltip
                    contentStyle={{ border: '1px solid #e4e4e7', borderRadius: 8, fontSize: 12 }}
                    formatter={(value: number, name: string, props: { payload?: { revenue?: number } }) => [
                      `${value} pedidos • ${formatCurrency(props?.payload?.revenue ?? 0)}`,
                      name,
                    ]}
                  />
                </PieChart>
              </ResponsiveContainer>
            </div>
          </section>
        )}

        {/* Top products */}
        {(payload.top_products ?? []).length > 0 && (
          <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 className="mb-3 text-sm font-semibold text-zinc-800 flex items-center gap-2">
              <Package className="h-4 w-4 text-zinc-500" />
              Produtos mais vendidos
            </h2>
            <ul className="divide-y divide-zinc-100">
              {(payload.top_products ?? []).slice(0, 8).map((p, idx) => {
                const src = resolveImage(p.image)
                return (
                  <li key={p.id ?? idx} className="flex items-center gap-3 py-2">
                    <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-semibold text-zinc-600">
                      {idx + 1}
                    </span>
                    {src && (
                      <img src={src} alt="" className="h-8 w-8 shrink-0 rounded-md border border-zinc-200 object-cover" />
                    )}
                    <div className="min-w-0 flex-1">
                      <p className="text-sm font-medium text-zinc-800 truncate">{p.name ?? `Produto #${p.id}`}</p>
                      <p className="text-[11px] text-zinc-500">
                        {p.total_sold ?? 0} vendidos • {formatCurrency(p.total_revenue ?? 0)}
                      </p>
                    </div>
                  </li>
                )
              })}
            </ul>
          </section>
        )}
      </div>

      {/* Recent orders */}
      {(payload.recent_orders ?? []).length > 0 && (
        <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
          <h2 className="mb-3 text-sm font-semibold text-zinc-800 flex items-center gap-2">
            <ShoppingBag className="h-4 w-4 text-zinc-500" />
            Pedidos recentes ({payload.recent_orders?.length ?? 0})
          </h2>
          <ul className="divide-y divide-zinc-100">
            {(payload.recent_orders ?? []).slice(0, 10).map((o) => (
              <li
                key={o.id}
                className="flex items-center justify-between gap-3 py-2 hover:bg-zinc-50 rounded-md px-2 -mx-2 transition-colors"
              >
                <div className="min-w-0 flex-1">
                  <p className="text-sm font-medium text-zinc-800">
                    #{o.id}
                    {o.customer_name ? ` · ${o.customer_name}` : ''}
                  </p>
                  {o.created_at && <p className="text-[11px] text-zinc-500">{o.created_at}</p>}
                </div>
                <OrderStatusBadge status={o.status} />
                <span className="text-sm font-semibold text-zinc-800 whitespace-nowrap min-w-[80px] text-right">
                  {formatCurrency(o.total)}
                </span>
              </li>
            ))}
          </ul>
        </section>
      )}

      {/* Conversion funnel */}
      {(payload.conversion_funnel ?? []).length > 0 && (
        <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
          <h2 className="mb-3 text-sm font-semibold text-zinc-800 flex items-center gap-2">
            <Eye className="h-4 w-4 text-zinc-500" />
            Funil de conversão
          </h2>
          <ul className="space-y-2">
            {(payload.conversion_funnel ?? []).map((step, idx) => {
              const max = Math.max(...(payload.conversion_funnel ?? []).map((s) => s.count), 1)
              const pct = (step.count / max) * 100
              return (
                <li key={idx} className="flex items-center gap-3">
                  <span className="min-w-[150px] text-xs text-zinc-600">{step.stage}</span>
                  <div className="relative flex-1 h-7 rounded-md bg-zinc-100 overflow-hidden">
                    <div
                      className="h-full rounded-md transition-all"
                      style={{ width: `${pct}%`, backgroundColor: ctx.palette.primaryColor }}
                    />
                    <span className="absolute inset-0 flex items-center px-3 text-xs font-semibold text-zinc-800">
                      {step.count}
                    </span>
                  </div>
                </li>
              )
            })}
          </ul>
        </section>
      )}
    </AdminStorePageShell>
  )
}
