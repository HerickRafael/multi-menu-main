import { useMemo, useState } from 'react'
import { ArrowLeft, Calculator, FileText, Send, Truck } from 'lucide-react'
import { Button } from '@/components/ui/button'
import {
  AdminPageHeader,
  AdminStorePageShell,
  showToast,
  useStoreContext,
} from '@/components/admin-store'

type Payload = {
  company: { id: number; name: string }
  urls: {
    self: string
    list: string
    quote: string
    create: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_IFOOD_SHIPPING_NEW__?: Payload
  }
}

type Item = { name: string; quantity: number; unitPrice: number }
type Address = {
  addressLine: string
  number: string
  neighborhood: string
  city: string
  state: string
  postalCode: string
  latitude?: number
  longitude?: number
}
type Customer = { name: string; phone: string; document?: string }
type PaymentInfo = { method: 'CASH' | 'CARD' | 'PIX'; amount: number }
type Dimensions = { height: number; width: number; length: number }

const DEFAULT_PAYLOAD = {
  customer: { name: '', phone: '', document: '' } as Customer,
  items: [{ name: '', quantity: 1, unitPrice: 0 }] as Item[],
  pickup: {
    addressLine: '', number: '', neighborhood: '', city: '', state: '', postalCode: '',
  } as Address,
  delivery: {
    addressLine: '', number: '', neighborhood: '', city: '', state: '', postalCode: '',
  } as Address,
  payment: { method: 'CASH', amount: 0 } as PaymentInfo,
  dimensions: { height: 20, width: 20, length: 20 } as Dimensions,
  weight: 0.5,
  observation: '',
}

