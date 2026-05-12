import { memo } from 'react'
import { Server } from 'lucide-react'
import { PageContainer, PageHeader } from '@/components/shared/PageHeader'
import { TableSkeleton } from '@/components/shared/Skeletons'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useRunSystemChecksMutation, useSystemData } from '@/js/hooks/usePhase6Data'
import { formatNumber, truncate } from '@/js/lib/utils'
import type { HealthCheckItem } from '@/js/types/phase6'

function statusVariant(status: string): 'default' | 'success' | 'warning' | 'destructive' {
  if (status === 'ok') return 'success'
  if (status === 'warning') return 'warning'
  if (status === 'critical') return 'destructive'
  return 'default'
}

const HealthCheckRow = memo(function HealthCheckRow({ check }: { check: HealthCheckItem }) {
  return (
    <tr className="border-t">
      <td className="px-4 py-3 font-medium">{check.component}</td>
      <td className="px-4 py-3"><Badge variant={statusVariant(check.status)}>{check.status}</Badge></td>
      <td className="px-4 py-3 text-muted-foreground">{truncate(check.message || '-', 80)}</td>
      <td className="px-4 py-3 text-muted-foreground">{truncate(check.metadata_json || '-', 60)}</td>
      <td className="px-4 py-3 text-muted-foreground">{check.checked_at ? new Date(check.checked_at).toLocaleString('pt-BR') : '-'}</td>
    </tr>
  )
})

export default function SystemPage() {
  const { data, isLoading, isFetching, error } = useSystemData()
  const runChecksMutation = useRunSystemChecksMutation()

  if (isLoading) {
    return (
      <PageContainer>
        <TableSkeleton rows={8} cols={5} />
      </PageContainer>
    )
  }

  if (error || !data) {
    return (
      <PageContainer>
        <Card>
          <CardHeader><CardTitle className="text-base text-destructive">Falha ao carregar sistema</CardTitle></CardHeader>
          <CardContent className="text-sm text-muted-foreground">{error?.message ?? 'Nenhum dado disponível.'}</CardContent>
        </Card>
      </PageContainer>
    )
  }

  return (
    <PageContainer>
      <PageHeader title="Sistema" description="Observabilidade, runtime e execução de health checks">
        <div className="flex items-center gap-2">
          <Badge variant="secondary" className="gap-1">
            <Server className="h-3.5 w-3.5" />
            {isFetching ? 'Atualizando' : 'Fase 6'}
          </Badge>
          <Button variant="outline" size="sm" onClick={() => runChecksMutation.mutate()} disabled={runChecksMutation.isPending}>
            {runChecksMutation.isPending ? 'Executando...' : 'Rodar checks'}
          </Button>
        </div>
      </PageHeader>

      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Checks/h</CardTitle></CardHeader><CardContent className="text-2xl font-bold">{formatNumber(data.summary.total)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">OK</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-emerald-600">{formatNumber(data.summary.ok)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Warnings</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-amber-600">{formatNumber(data.summary.warning)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Críticos</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-rose-600">{formatNumber(data.summary.critical)}</CardContent></Card>
      </div>

      <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <Card>
          <CardHeader><CardTitle className="text-base">Runtime</CardTitle></CardHeader>
          <CardContent className="space-y-3 text-sm">
            <div className="flex items-center justify-between"><span>PHP</span><span className="font-medium">{data.runtime.php_version}</span></div>
            <div className="flex items-center justify-between"><span>Environment</span><Badge variant={data.runtime.app_env === 'production' ? 'success' : 'warning'}>{data.runtime.app_env}</Badge></div>
            <div className="flex items-center justify-between"><span>Timezone</span><span className="font-medium">{data.runtime.timezone}</span></div>
            <div className="flex items-center justify-between"><span>Memória PHP</span><span className="font-medium">{formatNumber(data.runtime.memory_usage_mb)} MB</span></div>
            <div className="flex items-center justify-between"><span>Disco livre</span><span className="font-medium">{formatNumber(data.runtime.disk_free_gb)} GB</span></div>
            <div className="flex items-center justify-between"><span>Disco total</span><span className="font-medium">{formatNumber(data.runtime.disk_total_gb)} GB</span></div>
            <div className="flex items-center justify-between"><span>Logs graváveis</span><Badge variant={data.runtime.logs_writable ? 'success' : 'destructive'}>{data.runtime.logs_writable ? 'Sim' : 'Não'}</Badge></div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle className="text-base">Saúde geral</CardTitle></CardHeader>
          <CardContent className="space-y-3 text-sm text-muted-foreground">
            <p>{data.summary.critical > 0 ? 'Existem componentes críticos exigindo atenção imediata.' : 'Nenhum componente crítico ativo no último ciclo.'}</p>
            <p>{data.summary.warning > 0 ? `${formatNumber(data.summary.warning)} checks com warning nas últimas medições.` : 'Sem warnings relevantes no último ciclo.'}</p>
            <p>Atualizado em {new Date(data.updated_at).toLocaleString('pt-BR')}.</p>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader><CardTitle className="text-base">Últimos health checks</CardTitle></CardHeader>
        <CardContent>
          <div className="overflow-x-auto rounded-md border">
            <table className="w-full min-w-[920px] text-sm">
              <thead className="bg-muted/50 text-left">
                <tr>
                  <th className="px-4 py-3 font-medium">Componente</th>
                  <th className="px-4 py-3 font-medium">Status</th>
                  <th className="px-4 py-3 font-medium">Mensagem</th>
                  <th className="px-4 py-3 font-medium">Metadata</th>
                  <th className="px-4 py-3 font-medium">Horário</th>
                </tr>
              </thead>
              <tbody>
                {data.checks.map((check: HealthCheckItem, index: number) => (
                  <HealthCheckRow key={`${check.component}-${check.checked_at ?? index}`} check={check} />
                ))}
              </tbody>
            </table>
            {data.checks.length === 0 && <p className="p-6 text-center text-sm text-muted-foreground">Nenhum health check registrado ainda.</p>}
          </div>
        </CardContent>
      </Card>
    </PageContainer>
  )
}
