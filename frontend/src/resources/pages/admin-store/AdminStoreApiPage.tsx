import { useState, type FormEvent } from 'react'
import {
  Activity,
  AlertTriangle,
  Calendar,
  CheckCircle2,
  Code2,
  Copy,
  Eye,
  EyeOff,
  Key,
  Loader2,
  Plus,
  Server,
  Shield,
  Trash2,
  TrendingUp,
  Zap,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  AdminPageHeader,
  AdminStorePageShell,
  FormField,
  FormSection,
  getCsrfToken,
  showToast,
  useStoreContext,
} from '@/components/admin-store'

type ApiTokenRow = {
  id: number
  access_token: string
  jwt_preview: string | null
  scopes: string[]
  expires_at: string | null
  created_at: string | null
}

type ApiKeyRow = {
  id: number
  name: string
  key_preview: string | null
  scopes: string[]
  expires_at: string | null
  created_at: string | null
  is_active: boolean
  revoked_at: string | null
}

type EndpointEntry = { method: string; path: string; description: string }
type TopEndpoint = { endpoint: string; count: number }

type Payload = {
  company_name: string
  user_name: string
  tokens: ApiTokenRow[]
  api_keys: ApiKeyRow[]
  stats: {
    requests_today: number
    total_requests: number
    top_endpoints: TopEndpoint[]
  }
  endpoints: EndpointEntry[]
  base_url: string
  urls: {
    generate_token: string
    revoke_token: string
    generate_key: string
    revoke_key: string
    dashboard: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_API__?: Payload
  }
}

const SCOPE_OPTIONS = [
  { value: 'read', label: 'Leitura' },
  { value: 'write', label: 'Escrita' },
  { value: 'admin', label: 'Admin' },
]

const EXPIRES_OPTIONS = [
  { value: 3600, label: '1 hora' },
  { value: 86400, label: '24 horas' },
  { value: 604800, label: '7 dias' },
  { value: 2592000, label: '30 dias' },
]

