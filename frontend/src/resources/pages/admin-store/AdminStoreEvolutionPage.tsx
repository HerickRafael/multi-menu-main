import { useCallback, useEffect, useState, type FormEvent } from 'react'
import {
  AlertCircle,
  Check,
  Copy,
  Eye,
  EyeOff,
  Loader2,
  MessageSquare,
  Plus,
  QrCode,
  RefreshCw,
  Settings as SettingsIcon,
  Trash2,
  User,
  Users,
  Wifi,
  WifiOff,
  Zap,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  AdminStorePageShell,
  AdminPageHeader,
  ConfirmDialog,
  EmptyState,
  ErrorState,
  FormField,
  FormSection,
  LoadingState,
  showToast,
  getCsrfToken,
  useStoreContext,
} from '@/components/admin-store'

type Instance = {
  id?: string | number | null
  instanceName?: string
  instance_name?: string
  instance_identifier?: string | null
  contact_name?: string
  phone?: string
  instance_phone?: string
  show_phone?: boolean
  handle?: string
  users?: string | number
  messages?: string | number
  status?: string
  avatar?: { letters?: string; color?: string }
  profile_pic_url?: string | null
  qrcode?: string
}

type EvolutionPayload = {
  has_credentials: boolean
  urls: {
    instances_data: string
    create: string
    sync: string
    import_remote: string
    fetch_and_import: string
    refresh_qr: string
    delete: string
    instance_base: string
    settings: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_EVOLUTION__?: EvolutionPayload
  }
}

const STATUS_INFO: Record<
  string,
  { label: string; icon: typeof Wifi; pillClass: string; dotClass: string }
> = {
  connected: {
    label: 'Conectado',
    icon: Wifi,
    pillClass: 'bg-emerald-100 text-emerald-700 border-emerald-200',
    dotClass: 'bg-emerald-500',
  },
  pending: {
    label: 'Reconectando',
    icon: Loader2,
    pillClass: 'bg-amber-100 text-amber-800 border-amber-200',
    dotClass: 'bg-amber-500 animate-pulse',
  },
  disconnected: {
    label: 'Desconectado',
    icon: WifiOff,
    pillClass: 'bg-zinc-100 text-zinc-700 border-zinc-200',
    dotClass: 'bg-zinc-400',
  },
}

function getStatusInfo(status: string) {
  return STATUS_INFO[status] || STATUS_INFO.disconnected
}

