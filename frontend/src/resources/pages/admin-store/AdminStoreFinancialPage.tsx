import { useMemo } from 'react'
import {
  AlertTriangle,
  BarChart3,
  CalendarDays,
  CircleDollarSign,
  Clock,
  Package,
  Receipt,
  Settings as SettingsIcon,
  TrendingDown,
  TrendingUp,
  Truck,
} from 'lucide-react'
import {
  Bar,
  BarChart,
  CartesianGrid,
  Line,
  LineChart,
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

type FinancialPayload = {
  current_month: string
  previous_month: string
  summary: {
    total_orders: number
    completed_orders: number
    cancelled_orders: number
    gross_revenue: number
    products_revenue: number
    delivery_fees: number
    discounts: number
    cancelled_value: number
    total_items: number
    avg_ticket: number
  }
  comparison: {
    revenue_growth: number
    orders_growth: number
    ticket_growth: number
  }
  monthly_trend: Array<{ month: string; gross_revenue: number; total_orders: number }>
  top_products: Array<{ id: number; name: string; total_sold: number; total_revenue: number; image: string }>
  profitable_products: Array<{ id: number; name: string; total_sold: number; total_profit: number; margin_pct: number }>
  low_margin_products: Array<{ id: number; name: string; price: number; cost: number; margin_pct: number }>
  hourly_sales: Array<{ hour: number; orders: number; revenue: number }>
  daily_sales: Array<{ date: string; orders: number; revenue: number }>
  urls: {
    monthly: string
    yearly: string
    settings: string
    expenses: string
    product_costs: string
    chart_data: string
    recalculate: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_FINANCIAL__?: FinancialPayload
  }
}

function formatMonthName(ym: string): string {
  if (!ym) return ''
  const [y, m] = ym.split('-')
  const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez']
  const idx = Math.max(0, Math.min(11, Number(m) - 1))
  return `${months[idx]}/${y.slice(2)}`
}

function formatDateBR(iso: string): string {
  if (!iso) return ''
  const d = new Date(iso.replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return iso
  return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })
}

