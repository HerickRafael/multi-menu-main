import { useState } from 'react'
import { Flag } from 'lucide-react'
import { PageContainer, PageHeader } from '@/components/shared/PageHeader'
import { TableSkeleton } from '@/components/shared/Skeletons'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { useDebounce } from '@/js/hooks/useDebounce'
import { useFeatureFlagsData, useToggleFeatureFlagMutation } from '@/js/hooks/usePhase5Data'
import { DEBOUNCE_MS } from '@/js/lib/constants'
import { formatDate, formatNumber, truncate } from '@/js/lib/utils'

export default function FeatureFlagsPage() {
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')
  const [companyId, setCompanyId] = useState('1')
  const [active, setActive] = useState('')

  const debouncedSearch = useDebounce(search, DEBOUNCE_MS)
  const { data, isLoading, isFetching } = useFeatureFlagsData({
    page,
    per_page: 10,
    company_id: Number(companyId || 1),
    search: debouncedSearch,
    active,
  })
  const toggleFlagMutation = useToggleFeatureFlagMutation()
  const pagination = data?.pagination

  const handleToggle = (featureFlagId: number, enabled: boolean) => {
    toggleFlagMutation.mutate({
      company_id: Number(companyId || data?.selected_company_id || 1),
      feature_flag_id: featureFlagId,
      enabled,
    })
  }

  return (
    <PageContainer>
      <PageHeader title="Feature Flags" description="Controle fino de rollout por tenant">
        <Badge variant="secondary" className="gap-1">
          <Flag className="h-3.5 w-3.5" />
          {isFetching ? 'Atualizando' : 'Fase 5'}
        </Badge>
      </PageHeader>

      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Flags</CardTitle></CardHeader><CardContent className="text-2xl font-bold">{formatNumber(data?.stats.total ?? 0)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Ativas globalmente</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-emerald-600">{formatNumber(data?.stats.active ?? 0)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Habilitadas no tenant</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-blue-600">{formatNumber(data?.stats.enabled_for_company ?? 0)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Customizadas</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-amber-600">{formatNumber(data?.stats.customized ?? 0)}</CardContent></Card>
      </div>

      <Card>
        <CardHeader className="pb-3"><CardTitle className="text-base">Flags por tenant</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 gap-3 md:grid-cols-5">
            <select
              value={companyId}
              onChange={(e) => {
                setCompanyId(e.target.value)
                setPage(1)
              }}
              className="h-10 rounded-md border border-input bg-background px-3 text-sm md:col-span-1"
            >
              {(data?.companies ?? []).map((company) => (
                <option key={company.id} value={company.id}>{company.name}</option>
              ))}
            </select>
            <Input
              placeholder="Buscar por nome, chave ou descrição"
              value={search}
              onChange={(e) => {
                setSearch(e.target.value)
                setPage(1)
              }}
              className="md:col-span-3"
            />
            <select
              value={active}
              onChange={(e) => {
                setActive(e.target.value)
                setPage(1)
              }}
              className="h-10 rounded-md border border-input bg-background px-3 text-sm"
            >
              <option value="">Ativas e inativas</option>
              <option value="1">Somente ativas</option>
              <option value="0">Somente inativas</option>
            </select>
          </div>

          {isLoading ? (
            <TableSkeleton rows={8} cols={7} />
          ) : (
            <div className="overflow-x-auto rounded-md border">
              <table className="w-full min-w-[1040px] text-sm">
                <thead className="bg-muted/50 text-left">
                  <tr>
                    <th className="px-4 py-3 font-medium">Flag</th>
                    <th className="px-4 py-3 font-medium">Descrição</th>
                    <th className="px-4 py-3 font-medium">Default</th>
                    <th className="px-4 py-3 font-medium">Tenant</th>
                    <th className="px-4 py-3 font-medium">Global</th>
                    <th className="px-4 py-3 font-medium">Customizada</th>
                    <th className="px-4 py-3 font-medium">Atualizada</th>
                    <th className="px-4 py-3 font-medium">Ação</th>
                  </tr>
                </thead>
                <tbody>
                  {(data?.items ?? []).map((item) => (
                    <tr key={item.id} className="border-t">
                      <td className="px-4 py-3">
                        <div className="font-medium">{item.name}</div>
                        <div className="text-xs text-muted-foreground">{item.flag_key}</div>
                      </td>
                      <td className="px-4 py-3 text-muted-foreground">{truncate(item.description || '-', 70)}</td>
                      <td className="px-4 py-3"><Badge variant={item.default_enabled ? 'success' : 'warning'}>{item.default_enabled ? 'On' : 'Off'}</Badge></td>
                      <td className="px-4 py-3"><Badge variant={item.company_enabled ? 'success' : 'warning'}>{item.company_enabled ? 'On' : 'Off'}</Badge></td>
                      <td className="px-4 py-3"><Badge variant={item.is_active ? 'info' : 'outline'}>{item.is_active ? 'Ativa' : 'Inativa'}</Badge></td>
                      <td className="px-4 py-3"><Badge variant={item.customized ? 'warning' : 'outline'}>{item.customized ? 'Sim' : 'Não'}</Badge></td>
                      <td className="px-4 py-3 text-muted-foreground">{formatDate(item.updated_at)}</td>
                      <td className="px-4 py-3">
                        <Button
                          variant={item.company_enabled ? 'outline' : 'default'}
                          size="sm"
                          disabled={toggleFlagMutation.isPending}
                          onClick={() => handleToggle(item.id, !item.company_enabled)}
                        >
                          {item.company_enabled ? 'Desativar' : 'Ativar'}
                        </Button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
              {(data?.items.length ?? 0) === 0 && (
                <p className="p-6 text-center text-sm text-muted-foreground">Nenhuma feature flag encontrada para os filtros atuais.</p>
              )}
            </div>
          )}

          <div className="flex items-center justify-between">
            <p className="text-xs text-muted-foreground">
              {isFetching ? 'Atualizando...' : `Página ${pagination?.page ?? 1} de ${pagination?.total_pages ?? 1}`}
            </p>
            <div className="flex gap-2">
              <Button variant="outline" size="sm" disabled={!pagination?.has_prev} onClick={() => setPage((current) => Math.max(1, current - 1))}>Anterior</Button>
              <Button variant="outline" size="sm" disabled={!pagination?.has_next} onClick={() => setPage((current) => current + 1)}>Próxima</Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </PageContainer>
  )
}
