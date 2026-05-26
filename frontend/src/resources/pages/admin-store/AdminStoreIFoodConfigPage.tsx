import { useEffect, useState, type FormEvent } from 'react'
import {
  AlertTriangle,
  CheckCircle2,
  CircleAlert,
  Copy,
  ExternalLink,
  Key,
  Link as LinkIcon,
  Plug,
  RefreshCw,
  Save,
  ShoppingBag,
  Store as StoreIcon,
  Zap,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  AdminStorePageShell,
  AdminPageHeader,
  FormField,
  FormSection,
  showToast,
  getCsrfToken,
  useStoreContext,
} from '@/components/admin-store'

type IFoodConfig = {
  client_id: string
  client_secret_masked: string
  has_client_secret: boolean
  merchant_id: string
  sandbox_merchant_id: string
  environment: 'sandbox' | 'production'
  is_active: boolean
  auto_confirm: boolean
  last_sync_at: string | null
  last_error: string | null
}

type Merchant = { id: string; name: string; corporate_name: string }

type IFoodPayload = {
  config: IFoodConfig
  is_connected: boolean
  merchants: Merchant[]
  merchant_status: { status: string; is_open: boolean; updated_at: string } | null
  flash: { error: string | null; success: string | null }
  webhook_url: string
  urls: {
    submit: string
    orders: string
    status: string
    test_connection: string
    clear_error: string
    poll: string
    logs: string
    reviews: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_IFOOD_CONFIG__?: IFoodPayload
  }
}

export default function AdminStoreIFoodConfigPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_IFOOD_CONFIG__) || ({} as IFoodPayload)
  const cfg = payload.config ?? ({} as IFoodConfig)
  const urls = payload.urls

  const [clientId, setClientId] = useState(cfg.client_id ?? '')
  const [clientSecret, setClientSecret] = useState('')
  const [merchantId, setMerchantId] = useState(cfg.merchant_id ?? '')
  const [sandboxMerchantId, setSandboxMerchantId] = useState(cfg.sandbox_merchant_id ?? '')
  const [environment, setEnvironment] = useState<'sandbox' | 'production'>(
    cfg.environment === 'sandbox' ? 'sandbox' : 'production'
  )
  const [isActive, setIsActive] = useState<boolean>(!!cfg.is_active)
  const [autoConfirm, setAutoConfirm] = useState<boolean>(!!cfg.auto_confirm)
  const [testingConnection, setTestingConnection] = useState(false)
  const [polling, setPolling] = useState(false)
  const [webhookCopied, setWebhookCopied] = useState(false)

  const [merchantStatus, setMerchantStatus] = useState<{ status: string; is_open: boolean } | null>(
    () => (typeof window !== 'undefined' ? (window.__ADMIN_STORE_IFOOD_CONFIG__?.merchant_status ?? null) : null)
  )
  const [refreshingStatus, setRefreshingStatus] = useState(false)

  useEffect(() => {
    if (payload.flash?.error) showToast(payload.flash.error, 'error')
    if (payload.flash?.success) showToast(payload.flash.success, 'success')
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  useEffect(() => {
    if (!payload.is_connected || !urls?.status) return
    const id = setInterval(fetchMerchantStatus, 60_000)
    return () => clearInterval(id)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  function handleSubmit(e: FormEvent) {
    if (!clientId.trim()) {
      e.preventDefault()
      showToast('Informe o Client ID do iFood.', 'error')
      return
    }
    if (!cfg.has_client_secret && !clientSecret.trim()) {
      e.preventDefault()
      showToast('Informe o Client Secret do iFood.', 'error')
    }
  }

  async function testConnection() {
    setTestingConnection(true)
    try {
      const formData = new FormData()
      formData.append('csrf_token', getCsrfToken())
      const res = await fetch(urls.test_connection, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean; message?: string; error?: string } | null
      if (data?.success) {
        showToast(data.message || 'Conexão estabelecida com sucesso!', 'success')
      } else {
        showToast(data?.error || data?.message || 'Falha ao testar conexão.', 'error')
      }
    } catch {
      showToast('Falha de rede ao testar conexão.', 'error')
    } finally {
      setTestingConnection(false)
    }
  }

  function copyWebhookUrl() {
    if (!payload.webhook_url) return
    navigator.clipboard.writeText(payload.webhook_url).then(() => {
      setWebhookCopied(true)
      setTimeout(() => setWebhookCopied(false), 2000)
    })
  }

  async function pollOrders() {
    setPolling(true)
    try {
      const formData = new FormData()
      formData.append('csrf_token', getCsrfToken())
      const res = await fetch(urls.poll, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean; count?: number; message?: string } | null
      if (data?.success) {
        showToast(data.message || `Pedidos sincronizados (${data.count ?? 0} novos).`, 'success')
      } else {
        showToast(data?.message || 'Falha ao buscar pedidos.', 'error')
      }
    } catch {
      showToast('Falha de rede ao buscar pedidos.', 'error')
    } finally {
      setPolling(false)
    }
  }

  async function clearError() {
    try {
      const formData = new FormData()
      formData.append('csrf_token', getCsrfToken())
      await fetch(urls.clear_error, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      showToast('Aviso de erro removido.', 'success')
      window.location.reload()
    } catch {
      showToast('Falha ao limpar erro.', 'error')
    }
  }

  async function fetchMerchantStatus() {
    if (!urls?.status) return
    setRefreshingStatus(true)
    try {
      const res = await fetch(urls.status, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const data = (await res.json().catch(() => null)) as { merchant_status?: { status: string; is_open: boolean } | null } | null
      if (data && Object.prototype.hasOwnProperty.call(data, 'merchant_status')) {
        setMerchantStatus(data.merchant_status ?? null)
      }
    } catch {
      // silently ignore
    } finally {
      setRefreshingStatus(false)
    }
  }

  return (
    <AdminStorePageShell section="ifood">
      <AdminPageHeader
        title="Integração iFood"
        description="Conecte sua loja ao iFood para sincronizar pedidos automaticamente."
        icon={
          <span
            className="inline-flex h-5 w-5 items-center justify-center rounded-md text-white text-xs font-bold"
            style={{ background: '#EA1D2C' }}
          >
            iF
          </span>
        }
        actions={
          <div className="flex flex-wrap items-center gap-2">
            <Button asChild variant="outline" size="sm" className="gap-1.5">
              <a href={urls.orders}>
                <ShoppingBag className="h-3.5 w-3.5" />
                Pedidos iFood
              </a>
            </Button>
            {urls.reviews && (
              <Button asChild variant="outline" size="sm" className="gap-1.5">
                <a href={urls.reviews}>
                  <ShoppingBag className="h-3.5 w-3.5" />
                  Avaliações
                </a>
              </Button>
            )}
            {urls.logs && (
              <Button asChild variant="outline" size="sm" className="gap-1.5">
                <a href={urls.logs}>
                  <Plug className="h-3.5 w-3.5" />
                  Logs API
                </a>
              </Button>
            )}
            {payload.is_connected && (
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={pollOrders}
                disabled={polling}
                className="gap-1.5"
              >
                <RefreshCw className={`h-3.5 w-3.5 ${polling ? 'animate-spin' : ''}`} />
                {polling ? 'Sincronizando...' : 'Sincronizar agora'}
              </Button>
            )}
          </div>
        }
      />

      {/* Connection status */}
      <section className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <div className="flex items-center gap-2 mb-1">
            <Plug className="h-4 w-4 text-zinc-500" />
            <p className="text-xs text-zinc-500">Conexão</p>
          </div>
          {payload.is_connected ? (
            <Badge className="bg-emerald-100 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 gap-1">
              <CheckCircle2 className="h-3 w-3" />
              Conectado
            </Badge>
          ) : (
            <Badge className="bg-zinc-100 text-zinc-700 border border-zinc-200 hover:bg-zinc-100">
              Não conectado
            </Badge>
          )}
          {cfg.last_sync_at && (
            <p className="mt-2 text-[11px] text-zinc-500">Última sync: {cfg.last_sync_at}</p>
          )}
        </div>

        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <div className="flex items-center gap-2 mb-1">
            <Zap className="h-4 w-4 text-zinc-500" />
            <p className="text-xs text-zinc-500">Integração</p>
          </div>
          {cfg.is_active ? (
            <Badge className="bg-amber-100 text-amber-800 border border-amber-200 hover:bg-amber-100 gap-1">
              <Zap className="h-3 w-3" />
              Ativa
            </Badge>
          ) : (
            <Badge className="bg-zinc-100 text-zinc-700 border border-zinc-200 hover:bg-zinc-100">
              Desativada
            </Badge>
          )}
          {cfg.auto_confirm && (
            <p className="mt-2 text-[11px] text-zinc-500">Auto-confirmação ativa</p>
          )}
        </div>

        {(merchantStatus !== null || payload.is_connected) && (
          <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
            <div className="flex items-center justify-between mb-1">
              <div className="flex items-center gap-2">
                <StoreIcon className="h-4 w-4 text-zinc-500" />
                <p className="text-xs text-zinc-500">Status do merchant</p>
              </div>
              <button
                type="button"
                onClick={fetchMerchantStatus}
                disabled={refreshingStatus}
                title="Atualizar status"
                className="text-zinc-400 hover:text-zinc-600 disabled:opacity-40 transition"
              >
                <RefreshCw className={`h-3.5 w-3.5 ${refreshingStatus ? 'animate-spin' : ''}`} />
              </button>
            </div>
            {merchantStatus === null ? (
              <Badge className="bg-zinc-100 text-zinc-500 border border-zinc-200 hover:bg-zinc-100">
                Indisponível
              </Badge>
            ) : merchantStatus.is_open ? (
              <Badge className="bg-emerald-100 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 gap-1">
                <CheckCircle2 className="h-3 w-3" />
                Loja aberta
              </Badge>
            ) : (
              <Badge className="bg-red-100 text-red-700 border border-red-200 hover:bg-red-100 gap-1">
                <CircleAlert className="h-3 w-3" />
                Loja fechada
              </Badge>
            )}
            {merchantStatus?.status && (
              <p className="mt-2 text-[11px] text-zinc-500">{merchantStatus.status}</p>
            )}
          </div>
        )}
      </section>

      {/* Last error alert */}
      {cfg.last_error && (
        <section className="rounded-2xl border border-red-300 bg-red-50/70 p-4">
          <div className="flex items-start gap-3">
            <AlertTriangle className="h-5 w-5 text-red-600 mt-0.5 shrink-0" />
            <div className="flex-1 min-w-0">
              <p className="text-sm font-semibold text-red-900">Último erro reportado</p>
              <p className="text-xs text-red-700 mt-0.5 break-words">{cfg.last_error}</p>
            </div>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              className="text-red-700 hover:bg-red-100"
              onClick={clearError}
            >
              Limpar
            </Button>
          </div>
        </section>
      )}

      <form action={urls.submit} method="POST" onSubmit={handleSubmit} className="space-y-5 max-w-3xl">
        <input type="hidden" name="csrf_token" value={getCsrfToken()} />

        <FormSection
          title="Ambiente"
          description="Sandbox (homologação) usa as mesmas URLs da produção, mas exige credenciais e merchant ID específicos. Use sandbox para testar sem afetar pedidos reais."
        >
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            {(['production', 'sandbox'] as const).map((env) => (
              <label
                key={env}
                className={`flex items-start gap-3 cursor-pointer rounded-xl border p-3 transition ${
                  environment === env
                    ? 'border-zinc-900 bg-zinc-50'
                    : 'border-zinc-200 hover:bg-zinc-50'
                }`}
              >
                <input
                  type="radio"
                  name="environment"
                  value={env}
                  checked={environment === env}
                  onChange={() => setEnvironment(env)}
                  className="mt-1"
                />
                <span className="flex-1 text-sm">
                  <span className="font-medium text-zinc-800">
                    {env === 'production' ? 'Produção' : 'Sandbox (homologação)'}
                  </span>
                  <span className="block text-xs text-zinc-500 mt-0.5">
                    {env === 'production'
                      ? 'Pedidos reais. Use credenciais de produção.'
                      : 'Ambiente de teste do iFood. Pedidos não são reais.'}
                  </span>
                </span>
              </label>
            ))}
          </div>
        </FormSection>

        <FormSection
          title="Credenciais da API iFood"
          description={
            <span>
              Obtenha no <a href="https://developer.ifood.com.br" target="_blank" rel="noreferrer" className="text-blue-600 underline">portal de desenvolvedores do iFood</a>. Suas credenciais são armazenadas criptografadas.
            </span>
          }
        >
          <FormField label="Client ID" htmlFor="if-cid" required>
            <div className="relative">
              <Key className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" />
              <Input
                id="if-cid"
                name="client_id"
                value={clientId}
                onChange={(e) => setClientId(e.target.value)}
                placeholder="ex.: 1a2b3c4d-..."
                className="pl-9 font-mono text-sm"
                autoComplete="off"
              />
            </div>
          </FormField>

          <FormField
            label="Client Secret"
            htmlFor="if-csec"
            required={!cfg.has_client_secret}
            hint={cfg.has_client_secret ? 'Deixe em branco para manter o atual.' : 'Necessário na primeira configuração.'}
          >
            <div className="relative">
              <Key className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" />
              <Input
                id="if-csec"
                name="client_secret"
                type="password"
                value={clientSecret}
                onChange={(e) => setClientSecret(e.target.value)}
                placeholder={cfg.has_client_secret ? cfg.client_secret_masked : 'Cole o Client Secret aqui'}
                className="pl-9 font-mono text-sm"
                autoComplete="new-password"
              />
            </div>
          </FormField>

          {cfg.client_id && (
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={testConnection}
              disabled={testingConnection}
              className="gap-1.5 w-fit"
            >
              <Plug className={`h-3.5 w-3.5 ${testingConnection ? 'animate-pulse' : ''}`} />
              {testingConnection ? 'Testando...' : 'Testar conexão'}
            </Button>
          )}
        </FormSection>

        <FormSection
          title="Merchant (Loja)"
          description="O merchant ID que vai receber os pedidos depende do ambiente selecionado. Mantemos os dois lados em campos separados para você poder alternar sem perder configuração."
        >
          {payload.merchants.length > 0 && environment === 'production' ? (
            <FormField label="Merchant ID (Produção)" htmlFor="if-merchant" required>
              <select
                id="if-merchant"
                name="merchant_id"
                value={merchantId}
                onChange={(e) => setMerchantId(e.target.value)}
                className="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
              >
                <option value="">Selecione…</option>
                {payload.merchants.map((m) => (
                  <option key={m.id} value={m.id}>
                    {m.name} {m.corporate_name && m.corporate_name !== m.name ? `— ${m.corporate_name}` : ''}
                  </option>
                ))}
              </select>
            </FormField>
          ) : (
            <FormField
              label="Merchant ID (Produção)"
              htmlFor="if-merchant-text"
              hint="Preencha manualmente ou conecte com credenciais de produção para listar."
            >
              <Input
                id="if-merchant-text"
                name="merchant_id"
                value={merchantId}
                onChange={(e) => setMerchantId(e.target.value)}
                placeholder="UUID do merchant de produção"
                className="font-mono text-sm"
              />
            </FormField>
          )}

          <FormField
            label="Merchant ID (Sandbox)"
            htmlFor="if-merchant-sb"
            hint="Opcional. Use o merchant de homologação que o iFood te forneceu para testes."
          >
            <Input
              id="if-merchant-sb"
              name="sandbox_merchant_id"
              value={sandboxMerchantId}
              onChange={(e) => setSandboxMerchantId(e.target.value)}
              placeholder="UUID do merchant sandbox (vazio se ainda não tem)"
              className="font-mono text-sm"
            />
          </FormField>

          <p className="text-xs text-zinc-500">
            Ambiente atual: <strong className={environment === 'sandbox' ? 'text-amber-700' : 'text-emerald-700'}>{environment === 'sandbox' ? 'SANDBOX' : 'PRODUÇÃO'}</strong>.
            Será usado o merchant ID de {environment === 'sandbox' ? 'sandbox' : 'produção'} nas chamadas.
          </p>
        </FormSection>

        <FormSection title="Comportamento">
          <label className="flex items-start gap-3 cursor-pointer rounded-xl border border-zinc-200 p-3 hover:bg-zinc-50">
            <input
              type="checkbox"
              name="is_active"
              checked={isActive}
              onChange={(e) => setIsActive(e.target.checked)}
              className="peer sr-only"
            />
            <span className="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
              <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
            </span>
            <span className="flex-1 text-sm">
              <span className="font-medium text-zinc-800">{isActive ? 'Integração ativa' : 'Integração pausada'}</span>
              <span className="block text-xs text-zinc-500 mt-0.5">
                Quando ativa, o sistema busca novos pedidos do iFood automaticamente.
              </span>
            </span>
          </label>

          <label className="flex items-start gap-3 cursor-pointer rounded-xl border border-zinc-200 p-3 hover:bg-zinc-50">
            <input
              type="checkbox"
              name="auto_confirm"
              checked={autoConfirm}
              onChange={(e) => setAutoConfirm(e.target.checked)}
              className="peer sr-only"
            />
            <span className="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
              <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
            </span>
            <span className="flex-1 text-sm">
              <span className="font-medium text-zinc-800">
                {autoConfirm ? 'Confirmar pedidos automaticamente' : 'Confirmação manual'}
              </span>
              <span className="block text-xs text-zinc-500 mt-0.5">
                Auto-confirm aceita pedidos sem ação do operador. Use com cautela em horários de pico.
              </span>
            </span>
          </label>
        </FormSection>

        {payload.webhook_url && (
          <FormSection
            title="Webhook"
            description="Configure esta URL no portal de desenvolvedores do iFood para receber eventos de pedidos em tempo real."
          >
            <div className="rounded-xl border border-zinc-200 bg-zinc-50 p-4">
              <div className="flex items-center gap-2 mb-2">
                <LinkIcon className="h-4 w-4 text-zinc-500 shrink-0" />
                <span className="text-sm font-medium text-zinc-700">URL do Webhook</span>
              </div>
              <div className="flex items-center gap-2">
                <code className="flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-mono text-zinc-800 break-all select-all">
                  {payload.webhook_url}
                </code>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={copyWebhookUrl}
                  className="shrink-0 gap-1.5"
                >
                  {webhookCopied ? (
                    <>
                      <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600" />
                      Copiado!
                    </>
                  ) : (
                    <>
                      <Copy className="h-3.5 w-3.5" />
                      Copiar
                    </>
                  )}
                </Button>
              </div>
              <p className="mt-2 text-xs text-zinc-500">
                No{' '}
                <a
                  href="https://developer.ifood.com.br"
                  target="_blank"
                  rel="noreferrer"
                  className="underline hover:text-zinc-700"
                >
                  portal do desenvolvedor iFood
                </a>
                , acesse seu app → Webhooks → adicione esta URL.
              </p>
            </div>
          </FormSection>
        )}

        <div className="sticky bottom-0 -mx-5 mt-6 border-t border-zinc-200 bg-white/95 backdrop-blur px-5 py-3">
          <div className="mx-auto flex max-w-[1292px] flex-wrap items-center justify-end gap-2">
            <Button asChild type="button" variant="ghost" className="gap-2">
              <a href="https://developer.ifood.com.br" target="_blank" rel="noreferrer">
                <ExternalLink className="h-4 w-4" />
                Portal iFood
              </a>
            </Button>
            <Button type="submit" className="gap-2">
              <Save className="h-4 w-4" />
              Salvar configurações
            </Button>
          </div>
        </div>
      </form>
    </AdminStorePageShell>
  )
}
