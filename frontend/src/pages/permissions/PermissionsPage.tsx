import { useState } from 'react'
import { KeyRound } from 'lucide-react'
import { PageContainer, PageHeader } from '@/components/shared/PageHeader'
import { TableSkeleton } from '@/components/shared/Skeletons'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useAssignRoleMutation, usePermissionsData } from '@/hooks/usePhase5Data'
import { formatNumber, truncate } from '@/lib/utils'

export default function PermissionsPage() {
  const [selectedUserId, setSelectedUserId] = useState('')
  const [selectedRoleId, setSelectedRoleId] = useState('')
  const { data, isLoading, isFetching } = usePermissionsData()
  const assignRoleMutation = useAssignRoleMutation()

  const firstUserId = data?.admins[0]?.id ?? 0
  const firstRoleId = data?.roles[0]?.id ?? 0
  const currentUserId = Number(selectedUserId || firstUserId)
  const currentRoleId = Number(selectedRoleId || firstRoleId)

  const handleAssignRole = () => {
    if (currentUserId < 1 || currentRoleId < 1) {
      return
    }

    assignRoleMutation.mutate({ user_id: currentUserId, role_id: currentRoleId })
  }

  if (isLoading) {
    return (
      <PageContainer>
        <TableSkeleton rows={10} cols={6} />
      </PageContainer>
    )
  }

  return (
    <PageContainer>
      <PageHeader title="Permissões" description="RBAC, papéis administrativos e matriz de acesso">
        <Badge variant="secondary" className="gap-1">
          <KeyRound className="h-3.5 w-3.5" />
          {isFetching ? 'Atualizando' : 'Fase 5'}
        </Badge>
      </PageHeader>

      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Roles</CardTitle></CardHeader><CardContent className="text-2xl font-bold">{formatNumber(data?.stats.roles_total ?? 0)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Permissões</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-blue-600">{formatNumber(data?.stats.permissions_total ?? 0)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Admins</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-emerald-600">{formatNumber(data?.stats.admins_total ?? 0)}</CardContent></Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm">Atribuições</CardTitle></CardHeader><CardContent className="text-2xl font-bold text-amber-600">{formatNumber(data?.stats.assignments_total ?? 0)}</CardContent></Card>
      </div>

      <Card>
        <CardHeader className="pb-3"><CardTitle className="text-base">Atribuir role</CardTitle></CardHeader>
        <CardContent className="grid grid-cols-1 gap-3 md:grid-cols-4">
          <select value={selectedUserId} onChange={(e) => setSelectedUserId(e.target.value)} className="h-10 rounded-md border border-input bg-background px-3 text-sm md:col-span-2">
            {data?.admins.map((admin) => (
              <option key={admin.id} value={admin.id}>{admin.name} ({admin.email})</option>
            ))}
          </select>
          <select value={selectedRoleId} onChange={(e) => setSelectedRoleId(e.target.value)} className="h-10 rounded-md border border-input bg-background px-3 text-sm">
            {data?.roles.map((role) => (
              <option key={role.id} value={role.id}>{role.name}</option>
            ))}
          </select>
          <Button onClick={handleAssignRole} disabled={assignRoleMutation.isPending || currentUserId < 1 || currentRoleId < 1}>
            {assignRoleMutation.isPending ? 'Salvando...' : 'Atribuir role'}
          </Button>
        </CardContent>
      </Card>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader className="pb-3"><CardTitle className="text-base">Roles</CardTitle></CardHeader>
          <CardContent className="space-y-3">
            {data?.roles.map((role) => (
              <div key={role.id} className="rounded-md border p-3">
                <div className="flex items-center justify-between gap-3">
                  <div>
                    <p className="font-medium">{role.name}</p>
                    <p className="text-xs text-muted-foreground">{role.slug}</p>
                  </div>
                  <Badge variant={role.is_system ? 'info' : 'outline'}>{role.is_system ? 'Sistema' : 'Custom'}</Badge>
                </div>
                <div className="mt-3 flex gap-2 text-xs text-muted-foreground">
                  <span>{formatNumber(role.users_count)} usuários</span>
                  <span>•</span>
                  <span>{formatNumber(role.permissions_count)} permissões</span>
                </div>
              </div>
            ))}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-3"><CardTitle className="text-base">Admins e roles atribuídas</CardTitle></CardHeader>
          <CardContent className="space-y-3">
            {data?.admins.map((admin) => (
              <div key={admin.id} className="rounded-md border p-3">
                <div className="flex items-start justify-between gap-3">
                  <div>
                    <p className="font-medium">{admin.name}</p>
                    <p className="text-xs text-muted-foreground">{admin.email}</p>
                  </div>
                  <Badge variant="secondary">Base: {admin.base_role}</Badge>
                </div>
                <div className="mt-3 flex flex-wrap gap-2">
                  {admin.assigned_roles.length > 0 ? admin.assigned_roles.map((role) => (
                    <Badge key={role} variant="outline">{role}</Badge>
                  )) : <span className="text-xs text-muted-foreground">Sem roles adicionais</span>}
                </div>
              </div>
            ))}
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader className="pb-3"><CardTitle className="text-base">Matriz de permissões</CardTitle></CardHeader>
        <CardContent>
          <div className="overflow-x-auto rounded-md border">
            <table className="w-full min-w-[920px] text-sm">
              <thead className="bg-muted/50 text-left">
                <tr>
                  <th className="px-4 py-3 font-medium">Permissão</th>
                  <th className="px-4 py-3 font-medium">Módulo</th>
                  <th className="px-4 py-3 font-medium">Ação</th>
                  <th className="px-4 py-3 font-medium">Descrição</th>
                  <th className="px-4 py-3 font-medium">Roles</th>
                </tr>
              </thead>
              <tbody>
                {(data?.permissions ?? []).map((permission) => (
                  <tr key={permission.id} className="border-t">
                    <td className="px-4 py-3 font-medium">{permission.permission_key}</td>
                    <td className="px-4 py-3"><Badge variant="outline">{permission.module}</Badge></td>
                    <td className="px-4 py-3">{permission.action}</td>
                    <td className="px-4 py-3 text-muted-foreground">{truncate(permission.description || '-', 68)}</td>
                    <td className="px-4 py-3">
                      <div className="flex flex-wrap gap-2">
                        {permission.roles.length > 0 ? permission.roles.map((role) => (
                          <Badge key={`${permission.id}-${role}`} variant="info">{role}</Badge>
                        )) : <span className="text-xs text-muted-foreground">Sem vínculo</span>}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </PageContainer>
  )
}
