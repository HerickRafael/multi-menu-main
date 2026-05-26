import { useEffect, useState } from 'react'
import { Activity, AlertCircle, BarChart3, RefreshCw, Zap } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  AdminPageHeader,
  AdminStorePageShell,
  getCsrfToken,
  showToast,
  useStoreContext,
} from '@/components/admin-store'

// ── Types ─────────────────────────────────────────────────────────────────────

type QueueStats = {
  by_status: Record<string, number>
  dead_total: number
  dead_1h: number
  dead_24h: number
  retrying_total: number
  by_type_dead_24h: Record<string, number>
}

type ApiHealth = {
  window_hours: number
  total_calls: number
  success: number
  errors_4xx: number
  errors_5xx: number
  network_errors: number
  error_rate: number
  by_module: Record<string, Record<string, number>>
  by_status: Record<string, number>
}

type LatencyStats = {
  window_hours: number
  count: number
  avg_ms: number | null
  p50_ms: number | null
  p95_ms: number | null
  p99_ms: number | null
  max_ms: number | null
}

type TopError = {
  error: string
  count: number
  sample_job_id: number
  sample_job_type: string
}

type Health = {
  generated_at: string
  queue: QueueStats
  api: ApiHealth
  latency: LatencyStats
  top_errors: TopError[]
  thresholds: Record<string, number>
}

type DeadJob = {
  id: number
  company_id: number | null
  job_type: string
  attempts: number
  max_attempts: number
  last_error: string | null
  payload_json: string | null
  created_at: string
  updated_at: string
  reserved_at: string | null
}

