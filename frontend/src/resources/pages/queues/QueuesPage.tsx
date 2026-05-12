import { useState, memo } from 'react'
import { AlertCircle, Clock3, ListFilter, RotateCcw } from 'lucide-react'
import { PageContainer, PageHeader } from '@/components/shared/PageHeader'
import { TableSkeleton } from '@/components/shared/Skeletons'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { useDebounce } from '@/js/hooks/useDebounce'
import { useQueues, useRetryQueueJobMutation } from '@/js/hooks/usePhase4Data'
import { DEBOUNCE_MS } from '@/js/lib/constants'
import { formatDate, formatNumber, truncate } from '@/js/lib/utils'
import type { QueueData, QueueListFilters, QueueStatus } from '@/js/types/phase4'

function statusVariant(status: QueueStatus): 'default' | 'success' | 'warning' | 'destructive' | 'secondary' {
  if (status === 'pending') return 'warning'
  if (status === 'processing') return 'default'
  if (status === 'done') return 'success'
  if (status === 'failed' || status === 'dead') return 'destructive'
  return 'secondary'
}

function statusLabel(status: QueueStatus): string {
  if (status === 'pending') return 'Pendente'
  if (status === 'processing') return 'Processando'
  if (status === 'done') return 'Concluído'
  if (status === 'failed') return 'Falhou'
  if (status === 'retrying') return 'Reprocessando'
  if (status === 'dead') return 'Dead letter'
  return status
}

function formatMaybeDate(value: string | null): string {
  return value ? formatDate(value) : '-'
}

const QueueRow = memo(function QueueRow({
  job,
  onRetry,
  isRetrying,
}: {
  job: QueueData['items'][number]
  onRetry: (jobId: number) => void
  isRetrying: boolean
}) {
  const canRetry = job.status === 'failed' || job.status === 'dead'

  return (
    <tr className="border-t hover:bg-muted/30">
      <td className="px-4 py-3 font-mono text-sm">#{job.id}</td>
      <td className="px-4 py-3 text-sm text-muted-foreground whitespace-nowrap">{formatDate(job.created_at)}</td>
      <td className="px-4 py-3">
        <Badge variant="secondary">{job.job_type}</Badge>
      </td>
      <td className="px-4 py-3">
        <Badge variant={statusVariant(job.status)}>{statusLabel(job.status)}</Badge>
      </td>
      <td className="px-4 py-3 text-sm font-medium">{job.priority}</td>
      <td className="px-4 py-3 text-sm text-muted-foreground">
        {formatNumber(job.attempts)}/{formatNumber(job.max_attempts)}
      </td>
      <td className="px-4 py-3 text-sm text-muted-foreground">{job.company_id ?? '-'}</td>
      <td className="px-4 py-3 text-sm text-muted-foreground">{formatMaybeDate(job.available_at)}</td>
      <td className="px-4 py-3 text-sm text-muted-foreground">{truncate(job.last_error ?? '-', 64)}</td>
      <td className="px-4 py-3 text-right">
        <Button
          variant="outline"
          size="sm"
          onClick={() => onRetry(job.id)}
          disabled={!canRetry || isRetrying}
        >
          <RotateCcw className="mr-2 h-3.5 w-3.5" />
          Reprocessar
        </Button>
      </td>
    </tr>
  )
})