function resolveImage(path?: string): string {
  if (!path) return ''
  if (/^(https?:)?\/\//i.test(path) || path.startsWith('data:')) return path
  return path.startsWith('/') ? path : `/${path}`
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

export default function AdminStoreFinancialPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_FINANCIAL__) || ({} as FinancialPayload)
  const summary = payload.summary ?? ({} as FinancialPayload['summary'])
  const comparison = payload.comparison ?? ({} as FinancialPayload['comparison'])
  const urls = payload.urls

  const monthlyTrendChart = useMemo(
    () =>
      (payload.monthly_trend ?? [])
        .slice()
        .reverse()
        .map((row) => ({
          month: formatMonthName(row.month),
          Receita: row.gross_revenue,
          Pedidos: row.total_orders,
        })),
    [payload.monthly_trend],
  )

  const dailyChart = useMemo(
    () =>
      (payload.daily_sales ?? []).map((d) => ({
        date: formatDateBR(d.date),
        Pedidos: d.orders,
        Receita: d.revenue,
      })),
    [payload.daily_sales],
  )

  const hourlyChart = useMemo(
    () =>
      (payload.hourly_sales ?? []).map((h) => ({
        hour: `${String(h.hour).padStart(2, '0')}h`,
        Pedidos: h.orders,
        Receita: h.revenue,
      })),
    [payload.hourly_sales],
  )

  const hasData = (summary.total_orders ?? 0) > 0 || (payload.daily_sales ?? []).length > 0

  return (
    <AdminStorePageShell section="financial">
      <AdminPageHeader
        title="Financeiro"
        description={`Visão consolidada de ${formatMonthName(payload.current_month ?? '')}. Resumo de receita, custos e produtos.`}
        icon={<CircleDollarSign className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <div className="flex flex-wrap items-center gap-2">
            <Button asChild variant="outline" size="sm" className="gap-2">
              <a href={urls?.monthly}>
                <CalendarDays className="h-3.5 w-3.5" />
                Mensal
              </a>
            </Button>
            <Button asChild variant="outline" size="sm" className="gap-2">
              <a href={urls?.yearly}>
                <CalendarDays className="h-3.5 w-3.5" />
                Anual
              </a>
            </Button>
            <Button asChild variant="outline" size="sm" className="gap-2">
              <a href={urls?.settings}>
                <SettingsIcon className="h-3.5 w-3.5" />
                Config
              </a>
            </Button>
          </div>
        }
      />

      {/* KPIs principais */}
      <section className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <div className="flex items-center justify-between gap-2">
            <p className="text-xs text-zinc-500">Receita bruta</p>
            <GrowthBadge value={Number(comparison.revenue_growth ?? 0)} />
          </div>
          <p className="mt-1 text-2xl font-semibold text-zinc-800">{formatCurrency(summary.gross_revenue ?? 0)}</p>
          <p className="mt-1 text-[11px] text-zinc-500">
            Produtos: {formatCurrency(summary.products_revenue ?? 0)} · Entrega: {formatCurrency(summary.delivery_fees ?? 0)}
          </p>
        </div>

        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <div className="flex items-center justify-between gap-2">
            <p className="text-xs text-zinc-500">Pedidos</p>
            <GrowthBadge value={Number(comparison.orders_growth ?? 0)} />
          </div>
          <p className="mt-1 text-2xl font-semibold text-zinc-800">{summary.total_orders ?? 0}</p>
          <p className="mt-1 text-[11px] text-zinc-500">
            Concluídos: {summary.completed_orders ?? 0} · Cancelados: {summary.cancelled_orders ?? 0}
          </p>
        </div>

        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <div className="flex items-center justify-between gap-2">
            <p className="text-xs text-zinc-500">Ticket médio</p>
            <GrowthBadge value={Number(comparison.ticket_growth ?? 0)} />
          </div>
          <p className="mt-1 text-2xl font-semibold text-zinc-800">{formatCurrency(summary.avg_ticket ?? 0)}</p>
          <p className="mt-1 text-[11px] text-zinc-500">{summary.total_items ?? 0} itens vendidos</p>
        </div>

        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Descontos & cancelamentos</p>
          <p className="mt-1 text-2xl font-semibold text-red-600">−{formatCurrency((summary.discounts ?? 0) + (summary.cancelled_value ?? 0))}</p>
          <p className="mt-1 text-[11px] text-zinc-500">
            Descontos: {formatCurrency(summary.discounts ?? 0)} · Cancelado: {formatCurrency(summary.cancelled_value ?? 0)}
          </p>
        </div>
      </section>

      {!hasData && (
        <EmptyState
          title="Ainda sem vendas no mês"
          description="Os gráficos e métricas detalhados aparecem assim que houver pedidos concluídos."
          icon={<BarChart3 className="h-5 w-5" />}
        />
      )}

      {/* Atalhos para gestão */}
      <section className="grid gap-3 sm:grid-cols-3">
        <a
          href={urls?.expenses}
          className="group flex items-start gap-3 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm transition hover:border-zinc-400 hover:shadow-md"
        >
          <span className="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 text-red-600 ring-1 ring-red-100">
            <Receipt className="h-5 w-5" />
          </span>
          <div className="min-w-0 flex-1">
            <p className="text-sm font-semibold text-zinc-800 group-hover:text-zinc-950">Despesas</p>
            <p className="text-xs text-zinc-500">Gerencie custos fixos e variáveis do negócio.</p>
          </div>
        </a>

        <a
          href={urls?.product_costs}
          className="group flex items-start gap-3 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm transition hover:border-zinc-400 hover:shadow-md"
        >
          <span className="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-purple-600 ring-1 ring-purple-100">
            <CircleDollarSign className="h-5 w-5" />
          </span>
          <div className="min-w-0 flex-1">
            <p className="text-sm font-semibold text-zinc-800 group-hover:text-zinc-950">Custos de produtos</p>
            <p className="text-xs text-zinc-500">Atualize CMV e veja margens de lucro.</p>
          </div>
        </a>

        <a
          href={urls?.yearly}
          className="group flex items-start gap-3 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm transition hover:border-zinc-400 hover:shadow-md"
        >
          <span className="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100">
            <CalendarDays className="h-5 w-5" />
          </span>
          <div className="min-w-0 flex-1">
            <p className="text-sm font-semibold text-zinc-800 group-hover:text-zinc-950">Relatório anual</p>
            <p className="text-xs text-zinc-500">Compare meses ao longo do ano e veja tendências.</p>
          </div>
        </a>
      </section>

      {/* Tendência 6 meses */}
      {monthlyTrendChart.length > 0 && (
        <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
          <h2 className="mb-3 text-sm font-semibold text-zinc-800 flex items-center gap-2">
            <TrendingUp className="h-4 w-4 text-zinc-500" />
            Tendência de receita (últimos 6 meses)
          </h2>
          <div style={{ width: '100%', height: 240 }}>
            <ResponsiveContainer>
              <LineChart data={monthlyTrendChart}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e4e4e7" vertical={false} />
                <XAxis dataKey="month" stroke="#71717a" fontSize={11} />
                <YAxis stroke="#71717a" fontSize={11} />
                <Tooltip
                  contentStyle={{ border: '1px solid #e4e4e7', borderRadius: 8, fontSize: 12 }}
                  formatter={(value: number, name: string) => (name === 'Receita' ? formatCurrency(value) : value)}
                />
                <Line
                  type="monotone"
                  dataKey="Receita"
                  stroke={ctx.palette.primaryColor}
                  strokeWidth={2}
                  dot={{ r: 3, fill: ctx.palette.primaryColor }}
                />
              </LineChart>
            </ResponsiveContainer>
          </div>
        </section>
      )}

      <div className="grid gap-4 lg:grid-cols-2">
        {/* Daily sales */}
        {dailyChart.length > 0 && (
          <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 className="mb-3 text-sm font-semibold text-zinc-800 flex items-center gap-2">
              <CalendarDays className="h-4 w-4 text-zinc-500" />
              Vendas diárias do mês
            </h2>
            <div style={{ width: '100%', height: 220 }}>
              <ResponsiveContainer>
                <BarChart data={dailyChart}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#e4e4e7" vertical={false} />
                  <XAxis dataKey="date" stroke="#71717a" fontSize={10} />
                  <YAxis stroke="#71717a" fontSize={11} />
                  <Tooltip
                    contentStyle={{ border: '1px solid #e4e4e7', borderRadius: 8, fontSize: 12 }}
                    formatter={(value: number, name: string) => (name === 'Receita' ? formatCurrency(value) : value)}
                  />
                  <Bar dataKey="Pedidos" fill="#10b981" radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </section>
        )}

        {/* Hourly sales */}
        {hourlyChart.length > 0 && (
          <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 className="mb-3 text-sm font-semibold text-zinc-800 flex items-center gap-2">
              <Clock className="h-4 w-4 text-zinc-500" />
              Pedidos por hora
            </h2>
            <div style={{ width: '100%', height: 220 }}>
              <ResponsiveContainer>
                <BarChart data={hourlyChart}>
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

        {/* Top products */}
        {(payload.top_products ?? []).length > 0 && (
          <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 className="mb-3 text-sm font-semibold text-zinc-800 flex items-center gap-2">
              <Package className="h-4 w-4 text-zinc-500" />
              Mais vendidos no mês
            </h2>
            <ul className="divide-y divide-zinc-100">
              {(payload.top_products ?? []).map((p, idx) => {
                const src = resolveImage(p.image)
                return (
                  <li key={p.id || idx} className="flex items-center gap-3 py-2">
                    <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-semibold text-zinc-600">
                      {idx + 1}
                    </span>
                    {src && (
                      <img src={src} alt="" className="h-8 w-8 shrink-0 rounded-md border border-zinc-200 object-cover" />
                    )}
                    <div className="min-w-0 flex-1">
                      <p className="text-sm font-medium text-zinc-800 truncate">{p.name || `Produto #${p.id}`}</p>
                      <p className="text-[11px] text-zinc-500">{p.total_sold} vendidos</p>
                    </div>
                    <span className="text-sm font-semibold text-zinc-800 whitespace-nowrap">
                      {formatCurrency(p.total_revenue)}
                    </span>
                  </li>
                )
              })}
            </ul>
          </section>
        )}

        {/* Profitable products */}
        {(payload.profitable_products ?? []).length > 0 && (
          <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 className="mb-3 text-sm font-semibold text-zinc-800 flex items-center gap-2">
              <TrendingUp className="h-4 w-4 text-emerald-500" />
              Mais lucrativos
            </h2>
            <ul className="divide-y divide-zinc-100">
              {(payload.profitable_products ?? []).map((p, idx) => (
                <li key={p.id || idx} className="flex items-center gap-3 py-2">
                  <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-xs font-semibold text-emerald-700">
                    {idx + 1}
                  </span>
                  <div className="min-w-0 flex-1">
                    <p className="text-sm font-medium text-zinc-800 truncate">{p.name || `Produto #${p.id}`}</p>
                    <p className="text-[11px] text-zinc-500">
                      {p.total_sold} vendidos · margem {p.margin_pct.toFixed(1)}%
                    </p>
                  </div>
                  <span className="text-sm font-semibold text-emerald-700 whitespace-nowrap">
                    {formatCurrency(p.total_profit)}
                  </span>
                </li>
              ))}
            </ul>
          </section>
        )}
      </div>

      {/* Low margin */}
      {(payload.low_margin_products ?? []).length > 0 && (
        <section className="rounded-2xl border border-amber-200 bg-amber-50/30 p-5 shadow-sm">
          <h2 className="mb-3 text-sm font-semibold text-amber-900 flex items-center gap-2">
            <AlertTriangle className="h-4 w-4 text-amber-600" />
            Produtos com margem baixa — atenção
          </h2>
          <div className="grid gap-2 md:grid-cols-2">
            {(payload.low_margin_products ?? []).map((p) => (
              <div key={p.id} className="rounded-lg border border-amber-200 bg-white p-3">
                <p className="text-sm font-medium text-zinc-800 truncate">{p.name || `Produto #${p.id}`}</p>
                <p className="mt-1 text-[11px] text-zinc-500">
                  Preço: {formatCurrency(p.price)} · Custo: {formatCurrency(p.cost)}
                </p>
                <Badge
                  className={`mt-1 text-[10px] h-4 border ${
                    p.margin_pct < 20
                      ? 'bg-red-100 text-red-700 border-red-200 hover:bg-red-100'
                      : 'bg-amber-100 text-amber-700 border-amber-200 hover:bg-amber-100'
                  }`}
                >
                  Margem {p.margin_pct.toFixed(1)}%
                </Badge>
              </div>
            ))}
          </div>
        </section>
      )}
    </AdminStorePageShell>
  )
}