type Payload = {
  health: Health
  dead_jobs: DeadJob[]
  urls: {
    self: string
    health: string
    api_health: string
    dead_jobs: string
    retry_one: string
    retry_all: string
    logistics: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_IFOOD_OBSERVABILITY__?: Payload
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function pct(v: number): string {
  return `${(v * 100).toFixed(1)}%`
}

function fmtMs(v: number | null): string {
  return v === null ? '—' : `${v} ms`
}

// ── Main component ────────────────────────────────────────────────────────────

export default function AdminStoreIFoodObservabilityPage() {
  const ctx = useStoreContext()
  const initial =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_IFOOD_OBSERVABILITY__) || ({} as Payload)
  const urls = initial.urls ?? {
    self: '', health: '', api_health: '', dead_jobs: '',
    retry_one: '', retry_all: '', logistics: '',
  }

  const [health, setHealth] = useState<Health | null>(initial.health ?? null)
  const [deadJobs, setDeadJobs] = useState<DeadJob[]>(initial.dead_jobs ?? [])
  const [refreshing, setRefreshing] = useState(false)
  const [filterType, setFilterType] = useState('')

  async function refresh() {
    setRefreshing(true)
    try {
      const [healthRes, jobsRes] = await Promise.all([
        fetch(urls.health, {
          credentials: 'same-origin',
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        }),
        fetch(`${urls.dead_jobs}?limit=50${filterType ? `&job_type=${encodeURIComponent(filterType)}` : ''}`, {
          credentials: 'same-origin',
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        }),
      ])
      const healthJson = (await healthRes.json().catch(() => null)) as { success?: boolean; data?: Health } | null
      const jobsJson = (await jobsRes.json().catch(() => null)) as { success?: boolean; items?: DeadJob[] } | null
      if (healthJson?.success && healthJson.data) setHealth(healthJson.data)
      if (jobsJson?.success && jobsJson.items) setDeadJobs(jobsJson.items)
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setRefreshing(false)
    }
  }

  async function retryOne(id: number) {
    if (!confirm(`Re-enfileirar job #${id}?`)) return
    try {
      const fd = new FormData()
      fd.append('csrf_token', getCsrfToken())
      const res = await fetch(`${urls.retry_one}/${id}/retry`, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const j = (await res.json().catch(() => null)) as { success?: boolean; message?: string } | null
      if (j?.success) {
        showToast('Job re-enfileirado.', 'success')
        refresh()
      } else {
        showToast(j?.message || 'Falha.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    }
  }

  async function retryAllByType(jobType: string) {
    if (!confirm(`Re-enfileirar TODOS os dead de ${jobType}? (até 100)`)) return
    try {
      const res = await fetch(urls.retry_all, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ job_type: jobType, limit: 100 }),
      })
      const j = (await res.json().catch(() => null)) as { success?: boolean; retried?: number; message?: string } | null
      if (j?.success) {
        showToast(`${j.retried} job(s) re-enfileirado(s).`, 'success')
        refresh()
      } else {
        showToast(j?.message || 'Falha.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    }
  }

  useEffect(() => {
    const id = window.setInterval(refresh, 60000)
    return () => window.clearInterval(id)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [filterType])

  if (!health) {
    return (
      <AdminStorePageShell section="ifood">
        <AdminPageHeader
          title="DLQ & Observabilidade iFood"
          description="Sem dados."
          icon={<Activity className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        />
      </AdminStorePageShell>
    )
  }

  return (
    <AdminStorePageShell section="ifood">
      <AdminPageHeader
        title="DLQ & Observabilidade iFood"
        description={`Snapshot ${health.generated_at}. Thresholds: dead/h ${health.thresholds.dead_jobs_1h}, error_rate ${pct(health.thresholds.api_error_rate ?? 0)}, p95 ${health.thresholds.latency_p95_ms}ms.`}
        icon={<BarChart3 className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <div className="flex flex-wrap gap-2">
            <Button asChild variant="outline" size="sm">
              <a href={urls.logistics}>Central de Logística</a>
            </Button>
            <Button type="button" size="sm" onClick={refresh} disabled={refreshing} className="gap-1.5">
              <RefreshCw className={`h-3.5 w-3.5 ${refreshing ? 'animate-spin' : ''}`} />
              {refreshing ? 'Atualizando…' : 'Atualizar'}
            </Button>
          </div>
        }
      />

      {/* Queue stats */}
      <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <SummaryCard label="Pending"      value={health.queue.by_status.pending ?? 0} />
        <SummaryCard label="Processing"   value={health.queue.by_status.processing ?? 0} />
        <SummaryCard label="Retrying"     value={health.queue.retrying_total} />
        <SummaryCard label="Dead (1h)"    value={health.queue.dead_1h} highlight={health.queue.dead_1h > 0} />
        <SummaryCard label="Dead (24h)"   value={health.queue.dead_24h} />
      </section>

      {/* API + Latency */}
      <section className="grid gap-4 lg:grid-cols-2">
        <div className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
          <h3 className="text-sm font-semibold text-zinc-700">API iFood (última 1h)</h3>
          <dl className="mt-3 grid grid-cols-2 gap-3 text-sm">
            <Metric label="Total chamadas" value={health.api.total_calls} />
            <Metric
              label="Taxa de erro"
              value={pct(health.api.error_rate)}
              warning={health.api.error_rate > 0.10}
            />
            <Metric label="Sucesso 2xx" value={health.api.success} />
            <Metric label="Erros 4xx" value={health.api.errors_4xx} />
            <Metric label="Erros 5xx" value={health.api.errors_5xx} />
            <Metric label="Network errors" value={health.api.network_errors} />
          </dl>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
          <h3 className="text-sm font-semibold text-zinc-700">Latência (última 1h)</h3>
          <dl className="mt-3 grid grid-cols-2 gap-3 text-sm">
            <Metric label="Chamadas" value={health.latency.count} />
            <Metric label="avg" value={fmtMs(health.latency.avg_ms)} />
            <Metric label="p50" value={fmtMs(health.latency.p50_ms)} />
            <Metric label="p95" value={fmtMs(health.latency.p95_ms)} warning={(health.latency.p95_ms ?? 0) > 5000} />
            <Metric label="p99" value={fmtMs(health.latency.p99_ms)} />
            <Metric label="max" value={fmtMs(health.latency.max_ms)} />
          </dl>
        </div>
      </section>

      {/* Top errors */}
      {health.top_errors.length > 0 && (
        <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
          <h3 className="text-sm font-semibold text-zinc-700">Top erros (últimas 24h)</h3>
          <ul className="mt-3 space-y-2">
            {health.top_errors.map((e, i) => (
              <li key={i} className="flex items-start gap-3 rounded border border-zinc-100 p-2">
                <AlertCircle className="mt-0.5 h-4 w-4 shrink-0 text-red-500" />
                <div className="flex-1">
                  <p className="text-sm font-mono break-all">{e.error}</p>
                  <p className="mt-1 text-xs text-zinc-500">
                    {e.count}× • job_type=<code>{e.sample_job_type}</code> • sample id={e.sample_job_id}
                  </p>
                </div>
              </li>
            ))}
          </ul>
        </section>
      )}

      {/* Dead by type (with bulk retry) */}
      {Object.keys(health.queue.by_type_dead_24h).length > 0 && (
        <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
          <h3 className="text-sm font-semibold text-zinc-700">Mortos por job_type (24h)</h3>
          <table className="mt-3 w-full text-sm">
            <thead className="text-left text-zinc-500">
              <tr>
                <th className="py-1 pr-2">job_type</th>
                <th className="py-1 pr-2">Mortos</th>
                <th className="py-1 pr-2"></th>
              </tr>
            </thead>
            <tbody>
              {Object.entries(health.queue.by_type_dead_24h).map(([t, c]) => (
                <tr key={t} className="border-t border-zinc-100">
                  <td className="py-2 pr-2 font-mono">{t}</td>
                  <td className="py-2 pr-2">{c}</td>
                  <td className="py-2 pr-2">
                    <Button type="button" size="sm" variant="outline" onClick={() => retryAllByType(t)}>
                      Retry todos
                    </Button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </section>
      )}

      {/* Dead jobs list */}
      <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
        <div className="flex items-center justify-between">
          <h3 className="text-sm font-semibold text-zinc-700">Dead jobs (sua company)</h3>
          <div className="flex items-center gap-2">
            <input
              type="text"
              value={filterType}
              onChange={(e) => setFilterType(e.target.value)}
              placeholder="filtrar por job_type"
              className="rounded border border-zinc-200 px-2 py-1 text-xs"
            />
            <Button type="button" size="sm" variant="outline" onClick={refresh}>
              Aplicar
            </Button>
          </div>
        </div>
        {deadJobs.length === 0 ? (
          <p className="mt-3 text-sm text-zinc-400">Nenhum job morto. 🎉</p>
        ) : (
          <table className="mt-3 w-full text-xs">
            <thead className="text-left text-zinc-500">
              <tr>
                <th className="py-1 pr-2">ID</th>
                <th className="py-1 pr-2">job_type</th>
                <th className="py-1 pr-2">Tentativas</th>
                <th className="py-1 pr-2">Atualizado</th>
                <th className="py-1 pr-2">Erro</th>
                <th className="py-1 pr-2"></th>
              </tr>
            </thead>
            <tbody>
              {deadJobs.map((j) => (
                <tr key={j.id} className="border-t border-zinc-100">
                  <td className="py-2 pr-2 font-mono">{j.id}</td>
                  <td className="py-2 pr-2 font-mono">{j.job_type}</td>
                  <td className="py-2 pr-2">{j.attempts}/{j.max_attempts}</td>
                  <td className="py-2 pr-2 text-zinc-500">{j.updated_at}</td>
                  <td className="py-2 pr-2 max-w-xs truncate text-red-700" title={j.last_error ?? ''}>
                    {j.last_error || '—'}
                  </td>
                  <td className="py-2 pr-2">
                    <Button type="button" size="sm" variant="outline" onClick={() => retryOne(j.id)}>
                      <Zap className="h-3.5 w-3.5 mr-1" />
                      Retry
                    </Button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </section>
    </AdminStorePageShell>
  )
}

function SummaryCard({ label, value, highlight = false }: { label: string; value: number; highlight?: boolean }) {
  return (
    <div className={`rounded-2xl border p-4 shadow-sm ${highlight ? 'border-red-300 bg-red-50' : 'border-zinc-200 bg-white'}`}>
      <p className="text-xs text-zinc-500 uppercase tracking-wide">{label}</p>
      <p className={`mt-2 text-2xl font-semibold ${highlight ? 'text-red-700' : 'text-zinc-900'}`}>{value}</p>
    </div>
  )
}

function Metric({ label, value, warning = false }: { label: string; value: string | number; warning?: boolean }) {
  return (
    <div className={`rounded-lg px-3 py-2 ${warning ? 'bg-red-50' : 'bg-zinc-50'}`}>
      <dt className="text-xs text-zinc-500">{label}</dt>
      <dd className={`mt-0.5 font-semibold ${warning ? 'text-red-700' : 'text-zinc-900'}`}>{value}</dd>
    </div>
  )
}