function formatDateBr(s: string | null | undefined): string {
  if (!s) return '—'
  const d = new Date(String(s).replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return String(s)
  const dd = String(d.getDate()).padStart(2, '0')
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const yy = d.getFullYear()
  const hh = String(d.getHours()).padStart(2, '0')
  const mi = String(d.getMinutes()).padStart(2, '0')
  return `${dd}/${mm}/${yy} ${hh}:${mi}`
}

function tokenStatus(expiresAt: string | null, isActive = true, revokedAt: string | null = null) {
  if (!isActive || revokedAt) return { label: 'Revogado', cls: 'bg-red-100 text-red-700 border-red-200' }
  if (expiresAt && new Date(String(expiresAt).replace(' ', 'T')).getTime() < Date.now()) {
    return { label: 'Expirado', cls: 'bg-amber-100 text-amber-700 border-amber-200' }
  }
  return { label: 'Ativo', cls: 'bg-emerald-100 text-emerald-700 border-emerald-200' }
}

function methodColor(method: string): string {
  switch (method.toUpperCase()) {
    case 'GET':
      return 'bg-blue-100 text-blue-700 border-blue-200'
    case 'POST':
      return 'bg-emerald-100 text-emerald-700 border-emerald-200'
    case 'PUT':
    case 'PATCH':
      return 'bg-amber-100 text-amber-700 border-amber-200'
    case 'DELETE':
      return 'bg-red-100 text-red-700 border-red-200'
    default:
      return 'bg-zinc-100 text-zinc-700 border-zinc-200'
  }
}

export default function AdminStoreApiPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_API__) || ({} as Payload)
  const urls = payload.urls

  const [tokens, setTokens] = useState<ApiTokenRow[]>(payload.tokens || [])
  const [apiKeys, setApiKeys] = useState<ApiKeyRow[]>(payload.api_keys || [])

  // Token form state
  const [tokenScopes, setTokenScopes] = useState<string[]>(['read', 'write'])
  const [tokenExpiresIn, setTokenExpiresIn] = useState<number>(86400)
  const [generatingToken, setGeneratingToken] = useState(false)
  const [newToken, setNewToken] = useState<string | null>(null)
  const [showNewToken, setShowNewToken] = useState(false)

  // API key form state
  const [keyName, setKeyName] = useState('')
  const [keyScopes, setKeyScopes] = useState<string[]>(['read'])
  const [keyExpiresAt, setKeyExpiresAt] = useState<string>('')
  const [generatingKey, setGeneratingKey] = useState(false)
  const [newApiKey, setNewApiKey] = useState<string | null>(null)
  const [showNewKey, setShowNewKey] = useState(false)

  const [revokingId, setRevokingId] = useState<string | null>(null)

  function toggleScope(scope: string, current: string[], setter: (v: string[]) => void) {
    setter(current.includes(scope) ? current.filter((s) => s !== scope) : [...current, scope])
  }

  async function copy(text: string, label: string) {
    try {
      await navigator.clipboard?.writeText(text)
      showToast(`${label} copiado!`, 'success')
    } catch {
      showToast('Falha ao copiar.', 'error')
    }
  }

  async function generateToken(e: FormEvent) {
    e.preventDefault()
    if (tokenScopes.length === 0) {
      showToast('Selecione pelo menos um escopo.', 'error')
      return
    }
    setGeneratingToken(true)
    try {
      const res = await fetch(urls.generate_token, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json; charset=utf-8',
          Accept: 'application/json',
          'X-CSRF-Token': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ expires_in: tokenExpiresIn, scopes: tokenScopes }),
      })
      const data = (await res.json().catch(() => null)) as
        | { success?: boolean; message?: string; data?: { token: string; expires_in: number; scopes: string[] } }
        | null
      if (data?.success && data.data?.token) {
        setNewToken(data.data.token)
        setShowNewToken(true)
        showToast(data.message || 'Token gerado.', 'success')
        // Optimistic update — refresh after a short pause
        setTimeout(() => window.location.reload(), 4000)
      } else {
        showToast(data?.message || 'Falha ao gerar token.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setGeneratingToken(false)
    }
  }

  async function revokeToken(tokenId: number) {
    if (!window.confirm('Revogar este token JWT? Esta ação é irreversível.')) return
    setRevokingId(`token:${tokenId}`)
    try {
      const res = await fetch(urls.revoke_token, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json; charset=utf-8',
          Accept: 'application/json',
          'X-CSRF-Token': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ token_id: tokenId }),
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean; message?: string } | null
      if (data?.success) {
        setTokens((t) => t.filter((row) => row.id !== tokenId))
        showToast(data.message || 'Token revogado.', 'success')
      } else {
        showToast(data?.message || 'Falha ao revogar.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setRevokingId(null)
    }
  }

  async function generateApiKey(e: FormEvent) {
    e.preventDefault()
    if (keyScopes.length === 0) {
      showToast('Selecione pelo menos um escopo.', 'error')
      return
    }
    setGeneratingKey(true)
    try {
      const body: Record<string, unknown> = {
        name: keyName.trim() || undefined,
        scopes: keyScopes,
      }
      if (keyExpiresAt) body.expires_at = keyExpiresAt
      const res = await fetch(urls.generate_key, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json; charset=utf-8',
          Accept: 'application/json',
          'X-CSRF-Token': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(body),
      })
      const data = (await res.json().catch(() => null)) as
        | { success?: boolean; message?: string; data?: { api_key: string; name: string; scopes: string[] } }
        | null
      if (data?.success && data.data?.api_key) {
        setNewApiKey(data.data.api_key)
        setShowNewKey(true)
        setKeyName('')
        showToast(data.message || 'API Key gerada.', 'success')
        setTimeout(() => window.location.reload(), 4000)
      } else {
        showToast(data?.message || 'Falha ao gerar API Key.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setGeneratingKey(false)
    }
  }

  async function revokeApiKey(keyId: number) {
    if (!window.confirm('Revogar esta API Key? Esta ação é irreversível.')) return
    setRevokingId(`key:${keyId}`)
    try {
      const res = await fetch(urls.revoke_key, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json; charset=utf-8',
          Accept: 'application/json',
          'X-CSRF-Token': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ key_id: keyId }),
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean; message?: string } | null
      if (data?.success) {
        setApiKeys((k) =>
          k.map((row) =>
            row.id === keyId ? { ...row, is_active: false, revoked_at: new Date().toISOString() } : row,
          ),
        )
        showToast(data.message || 'API Key revogada.', 'success')
      } else {
        showToast(data?.message || 'Falha ao revogar.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setRevokingId(null)
    }
  }

  const stats = payload.stats || { requests_today: 0, total_requests: 0, top_endpoints: [] }

  return (
    <AdminStorePageShell section="settings">
      <AdminPageHeader
        title="Gerenciamento de API"
        description={`Tokens JWT e API Keys para integrar com ${payload.company_name || 'sua empresa'}.`}
        icon={<Code2 className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <div className="flex flex-wrap items-center gap-2 text-xs text-zinc-500">
            <span className="inline-flex items-center gap-1.5">
              <Server className="h-3.5 w-3.5" />
              <code className="font-mono text-zinc-700 bg-zinc-100 px-2 py-0.5 rounded">
                {payload.base_url}
              </code>
            </span>
          </div>
        }
      />

      {/* Stats */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-xs font-medium text-zinc-500">Requisições hoje</p>
              <p className="text-2xl font-bold text-blue-600 mt-0.5">
                {stats.requests_today.toLocaleString('pt-BR')}
              </p>
            </div>
            <div className="rounded-xl bg-blue-100 p-2.5">
              <Zap className="h-5 w-5 text-blue-600" />
            </div>
          </div>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-xs font-medium text-zinc-500">Total acumulado</p>
              <p className="text-2xl font-bold text-emerald-600 mt-0.5">
                {stats.total_requests.toLocaleString('pt-BR')}
              </p>
            </div>
            <div className="rounded-xl bg-emerald-100 p-2.5">
              <TrendingUp className="h-5 w-5 text-emerald-600" />
            </div>
          </div>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-xs font-medium text-zinc-500">Endpoints disponíveis</p>
              <p className="text-2xl font-bold text-zinc-900 mt-0.5">{payload.endpoints?.length ?? 0}</p>
            </div>
            <div className="rounded-xl bg-zinc-100 p-2.5">
              <Activity className="h-5 w-5 text-zinc-700" />
            </div>
          </div>
        </div>
      </div>

      {/* New credentials reveal */}
      {(newToken || newApiKey) && (
        <section className="rounded-2xl border border-amber-300 bg-amber-50 p-4 shadow-sm">
          <div className="flex items-start gap-3">
            <AlertTriangle className="h-5 w-5 text-amber-600 shrink-0 mt-0.5" />
            <div className="flex-1 min-w-0">
              <h3 className="text-sm font-semibold text-amber-900">
                Copie o {newToken ? 'token' : 'API Key'} agora — esta é a única vez que será exibido!
              </h3>
              <p className="text-xs text-amber-800 mt-0.5">
                Após sair desta tela, somente o hash ficará armazenado por segurança.
              </p>
              <div className="mt-3 flex items-center gap-2">
                <code className="flex-1 min-w-0 truncate rounded-md border border-amber-300 bg-white px-3 py-2 text-xs font-mono text-zinc-700">
                  {(newToken && showNewToken) || (newApiKey && showNewKey)
                    ? (newToken || newApiKey)
                    : (newToken ? newToken.replace(/./g, '•') : (newApiKey || '').replace(/./g, '•'))}
                </code>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={() => {
                    if (newToken) setShowNewToken((v) => !v)
                    else setShowNewKey((v) => !v)
                  }}
                  className="gap-1.5"
                >
                  {(newToken ? showNewToken : showNewKey) ? (
                    <EyeOff className="h-3.5 w-3.5" />
                  ) : (
                    <Eye className="h-3.5 w-3.5" />
                  )}
                </Button>
                <Button
                  type="button"
                  size="sm"
                  onClick={() => copy((newToken || newApiKey || ''), newToken ? 'Token' : 'API Key')}
                  className="gap-1.5"
                >
                  <Copy className="h-3.5 w-3.5" />
                  Copiar
                </Button>
              </div>
            </div>
          </div>
        </section>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {/* JWT TOKENS */}
        <section className="rounded-2xl border border-zinc-200 bg-white shadow-sm">
          <div className="border-b border-zinc-100 px-5 py-3">
            <div className="flex items-center justify-between">
              <h2 className="text-sm font-semibold text-zinc-900 inline-flex items-center gap-2">
                <Shield className="h-4 w-4 text-blue-600" />
                JWT Tokens
              </h2>
              <Badge variant="secondary">{tokens.length}</Badge>
            </div>
            <p className="mt-0.5 text-xs text-zinc-500">
              Tokens de curta duração para autenticar requisições.
            </p>
          </div>

          <div className="p-5">
            <form onSubmit={generateToken} className="space-y-3">
              <FormSection
                title="Gerar novo token"
                description="Escolha escopo e expiração."
              >
                <FormField label="Escopos" htmlFor="token-scopes" hint="Selecione os escopos que este token deve ter.">
                  <div className="flex flex-wrap gap-1.5">
                    {SCOPE_OPTIONS.map((opt) => {
                      const active = tokenScopes.includes(opt.value)
                      return (
                        <button
                          key={opt.value}
                          type="button"
                          onClick={() => toggleScope(opt.value, tokenScopes, setTokenScopes)}
                          className={`inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-medium transition-colors ${
                            active
                              ? 'bg-blue-100 text-blue-700 border-blue-200'
                              : 'bg-white text-zinc-600 border-zinc-200 hover:bg-zinc-50'
                          }`}
                        >
                          {active && <CheckCircle2 className="h-3 w-3" />}
                          {opt.label}
                        </button>
                      )
                    })}
                  </div>
                </FormField>
                <FormField label="Expira em" htmlFor="token-expires">
                  <select
                    id="token-expires"
                    value={tokenExpiresIn}
                    onChange={(e) => setTokenExpiresIn(Number(e.target.value))}
                    className="w-full h-9 rounded-md border border-zinc-200 bg-white px-3 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
                  >
                    {EXPIRES_OPTIONS.map((o) => (
                      <option key={o.value} value={o.value}>
                        {o.label}
                      </option>
                    ))}
                  </select>
                </FormField>
              </FormSection>
              <Button type="submit" disabled={generatingToken} className="w-full gap-2">
                {generatingToken ? <Loader2 className="h-4 w-4 animate-spin" /> : <Plus className="h-4 w-4" />}
                {generatingToken ? 'Gerando...' : 'Gerar JWT Token'}
              </Button>
            </form>

            {tokens.length > 0 && (
              <div className="mt-4 space-y-2">
                <p className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                  Tokens recentes
                </p>
                {tokens.map((t) => {
                  const st = tokenStatus(t.expires_at, true, null)
                  const busy = revokingId === `token:${t.id}`
                  return (
                    <div
                      key={t.id}
                      className="flex items-start justify-between gap-3 rounded-xl border border-zinc-200 bg-zinc-50/50 p-3"
                    >
                      <div className="flex-1 min-w-0 space-y-1">
                        <div className="flex flex-wrap items-center gap-1.5">
                          <span
                            className={`inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-medium ${st.cls}`}
                          >
                            {st.label}
                          </span>
                          {t.scopes.map((s) => (
                            <Badge key={s} variant="outline" className="text-[10px] font-normal">
                              {s}
                            </Badge>
                          ))}
                        </div>
                        <p className="text-[11px] text-zinc-500 inline-flex items-center gap-1">
                          <Calendar className="h-3 w-3" />
                          Criado: {formatDateBr(t.created_at)}
                          {t.expires_at && (
                            <>
                              <span className="mx-1">·</span>
                              Expira: {formatDateBr(t.expires_at)}
                            </>
                          )}
                        </p>
                        {t.jwt_preview && (
                          <code className="block truncate text-[10px] font-mono text-zinc-600">
                            {t.jwt_preview}
                          </code>
                        )}
                      </div>
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => revokeToken(t.id)}
                        disabled={busy}
                        className="h-8 w-8 p-0 text-red-600 hover:bg-red-50"
                        title="Revogar"
                      >
                        {busy ? <Loader2 className="h-3.5 w-3.5 animate-spin" /> : <Trash2 className="h-3.5 w-3.5" />}
                      </Button>
                    </div>
                  )
                })}
              </div>
            )}
          </div>
        </section>

        {/* API KEYS */}
        <section className="rounded-2xl border border-zinc-200 bg-white shadow-sm">
          <div className="border-b border-zinc-100 px-5 py-3">
            <div className="flex items-center justify-between">
              <h2 className="text-sm font-semibold text-zinc-900 inline-flex items-center gap-2">
                <Key className="h-4 w-4 text-emerald-600" />
                API Keys
              </h2>
              <Badge variant="secondary">{apiKeys.length}</Badge>
            </div>
            <p className="mt-0.5 text-xs text-zinc-500">
              Chaves persistentes para integrações servidor-a-servidor.
            </p>
          </div>

          <div className="p-5">
            <form onSubmit={generateApiKey} className="space-y-3">
              <FormSection
                title="Gerar nova API Key"
                description="Identifique e configure permissões."
              >
                <FormField label="Nome (opcional)" htmlFor="key-name">
                  <Input
                    id="key-name"
                    value={keyName}
                    onChange={(e) => setKeyName(e.target.value)}
                    placeholder="Ex.: Integração contábil"
                  />
                </FormField>
                <FormField label="Escopos" htmlFor="key-scopes">
                  <div className="flex flex-wrap gap-1.5">
                    {SCOPE_OPTIONS.map((opt) => {
                      const active = keyScopes.includes(opt.value)
                      return (
                        <button
                          key={opt.value}
                          type="button"
                          onClick={() => toggleScope(opt.value, keyScopes, setKeyScopes)}
                          className={`inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-medium transition-colors ${
                            active
                              ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
                              : 'bg-white text-zinc-600 border-zinc-200 hover:bg-zinc-50'
                          }`}
                        >
                          {active && <CheckCircle2 className="h-3 w-3" />}
                          {opt.label}
                        </button>
                      )
                    })}
                  </div>
                </FormField>
                <FormField label="Expira em (opcional)" htmlFor="key-expires" hint="Deixe em branco para chave sem expiração.">
                  <Input
                    id="key-expires"
                    type="date"
                    value={keyExpiresAt}
                    onChange={(e) => setKeyExpiresAt(e.target.value)}
                  />
                </FormField>
              </FormSection>
              <Button type="submit" disabled={generatingKey} className="w-full gap-2">
                {generatingKey ? <Loader2 className="h-4 w-4 animate-spin" /> : <Plus className="h-4 w-4" />}
                {generatingKey ? 'Gerando...' : 'Gerar API Key'}
              </Button>
            </form>

            {apiKeys.length > 0 && (
              <div className="mt-4 space-y-2">
                <p className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                  API Keys
                </p>
                {apiKeys.map((k) => {
                  const st = tokenStatus(k.expires_at, k.is_active, k.revoked_at)
                  const busy = revokingId === `key:${k.id}`
                  return (
                    <div
                      key={k.id}
                      className="flex items-start justify-between gap-3 rounded-xl border border-zinc-200 bg-zinc-50/50 p-3"
                    >
                      <div className="flex-1 min-w-0 space-y-1">
                        <div className="flex flex-wrap items-center gap-1.5">
                          <p className="text-sm font-medium text-zinc-900">
                            {k.name || `Key #${k.id}`}
                          </p>
                          <span
                            className={`inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-medium ${st.cls}`}
                          >
                            {st.label}
                          </span>
                        </div>
                        <div className="flex flex-wrap gap-1">
                          {k.scopes.map((s) => (
                            <Badge key={s} variant="outline" className="text-[10px] font-normal">
                              {s}
                            </Badge>
                          ))}
                        </div>
                        <p className="text-[11px] text-zinc-500 inline-flex items-center gap-1">
                          <Calendar className="h-3 w-3" />
                          Criada: {formatDateBr(k.created_at)}
                          {k.expires_at && (
                            <>
                              <span className="mx-1">·</span>
                              Expira: {formatDateBr(k.expires_at)}
                            </>
                          )}
                        </p>
                      </div>
                      {k.is_active && !k.revoked_at && (
                        <Button
                          type="button"
                          variant="ghost"
                          size="sm"
                          onClick={() => revokeApiKey(k.id)}
                          disabled={busy}
                          className="h-8 w-8 p-0 text-red-600 hover:bg-red-50"
                          title="Revogar"
                        >
                          {busy ? (
                            <Loader2 className="h-3.5 w-3.5 animate-spin" />
                          ) : (
                            <Trash2 className="h-3.5 w-3.5" />
                          )}
                        </Button>
                      )}
                    </div>
                  )
                })}
              </div>
            )}
          </div>
        </section>
      </div>

      {/* Top endpoints */}
      {stats.top_endpoints.length > 0 && (
        <section className="rounded-2xl border border-zinc-200 bg-white shadow-sm">
          <div className="border-b border-zinc-100 px-5 py-3">
            <h2 className="text-sm font-semibold text-zinc-900 inline-flex items-center gap-2">
              <TrendingUp className="h-4 w-4 text-zinc-500" />
              Endpoints mais usados (últimos 7 dias)
            </h2>
          </div>
          <div className="divide-y divide-zinc-100">
            {stats.top_endpoints.map((e) => (
              <div key={e.endpoint} className="flex items-center justify-between px-5 py-2.5">
                <code className="text-xs font-mono text-zinc-700 truncate max-w-md">{e.endpoint}</code>
                <Badge variant="secondary">{e.count.toLocaleString('pt-BR')} req</Badge>
              </div>
            ))}
          </div>
        </section>
      )}

      {/* Endpoints reference */}
      <section className="rounded-2xl border border-zinc-200 bg-white shadow-sm">
        <div className="border-b border-zinc-100 px-5 py-3">
          <h2 className="text-sm font-semibold text-zinc-900 inline-flex items-center gap-2">
            <Code2 className="h-4 w-4 text-zinc-500" />
            Endpoints disponíveis
          </h2>
          <p className="mt-0.5 text-xs text-zinc-500">
            Base URL: <code className="font-mono text-zinc-700 bg-zinc-100 px-1.5 py-0.5 rounded">{payload.base_url}</code>
          </p>
        </div>
        <div className="divide-y divide-zinc-100">
          {payload.endpoints.map((ep, i) => (
            <div key={`${ep.method}-${ep.path}-${i}`} className="flex flex-wrap items-center gap-3 px-5 py-2.5">
              <span
                className={`inline-flex items-center rounded-md border px-2 py-0.5 text-[10px] font-semibold uppercase ${methodColor(
                  ep.method,
                )}`}
              >
                {ep.method}
              </span>
              <code className="text-xs font-mono text-zinc-800 truncate">{ep.path}</code>
              <span className="ml-auto text-xs text-zinc-500">{ep.description}</span>
            </div>
          ))}
        </div>
      </section>
    </AdminStorePageShell>
  )
}
