import { useEffect, useMemo, useState, type FormEvent } from 'react'
import {
  CheckCircle2,
  Filter,
  Gift,
  Heart,
  ImageOff,
  Pencil,
  Percent,
  Plus,
  Save,
  Search,
  Trash2,
  Truck,
  UserCheck,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  AdminStorePageShell,
  AdminPageHeader,
  FormField,
  FormSection,
  ConfirmDialog,
  EmptyState,
  showToast,
  getCsrfToken,
  formatCurrency,
  useStoreContext,
} from '@/components/admin-store'

type Coupon = {
  id: number
  coupon_code: string
  customer_phone: string
  discount_percentage: number
  usage_limit: number
  times_used: number
  is_exhausted: boolean
  used_at: string | null
  created_at: string
}

type ProductRow = {
  id: number
  name: string
  category_id: number | null
  price: number
  image: string
  embedded_fee_enabled: boolean
}

type LoyaltyPayload = {
  embedded_delivery_fee: number
  loyalty_active: boolean
  loyalty_discount: number
  loyalty_message: string
  coupon_prefix: string
  coupons: Coupon[]
  coupons_stats: { total: number; active: number; used: number; totalUsage: number }
  categories: Array<{ id: number; name: string }>
  all_products: ProductRow[]
  flash: { success: string | null; error: string | null }
  urls: {
    submit: string
    coupons_create: string
    coupons_edit_base: string
    coupons_destroy_base: string
    coupons_toggle_base: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_LOYALTY_DISCOUNT__?: LoyaltyPayload
  }
}

function moneyMask(raw: string): string {
  const digits = raw.replace(/[^\d,]/g, '')
  if (!digits) return ''
  const parts = digits.split(',')
  const intPart = parts[0].replace(/^0+(?=\d)/, '') || '0'
  if (parts.length === 1) return intPart
  return `${intPart},${parts.slice(1).join('').slice(0, 2)}`
}

function percentMask(raw: string): string {
  const digits = raw.replace(/[^\d,]/g, '')
  if (!digits) return ''
  const parts = digits.split(',')
  const intPart = parts[0].replace(/^0+(?=\d)/, '') || '0'
  if (parts.length === 1) return intPart
  return `${intPart},${parts.slice(1).join('').slice(0, 2)}`
}

function formatBR(n: number, decimals = 2): string {
  return n.toFixed(decimals).replace('.', ',')
}

