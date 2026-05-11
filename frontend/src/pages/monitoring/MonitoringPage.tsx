import { Activity, Cpu, Database, HardDrive } from 'lucide-react'
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts'
import { PageContainer, PageHeader } from '@/components/shared/PageHeader'
import { ChartSkeleton, KpiCardsSkeleton } from '@/components/shared/Skeletons'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useMonitoring } from '@/hooks/usePhase4Data'
import { formatNumber } from '@/lib/utils'

export default function MonitoringPage() {
  const { data, isLoading, isFetching, error } = useMonitoring()

  if (isLoading) {
    return (
      <PageContainer>
        <KpiCardsSkeleton />
        <ChartSkeleton height={320} />
        <ChartSkeleton height={320} />
      </PageContainer>
    )
  }

  if (error) {
    return (
      <PageContainer>
        <Card>
          <CardHeader>
            <CardTitle className="text-base text-destructive">Falha ao carregar monitoramento</CardTitle>
          </CardHeader>
          <CardContent className="text-sm text-muted-foreground">{error.message}</CardContent>
        </Card>
      </PageContainer>
    )
  }

  if (!data) {
    return (
      <PageContainer>
        <Card>
          <CardContent className="py-6 text-sm text-muted-foreground">Nenhum dado disponível.</CardContent>
        </Card>
      </PageContainer>
    )
  }

  const { system, workers, updated_at } = data
  const { cpu, memory, database } = system

  const getCpuStatus = (percent: number) => {
    if (percent >= 80) return 'destructive'
    if (percent >= 60) return 'warning'
    return 'success'
  }

  const getMemoryStatus = (percent: number) => {
    if (percent >= 85) return 'destructive'
    if (percent >= 70) return 'warning'
    return 'success'
  }

  return (
    <PageContainer>
      <PageHeader title="Monitoramento" description="Saúde operacional da plataforma em tempo real">
        <Badge variant="secondary" className="gap-1">
          <Activity className="h-3.5 w-3.5" />
          {isFetching ? 'Atualizando' : 'Fase 4'}
        </Badge>
      </PageHeader>

      <div className="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm font-medium flex items-center gap-2">
              <Cpu className="h-4 w-4" />
              Processador
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{cpu.percent}%</div>
            <Badge variant={getCpuStatus(cpu.percent)} className="mt-2">
              {cpu.percent >= 80 ? 'Alto' : cpu.percent >= 60 ? 'Médio' : 'Baixo'}
            </Badge>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm font-medium flex items-center gap-2">
              <HardDrive className="h-4 w-4" />
              Memória
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{memory.percent}%</div>
            <p className="text-xs text-muted-foreground mt-1">
              {memory.used_mb}MB / {memory.total_mb}MB
            </p>
            <Badge variant={getMemoryStatus(memory.percent)} className="mt-2">
              {memory.percent >= 85 ? 'Crítico' : memory.percent >= 70 ? 'Alto' : 'Normal'}
            </Badge>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm font-medium flex items-center gap-2">
              <Database className="h-4 w-4" />
              Banco de Dados
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{database.connections}</div>
            <p className="text-xs text-muted-foreground mt-1">
              {database.max_connections} máximo
            </p>
            <Badge variant="default" className="mt-2">
              {database.max_connections > 0 ? ((database.connections / database.max_connections) * 100).toFixed(0) : 0}% uso
            </Badge>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm font-medium flex items-center gap-2">
              <Activity className="h-4 w-4" />
              Workers
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatNumber(workers.jobs_queued)}</div>
            <p className="text-xs text-muted-foreground mt-1">
              {workers.online} worker(s) online
            </p>
            <Badge variant={workers.jobs_queued > 50 ? 'warning' : 'default'} className="mt-2">
              {workers.jobs_queued > 0 ? 'Processando' : 'Idle'}
            </Badge>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Uso de CPU (12h)</CardTitle>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={300}>
              <LineChart data={cpu.history}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="time" />
                <YAxis domain={[0, 100]} />
                <Tooltip />
                <Line
                  type="monotone"
                  dataKey="value"
                  stroke="hsl(var(--primary))"
                  name="CPU %"
                  isAnimationActive
                />
              </LineChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Uso de Memória (12h)</CardTitle>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={300}>
              <LineChart data={memory.history}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="time" />
                <YAxis domain={[0, 100]} />
                <Tooltip />
                <Line
                  type="monotone"
                  dataKey="value"
                  stroke="hsl(var(--chart-2))"
                  name="Memória %"
                  isAnimationActive
                />
              </LineChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="text-base">Sinais de atenção</CardTitle>
        </CardHeader>
        <CardContent className="space-y-2 text-sm text-muted-foreground">
          <p>{cpu.percent >= 80 ? 'CPU acima de 80%: investigar processos pesados.' : 'CPU sob controle.'}</p>
          <p>{memory.percent >= 85 ? `Memória crítica em ${memory.percent}%.` : 'Memória dentro da faixa esperada.'}</p>
          <p>{workers.jobs_queued > 100 ? `${formatNumber(workers.jobs_queued)} jobs aguardando processamento.` : 'Fila operacional sem gargalo relevante.'}</p>
          <p>Atualizado em {new Date(updated_at).toLocaleString('pt-BR')}.</p>
        </CardContent>
      </Card>
    </PageContainer>
  )
}
