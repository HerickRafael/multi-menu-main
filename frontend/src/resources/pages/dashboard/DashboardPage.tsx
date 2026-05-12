import { memo } from 'react'
import { PageContainer, PageHeader } from '@/components/shared/PageHeader'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { useDashboardMetrics } from '@/js/hooks/useDashboardMetrics'
import { cn, formatNumber, formatRelativeTime } from '@/js/lib/utils'
import { Activity, Store, ShoppingBag, MessageSquare, Cpu, MemoryStick, Wifi } from 'lucide-react'
import type { DashboardData, OrdersPerHourPoint, RecentEvent } from '@/js/types/dashboard'

const FALLBACK_DASHBOARD_DATA: DashboardData = {
  kpis: {
    stores_online: 0,
    stores_total: 0,
    orders_active: 0,
    users_total: 0,
  },
  system: {
    cpu_percent: 0,
    ram_mb: 0,
    websocket_clients: 0,
    workers_online: 0,
  },
  orders_per_hour: [],
  recent_events: [],
  updated_at: new Date(0).toISOString(),
}

const barHeights = ['h-2', 'h-3', 'h-4', 'h-5', 'h-6', 'h-7', 'h-8', 'h-9', 'h-10', 'h-12', 'h-14', 'h-16']

function resolveBarHeight(value: number) {
  const index = Math.min(barHeights.length - 1, Math.floor((value / 100) * barHeights.length))
  return barHeights[index]
}

// Placeholder KPI card — will be replaced with real data in PLANO 2
function PlaceholderKpiCard({ title, value, subtitle, icon: Icon }: {
  title: string
  value: string | number
  subtitle?: string
  icon: React.ElementType
}) {
  return (
    <Card className="relative overflow-hidden">
      <CardHeader className="pb-2 flex-row items-start justify-between space-y-0">
        <CardTitle className="text-sm font-medium text-muted-foreground">{title}</CardTitle>
        <Icon className="h-4 w-4 text-muted-foreground" />
      </CardHeader>
      <CardContent>
        <div className="text-2xl font-bold">{value}</div>
        {subtitle && <p className="text-xs text-muted-foreground mt-0.5">{subtitle}</p>}
      </CardContent>
    </Card>
  )
}

const ActivityBars = memo(function ActivityBars({ points }: { points: OrdersPerHourPoint[] }) {
  if (!points.length) {
    return <p className="text-sm text-muted-foreground text-center py-6">Sem dados nas ultimas horas.</p>
  }

  const limited = points.slice(-12)
  const maxOrders = Math.max(1, ...limited.map((p) => p.orders))

  return (
    <div className="grid grid-cols-12 gap-1.5">
      {limited.map((point) => {
        const height = Math.max(8, Math.round((point.orders / maxOrders) * 100))
        const heightClass = resolveBarHeight(height)
        return (
          <div key={`${point.hour}-${point.orders}`} className="flex flex-col items-center gap-1">
            <div
              className={cn('w-full rounded-sm bg-primary/20', heightClass)}
              title={`${point.hour}: ${point.orders}`}
            />
            <span className="text-[10px] text-muted-foreground">{point.hour}</span>
          </div>
        )
      })}
    </div>
  )
})

const RecentEvents = memo(function RecentEvents({ events }: { events: RecentEvent[] }) {
  if (events.length === 0) {
    return <p className="text-sm text-muted-foreground text-center py-6">Sem eventos recentes.</p>
  }

  return (
    <div className="space-y-3">
      {events.map((event) => {
        const key = `${event.title}-${event.at}`
        return (
          <div key={key} className="flex items-start justify-between gap-3 border-b border-border/50 pb-2">
            <p className="text-sm font-medium leading-tight">{event.title}</p>
            <p className="text-xs text-muted-foreground whitespace-nowrap">{formatRelativeTime(event.at)}</p>
          </div>
        )
      })}
    </div>
  )
})

export default function DashboardPage() {
  const { data, isError, error, isFetching } = useDashboardMetrics()
  const safeData = data ?? FALLBACK_DASHBOARD_DATA

  if (!data && isFetching) {
    return (
      <PageContainer>
        <PageHeader title="Dashboard" description="Visao geral da plataforma" />
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Carregando dashboard...</CardTitle>
            <CardDescription>Buscando os dados mais recentes.</CardDescription>
          </CardHeader>
        </Card>
      </PageContainer>
    )
  }

  if (isError && !data) {
    return (
      <PageContainer>
        <PageHeader title="Dashboard" description="Visao geral da plataforma" />
        <Card>
          <CardHeader>
            <CardTitle className="text-base text-destructive">Falha ao carregar dashboard</CardTitle>
            <CardDescription>{error?.message ?? 'Nao foi possivel carregar os dados agora.'}</CardDescription>
          </CardHeader>
        </Card>
      </PageContainer>
    )
  }

  return (
    <PageContainer>
      <PageHeader
        title="Dashboard"
        description="Visão geral da plataforma em tempo real"
      >
        <Badge variant={isFetching ? 'secondary' : 'success'} className="gap-1">
          <span className="live-indicator" />
          {isFetching ? 'Atualizando' : 'Ao vivo'}
        </Badge>
      </PageHeader>

      {isError && (
        <Card>
          <CardHeader>
            <CardTitle className="text-base text-destructive">Falha ao atualizar dados</CardTitle>
            <CardDescription>{error?.message ?? 'Mantendo o ultimo snapshot valido.'}</CardDescription>
          </CardHeader>
        </Card>
      )}

      {/* KPI Grid */}
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-4">
        <PlaceholderKpiCard
          title="Lojas Online"
          value={`${formatNumber(safeData.kpis.stores_online)} / ${formatNumber(safeData.kpis.stores_total)}`}
          icon={Store}
        />
        <PlaceholderKpiCard title="Pedidos Ativos" value={formatNumber(safeData.kpis.orders_active)} icon={ShoppingBag} />
        <PlaceholderKpiCard title="Usuários" value={formatNumber(safeData.kpis.users_total)} icon={MessageSquare} />
        <PlaceholderKpiCard title="Workers Online" value={formatNumber(safeData.system.workers_online)} icon={Activity} />
      </div>

      {/* System metrics row */}
      <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
        <PlaceholderKpiCard title="CPU" value={`${formatNumber(safeData.system.cpu_percent)}%`} icon={Cpu} subtitle="Carga aproximada" />
        <PlaceholderKpiCard title="RAM" value={`${formatNumber(safeData.system.ram_mb)} MB`} icon={MemoryStick} subtitle="Uso do processo" />
        <PlaceholderKpiCard
          title="WebSocket"
          value="Conectado"
          icon={Wifi}
          subtitle={`${formatNumber(safeData.system.websocket_clients)} clientes ativos`}
        />
      </div>

      {/* Charts */}
      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle className="text-sm">Pedidos por hora</CardTitle>
            <CardDescription>Ultimas 12 horas (modo leve)</CardDescription>
          </CardHeader>
          <CardContent>
            <ActivityBars points={safeData.orders_per_hour} />
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle className="text-sm">Eventos Recentes</CardTitle>
            <CardDescription>Sistema + Operação</CardDescription>
          </CardHeader>
          <CardContent>
            <RecentEvents events={safeData.recent_events} />
          </CardContent>
        </Card>
      </div>

      <p className="text-xs text-muted-foreground text-right">
        Atualizado {formatRelativeTime(safeData.updated_at)}
      </p>
    </PageContainer>
  )
}
