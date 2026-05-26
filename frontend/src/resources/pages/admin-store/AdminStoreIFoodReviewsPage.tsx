import { useMemo, useState, type FormEvent } from 'react'
import {
  ArrowLeft,
  ChevronLeft,
  ChevronRight,
  Filter,
  MessageSquare,
  RefreshCw,
  Star,
  X,
} from 'lucide-react'
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

type Review = {
  id: number
  environment: 'sandbox' | 'production'
  ifood_review_id: string
  ifood_order_id: string | null
  order_display_id: string | null
  rating: number
  comment: string | null
  customer_name: string | null
  moderation_status: string | null
  review_date: string | null
  fetched_at: string
}

type Stats = {
  total: number
  avg_rating: number | null
  breakdown: Record<1 | 2 | 3 | 4 | 5, number>
  last_fetched: string | null
}

type Payload = {
  reviews: Review[]
  stats: Stats
  pagination: { page: number; per_page: number; total: number; total_pages: number }
  filters: { rating: string; has_comment: string; env: string }
  config: {
    current_env: 'sandbox' | 'production'
    merchant_for_env: string
    can_sync: boolean
  }
  urls: { self: string; config: string; fetch: string; order: string }
}

declare global {
  interface Window {
    __ADMIN_STORE_IFOOD_REVIEWS__?: Payload
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatDateBr(s: string | null): string {
  if (!s) return '—'
  const d = new Date(s.replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return s
  return d.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' })
}

function Stars({ value, size = 'sm' }: { value: number; size?: 'sm' | 'lg' }) {
  const sz = size === 'lg' ? 'h-5 w-5' : 'h-3.5 w-3.5'
  return (
    <span className="inline-flex items-center gap-0.5">
      {[1, 2, 3, 4, 5].map((n) => (
        <Star
          key={n}
          className={`${sz} ${n <= value ? 'fill-amber-400 text-amber-400' : 'text-zinc-300'}`}
        />
      ))}
    </span>
  )
}

// ── Main component ────────────────────────────────────────────────────────────

export default function AdminStoreIFoodReviewsPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_IFOOD_REVIEWS__) || ({} as Payload)

  const reviews = payload.reviews ?? []
  const stats = payload.stats ?? { total: 0, avg_rating: null, breakdown: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 }, last_fetched: null }
  const pagination = payload.pagination ?? { page: 1, per_page: 20, total: 0, total_pages: 1 }
  const filters = payload.filters ?? { rating: '', has_comment: '', env: '' }
  const config = payload.config ?? { current_env: 'production', merchant_for_env: '', can_sync: false }
  const urls = payload.urls ?? { self: '', config: '', fetch: '', order: '' }

  const [rating, setRating] = useState(filters.rating)
  const [hasComment, setHasComment] = useState(filters.has_comment)
  const [env, setEnv] = useState(filters.env)
  const [syncing, setSyncing] = useState(false)

  const buildUrl = useMemo(() => {
    return (page: number) => {
      const p = new URLSearchParams()
      if (rating) p.set('rating', rating)
      if (hasComment) p.set('has_comment', hasComment)
      if (env) p.set('env', env)
      if (page > 1) p.set('page', String(page))
      const qs = p.toString()
      return urls.self + (qs ? `?${qs}` : '')
    }
  }, [rating, hasComment, env, urls.self])

  function applyFilters(e: FormEvent) {
    e.preventDefault()
    window.location.href = buildUrl(1)
  }

  function clearFilters() {
    window.location.href = urls.self
  }

  async function syncNow() {
    if (!config.can_sync) {
      showToast(
        `Configure o merchant ID de ${config.current_env} antes de sincronizar.`,
        'error',
      )
      return
    }
    setSyncing(true)
    try {
      const fd = new FormData()
      fd.append('csrf_token', getCsrfToken())
      const res = await fetch(urls.fetch, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const data = (await res.json().catch(() => null)) as
        | { success?: boolean; message?: string }
        | null
      if (data?.success) {
        showToast(data.message || 'Sincronização agendada.', 'success')
        setTimeout(() => window.location.reload(), 1500)
      } else {
        showToast(data?.message || 'Falha ao agendar sincronização.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setSyncing(false)
    }
  }

  const hasFilters = !!(rating || hasComment || env)
  const breakdownMax = Math.max(1, ...Object.values(stats.breakdown))

  return (
    <AdminStorePageShell section="ifood">
      <AdminPageHeader
        title="Avaliações iFood"
        description={
          stats.last_fetched
            ? `Última sincronização: ${formatDateBr(stats.last_fetched)}. Ambiente atual: ${config.current_env === 'sandbox' ? 'sandbox' : 'produção'}.`
            : `Ambiente atual: ${config.current_env === 'sandbox' ? 'sandbox' : 'produção'}. Nenhuma sincronização registrada ainda.`
        }
        icon={<Star className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <div className="flex flex-wrap gap-2">
            <Button asChild variant="outline" size="sm" className="gap-1.5">
              <a href={urls.config}>
                <ArrowLeft className="h-3.5 w-3.5" />
                Configuração
              </a>
            </Button>
            <Button
              type="button"
              size="sm"
              onClick={syncNow}
              disabled={syncing || !config.can_sync}
              className="gap-1.5"
            >
              <RefreshCw className={`h-3.5 w-3.5 ${syncing ? 'animate-spin' : ''}`} />
              {syncing ? 'Agendando…' : 'Sincronizar agora'}
            </Button>
          </div>
        }
      />

      {/* Stats card */}
      <section className="grid gap-4 lg:grid-cols-3">
        <div className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
          <p className="text-xs text-zinc-500 uppercase tracking-wide">Nota média</p>
          <div className="mt-2 flex items-baseline gap-3">
            <span className="text-4xl font-bold text-zinc-900">
              {stats.avg_rating !== null ? stats.avg_rating.toFixed(2).replace('.', ',') : '—'}
            </span>
            {stats.avg_rating !== null && <Stars value={Math.round(stats.avg_rating)} size="lg" />}
          </div>
          <p className="mt-1 text-xs text-zinc-500">
            Calculada sobre {stats.total} {stats.total === 1 ? 'avaliação' : 'avaliações'}.
          </p>
        </div>

        <div className="lg:col-span-2 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
          <p className="text-xs text-zinc-500 uppercase tracking-wide mb-3">Distribuição</p>
          <div className="space-y-1.5">
            {([5, 4, 3, 2, 1] as const).map((n) => {
              const count = stats.breakdown[n] ?? 0
              const pct = (count / breakdownMax) * 100
              return (
                <div key={n} className="flex items-center gap-3 text-sm">
                  <span className="w-8 inline-flex items-center gap-0.5 text-zinc-700">
                    {n}
                    <Star className="h-3 w-3 fill-amber-400 text-amber-400" />
                  </span>
                  <div className="flex-1 h-2.5 rounded-full bg-zinc-100 overflow-hidden">
                    <div
                      className="h-full rounded-full bg-amber-400 transition-all"
                      style={{ width: `${pct}%` }}
                    />
                  </div>
                  <span className="w-10 text-right text-xs text-zinc-600 tabular-nums">
                    {count.toLocaleString('pt-BR')}
                  </span>
                </div>
              )
            })}
          </div>
        </div>
      </section>

      {/* Filters */}
      <section className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
        <form onSubmit={applyFilters} className="flex flex-wrap items-end gap-3">
          <div className="flex-1 min-w-[140px]">
            <label className="block text-xs text-zinc-500 mb-1">Nota</label>
            <select
              value={rating}
              onChange={(e) => setRating(e.target.value)}
              className="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm"
            >
              <option value="">Todas</option>
              {[5, 4, 3, 2, 1].map((n) => (
                <option key={n} value={n}>
                  {n} {n === 1 ? 'estrela' : 'estrelas'}
                </option>
              ))}
            </select>
          </div>

          <div className="flex-1 min-w-[160px]">
            <label className="block text-xs text-zinc-500 mb-1">Comentário</label>
            <select
              value={hasComment}
              onChange={(e) => setHasComment(e.target.value)}
              className="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm"
            >
              <option value="">Todos</option>
              <option value="yes">Com comentário</option>
              <option value="no">Sem comentário (só nota)</option>
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
              <Button type="button" variant="outline" size="sm" onClick={clearFilters} className="gap-1.5">
                <X className="h-3.5 w-3.5" />
                Limpar
              </Button>
            )}
          </div>
        </form>
      </section>

      {/* List */}
      <section className="rounded-2xl border border-zinc-200 bg-white shadow-sm overflow-hidden">
        {reviews.length === 0 ? (
          <div className="p-12 text-center">
            <MessageSquare className="mx-auto h-10 w-10 text-zinc-300" />
            <p className="mt-2 text-sm text-zinc-500">
              {hasFilters
                ? 'Nenhuma avaliação encontrada com esses filtros.'
                : config.can_sync
                ? 'Nenhuma avaliação sincronizada ainda. Clique em "Sincronizar agora" para buscar.'
                : `Configure o merchant ID de ${config.current_env} primeiro para sincronizar avaliações.`}
            </p>
          </div>
        ) : (
          <ul className="divide-y divide-zinc-100">
            {reviews.map((r) => (
              <li key={r.id} className="p-5">
                <div className="flex items-start justify-between gap-3 mb-2">
                  <div className="flex items-center gap-3">
                    <Stars value={r.rating} />
                    <span className="text-sm font-medium text-zinc-800">
                      {r.customer_name || 'Cliente anônimo'}
                    </span>
                    <Badge
                      className={`text-[10px] ${
                        r.environment === 'sandbox'
                          ? 'bg-amber-100 text-amber-800 border-amber-200'
                          : 'bg-emerald-100 text-emerald-700 border-emerald-200'
                      }`}
                    >
                      {r.environment}
                    </Badge>
                    {r.moderation_status && r.moderation_status !== 'PUBLISHED' && (
                      <Badge className="text-[10px] bg-zinc-100 text-zinc-700 border-zinc-200">
                        {r.moderation_status}
                      </Badge>
                    )}
                  </div>
                  <div className="text-right text-xs text-zinc-500 whitespace-nowrap">
                    {formatDateBr(r.review_date ?? r.fetched_at)}
                  </div>
                </div>

                {r.comment ? (
                  <p className="text-sm text-zinc-700 whitespace-pre-wrap leading-relaxed">
                    {r.comment}
                  </p>
                ) : (
                  <p className="text-xs italic text-zinc-400">Sem comentário escrito.</p>
                )}

                {r.order_display_id && (
                  <div className="mt-3 text-xs">
                    {r.ifood_order_id ? (
                      <a
                        href={`${urls.order}${r.ifood_order_id}`}
                        className="inline-flex items-center gap-1 text-zinc-600 hover:text-zinc-900 underline"
                      >
                        Pedido #{r.order_display_id}
                      </a>
                    ) : (
                      <span className="text-zinc-500">Pedido #{r.order_display_id}</span>
                    )}
                  </div>
                )}
              </li>
            ))}
          </ul>
        )}
      </section>

      {/* Pagination */}
      {pagination.total_pages > 1 && (
        <div className="flex items-center justify-between text-sm text-zinc-600">
          <span>
            Página {pagination.page} de {pagination.total_pages} · {pagination.total.toLocaleString('pt-BR')} avaliações
          </span>
          <div className="flex items-center gap-2">
            <Button asChild variant="outline" size="sm" disabled={pagination.page <= 1} className="gap-1">
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
