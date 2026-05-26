import { useMemo, useState } from 'react'
import { AlertTriangle, CheckCircle2, Clock, Package, RefreshCw, Zap } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  AdminPageHeader,
  AdminStorePageShell,
  getCsrfToken,
  showToast,
  useStoreContext,
} from '@/components/admin-store'

type StockRow = {
  product_id: number
  ifood_product_id: string
  mapping_active: 0 | 1
  product_name: string | null
  product_active: 0 | 1 | null
  environment: 'sandbox' | 'production' | null
  desired_status: 'AVAILABLE' | 'UNAVAILABLE' | null
  last_synced_status: 'AVAILABLE' | 'UNAVAILABLE' | null
  last_synced_at: string | null
  last_error: string | null
  consecutive_failures: number | null
}

type Stats = {
  total: number
  synced: number
  drift: number
  never_synced: number
  with_errors: number
}

type Payload = {
  items: StockRow[]
  stats: Stats
  urls: {
    self: string
    sync: string
    state: string
    logistics: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_IFOOD_STOCK__?: Payload
  }
}

type RowState = 'synced' | 'drift' | 'never_synced'

function classifyRow(row: StockRow): RowState {
  if (row.last_synced_status === null) return 'never_synced'
  if (row.desired_status && row.last_synced_status !== row.desired_status) return 'drift'
  return 'synced'
}

function stateColor(s: RowState): string {
  switch (s) {
    case 'synced':      return 'bg-green-100 text-green-700 border-green-200'
    case 'drift':       return 'bg-amber-100 text-amber-800 border-amber-200'
    case 'never_synced':return 'bg-zinc-100 text-zinc-700 border-zinc-200'
  }
}

function stateLabel(s: RowState): string {
  return s === 'synced' ? 'Sincronizado' : s === 'drift' ? 'Drift' : 'Nunca sincronizado'
}