export default function AdminStoreIFoodShippingCreatePage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_IFOOD_SHIPPING_NEW__) || ({} as Payload)
  const urls = payload.urls ?? { self: '', list: '', quote: '', create: '' }

  const [draft, setDraft] = useState(DEFAULT_PAYLOAD)
  const [externalRef, setExternalRef] = useState('')
  const [orderId, setOrderId] = useState('')
  const [advancedJson, setAdvancedJson] = useState('')
  const [useAdvanced, setUseAdvanced] = useState(false)
  const [quote, setQuote] = useState<{ data: unknown; http_status?: number } | null>(null)
  const [quoting, setQuoting] = useState(false)
  const [creating, setCreating] = useState(false)

  const finalPayload = useMemo(() => {
    if (useAdvanced) {
      try {
        return JSON.parse(advancedJson)
      } catch {
        return null
      }
    }
    // Auto-soma payment.amount com base nos items
    const itemsTotal = draft.items.reduce((s, it) => s + Number(it.unitPrice || 0) * Number(it.quantity || 0), 0)
    return {
      ...draft,
      payment: { ...draft.payment, amount: Number(draft.payment.amount || itemsTotal) },
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [draft, advancedJson, useAdvanced])

  function addItem() {
    setDraft((d) => ({ ...d, items: [...d.items, { name: '', quantity: 1, unitPrice: 0 }] }))
  }
  function removeItem(i: number) {
    setDraft((d) => ({ ...d, items: d.items.filter((_, idx) => idx !== i) }))
  }
  function updateItem(i: number, key: keyof Item, value: string | number) {
    setDraft((d) => ({
      ...d,
      items: d.items.map((it, idx) =>
        idx === i ? { ...it, [key]: key === 'name' ? String(value) : Number(value) } : it,
      ),
    }))
  }

  function setAddress(which: 'pickup' | 'delivery', key: keyof Address, value: string) {
    setDraft((d) => ({ ...d, [which]: { ...d[which], [key]: value } }))
  }

  function toAdvanced() {
    setAdvancedJson(JSON.stringify(finalPayload, null, 2))
    setUseAdvanced(true)
  }

  async function getQuote() {
    if (!finalPayload) {
      showToast('Payload JSON inválido.', 'error')
      return
    }
    setQuoting(true)
    setQuote(null)
    try {
      const res = await fetch(urls.quote, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ payload: finalPayload }),
      })
      const j = (await res.json().catch(() => null)) as
        | { success?: boolean; quote?: unknown; http_status?: number; message?: string }
        | null
      if (j?.success) {
        setQuote({ data: j.quote, http_status: j.http_status })
        showToast('Quote recebido.', 'success')
      } else {
        setQuote({ data: j?.message ?? 'erro', http_status: j?.http_status })
        showToast(j?.message || 'Quote falhou.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setQuoting(false)
    }
  }

  async function create() {
    if (!finalPayload) {
      showToast('Payload JSON inválido.', 'error')
      return
    }
    setCreating(true)
    try {
      const body: Record<string, unknown> = { payload: finalPayload }
      if (externalRef.trim() !== '') body.external_reference = externalRef.trim()
      if (orderId.trim() !== '') body.order_id = Number(orderId)

      const res = await fetch(urls.create, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(body),
      })
      const j = (await res.json().catch(() => null)) as
        | { success?: boolean; external_reference?: string; status?: string; message?: string }
        | null
      if (j?.success && j.external_reference) {
        showToast('Shipping criado.', 'success')
        const detailUrl = window.location.pathname.replace(/\/new$/, '/r/') + encodeURIComponent(j.external_reference)
        setTimeout(() => { window.location.href = detailUrl }, 1000)
      } else {
        showToast(j?.message || 'Falha ao criar.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setCreating(false)
    }
  }

  return (
    <AdminStorePageShell section="ifood">
      <AdminPageHeader
        title="Novo Shipping Order"
        description="Cria um pedido de logística no iFood a partir do seu sistema. Quote primeiro pra ver custo, depois criar."
        icon={<Truck className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <div className="flex flex-wrap gap-2">
            <Button asChild variant="outline" size="sm" className="gap-1.5">
              <a href={urls.list}>
                <ArrowLeft className="h-3.5 w-3.5" />
                Voltar à lista
              </a>
            </Button>
            <Button type="button" size="sm" variant="outline" onClick={getQuote} disabled={quoting} className="gap-1.5">
              <Calculator className="h-3.5 w-3.5" />
              {quoting ? 'Cotando…' : 'Get Quote'}
            </Button>
            <Button type="button" size="sm" onClick={create} disabled={creating} className="gap-1.5">
              <Send className="h-3.5 w-3.5" />
              {creating ? 'Criando…' : 'Criar'}
            </Button>
          </div>
        }
      />

      {/* Toggle modo avançado */}
      <div className="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-sm">
        <label className="inline-flex items-center gap-2">
          <input
            type="checkbox"
            checked={useAdvanced}
            onChange={(e) => {
              if (e.target.checked) {
                toAdvanced()
              } else {
                setUseAdvanced(false)
              }
            }}
          />
          Modo avançado (JSON puro)
        </label>
        <span className="text-xs text-zinc-500">
          Use o form normal pro caso típico, ou JSON pra customizações.
        </span>
      </div>

      {useAdvanced ? (
        <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
          <h3 className="text-sm font-semibold text-zinc-700">Payload JSON</h3>
          <textarea
            value={advancedJson}
            onChange={(e) => setAdvancedJson(e.target.value)}
            rows={20}
            className="mt-3 w-full rounded border border-zinc-200 p-3 font-mono text-xs"
            spellCheck={false}
          />
        </section>
      ) : (
        <>
          {/* Customer + opts */}
          <section className="grid gap-4 lg:grid-cols-2">
            <Card title="Cliente">
              <Field label="Nome">
                <input
                  value={draft.customer.name}
                  onChange={(e) => setDraft((d) => ({ ...d, customer: { ...d.customer, name: e.target.value } }))}
                  className="w-full rounded border border-zinc-200 px-2 py-1 text-sm"
                />
              </Field>
              <Field label="Telefone">
                <input
                  value={draft.customer.phone}
                  onChange={(e) => setDraft((d) => ({ ...d, customer: { ...d.customer, phone: e.target.value } }))}
                  className="w-full rounded border border-zinc-200 px-2 py-1 text-sm"
                  placeholder="+5511..."
                />
              </Field>
              <Field label="Documento (CPF)" hint="Opcional">
                <input
                  value={draft.customer.document ?? ''}
                  onChange={(e) => setDraft((d) => ({ ...d, customer: { ...d.customer, document: e.target.value } }))}
                  className="w-full rounded border border-zinc-200 px-2 py-1 text-sm"
                />
              </Field>
            </Card>
            <Card title="Identificadores (opcionais)">
              <Field label="external_reference" hint="Em branco = gera UUID automático">
                <input
                  value={externalRef}
                  onChange={(e) => setExternalRef(e.target.value)}
                  placeholder="ORDER-12345 ou deixe em branco"
                  className="w-full rounded border border-zinc-200 px-2 py-1 text-sm font-mono"
                />
              </Field>
              <Field label="order_id local" hint="Vincula a uma row da tabela orders local">
                <input
                  type="number"
                  value={orderId}
                  onChange={(e) => setOrderId(e.target.value)}
                  className="w-full rounded border border-zinc-200 px-2 py-1 text-sm"
                />
              </Field>
            </Card>
          </section>

          {/* Items */}
          <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div className="flex items-center justify-between">
              <h3 className="text-sm font-semibold text-zinc-700">Itens</h3>
              <Button type="button" size="sm" variant="outline" onClick={addItem}>Adicionar item</Button>
            </div>
            <table className="mt-3 w-full text-sm">
              <thead className="text-left text-xs text-zinc-500">
                <tr>
                  <th className="py-1 pr-2">Nome</th>
                  <th className="py-1 pr-2 w-24">Qtd</th>
                  <th className="py-1 pr-2 w-32">Preço unit.</th>
                  <th className="py-1 pr-2 w-32">Subtotal</th>
                  <th className="py-1 pr-2"></th>
                </tr>
              </thead>
              <tbody>
                {draft.items.map((it, i) => (
                  <tr key={i} className="border-t border-zinc-100">
                    <td className="py-2 pr-2">
                      <input
                        value={it.name}
                        onChange={(e) => updateItem(i, 'name', e.target.value)}
                        className="w-full rounded border border-zinc-200 px-2 py-1 text-sm"
                      />
                    </td>
                    <td className="py-2 pr-2">
                      <input
                        type="number"
                        min={1}
                        value={it.quantity}
                        onChange={(e) => updateItem(i, 'quantity', e.target.value)}
                        className="w-full rounded border border-zinc-200 px-2 py-1 text-sm"
                      />
                    </td>
                    <td className="py-2 pr-2">
                      <input
                        type="number"
                        step="0.01"
                        min={0}
                        value={it.unitPrice}
                        onChange={(e) => updateItem(i, 'unitPrice', e.target.value)}
                        className="w-full rounded border border-zinc-200 px-2 py-1 text-sm"
                      />
                    </td>
                    <td className="py-2 pr-2 text-sm tabular-nums">
                      {(it.unitPrice * it.quantity).toFixed(2)}
                    </td>
                    <td className="py-2 pr-2">
                      {draft.items.length > 1 && (
                        <Button type="button" size="sm" variant="ghost" onClick={() => removeItem(i)}>
                          remover
                        </Button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </section>

          {/* Addresses */}
          <section className="grid gap-4 lg:grid-cols-2">
            <AddressCard
              title="Origem (pickup)"
              address={draft.pickup}
              onChange={(k, v) => setAddress('pickup', k, v)}
            />
            <AddressCard
              title="Destino (delivery)"
              address={draft.delivery}
              onChange={(k, v) => setAddress('delivery', k, v)}
            />
          </section>

          {/* Payment + dimensions */}
          <section className="grid gap-4 lg:grid-cols-2">
            <Card title="Pagamento">
              <Field label="Método">
                <select
                  value={draft.payment.method}
                  onChange={(e) =>
                    setDraft((d) => ({ ...d, payment: { ...d.payment, method: e.target.value as PaymentInfo['method'] } }))
                  }
                  className="rounded border border-zinc-200 px-2 py-1 text-sm"
                >
                  <option value="CASH">Dinheiro</option>
                  <option value="CARD">Cartão</option>
                  <option value="PIX">PIX</option>
                </select>
              </Field>
              <Field label="Valor (deixe 0 para auto-somar items)">
                <input
                  type="number"
                  step="0.01"
                  value={draft.payment.amount}
                  onChange={(e) =>
                    setDraft((d) => ({ ...d, payment: { ...d.payment, amount: Number(e.target.value) } }))
                  }
                  className="w-full rounded border border-zinc-200 px-2 py-1 text-sm"
                />
              </Field>
            </Card>
            <Card title="Pacote (cm) e peso (kg)">
              <div className="grid grid-cols-3 gap-2">
                <Field label="Altura">
                  <input
                    type="number" min={0}
                    value={draft.dimensions.height}
                    onChange={(e) => setDraft((d) => ({ ...d, dimensions: { ...d.dimensions, height: Number(e.target.value) } }))}
                    className="w-full rounded border border-zinc-200 px-2 py-1 text-sm"
                  />
                </Field>
                <Field label="Largura">
                  <input
                    type="number" min={0}
                    value={draft.dimensions.width}
                    onChange={(e) => setDraft((d) => ({ ...d, dimensions: { ...d.dimensions, width: Number(e.target.value) } }))}
                    className="w-full rounded border border-zinc-200 px-2 py-1 text-sm"
                  />
                </Field>
                <Field label="Comprimento">
                  <input
                    type="number" min={0}
                    value={draft.dimensions.length}
                    onChange={(e) => setDraft((d) => ({ ...d, dimensions: { ...d.dimensions, length: Number(e.target.value) } }))}
                    className="w-full rounded border border-zinc-200 px-2 py-1 text-sm"
                  />
                </Field>
              </div>
              <Field label="Peso (kg)">
                <input
                  type="number" min={0} step="0.01"
                  value={draft.weight}
                  onChange={(e) => setDraft((d) => ({ ...d, weight: Number(e.target.value) }))}
                  className="w-full rounded border border-zinc-200 px-2 py-1 text-sm"
                />
              </Field>
              <Field label="Observação">
                <textarea
                  value={draft.observation}
                  onChange={(e) => setDraft((d) => ({ ...d, observation: e.target.value }))}
                  rows={3}
                  className="w-full rounded border border-zinc-200 px-2 py-1 text-sm"
                />
              </Field>
            </Card>
          </section>
        </>
      )}

      {/* Quote result */}
      {quote && (
        <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
          <h3 className="text-sm font-semibold text-zinc-700 flex items-center gap-2">
            <FileText className="h-4 w-4" />
            Quote ({quote.http_status ?? '?'})
          </h3>
          <pre className="mt-3 overflow-auto rounded bg-zinc-50 p-3 text-xs">
            {JSON.stringify(quote.data, null, 2)}
          </pre>
        </section>
      )}
    </AdminStorePageShell>
  )
}

function Card({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <div className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm space-y-3">
      <h3 className="text-sm font-semibold text-zinc-700">{title}</h3>
      {children}
    </div>
  )
}

function Field({ label, hint, children }: { label: string; hint?: string; children: React.ReactNode }) {
  return (
    <div className="space-y-1">
      <label className="block text-xs font-medium text-zinc-700">{label}</label>
      {children}
      {hint && <p className="text-xs text-zinc-500">{hint}</p>}
    </div>
  )
}

function AddressCard({
  title,
  address,
  onChange,
}: {
  title: string
  address: Address
  onChange: (key: keyof Address, value: string) => void
}) {
  return (
    <Card title={title}>
      <Field label="Logradouro">
        <input
          value={address.addressLine}
          onChange={(e) => onChange('addressLine', e.target.value)}
          className="w-full rounded border border-zinc-200 px-2 py-1 text-sm"
        />
      </Field>
      <div className="grid grid-cols-2 gap-2">
        <Field label="Número">
          <input
            value={address.number}
            onChange={(e) => onChange('number', e.target.value)}
            className="w-full rounded border border-zinc-200 px-2 py-1 text-sm"
          />
        </Field>
        <Field label="CEP">
          <input
            value={address.postalCode}
            onChange={(e) => onChange('postalCode', e.target.value)}
            className="w-full rounded border border-zinc-200 px-2 py-1 text-sm"
          />
        </Field>
      </div>
      <Field label="Bairro">
        <input
          value={address.neighborhood}
          onChange={(e) => onChange('neighborhood', e.target.value)}
          className="w-full rounded border border-zinc-200 px-2 py-1 text-sm"
        />
      </Field>
      <div className="grid grid-cols-3 gap-2">
        <div className="col-span-2">
          <Field label="Cidade">
            <input
              value={address.city}
              onChange={(e) => onChange('city', e.target.value)}
              className="w-full rounded border border-zinc-200 px-2 py-1 text-sm"
            />
          </Field>
        </div>
        <Field label="UF">
          <input
            value={address.state}
            onChange={(e) => onChange('state', e.target.value)}
            maxLength={2}
            className="w-full rounded border border-zinc-200 px-2 py-1 text-sm uppercase"
          />
        </Field>
      </div>
    </Card>
  )
}
