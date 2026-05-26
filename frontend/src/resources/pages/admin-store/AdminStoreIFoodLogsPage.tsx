import { useMemo, useState, type FormEvent } from 'react'
import {
  ArrowLeft,
  ChevronLeft,
  ChevronRight,
  Filter,
  Plug,
  X,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  AdminPageHeader,
  AdminStorePageShell,
  useStoreContext,
} from '@/components/admin-store'

// ── Types ─────────────────────────────────────────────────────────────────────

type LogRow = {
  id: number
  environment: 'sandbox' | 'production'
  module: string
  method: string
  url: string
  status: number | null
  latency_ms: number | null
  attempt: number
  error: string | null
  job_id: number | null
  created_at: string
  request_preview: string | null
  response_preview: string | null
}

type Payload = {
  logs: LogRow[]
  pagination: { page: number; per_page: number; total: number; total_pages: number }
  filters: { module: string; status: string; method: string; env: string }
  urls: { self: string; config: string }
}

declare global {
  interface Window {
    __ADMIN_STORE_IFOOD_LOGS__?: Payload
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────────

const MODULES = ['auth', 'order', 'events', 'catalog', 'review', 'shipping', 'merchant', 'logistics']
const METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']

function statusColor(status: number | null): string {
  if (status === null) return 'bg-zinc-100 text-zinc-700 border-zinc-200'
  if (status >= 200 && status < 300) return 'bg-emerald-100 text-emerald-700 border-emerald-200'
  if (status >= 300 && status < 400) return 'bg-blue-100 text-blue-700 border-blue-200'
  if (status >= 400 && status < 500) return 'bg-amber-100 text-amber-800 border-amber-200'
  return 'bg-red-100 text-red-700 border-red-200'
}

function formatDateBr(s: string): string {
  if (!s) return '—'
  const d = new Date(s.replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return s
  const dd = String(d.getDate()).padStart(2, '0')
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const hh = String(d.getHours()).padStart(2, '0')
  const mi = String(d.getMinutes()).padStart(2, '0')
  const ss = String(d.getSeconds()).padStart(2, '0')
  return `${dd}/${mm} ${hh}:${mi}:${ss}`
}

function truncate(s: string, max: number): string {
  return s.length <= max ? s : s.slice(0, max - 1) + '…'
}

function prettyJson(s: string | null): string {
  if (!s) return ''
  try {
    return JSON.stringify(JSON.parse(s), null, 2)
  } catch {
    return s
  }
}

// ── Main component ────────────────────────────────────────────────────────────

export default function AdminStoreIFoodLogsPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_IFOOD_LOGS__) || ({} as Payload)

  const logs = payload.logs ?? []
  const pagination = payload.pagination ?? { page: 1, per_page: 50, total: 0, total_pages: 1 }
  const filters = payload.filters ?? { module: '', status: '', method: '', env: '' }
  const urls = payload.urls ?? { self: '', config: '' }

  const [module, setModule] = useState(filters.module)
  const [status, setStatus] = useState(filters.status)
  const [method, setMethod] = useState(filters.method)
  const [env, setEnv] = useState(filters.env)
  const [expandedId, setExpandedId] = useState<number | null>(null)

  const buildUrl = useMemo(() => {
    return (page: number, overrides: Partial<typeof filters> = {}) => {
      const params = new URLSearchParams()
      const m = overrides.module ?? module
      const s = overrides.status ?? status
      const mt = overrides.method ?? method
      const e = overrides.env ?? env
      if (m) params.set('module', m)
      if (s) params.set('status', s)
      if (mt) params.set('method', mt)
      if (e) params.set('env', e)
      if (page > 1) params.set('page', String(page))
      const qs = params.toString()
      return urls.self + (qs ? `?${qs}` : '')
    }
  }, [module, status, method, env, urls.self])

  function applyFilters(e: FormEvent) {
    e.preventDefault()
    window.location.href = buildUrl(1)
  }

  function clearFilters() {
    window.location.href = urls.self
  }

  const hasFilters = !!(module || status || method || env)

  return (
    <AdminStorePageShell section="ifood">
      <AdminPageHeader
        title="Logs da API iFood"
        description={`${pagination.total.toLocaleString('pt-BR')} chamadas registradas. Cada tentativa de retry gera uma linha separada.`}
        icon={<Plug className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <Button asChild variant="outline" size="sm" className="gap-1.5">
            <a href={urls.config}>
              <ArrowLeft className="h-3.5 w-3.5" />
              Voltar para configuração
            </a>
          </Button>
        }
      />

      {/* Filters */}
      <section className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
        <form onSubmit={applyFilters} className="flex flex-wrap items-end gap-3">
          <div className="flex-1 min-w-[140px]">
            <label className="block text-xs text-zinc-500 mb-1">Módulo</label>
            <select
              value={module}
              onChange={(e) => setModule(e.target.value)}
              className="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm"
            >
              <option value="">Todos</option>
              {MODULES.map((m) => (
                <option key={m} value={m}>{m}</option>
              ))}
            </select>
          </div>

          <div className="flex-1 min-w-[140px]">
            <label className="block text-xs text-zinc-500 mb-1">HTTP status</label>
            <select
              value={status}
              onChange={(e) => setStatus(e.target.value)}
              className="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm"
            >
              <option value="">Todos</option>
              <option value="2xx">2xx (sucesso)</option>
              <option value="4xx">4xx (erro do cliente)</option>
              <option value="5xx">5xx (erro do iFood)</option>
              <option value="error">Sem resposta (rede)</option>
            </select>
          </div>

          <div className="flex-1 min-w-[120px]">
            <label className="block text-xs text-zinc-500 mb-1">Método</label>
            <select
              value={method}
              onChange={(e) => setMethod(e.target.value)}
              className="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm"
            >
              <option value="">Todos</option>
              {METHODS.map((m) => (
                <option key={m} value={m}>{m}</option>
              ))}
            </select>
          </div>

          <div className="flex-1 min-w-[140px]">
            <label className="block text-xs text-zinc-500 mb-1">Ambiente</label>
            <select
              value={env}
              onChange={(e) => setEnv(e.target.value)}
              className="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm"
            >
              <option value="">Todos</option>
              <option value="production">Produção</option>
              <option value="sandbox">Sandbox</option>
            </select>
          </div>

          <div className="flex items-center gap-2">
            <Button type="submit" size="sm" className="gap-1.5">
              <Filter className="h-3.5 w-3.5" />
              Filtrar
            </Button>
            {hasFilters && (
              <Button type="button" size="sm" variant="outline" onClick={clearFilters} className="gap-1.5">
                <X className="h-3.5 w-3.5" />
                Limpar
              </Button>
            )}
          </div>
        </form>
      </section>

      {/* Table */}
      <section className="rounded-2xl border border-zinc-200 bg-white shadow-sm overflow-hidden">
        {logs.length === 0 ? (
          <div className="p-12 text-center">
            <Plug className="mx-auto h-10 w-10 text-zinc-300" />
            <p className="mt-2 text-sm text-zinc-500">
              {hasFilters
                ? 'Nenhuma chamada encontrada com esses filtros.'
                : 'Nenhuma chamada à API registrada ainda. Quando o sistema fizer requisições ao iFood, elas aparecem aqui.'}
            </p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full text-sm">
              <thead className="bg-zinc-50 text-xs font-medium text-zinc-600 uppercase tracking-wide">
                <tr>
                  <th className="px-3 py-2 text-left">Quando</th>
                  <th className="px-3 py-2 text-left">Env</th>
                  <th className="px-3 py-2 text-left">Módulo</th>
                  <th className="px-3 py-2 text-left">Método</th>
                  <th className="px-3 py-2 text-left">URL</th>
                  <th className="px-3 py-2 text-left">Status</th>
                  <th className="px-3 py-2 text-right">Latência</th>
                  <th className="px-3 py-2 text-right">Try</th>
                  <th className="px-3 py-2 text-left">Erro</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-100">
                {logs.map((row) => {
                  const isOpen = expandedId === row.id
                  return (
                    <>
                      <tr
                        key={row.id}
                        onClick={() => setExpandedId(isOpen ? null : row.id)}
                        className="cursor-pointer hover:bg-zinc-50"
                      >
                        <td className="px-3 py-2 font-mono text-xs text-zinc-600 whitespace-nowrap">
                          {formatDateBr(row.created_at)}
                        </td>
                        <td className="px-3 py-2">
                          <Badge
                            className={`text-[10px] ${
                              row.environment === 'sandbox'
                                ? 'bg-amber-100 text-amber-800 border-amber-200'
                                : 'bg-emerald-100 text-emerald-700 border-emerald-200'
                            }`}
                          >
                            {row.environment}
                          </Badge>
                        </td>
                        <td className="px-3 py-2 text-zinc-700">{row.module}</td>
                        <td className="px-3 py-2 font-mono text-xs text-zinc-700">{row.method}</td>
                        <td className="px-3 py-2 font-mono text-xs text-zinc-700 max-w-[400px] truncate" title={row.url}>
                          {truncate(row.url.replace(/^https?:\/\/[^/]+/, ''), 60)}
                        </td>
                        <td className="px-3 py-2">
                          <span className={`inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium ${statusColor(row.status)}`}>
                            {row.status ?? 'ERR'}
                          </span>
                        </td>
                        <td className="px-3 py-2 text-right font-mono text-xs text-zinc-600">
                          {row.latency_ms !== null ? `${row.latency_ms}ms` : '—'}
                        </td>
                        <td className="px-3 py-2 text-right text-xs text-zinc-500">{row.attempt}</td>
                        <td className="px-3 py-2 text-xs text-red-600 max-w-[200px] truncate" title={row.error ?? ''}>
                          {row.error ? truncate(row.error, 40) : ''}
                        </td>
                      </tr>
                      {isOpen && (
                        <tr key={`${row.id}-detail`}>
                          <td colSpan={9} className="bg-zinc-50 px-4 py-4 border-l-2 border-zinc-300">
                            <div className="grid gap-3 md:grid-cols-2">
                              <div>
                                <p className="text-xs font-semibold text-zinc-700 mb-1">Request body (sanitizado)</p>
                                <pre className="text-[11px] font-mono bg-white border border-zinc-200 rounded p-2 max-h-[300px] overflow-auto whitespace-pre-wrap break-all">
                                  {prettyJson(row.request_preview) || '(vazio)'}
                                </pre>
                              </div>
                              <div>
                                <p className="text-xs font-semibold text-zinc-700 mb-1">Response body</p>
                                <pre className="text-[11px] font-mono bg-white border border-zinc-200 rounded p-2 max-h-[300px] overflow-auto whitespace-pre-wrap break-all">
                                  {prettyJson(row.response_preview) || '(vazio)'}
                                </pre>
                              </div>
                              <div className="md:col-span-2 text-xs text-zinc-500">
                                <strong>URL completa:</strong> <span className="font-mono break-all">{row.url}</span>
                                {row.job_id !== null && <> · <strong>Job:</strong> #{row.job_id}</>}
                              </div>
                            </div>
                          </td>
                        </tr>
                      )}
                    </>
                  )
                })}
              </tbody>
            </table>
          </div>
        )}
      </section>

      {/* Pagination */}
      {pagination.total_pages > 1 && (
        <div className="flex items-center justify-between text-sm text-zinc-600">
          <span>
            Página {pagination.page} de {pagination.total_pages}
          </span>
          <div className="flex items-center gap-2">
            <Button
              asChild
              variant="outline"
              size="sm"
              disabled={pagination.page <= 1}
              className="gap-1"
            >
              <a href={pagination.page > 1 ? buildUrl(pagination.page - 1) : '#'}>
                <ChevronLeft className="h-3.5 w-3.5" />
                Anterior
              </a>
            </Button>
            <Button
              asChild
              variant="outline"
              size="sm"
              disabled={pagination.page >= pagination.total_pages}
              className="gap-1"
            >
              <a href={pagination.page < pagination.total_pages ? buildUrl(pagination.page + 1) : '#'}>
                Próxima
                <ChevronRight className="h-3.5 w-3.5" />
              </a>
            </Button>
          </div>
        </div>
      )}
    </AdminStorePageShell>
  )
}
