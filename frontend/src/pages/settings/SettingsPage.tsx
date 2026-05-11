import { Settings } from 'lucide-react'
import { PageContainer, PageHeader } from '@/components/shared/PageHeader'
import { TableSkeleton } from '@/components/shared/Skeletons'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useSettingsData } from '@/hooks/usePhase6Data'
import { formatNumber } from '@/lib/utils'

function booleanBadge(value: boolean, trueLabel = 'Ativo', falseLabel = 'Inativo') {
  return <Badge variant={value ? 'success' : 'warning'}>{value ? trueLabel : falseLabel}</Badge>
}

export default function SettingsPage() {
  const { data, isLoading, isFetching, error } = useSettingsData()

  if (isLoading) {
    return (
      <PageContainer>
        <TableSkeleton rows={10} cols={2} />
      </PageContainer>
    )
  }

  if (error || !data) {
    return (
      <PageContainer>
        <Card>
          <CardHeader><CardTitle className="text-base text-destructive">Falha ao carregar configurações</CardTitle></CardHeader>
          <CardContent className="text-sm text-muted-foreground">{error?.message ?? 'Nenhum dado disponível.'}</CardContent>
        </Card>
      </PageContainer>
    )
  }

  return (
    <PageContainer>
      <PageHeader title="Configurações" description="Leitura consolidada da configuração global em produção">
        <Badge variant="secondary" className="gap-1">
          <Settings className="h-3.5 w-3.5" />
          {isFetching ? 'Atualizando' : 'Fase 6'}
        </Badge>
      </PageHeader>

      <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <Card>
          <CardHeader><CardTitle className="text-base">Geral</CardTitle></CardHeader>
          <CardContent className="space-y-3 text-sm">
            <div className="flex items-center justify-between"><span>App</span><span className="font-medium">{data.general.app_name}</span></div>
            <div className="flex items-center justify-between"><span>Ambiente</span><Badge variant={data.general.env === 'production' ? 'success' : 'warning'}>{data.general.env}</Badge></div>
            <div className="flex items-center justify-between gap-3"><span>Base URL</span><span className="max-w-[70%] truncate text-right font-medium">{data.general.base_url}</span></div>
            <div className="flex items-center justify-between"><span>Timezone</span><span className="font-medium">{data.general.timezone}</span></div>
            <div className="flex items-center justify-between"><span>Debug</span>{booleanBadge(data.general.debug, 'On', 'Off')}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle className="text-base">Segurança</CardTitle></CardHeader>
          <CardContent className="space-y-3 text-sm">
            <div className="flex items-center justify-between"><span>Session name</span><span className="font-medium">{data.security.session_name}</span></div>
            <div className="flex items-center justify-between"><span>CSRF TTL</span><span className="font-medium">{formatNumber(data.security.csrf_ttl)} s</span></div>
            <div className="flex items-center justify-between"><span>Sessão</span><span className="font-medium">{formatNumber(data.security.session_lifetime_seconds)} s</span></div>
            <div className="flex items-center justify-between"><span>Login obrigatório</span>{booleanBadge(data.security.login_required, 'Sim', 'Não')}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle className="text-base">Features</CardTitle></CardHeader>
          <CardContent className="space-y-3 text-sm">
            <div className="flex items-center justify-between"><span>Dias de novidade</span><span className="font-medium">{formatNumber(data.features.novidades_days)}</span></div>
            <div className="flex items-center justify-between"><span>KDS refresh</span><span className="font-medium">{formatNumber(data.features.kds_refresh_ms)} ms</span></div>
            <div className="flex items-center justify-between"><span>KDS SLA</span><span className="font-medium">{formatNumber(data.features.kds_sla_minutes)} min</span></div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle className="text-base">Integrações</CardTitle></CardHeader>
          <CardContent className="space-y-3 text-sm">
            <div className="flex items-center justify-between"><span>Redis</span>{booleanBadge(data.integrations.redis_enabled, 'On', 'Off')}</div>
            <div className="flex items-center justify-between"><span>Redis host</span><span className="font-medium">{data.integrations.redis_host}</span></div>
            <div className="flex items-center justify-between"><span>Redis port</span><span className="font-medium">{formatNumber(data.integrations.redis_port)}</span></div>
            <div className="flex items-center justify-between gap-3"><span>VAPID subject</span><span className="max-w-[70%] truncate text-right font-medium">{data.integrations.vapid_subject}</span></div>
          </CardContent>
        </Card>
      </div>
    </PageContainer>
  )
}
