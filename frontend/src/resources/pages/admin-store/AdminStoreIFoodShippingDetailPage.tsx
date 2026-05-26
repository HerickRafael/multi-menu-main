import { useState } from 'react'
import { ArrowLeft, Ban, CheckCircle2, Clock, RefreshCw, Truck, X } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  AdminPageHeader,
  AdminStorePageShell,
  getCsrfToken,
  showToast,
  useStoreContext,
} from '@/components/admin-store'

type ShippingRow = {
  id: number
  company_id: number
  environment: 'sandbox' | 'production'
  order_id: number | null
  external_reference: string
  ifood_shipping_id: string | null
  status: string
  request_payload: string | null
  response_payload: string | null
  last_error: string | null
  last_response_status: number | null
  submitted_at: string | null
  accepted_at: string | null
  picked_up_at: string | null
  delivered_at: string | null
  cancelled_at: string | null
  cancel_reason: string | null
  retries: number
  next_poll_at: string | null
  created_at: string
  updated_at: string
}

type Payload = {
  shipping: ShippingRow | null
  urls: {
    self: string
    list: string
    state: string
    cancel: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_IFOOD_SHIPPING_DETAIL__?: Payload
  }
}

function statusColor(status: string): string {
  switch (status) {
    case 'DELIVERED': return 'bg-green-100 text-green-800 border-green-200'
    case 'CANCELLED': return 'bg-zinc-100 text-zinc-700 border-zinc-200'
    case 'REJECTED':
    case 'FAILED':    return 'bg-red-100 text-red-800 border-red-200'
    case 'PICKED_UP': return 'bg-blue-100 text-blue-800 border-blue-200'
    case 'CONFIRMED': return 'bg-indigo-100 text-indigo-800 border-indigo-200'
    case 'ACCEPTED':  return 'bg-cyan-100 text-cyan-800 border-cyan-200'
    case 'SUBMITTED': return 'bg-amber-100 text-amber-800 border-amber-200'
    default:          return 'bg-zinc-100 text-zinc-800 border-zinc-200'
  }
}

const TIMELINE_STEPS: Array<{ key: keyof ShippingRow; label: string }> = [
  { key: 'created_at',   label: 'Criado' },
  { key: 'submitted_at', label: 'Submetido ao iFood' },
  { key: 'accepted_at',  label: 'Aceito / Confirmado' },
  { key: 'picked_up_at', label: 'Entregador retirou' },
  { key: 'delivered_at', label: 'Entregue' },
  { key: 'cancelled_at', label: 'Cancelado' },
]

const TERMINAL = ['DELIVERED', 'CANCELLED', 'REJECTED', 'FAILED']

