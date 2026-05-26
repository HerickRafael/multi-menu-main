import { useMemo } from 'react'
import { ArrowLeft, BarChart3, CalendarDays, TrendingUp } from 'lucide-react'
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
import { AdminStorePageShell, AdminPageHeader, EmptyState, formatCurrency, useStoreContext } from '@/components/admin-store'

type Summary = {
  total_orders: number
  completed_orders: number
  gross_revenue: number
  products_revenue: number
  delivery_fees: number
  discounts: number
  total_expenses: number
  net_profit: number
  profit_margin: number
  avg_ticket: number
}

type Payload = {
  year: number
  available_years: number[]
  summary: Summary
  monthly_trend: Array<{
    month: string
    gross_revenue: number
    total_orders: number
    net_profit: number
  }>
  urls: { dashboard: string; monthly: string; settings: string; self: string }
}

declare global {
  interface Window {
    __ADMIN_STORE_FINANCIAL_YEARLY__?: Payload
  }
}

function formatMonthName(ym: string): string {
  if (!ym) return ''
  const [y, m] = ym.split('-')
  const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez']
  const idx = Math.max(0, Math.min(11, Number(m) - 1))
  return `${months[idx]}/${y.slice(2)}`
}

export default function AdminStoreFinancialYearlyPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_FINANCIAL_YEARLY__) || ({} as Payload)
  const s = payload.summary ?? ({} as Summary)
  const urls = payload.urls

  function changeYear(year: number) {
    if (typeof window !== 'undefined') {
      const url = new URL(window.location.href)
      url.searchParams.set('year', String(year))
      window.location.href = url.toString()
    }
  }

  const trendChart = useMemo(
    () =>
      (payload.monthly_trend ?? [])
        .slice()
        .reverse()
        .map((row) => ({
          month: formatMonthName(row.month),
          Receita: row.gross_revenue,
          Lucro: row.net_profit,
          Pedidos: row.total_orders,
        })),
    [payload.monthly_trend],
  )

  const hasData = (s.total_orders ?? 0) > 0 || trendChart.length > 0
  const netProfit = s.net_profit ?? 0
  const profitMargin = s.profit_margin || 0

  return (
    <AdminStorePageShell section="financial">
      <AdminPageHeader
        title={`Relatório anual · ${payload.year}`}
        description="Visão consolidada do ano completo com tendência mês a mês."
        icon={<CalendarDays className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <div className="flex flex-wrap items-center gap-2">
            <select
              value={payload.year}
              onChange={(e) => changeYear(Number(e.target.value))}
              className="flex h-9 rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
            >
              {payload.available_years?.map((y) => (
                <option key={y} value={y}>
                  {y}
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
          <p className="text-xs text-zinc-500">Receita do ano</p>
          <p className="mt-1 text-2xl font-semibold text-zinc-800">{formatCurrency(s.gross_revenue ?? 0)}</p>
          <p className="mt-1 text-[11px] text-zinc-500">{s.total_orders ?? 0} pedidos</p>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Despesas do ano</p>
          <p className="mt-1 text-2xl font-semibold text-red-600">−{formatCurrency(s.total_expenses ?? 0)}</p>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Lucro líquido</p>
          <p className={`mt-1 text-2xl font-semibold ${netProfit >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>
            {netProfit >= 0 ? '' : '−'}
            {formatCurrency(Math.abs(netProfit))}
          </p>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Margem média</p>
          <p
            className={`mt-1 text-2xl font-semibold ${
              profitMargin >= 20 ? 'text-emerald-600' : profitMargin >= 0 ? 'text-amber-600' : 'text-red-600'
            }`}
          >
            {profitMargin.toFixed(1)}%
          </p>
          <p className="mt-1 text-[11px] text-zinc-500">Ticket {formatCurrency(s.avg_ticket ?? 0)}</p>
        </div>
      </section>

      {!hasData ? (
        <EmptyState
          title="Sem dados neste ano"
          description="Quando houver pedidos, o relatório anual aparece aqui."
          icon={<BarChart3 className="h-5 w-5" />}
        />
      ) : (
        <>
          {/* Trend chart */}
          <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 className="mb-3 text-sm font-semibold text-zinc-800 flex items-center gap-2">
              <TrendingUp className="h-4 w-4 text-zinc-500" />
              Receita × Lucro mês a mês
            </h2>
            <div style={{ width: '100%', height: 280 }}>
              <ResponsiveContainer>
                <LineChart data={trendChart}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#e4e4e7" vertical={false} />
                  <XAxis dataKey="month" stroke="#71717a" fontSize={11} />
                  <YAxis stroke="#71717a" fontSize={11} />
                  <Tooltip
                    contentStyle={{ border: '1px solid #e4e4e7', borderRadius: 8, fontSize: 12 }}
                    formatter={(value: number, name: string) =>
                      name === 'Pedidos' ? value : formatCurrency(value)
                    }
                  />
                  <Line
                    type="monotone"
                    dataKey="Receita"
                    stroke={ctx.palette.primaryColor}
                    strokeWidth={2}
                    dot={{ r: 3, fill: ctx.palette.primaryColor }}
                  />
                  <Line
                    type="monotone"
                    dataKey="Lucro"
                    stroke="#10b981"
                    strokeWidth={2}
                    dot={{ r: 3, fill: '#10b981' }}
                  />
                </LineChart>
              </ResponsiveContainer>
            </div>
          </section>

          {/* Orders bar chart */}
          <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 className="mb-3 text-sm font-semibold text-zinc-800 flex items-center gap-2">
              <CalendarDays className="h-4 w-4 text-zinc-500" />
              Pedidos mês a mês
            </h2>
            <div style={{ width: '100%', height: 220 }}>
              <ResponsiveContainer>
                <BarChart data={trendChart}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#e4e4e7" vertical={false} />
                  <XAxis dataKey="month" stroke="#71717a" fontSize={11} />
                  <YAxis stroke="#71717a" fontSize={11} />
                  <Tooltip contentStyle={{ border: '1px solid #e4e4e7', borderRadius: 8, fontSize: 12 }} />
                  <Bar dataKey="Pedidos" fill="#0ea5e9" radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </section>

          {/* Monthly table */}
          <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 className="mb-3 text-sm font-semibold text-zinc-800">Detalhamento mensal</h2>
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-zinc-200 text-xs uppercase tracking-wide text-zinc-500">
                    <th className="text-left py-2 font-medium">Mês</th>
                    <th className="text-right py-2 font-medium">Pedidos</th>
                    <th className="text-right py-2 font-medium">Receita</th>
                    <th className="text-right py-2 font-medium">Lucro</th>
                  </tr>
                </thead>
                <tbody>
                  {(payload.monthly_trend ?? []).map((row) => (
                    <tr key={row.month} className="border-b border-zinc-100 hover:bg-zinc-50">
                      <td className="py-2 font-medium text-zinc-800">{formatMonthName(row.month)}</td>
                      <td className="text-right py-2 text-zinc-700">{row.total_orders}</td>
                      <td className="text-right py-2 text-zinc-700">{formatCurrency(row.gross_revenue)}</td>
                      <td
                        className={`text-right py-2 font-medium ${row.net_profit >= 0 ? 'text-emerald-700' : 'text-red-600'}`}
                      >
                        {row.net_profit >= 0 ? '' : '−'}
                        {formatCurrency(Math.abs(row.net_profit))}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>
        </>
      )}
    </AdminStorePageShell>
  )
}