export default function QueuesPage() {
  const [page, setPage] = useState(1)
  const [status, setStatus] = useState<QueueStatus | ''>('')
  const [jobType, setJobType] = useState('')
  const [companyId, setCompanyId] = useState('')

  const debouncedJobType = useDebounce(jobType, DEBOUNCE_MS)
  const debouncedCompanyId = useDebounce(companyId, DEBOUNCE_MS)

  const filters: QueueListFilters = {
    page,
    per_page: 50,
    status,
    job_type: debouncedJobType,
    company_id: debouncedCompanyId,
  }

  const { data, isLoading, isFetching, error } = useQueues(filters)
  const retryMutation = useRetryQueueJobMutation()

  if (isLoading) {
    return (
      <PageContainer>
        <TableSkeleton rows={10} cols={10} />
      </PageContainer>
    )
  }

  if (error) {
    return (
      <PageContainer>
        <Card>
          <CardHeader>
            <CardTitle className="text-base text-destructive">Falha ao carregar filas</CardTitle>
          </CardHeader>
          <CardContent className="text-sm text-muted-foreground">{error.message}</CardContent>
        </Card>
      </PageContainer>
    )
  }

  const { items, pagination, stats } = data ?? {
    items: [],
    pagination: { page: 1, per_page: 50, total: 0, total_pages: 1, has_next: false, has_prev: false },
    stats: { total: 0, pending: 0, processing: 0, done: 0, failed: 0, dead: 0 },
  }

  return (
    <PageContainer>
      <PageHeader title="Filas" description="Jobs, workers e processamento assíncrono">
        <Badge variant="secondary" className="gap-1">
          <ListFilter className="h-3.5 w-3.5" />
          {isFetching ? 'Atualizando' : 'PLANO 4'}
        </Badge>
      </PageHeader>

      <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <Card>
          <CardHeader className="pb-2"><CardTitle className="text-sm">Total</CardTitle></CardHeader>
          <CardContent className="text-2xl font-bold">{formatNumber(stats.total)}</CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2"><CardTitle className="text-sm flex items-center gap-2"><Clock3 className="h-4 w-4" />Pendentes</CardTitle></CardHeader>
          <CardContent className="text-2xl font-bold text-amber-600">{formatNumber(stats.pending)}</CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2"><CardTitle className="text-sm">Processando</CardTitle></CardHeader>
          <CardContent className="text-2xl font-bold text-blue-600">{formatNumber(stats.processing)}</CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2"><CardTitle className="text-sm">Concluídas</CardTitle></CardHeader>
          <CardContent className="text-2xl font-bold text-emerald-600">{formatNumber(stats.done)}</CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2"><CardTitle className="text-sm flex items-center gap-2"><AlertCircle className="h-4 w-4" />Falhas/Dead</CardTitle></CardHeader>
          <CardContent className="text-2xl font-bold text-rose-600">{formatNumber(stats.failed + stats.dead)}</CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="text-base">Filtros</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 gap-3 md:grid-cols-4">
            <select
              value={status}
              onChange={(e) => {
                setStatus(e.target.value as QueueStatus | '')
                setPage(1)
              }}
              className="h-10 rounded-md border border-input bg-background px-3 text-sm"
            >
              <option value="">Todos os status</option>
              <option value="pending">Pendente</option>
              <option value="processing">Processando</option>
              <option value="done">Concluída</option>
              <option value="failed">Falhou</option>
              <option value="retrying">Reprocessando</option>
              <option value="dead">Dead letter</option>
            </select>
            <Input
              placeholder="Filtrar por tipo de job"
              value={jobType}
              onChange={(e) => {
                setJobType(e.target.value)
                setPage(1)
              }}
            />
            <Input
              placeholder="Filtrar por empresa (ID)"
              value={companyId}
              onChange={(e) => {
                setCompanyId(e.target.value)
                setPage(1)
              }}
              inputMode="numeric"
            />
            <div className="flex items-center text-sm text-muted-foreground md:justify-end">
              {isFetching ? 'Atualizando dados...' : `Página ${pagination.page} de ${pagination.total_pages}`}
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent className="p-0">
          {items.length === 0 ? (
            <div className="p-8 text-center text-muted-foreground">
              Nenhum job encontrado para os filtros atuais.
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full min-w-[1240px] text-sm">
                <thead className="bg-muted/50 text-left">
                  <tr>
                    <th className="px-4 py-3 font-medium">ID</th>
                    <th className="px-4 py-3 font-medium">Criado em</th>
                    <th className="px-4 py-3 font-medium">Tipo</th>
                    <th className="px-4 py-3 font-medium">Status</th>
                    <th className="px-4 py-3 font-medium">Prioridade</th>
                    <th className="px-4 py-3 font-medium">Tentativas</th>
                    <th className="px-4 py-3 font-medium">Empresa</th>
                    <th className="px-4 py-3 font-medium">Disponível em</th>
                    <th className="px-4 py-3 font-medium">Último erro</th>
                    <th className="px-4 py-3 font-medium text-right">Ação</th>
                  </tr>
                </thead>
                <tbody>
                  {items.map((job) => (
                    <QueueRow
                      key={job.id}
                      job={job}
                      isRetrying={retryMutation.isPending}
                      onRetry={(jobId) => retryMutation.mutate(jobId)}
                    />
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>

      <div className="flex items-center justify-between">
        <p className="text-xs text-muted-foreground">
          {isFetching ? 'Atualizando...' : `Página ${pagination.page} de ${pagination.total_pages} (${formatNumber(pagination.total)} jobs)`}
        </p>
        <div className="flex gap-2">
          <Button variant="outline" size="sm" disabled={!pagination.has_prev} onClick={() => setPage((current) => Math.max(1, current - 1))}>
            Anterior
          </Button>
          <Button variant="outline" size="sm" disabled={!pagination.has_next} onClick={() => setPage((current) => current + 1)}>
            Próxima
          </Button>
        </div>
      </div>

      <div className="rounded-lg border border-dashed bg-muted/20 p-4 text-sm text-muted-foreground">
        Jobs com estado <span className="font-medium text-foreground">failed</span> ou <span className="font-medium text-foreground">dead</span> podem ser reenfileirados manualmente.
      </div>
    </PageContainer>
  )
}