function InstanceCard({
  data,
  onDelete,
  onShowQr,
  instanceBaseUrl,
}: {
  data: Instance
  onDelete: (inst: Instance) => void
  onShowQr: (inst: Instance) => void
  instanceBaseUrl: string
}) {
  const name = data.instance_name || data.instanceName || `Instance ${data.id ?? ''}`
  const status = (data.status || 'disconnected').toLowerCase()
  const info = getStatusInfo(status)
  const StatusIcon = info.icon

  const identifier = String(data.instance_identifier ?? data.id ?? '')
  const [revealed, setRevealed] = useState(false)
  const [copied, setCopied] = useState(false)

  const showQrButton = status === 'pending' || status === 'connecting' || status === 'qr' || status === 'qrcode' || status === 'disconnected'

  async function copyIdentifier() {
    if (!identifier) return
    try {
      await navigator.clipboard?.writeText(identifier)
      setCopied(true)
      showToast('UUID da instância copiado!', 'success')
      setTimeout(() => setCopied(false), 1500)
    } catch {
      showToast('Falha ao copiar.', 'error')
    }
  }

  const phoneOrHint =
    data.show_phone && data.instance_phone
      ? data.instance_phone
      : status === 'connected'
        ? 'Número não disponível'
        : status === 'pending'
          ? 'Conecte via QR Code'
          : 'Desconectado'

  return (
    <article className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:shadow-md">
      {/* Header */}
      <div className="mb-4 flex items-center justify-between gap-2">
        <h3 className="font-semibold text-zinc-900 truncate font-mono text-sm">{name}</h3>
        <a
          href={`${instanceBaseUrl}${encodeURIComponent(name)}`}
          className="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50"
          title="Configurações"
        >
          <SettingsIcon className="h-4 w-4" />
        </a>
      </div>

      {/* UUID box */}
      {identifier && (
        <div className="mb-4 rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2.5 text-sm">
          <div className="flex items-center gap-2">
            <span
              className="flex-1 select-all overflow-hidden text-ellipsis whitespace-nowrap font-mono text-xs text-zinc-700"
              title={identifier}
            >
              {revealed ? identifier : identifier.replace(/./g, '•')}
            </span>
            <button
              type="button"
              onClick={copyIdentifier}
              className="inline-flex h-6 w-6 items-center justify-center rounded text-zinc-500 hover:bg-zinc-200"
              title="Copiar UUID"
            >
              {copied ? <Check className="h-3 w-3 text-emerald-600" /> : <Copy className="h-3 w-3" />}
            </button>
            <button
              type="button"
              onClick={() => setRevealed((v) => !v)}
              className="inline-flex h-6 w-6 items-center justify-center rounded text-zinc-500 hover:bg-zinc-200"
              title={revealed ? 'Ocultar' : 'Mostrar'}
            >
              {revealed ? <EyeOff className="h-3 w-3" /> : <Eye className="h-3 w-3" />}
            </button>
          </div>
        </div>
      )}

      {/* Profile + stats */}
      <div className="mb-4 flex items-start justify-between gap-4">
        <div className="flex items-center gap-3 min-w-0 flex-1">
          {data.profile_pic_url ? (
            <div className="relative h-10 w-10 shrink-0 overflow-hidden rounded-full bg-zinc-100 ring-2 ring-zinc-200">
              <img
                src={data.profile_pic_url}
                alt="Profile"
                className="h-full w-full object-cover"
                onError={(e) => {
                  const target = e.currentTarget as HTMLImageElement
                  target.style.display = 'none'
                  const fallback = target.nextElementSibling as HTMLElement | null
                  if (fallback) fallback.style.display = 'flex'
                }}
              />
              <div
                className={`absolute inset-0 hidden items-center justify-center rounded-full text-xs font-bold text-white ${data.avatar?.color || 'bg-zinc-400'}`}
              >
                {data.avatar?.letters || '??'}
              </div>
            </div>
          ) : (
            <div
              className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white ${data.avatar?.color || 'bg-zinc-400'}`}
            >
              {data.avatar?.letters || '??'}
            </div>
          )}
          <div className="min-w-0">
            <p className="font-medium text-zinc-900 truncate">{data.contact_name || 'Contato'}</p>
            <p className="text-xs text-zinc-500 truncate">{phoneOrHint}</p>
          </div>
        </div>

        <div className="flex items-center gap-3 text-sm">
          <div className="flex items-center gap-1 text-zinc-600" title="Chats">
            <Users className="h-3.5 w-3.5 text-zinc-400" />
            <span className="font-medium">{data.users ?? 0}</span>
          </div>
          <div className="flex items-center gap-1 text-zinc-600" title="Mensagens">
            <MessageSquare className="h-3.5 w-3.5 text-zinc-400" />
            <span className="font-medium">{data.messages ?? 0}</span>
          </div>
        </div>
      </div>

      {/* Status pill + actions */}
      <div className="flex items-center justify-between gap-2">
        <span className={`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium ${info.pillClass}`}>
          <span className={`h-1.5 w-1.5 rounded-full ${info.dotClass}`} />
          {info.label}
        </span>
        <div className="flex items-center gap-1">
          {showQrButton && (
            <Button
              size="sm"
              variant="outline"
              onClick={() => onShowQr(data)}
              className="h-7 px-2 text-xs gap-1"
            >
              <QrCode className="h-3 w-3" />
              QR
            </Button>
          )}
          <Button
            size="sm"
            className="h-7 bg-red-600 text-white hover:bg-red-700 px-3 text-xs"
            onClick={() => onDelete(data)}
          >
            <Trash2 className="h-3 w-3 mr-1" />
            Delete
          </Button>
        </div>
      </div>
    </article>
  )
}

export default function AdminStoreEvolutionPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_EVOLUTION__) || ({} as EvolutionPayload)
  const urls = payload.urls

  const [instances, setInstances] = useState<Instance[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [refreshing, setRefreshing] = useState(false)
  const [showCreateForm, setShowCreateForm] = useState(false)
  const [newInstanceName, setNewInstanceName] = useState('')
  const [creating, setCreating] = useState(false)
  const [confirmDelete, setConfirmDelete] = useState<Instance | null>(null)
  const [qrModal, setQrModal] = useState<Instance | null>(null)
  const [searchTerm, setSearchTerm] = useState('')

  const fetchInstances = useCallback(
    async (showFeedback = false) => {
      if (!urls?.instances_data) {
        setError('URL de dados não configurada.')
        setLoading(false)
        return
      }
      setError(null)
      if (showFeedback) setRefreshing(true)
      try {
        const res = await fetch(urls.instances_data, {
          credentials: 'same-origin',
          headers: { Accept: 'application/json' },
        })
        const data = (await res.json().catch(() => null)) as { instances?: Instance[]; error?: string } | null
        if (!data) {
          setError('Resposta inválida do servidor.')
        } else if (data.error) {
          setError(data.error)
        } else {
          setInstances(data.instances ?? [])
          if (showFeedback) showToast('Lista atualizada.', 'success')
        }
      } catch {
        setError('Falha de rede ao buscar instâncias.')
      } finally {
        setLoading(false)
        setRefreshing(false)
      }
    },
    [urls?.instances_data],
  )

  useEffect(() => {
    if (!payload.has_credentials) {
      setLoading(false)
      return
    }
    fetchInstances()
    const id = setInterval(fetchInstances, 15000)
    return () => clearInterval(id)
  }, [fetchInstances, payload.has_credentials])

  async function handleCreate(e: FormEvent) {
    e.preventDefault()
    if (!newInstanceName.trim()) {
      showToast('Informe o nome da instância.', 'error')
      return
    }
    setCreating(true)
    try {
      const fd = new FormData()
      fd.append('csrf_token', getCsrfToken())
      fd.append('instance_name', newInstanceName.trim())
      const res = await fetch(urls.create, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        redirect: 'manual',
      })
      if (res.type === 'opaqueredirect' || res.ok || res.status === 302 || res.status === 0) {
        showToast(`Instância "${newInstanceName.trim()}" criada.`, 'success')
        setNewInstanceName('')
        setShowCreateForm(false)
        await fetchInstances()
      } else {
        const data = (await res.json().catch(() => null)) as { message?: string } | null
        showToast(data?.message || 'Falha ao criar instância.', 'error')
      }
    } catch {
      showToast(`Instância "${newInstanceName.trim()}" criada.`, 'success')
      setNewInstanceName('')
      setShowCreateForm(false)
      await fetchInstances()
    } finally {
      setCreating(false)
    }
  }

  async function handleSync() {
    setRefreshing(true)
    try {
      const fd = new FormData()
      fd.append('csrf_token', getCsrfToken())
      await fetch(urls.fetch_and_import, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        redirect: 'manual',
      })
      showToast('Sincronização concluída.', 'success')
      await fetchInstances()
    } catch {
      await fetchInstances()
    }
  }

  async function handleDelete() {
    if (!confirmDelete) return
    const name = confirmDelete.instance_name || confirmDelete.instanceName || ''
    try {
      const fd = new FormData()
      fd.append('csrf_token', getCsrfToken())
      fd.append('instanceName', name)
      if (confirmDelete.id) fd.append('instanceId', String(confirmDelete.id))
      const res = await fetch(urls.delete, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        redirect: 'manual',
      })
      if (res.type === 'opaqueredirect' || res.ok || res.status === 302 || res.status === 0) {
        showToast(`Instância "${name}" removida.`, 'success')
        await fetchInstances()
      } else {
        showToast('Falha ao remover instância.', 'error')
      }
    } catch {
      showToast(`Instância "${name}" removida.`, 'success')
      await fetchInstances()
    }
  }

  async function showQr(inst: Instance) {
    const name = inst.instance_name || inst.instanceName || ''
    try {
      const fd = new FormData()
      fd.append('csrf_token', getCsrfToken())
      fd.append('instance_name', name)
      const res = await fetch(urls.refresh_qr, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const data = (await res.json().catch(() => null)) as { qrcode?: string; success?: boolean; message?: string } | null
      if (data?.qrcode) {
        setQrModal({ ...inst, qrcode: data.qrcode })
      } else if (data?.success === false) {
        showToast(data?.message || 'QR code não disponível.', 'error')
      } else {
        setQrModal(inst)
      }
    } catch {
      showToast('Falha ao buscar QR code.', 'error')
    }
  }

  const filtered = instances.filter((inst) => {
    if (!searchTerm.trim()) return true
    const haystack = [
      inst.instance_name,
      inst.instanceName,
      inst.contact_name,
      inst.handle,
      inst.phone,
      inst.instance_phone,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()
    return haystack.includes(searchTerm.trim().toLowerCase())
  })

  if (!payload.has_credentials) {
    return (
      <AdminStorePageShell section="whatsapp">
        <AdminPageHeader
          title="WhatsApp · Evolution API"
          description="Conecte sua loja à Evolution API para enviar notificações via WhatsApp."
          icon={<MessageSquare className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        />
        <EmptyState
          title="Credenciais não configuradas"
          description="Para gerenciar instâncias do WhatsApp, configure primeiro a URL do servidor Evolution e a chave de API nas Configurações da loja."
          icon={<AlertCircle className="h-5 w-5" />}
          action={
            <Button asChild className="gap-2">
              <a href={urls.settings}>
                <SettingsIcon className="h-4 w-4" />
                Ir para Configurações
              </a>
            </Button>
          }
        />
      </AdminStorePageShell>
    )
  }

  return (
    <AdminStorePageShell section="whatsapp">
      <AdminPageHeader
        title="WhatsApp · Evolution"
        description={`${instances.length} instância${instances.length === 1 ? '' : 's'} configurada${instances.length === 1 ? '' : 's'}. Atualiza automaticamente a cada 15s.`}
        icon={<MessageSquare className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <div className="flex items-center gap-2">
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={() => fetchInstances(true)}
              disabled={refreshing}
              className="gap-1.5"
            >
              <RefreshCw className={`h-3.5 w-3.5 ${refreshing ? 'animate-spin' : ''}`} />
              Atualizar
            </Button>
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={handleSync}
              disabled={refreshing}
              className="gap-1.5"
            >
              <Zap className="h-3.5 w-3.5" />
              Sincronizar
            </Button>
            <Button type="button" size="sm" onClick={() => setShowCreateForm((s) => !s)} className="gap-1.5">
              <Plus className="h-3.5 w-3.5" />
              Nova instância
            </Button>
          </div>
        }
      />

      {showCreateForm && (
        <form onSubmit={handleCreate}>
          <FormSection title="Criar nova instância" description="Dê um nome único para a instância (sem espaços ou caracteres especiais).">
            <div className="flex flex-wrap items-end gap-2">
              <FormField label="Nome da instância" htmlFor="ev-name" required className="flex-1 min-w-[200px]">
                <Input
                  id="ev-name"
                  value={newInstanceName}
                  onChange={(e) => setNewInstanceName(e.target.value.replace(/[^a-zA-Z0-9_-]/g, ''))}
                  placeholder="ex.: loja_principal"
                  maxLength={50}
                  className="font-mono text-sm"
                  autoFocus
                />
              </FormField>
              <Button type="submit" disabled={creating} className="gap-1.5">
                {creating ? <Loader2 className="h-4 w-4 animate-spin" /> : <Check className="h-4 w-4" />}
                Criar
              </Button>
              <Button type="button" variant="ghost" onClick={() => setShowCreateForm(false)}>
                Cancelar
              </Button>
            </div>
          </FormSection>
        </form>
      )}

      {/* Search */}
      {instances.length > 0 && (
        <div className="relative max-w-md">
          <User className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" />
          <Input
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            placeholder="Buscar por nome, telefone, perfil..."
            className="pl-9"
          />
        </div>
      )}

      {loading ? (
        <LoadingState label="Carregando instâncias..." />
      ) : error ? (
        <ErrorState
          title="Não foi possível carregar"
          description={error}
          action={
            <Button onClick={() => fetchInstances(true)} variant="outline">
              Tentar novamente
            </Button>
          }
        />
      ) : instances.length === 0 ? (
        <EmptyState
          title="Nenhuma instância configurada"
          description="Crie uma instância do WhatsApp para começar a enviar notificações automáticas aos clientes."
          icon={<MessageSquare className="h-5 w-5" />}
          action={
            <Button onClick={() => setShowCreateForm(true)} className="gap-2">
              <Plus className="h-4 w-4" />
              Criar primeira instância
            </Button>
          }
        />
      ) : filtered.length === 0 ? (
        <EmptyState
          title="Nenhuma instância encontrada"
          description="Limpe a busca para ver todas as instâncias."
          icon={<MessageSquare className="h-5 w-5" />}
        />
      ) : (
        <section className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {filtered.map((inst, idx) => {
            const key = inst.instance_identifier || inst.id || inst.instance_name || idx
            return (
              <InstanceCard
                key={String(key)}
                data={inst}
                onDelete={setConfirmDelete}
                onShowQr={showQr}
                instanceBaseUrl={urls.instance_base}
              />
            )
          })}
        </section>
      )}

      {/* QR Code Modal */}
      {qrModal && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
          onClick={() => setQrModal(null)}
        >
          <div
            className="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="mb-4 flex items-center justify-between gap-2">
              <div>
                <h3 className="text-lg font-semibold text-zinc-800">QR Code</h3>
                <p className="text-xs text-zinc-500">
                  Instância: <span className="font-mono">{qrModal.instance_name ?? qrModal.instanceName}</span>
                </p>
              </div>
              <Button variant="ghost" size="sm" onClick={() => setQrModal(null)}>
                ✕
              </Button>
            </div>
            <div className="flex flex-col items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4">
              {qrModal.qrcode ? (
                <img
                  src={qrModal.qrcode.startsWith('data:') ? qrModal.qrcode : `data:image/png;base64,${qrModal.qrcode}`}
                  alt="QR Code"
                  className="h-64 w-64"
                />
              ) : (
                <div className="flex h-64 w-64 items-center justify-center text-zinc-400">
                  <Loader2 className="h-8 w-8 animate-spin" />
                </div>
              )}
              <p className="text-center text-xs text-zinc-600">
                Abra o WhatsApp no celular, vá em <strong>Dispositivos conectados</strong> e aponte a câmera para este QR.
              </p>
            </div>
            <div className="mt-3 flex justify-end gap-2">
              <Button variant="outline" onClick={() => showQr(qrModal)} className="gap-1.5">
                <RefreshCw className="h-3.5 w-3.5" />
                Atualizar QR
              </Button>
              <Button onClick={() => setQrModal(null)}>Fechar</Button>
            </div>
          </div>
        </div>
      )}

      <ConfirmDialog
        open={!!confirmDelete}
        onOpenChange={(open) => !open && setConfirmDelete(null)}
        title="Remover esta instância?"
        description={
          confirmDelete
            ? `A instância "${confirmDelete.instance_name ?? confirmDelete.instanceName}" será removida do servidor Evolution. Esta ação não pode ser desfeita.`
            : null
        }
        confirmLabel="Remover"
        destructive
        onConfirm={handleDelete}
      />
    </AdminStorePageShell>
  )
}
