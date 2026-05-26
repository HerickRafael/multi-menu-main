import { useMemo } from 'react'
import {
  AlertTriangle,
  ArrowLeft,
  BarChart3,
  CalendarDays,
  CircleDollarSign,
  Clock,
  Package,
  Settings as SettingsIcon,
  TrendingUp,
} from 'lucide-react'
import {
  Bar,
  BarChart,
  CartesianGrid,
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

type Summary = {
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
  production_cost: number
  products_profit: number
  fixed_expenses: number
  variable_expenses: number
  total_expenses: number
  net_profit: number
  profit_margin: number
}

type Payload = {
  month: string
  month_label: string
  available_months: Array<{ value: string; label: string }>
  summary: Summary
  top_products: Array<{
    id: number
    name: string
    price: number
    quantity_sold: number
    total_revenue: number
    total_profit: number
    avg_margin: number
  }>
  profitable_products: Array<{
    id: number
    name: string
    total_sold: number
    total_profit: number
    margin_pct: number
  }>
  daily_sales: Array<{ date: string; orders: number; revenue: number }>
  hourly_sales: Array<{ hour: number; orders: number; revenue: number }>
  urls: { dashboard: string; yearly: string; settings: string; self: string }
}

declare global {
  interface Window {
    __ADMIN_STORE_FINANCIAL_MONTHLY__?: Payload
  }
}

function formatDateBR(iso: string): string {
  if (!iso) return ''
  const d = new Date(iso.replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return iso
  return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })
}

export default function AdminStoreFinancialMonthlyPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_FINANCIAL_MONTHLY__) || ({} as Payload)
  const s = payload.summary ?? ({} as Summary)
  const urls = payload.urls

  function changeMonth(month: string) {
    if (typeof window !== 'undefined') {
      const url = new URL(window.location.href)
      url.searchParams.set('month', month)
      window.location.href = url.toString()
    }
  }

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
      })),
    [payload.hourly_sales],
  )

  const hasData = (s.total_orders ?? 0) > 0 || (payload.daily_sales ?? []).length > 0
  const netProfit = s.net_profit ?? s.gross_revenue - s.total_expenses
  const profitMargin = s.profit_margin || (s.gross_revenue > 0 ? (netProfit / s.gross_revenue) * 100 : 0)

  return (
    <AdminStorePageShell section="financial">
      <AdminPageHeader
        title="Relatório mensal"
        description={`Análise detalhada de ${payload.month_label}.`}
        icon={<BarChart3 className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
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
              <a href={urls.dashboard}>
                <ArrowLeft className="h-3.5 w-3.5" />
                Voltar
              </a>
            </Button>
          </div>
        }
      />

      <section className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Receita bruta</p>
          <p className="mt-1 text-2xl font-semibold text-zinc-800">{formatCurrency(s.gross_revenue ?? 0)}</p>
          <p className="mt-1 text-[11px] text-zinc-500">
            {s.total_orders ?? 0} pedidos · ticket {formatCurrency(s.avg_ticket ?? 0)}
          </p>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Despesas totais</p>
          <p className="mt-1 text-2xl font-semibold text-red-600">−{formatCurrency(s.total_expenses ?? 0)}</p>
          <p className="mt-1 text-[11px] text-zinc-500">
            Fixas {formatCurrency(s.fixed_expenses ?? 0)} · Var {formatCurrency(s.variable_expenses ?? 0)}
          </p>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Lucro líquido</p>
          <p className={`mt-1 text-2xl font-semibold ${netProfit >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>
            {netProfit >= 0 ? '' : '−'}
            {formatCurrency(Math.abs(netProfit))}
          </p>
          <p className="mt-1 text-[11px] text-zinc-500">CMV: {formatCurrency(s.production_cost ?? 0)}</p>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Margem de lucro</p>
          <p className={`mt-1 text-2xl font-semibold ${profitMargin >= 20 ? 'text-emerald-600' : profitMargin >= 0 ? 'text-amber-600' : 'text-red-600'}`}>
            {profitMargin.toFixed(1)}%
          </p>
          <Badge
            className={`mt-1 border text-[10px] h-4 ${
              profitMargin >= 20
                ? 'bg-emerald-100 text-emerald-700 border-emerald-200 hover:bg-emerald-100'
                : profitMargin >= 0
                  ? 'bg-amber-100 text-amber-700 border-amber-200 hover:bg-amber-100'
                  : 'bg-red-100 text-red-700 border-red-200 hover:bg-red-100'
            }`}
          >
            {profitMargin >= 20 ? 'Saudável' : profitMargin >= 0 ? 'Atenção' : 'Prejuízo'}
          </Badge>
        </div>
      </section>

      {!hasData && (
        <EmptyState
          title="Sem dados neste mês"
          description="Faça pedidos para ver o relatório financeiro detalhado."
          icon={<BarChart3 className="h-5 w-5" />}
        />
      )}

      {dailyChart.length > 0 && (
        <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
          <h2 className="mb-3 text-sm font-semibold text-zinc-800 flex items-center gap-2">
            <CalendarDays className="h-4 w-4 text-zinc-500" />
            Vendas dia a dia
          </h2>
          <div style={{ width: '100%', height: 260 }}>
            <ResponsiveContainer>
              <BarChart data={dailyChart}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e4e4e7" vertical={false} />
                <XAxis dataKey="date" stroke="#71717a" fontSize={10} />
                <YAxis stroke="#71717a" fontSize={11} />
                <Tooltip
                  contentStyle={{ border: '1px solid #e4e4e7', borderRadius: 8, fontSize: 12 }}
                  formatter={(value: number, name: string) => (name === 'Receita' ? formatCurrency(value) : value)}
                />
                <Bar dataKey="Pedidos" fill={ctx.palette.primaryColor} radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </section>
      )}

      <div className="grid gap-4 lg:grid-cols-2">
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

        {(payload.top_products ?? []).length > 0 && (
          <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 className="mb-3 text-sm font-semibold text-zinc-800 flex items-center gap-2">
              <Package className="h-4 w-4 text-zinc-500" />
              Top 20 produtos do mês
            </h2>
            <ul className="divide-y divide-zinc-100 max-h-96 overflow-y-auto">
              {(payload.top_products ?? []).map((p, idx) => (
                <li key={p.id || idx} className="flex items-center gap-3 py-2">
                  <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-semibold text-zinc-600">
                    {idx + 1}
                  </span>
                  <div className="min-w-0 flex-1">
                    <p className="text-sm font-medium text-zinc-800 truncate">{p.name}</p>
                    <p className="text-[11px] text-zinc-500">{p.quantity_sold} vendidos</p>
                  </div>
                  <span className="text-sm font-semibold text-zinc-800 whitespace-nowrap">
                    {formatCurrency(p.total_revenue)}
                  </span>
                </li>
              ))}
            </ul>
          </section>
        )}
      </div>

      {/* Profit/loss breakdown */}
      <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
        <h2 className="mb-4 text-sm font-semibold text-zinc-800 flex items-center gap-2">
          <CircleDollarSign className="h-4 w-4 text-zinc-500" />
          Composição do resultado
        </h2>
        <ul className="space-y-1.5 text-sm">
          <li className="flex items-center justify-between border-b border-zinc-100 pb-1.5">
            <span className="text-zinc-600">Receita bruta</span>
            <span className="font-semibold text-zinc-800">{formatCurrency(s.gross_revenue ?? 0)}</span>
          </li>
          <li className="flex items-center justify-between text-xs pl-3">
            <span className="text-zinc-500">↳ Produtos</span>
            <span className="text-zinc-600">{formatCurrency(s.products_revenue ?? 0)}</span>
          </li>
          <li className="flex items-center justify-between text-xs pl-3">
            <span className="text-zinc-500">↳ Taxa entrega</span>
            <span className="text-zinc-600">{formatCurrency(s.delivery_fees ?? 0)}</span>
          </li>
          <li className="flex items-center justify-between text-xs pl-3">
            <span className="text-zinc-500">↳ Descontos</span>
            <span className="text-red-600">−{formatCurrency(s.discounts ?? 0)}</span>
          </li>
          <li className="flex items-center justify-between border-b border-zinc-100 py-1.5 mt-2">
            <span className="text-zinc-600">CMV (custo de produção)</span>
            <span className="font-semibold text-red-600">−{formatCurrency(s.production_cost ?? 0)}</span>
          </li>
          <li className="flex items-center justify-between border-b border-zinc-100 py-1.5">
            <span className="text-zinc-600">Despesas operacionais</span>
            <span className="font-semibold text-red-600">−{formatCurrency(s.total_expenses ?? 0)}</span>
          </li>
          <li className="flex items-center justify-between text-xs pl-3">
            <span className="text-zinc-500">↳ Fixas</span>
            <span className="text-zinc-600">{formatCurrency(s.fixed_expenses ?? 0)}</span>
          </li>
          <li className="flex items-center justify-between text-xs pl-3">
            <span className="text-zinc-500">↳ Variáveis</span>
            <span className="text-zinc-600">{formatCurrency(s.variable_expenses ?? 0)}</span>
          </li>
          <li className="flex items-center justify-between pt-2">
            <span className="font-semibold text-zinc-800">Lucro líquido</span>
            <span className={`font-bold text-lg ${netProfit >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>
              {netProfit >= 0 ? '' : '−'}
              {formatCurrency(Math.abs(netProfit))}
            </span>
          </li>
        </ul>
      </section>
    </AdminStorePageShell>
  )
}
