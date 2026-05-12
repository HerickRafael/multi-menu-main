import { BarChart3 } from 'lucide-react'
import { BarChart, Bar, CartesianGrid, LineChart, Line, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { PageContainer, PageHeader } from '@/components/shared/PageHeader'
import { ChartSkeleton, TableSkeleton } from '@/components/shared/Skeletons'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useAnalyticsData } from '@/js/hooks/usePhase6Data'
import { formatCurrency, formatNumber } from '@/js/lib/utils'

function statusVariant(status: string): 'default' | 'success' | 'warning' | 'destructive' {
  if (status === 'completed' || status === 'paid') return 'success'
  if (status === 'pending') return 'warning'
  if (status === 'canceled' || status === 'failed') return 'destructive'
  return 'default'
}

export default function AnalyticsPage() {
  const { data, isLoading, isFetching, error } = useAnalyticsData()

  if (isLoading) {
    return (
      <PageContainer>
        <ChartSkeleton height={220} />
        <ChartSkeleton height={320} />
        <TableSkeleton rows={6} cols={4} />
      </PageContainer>
    )
  }

  if (error || !data) {
    return (
      <PageContainer>
        <Card>
          <CardHeader><CardTitle className="text-base text-destructive">Falha ao carregar analytics</CardTitle></CardHeader>
          <CardContent className="text-sm text-muted-foreground">{error?.message ?? 'Nenhum dado disponível.'}</CardContent>
        </Card>
      </PageContainer>
    )
  }

  return (
    <PageContainer>
      <PageHeader title="Analytics" description="Receita, volume e performance operacional da plataforma">
        <Badge variant="secondary" className="gap-1">
          <BarChart3 className="h-3.5 w-3.5" />
          {isFetching ? 'Atualizando' : 'Fase 6'}
        </Badge>
      </PageHeader>

      <div className="grid grid-cols-2 gap-4 lg:grid-cols-5">
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Pedidos</CardTitle></CardHeader><CardContent className="text-2xl font-bold">{formatNumber(data.kpis.orders_total)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Receita</CardTitle></CardHeader><CardContent className="text-xl font-bold">{formatCurrency(data.kpis.revenue_total)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Ticket médio</CardTitle></CardHeader><CardContent className="text-xl font-bold text-blue-600">{formatCurrency(data.kpis.average_ticket)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Lojas</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-emerald-600">{formatNumber(data.kpis.stores_total)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Usuários ativos</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-amber-600">{formatNumber(data.kpis.active_users)}</CardContent></Card>
      </div>

      <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <Card>
          <CardHeader><CardTitle className="text-base">Receita por dia</CardTitle></CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={300}>
              <LineChart data={data.revenue_by_day}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="date" />
                <YAxis />
                <Tooltip formatter={(value: number) => formatCurrency(value)} />
                <Line type="monotone" dataKey="revenue" stroke="hsl(var(--primary))" strokeWidth={2} />
              </LineChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle className="text-base">Pedidos por dia</CardTitle></CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={300}>
              <BarChart data={data.revenue_by_day}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="date" />
                <YAxis />
                <Tooltip formatter={(value: number) => formatNumber(value)} />
                <Bar dataKey="orders" fill="hsl(var(--chart-2))" radius={[6, 6, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>
      </div>

      <div className="grid grid-cols-1 gap-4 xl:grid-cols-[1.4fr_1fr]">
        <Card>
          <CardHeader><CardTitle className="text-base">Top lojas por receita</CardTitle></CardHeader>
          <CardContent>
            <div className="overflow-x-auto rounded-md border">
              <table className="w-full min-w-[620px] text-sm">
                <thead className="bg-muted/50 text-left">
                  <tr>
                    <th className="px-4 py-3 font-medium">Loja</th>
                    <th className="px-4 py-3 font-medium">Pedidos</th>
                    <th className="px-4 py-3 font-medium">Receita</th>
                  </tr>
                </thead>
                <tbody>
                  {data.top_stores.map((store) => (
                    <tr key={store.id} className="border-t">
                      <td className="px-4 py-3 font-medium">{store.name}</td>
                      <td className="px-4 py-3">{formatNumber(store.orders)}</td>
                      <td className="px-4 py-3">{formatCurrency(store.revenue)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
              {data.top_stores.length === 0 && <p className="p-6 text-center text-sm text-muted-foreground">Sem dados de lojas no período.</p>}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle className="text-base">Distribuição por status</CardTitle></CardHeader>
          <CardContent className="space-y-3">
            {data.status_breakdown.map((item) => (
              <div key={item.status} className="flex items-center justify-between rounded-md border px-3 py-2">
                <Badge variant={statusVariant(item.status)}>{item.status}</Badge>
                <span className="font-medium">{formatNumber(item.total)}</span>
              </div>
            ))}
            {data.status_breakdown.length === 0 && <p className="text-sm text-muted-foreground">Sem status registrados.</p>}
          </CardContent>
        </Card>
      </div>
    </PageContainer>
  )
}
