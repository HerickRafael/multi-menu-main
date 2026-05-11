import { useState } from 'react'
import { ShieldCheck } from 'lucide-react'
import { PageContainer, PageHeader } from '@/components/shared/PageHeader'
import { TableSkeleton } from '@/components/shared/Skeletons'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { useDebounce } from '@/hooks/useDebounce'
import { useAuditData } from '@/hooks/usePhase5Data'
import { DEBOUNCE_MS } from '@/lib/constants'
import { formatDate, formatNumber, truncate } from '@/lib/utils'

function actionVariant(action: string): 'default' | 'success' | 'warning' | 'destructive' | 'info' {
  if (action === 'create' || action === 'activate') return 'success'
  if (action === 'update' || action === 'impersonate') return 'info'
  if (action === 'suspend' || action === 'retry') return 'warning'
  if (action === 'delete' || action === 'logout') return 'destructive'
  return 'default'
}

export default function AuditPage() {
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')
  const [module, setModule] = useState('')
  const [action, setAction] = useState('')

  const debouncedSearch = useDebounce(search, DEBOUNCE_MS)
  const { data, isLoading, isFetching } = useAuditData({
    page,
    per_page: 10,
    search: debouncedSearch,
    module,
    action,
  })

  const pagination = data?.pagination

  return (
    <PageContainer>
      <PageHeader title="Auditoria" description="Trilha global de ações críticas do super admin">
        <Badge variant="secondary" className="gap-1">
          <ShieldCheck className="h-3.5 w-3.5" />
          {isFetching ? 'Atualizando' : 'Fase 5'}
        </Badge>
      </PageHeader>

      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Eventos</CardTitle></CardHeader><CardContent className="text-2xl font-bold">{formatNumber(data?.stats.total ?? 0)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Últimas 24h</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-blue-600">{formatNumber(data?.stats.last_24h ?? 0)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Módulos</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-emerald-600">{formatNumber(data?.stats.modules ?? 0)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Admins</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-amber-600">{formatNumber(data?.stats.admins ?? 0)}</CardContent></Card>
      </div>

      <Card>
        <CardHeader className="pb-3"><CardTitle className="text-base">Eventos recentes</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 gap-3 md:grid-cols-5">
            <Input
              placeholder="Buscar por descrição, módulo, admin ou loja"
              value={search}
              onChange={(e) => {
                setSearch(e.target.value)
                setPage(1)
              }}
              className="md:col-span-3"
            />
            <select
              value={module}
              onChange={(e) => {
                setModule(e.target.value)
                setPage(1)
              }}
              className="h-10 rounded-md border border-input bg-background px-3 text-sm"
            >
              <option value="">Todos os módulos</option>
              <option value="stores">Stores</option>
              <option value="orders">Orders</option>
              <option value="users">Users</option>
              <option value="webhooks">Webhooks</option>
              <option value="queues">Queues</option>
              <option value="flags">Flags</option>
            </select>
            <select
              value={action}
              onChange={(e) => {
                setAction(e.target.value)
                setPage(1)
              }}
              className="h-10 rounded-md border border-input bg-background px-3 text-sm"
            >
              <option value="">Todas as ações</option>
              <option value="create">Create</option>
              <option value="update">Update</option>
              <option value="delete">Delete</option>
              <option value="activate">Activate</option>
              <option value="suspend">Suspend</option>
              <option value="impersonate">Impersonate</option>
            </select>
          </div>

          {isLoading ? (
            <TableSkeleton rows={8} cols={7} />
          ) : (
            <div className="overflow-x-auto rounded-md border">
              <table className="w-full min-w-[980px] text-sm">
                <thead className="bg-muted/50 text-left">
                  <tr>
                    <th className="px-4 py-3 font-medium">Admin</th>
                    <th className="px-4 py-3 font-medium">Módulo</th>
                    <th className="px-4 py-3 font-medium">Ação</th>
                    <th className="px-4 py-3 font-medium">Descrição</th>
                    <th className="px-4 py-3 font-medium">Loja</th>
                    <th className="px-4 py-3 font-medium">IP</th>
                    <th className="px-4 py-3 font-medium">Data</th>
                  </tr>
                </thead>
                <tbody>
                  {(data?.items ?? []).map((item) => (
                    <tr key={item.id} className="border-t">
                      <td className="px-4 py-3">
                        <div className="font-medium">{item.admin_name}</div>
                        <div className="text-xs text-muted-foreground">{item.admin_email}</div>
                      </td>
                      <td className="px-4 py-3"><Badge variant="outline">{item.module}</Badge></td>
                      <td className="px-4 py-3"><Badge variant={actionVariant(item.action)}>{item.action}</Badge></td>
                      <td className="px-4 py-3 text-muted-foreground">{truncate(item.description || '-', 72)}</td>
                      <td className="px-4 py-3">{item.company_name || 'Global'}</td>
                      <td className="px-4 py-3 text-muted-foreground">{item.ip_address || '-'}</td>
                      <td className="px-4 py-3 text-muted-foreground">{formatDate(item.created_at)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
              {(data?.items.length ?? 0) === 0 && (
                <p className="p-6 text-center text-sm text-muted-foreground">Nenhum evento encontrado para os filtros atuais.</p>
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