function resolveImage(path: string): string {
  if (!path) return ''
  if (/^(https?:)?\/\//i.test(path) || path.startsWith('data:')) return path
  return path.startsWith('/') ? path : `/${path}`
}

function formatPhone(raw: string): string {
  const d = (raw || '').replace(/\D/g, '')
  if (d.length === 0) return ''
  if (d.length === 11) return `(${d.slice(0, 2)}) ${d.slice(2, 7)}-${d.slice(7)}`
  if (d.length === 10) return `(${d.slice(0, 2)}) ${d.slice(2, 6)}-${d.slice(6)}`
  if (d.length === 13 && d.startsWith('55')) {
    const local = d.slice(2)
    return `+55 (${local.slice(0, 2)}) ${local.slice(2, 7)}-${local.slice(7)}`
  }
  return raw
}

export default function AdminStoreLoyaltyDiscountPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_LOYALTY_DISCOUNT__) ||
    ({} as LoyaltyPayload)
  const urls = payload.urls

  // Form state
  const [embeddedFee, setEmbeddedFee] = useState(formatBR(payload.embedded_delivery_fee ?? 0))
  const [loyaltyActive, setLoyaltyActive] = useState<boolean>(!!payload.loyalty_active)
  const [loyaltyDiscount, setLoyaltyDiscount] = useState(formatBR(payload.loyalty_discount ?? 0))
  const [loyaltyMessage, setLoyaltyMessage] = useState(payload.loyalty_message ?? '')
  const [couponPrefix, setCouponPrefix] = useState(payload.coupon_prefix ?? 'WOLL')

  // Product selector for embedded fee
  const [enabledProducts, setEnabledProducts] = useState<Set<number>>(
    () => new Set((payload.all_products ?? []).filter((p) => p.embedded_fee_enabled).map((p) => p.id)),
  )
  const [productSearch, setProductSearch] = useState('')
  const [productCategoryFilter, setProductCategoryFilter] = useState<string>('all')

  // Coupons list
  const [coupons, setCoupons] = useState<Coupon[]>(payload.coupons ?? [])
  const [confirmDeleteCoupon, setConfirmDeleteCoupon] = useState<Coupon | null>(null)

  useEffect(() => {
    if (payload.flash?.error) showToast(payload.flash.error, 'error')
    if (payload.flash?.success) showToast(payload.flash.success, 'success')
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  const filteredProducts = useMemo(() => {
    const term = productSearch.trim().toLowerCase()
    return (payload.all_products ?? []).filter((p) => {
      if (productCategoryFilter !== 'all') {
        if (productCategoryFilter === 'none') {
          if (p.category_id !== null) return false
        } else {
          if (p.category_id !== Number(productCategoryFilter)) return false
        }
      }
      if (!term) return true
      return p.name.toLowerCase().includes(term)
    })
  }, [payload.all_products, productSearch, productCategoryFilter])

  function toggleProduct(id: number) {
    setEnabledProducts((prev) => {
      const next = new Set(prev)
      if (next.has(id)) next.delete(id)
      else next.add(id)
      return next
    })
  }

  function selectAllVisible() {
    setEnabledProducts((prev) => {
      const next = new Set(prev)
      filteredProducts.forEach((p) => next.add(p.id))
      return next
    })
  }

  function deselectAllVisible() {
    setEnabledProducts((prev) => {
      const next = new Set(prev)
      filteredProducts.forEach((p) => next.delete(p.id))
      return next
    })
  }

  function handleSubmit(e: FormEvent) {
    const pct = Number.parseFloat((loyaltyDiscount || '0').replace(',', '.'))
    if (loyaltyActive && (pct <= 0 || pct > 100)) {
      e.preventDefault()
      showToast('Desconto progressivo deve ser entre 1% e 100%.', 'error')
      return
    }
  }

  async function deleteCoupon() {
    if (!confirmDeleteCoupon) return
    const formData = new FormData()
    const csrf = getCsrfToken()
    if (csrf) formData.append('csrf_token', csrf)

    try {
      const res = await fetch(`${urls.coupons_destroy_base}${confirmDeleteCoupon.id}/delete`, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean; message?: string } | null
      if (data?.success) {
        setCoupons((rows) => rows.filter((r) => r.id !== confirmDeleteCoupon.id))
        showToast('Cupom removido.', 'success')
      } else {
        showToast(data?.message || 'Falha ao remover cupom.', 'error')
      }
    } catch {
      showToast('Falha de rede ao remover cupom.', 'error')
    }
  }

  return (
    <AdminStorePageShell section="loyalty">
      <AdminPageHeader
        title="Fidelidade & Descontos"
        description="Configure taxa de entrega embutida, desconto por cadastro de CPF/aniversário e cupons promocionais."
        icon={<Heart className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <Button asChild className="gap-2">
            <a href={urls.coupons_create}>
              <Plus className="h-4 w-4" />
              Novo cupom
            </a>
          </Button>
        }
      />

      <section className="grid gap-3 sm:grid-cols-4">
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Total de cupons</p>
          <p className="mt-1 text-2xl font-semibold text-zinc-800">{payload.coupons_stats?.total ?? 0}</p>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Ativos</p>
          <p className="mt-1 text-2xl font-semibold text-emerald-600">{payload.coupons_stats?.active ?? 0}</p>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Utilizados</p>
          <p className="mt-1 text-2xl font-semibold text-zinc-500">{payload.coupons_stats?.used ?? 0}</p>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Total de usos</p>
          <p className="mt-1 text-2xl font-semibold text-violet-600">{payload.coupons_stats?.totalUsage ?? 0}</p>
        </div>
      </section>

      <form action={urls.submit} method="POST" onSubmit={handleSubmit} className="space-y-5">
        <input type="hidden" name="csrf_token" value={getCsrfToken()} />
        <input type="hidden" name="embedded_fee_products_present" value="1" />

        <Tabs defaultValue="taxa">
          <TabsList className="h-auto flex-wrap p-1 bg-white border border-zinc-200 rounded-xl">
            <TabsTrigger value="taxa" className="gap-1.5 px-3 py-1.5 text-xs sm:text-sm">
              <Truck className="h-3.5 w-3.5" />
              Taxa embutida
            </TabsTrigger>
            <TabsTrigger value="progressivo" className="gap-1.5 px-3 py-1.5 text-xs sm:text-sm">
              <UserCheck className="h-3.5 w-3.5" />
              Desconto progressivo
            </TabsTrigger>
            <TabsTrigger value="cupons" className="gap-1.5 px-3 py-1.5 text-xs sm:text-sm">
              <Gift className="h-3.5 w-3.5" />
              Cupons
            </TabsTrigger>
          </TabsList>

          {/* ── Taxa embutida ──────────────────────────────────────────── */}
          <TabsContent value="taxa" className="mt-4 space-y-5">
            <FormSection
              title="Taxa de entrega embutida"
              description="Quando ativada nos produtos selecionados, parte do preço cobre a entrega — o cliente vê 'Entrega grátis' no cardápio."
            >
              <FormField
                label="Valor embutido por pedido"
                htmlFor="ld-fee"
                hint="Valor descontado da taxa de entrega quando o cliente compra produtos elegíveis."
              >
                <div className="relative max-w-xs">
                  <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">R$</span>
                  <Input
                    id="ld-fee"
                    name="embedded_delivery_fee"
                    value={embeddedFee}
                    onChange={(e) => setEmbeddedFee(moneyMask(e.target.value))}
                    inputMode="decimal"
                    placeholder="0,00"
                    className="pl-9"
                  />
                </div>
              </FormField>
            </FormSection>

            <FormSection
              title="Produtos elegíveis"
              description={`${enabledProducts.size} de ${payload.all_products?.length ?? 0} produtos com taxa embutida ativada.`}
            >
              <div className="flex flex-wrap items-end gap-3">
                <div className="relative flex-1 min-w-[200px]">
                  <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" />
                  <Input
                    value={productSearch}
                    onChange={(e) => setProductSearch(e.target.value)}
                    placeholder="Buscar produto..."
                    className="pl-9 h-9"
                  />
                </div>
                <div className="min-w-[180px]">
                  <select
                    value={productCategoryFilter}
                    onChange={(e) => setProductCategoryFilter(e.target.value)}
                    className="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
                  >
                    <option value="all">Todas as categorias</option>
                    <option value="none">Sem categoria</option>
                    {payload.categories?.map((c) => (
                      <option key={c.id} value={c.id}>
                        {c.name}
                      </option>
                    ))}
                  </select>
                </div>
                <Button type="button" size="sm" variant="outline" className="h-9" onClick={selectAllVisible}>
                  Marcar visíveis
                </Button>
                <Button type="button" size="sm" variant="ghost" className="h-9" onClick={deselectAllVisible}>
                  Desmarcar visíveis
                </Button>
              </div>

              <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 max-h-[400px] overflow-y-auto pr-1">
                {filteredProducts.length === 0 ? (
                  <p className="col-span-full py-6 text-center text-sm text-zinc-500">
                    Nenhum produto com esses filtros.
                  </p>
                ) : (
                  filteredProducts.map((p) => {
                    const enabled = enabledProducts.has(p.id)
                    const src = resolveImage(p.image)
                    return (
                      <label
                        key={p.id}
                        className={`flex items-center gap-2.5 rounded-lg border p-2 cursor-pointer transition ${
                          enabled
                            ? 'border-emerald-300 bg-emerald-50/50'
                            : 'border-zinc-200 bg-white hover:border-zinc-400'
                        }`}
                      >
                        <input
                          type="checkbox"
                          name="embedded_fee_products[]"
                          value={p.id}
                          checked={enabled}
                          onChange={() => toggleProduct(p.id)}
                          className="h-4 w-4 rounded border-zinc-300"
                        />
                        {src ? (
                          <img src={src} alt="" className="h-10 w-10 shrink-0 rounded-md border border-zinc-200 object-cover" />
                        ) : (
                          <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-zinc-200 bg-zinc-50 text-zinc-400">
                            <ImageOff className="h-4 w-4" />
                          </span>
                        )}
                        <div className="min-w-0 flex-1">
                          <p className="text-sm font-medium text-zinc-800 truncate">{p.name}</p>
                          <p className="text-xs text-zinc-500">{formatCurrency(p.price)}</p>
                        </div>
                      </label>
                    )
                  })
                )}
              </div>
            </FormSection>
          </TabsContent>

          {/* ── Desconto progressivo ───────────────────────────────────── */}
          <TabsContent value="progressivo" className="mt-4 space-y-5">
            <FormSection
              title="Desconto por cadastro completo"
              description="Oferece um desconto automático quando o cliente preenche CPF e data de nascimento no checkout."
            >
              <FormField label="Status do desconto">
                <label className="flex items-start gap-3 cursor-pointer rounded-xl border border-zinc-200 p-3 hover:bg-zinc-50">
                  <input
                    type="checkbox"
                    name="loyalty_active"
                    value="1"
                    checked={loyaltyActive}
                    onChange={(e) => setLoyaltyActive(e.target.checked)}
                    className="peer sr-only"
                  />
                  <span className="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
                    <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
                  </span>
                  <span className="flex-1 text-sm">
                    <span className="font-medium text-zinc-800">
                      {loyaltyActive ? 'Desconto ativo' : 'Desconto desativado'}
                    </span>
                    <span className="block text-xs text-zinc-500 mt-0.5">
                      Quando ativo, clientes que preencherem CPF e data de nascimento recebem desconto automático no primeiro pedido.
                    </span>
                  </span>
                </label>
              </FormField>

              <div className="grid gap-4 md:grid-cols-2">
                <FormField label="Percentual de desconto" htmlFor="ld-discount" required>
                  <div className="relative max-w-xs">
                    <Input
                      id="ld-discount"
                      name="loyalty_discount"
                      value={loyaltyDiscount}
                      onChange={(e) => setLoyaltyDiscount(percentMask(e.target.value))}
                      inputMode="decimal"
                      placeholder="10"
                      disabled={!loyaltyActive}
                      className="pr-10"
                    />
                    <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">%</span>
                  </div>
                </FormField>

                <FormField
                  label="Mensagem de boas-vindas"
                  htmlFor="ld-msg"
                  hint="Texto mostrado ao cliente após receber o desconto."
                >
                  <Input
                    id="ld-msg"
                    name="loyalty_message"
                    value={loyaltyMessage}
                    onChange={(e) => setLoyaltyMessage(e.target.value)}
                    maxLength={200}
                    placeholder="Bem-vindo! Você ganhou 10% de desconto."
                    disabled={!loyaltyActive}
                  />
                </FormField>
              </div>
            </FormSection>
          </TabsContent>

          {/* ── Cupons ─────────────────────────────────────────────────── */}
          <TabsContent value="cupons" className="mt-4 space-y-5">
            <FormSection
              title="Prefixo de cupons gerados"
              description="Usado quando o sistema gera códigos automáticos. Ex.: WOLL-AB12CD"
            >
              <FormField label="Prefixo" htmlFor="ld-prefix" hint="Até 10 caracteres, sem espaços.">
                <Input
                  id="ld-prefix"
                  name="coupon_prefix"
                  value={couponPrefix}
                  onChange={(e) =>
                    setCouponPrefix(e.target.value.toUpperCase().replace(/[^A-Z0-9_-]/g, '').slice(0, 10))
                  }
                  className="max-w-xs font-mono uppercase"
                  placeholder="WOLL"
                />
              </FormField>
            </FormSection>

            <FormSection
              title={`Cupons existentes (${coupons.length})`}
              description="Lista de todos os cupons criados manualmente para esta loja."
            >
              {coupons.length === 0 ? (
                <EmptyState
                  title="Sem cupons cadastrados"
                  description="Crie cupons individuais ou abertos para promoções."
                  icon={<Gift className="h-5 w-5" />}
                  action={
                    <Button asChild>
                      <a href={urls.coupons_create}>Criar primeiro cupom</a>
                    </Button>
                  }
                />
              ) : (
                <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                  {coupons.map((c) => (
                    <div
                      key={c.id}
                      className="group rounded-xl border border-zinc-200 bg-white p-3 transition hover:border-zinc-400 hover:shadow-sm"
                    >
                      <div className="mb-2 flex items-center justify-between gap-2">
                        <a
                          href={`${urls.coupons_edit_base}${c.id}/edit`}
                          className="font-mono text-sm font-semibold text-zinc-800 truncate hover:text-zinc-950 hover:underline"
                        >
                          {c.coupon_code}
                        </a>
                        {c.is_exhausted ? (
                          <Badge className="bg-zinc-100 text-zinc-600 border border-zinc-200 hover:bg-zinc-100 text-[10px] h-4">
                            Usado
                          </Badge>
                        ) : (
                          <Badge className="bg-emerald-100 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 text-[10px] h-4">
                            Ativo
                          </Badge>
                        )}
                      </div>

                      <div className="flex items-center gap-1.5 text-xs text-zinc-600">
                        <Percent className="h-3 w-3 text-zinc-400" />
                        <span className="font-semibold text-zinc-800">{formatBR(c.discount_percentage)}%</span>
                        <span>de desconto</span>
                      </div>

                      {c.customer_phone && (
                        <p className="mt-1 text-xs text-zinc-500 truncate">Para: {formatPhone(c.customer_phone)}</p>
                      )}

                      <p className="mt-1 text-[11px] text-zinc-400">
                        {c.times_used} uso{c.times_used === 1 ? '' : 's'}
                        {c.usage_limit > 0 ? ` / ${c.usage_limit} limite` : ' / ilimitado'}
                      </p>

                      <div className="mt-2 flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <Button asChild size="sm" variant="ghost" className="h-7 px-2 text-zinc-600">
                          <a href={`${urls.coupons_edit_base}${c.id}/edit`} aria-label="Editar">
                            <Pencil className="h-3 w-3" />
                          </a>
                        </Button>
                        <Button
                          type="button"
                          size="sm"
                          variant="ghost"
                          className="h-7 px-2 text-red-600 hover:text-red-700 hover:bg-red-50"
                          onClick={() => setConfirmDeleteCoupon(c)}
                          aria-label="Remover"
                        >
                          <Trash2 className="h-3 w-3" />
                        </Button>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </FormSection>
          </TabsContent>
        </Tabs>

        <div className="sticky bottom-0 -mx-5 mt-6 border-t border-zinc-200 bg-white/95 backdrop-blur px-5 py-3">
          <div className="mx-auto flex max-w-[1292px] flex-wrap items-center justify-end gap-2">
            <Button type="submit" className="gap-2">
              <Save className="h-4 w-4" />
              Salvar configurações
            </Button>
          </div>
        </div>
      </form>

      <ConfirmDialog
        open={!!confirmDeleteCoupon}
        onOpenChange={(open) => !open && setConfirmDeleteCoupon(null)}
        title="Remover este cupom?"
        description={
          confirmDeleteCoupon
            ? `O cupom "${confirmDeleteCoupon.coupon_code}" será removido permanentemente.`
            : null
        }
        confirmLabel="Remover"
        destructive
        onConfirm={deleteCoupon}
      />
    </AdminStorePageShell>
  )
}
