'use client'

import { useState } from 'react'
import { AlertCircle, CheckCircle, Clock, Webhook } from 'lucide-react'
import { PageContainer, PageHeader } from '@/components/shared/PageHeader'
import { TableSkeleton } from '@/components/shared/Skeletons'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useWebhooks } from '@/js/hooks/usePhase4Data'
import { formatDate, formatNumber, truncate } from '@/js/lib/utils'
import type { WebhookListFilters } from '@/js/types/phase4'

const statusColors = {
  pending: 'warning',
  success: 'default',
  failed: 'destructive',
} as const

const statusIcons = {
  pending: <Clock className="h-4 w-4" />,
  success: <CheckCircle className="h-4 w-4" />,
  failed: <AlertCircle className="h-4 w-4" />,
} as const

export default function WebhooksPage() {
  const [page, setPage] = useState(1)
  const [status, setStatus] = useState<'' | 'pending' | 'success' | 'failed'>('')

  const filters: WebhookListFilters = { page, per_page: 15, status }
  const { data, isLoading, isFetching, error } = useWebhooks(filters)

  if (isLoading) {
    return (
      <PageContainer>
        <TableSkeleton rows={10} cols={7} />
      </PageContainer>
    )
  }

  if (error) {
    return (
      <PageContainer>
        <Card>
          <CardHeader>
            <CardTitle className="text-base text-destructive">Falha ao carregar webhooks</CardTitle>
          </CardHeader>
          <CardContent className="text-sm text-muted-foreground">{error.message}</CardContent>
        </Card>
      </PageContainer>
    )
  }

  const { items, pagination, stats } = data || {
    items: [],
    pagination: { page: 1, per_page: 15, total: 0, total_pages: 1, has_next: false, has_prev: false },
    stats: { total: 0, pending: 0, success: 0, failed: 0 },
  }

  return (
    <PageContainer>
      <PageHeader title="Webhooks" description="Entrega, retries e saúde operacional dos callbacks">
        <Badge variant="secondary" className="gap-1">
          <Webhook className="h-3.5 w-3.5" />
          {isFetching ? 'Atualizando' : 'Fase 4'}
        </Badge>
      </PageHeader>

      <div className="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm font-medium">Total</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatNumber(stats.total)}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm font-medium flex items-center gap-2">
              <Clock className="h-4 w-4" />
              Pendente
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatNumber(stats.pending)}</div>
            <Badge variant="warning" className="mt-2">
              {((stats.pending / (stats.total || 1)) * 100).toFixed(0)}%
            </Badge>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm font-medium flex items-center gap-2">
              <CheckCircle className="h-4 w-4" />
              Sucesso
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatNumber(stats.success)}</div>
            <Badge variant="success" className="mt-2">
              {((stats.success / (stats.total || 1)) * 100).toFixed(0)}%
            </Badge>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm font-medium flex items-center gap-2">
              <AlertCircle className="h-4 w-4" />
              Falha
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatNumber(stats.failed)}</div>
            <Badge variant="destructive" className="mt-2">
              {((stats.failed / (stats.total || 1)) * 100).toFixed(0)}%
            </Badge>
          </CardContent>
        </Card>
      </div>

      <div className="flex gap-3 flex-col md:flex-row md:items-center md:justify-between">
        <select
          value={status}
          onChange={(e) => {
            setStatus(e.target.value as '' | 'pending' | 'success' | 'failed')
            setPage(1)
          }}
          className="h-10 rounded-md border border-input bg-background px-3 text-sm md:w-[220px]"
        >
          <option value="">Todos os status</option>
          <option value="pending">Pendente</option>
          <option value="success">Sucesso</option>
          <option value="failed">Falha</option>
        </select>
        <p className="text-sm text-muted-foreground">
          Taxa de sucesso: {((stats.success / (stats.total || 1)) * 100).toFixed(0)}%
        </p>
      </div>

      <Card>
        <CardContent className="p-0">
          {items.length === 0 ? (
            <div className="p-8 text-center text-muted-foreground">
              Nenhum webhook encontrado
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full min-w-[920px] text-sm">
                <thead className="bg-muted/50 text-left">
                  <tr>
                    <th className="px-4 py-3 font-medium">ID</th>
                    <th className="px-4 py-3 font-medium">Evento</th>
                    <th className="px-4 py-3 font-medium">URL</th>
                    <th className="px-4 py-3 font-medium">Status</th>
                    <th className="px-4 py-3 font-medium">Código</th>
                    <th className="px-4 py-3 font-medium">Tentativas</th>
                    <th className="px-4 py-3 font-medium">Criado em</th>
                  </tr>
                </thead>
                <tbody>
                  {items.map((webhook) => (
                    <tr key={webhook.id} className="border-t">
                      <td className="px-4 py-3 font-mono text-sm">{webhook.id}</td>
                      <td className="px-4 py-3">
                        <Badge variant="secondary">{webhook.event_type}</Badge>
                      </td>
                      <td className="px-4 py-3 text-xs text-muted-foreground">{truncate(webhook.webhook_url, 64)}</td>
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-2">
                          {statusIcons[webhook.status]}
                          <Badge variant={statusColors[webhook.status]}>
                            {webhook.status === 'pending' && 'Pendente'}
                            {webhook.status === 'success' && 'Sucesso'}
                            {webhook.status === 'failed' && 'Falha'}
                          </Badge>
                        </div>
                      </td>
                      <td className="px-4 py-3 text-sm">
                        {webhook.status_code ? (
                          <Badge
                            variant={
                              webhook.status_code >= 200 && webhook.status_code < 300
                                ? 'success'
                                : 'destructive'
                            }
                          >
                            {webhook.status_code}
                          </Badge>
                        ) : (
                          '-'
                        )}
                      </td>
                      <td className="px-4 py-3 text-sm">{formatNumber(webhook.retry_count)}</td>
                      <td className="px-4 py-3 text-sm text-muted-foreground">{formatDate(webhook.created_at)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>

      <div className="flex items-center justify-between">
        <p className="text-sm text-muted-foreground">
          Página {pagination.page} de {pagination.total_pages} ({pagination.total} total)
        </p>
        <div className="flex gap-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => setPage((current) => Math.max(1, current - 1))}
            disabled={!pagination.has_prev}
          >
            Anterior
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={() => setPage((current) => current + 1)}
            disabled={!pagination.has_next}
          >
            Próxima
          </Button>
        </div>
      </div>

      {stats.total === 0 && (
        <Card>
          <CardContent className="py-4 text-sm text-muted-foreground">
            Nenhum webhook foi entregue ainda. Configure callbacks e retries para começar a monitorar a fila.
          </CardContent>
        </Card>
      )}
    </PageContainer>
  )
}