export default function AdminStoreIFoodStockPage() {
  const ctx = useStoreContext()
  const initial =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_IFOOD_STOCK__) || ({} as Payload)
  const urls = initial.urls ?? { self: '', sync: '', state: '', logistics: '' }

  const [items, setItems] = useState<StockRow[]>(initial.items ?? [])
  const [stats, setStats] = useState<Stats>(initial.stats ?? {
    total: 0, synced: 0, drift: 0, never_synced: 0, with_errors: 0,
  })
  const [filter, setFilter] = useState<'' | RowState>('')
  const [syncing, setSyncing] = useState(false)
  const [pendingRow, setPendingRow] = useState<number | null>(null)
  const [refreshing, setRefreshing] = useState(false)

  const filtered = useMemo(() => {
    if (!filter) return items
    return items.filter((r) => classifyRow(r) === filter)
  }, [items, filter])

  async function syncAll() {
    if (!confirm(`Sincronizar TODOS os ${stats.total} produtos com o iFood agora?\n(Enfileira jobs; o worker processa em sequência.)`)) return
    setSyncing(true)
    try {
      const fd = new FormData()
      fd.append('csrf_token', getCsrfToken())
      const res = await fetch(urls.sync, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const j = (await res.json().catch(() => null)) as
        | { success?: boolean; enqueued?: number; message?: string }
        | null
      if (j?.success) {
        showToast(`${j.enqueued ?? 0} job(s) enfileirado(s).`, 'success')
      } else {
        showToast(j?.message || 'Falha.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setSyncing(false)
    }
  }

  async function syncOne(productId: number) {
    setPendingRow(productId)
    try {
      const fd = new FormData()
      fd.append('csrf_token', getCsrfToken())
      fd.append('product_id', String(productId))
      const res = await fetch(urls.sync, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const j = (await res.json().catch(() => null)) as
        | { success?: boolean; enqueued?: number; message?: string }
        | null
      if (j?.success) {
        showToast(j.enqueued ? 'Sync enfileirado.' : (j.message || 'Sem mudança a sincronizar.'), 'success')
      } else {
        showToast(j?.message || 'Falha.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setPendingRow(null)
    }
  }

  async function refresh() {
    setRefreshing(true)
    try {
      const res = await fetch(urls.state, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const j = (await res.json().catch(() => null)) as
        | { success?: boolean; items?: Array<Record<string, unknown>> }
        | null
      if (!j?.success || !j.items) {
        showToast('Falha ao atualizar.', 'error')
        return
      }
      // O endpoint /stock/state usa nomes ligeiramente diferentes —
      // recarrega a página inteira pra pegar tudo consistente.
      window.location.reload()
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setRefreshing(false)
    }
  }

  return (
    <AdminStorePageShell section="ifood">
      <AdminPageHeader
        title="Estoque iFood"
        description="Sincroniza o status AVAILABLE/UNAVAILABLE de produtos mapeados com o catálogo iFood. Mudanças locais disparam sync automático; este painel é pra reconciliar manualmente."
        icon={<Package className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <div className="flex flex-wrap gap-2">
            <Button asChild variant="outline" size="sm">
              <a href={urls.logistics}>Central de Logística</a>
            </Button>
            <Button type="button" size="sm" variant="outline" onClick={refresh} disabled={refreshing} className="gap-1.5">
              <RefreshCw className={`h-3.5 w-3.5 ${refreshing ? 'animate-spin' : ''}`} />
              Atualizar
            </Button>
            <Button type="button" size="sm" onClick={syncAll} disabled={syncing} className="gap-1.5">
              <Zap className={`h-3.5 w-3.5 ${syncing ? 'animate-pulse' : ''}`} />
              {syncing ? 'Agendando…' : 'Sincronizar tudo'}
            </Button>
          </div>
        }
      />

      {/* Stats cards */}
      <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <Card label="Total mapeado" value={stats.total} />
        <Card
          label="Sincronizado"
          value={stats.synced}
          tone="success"
          onClick={() => setFilter(filter === 'synced' ? '' : 'synced')}
          active={filter === 'synced'}
        />
        <Card
          label="Drift"
          value={stats.drift}
          tone="warn"
          onClick={() => setFilter(filter === 'drift' ? '' : 'drift')}
          active={filter === 'drift'}
        />
        <Card
          label="Nunca sincronizado"
          value={stats.never_synced}
          tone="neutral"
          onClick={() => setFilter(filter === 'never_synced' ? '' : 'never_synced')}
          active={filter === 'never_synced'}
        />
        <Card label="Com erros" value={stats.with_errors} tone="error" />
      </section>

      {filter && (
        <div className="flex items-center gap-2 text-sm">
          <span className="text-zinc-600">Filtro ativo: <strong>{stateLabel(filter)}</strong></span>
          <Button type="button" size="sm" variant="ghost" onClick={() => setFilter('')}>limpar</Button>
        </div>
      )}

      {/* Table */}
      <section className="overflow-x-auto rounded-2xl border border-zinc-200 bg-white shadow-sm">
        <table className="w-full text-sm">
          <thead className="bg-zinc-50 text-left text-xs text-zinc-500">
            <tr>
              <th className="px-4 py-3">Produto</th>
              <th className="px-4 py-3">iFood ID</th>
              <th className="px-4 py-3">Local</th>
              <th className="px-4 py-3">Estado sync</th>
              <th className="px-4 py-3">Último sync</th>
              <th className="px-4 py-3">Falhas</th>
              <th className="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            {filtered.length === 0 && (
              <tr>
                <td colSpan={7} className="px-4 py-12 text-center text-zinc-400">
                  {items.length === 0 ? 'Nenhum produto mapeado para o iFood ainda.' : 'Sem produtos nesse filtro.'}
                </td>
              </tr>
            )}
            {filtered.map((row) => {
              const cls = classifyRow(row)
              const failures = row.consecutive_failures ?? 0
              return (
                <tr key={row.product_id} className="border-t border-zinc-100 hover:bg-zinc-50">
                  <td className="px-4 py-3">
                    <div className="font-medium text-zinc-900">{row.product_name || '—'}</div>
                    <div className="text-xs text-zinc-500">#{row.product_id}</div>
                  </td>
                  <td className="px-4 py-3 font-mono text-xs">{row.ifood_product_id}</td>
                  <td className="px-4 py-3">
                    {row.product_active === 1 ? (
                      <Badge className="bg-green-100 text-green-700 border-green-200 border" variant="outline">
                        AVAILABLE
                      </Badge>
                    ) : (
                      <Badge className="bg-zinc-100 text-zinc-600 border-zinc-200 border" variant="outline">
                        UNAVAILABLE
                      </Badge>
                    )}
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2">
                      <Badge className={`${stateColor(cls)} border`} variant="outline">
                        {stateLabel(cls)}
                      </Badge>
                      {row.last_synced_status && row.last_synced_status !== row.desired_status && (
                        <span className="text-xs text-zinc-500">
                          remoto: <code>{row.last_synced_status}</code>
                        </span>
                      )}
                    </div>
                    {row.last_error && (
                      <p className="mt-1 max-w-md truncate text-xs text-red-700" title={row.last_error}>
                        {row.last_error}
                      </p>
                    )}
                  </td>
                  <td className="px-4 py-3 text-xs text-zinc-500">
                    {row.last_synced_at ? (
                      <span className="inline-flex items-center gap-1">
                        <Clock className="h-3 w-3" />
                        {row.last_synced_at}
                      </span>
                    ) : (
                      '—'
                    )}
                  </td>
                  <td className="px-4 py-3">
                    {failures > 0 ? (
                      <span className="inline-flex items-center gap-1 text-red-700">
                        <AlertTriangle className="h-3.5 w-3.5" />
                        {failures}
                      </span>
                    ) : (
                      <CheckCircle2 className="h-3.5 w-3.5 text-green-600" />
                    )}
                  </td>
                  <td className="px-4 py-3">
                    <Button
                      type="button"
                      size="sm"
                      variant="outline"
                      onClick={() => syncOne(row.product_id)}
                      disabled={pendingRow === row.product_id}
                      className="gap-1"
                    >
                      <Zap className="h-3.5 w-3.5" />
                      {pendingRow === row.product_id ? '...' : 'Sync'}
                    </Button>
                  </td>
                </tr>
              )
            })}
          </tbody>
        </table>
      </section>
    </AdminStorePageShell>
  )
}

function Card({
  label,
  value,
  tone = 'neutral',
  onClick,
  active = false,
}: {
  label: string
  value: number
  tone?: 'success' | 'warn' | 'error' | 'neutral'
  onClick?: () => void
  active?: boolean
}) {
  const toneCls =
    tone === 'success' ? 'text-green-700' :
    tone === 'warn'    ? 'text-amber-700' :
    tone === 'error'   ? 'text-red-700' :
                         'text-zinc-900'
  const ring = active ? 'ring-2 ring-zinc-900' : ''
  const clickable = onClick ? 'cursor-pointer hover:bg-zinc-50' : ''
  return (
    <div
      onClick={onClick}
      className={`rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm ${ring} ${clickable}`}
    >
      <p className="text-xs text-zinc-500 uppercase tracking-wide">{label}</p>
      <p className={`mt-2 text-2xl font-semibold ${toneCls}`}>{value}</p>
    </div>
  )
}
