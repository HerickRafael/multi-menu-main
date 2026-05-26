import { useCallback, useEffect, useState, type FormEvent } from 'react'
import {
  AlertTriangle,
  ArrowLeft,
  Bell,
  CheckCircle2,
  Clock,
  Copy,
  Eye,
  EyeOff,
  Heart,
  Info,
  Link as LinkIcon,
  Loader2,
  MessageSquare,
  Moon,
  PauseCircle,
  Phone,
  PhoneOff,
  Power,
  QrCode,
  RefreshCw,
  Save,
  Send,
  Settings as SettingsIcon,
  Smartphone,
  UserPlus,
  Users,
  Wifi,
  WifiOff,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  AdminStorePageShell,
  AdminPageHeader,
  FormField,
  FormSection,
  getCsrfToken,
  showToast,
  useStoreContext,
} from '@/components/admin-store'

type InstanceData = {
  instance_identifier: string
  status: string
  connection_status: string
  profile_name: string
  profile_pic_url: string
  number: string
  token: string
}

type Payload = {
  instance_name: string
  instance_data: InstanceData | null
  urls: Record<string, string>
}

declare global {
  interface Window {
    __ADMIN_STORE_EVOLUTION_INSTANCE__?: Payload
  }
}

type SettingsState = {
  rejectCall: boolean
  msgCall: string
  groupsIgnore: boolean
  alwaysOnline: boolean
  readMessages: boolean
  readStatus: boolean
  syncFullHistory: boolean
}

type OrderNotificationConfig = {
  enabled: boolean
  primary_number: string
  secondary_number: string
  updated_at?: string
}

type EngagementConfig = {
  enabled: boolean
  scenario1_enabled: boolean
  scenario1_delay: number
  scenario2_enabled: boolean
  scenario2_days: number
  out_of_hours_enabled: boolean
  out_of_hours_message: string
  scheduled_pause_enabled: boolean
  scheduled_pause_message: string
  business_hours_automation_enabled: boolean
}

type EngagementStats = {
  messages?: { total_sent?: number | string; scenario1_sent?: number | string; scenario2_sent?: number | string }
  queue?: { pending?: number | string }
  dlq?: { dead_letters?: number | string }
  conversion?: {
    total_engaged?: number
    converted?: number
    conversion_rate?: number
    revenue?: number
  }
}

const STATUS_INFO: Record<string, { label: string; icon: typeof Wifi; pill: string; dot: string }> = {
  connected: {
    label: 'Conectado',
    icon: Wifi,
    pill: 'bg-emerald-100 text-emerald-700 border-emerald-200',
    dot: 'bg-emerald-500',
  },
  open: {
    label: 'Conectado',
    icon: Wifi,
    pill: 'bg-emerald-100 text-emerald-700 border-emerald-200',
    dot: 'bg-emerald-500',
  },
  pending: {
    label: 'Aguardando conexão',
    icon: Loader2,
    pill: 'bg-amber-100 text-amber-800 border-amber-200',
    dot: 'bg-amber-500 animate-pulse',
  },
  connecting: {
    label: 'Conectando...',
    icon: Loader2,
    pill: 'bg-amber-100 text-amber-800 border-amber-200',
    dot: 'bg-amber-500 animate-pulse',
  },
  disconnected: {
    label: 'Desconectado',
    icon: WifiOff,
    pill: 'bg-zinc-100 text-zinc-700 border-zinc-200',
    dot: 'bg-zinc-400',
  },
}

function getStatusInfo(status: string) {
  return STATUS_INFO[String(status).toLowerCase()] || STATUS_INFO.disconnected
}

function maskPhone(raw: string): string {
  const d = raw.replace(/\D/g, '').slice(0, 13)
  if (d.length === 0) return ''
  if (d.length <= 2) return d
  if (d.length <= 4) return `+${d}`
  if (d.length <= 6) return `+${d.slice(0, 2)} (${d.slice(2)}`
  if (d.length <= 11) return `+${d.slice(0, 2)} (${d.slice(2, 4)}) ${d.slice(4)}`
  return `+${d.slice(0, 2)} (${d.slice(2, 4)}) ${d.slice(4, 9)}-${d.slice(9)}`
}

