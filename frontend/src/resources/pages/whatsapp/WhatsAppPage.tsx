import { useEffect, useState } from 'react'
import { MessageSquare } from 'lucide-react'
import { PageContainer, PageHeader } from '@/components/shared/PageHeader'
import { TableSkeleton } from '@/components/shared/Skeletons'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { DEBOUNCE_MS } from '@/js/lib/constants'
import { formatDate, formatNumber } from '@/js/lib/utils'
import { useDebounce } from '@/js/hooks/useDebounce'
import { useWhatsApp } from '@/js/hooks/usePhase4Data'
import { useCompanyFilterStore } from '@/js/stores/companyFilterStore'
import type { WhatsAppStatus } from '@/js/types/phase4'

function statusVariant(status: string): 'default' | 'success' | 'warning' | 'destructive' {
  if (status === 'connected') return 'success'
  if (status === 'awaiting_pairing') return 'warning'
  if (status === 'not_configured') return 'destructive'
  return 'default'
}

function statusLabel(status: string): string {
  if (status === 'connected') return 'Conectada'
  if (status === 'awaiting_pairing') return 'Aguardando pareamento'
  if (status === 'not_configured') return 'Não configurada'
  return status
}

export default function WhatsAppPage() {
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState<WhatsAppStatus | ''>('')
  const selectedCompanyId = useCompanyFilterStore((state) => state.selectedCompanyId)

  useEffect(() => {
    setPage(1)
  }, [selectedCompanyId])

  const debouncedSearch = useDebounce(search, DEBOUNCE_MS)
  const { data, isLoading, isFetching, isError, error } = useWhatsApp({
    page,
    per_page: 10,
    search: debouncedSearch,
    status,
    company_id: selectedCompanyId ?? undefined,
  })

  const stats = data?.stats
  const pagination = data?.pagination
  const items = data?.items ?? []

  if (isError) {
    return (
      <PageContainer>
        <PageHeader title="WhatsApp" description="Status das instâncias e sessões" />
        <Card>
          <CardHeader>
            <CardTitle className="text-base text-destructive">Falha ao carregar instâncias</CardTitle>
          </CardHeader>
          <CardContent className="text-sm text-muted-foreground">{error?.message ?? 'Tente novamente em instantes.'}</CardContent>
        </Card>
      </PageContainer>
    )
  }

  return (
    <PageContainer>
      <PageHeader title="WhatsApp" description="Status das instâncias e sessões">
        <Badge variant="secondary" className="gap-1">
          <MessageSquare className="h-3.5 w-3.5" />
          Operacional
        </Badge>
      </PageHeader>

      <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Total</CardTitle></CardHeader><CardContent className="text-2xl font-bold">{formatNumber(stats?.total ?? 0)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Conectadas</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-emerald-600">{formatNumber(stats?.connected ?? 0)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Aguardando</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-amber-600">{formatNumber(stats?.awaiting_pairing ?? 0)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Não configuradas</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-rose-600">{formatNumber(stats?.not_configured ?? 0)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Lojas cobertas</CardTitle></CardHeader><CardContent className="text-2xl font-bold">{formatNumber(stats?.companies_covered ?? 0)}</CardContent></Card>
      </div>

      <Card>
        <CardHeader className="pb-3"><CardTitle className="text-base">Instâncias</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 gap-3 md:grid-cols-4">
            <Input
              placeholder="Buscar por loja, label, número ou identificador"
              value={search}
              onChange={(e) => {
                setSearch(e.target.value)
                setPage(1)
              }}
              className="md:col-span-3"
            />
            <select
              value={status}
              onChange={(e) => {
                setStatus(e.target.value as WhatsAppStatus | '')
                setPage(1)
              }}
              className="h-10 rounded-md border border-input bg-background px-3 text-sm"
            >
              <option value="">Todos os status</option>
              <option value="connected">Conectada</option>
              <option value="awaiting_pairing">Aguardando pareamento</option>
              <option value="not_configured">Não configurada</option>
            </select>
          </div>

          {isLoading ? (
            <TableSkeleton rows={8} cols={5} />
          ) : (
            <div className="overflow-x-auto rounded-md border">
              <table className="w-full min-w-[980px] text-sm">
                <thead className="bg-muted/50 text-left">
                  <tr>
                    <th className="px-4 py-3 font-medium">Loja</th>
                    <th className="px-4 py-3 font-medium">Instância</th>
                    <th className="px-4 py-3 font-medium">Número</th>
                    <th className="px-4 py-3 font-medium">Status</th>
                    <th className="px-4 py-3 font-medium">Criada em</th>
                  </tr>
                </thead>
                <tbody>
                  {items.map((item) => (
                    <tr key={item.id} className="border-t">
                      <td className="px-4 py-3">{item.company_name}</td>
                      <td className="px-4 py-3">{item.label || item.instance_identifier || '-'}</td>
                      <td className="px-4 py-3 text-muted-foreground">{item.number || '-'}</td>
                      <td className="px-4 py-3">
                        <Badge variant={statusVariant(item.status)}>{statusLabel(item.status)}</Badge>
                      </td>
                      <td className="px-4 py-3 text-muted-foreground">{formatDate(item.created_at)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
              {items.length === 0 && (
                <p className="p-6 text-center text-sm text-muted-foreground">Nenhuma instância encontrada para os filtros atuais.</p>
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
