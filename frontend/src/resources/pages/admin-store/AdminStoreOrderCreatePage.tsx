import { useEffect, useMemo, useRef, useState, type FormEvent } from 'react'
import {
  ArrowLeft,
  Bike,
  CheckCircle2,
  ImageOff,
  Minus,
  Package,
  Plus,
  Receipt,
  Search,
  ShoppingCart,
  Store as StoreIcon,
  Trash2,
  User,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  AdminStorePageShell,
  AdminPageHeader,
  FormField,
  FormSection,
  EmptyState,
  showToast,
  getCsrfToken,
  formatCurrency,
  useStoreContext,
} from '@/components/admin-store'

type Product = {
  id: number
  name: string
  description: string
  sku: string
  price: number
  promo_price: number | null
  category_id: number | null
  category_name: string
  image: string
  type: 'simple' | 'combo'
  active: boolean
}

type Customer = {
  id: number
  name: string
  whatsapp: string
}

type Zone = {
  id: number
  city_id: number
  neighborhood: string
  fee: number
}

type PaymentMethod = {
  id: number
  name: string
  type: string
}

type InitialItem = {
  product_id: number
  quantity: number
  unit_price: number
  product_name: string
  customization_data: Record<string, unknown> | null
}

type Prefill = {
  customer_name: string
  customer_phone: string
  notes: string
  delivery_fee: number
  discount: number
  delivery_type: 'delivery' | 'pickup'
  street: string
  number: string
  complement: string
  city_id: number | null
  zone_id: number | null
  payment_method_id: number | null
  cash_amount: number | null
  items: InitialItem[]
}

type OrderFormPayload = {
  is_edit?: boolean
  order_id?: number
  order_number?: number
  products: Product[]
  categories: Array<{ id: number; name: string }>
  customers: Customer[]
  cities: Array<{ id: number; name: string }>
  zones_by_city: Record<number, Zone[]>
  payment_methods: PaymentMethod[]
  defaults: {
    customer_name: string
    customer_phone: string
    notes: string
    delivery_fee: number
    discount: number
  }
  prefill?: Prefill
  urls: {
    list: string
    submit: string
    show?: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_ORDER_FORM__?: OrderFormPayload
  }
}

type CartItem = {
  product_id: number
  name: string
  unit_price: number
  qty: number
  image: string
  customization_data?: Record<string, unknown> | null
}