export default function AdminStoreEvolutionInstanceConfigPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_EVOLUTION_INSTANCE__) || ({} as Payload)
  const urls = payload.urls
  const instanceName = payload.instance_name

  const [instanceData, setInstanceData] = useState<InstanceData | null>(payload.instance_data)
  const [status, setStatus] = useState<string>(payload.instance_data?.status || 'disconnected')
  const [refreshing, setRefreshing] = useState(false)
  const [qrModal, setQrModal] = useState<string | null>(null)
  const [revealedId, setRevealedId] = useState(false)
  const [actionBusy, setActionBusy] = useState<string | null>(null)

  // Settings state
  const [settings, setSettings] = useState<SettingsState>({
    rejectCall: false,
    msgCall: '',
    groupsIgnore: false,
    alwaysOnline: false,
    readMessages: false,
    readStatus: false,
    syncFullHistory: false,
  })
  const [settingsLoaded, setSettingsLoaded] = useState(false)
  const [savingSettings, setSavingSettings] = useState(false)

  // Order notification state
  const [notification, setNotification] = useState<OrderNotificationConfig>({
    enabled: false,
    primary_number: '',
    secondary_number: '',
  })
  const [notificationLoaded, setNotificationLoaded] = useState(false)
  const [savingNotification, setSavingNotification] = useState(false)

  // Customer engagement state
  const [engagement, setEngagement] = useState<EngagementConfig>({
    enabled: false,
    scenario1_enabled: true,
    scenario1_delay: 10,
    scenario2_enabled: true,
    scenario2_days: 15,
    out_of_hours_enabled: true,
    out_of_hours_message: '',
    scheduled_pause_enabled: true,
    scheduled_pause_message: '',
    business_hours_automation_enabled: false,
  })
  const [engagementLoaded, setEngagementLoaded] = useState(false)
  const [savingEngagement, setSavingEngagement] = useState(false)
  const [engagementStats, setEngagementStats] = useState<EngagementStats | null>(null)

  /* ─────────── Connection state ─────────── */

  const refreshConnectionState = useCallback(async () => {
    setRefreshing(true)
    try {
      const res = await fetch(urls.connection_state, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      })
      const data = (await res.json().catch(() => null)) as
        | { success?: boolean; state?: string; status?: string; data?: any }
        | null
      const newStatus = String(data?.state || data?.status || data?.data?.state || 'disconnected').toLowerCase()
      setStatus(newStatus)
      if (instanceData) {
        setInstanceData({ ...instanceData, status: newStatus, connection_status: newStatus })
      }
    } catch {
      // silent
    } finally {
      setRefreshing(false)
    }
  }, [urls.connection_state, instanceData])

  useEffect(() => {
    refreshConnectionState()
    const id = setInterval(refreshConnectionState, 10000) // every 10s
    return () => clearInterval(id)
  }, [refreshConnectionState])

  /* ─────────── Action handlers ─────────── */

  async function performAction(actionKey: string, url: string, method: 'POST' | 'GET' = 'POST', toastOk?: string) {
    setActionBusy(actionKey)
    try {
      const fd = new FormData()
      fd.append('csrf_token', getCsrfToken())
      const res = await fetch(url, {
        method,
        body: method === 'POST' ? fd : undefined,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean; message?: string } | null
      if (data?.success !== false) {
        showToast(toastOk || data?.message || 'OK', 'success')
        setTimeout(refreshConnectionState, 500)
      } else {
        showToast(data?.message || 'Falha na operação.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setActionBusy(null)
    }
  }

  async function showQrCode() {
    setQrModal('loading')
    try {
      const res = await fetch(urls.qr_code, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      })
      const data = (await res.json().catch(() => null)) as
        | { success?: boolean; qrcode?: string; base64?: string; data?: any }
        | null
      const qr = data?.qrcode || data?.base64 || data?.data?.base64 || data?.data?.qrcode || ''
      if (qr) {
        setQrModal(qr.startsWith('data:') ? qr : `data:image/png;base64,${qr}`)
      } else {
        showToast('QR code não disponível no momento.', 'error')
        setQrModal(null)
      }
    } catch {
      showToast('Falha ao buscar QR.', 'error')
      setQrModal(null)
    }
  }

  async function copyIdentifier() {
    if (!instanceData?.instance_identifier) return
    try {
      await navigator.clipboard?.writeText(instanceData.instance_identifier)
      showToast('UUID copiado!', 'success')
    } catch {
      showToast('Falha ao copiar.', 'error')
    }
  }

  /* ─────────── Settings load/save ─────────── */

  const loadSettings = useCallback(async () => {
    try {
      const res = await fetch(urls.get_settings, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      })
      const data = (await res.json().catch(() => null)) as
        | { success?: boolean; data?: SettingsState }
        | null
      if (data?.success && data.data) {
        setSettings(data.data)
      }
    } catch {
      // ignore
    } finally {
      setSettingsLoaded(true)
    }
  }, [urls.get_settings])

  useEffect(() => {
    loadSettings()
  }, [loadSettings])

  async function saveSettings(e: FormEvent) {
    e.preventDefault()
    setSavingSettings(true)
    try {
      const res = await fetch(urls.save_settings, {
        method: 'POST',
        body: JSON.stringify(settings),
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json; charset=utf-8',
          Accept: 'application/json',
          'X-CSRF-Token': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean; message?: string } | null
      if (data?.success) {
        showToast(data.message || 'Configurações salvas.', 'success')
      } else {
        showToast(data?.message || 'Falha ao salvar.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setSavingSettings(false)
    }
  }

  /* ─────────── Order notification load/save ─────────── */

  const loadNotification = useCallback(async () => {
    try {
      const res = await fetch(urls.order_notification, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean; data?: OrderNotificationConfig } | null
      if (data?.success && data.data) {
        setNotification({
          enabled: !!data.data.enabled,
          primary_number: maskPhone(String(data.data.primary_number ?? '')),
          secondary_number: maskPhone(String(data.data.secondary_number ?? '')),
        })
      }
    } catch {
      // ignore
    } finally {
      setNotificationLoaded(true)
    }
  }, [urls.order_notification])

  useEffect(() => {
    loadNotification()
  }, [loadNotification])

  async function saveNotification(e: FormEvent, forceSwitch = false) {
    e.preventDefault()
    setSavingNotification(true)
    try {
      const body = {
        enabled: notification.enabled,
        primary_number: notification.primary_number.replace(/\D/g, ''),
        secondary_number: notification.secondary_number.replace(/\D/g, ''),
        force_switch: forceSwitch,
      }
      const res = await fetch(urls.order_notification, {
        method: 'POST',
        body: JSON.stringify(body),
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json; charset=utf-8',
          Accept: 'application/json',
          'X-CSRF-Token': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
      })
      const data = (await res.json().catch(() => null)) as
        | { success?: boolean; message?: string; conflict?: boolean; active_instance?: string; error?: string }
        | null
      if (data?.success) {
        showToast(data.message || 'Notificações salvas.', 'success')
      } else if (data?.conflict) {
        if (
          window.confirm(
            `A instância "${data.active_instance}" já tem notificações ativas. Mover para esta instância?`,
          )
        ) {
          await saveNotification(e, true)
        }
      } else {
        showToast(data?.error || data?.message || 'Falha ao salvar.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setSavingNotification(false)
    }
  }

  /* ─────────── Customer engagement load/save ─────────── */

  const loadEngagementStats = useCallback(async () => {
    if (!urls.engagement_stats) return
    try {
      const res = await fetch(urls.engagement_stats, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean; data?: EngagementStats } | null
      if (data?.success && data.data) {
        setEngagementStats(data.data)
      }
    } catch {
      // ignore
    }
  }, [urls.engagement_stats])

  const loadEngagement = useCallback(async () => {
    try {
      const res = await fetch(urls.customer_engagement, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean; data?: Partial<EngagementConfig> } | null
      if (data?.success && data.data) {
        const d = data.data
        const next: EngagementConfig = {
          enabled: !!d.enabled,
          scenario1_enabled: d.scenario1_enabled !== false,
          scenario1_delay: Number(d.scenario1_delay ?? 10) || 10,
          scenario2_enabled: d.scenario2_enabled !== false,
          scenario2_days: Number(d.scenario2_days ?? 15) || 15,
          out_of_hours_enabled: d.out_of_hours_enabled !== false,
          out_of_hours_message: String(d.out_of_hours_message ?? ''),
          scheduled_pause_enabled: d.scheduled_pause_enabled !== false,
          scheduled_pause_message: String(d.scheduled_pause_message ?? ''),
          business_hours_automation_enabled:
            d.business_hours_automation_enabled === true ||
            (d.business_hours_automation_enabled as unknown) === 1 ||
            (d.business_hours_automation_enabled as unknown) === '1',
        }
        setEngagement(next)
        if (next.enabled) loadEngagementStats()
      }
    } catch {
      // ignore
    } finally {
      setEngagementLoaded(true)
    }
  }, [urls.customer_engagement, loadEngagementStats])

  useEffect(() => {
    loadEngagement()
  }, [loadEngagement])

  async function saveEngagement(e: FormEvent | null, forceSwitch = false): Promise<boolean> {
    if (e) e.preventDefault()

    if (engagement.scenario1_delay < 5 || engagement.scenario1_delay > 60) {
      showToast('O tempo de espera deve estar entre 5 e 60 minutos.', 'error')
      return false
    }
    if (engagement.scenario2_days < 7 || engagement.scenario2_days > 90) {
      showToast('O período de inatividade deve estar entre 7 e 90 dias.', 'error')
      return false
    }

    setSavingEngagement(true)
    try {
      const body = {
        enabled: engagement.enabled,
        scenario1_enabled: engagement.scenario1_enabled,
        scenario1_delay: engagement.scenario1_delay,
        scenario2_enabled: engagement.scenario2_enabled,
        scenario2_days: engagement.scenario2_days,
        out_of_hours_enabled: engagement.out_of_hours_enabled,
        out_of_hours_message: engagement.out_of_hours_message,
        scheduled_pause_enabled: engagement.scheduled_pause_enabled,
        scheduled_pause_message: engagement.scheduled_pause_message,
        business_hours_automation_enabled: engagement.business_hours_automation_enabled,
        force_switch: forceSwitch,
      }
      const res = await fetch(urls.customer_engagement, {
        method: 'POST',
        body: JSON.stringify(body),
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json; charset=utf-8',
          Accept: 'application/json',
          'X-CSRF-Token': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
      })
      const data = (await res.json().catch(() => null)) as
        | { success?: boolean; message?: string; conflict?: boolean; active_instance?: string; error?: string }
        | null
      if (data?.success) {
        showToast(data.message || 'Configuração de engajamento salva.', 'success')
        if (engagement.enabled) loadEngagementStats()
        return true
      }
      if (data?.conflict) {
        if (
          window.confirm(
            `A instância "${data.active_instance}" já está com engajamento ativado. Mover para esta instância?`,
          )
        ) {
          return await saveEngagement(null, true)
        }
        return false
      }
      showToast(data?.error || data?.message || 'Falha ao salvar.', 'error')
      return false
    } catch {
      showToast('Falha de rede.', 'error')
      return false
    } finally {
      setSavingEngagement(false)
    }
  }

  async function handleEngagementToggle(newEnabled: boolean) {
    const previous = engagement.enabled
    setEngagement((prev) => ({ ...prev, enabled: newEnabled }))
    // Save immediately
    const ok = await saveEngagementAfterToggle(newEnabled)
    if (!ok) {
      setEngagement((prev) => ({ ...prev, enabled: previous }))
    } else if (newEnabled) {
      loadEngagementStats()
    } else {
      setEngagementStats(null)
    }
  }

  async function saveEngagementAfterToggle(enabledOverride: boolean, forceSwitch = false): Promise<boolean> {
    setSavingEngagement(true)
    try {
      const body = {
        enabled: enabledOverride,
        scenario1_enabled: engagement.scenario1_enabled,
        scenario1_delay: engagement.scenario1_delay,
        scenario2_enabled: engagement.scenario2_enabled,
        scenario2_days: engagement.scenario2_days,
        out_of_hours_enabled: engagement.out_of_hours_enabled,
        out_of_hours_message: engagement.out_of_hours_message,
        scheduled_pause_enabled: engagement.scheduled_pause_enabled,
        scheduled_pause_message: engagement.scheduled_pause_message,
        business_hours_automation_enabled: engagement.business_hours_automation_enabled,
        force_switch: forceSwitch,
      }
      const res = await fetch(urls.customer_engagement, {
        method: 'POST',
        body: JSON.stringify(body),
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json; charset=utf-8',
          Accept: 'application/json',
          'X-CSRF-Token': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
      })
      const data = (await res.json().catch(() => null)) as
        | { success?: boolean; message?: string; conflict?: boolean; active_instance?: string; error?: string }
        | null
      if (data?.success) {
        showToast(enabledOverride ? 'Engajamento ativado!' : 'Engajamento desativado.', enabledOverride ? 'success' : 'info')
        return true
      }
      if (data?.conflict) {
        if (
          window.confirm(
            `A instância "${data.active_instance}" já está com engajamento ativado. Mover para esta instância?`,
          )
        ) {
          return await saveEngagementAfterToggle(enabledOverride, true)
        }
        return false
      }
      showToast(data?.error || data?.message || 'Falha ao salvar.', 'error')
      return false
    } catch {
      showToast('Falha de rede.', 'error')
      return false
    } finally {
      setSavingEngagement(false)
    }
  }

  const info = getStatusInfo(status)
  const StatusIcon = info.icon
  const isConnected = status === 'connected' || status === 'open'
  const canShowQr = !isConnected
  const identifier = instanceData?.instance_identifier || instanceName

  return (
    <AdminStorePageShell section="whatsapp">
      <AdminPageHeader
        title={`Instância · ${instanceName}`}
        description="Configure conexão, comportamento das mensagens e notificações automáticas de pedidos."
        icon={<MessageSquare className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <Button asChild variant="outline" size="sm" className="gap-1.5">
            <a href={urls.instances_list}>
              <ArrowLeft className="h-3.5 w-3.5" />
              Voltar para instâncias
            </a>
          </Button>
        }
      />

      {/* Connection status card */}
      <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
        <div className="flex flex-wrap items-start gap-4 mb-4">
          {instanceData?.profile_pic_url ? (
            <img
              src={instanceData.profile_pic_url}
              alt="Profile"
              className="h-16 w-16 shrink-0 rounded-full border-2 border-zinc-200 object-cover"
            />
          ) : (
            <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-zinc-100 ring-2 ring-zinc-200">
              <Smartphone className="h-7 w-7 text-zinc-400" />
            </div>
          )}
          <div className="flex-1 min-w-0">
            <div className="flex flex-wrap items-center gap-2 mb-1">
              <p className="font-mono text-sm font-semibold text-zinc-900">{instanceName}</p>
              <span className={`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium ${info.pill}`}>
                <span className={`h-1.5 w-1.5 rounded-full ${info.dot}`} />
                {info.label}
              </span>
            </div>
            {instanceData?.profile_name && (
              <p className="text-sm text-zinc-700">{instanceData.profile_name}</p>
            )}
            {instanceData?.number && (
              <p className="text-xs text-zinc-500 flex items-center gap-1 mt-0.5">
                <Phone className="h-3 w-3" />
                <span className="font-mono">{instanceData.number}</span>
              </p>
            )}

            {/* UUID with copy/reveal */}
            <div className="mt-2 inline-flex items-center gap-1 rounded-md border border-zinc-200 bg-zinc-50 px-2 py-1 max-w-full">
              <span
                className="font-mono text-[11px] text-zinc-600 truncate max-w-[200px]"
                title={identifier}
              >
                {revealedId ? identifier : identifier.replace(/./g, '•')}
              </span>
              <button
                type="button"
                onClick={() => setRevealedId((v) => !v)}
                className="p-0.5 text-zinc-500 hover:text-zinc-800"
                aria-label="Mostrar/Ocultar"
              >
                {revealedId ? <EyeOff className="h-3 w-3" /> : <Eye className="h-3 w-3" />}
              </button>
              <button
                type="button"
                onClick={copyIdentifier}
                className="p-0.5 text-zinc-500 hover:text-zinc-800"
                aria-label="Copiar"
              >
                <Copy className="h-3 w-3" />
              </button>
            </div>
          </div>

          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={refreshConnectionState}
            disabled={refreshing}
            className="gap-1.5"
          >
            <RefreshCw className={`h-3.5 w-3.5 ${refreshing ? 'animate-spin' : ''}`} />
            Atualizar status
          </Button>
        </div>

        {/* Action buttons */}
        <div className="flex flex-wrap gap-2 pt-3 border-t border-zinc-100">
          {!isConnected && (
            <Button
              type="button"
              onClick={showQrCode}
              disabled={actionBusy === 'qr'}
              className="gap-2"
            >
              <QrCode className="h-4 w-4" />
              {isConnected ? 'Reconectar' : 'Conectar via QR Code'}
            </Button>
          )}
          <Button
            type="button"
            variant="outline"
            onClick={() => performAction('restart', urls.restart, 'POST', 'Instância reiniciada.')}
            disabled={!!actionBusy}
            className="gap-2"
          >
            <Power className={`h-4 w-4 ${actionBusy === 'restart' ? 'animate-spin' : ''}`} />
            Reiniciar
          </Button>
          {isConnected && (
            <Button
              type="button"
              variant="outline"
              className="text-red-600 hover:bg-red-50 gap-2"
              onClick={() => {
                if (window.confirm('Desconectar a instância? O cliente terá que escanear o QR novamente para reconectar.')) {
                  performAction('disconnect', urls.disconnect, 'POST', 'Instância desconectada.')
                }
              }}
              disabled={!!actionBusy}
            >
              <PhoneOff className="h-4 w-4" />
              Desconectar
            </Button>
          )}
        </div>
      </section>

      {/* Tabs */}
      <Tabs defaultValue="settings">
        <TabsList className="h-auto flex-wrap p-1 bg-white border border-zinc-200 rounded-xl">
          <TabsTrigger value="settings" className="gap-1.5 px-3 py-1.5 text-xs sm:text-sm">
            <SettingsIcon className="h-3.5 w-3.5" />
            Comportamento
          </TabsTrigger>
          <TabsTrigger value="notification" className="gap-1.5 px-3 py-1.5 text-xs sm:text-sm">
            <Bell className="h-3.5 w-3.5" />
            Notificações de pedidos
            {notification.enabled && (
              <span className="h-2 w-2 rounded-full bg-emerald-500 ml-1" />
            )}
          </TabsTrigger>
          <TabsTrigger value="engagement" className="gap-1.5 px-3 py-1.5 text-xs sm:text-sm">
            <Heart className="h-3.5 w-3.5" />
            Engajamento de clientes
            {engagement.enabled && (
              <span className="h-2 w-2 rounded-full bg-emerald-500 ml-1" />
            )}
          </TabsTrigger>
        </TabsList>

        {/* ── Settings ──────────────────────────────────────────────── */}
        <TabsContent value="settings" className="mt-4">
          {!settingsLoaded ? (
            <div className="rounded-2xl border border-zinc-200 bg-white p-12 text-center">
              <Loader2 className="h-6 w-6 animate-spin mx-auto text-zinc-400" />
              <p className="text-sm text-zinc-500 mt-2">Carregando configurações...</p>
            </div>
          ) : (
            <form onSubmit={saveSettings}>
              <FormSection
                title="Comportamento da instância"
                description="Define como esta instância do WhatsApp responde a chamadas, leitura e sincronização."
              >
                <label className="flex items-start gap-3 cursor-pointer rounded-xl border border-zinc-200 p-3 hover:bg-zinc-50">
                  <input
                    type="checkbox"
                    checked={settings.rejectCall}
                    onChange={(e) => setSettings({ ...settings, rejectCall: e.target.checked })}
                    className="peer sr-only"
                  />
                  <span className="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
                    <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
                  </span>
                  <span className="flex-1 text-sm">
                    <span className="font-medium text-zinc-800">Rejeitar chamadas</span>
                    <span className="block text-xs text-zinc-500 mt-0.5">
                      Chamadas de voz e vídeo recebidas serão automaticamente rejeitadas.
                    </span>
                  </span>
                </label>

                {settings.rejectCall && (
                  <FormField
                    label="Mensagem ao rejeitar chamada"
                    htmlFor="ev-msgcall"
                    hint="Texto enviado automaticamente quando uma chamada é rejeitada."
                  >
                    <textarea
                      id="ev-msgcall"
                      value={settings.msgCall}
                      onChange={(e) => setSettings({ ...settings, msgCall: e.target.value })}
                      rows={2}
                      maxLength={300}
                      placeholder="Ex.: Olá! No momento não atendo chamadas. Envie sua mensagem que respondo o quanto antes!"
                      className="w-full rounded-md border border-zinc-200 bg-white p-3 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
                    />
                  </FormField>
                )}

                <label className="flex items-start gap-3 cursor-pointer rounded-xl border border-zinc-200 p-3 hover:bg-zinc-50">
                  <input
                    type="checkbox"
                    checked={settings.alwaysOnline}
                    onChange={(e) => setSettings({ ...settings, alwaysOnline: e.target.checked })}
                    className="peer sr-only"
                  />
                  <span className="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
                    <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
                  </span>
                  <span className="flex-1 text-sm">
                    <span className="font-medium text-zinc-800">Sempre online</span>
                    <span className="block text-xs text-zinc-500 mt-0.5">
                      Mostra o número sempre como "online" no WhatsApp.
                    </span>
                  </span>
                </label>

                <label className="flex items-start gap-3 cursor-pointer rounded-xl border border-zinc-200 p-3 hover:bg-zinc-50">
                  <input
                    type="checkbox"
                    checked={settings.readMessages}
                    onChange={(e) => setSettings({ ...settings, readMessages: e.target.checked })}
                    className="peer sr-only"
                  />
                  <span className="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
                    <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
                  </span>
                  <span className="flex-1 text-sm">
                    <span className="font-medium text-zinc-800">Marcar mensagens como lidas automaticamente</span>
                    <span className="block text-xs text-zinc-500 mt-0.5">
                      Os clientes verão o "✓✓" azul assim que a mensagem chegar no servidor.
                    </span>
                  </span>
                </label>

                <label className="flex items-start gap-3 cursor-pointer rounded-xl border border-zinc-200 p-3 hover:bg-zinc-50">
                  <input
                    type="checkbox"
                    checked={settings.readStatus}
                    onChange={(e) => setSettings({ ...settings, readStatus: e.target.checked })}
                    className="peer sr-only"
                  />
                  <span className="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
                    <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
                  </span>
                  <span className="flex-1 text-sm">
                    <span className="font-medium text-zinc-800">Ler status de contatos</span>
                    <span className="block text-xs text-zinc-500 mt-0.5">
                      Marcar como visto os "Status" (stories) dos contatos.
                    </span>
                  </span>
                </label>

                <label className="flex items-start gap-3 cursor-pointer rounded-xl border border-zinc-200 p-3 hover:bg-zinc-50">
                  <input
                    type="checkbox"
                    checked={settings.groupsIgnore}
                    onChange={(e) => setSettings({ ...settings, groupsIgnore: e.target.checked })}
                    className="peer sr-only"
                  />
                  <span className="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
                    <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
                  </span>
                  <span className="flex-1 text-sm">
                    <span className="font-medium text-zinc-800">Ignorar mensagens de grupos</span>
                    <span className="block text-xs text-zinc-500 mt-0.5">
                      Mensagens enviadas a grupos não disparam webhooks/automação.
                    </span>
                  </span>
                </label>

                <label className="flex items-start gap-3 cursor-pointer rounded-xl border border-zinc-200 p-3 hover:bg-zinc-50">
                  <input
                    type="checkbox"
                    checked={settings.syncFullHistory}
                    onChange={(e) => setSettings({ ...settings, syncFullHistory: e.target.checked })}
                    className="peer sr-only"
                  />
                  <span className="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
                    <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
                  </span>
                  <span className="flex-1 text-sm">
                    <span className="font-medium text-zinc-800">Sincronizar histórico completo</span>
                    <span className="block text-xs text-zinc-500 mt-0.5">
                      Importa todas as conversas antigas ao conectar (pode demorar e usar muita memória).
                    </span>
                  </span>
                </label>
              </FormSection>

              <div className="mt-4 flex justify-end">
                <Button type="submit" disabled={savingSettings} className="gap-2">
                  <Save className="h-4 w-4" />
                  {savingSettings ? 'Salvando...' : 'Salvar comportamento'}
                </Button>
              </div>
            </form>
          )}
        </TabsContent>

        {/* ── Order notification ────────────────────────────────────── */}
        <TabsContent value="notification" className="mt-4">
          {!notificationLoaded ? (
            <div className="rounded-2xl border border-zinc-200 bg-white p-12 text-center">
              <Loader2 className="h-6 w-6 animate-spin mx-auto text-zinc-400" />
              <p className="text-sm text-zinc-500 mt-2">Carregando configuração...</p>
            </div>
          ) : (
            <form onSubmit={(e) => saveNotification(e, false)}>
              <FormSection
                title="Notificações de novos pedidos"
                description="Enviar uma mensagem no WhatsApp do dono da loja sempre que um pedido é recebido."
              >
                <label className="flex items-start gap-3 cursor-pointer rounded-xl border border-zinc-200 p-3 hover:bg-zinc-50">
                  <input
                    type="checkbox"
                    checked={notification.enabled}
                    onChange={(e) => setNotification({ ...notification, enabled: e.target.checked })}
                    className="peer sr-only"
                  />
                  <span className="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
                    <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
                  </span>
                  <span className="flex-1 text-sm">
                    <span className="font-medium text-zinc-800">
                      {notification.enabled ? 'Notificações ativadas' : 'Notificações desativadas'}
                    </span>
                    <span className="block text-xs text-zinc-500 mt-0.5">
                      Só uma instância pode receber notificações por vez. Se já houver outra ativa, o sistema oferece troca.
                    </span>
                  </span>
                </label>

                <FormField
                  label="Número principal"
                  htmlFor="ev-num-1"
                  hint="WhatsApp que receberá os pedidos. Formato internacional: +55 (51) 99999-9999"
                >
                  <Input
                    id="ev-num-1"
                    value={notification.primary_number}
                    onChange={(e) => setNotification({ ...notification, primary_number: maskPhone(e.target.value) })}
                    inputMode="tel"
                    placeholder="+55 (51) 99999-9999"
                    disabled={!notification.enabled}
                  />
                </FormField>

                <FormField
                  label="Número secundário"
                  htmlFor="ev-num-2"
                  hint="Opcional. Recebe cópia das notificações."
                >
                  <Input
                    id="ev-num-2"
                    value={notification.secondary_number}
                    onChange={(e) => setNotification({ ...notification, secondary_number: maskPhone(e.target.value) })}
                    inputMode="tel"
                    placeholder="+55 (51) 88888-7777"
                    disabled={!notification.enabled}
                  />
                </FormField>

                {!isConnected && notification.enabled && (
                  <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 flex items-start gap-2">
                    <AlertTriangle className="h-4 w-4 mt-0.5 shrink-0" />
                    <span>
                      A instância precisa estar <strong>conectada</strong> para enviar notificações. Conecte via QR code antes de ativar.
                    </span>
                  </div>
                )}
              </FormSection>

              <div className="mt-4 flex justify-end">
                <Button type="submit" disabled={savingNotification} className="gap-2">
                  <Save className="h-4 w-4" />
                  {savingNotification ? 'Salvando...' : 'Salvar notificações'}
                </Button>
              </div>
            </form>
          )}
        </TabsContent>

        {/* ── Customer engagement ─────────────────────────────────── */}
        <TabsContent value="engagement" className="mt-4">
          {!engagementLoaded ? (
            <div className="rounded-2xl border border-zinc-200 bg-white p-12 text-center">
              <Loader2 className="h-6 w-6 animate-spin mx-auto text-zinc-400" />
              <p className="text-sm text-zinc-500 mt-2">Carregando configuração...</p>
            </div>
          ) : (
            <div className="space-y-4">
              {/* Master toggle */}
              <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div className="flex items-start gap-3">
                    <div className="rounded-lg bg-emerald-100 p-2">
                      <Heart className="h-5 w-5 text-emerald-600" />
                    </div>
                    <div>
                      <h3 className="text-sm font-semibold text-zinc-900">Engajamento automático de clientes</h3>
                      <p className="text-xs text-zinc-500 max-w-md mt-0.5">
                        Envie mensagens automáticas para recuperar clientes cadastrados sem pedido e reativar clientes inativos. Somente uma instância pode ter o engajamento ativo por vez.
                      </p>
                    </div>
                  </div>
                  <label className="inline-flex items-center cursor-pointer">
                    <input
                      type="checkbox"
                      checked={engagement.enabled}
                      onChange={(e) => handleEngagementToggle(e.target.checked)}
                      disabled={savingEngagement}
                      className="peer sr-only"
                    />
                    <span className="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500 peer-disabled:opacity-60">
                      <span className="ml-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5" />
                    </span>
                  </label>
                </div>
              </section>

              {engagement.enabled && (
                <>
                  {/* Stats card */}
                  {engagementStats && (
                    <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
                      <div className="flex items-center justify-between mb-3">
                        <div>
                          <h3 className="text-sm font-semibold text-zinc-900">Estatísticas dos últimos 30 dias</h3>
                          <p className="text-xs text-zinc-500">Resumo do desempenho do sistema de engajamento.</p>
                        </div>
                        <Button
                          type="button"
                          variant="outline"
                          size="sm"
                          onClick={loadEngagementStats}
                          className="gap-1.5"
                        >
                          <RefreshCw className="h-3.5 w-3.5" />
                          Atualizar
                        </Button>
                      </div>
                      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div className="rounded-xl border border-zinc-200 bg-zinc-50 p-3 text-center">
                          <Send className="h-4 w-4 mx-auto text-zinc-500 mb-1" />
                          <p className="text-lg font-bold text-zinc-900">
                            {Number(engagementStats.messages?.total_sent ?? 0)}
                          </p>
                          <p className="text-[11px] text-zinc-500">Mensagens enviadas</p>
                        </div>
                        <div className="rounded-xl border border-blue-200 bg-blue-50 p-3 text-center">
                          <UserPlus className="h-4 w-4 mx-auto text-blue-600 mb-1" />
                          <p className="text-lg font-bold text-blue-700">
                            {Number(engagementStats.messages?.scenario1_sent ?? 0)}
                          </p>
                          <p className="text-[11px] text-blue-700/80">Cenário 1 (cadastro)</p>
                        </div>
                        <div className="rounded-xl border border-amber-200 bg-amber-50 p-3 text-center">
                          <Clock className="h-4 w-4 mx-auto text-amber-600 mb-1" />
                          <p className="text-lg font-bold text-amber-700">
                            {Number(engagementStats.messages?.scenario2_sent ?? 0)}
                          </p>
                          <p className="text-[11px] text-amber-700/80">Cenário 2 (inativo)</p>
                        </div>
                        <div className="rounded-xl border border-zinc-200 bg-white p-3 text-center">
                          <Users className="h-4 w-4 mx-auto text-zinc-500 mb-1" />
                          <p className="text-lg font-bold text-zinc-900">
                            {engagementStats.conversion?.conversion_rate != null
                              ? `${Number(engagementStats.conversion.conversion_rate).toFixed(1)}%`
                              : '—'}
                          </p>
                          <p className="text-[11px] text-zinc-500">Taxa de conversão</p>
                        </div>
                      </div>
                      {(Number(engagementStats.queue?.pending ?? 0) > 0 ||
                        Number(engagementStats.dlq?.dead_letters ?? 0) > 0) && (
                        <div className="mt-3 flex flex-wrap gap-2 text-xs">
                          {Number(engagementStats.queue?.pending ?? 0) > 0 && (
                            <Badge variant="secondary" className="gap-1.5">
                              <Loader2 className="h-3 w-3 animate-spin" />
                              {Number(engagementStats.queue?.pending)} pendentes na fila
                            </Badge>
                          )}
                          {Number(engagementStats.dlq?.dead_letters ?? 0) > 0 && (
                            <Badge variant="destructive" className="gap-1.5">
                              <AlertTriangle className="h-3 w-3" />
                              {Number(engagementStats.dlq?.dead_letters)} falhas persistentes
                            </Badge>
                          )}
                        </div>
                      )}
                    </section>
                  )}

                  <form onSubmit={(e) => saveEngagement(e, false)} className="space-y-4">
                    {/* Cenários */}
                    <FormSection
                      title="Cenários de engajamento"
                      description="Configure quando o sistema deve disparar mensagens automaticamente."
                    >
                      {/* Scenario 1 */}
                      <div className="rounded-xl border border-zinc-200 bg-white p-4">
                        <div className="flex flex-wrap items-start justify-between gap-3 mb-3">
                          <div className="flex items-start gap-3">
                            <div className="rounded-lg bg-blue-100 p-2 shrink-0">
                              <UserPlus className="h-4 w-4 text-blue-600" />
                            </div>
                            <div>
                              <p className="text-sm font-medium text-zinc-900">Cenário 1 · Cadastro sem pedido</p>
                              <p className="text-xs text-zinc-500">
                                Cliente se cadastra mas não finaliza o primeiro pedido.
                              </p>
                            </div>
                          </div>
                          <label className="inline-flex items-center cursor-pointer">
                            <input
                              type="checkbox"
                              checked={engagement.scenario1_enabled}
                              onChange={(e) =>
                                setEngagement({ ...engagement, scenario1_enabled: e.target.checked })
                              }
                              className="peer sr-only"
                            />
                            <span className="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
                              <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
                            </span>
                          </label>
                        </div>
                        <div
                          className="pl-11"
                          style={{
                            opacity: engagement.scenario1_enabled ? 1 : 0.5,
                            pointerEvents: engagement.scenario1_enabled ? 'auto' : 'none',
                          }}
                        >
                          <FormField
                            label="Tempo de espera após o cadastro"
                            htmlFor="ev-scenario1-delay"
                            hint="Entre 5 e 60 minutos. Recomendado: 10 minutos."
                          >
                            <div className="flex items-center gap-2">
                              <Input
                                id="ev-scenario1-delay"
                                type="number"
                                min={5}
                                max={60}
                                value={engagement.scenario1_delay}
                                onChange={(e) =>
                                  setEngagement({
                                    ...engagement,
                                    scenario1_delay: Number(e.target.value || 10),
                                  })
                                }
                                className="w-24"
                              />
                              <span className="text-xs text-zinc-500">minutos</span>
                            </div>
                          </FormField>
                        </div>
                      </div>

                      {/* Scenario 2 */}
                      <div className="rounded-xl border border-zinc-200 bg-white p-4">
                        <div className="flex flex-wrap items-start justify-between gap-3 mb-3">
                          <div className="flex items-start gap-3">
                            <div className="rounded-lg bg-amber-100 p-2 shrink-0">
                              <Clock className="h-4 w-4 text-amber-600" />
                            </div>
                            <div>
                              <p className="text-sm font-medium text-zinc-900">Cenário 2 · Cliente inativo</p>
                              <p className="text-xs text-zinc-500">
                                Cliente que não faz pedidos há algum tempo.
                              </p>
                            </div>
                          </div>
                          <label className="inline-flex items-center cursor-pointer">
                            <input
                              type="checkbox"
                              checked={engagement.scenario2_enabled}
                              onChange={(e) =>
                                setEngagement({ ...engagement, scenario2_enabled: e.target.checked })
                              }
                              className="peer sr-only"
                            />
                            <span className="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
                              <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
                            </span>
                          </label>
                        </div>
                        <div
                          className="pl-11"
                          style={{
                            opacity: engagement.scenario2_enabled ? 1 : 0.5,
                            pointerEvents: engagement.scenario2_enabled ? 'auto' : 'none',
                          }}
                        >
                          <FormField
                            label="Período de inatividade"
                            htmlFor="ev-scenario2-days"
                            hint="Entre 7 e 90 dias. Recomendado: 15 dias."
                          >
                            <div className="flex items-center gap-2">
                              <Input
                                id="ev-scenario2-days"
                                type="number"
                                min={7}
                                max={90}
                                value={engagement.scenario2_days}
                                onChange={(e) =>
                                  setEngagement({
                                    ...engagement,
                                    scenario2_days: Number(e.target.value || 15),
                                  })
                                }
                                className="w-24"
                              />
                              <span className="text-xs text-zinc-500">dias sem pedidos</span>
                            </div>
                          </FormField>
                        </div>
                      </div>

                      <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-4 flex items-start gap-3">
                        <Info className="h-4 w-4 text-emerald-600 mt-0.5 shrink-0" />
                        <div className="text-xs text-emerald-800">
                          <p className="font-medium mb-1">Como funciona:</p>
                          <ul className="list-disc list-inside space-y-0.5 text-emerald-700/90">
                            <li>As mensagens são enviadas apenas no horário de funcionamento.</li>
                            <li>Cada cliente recebe no máximo 1 mensagem por cenário a cada 30 dias.</li>
                            <li>As mensagens são humanizadas e divididas em 3 partes.</li>
                            <li>O sistema usa saudações dinâmicas (Bom dia/tarde/noite).</li>
                          </ul>
                        </div>
                      </div>
                    </FormSection>

                    {/* Resposta fora do expediente */}
                    <FormSection
                      title="Resposta fora do expediente"
                      description="Responde automaticamente quando o cliente envia mensagem fora do horário de funcionamento."
                    >
                      <label className="flex items-start gap-3 cursor-pointer rounded-xl border border-zinc-200 p-3 hover:bg-zinc-50">
                        <input
                          type="checkbox"
                          checked={engagement.out_of_hours_enabled}
                          onChange={(e) =>
                            setEngagement({ ...engagement, out_of_hours_enabled: e.target.checked })
                          }
                          className="peer sr-only"
                        />
                        <span className="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
                          <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
                        </span>
                        <span className="flex-1 text-sm">
                          <span className="font-medium text-zinc-800 inline-flex items-center gap-1.5">
                            <Moon className="h-3.5 w-3.5 text-purple-500" />
                            Ativar resposta automática fora do expediente
                          </span>
                          <span className="block text-xs text-zinc-500 mt-0.5">
                            Cooldown de 30 minutos entre respostas para o mesmo cliente.
                          </span>
                        </span>
                      </label>

                      {engagement.out_of_hours_enabled && (
                        <FormField
                          label="Mensagem personalizada (opcional)"
                          htmlFor="ev-ooh-message"
                          hint="Deixe em branco para usar a mensagem padrão. Variáveis: {saudacao}, {dia}, {hora}."
                        >
                          <textarea
                            id="ev-ooh-message"
                            value={engagement.out_of_hours_message}
                            onChange={(e) =>
                              setEngagement({ ...engagement, out_of_hours_message: e.target.value })
                            }
                            rows={4}
                            className="w-full rounded-md border border-zinc-200 bg-white p-3 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
                            placeholder="Ex.: {saudacao}! Estamos fora do horário de atendimento. Voltamos {dia} às {hora}!"
                          />
                        </FormField>
                      )}
                    </FormSection>

                    {/* Pausa programada */}
                    <FormSection
                      title="Resposta em pausa programada"
                      description="Responde automaticamente quando a loja estiver em pausa programada."
                    >
                      <label className="flex items-start gap-3 cursor-pointer rounded-xl border border-zinc-200 p-3 hover:bg-zinc-50">
                        <input
                          type="checkbox"
                          checked={engagement.scheduled_pause_enabled}
                          onChange={(e) =>
                            setEngagement({ ...engagement, scheduled_pause_enabled: e.target.checked })
                          }
                          className="peer sr-only"
                        />
                        <span className="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
                          <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
                        </span>
                        <span className="flex-1 text-sm">
                          <span className="font-medium text-zinc-800 inline-flex items-center gap-1.5">
                            <PauseCircle className="h-3.5 w-3.5 text-orange-500" />
                            Ativar resposta em pausa programada
                          </span>
                          <span className="block text-xs text-zinc-500 mt-0.5">
                            Detecta automaticamente quando a loja está pausada.
                          </span>
                        </span>
                      </label>

                      {engagement.scheduled_pause_enabled && (
                        <FormField
                          label="Mensagem personalizada (opcional)"
                          htmlFor="ev-pause-message"
                          hint="Deixe em branco para usar a mensagem padrão. Variáveis: {motivo}, {tempo_restante}."
                        >
                          <textarea
                            id="ev-pause-message"
                            value={engagement.scheduled_pause_message}
                            onChange={(e) =>
                              setEngagement({ ...engagement, scheduled_pause_message: e.target.value })
                            }
                            rows={4}
                            className="w-full rounded-md border border-zinc-200 bg-white p-3 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
                            placeholder="Ex.: Estamos em pausa: {motivo}. Voltamos em {tempo_restante}."
                          />
                        </FormField>
                      )}
                    </FormSection>

                    {/* Automacao por expediente */}
                    <FormSection
                      title="Automação por expediente"
                      description="Controla automaticamente os toggles 'Sempre online' e 'Rejeitar chamadas' conforme o horário de funcionamento da loja."
                    >
                      <label className="flex items-start gap-3 cursor-pointer rounded-xl border border-zinc-200 p-3 hover:bg-zinc-50">
                        <input
                          type="checkbox"
                          checked={engagement.business_hours_automation_enabled}
                          onChange={(e) =>
                            setEngagement({
                              ...engagement,
                              business_hours_automation_enabled: e.target.checked,
                            })
                          }
                          className="peer sr-only"
                        />
                        <span className="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
                          <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
                        </span>
                        <span className="flex-1 text-sm">
                          <span className="font-medium text-zinc-800">
                            Ativar controle automático de presença e chamadas
                          </span>
                          <span className="block text-xs text-zinc-500 mt-0.5">
                            <strong>Dentro do horário:</strong> sempre online ligado, rejeitar chamadas desligado.{' '}
                            <strong>Fora do horário:</strong> sempre online desligado, rejeitar chamadas ligado.
                          </span>
                        </span>
                      </label>

                      {engagement.business_hours_automation_enabled && (
                        <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-3 flex items-start gap-2 text-xs text-emerald-800">
                          <CheckCircle2 className="h-4 w-4 mt-0.5 shrink-0" />
                          <span>
                            Os toggles de comportamento ficarão bloqueados na aba <strong>Comportamento</strong> enquanto esta automação estiver ativa.
                          </span>
                        </div>
                      )}
                    </FormSection>

                    <div className="flex justify-end">
                      <Button type="submit" disabled={savingEngagement} className="gap-2">
                        <Save className="h-4 w-4" />
                        {savingEngagement ? 'Salvando...' : 'Salvar engajamento'}
                      </Button>
                    </div>
                  </form>
                </>
              )}

              {!engagement.enabled && (
                <div className="rounded-2xl border border-dashed border-zinc-300 bg-white p-8 text-center">
                  <Heart className="h-8 w-8 mx-auto text-zinc-300 mb-2" />
                  <p className="text-sm font-medium text-zinc-700">Engajamento desativado</p>
                  <p className="text-xs text-zinc-500 mt-1 max-w-sm mx-auto">
                    Ative o engajamento automático para configurar cenários, mensagens fora do expediente e estatísticas.
                  </p>
                </div>
              )}
            </div>
          )}
        </TabsContent>
      </Tabs>

      {/* QR Code Modal */}
      {qrModal !== null && (
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
                <h3 className="text-lg font-semibold text-zinc-800">Conectar via QR Code</h3>
                <p className="text-xs text-zinc-500">
                  Instância: <span className="font-mono">{instanceName}</span>
                </p>
              </div>
              <Button variant="ghost" size="sm" onClick={() => setQrModal(null)}>
                ✕
              </Button>
            </div>
            <div className="flex flex-col items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4">
              {qrModal === 'loading' ? (
                <div className="flex h-64 w-64 items-center justify-center">
                  <Loader2 className="h-8 w-8 animate-spin text-zinc-400" />
                </div>
              ) : (
                <img src={qrModal} alt="QR Code" className="h-64 w-64" />
              )}
              <p className="text-center text-xs text-zinc-600">
                Abra o WhatsApp no celular → <strong>Dispositivos conectados</strong> → aponte a câmera para este QR.
                Atualiza automaticamente após conectar.
              </p>
            </div>
            <div className="mt-3 flex justify-end gap-2">
              <Button variant="outline" onClick={showQrCode} className="gap-1.5">
                <RefreshCw className="h-3.5 w-3.5" />
                Atualizar QR
              </Button>
              <Button onClick={() => setQrModal(null)}>Fechar</Button>
            </div>
          </div>
        </div>
      )}
    </AdminStorePageShell>
  )
}