export default function AdminStoreIFoodShippingDetailPage() {
  const ctx = useStoreContext()
  const initial =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_IFOOD_SHIPPING_DETAIL__) || ({} as Payload)
  const urls = initial.urls ?? { self: '', list: '', state: '', cancel: '' }

  const [shipping, setShipping] = useState<ShippingRow | null>(initial.shipping ?? null)
  const [refreshing, setRefreshing] = useState(false)
  const [cancelling, setCancelling] = useState(false)

  if (!shipping) {
    return (
      <AdminStorePageShell section="ifood">
        <AdminPageHeader
          title="Shipping não encontrado"
          description="A referência informada não existe ou pertence a outra company."
          icon={<X className="h-5 w-5 text-red-600" />}
          actions={
            <Button asChild variant="outline" size="sm">
              <a href={urls.list}><ArrowLeft className="mr-1 h-3.5 w-3.5" />Voltar</a>
            </Button>
          }
        />
      </AdminStorePageShell>
    )
  }

  async function refresh() {
    setRefreshing(true)
    try {
      const res = await fetch(urls.state, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const j = (await res.json().catch(() => null)) as { success?: boolean; state?: ShippingRow } | null
      if (j?.success && j.state) {
        setShipping(j.state)
      } else {
        showToast('Não foi possível atualizar.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setRefreshing(false)
    }
  }

  async function cancel() {
    const reason = prompt('Motivo do cancelamento:', 'admin_cancel')
    if (reason === null) return
    setCancelling(true)
    try {
      const fd = new FormData()
      fd.append('csrf_token', getCsrfToken())
      fd.append('reason', reason)
      const res = await fetch(urls.cancel, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const j = (await res.json().catch(() => null)) as { success?: boolean; enqueued?: number; message?: string } | null
      if (j?.success) {
        showToast(j.enqueued ? 'Cancelamento enfileirado.' : (j.message || 'Sem ação necessária.'), 'success')
        refresh()
      } else {
        showToast(j?.message || 'Falha ao cancelar.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setCancelling(false)
    }
  }

  const isTerminal = TERMINAL.includes(shipping.status)
  let request: unknown = null
  let response: unknown = null
  try { request = shipping.request_payload ? JSON.parse(shipping.request_payload) : null } catch { request = shipping.request_payload }
  try { response = shipping.response_payload ? JSON.parse(shipping.response_payload) : null } catch { response = shipping.response_payload }

  return (
    <AdminStorePageShell section="ifood">
      <AdminPageHeader
        title="Detalhe do Shipping"
        description={
          shipping.external_reference +
          (shipping.ifood_shipping_id ? ` · iFood ID: ${shipping.ifood_shipping_id}` : '')
        }
        icon={<Truck className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <div className="flex flex-wrap gap-2">
            <Button asChild variant="outline" size="sm" className="gap-1.5">
              <a href={urls.list}>
                <ArrowLeft className="h-3.5 w-3.5" />
                Voltar
              </a>
            </Button>
            <Button type="button" size="sm" variant="outline" onClick={refresh} disabled={refreshing} className="gap-1.5">
              <RefreshCw className={`h-3.5 w-3.5 ${refreshing ? 'animate-spin' : ''}`} />
              Atualizar
            </Button>
            {!isTerminal && (
              <Button
                type="button"
                size="sm"
                variant="destructive"
                onClick={cancel}
                disabled={cancelling}
                className="gap-1.5"
              >
                <Ban className="h-3.5 w-3.5" />
                {cancelling ? 'Cancelando…' : 'Cancelar'}
              </Button>
            )}
          </div>
        }
      />

      {/* Status & key metadata */}
      <section className="grid gap-4 lg:grid-cols-3">
        <div className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
          <p className="text-xs text-zinc-500 uppercase tracking-wide">Status atual</p>
          <Badge className={`${statusColor(shipping.status)} mt-2 border text-base`} variant="outline">
            {shipping.status}
          </Badge>
          {shipping.last_error && (
            <p className="mt-3 text-xs text-red-700 font-mono break-all">
              {shipping.last_error}
            </p>
          )}
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
          <p className="text-xs text-zinc-500 uppercase tracking-wide">Tentativas</p>
          <p className="mt-2 text-2xl font-semibold">{shipping.retries}</p>
          {shipping.last_response_status !== null && (
            <p className="mt-1 text-xs text-zinc-500">
              último HTTP: <code>{shipping.last_response_status}</code>
            </p>
          )}
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
          <p className="text-xs text-zinc-500 uppercase tracking-wide">Próximo poll</p>
          <p className="mt-2 text-sm">
            {shipping.next_poll_at ? (
              <span className="inline-flex items-center gap-1 text-zinc-700">
                <Clock className="h-3.5 w-3.5" />
                {shipping.next_poll_at}
              </span>
            ) : (
              <span className="text-zinc-400">— (estado terminal ou pausado)</span>
            )}
          </p>
          <p className="mt-2 text-xs text-zinc-500">
            ambiente: <code>{shipping.environment}</code>
          </p>
        </div>
      </section>

      {/* Timeline */}
      <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
        <h3 className="text-sm font-semibold text-zinc-700">Timeline</h3>
        <ol className="mt-3 space-y-2">
          {TIMELINE_STEPS.map((step) => {
            const v = shipping[step.key] as string | null | undefined
            const reached = !!v
            return (
              <li key={step.key as string} className="flex items-center gap-3 text-sm">
                <span className={`flex h-6 w-6 items-center justify-center rounded-full ${
                  reached ? 'bg-green-100 text-green-700' : 'bg-zinc-100 text-zinc-400'
                }`}>
                  {reached ? <CheckCircle2 className="h-4 w-4" /> : <Clock className="h-4 w-4" />}
                </span>
                <span className={reached ? 'font-medium text-zinc-900' : 'text-zinc-400'}>
                  {step.label}
                </span>
                <span className="text-xs text-zinc-500 font-mono">{v || ''}</span>
              </li>
            )
          })}
        </ol>
        {shipping.cancel_reason && (
          <p className="mt-3 text-xs text-zinc-500">
            Motivo do cancelamento: <code>{shipping.cancel_reason}</code>
          </p>
        )}
      </section>

      {/* Payloads */}
      <section className="grid gap-4 lg:grid-cols-2">
        <PayloadCard title="Request (enviado pro iFood)" data={request} />
        <PayloadCard title="Response (recebido do iFood)" data={response} />
      </section>
    </AdminStorePageShell>
  )
}

function PayloadCard({ title, data }: { title: string; data: unknown }) {
  return (
    <div className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
      <h3 className="text-sm font-semibold text-zinc-700">{title}</h3>
      {data ? (
        <pre className="mt-3 max-h-96 overflow-auto rounded bg-zinc-50 p-3 text-xs">
          {typeof data === 'string' ? data : JSON.stringify(data, null, 2)}
        </pre>
      ) : (
        <p className="mt-3 text-sm text-zinc-400">—</p>
      )}
    </div>
  )
}
