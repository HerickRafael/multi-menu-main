import { memo, useState } from 'react'
import { AlertCircle, AlertTriangle, Info, Activity } from 'lucide-react'
import { PageContainer, PageHeader } from '@/components/shared/PageHeader'
import { TableSkeleton } from '@/components/shared/Skeletons'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { DEBOUNCE_MS } from '@/js/lib/constants'
import { formatDate, formatNumber } from '@/js/lib/utils'
import { useDebounce } from '@/js/hooks/useDebounce'
import { useLogsData } from '@/js/hooks/usePhase3Data'
import type { LogItem } from '@/js/types/phase3'

function levelVariant(level: string): 'default' | 'success' | 'warning' | 'destructive' | 'secondary' {
  if (level === 'info') return 'default'
  if (level === 'warning') return 'warning'
  if (level === 'error' || level === 'critical') return 'destructive'
  return 'secondary'
}

function levelIcon(level: string) {
  switch (level) {
    case 'info': return <Info className="h-3.5 w-3.5" />
    case 'warning': return <AlertTriangle className="h-3.5 w-3.5" />
    case 'error': return <AlertCircle className="h-3.5 w-3.5" />
    case 'critical': return <AlertCircle className="h-3.5 w-3.5" />
    default: return <Activity className="h-3.5 w-3.5" />
  }
}

const LogRow = memo(function LogRow({ log }: { log: LogItem }) {
  return (
    <tr className="border-t hover:bg-muted/30">
      <td className="px-4 py-3">
        <Badge variant={levelVariant(log.level)} className="gap-1">
          {levelIcon(log.level)}
          {log.level.toUpperCase()}
        </Badge>
      </td>
      <td className="px-4 py-3 font-medium">{log.module}</td>
      <td className="px-4 py-3 text-sm">{log.message}</td>
      <td className="px-4 py-3 text-muted-foreground text-xs whitespace-nowrap">{formatDate(log.logged_at)}</td>
    </tr>
  )
})

export default function LogsPage() {
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')
  const [level, setLevel] = useState('')

  const debouncedSearch = useDebounce(search, DEBOUNCE_MS)
  const { data, isLoading, isFetching } = useLogsData({
    page,
    per_page: 20,
    search: debouncedSearch,
    level,
  })

  const stats = data?.stats ?? {
    total: 0,
    debug: 0,
    info: 0,
    warning: 0,
    error: 0,
    critical: 0,
  }
  const items = data?.items ?? []
  const pagination = data?.pagination

  return (
    <PageContainer>
      <PageHeader title="Logs" description="Visualizador centralizado de logs do sistema">
        <Badge variant="secondary" className="gap-1">
          <Activity className="h-3.5 w-3.5" />
          Fase 3
        </Badge>
      </PageHeader>

      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-4">
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Total</CardTitle></CardHeader><CardContent className="text-2xl font-bold">{formatNumber(stats.total)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Erros</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-destructive">{formatNumber(stats.error)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Avisos</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-warning">{formatNumber(stats.warning)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Críticos</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-red-600">{formatNumber(stats.critical)}</CardContent></Card>
      </div>

      <Card>
        <CardHeader className="pb-3"><CardTitle className="text-base">Logs Recentes</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
            <Input
              placeholder="Buscar por módulo ou mensagem"
              value={search}
              onChange={(e) => {
                setSearch(e.target.value)
                setPage(1)
              }}
              className="md:col-span-2"
            />
            <select
              value={level}
              onChange={(e) => {
                setLevel(e.target.value)
                setPage(1)
              }}
              className="h-10 rounded-md border border-input bg-background px-3 text-sm"
            >
              <option value="">Todos os níveis</option>
              <option value="debug">Debug</option>
              <option value="info">Info</option>
              <option value="warning">Aviso</option>
              <option value="error">Erro</option>
              <option value="critical">Crítico</option>
            </select>
          </div>

          {isLoading ? (
            <TableSkeleton rows={8} cols={4} />
          ) : (
            <div className="overflow-x-auto rounded-md border">
              <table className="w-full min-w-[900px] text-sm">
                <thead className="bg-muted/50 text-left">
                  <tr>
                    <th className="px-4 py-3 font-medium">Nível</th>
                    <th className="px-4 py-3 font-medium">Módulo</th>
                    <th className="px-4 py-3 font-medium">Mensagem</th>
                    <th className="px-4 py-3 font-medium">Timestamp</th>
                  </tr>
                </thead>
                <tbody>
                  {items.map((log) => (
                    <LogRow key={log.id} log={log} />
                  ))}
                </tbody>
              </table>
              {items.length === 0 && (
                <p className="p-6 text-center text-sm text-muted-foreground">Nenhum log encontrado para os filtros atuais.</p>
              )}
            </div>
          )}

          <div className="flex items-center justify-between">
            <p className="text-xs text-muted-foreground">
              {isFetching ? 'Atualizando...' : `Página ${pagination?.page ?? 1} de ${pagination?.total_pages ?? 1}`}
            </p>
            <div className="flex gap-2">
              <Button variant="outline" size="sm" disabled={!pagination?.has_prev} onClick={() => setPage((p) => Math.max(1, p - 1))}>Anterior</Button>
              <Button variant="outline" size="sm" disabled={!pagination?.has_next} onClick={() => setPage((p) => p + 1)}>Próxima</Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </PageContainer>
  )
}
