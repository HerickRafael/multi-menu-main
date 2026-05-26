import { useMemo, useState, type FormEvent } from 'react'
import { ArrowRight, Plus, Truck } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  AdminPageHeader,
  AdminStorePageShell,
  useStoreContext,
} from '@/components/admin-store'

type ShippingRow = {
  external_reference: string
  environment: 'sandbox' | 'production'
  ifood_shipping_id: string | null
  status: string
  submitted_at: string | null
  delivered_at: string | null
  cancelled_at: string | null
  updated_at: string
  order_id: number | null
  retries: number
}

type Payload = {
  items: ShippingRow[]
  counts: Record<string, number>
  filters: { status: string; env: string }
  urls: {
    self: string
    list: string
    create: string
    detail_base: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_IFOOD_SHIPPING_LIST__?: Payload
  }
}

const STATUSES = [
  'PENDING', 'SUBMITTED', 'ACCEPTED', 'CONFIRMED',
  'PICKED_UP', 'DELIVERED', 'CANCELLED', 'REJECTED', 'FAILED',
]

function statusColor(status: string): string {
  switch (status) {
    case 'DELIVERED':  return 'bg-green-100 text-green-800 border-green-200'
    case 'CANCELLED':  return 'bg-zinc-100 text-zinc-700 border-zinc-200'
    case 'REJECTED':
    case 'FAILED':     return 'bg-red-100 text-red-800 border-red-200'
    case 'PICKED_UP':  return 'bg-blue-100 text-blue-800 border-blue-200'
    case 'CONFIRMED':  return 'bg-indigo-100 text-indigo-800 border-indigo-200'
    case 'ACCEPTED':   return 'bg-cyan-100 text-cyan-800 border-cyan-200'
    case 'SUBMITTED':  return 'bg-amber-100 text-amber-800 border-amber-200'
    default:           return 'bg-zinc-100 text-zinc-800 border-zinc-200'
  }
}

export default function AdminStoreIFoodShippingListPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_IFOOD_SHIPPING_LIST__) || ({} as Payload)
  const items = payload.items ?? []
  const counts = payload.counts ?? {}
  const filters = payload.filters ?? { status: '', env: '' }
  const urls = payload.urls ?? { self: '', list: '', create: '', detail_base: '' }

  const [status, setStatus] = useState(filters.status)
  const [env, setEnv] = useState(filters.env)

  const totalShown = items.length
  const totalAll = useMemo(() => Object.values(counts).reduce((a, b) => a + b, 0), [counts])

  function apply(e: FormEvent) {
    e.preventDefault()
    const qs = new URLSearchParams()
    if (status) qs.set('status', status)
    if (env) qs.set('env', env)
    const s = qs.toString()
    window.location.href = urls.self + (s ? `?${s}` : '')
  }

  function clear() {
    window.location.href = urls.self
  }

  return (
    <AdminStorePageShell section="ifood">
      <AdminPageHeader
        title="Shipping Orders (HUB)"
        description={`${totalShown} de ${totalAll} pedido(s) de logística — pedidos criados pelo seu sistema usando entregadores iFood.`}
        icon={<Truck className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <Button asChild size="sm" className="gap-1.5">
            <a href={urls.create}>
              <Plus className="h-3.5 w-3.5" />
              Novo
            </a>
          </Button>
        }
      />

      {/* Status counters */}
      <section className="flex flex-wrap gap-2">
        {STATUSES.map((st) => {
          const n = counts[st] ?? 0
          if (n === 0 && st !== filters.status) return null
          return (
            <button
              key={st}
              type="button"
              onClick={() => {
                setStatus(st === status ? '' : st)
                const qs = new URLSearchParams()
                if (st !== status) qs.set('status', st)
                if (env) qs.set('env', env)
                const s = qs.toString()
                window.location.href = urls.self + (s ? `?${s}` : '')
              }}
              className={`rounded-full border px-3 py-1 text-xs transition ${
                status === st
                  ? 'border-zinc-900 bg-zinc-900 text-white'
                  : `${statusColor(st)} hover:opacity-80`
              }`}
            >
              {st} <span className="opacity-70">({n})</span>
            </button>
          )
        })}
      </section>

      {/* Filters form */}
      <form
        onSubmit={apply}
        className="flex flex-wrap items-end gap-2 rounded-2xl border border-zinc-200 bg-white p-4"
      >
        <div>
          <label className="block text-xs text-zinc-500">Status</label>
          <select
            value={status}
            onChange={(e) => setStatus(e.target.value)}
            className="mt-1 rounded border border-zinc-200 px-2 py-1 text-sm"
          >
            <option value="">Todos</option>
            {STATUSES.map((s) => <option key={s} value={s}>{s}</option>)}
          </select>
        </div>
        <div>
          <label className="block text-xs text-zinc-500">Ambiente</label>
          <select
            value={env}
            onChange={(e) => setEnv(e.target.value)}
            className="mt-1 rounded border border-zinc-200 px-2 py-1 text-sm"
          >
            <option value="">Ambos</option>
            <option value="production">Produção</option>
            <option value="sandbox">Sandbox</option>
          </select>
        </div>
        <div className="flex gap-2">
          <Button type="submit" size="sm">Filtrar</Button>
          {(status || env) && (
            <Button type="button" size="sm" variant="outline" onClick={clear}>Limpar</Button>
          )}
        </div>
      </form>

      {/* Table */}
      <section className="overflow-x-auto rounded-2xl border border-zinc-200 bg-white shadow-sm">
        <table className="w-full text-sm">
          <thead className="bg-zinc-50 text-left text-xs text-zinc-500">
            <tr>
              <th className="px-4 py-3">Referência</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Env</th>
              <th className="px-4 py-3">iFood ID</th>
              <th className="px-4 py-3">Pedido local</th>
              <th className="px-4 py-3">Retries</th>
              <th className="px-4 py-3">Atualizado</th>
              <th className="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            {items.length === 0 && (
              <tr>
                <td colSpan={8} className="px-4 py-12 text-center text-zinc-400">
                  Nenhum shipping order encontrado.
                </td>
              </tr>
            )}
            {items.map((row) => (
              <tr key={row.external_reference} className="border-t border-zinc-100 hover:bg-zinc-50">
                <td className="px-4 py-3 font-mono text-xs">{row.external_reference}</td>
                <td className="px-4 py-3">
                  <Badge className={`${statusColor(row.status)} border`} variant="outline">
                    {row.status}
                  </Badge>
                </td>
                <td className="px-4 py-3 text-xs">{row.environment}</td>
                <td className="px-4 py-3 font-mono text-xs">
                  {row.ifood_shipping_id ? row.ifood_shipping_id.slice(0, 12) : '—'}
                </td>
                <td className="px-4 py-3 text-xs">{row.order_id ?? '—'}</td>
                <td className="px-4 py-3 text-xs">{row.retries}</td>
                <td className="px-4 py-3 text-xs text-zinc-500">{row.updated_at}</td>
                <td className="px-4 py-3">
                  <a
                    href={urls.detail_base + encodeURIComponent(row.external_reference)}
                    className="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:underline"
                  >
                    Abrir
                    <ArrowRight className="h-3 w-3" />
                  </a>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </section>
    </AdminStorePageShell>
  )
}
