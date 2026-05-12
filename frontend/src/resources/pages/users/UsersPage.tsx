import { useEffect, useState } from 'react'
import { Users } from 'lucide-react'
import { PageContainer, PageHeader } from '@/components/shared/PageHeader'
import { TableSkeleton } from '@/components/shared/Skeletons'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { DEBOUNCE_MS } from '@/js/lib/constants'
import { formatDate, formatNumber } from '@/js/lib/utils'
import { useDebounce } from '@/js/hooks/useDebounce'
import { useUsersData } from '@/js/hooks/usePhase3Data'
import { useCompanyFilterStore } from '@/js/stores/companyFilterStore'

function roleVariant(role: string): 'default' | 'info' | 'warning' | 'success' {
  if (role === 'root') return 'warning'
  if (role === 'owner') return 'info'
  if (role === 'staff') return 'success'
  return 'default'
}

export default function UsersPage() {
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')
  const [role, setRole] = useState('')
  const [active, setActive] = useState('')
  const selectedCompanyId = useCompanyFilterStore((state) => state.selectedCompanyId)

  useEffect(() => {
    setPage(1)
  }, [selectedCompanyId])

  const debouncedSearch = useDebounce(search, DEBOUNCE_MS)
  const { data, isLoading, isFetching, isError, error } = useUsersData({
    page,
    per_page: 10,
    search: debouncedSearch,
    company_id: selectedCompanyId ?? undefined,
    role,
    active,
  })

  const pagination = data?.pagination

  if (isError) {
    return (
      <PageContainer>
        <PageHeader title="Usuários" description="Gestão de usuários da plataforma" />
        <Card>
          <CardHeader>
            <CardTitle className="text-base text-destructive">Falha ao carregar usuários</CardTitle>
          </CardHeader>
          <CardContent className="text-sm text-muted-foreground">{error?.message ?? 'Tente novamente em instantes.'}</CardContent>
        </Card>
      </PageContainer>
    )
  }

  return (
    <PageContainer>
      <PageHeader title="Usuários" description="Gestão de usuários da plataforma">
        <Badge variant="secondary" className="gap-1">
          <Users className="h-3.5 w-3.5" />
          Fase 3
        </Badge>
      </PageHeader>

      <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Total</CardTitle></CardHeader><CardContent className="text-2xl font-bold">{formatNumber(data?.stats.total ?? 0)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Ativos</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-emerald-600">{formatNumber(data?.stats.active ?? 0)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Inativos</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-amber-600">{formatNumber(data?.stats.inactive ?? 0)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Root</CardTitle></CardHeader><CardContent className="text-2xl font-bold">{formatNumber(data?.stats.root ?? 0)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Owner</CardTitle></CardHeader><CardContent className="text-2xl font-bold">{formatNumber(data?.stats.owner ?? 0)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Staff</CardTitle></CardHeader><CardContent className="text-2xl font-bold">{formatNumber(data?.stats.staff ?? 0)}</CardContent></Card>
      </div>

      <Card>
        <CardHeader className="pb-3"><CardTitle className="text-base">Lista de Usuários</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 gap-3 md:grid-cols-5">
            <Input
              placeholder="Buscar por nome, e-mail ou loja"
              value={search}
              onChange={(e) => {
                setSearch(e.target.value)
                setPage(1)
              }}
              className="md:col-span-3"
            />
            <select
              value={role}
              onChange={(e) => {
                setRole(e.target.value)
                setPage(1)
              }}
              className="h-10 rounded-md border border-input bg-background px-3 text-sm"
            >
              <option value="">Todos os papéis</option>
              <option value="root">Root</option>
              <option value="owner">Owner</option>
              <option value="staff">Staff</option>
            </select>
            <select
              value={active}
              onChange={(e) => {
                setActive(e.target.value)
                setPage(1)
              }}
              className="h-10 rounded-md border border-input bg-background px-3 text-sm"
            >
              <option value="">Todos os status</option>
              <option value="1">Ativos</option>
              <option value="0">Inativos</option>
            </select>
          </div>

          {isLoading ? (
            <TableSkeleton rows={8} cols={6} />
          ) : (
            <div className="overflow-x-auto rounded-md border">
              <table className="w-full min-w-[850px] text-sm">
                <thead className="bg-muted/50 text-left">
                  <tr>
                    <th className="px-4 py-3 font-medium">Nome</th>
                    <th className="px-4 py-3 font-medium">E-mail</th>
                    <th className="px-4 py-3 font-medium">Papel</th>
                    <th className="px-4 py-3 font-medium">Status</th>
                    <th className="px-4 py-3 font-medium">Loja</th>
                    <th className="px-4 py-3 font-medium">Criado em</th>
                  </tr>
                </thead>
                <tbody>
                  {(data?.items ?? []).map((user) => (
                    <tr key={user.id} className="border-t">
                      <td className="px-4 py-3 font-medium">{user.name}</td>
                      <td className="px-4 py-3 text-muted-foreground">{user.email}</td>
                      <td className="px-4 py-3"><Badge variant={roleVariant(user.role)}>{user.role}</Badge></td>
                      <td className="px-4 py-3"><Badge variant={user.active ? 'success' : 'warning'}>{user.active ? 'Ativo' : 'Inativo'}</Badge></td>
                      <td className="px-4 py-3">{user.company_name || 'Global'}</td>
                      <td className="px-4 py-3 text-muted-foreground">{formatDate(user.created_at)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
              {(data?.items.length ?? 0) === 0 && (
                <p className="p-6 text-center text-sm text-muted-foreground">Nenhum usuário encontrado para os filtros atuais.</p>
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