function resolveImage(path: string): string {
  if (!path) return ''
  if (/^(https?:)?\/\//i.test(path) || path.startsWith('data:')) return path
  return path.startsWith('/') ? path : `/${path}`
}

function moneyMask(raw: string): string {
  const digits = raw.replace(/[^\d,]/g, '')
  if (!digits) return ''
  const parts = digits.split(',')
  const intPart = parts[0].replace(/^0+(?=\d)/, '') || '0'
  if (parts.length === 1) return intPart
  return `${intPart},${parts.slice(1).join('').slice(0, 2)}`
}

function parseMoney(raw: string | number): number {
  if (typeof raw === 'number') return Number.isFinite(raw) ? raw : 0
  const cleaned = (raw || '0').replace(/\./g, '').replace(',', '.')
  const n = Number.parseFloat(cleaned)
  return Number.isFinite(n) ? n : 0
}

function maskPhone(raw: string): string {
  const d = raw.replace(/\D/g, '').slice(0, 11)
  if (d.length === 0) return ''
  if (d.length <= 2) return `(${d}`
  if (d.length <= 6) return `(${d.slice(0, 2)}) ${d.slice(2)}`
  if (d.length <= 10) return `(${d.slice(0, 2)}) ${d.slice(2, 6)}-${d.slice(6)}`
  return `(${d.slice(0, 2)}) ${d.slice(2, 7)}-${d.slice(7)}`
}

export default function AdminStoreOrderCreatePage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_ORDER_FORM__) || ({} as OrderFormPayload)

  const isEdit = !!payload.is_edit
  const prefill = payload.prefill
  const orderNumber = payload.order_number ?? payload.order_id ?? 0

  function moneyToBr(n: number | null | undefined): string {
    return Number(n ?? 0).toFixed(2).replace('.', ',')
  }

  // Catalog filters
  const [searchTerm, setSearchTerm] = useState('')
  const [categoryFilter, setCategoryFilter] = useState<string>('all')

  // Cart — hydrated from prefill on edit
  const [cart, setCart] = useState<CartItem[]>(() => {
    if (!prefill?.items?.length) return []
    return prefill.items
      .filter((it) => it.product_id > 0 && it.quantity > 0)
      .map((it) => ({
        product_id: it.product_id,
        name: it.product_name,
        unit_price: it.unit_price,
        qty: it.quantity,
        image: '',
        customization_data: it.customization_data ?? null,
      }))
  })

  // Customer
  const initialName = prefill?.customer_name ?? payload.defaults?.customer_name ?? ''
  const initialPhone = prefill?.customer_phone ?? payload.defaults?.customer_phone ?? ''
  const [customerName, setCustomerName] = useState(initialName)
  const [customerPhone, setCustomerPhone] = useState(maskPhone(initialPhone))
  const [customerPicker, setCustomerPicker] = useState('')
  const [showCustomerSuggestions, setShowCustomerSuggestions] = useState(false)

  // Delivery
  const [deliveryType, setDeliveryType] = useState<'delivery' | 'pickup'>(prefill?.delivery_type ?? 'delivery')
  const [selectedCityId, setSelectedCityId] = useState<number | ''>(prefill?.city_id ?? '')
  const [selectedZoneId, setSelectedZoneId] = useState<number | ''>(prefill?.zone_id ?? '')
  const [street, setStreet] = useState(prefill?.street ?? '')
  const [number, setNumber] = useState(prefill?.number ?? '')
  const [complement, setComplement] = useState(prefill?.complement ?? '')

  // Payment
  const [paymentMethodId, setPaymentMethodId] = useState<number | ''>(prefill?.payment_method_id ?? '')
  const [cashAmount, setCashAmount] = useState(
    prefill?.cash_amount != null ? moneyToBr(prefill.cash_amount) : '',
  )

  // Totals
  const [deliveryFeeRaw, setDeliveryFeeRaw] = useState(moneyToBr(prefill?.delivery_fee ?? 0))
  const [discountRaw, setDiscountRaw] = useState(moneyToBr(prefill?.discount ?? 0))
  const [notes, setNotes] = useState(prefill?.notes ?? payload.defaults?.notes ?? '')

  const [submitting, setSubmitting] = useState(false)
  const formRef = useRef<HTMLFormElement>(null)

  const products = payload.products ?? []
  const categories = payload.categories ?? []
  const customers = payload.customers ?? []
  const cities = payload.cities ?? []
  const zonesByCity = payload.zones_by_city ?? {}
  const paymentMethods = payload.payment_methods ?? []

  const filteredProducts = useMemo(() => {
    const term = searchTerm.trim().toLowerCase()
    return products
      .filter((p) => p.active)
      .filter((p) => {
        if (categoryFilter !== 'all') {
          if (categoryFilter === 'none') {
            if (p.category_id !== null) return false
          } else {
            if (p.category_id !== Number(categoryFilter)) return false
          }
        }
        if (!term) return true
        return (
          p.name.toLowerCase().includes(term) ||
          p.sku.toLowerCase().includes(term) ||
          p.description.toLowerCase().includes(term)
        )
      })
  }, [products, searchTerm, categoryFilter])

  const availableZones = useMemo<Zone[]>(() => {
    if (!selectedCityId) return []
    return zonesByCity[selectedCityId] ?? []
  }, [zonesByCity, selectedCityId])

  // When zone changes, auto-fill delivery fee — skip the initial render in edit
  // mode so the prefilled (possibly manual) fee isn't overwritten.
  const zoneAutofillSkipped = useRef(isEdit)
  useEffect(() => {
    if (deliveryType !== 'delivery' || !selectedZoneId) return
    if (zoneAutofillSkipped.current) {
      zoneAutofillSkipped.current = false
      return
    }
    const zone = availableZones.find((z) => z.id === Number(selectedZoneId))
    if (zone) setDeliveryFeeRaw(zone.fee.toFixed(2).replace('.', ','))
  }, [selectedZoneId, availableZones, deliveryType])

  // Hydrate cart image from products when entering edit mode.
  useEffect(() => {
    if (!isEdit || products.length === 0) return
    setCart((prev) =>
      prev.map((it) =>
        it.image
          ? it
          : { ...it, image: products.find((p) => p.id === it.product_id)?.image ?? '' },
      ),
    )
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [products.length])

  // Reset delivery fee when switching to pickup
  useEffect(() => {
    if (deliveryType === 'pickup') {
      setDeliveryFeeRaw('0,00')
      setSelectedZoneId('')
    }
  }, [deliveryType])

  const subtotal = useMemo(
    () => cart.reduce((acc, it) => acc + it.unit_price * it.qty, 0),
    [cart],
  )
  const deliveryFee = deliveryType === 'pickup' ? 0 : parseMoney(deliveryFeeRaw)
  const discount = parseMoney(discountRaw)
  const total = Math.max(0, subtotal + deliveryFee - discount)

  const selectedPayment = paymentMethods.find((pm) => pm.id === Number(paymentMethodId))
  const requiresCash = selectedPayment?.type === 'cash'
  const cashChange = requiresCash ? Math.max(0, parseMoney(cashAmount) - total) : 0

  function addToCart(p: Product) {
    const unit = p.promo_price ?? p.price
    setCart((prev) => {
      const existing = prev.find((it) => it.product_id === p.id)
      if (existing) {
        return prev.map((it) =>
          it.product_id === p.id ? { ...it, qty: it.qty + 1 } : it,
        )
      }
      return [...prev, { product_id: p.id, name: p.name, unit_price: unit, qty: 1, image: p.image }]
    })
  }

  function updateQty(productId: number, delta: number) {
    setCart((prev) =>
      prev
        .map((it) => (it.product_id === productId ? { ...it, qty: it.qty + delta } : it))
        .filter((it) => it.qty > 0),
    )
  }

  function removeFromCart(productId: number) {
    setCart((prev) => prev.filter((it) => it.product_id !== productId))
  }

  function pickCustomer(c: Customer) {
    setCustomerName(c.name)
    setCustomerPhone(maskPhone(c.whatsapp))
    setCustomerPicker('')
    setShowCustomerSuggestions(false)
  }

  const customerSuggestions = useMemo(() => {
    const term = customerPicker.trim().toLowerCase()
    if (!term) return [] as Customer[]
    return customers
      .filter(
        (c) =>
          c.name.toLowerCase().includes(term) ||
          c.whatsapp.replace(/\D/g, '').includes(term.replace(/\D/g, '')),
      )
      .slice(0, 8)
  }, [customers, customerPicker])

  function validate(): string | null {
    if (cart.length === 0) return 'Adicione ao menos um produto ao pedido.'
    if (!customerName.trim()) return 'Informe o nome do cliente.'
    const digits = customerPhone.replace(/\D/g, '')
    if (digits.length < 10) return 'Informe o WhatsApp do cliente (mínimo 10 dígitos com DDD).'
    if (deliveryType === 'delivery') {
      if (!selectedZoneId) return 'Selecione a cidade e o bairro para entrega.'
      if (!street.trim() || !number.trim()) return 'Informe rua e número do endereço.'
    }
    if (requiresCash) {
      const paid = parseMoney(cashAmount)
      if (paid < total) return `Valor recebido em dinheiro deve ser ≥ ${formatCurrency(total)}.`
    }
    return null
  }

  function handleSubmit(e: FormEvent) {
    const err = validate()
    if (err) {
      e.preventDefault()
      showToast(err, 'error')
      return
    }
    setSubmitting(true)
    // Native form submission proceeds — payload uses field names PHP expects
  }

  return (
    <AdminStorePageShell section="orders">
      <AdminPageHeader
        title={isEdit ? `Editar pedido #${orderNumber}` : 'Novo pedido manual'}
        description={
          isEdit
            ? 'Atualize itens, cliente, entrega ou pagamento. Apenas pedidos pendentes podem ser editados.'
            : 'Crie um pedido em nome do cliente — útil para canais offline, telefone ou WhatsApp.'
        }
        icon={<Receipt className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <Button asChild variant="outline">
            <a href={isEdit && payload.urls?.show ? payload.urls.show : payload.urls?.list} className="gap-2">
              <ArrowLeft className="h-4 w-4" />
              Voltar
            </a>
          </Button>
        }
      />

      <form ref={formRef} action={payload.urls?.submit} method="POST" onSubmit={handleSubmit}>
        <input type="hidden" name="csrf_token" value={getCsrfToken()} />
        {/* Cart hidden fields for PHP — generated from state */}
        {cart.map((it, idx) => (
          <div key={`${it.product_id}-${idx}`} className="hidden">
            <input type="hidden" name="product_id[]" value={it.product_id} />
            <input type="hidden" name="quantity[]" value={it.qty} />
            <input
              type="hidden"
              name="customization_data_json[]"
              value={it.customization_data ? JSON.stringify(it.customization_data) : ''}
            />
          </div>
        ))}

        <div className="grid gap-5 lg:grid-cols-[1fr_400px]">
          {/* LEFT — catalog + details */}
          <div className="space-y-5 min-w-0">
            {/* Catalog picker */}
            <FormSection
              title="Adicionar produtos"
              description={`${cart.length} ${cart.length === 1 ? 'item' : 'itens'} no carrinho — ${formatCurrency(subtotal)} em produtos.`}
            >
              <div className="flex flex-wrap items-end gap-3">
                <div className="relative flex-1 min-w-[200px]">
                  <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" />
                  <Input
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    placeholder="Buscar por nome, SKU ou descrição..."
                    className="pl-9 h-9"
                  />
                </div>
                <div className="min-w-[180px]">
                  <select
                    value={categoryFilter}
                    onChange={(e) => setCategoryFilter(e.target.value)}
                    className="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
                  >
                    <option value="all">Todas as categorias</option>
                    <option value="none">Sem categoria</option>
                    {categories.map((c) => (
                      <option key={c.id} value={c.id}>
                        {c.name}
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              <div className="grid gap-2 sm:grid-cols-2 max-h-[420px] overflow-y-auto pr-1">
                {filteredProducts.length === 0 ? (
                  <p className="col-span-full py-6 text-center text-sm text-zinc-500">
                    Nenhum produto encontrado.
                  </p>
                ) : (
                  filteredProducts.map((p) => {
                    const unit = p.promo_price ?? p.price
                    const src = resolveImage(p.image)
                    return (
                      <button
                        key={p.id}
                        type="button"
                        onClick={() => addToCart(p)}
                        className="flex items-center gap-2.5 rounded-lg border border-zinc-200 bg-white p-2 text-left transition hover:border-zinc-400 hover:bg-zinc-50"
                      >
                        {src ? (
                          <img src={src} alt="" className="h-12 w-12 shrink-0 rounded-md border border-zinc-200 object-cover" />
                        ) : (
                          <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-md border border-zinc-200 bg-zinc-50 text-zinc-400">
                            <ImageOff className="h-4 w-4" />
                          </span>
                        )}
                        <div className="min-w-0 flex-1">
                          <p className="text-sm font-medium text-zinc-800 truncate">{p.name}</p>
                          <p className="text-xs text-zinc-500 truncate">
                            {p.category_name || 'Sem categoria'}
                          </p>
                          <div className="flex items-center gap-2 mt-0.5">
                            {p.promo_price != null && (
                              <span className="text-[10px] text-zinc-400 line-through">{formatCurrency(p.price)}</span>
                            )}
                            <span className="text-sm font-semibold text-zinc-800">{formatCurrency(unit)}</span>
                          </div>
                        </div>
                        <Plus className="h-4 w-4 shrink-0 text-zinc-400" />
                      </button>
                    )
                  })
                )}
              </div>
            </FormSection>

            {/* Customer */}
            <FormSection
              title="Cliente"
              description="Busque um cliente já cadastrado ou informe os dados manualmente."
              className="relative"
            >
              <FormField
                label="Buscar cliente cadastrado"
                htmlFor="cs-search"
                hint="Digite nome ou WhatsApp para autocompletar."
              >
                <div className="relative">
                  <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" />
                  <Input
                    id="cs-search"
                    value={customerPicker}
                    onChange={(e) => {
                      setCustomerPicker(e.target.value)
                      setShowCustomerSuggestions(true)
                    }}
                    onFocus={() => setShowCustomerSuggestions(true)}
                    onBlur={() => setTimeout(() => setShowCustomerSuggestions(false), 200)}
                    placeholder="Nome ou (11) 98888-7777..."
                    className="pl-9"
                  />
                  {showCustomerSuggestions && customerSuggestions.length > 0 && (
                    <ul className="absolute z-10 mt-1 w-full overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg">
                      {customerSuggestions.map((c) => (
                        <li key={c.id}>
                          <button
                            type="button"
                            onMouseDown={(e) => {
                              e.preventDefault()
                              pickCustomer(c)
                            }}
                            className="block w-full px-3 py-2 text-left text-sm hover:bg-zinc-50"
                          >
                            <span className="font-medium text-zinc-800">{c.name}</span>
                            {c.whatsapp && (
                              <span className="ml-2 text-xs text-zinc-500">{maskPhone(c.whatsapp)}</span>
                            )}
                          </button>
                        </li>
                      ))}
                    </ul>
                  )}
                </div>
              </FormField>

              <div className="grid gap-4 md:grid-cols-2">
                <FormField label="Nome do cliente" htmlFor="cs-name" required>
                  <Input
                    id="cs-name"
                    name="customer_name"
                    value={customerName}
                    onChange={(e) => setCustomerName(e.target.value)}
                    maxLength={150}
                  />
                </FormField>

                <FormField label="WhatsApp" htmlFor="cs-phone" required>
                  <Input
                    id="cs-phone"
                    name="customer_phone"
                    value={customerPhone}
                    onChange={(e) => setCustomerPhone(maskPhone(e.target.value))}
                    inputMode="tel"
                    placeholder="(11) 98888-7777"
                  />
                </FormField>
              </div>
            </FormSection>

            {/* Delivery */}
            <FormSection title="Entrega" description="Pedido com entrega ou retirada na loja.">
              <div className="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  onClick={() => setDeliveryType('delivery')}
                  className={`flex items-center justify-center gap-2 rounded-xl border p-3 transition ${
                    deliveryType === 'delivery'
                      ? 'border-zinc-900 bg-zinc-900 text-white'
                      : 'border-zinc-200 bg-white text-zinc-700 hover:border-zinc-400'
                  }`}
                >
                  <Bike className="h-4 w-4" />
                  Entrega
                </button>
                <button
                  type="button"
                  onClick={() => setDeliveryType('pickup')}
                  className={`flex items-center justify-center gap-2 rounded-xl border p-3 transition ${
                    deliveryType === 'pickup'
                      ? 'border-zinc-900 bg-zinc-900 text-white'
                      : 'border-zinc-200 bg-white text-zinc-700 hover:border-zinc-400'
                  }`}
                >
                  <StoreIcon className="h-4 w-4" />
                  Retirada na loja
                </button>
              </div>
              <input type="hidden" name="delivery_type" value={deliveryType} />

              {deliveryType === 'delivery' && (
                <>
                  <div className="grid gap-4 md:grid-cols-2">
                    <FormField label="Cidade" htmlFor="dl-city" required>
                      <select
                        id="dl-city"
                        value={selectedCityId}
                        onChange={(e) => {
                          setSelectedCityId(e.target.value ? Number(e.target.value) : '')
                          setSelectedZoneId('')
                        }}
                        className="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
                      >
                        <option value="">Selecione…</option>
                        {cities.map((c) => (
                          <option key={c.id} value={c.id}>
                            {c.name}
                          </option>
                        ))}
                      </select>
                    </FormField>

                    <FormField label="Bairro" htmlFor="dl-zone" required>
                      <select
                        id="dl-zone"
                        name="zone_id"
                        value={selectedZoneId}
                        onChange={(e) => setSelectedZoneId(e.target.value ? Number(e.target.value) : '')}
                        disabled={!selectedCityId || availableZones.length === 0}
                        className="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400 disabled:bg-zinc-50 disabled:text-zinc-400"
                      >
                        <option value="">{selectedCityId ? 'Selecione…' : 'Selecione a cidade'}</option>
                        {availableZones.map((z) => (
                          <option key={z.id} value={z.id}>
                            {z.neighborhood} — {formatCurrency(z.fee)}
                          </option>
                        ))}
                      </select>
                    </FormField>
                  </div>

                  <div className="grid gap-4 md:grid-cols-[1fr_120px]">
                    <FormField label="Rua" htmlFor="dl-street" required>
                      <Input
                        id="dl-street"
                        name="street"
                        value={street}
                        onChange={(e) => setStreet(e.target.value)}
                        maxLength={200}
                      />
                    </FormField>

                    <FormField label="Número" htmlFor="dl-number" required>
                      <Input
                        id="dl-number"
                        name="number"
                        value={number}
                        onChange={(e) => setNumber(e.target.value)}
                        maxLength={30}
                      />
                    </FormField>
                  </div>

                  <FormField label="Complemento" htmlFor="dl-cmp" hint="Apartamento, bloco, referência (opcional)">
                    <Input
                      id="dl-cmp"
                      name="complement"
                      value={complement}
                      onChange={(e) => setComplement(e.target.value)}
                      maxLength={120}
                    />
                  </FormField>
                </>
              )}
            </FormSection>

            {/* Payment */}
            <FormSection title="Pagamento">
              <FormField label="Método de pagamento" htmlFor="pm-id" required>
                <select
                  id="pm-id"
                  name="payment_method_id"
                  value={paymentMethodId}
                  onChange={(e) => setPaymentMethodId(e.target.value ? Number(e.target.value) : '')}
                  className="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
                >
                  <option value="">Selecione…</option>
                  {paymentMethods.map((pm) => (
                    <option key={pm.id} value={pm.id}>
                      {pm.name}
                    </option>
                  ))}
                </select>
              </FormField>

              {requiresCash && (
                <div className="grid gap-4 md:grid-cols-2">
                  <FormField label="Cliente paga com" htmlFor="cash-amount" hint="Valor que o cliente vai entregar em dinheiro">
                    <div className="relative">
                      <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">R$</span>
                      <Input
                        id="cash-amount"
                        name="cash_amount"
                        value={cashAmount}
                        onChange={(e) => setCashAmount(moneyMask(e.target.value))}
                        inputMode="decimal"
                        placeholder="0,00"
                        className="pl-9"
                      />
                    </div>
                  </FormField>
                  <FormField label="Troco">
                    <div className="flex h-9 items-center rounded-md border border-zinc-200 bg-zinc-50 px-3 text-sm font-mono text-zinc-700">
                      {formatCurrency(cashChange)}
                    </div>
                  </FormField>
                </div>
              )}
            </FormSection>

            {/* Notes */}
            <FormSection title="Observações" description="Opcional. Visível na cozinha e no recibo.">
              <textarea
                name="notes"
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                rows={3}
                maxLength={500}
                placeholder="Ex.: Sem cebola, entregar no portão dos fundos..."
                className="block w-full rounded-md border border-zinc-200 bg-white p-3 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
              />
            </FormSection>
          </div>

          {/* RIGHT — sticky summary */}
          <aside className="space-y-3 lg:sticky lg:top-16 lg:self-start">
            <div className="rounded-2xl border border-zinc-200 bg-white shadow-sm">
              <header className="border-b border-zinc-200 p-4">
                <h2 className="flex items-center gap-2 text-sm font-semibold text-zinc-800">
                  <ShoppingCart className="h-4 w-4" />
                  Resumo do pedido
                </h2>
                <p className="text-xs text-zinc-500">{cart.length} {cart.length === 1 ? 'item' : 'itens'}</p>
              </header>

              {cart.length === 0 ? (
                <div className="p-6 text-center">
                  <Package className="mx-auto mb-2 h-8 w-8 text-zinc-300" />
                  <p className="text-sm text-zinc-500">Adicione produtos ao carrinho.</p>
                </div>
              ) : (
                <ul className="divide-y divide-zinc-100 max-h-72 overflow-y-auto">
                  {cart.map((it) => (
                    <li key={it.product_id} className="flex items-start gap-2 p-3">
                      <div className="min-w-0 flex-1">
                        <p className="text-sm font-medium text-zinc-800 truncate">{it.name}</p>
                        <p className="text-xs text-zinc-500">{formatCurrency(it.unit_price)} cada</p>
                        <div className="mt-1 flex items-center gap-1">
                          <button
                            type="button"
                            onClick={() => updateQty(it.product_id, -1)}
                            className="flex h-6 w-6 items-center justify-center rounded-md border border-zinc-200 text-zinc-600 hover:bg-zinc-50"
                            aria-label="Diminuir"
                          >
                            <Minus className="h-3 w-3" />
                          </button>
                          <span className="min-w-[24px] text-center text-sm font-medium">{it.qty}</span>
                          <button
                            type="button"
                            onClick={() => updateQty(it.product_id, 1)}
                            className="flex h-6 w-6 items-center justify-center rounded-md border border-zinc-200 text-zinc-600 hover:bg-zinc-50"
                            aria-label="Aumentar"
                          >
                            <Plus className="h-3 w-3" />
                          </button>
                          <button
                            type="button"
                            onClick={() => removeFromCart(it.product_id)}
                            className="ml-1 flex h-6 w-6 items-center justify-center rounded-md text-red-500 hover:bg-red-50"
                            aria-label="Remover item"
                          >
                            <Trash2 className="h-3 w-3" />
                          </button>
                        </div>
                      </div>
                      <p className="text-sm font-semibold text-zinc-800 whitespace-nowrap">
                        {formatCurrency(it.unit_price * it.qty)}
                      </p>
                    </li>
                  ))}
                </ul>
              )}

              <div className="space-y-2 border-t border-zinc-200 p-4">
                <div className="flex items-center justify-between gap-2">
                  <Label className="text-xs text-zinc-500">Taxa de entrega</Label>
                  <div className="relative w-28">
                    <span className="pointer-events-none absolute left-2 top-1/2 -translate-y-1/2 text-xs text-zinc-400">R$</span>
                    <Input
                      name="delivery_fee"
                      value={deliveryFeeRaw}
                      onChange={(e) => setDeliveryFeeRaw(moneyMask(e.target.value))}
                      inputMode="decimal"
                      disabled={deliveryType === 'pickup'}
                      className="pl-7 h-7 text-right text-xs font-mono"
                    />
                  </div>
                </div>
                <div className="flex items-center justify-between gap-2">
                  <Label className="text-xs text-zinc-500">Desconto</Label>
                  <div className="relative w-28">
                    <span className="pointer-events-none absolute left-2 top-1/2 -translate-y-1/2 text-xs text-zinc-400">R$</span>
                    <Input
                      name="discount"
                      value={discountRaw}
                      onChange={(e) => setDiscountRaw(moneyMask(e.target.value))}
                      inputMode="decimal"
                      className="pl-7 h-7 text-right text-xs font-mono"
                    />
                  </div>
                </div>
                <div className="flex items-center justify-between border-t border-zinc-200 pt-2">
                  <span className="text-xs text-zinc-500">Subtotal</span>
                  <span className="text-sm font-medium text-zinc-700">{formatCurrency(subtotal)}</span>
                </div>
                <div className="flex items-center justify-between text-base font-semibold text-zinc-900">
                  <span>Total</span>
                  <span>{formatCurrency(total)}</span>
                </div>
              </div>

              <div className="border-t border-zinc-200 p-3">
                <Button
                  type="submit"
                  disabled={submitting || cart.length === 0}
                  className="w-full gap-2"
                >
                  <CheckCircle2 className="h-4 w-4" />
                  {submitting
                    ? (isEdit ? 'Salvando...' : 'Criando...')
                    : (isEdit ? 'Salvar alterações' : 'Criar pedido')}
                </Button>
              </div>
            </div>

            {cart.length === 0 && (
              <EmptyState
                title="Selecione produtos"
                description="Use a busca à esquerda para adicionar itens ao pedido."
                icon={<User className="h-5 w-5" />}
                className="p-4 text-xs"
              />
            )}
          </aside>
        </div>
      </form>
    </AdminStorePageShell>
  )
}
