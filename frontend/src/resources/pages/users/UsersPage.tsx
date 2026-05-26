import { useEffect, useState } from 'react'
import { KeyRound, MoreHorizontal, Plus } from 'lucide-react'
import { PageContainer, PageHeader } from '@/components/shared/PageHeader'
import { TableSkeleton } from '@/components/shared/Skeletons'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Dialog, DialogCloseButton, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Separator } from '@/components/ui/separator'
import { DEBOUNCE_MS } from '@/js/lib/constants'
import { formatDate, formatNumber } from '@/js/lib/utils'
import { useDebounce } from '@/js/hooks/useDebounce'
import {
  useChangeUserPasswordMutation,
  useCreateUserMutation,
  useDeleteUserMutation,
  useUpdateUserMutation,
  useUsersData,
} from '@/js/hooks/usePhase3Data'
import { useCompanyFilterStore } from '@/js/stores/companyFilterStore'
import { toast } from 'sonner'
import type { ManagedUserRole, UserItem } from '@/js/types/phase3'

const ROLE_OPTIONS: Array<{ value: ManagedUserRole; label: string }> = [
  { value: 'root', label: 'Root' },
  { value: 'owner', label: 'Owner' },
  { value: 'staff', label: 'Staff' },
]

function roleVariant(role: string): 'default' | 'info' | 'warning' | 'success' {
  if (role === 'root') return 'warning'
  if (role === 'owner') return 'info'
  if (role === 'staff') return 'success'
  return 'default'
}

function getRoleLabel(role: string) {
  return ROLE_OPTIONS.find((option) => option.value === role)?.label ?? role
}

export default function UsersPage() {
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')
  const [role, setRole] = useState('')
  const [active, setActive] = useState('')
  const [createOpen, setCreateOpen] = useState(false)
  const [editOpen, setEditOpen] = useState(false)
  const [passwordOpen, setPasswordOpen] = useState(false)
  const [deleteOpen, setDeleteOpen] = useState(false)
  const [selectedUser, setSelectedUser] = useState<UserItem | null>(null)
  const [createForm, setCreateForm] = useState({
    name: '',
    email: '',
    password: '',
    role: 'owner' as ManagedUserRole,
    active: true,
  })
  const [editForm, setEditForm] = useState({
    name: '',
    email: '',
    role: 'owner' as ManagedUserRole,
    active: true,
  })
  const [passwordForm, setPasswordForm] = useState({
    password: '',
    confirmPassword: '',
  })
  const selectedCompanyId = useCompanyFilterStore((state) => state.selectedCompanyId)
  const createUserMutation = useCreateUserMutation()
  const updateUserMutation = useUpdateUserMutation()
  const changePasswordMutation = useChangeUserPasswordMutation()
  const deleteUserMutation = useDeleteUserMutation()

  useEffect(() => {
    setPage(1)
  }, [selectedCompanyId])

  useEffect(() => {
    setCreateOpen(false)
    setEditOpen(false)
    setPasswordOpen(false)
    setDeleteOpen(false)
    setSelectedUser(null)
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
  const isBusy = createUserMutation.isPending || updateUserMutation.isPending || changePasswordMutation.isPending || deleteUserMutation.isPending

  const openCreateDialog = () => {
    if (!selectedCompanyId) {
      toast.info('Selecione uma loja para criar usuários')
      return
    }

    setCreateForm({
      name: '',
      email: '',
      password: '',
      role: 'owner',
      active: true,
    })
    setCreateOpen(true)
  }

  const openEditDialog = (user: UserItem) => {
    setSelectedUser(user)
    setEditForm({
      name: user.name,
      email: user.email,
      role: (user.role as ManagedUserRole) ?? 'owner',
      active: user.active,
    })
    setEditOpen(true)
  }

  const openPasswordDialog = (user: UserItem) => {
    setSelectedUser(user)
    setPasswordForm({ password: '', confirmPassword: '' })
    setPasswordOpen(true)
  }

  const openDeleteDialog = (user: UserItem) => {
    setSelectedUser(user)
    setDeleteOpen(true)
  }

  const handleCreateUser = () => {
    if (!selectedCompanyId) return
    if (createForm.password.trim().length < 8) {
      toast.error('A senha deve ter ao menos 8 caracteres')
      return
    }

    createUserMutation.mutate(
      {
        company_id: selectedCompanyId,
        name: createForm.name.trim(),
        email: createForm.email.trim(),
        password: createForm.password,
        role: createForm.role,
        active: createForm.active,
      },
      {
        onSuccess: () => setCreateOpen(false),
      },
    )
  }

  const handleUpdateUser = () => {
    if (!selectedUser) return

    updateUserMutation.mutate(
      {
        user_id: selectedUser.id,
        company_id: selectedUser.company_id ?? selectedCompanyId ?? 0,
        name: editForm.name.trim(),
        email: editForm.email.trim(),
        role: editForm.role,
        active: editForm.active,
      },
      {
        onSuccess: () => setEditOpen(false),
      },
    )
  }

  const handleChangePassword = () => {
    if (!selectedUser) return
    if (passwordForm.password !== passwordForm.confirmPassword) {
      toast.error('As senhas não conferem')
      return
    }
    if (passwordForm.password.length < 8) {
      toast.error('A senha deve ter ao menos 8 caracteres')
      return
    }

    changePasswordMutation.mutate(
      {
        user_id: selectedUser.id,
        password: passwordForm.password,
      },
      {
        onSuccess: () => setPasswordOpen(false),
      },
    )
  }

  const handleDeleteUser = () => {
    if (!selectedUser) return

    deleteUserMutation.mutate(
      {
        user_id: selectedUser.id,
      },
      {
        onSuccess: () => setDeleteOpen(false),
      },
    )
  }

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
          <KeyRound className="h-3.5 w-3.5" />
          Fase 3
        </Badge>
      </PageHeader>

      <Card>
        <CardContent className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="space-y-1">
            <p className="text-sm font-medium">Escopo atual</p>
            <p className="text-sm text-muted-foreground">
              {selectedCompanyId ? `Loja selecionada: ${selectedCompanyId}` : 'Sem loja selecionada. A listagem continua disponível, mas a criação fica bloqueada.'}
            </p>
          </div>
          <Button className="gap-2" onClick={openCreateDialog} disabled={!selectedCompanyId || isBusy}>
            <Plus className="h-4 w-4" />
            Novo usuário
          </Button>
        </CardContent>
      </Card>

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
            <Button className="md:col-span-1" onClick={openCreateDialog} disabled={!selectedCompanyId || isBusy}>
              Criar usuário
            </Button>
          </div>

          {isLoading ? (
            <TableSkeleton rows={8} cols={6} />
          ) : (
            <div className="overflow-x-auto rounded-md border">
              <table className="w-full min-w-[980px] text-sm">
                <thead className="bg-muted/50 text-left">
                  <tr>
                    <th className="px-4 py-3 font-medium">Nome</th>
                    <th className="px-4 py-3 font-medium">E-mail</th>
                    <th className="px-4 py-3 font-medium">Papel</th>
                    <th className="px-4 py-3 font-medium">Status</th>
                    <th className="px-4 py-3 font-medium">Loja</th>
                    <th className="px-4 py-3 font-medium">Criado em</th>
                    <th className="px-4 py-3 font-medium text-right">Ações</th>
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
                      <td className="px-4 py-3 text-right">
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon-sm">
                              <MoreHorizontal className="h-4 w-4" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end" className="w-52">
                            <DropdownMenuItem onClick={() => openEditDialog(user)}>Editar perfil</DropdownMenuItem>
                            <DropdownMenuItem onClick={() => openPasswordDialog(user)}>Alterar senha</DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                              className="text-destructive focus:text-destructive"
                              onClick={() => openDeleteDialog(user)}
                              disabled={user.role === 'root'}
                            >
                              Excluir
                            </DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </td>
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

      <Dialog open={createOpen} onOpenChange={setCreateOpen}>
        <DialogContent className="sm:max-w-2xl">
          <DialogHeader>
            <DialogTitle>Novo usuário</DialogTitle>
            <DialogCloseButton onClick={() => setCreateOpen(false)} />
          </DialogHeader>
          <div className="grid gap-4 p-4">
            <div className="grid gap-2 sm:grid-cols-2">
              <div className="space-y-2">
                <p className="text-sm font-medium">Nome</p>
                <Input value={createForm.name} onChange={(e) => setCreateForm((prev) => ({ ...prev, name: e.target.value }))} placeholder="Nome completo" />
              </div>
              <div className="space-y-2">
                <p className="text-sm font-medium">E-mail</p>
                <Input value={createForm.email} onChange={(e) => setCreateForm((prev) => ({ ...prev, email: e.target.value }))} placeholder="usuario@exemplo.com" />
              </div>
            </div>
            <div className="grid gap-2 sm:grid-cols-2">
              <div className="space-y-2">
                <p className="text-sm font-medium">Senha</p>
                <Input type="password" value={createForm.password} onChange={(e) => setCreateForm((prev) => ({ ...prev, password: e.target.value }))} placeholder="Mínimo de 8 caracteres" />
              </div>
              <div className="space-y-2">
                <p className="text-sm font-medium">Papel</p>
                <Select value={createForm.role} onValueChange={(value) => setCreateForm((prev) => ({ ...prev, role: value as ManagedUserRole }))}>
                  <SelectTrigger>
                    <SelectValue placeholder="Selecione o papel" />
                  </SelectTrigger>
                  <SelectContent>
                    {ROLE_OPTIONS.map((option) => (
                      <SelectItem key={option.value} value={option.value}>
                        {option.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>
            <div className="flex items-center justify-between rounded-md border p-3">
              <div>
                <p className="text-sm font-medium">Usuário ativo</p>
                <p className="text-xs text-muted-foreground">Usuários inativos não acessam a loja.</p>
              </div>
              <input
                type="checkbox"
                checked={createForm.active}
                onChange={(e) => setCreateForm((prev) => ({ ...prev, active: e.target.checked }))}
                className="h-4 w-4"
              />
            </div>
            <Separator />
            <div className="flex items-center justify-end gap-2">
              <Button variant="outline" onClick={() => setCreateOpen(false)}>Cancelar</Button>
              <Button onClick={handleCreateUser} disabled={isBusy}>Criar usuário</Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      <Dialog open={editOpen} onOpenChange={setEditOpen}>
        <DialogContent className="sm:max-w-2xl">
          <DialogHeader>
            <DialogTitle>Editar usuário</DialogTitle>
            <DialogCloseButton onClick={() => setEditOpen(false)} />
          </DialogHeader>
          <div className="grid gap-4 p-4">
            <div className="grid gap-2 sm:grid-cols-2">
              <div className="space-y-2">
                <p className="text-sm font-medium">Nome</p>
                <Input value={editForm.name} onChange={(e) => setEditForm((prev) => ({ ...prev, name: e.target.value }))} placeholder="Nome completo" />
              </div>
              <div className="space-y-2">
                <p className="text-sm font-medium">E-mail</p>
                <Input value={editForm.email} onChange={(e) => setEditForm((prev) => ({ ...prev, email: e.target.value }))} placeholder="usuario@exemplo.com" />
              </div>
            </div>
            <div className="grid gap-2 sm:grid-cols-2">
              <div className="space-y-2">
                <p className="text-sm font-medium">Papel / permissões</p>
                <Select value={editForm.role} onValueChange={(value) => setEditForm((prev) => ({ ...prev, role: value as ManagedUserRole }))}>
                  <SelectTrigger>
                    <SelectValue placeholder="Selecione o papel" />
                  </SelectTrigger>
                  <SelectContent>
                    {ROLE_OPTIONS.map((option) => (
                      <SelectItem key={option.value} value={option.value}>
                        {option.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="flex items-center justify-between rounded-md border p-3">
                <div>
                  <p className="text-sm font-medium">Usuário ativo</p>
                  <p className="text-xs text-muted-foreground">Desativar impede o acesso à loja.</p>
                </div>
                <input
                  type="checkbox"
                  checked={editForm.active}
                  onChange={(e) => setEditForm((prev) => ({ ...prev, active: e.target.checked }))}
                  className="h-4 w-4"
                />
              </div>
            </div>
            <Separator />
            <div className="flex items-center justify-end gap-2">
              <Button variant="outline" onClick={() => setEditOpen(false)}>Cancelar</Button>
              <Button onClick={handleUpdateUser} disabled={isBusy}>Salvar alterações</Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      <Dialog open={passwordOpen} onOpenChange={setPasswordOpen}>
        <DialogContent className="sm:max-w-xl">
          <DialogHeader>
            <DialogTitle>Alterar senha</DialogTitle>
            <DialogCloseButton onClick={() => setPasswordOpen(false)} />
          </DialogHeader>
          <div className="grid gap-4 p-4">
            <div className="rounded-md border bg-muted/30 p-3 text-sm">
              <p className="font-medium">{selectedUser?.name}</p>
              <p className="text-muted-foreground">{selectedUser?.email}</p>
            </div>
            <div className="grid gap-2 sm:grid-cols-2">
              <div className="space-y-2">
                <p className="text-sm font-medium">Nova senha</p>
                <Input type="password" value={passwordForm.password} onChange={(e) => setPasswordForm((prev) => ({ ...prev, password: e.target.value }))} placeholder="Mínimo de 8 caracteres" />
              </div>
              <div className="space-y-2">
                <p className="text-sm font-medium">Confirmar senha</p>
                <Input type="password" value={passwordForm.confirmPassword} onChange={(e) => setPasswordForm((prev) => ({ ...prev, confirmPassword: e.target.value }))} placeholder="Repita a senha" />
              </div>
            </div>
            <div className="flex items-center justify-end gap-2">
              <Button variant="outline" onClick={() => setPasswordOpen(false)}>Cancelar</Button>
              <Button onClick={handleChangePassword} disabled={isBusy}>Salvar senha</Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      <AlertDialog open={deleteOpen} onOpenChange={setDeleteOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Excluir usuário</AlertDialogTitle>
            <AlertDialogDescription>
              {selectedUser ? `Tem certeza que deseja excluir ${selectedUser.name}? Essa ação não pode ser desfeita.` : 'Tem certeza que deseja excluir este usuário?'}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel onClick={() => setDeleteOpen(false)}>Cancelar</AlertDialogCancel>
            <AlertDialogAction onClick={handleDeleteUser} disabled={isBusy}>Excluir</AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </PageContainer>
  )
}
