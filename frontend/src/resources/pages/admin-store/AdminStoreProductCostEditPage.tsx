import { useEffect, useMemo, useState, type FormEvent } from 'react'
import {
  ArrowLeft,
  Box,
  CheckCircle2,
  CircleDollarSign,
  Layers,
  Package,
  Pencil,
  Plus,
  Receipt,
  Save,
  Trash2,
  Utensils,
  X,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  AdminStorePageShell,
  AdminPageHeader,
  FormField,
  FormSection,
  formatCurrency,
  getCsrfToken,
  showToast,
  useStoreContext,
} from '@/components/admin-store'

type Payload = {
  product: { id: number; name: string; price: number }
  breakdown: {
    ingredient_cost: number
    packaging_cost: number
    labor_cost: number
    waste_cost: number
    tax_cost: number
    platform_fee_cost: number
    other_costs: number
    total_cost: number
    profit: number
    profit_margin: number
  }
  additional_costs: {
    packaging_cost: number
    packaging_description: string
    labor_cost: number
    labor_minutes: number
    waste_percentage: number
    tax_percentage: number
    platform_fee_percentage: number
    other_costs: number
    other_costs_description: string
    notes: string
  }
  ingredients: Array<{
    id: number
    name: string
    quantity: number
    unit_cost: number
    total_cost: number
    unit: string
  }>
  single_choice_variations: Array<{
    group_name: string
    option_name: string
    cost_delta: number
    total_cost: number
  }>
  available_packaging: Array<{
    id: number
    name: string
    unit: string
    cost_per_unit: number
    supplier: string
  }>
  product_packaging: Array<{
    supply_id: number
    name: string
    quantity: number
    unit: string
    cost_per_unit: number
  }>
  packaging_cost_from_links: number
  flash: { success: string | null }
  urls: {
    list: string
    submit: string
    update_packaging: string
    product_edit: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_PRODUCT_COST_EDIT__?: Payload
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

function formatBR(n: number, decimals = 2): string {
  return n.toFixed(decimals).replace('.', ',')
}

function marginBadgeClass(margin: number): string {
  if (margin >= 30) return 'bg-emerald-100 text-emerald-700 border-emerald-200'
  if (margin >= 15) return 'bg-amber-100 text-amber-800 border-amber-200'
  if (margin >= 0) return 'bg-orange-100 text-orange-700 border-orange-200'
  return 'bg-red-100 text-red-700 border-red-200'
}

export default function AdminStoreProductCostEditPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_PRODUCT_COST_EDIT__) || ({} as Payload)
  const urls = payload.urls
  const b = payload.breakdown ?? ({} as Payload['breakdown'])

  // Packaging links - editable state
  type LinkedSupply = { supply_id: number; quantity: string }
  const [linkedSupplies, setLinkedSupplies] = useState<LinkedSupply[]>(
    (payload.product_packaging ?? []).map((p) => ({
      supply_id: p.supply_id,
      quantity: formatBR(p.quantity, 2),
    })),
  )

  // Live computed packaging cost
  const computedPackagingCost = useMemo(() => {
    let total = 0
    for (const link of linkedSupplies) {
      const supply = payload.available_packaging?.find((s) => s.id === link.supply_id)
      if (!supply) continue
      const qty = Number.parseFloat(link.quantity.replace(',', '.')) || 0
      total += qty * supply.cost_per_unit
    }
    return total
  }, [linkedSupplies, payload.available_packaging])

  // Live computed total + profit
  const liveTotalCost =
    computedPackagingCost +
    (b.ingredient_cost ?? 0) +
    (b.labor_cost ?? 0) +
    (b.waste_cost ?? 0) +
    (b.tax_cost ?? 0) +
    (b.platform_fee_cost ?? 0) +
    (b.other_costs ?? 0)
  const liveProfit = (payload.product?.price ?? 0) - liveTotalCost
  const liveMargin = (payload.product?.price ?? 0) > 0 ? (liveProfit / payload.product.price) * 100 : 0

  const [savingPackaging, setSavingPackaging] = useState(false)
  const [supplyDraft, setSupplyDraft] = useState<number | ''>('')

  useEffect(() => {
    if (payload.flash?.success) showToast(payload.flash.success, 'success')
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  function addSupply() {
    if (!supplyDraft) {
      showToast('Selecione um insumo para adicionar.', 'error')
      return
    }
    if (linkedSupplies.some((l) => l.supply_id === supplyDraft)) {
      showToast('Este insumo já está vinculado.', 'error')
      return
    }
    setLinkedSupplies((prev) => [...prev, { supply_id: Number(supplyDraft), quantity: '1,00' }])
    setSupplyDraft('')
  }

  function removeSupply(supplyId: number) {
    setLinkedSupplies((prev) => prev.filter((l) => l.supply_id !== supplyId))
  }

  function updateQty(supplyId: number, qty: string) {
    setLinkedSupplies((prev) =>
      prev.map((l) => (l.supply_id === supplyId ? { ...l, quantity: moneyMask(qty) } : l)),
    )
  }

  async function savePackaging() {
    setSavingPackaging(true)
    try {
      const body = {
        packaging: linkedSupplies.map((l) => ({
          supply_id: l.supply_id,
          quantity: Number.parseFloat(l.quantity.replace(',', '.')) || 0,
        })),
      }
      const res = await fetch(urls.update_packaging, {
        method: 'POST',
        body: JSON.stringify(body),
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json; charset=utf-8',
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': getCsrfToken(),
        },
      })
      const data = (await res.json().catch(() => null)) as
        | { success?: boolean; message?: string; packaging_cost?: number }
        | null
      if (data?.success) {
        showToast(data.message || 'Embalagens salvas!', 'success')
      } else {
        showToast(data?.message || 'Falha ao salvar embalagens.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setSavingPackaging(false)
    }
  }

  function handleFullSubmit(e: FormEvent) {
    // hidden inputs are generated for each linked supply — the native form post handles everything
  }

  const availableForPicker = (payload.available_packaging ?? []).filter(
    (s) => !linkedSupplies.some((l) => l.supply_id === s.id),
  )

  return (
    <AdminStorePageShell section="financial">
      <AdminPageHeader
        title={`Custos · ${payload.product?.name || ''}`}
        description={`Preço de venda ${formatCurrency(payload.product?.price ?? 0)} · Configure embalagens e veja a margem em tempo real.`}
        icon={<Receipt className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <div className="flex flex-wrap items-center gap-2">
            <Button asChild variant="outline" size="sm" className="gap-1.5">
              <a href={urls.product_edit}>
                <Pencil className="h-3.5 w-3.5" />
                Editar produto
              </a>
            </Button>
            <Button asChild variant="outline" size="sm" className="gap-1.5">
              <a href={urls.list}>
                <ArrowLeft className="h-3.5 w-3.5" />
                Voltar
              </a>
            </Button>
          </div>
        }
      />

      {/* Live KPIs */}
      <section className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Preço de venda</p>
          <p className="mt-1 text-2xl font-semibold text-zinc-800">{formatCurrency(payload.product?.price ?? 0)}</p>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">CMV total (live)</p>
          <p className="mt-1 text-2xl font-semibold text-red-600">−{formatCurrency(liveTotalCost)}</p>
          <p className="mt-1 text-[11px] text-zinc-500">
            Embalagem: {formatCurrency(computedPackagingCost)}
          </p>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Lucro líquido</p>
          <p className={`mt-1 text-2xl font-semibold ${liveProfit >= 0 ? 'text-emerald-700' : 'text-red-600'}`}>
            {liveProfit >= 0 ? '' : '−'}
            {formatCurrency(Math.abs(liveProfit))}
          </p>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Margem (live)</p>
          <p
            className={`mt-1 text-2xl font-semibold ${liveMargin >= 30 ? 'text-emerald-700' : liveMargin >= 15 ? 'text-amber-600' : liveMargin >= 0 ? 'text-orange-600' : 'text-red-600'}`}
          >
            {liveMargin.toFixed(1)}%
          </p>
          <Badge className={`mt-1 border text-[10px] h-4 ${marginBadgeClass(liveMargin)}`}>
            {liveMargin >= 30 ? 'Saudável' : liveMargin >= 15 ? 'Atenção' : liveMargin >= 0 ? 'Baixa' : 'Prejuízo'}
          </Badge>
        </div>
      </section>

      <form action={urls.submit} method="POST" onSubmit={handleFullSubmit} className="space-y-5">
        <input type="hidden" name="csrf_token" value={getCsrfToken()} />
        {/* Hidden inputs from state for native form submit */}
        {linkedSupplies.map((l, idx) => (
          <div key={l.supply_id} className="hidden">
            <input type="hidden" name="packaging_supply_id[]" value={l.supply_id} />
            <input type="hidden" name="packaging_quantity[]" value={l.quantity} />
          </div>
        ))}

        {/* Ingredients (read-only) */}
        {(payload.ingredients ?? []).length > 0 && (
          <FormSection
            title="Ingredientes nativos do produto"
            description="Custo calculado automaticamente a partir dos ingredientes vinculados ao produto. Para alterar, vá em 'Editar produto'."
          >
            <ul className="divide-y divide-zinc-100">
              {(payload.ingredients ?? []).map((ing) => (
                <li key={ing.id} className="flex items-center gap-3 py-2">
                  <Utensils className="h-4 w-4 text-zinc-400 shrink-0" />
                  <div className="min-w-0 flex-1">
                    <p className="text-sm font-medium text-zinc-800 truncate">{ing.name}</p>
                    <p className="text-[11px] text-zinc-500">
                      {formatBR(ing.quantity, 3).replace(/,?0+$/, '')} {ing.unit} × {formatCurrency(ing.unit_cost)}
                    </p>
                  </div>
                  <span className="font-mono text-sm text-zinc-700 whitespace-nowrap">
                    {formatCurrency(ing.total_cost)}
                  </span>
                </li>
              ))}
              <li className="flex items-center justify-between py-2 pt-3 border-t-2 border-zinc-200">
                <span className="text-sm font-semibold text-zinc-700">Total ingredientes</span>
                <span className="font-mono text-base font-bold text-zinc-900">
                  {formatCurrency(b.ingredient_cost ?? 0)}
                </span>
              </li>
            </ul>
          </FormSection>
        )}

        {/* Single-choice variations */}
        {(payload.single_choice_variations ?? []).length > 0 && (
          <FormSection
            title="Variações de custo por escolha"
            description="Quando o cliente escolhe entre alternativas (ex.: Mussarela vs Cheddar), o custo varia. Mostrando o impacto de cada opção."
          >
            <ul className="space-y-1.5">
              {(payload.single_choice_variations ?? []).map((v, idx) => (
                <li
                  key={idx}
                  className="flex items-center justify-between gap-2 rounded-lg border border-zinc-200 bg-zinc-50/50 px-3 py-2"
                >
                  <div className="min-w-0 flex-1">
                    <p className="text-sm font-medium text-zinc-800 truncate">
                      <span className="text-zinc-500">{v.group_name}:</span> {v.option_name}
                    </p>
                  </div>
                  <span
                    className={`font-mono text-xs font-semibold ${v.cost_delta >= 0 ? 'text-red-600' : 'text-emerald-600'}`}
                  >
                    {v.cost_delta >= 0 ? '+' : '−'}
                    {formatCurrency(Math.abs(v.cost_delta))}
                  </span>
                </li>
              ))}
            </ul>
          </FormSection>
        )}

        {/* Packaging supplies */}
        <FormSection
          title="Embalagens vinculadas"
          description="Adicione as embalagens que este produto usa. O custo é calculado automaticamente."
        >
          {linkedSupplies.length > 0 && (
            <ul className="space-y-2">
              {linkedSupplies.map((link) => {
                const supply = payload.available_packaging?.find((s) => s.id === link.supply_id)
                const qty = Number.parseFloat(link.quantity.replace(',', '.')) || 0
                const cost = supply ? qty * supply.cost_per_unit : 0
                return (
                  <li
                    key={link.supply_id}
                    className="grid grid-cols-12 items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50/40 p-2.5"
                  >
                    <div className="col-span-12 sm:col-span-5 flex items-center gap-2 min-w-0">
                      <Box className="h-4 w-4 text-zinc-400 shrink-0" />
                      <div className="min-w-0 flex-1">
                        <p className="text-sm font-medium text-zinc-800 truncate">{supply?.name || 'Insumo'}</p>
                        <p className="text-[11px] text-zinc-500">
                          {supply ? `${formatCurrency(supply.cost_per_unit)} / ${supply.unit}` : '—'}
                        </p>
                      </div>
                    </div>
                    <div className="col-span-7 sm:col-span-3 flex items-center gap-1.5">
                      <Input
                        value={link.quantity}
                        onChange={(e) => updateQty(link.supply_id, e.target.value)}
                        inputMode="decimal"
                        className="h-9 text-center font-mono text-sm"
                      />
                      <span className="text-[11px] text-zinc-500 whitespace-nowrap">{supply?.unit || 'un'}</span>
                    </div>
                    <div className="col-span-3 sm:col-span-3 text-right">
                      <p className="text-[10px] text-zinc-500">Custo</p>
                      <p className="font-mono text-sm font-semibold text-zinc-700">{formatCurrency(cost)}</p>
                    </div>
                    <div className="col-span-2 sm:col-span-1 flex justify-end">
                      <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        className="h-8 w-8 p-0 text-red-500 hover:bg-red-50 hover:text-red-700"
                        onClick={() => removeSupply(link.supply_id)}
                        aria-label="Remover"
                      >
                        <X className="h-3.5 w-3.5" />
                      </Button>
                    </div>
                  </li>
                )
              })}

              <li className="flex items-center justify-between rounded-lg border-2 border-zinc-200 bg-white px-3 py-2 mt-2">
                <span className="text-sm font-semibold text-zinc-700">Custo total de embalagens</span>
                <span className="font-mono text-base font-bold text-zinc-900">
                  {formatCurrency(computedPackagingCost)}
                </span>
              </li>
            </ul>
          )}

          {availableForPicker.length > 0 && (
            <div className="flex flex-wrap items-end gap-2 pt-2 border-t border-zinc-100">
              <FormField label="Adicionar insumo" htmlFor="pkg-pick" className="flex-1 min-w-[200px]">
                <select
                  id="pkg-pick"
                  value={supplyDraft}
                  onChange={(e) => setSupplyDraft(e.target.value ? Number(e.target.value) : '')}
                  className="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
                >
                  <option value="">Selecione um insumo…</option>
                  {availableForPicker.map((p) => (
                    <option key={p.id} value={p.id}>
                      {p.name} ({formatCurrency(p.cost_per_unit)}/{p.unit})
                    </option>
                  ))}
                </select>
              </FormField>
              <Button type="button" variant="outline" onClick={addSupply} className="gap-1.5">
                <Plus className="h-3.5 w-3.5" />
                Adicionar
              </Button>
            </div>
          )}

          {linkedSupplies.length === 0 && availableForPicker.length === 0 && (
            <p className="text-sm text-zinc-500 text-center py-6">
              Nenhum insumo cadastrado.{' '}
              <a
                href={urls.list.replace('/product-costs', '/packaging')}
                className="text-blue-600 underline"
              >
                Cadastre insumos primeiro
              </a>
            </p>
          )}

          {linkedSupplies.length > 0 && (
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={savePackaging}
              disabled={savingPackaging}
              className="gap-2 w-fit"
            >
              <Save className="h-3.5 w-3.5" />
              {savingPackaging ? 'Salvando...' : 'Salvar embalagens (autosave)'}
            </Button>
          )}
        </FormSection>

        {/* Cost breakdown (read-only summary) */}
        <FormSection
          title="Composição do custo"
          description="Detalhamento de todos os componentes que somam para o CMV total deste produto."
        >
          <ul className="space-y-1.5 text-sm">
            <li className="flex items-center justify-between py-1 border-b border-zinc-100">
              <span className="text-zinc-600 flex items-center gap-2">
                <Utensils className="h-3.5 w-3.5 text-zinc-400" />
                Ingredientes
              </span>
              <span className="font-mono text-zinc-800">{formatCurrency(b.ingredient_cost ?? 0)}</span>
            </li>
            <li className="flex items-center justify-between py-1 border-b border-zinc-100">
              <span className="text-zinc-600 flex items-center gap-2">
                <Package className="h-3.5 w-3.5 text-zinc-400" />
                Embalagens (live)
              </span>
              <span className="font-mono text-zinc-800">{formatCurrency(computedPackagingCost)}</span>
            </li>
            {(b.labor_cost ?? 0) > 0 && (
              <li className="flex items-center justify-between py-1 border-b border-zinc-100">
                <span className="text-zinc-600">Mão-de-obra</span>
                <span className="font-mono text-zinc-800">{formatCurrency(b.labor_cost)}</span>
              </li>
            )}
            {(b.waste_cost ?? 0) > 0 && (
              <li className="flex items-center justify-between py-1 border-b border-zinc-100">
                <span className="text-zinc-600">Desperdício</span>
                <span className="font-mono text-zinc-800">{formatCurrency(b.waste_cost)}</span>
              </li>
            )}
            {(b.tax_cost ?? 0) > 0 && (
              <li className="flex items-center justify-between py-1 border-b border-zinc-100">
                <span className="text-zinc-600">Impostos</span>
                <span className="font-mono text-zinc-800">{formatCurrency(b.tax_cost)}</span>
              </li>
            )}
            {(b.platform_fee_cost ?? 0) > 0 && (
              <li className="flex items-center justify-between py-1 border-b border-zinc-100">
                <span className="text-zinc-600">Taxa de plataforma</span>
                <span className="font-mono text-zinc-800">{formatCurrency(b.platform_fee_cost)}</span>
              </li>
            )}
            {(b.other_costs ?? 0) > 0 && (
              <li className="flex items-center justify-between py-1 border-b border-zinc-100">
                <span className="text-zinc-600">Outros</span>
                <span className="font-mono text-zinc-800">{formatCurrency(b.other_costs)}</span>
              </li>
            )}
            <li className="flex items-center justify-between py-2 mt-2 border-t-2 border-zinc-300">
              <span className="font-semibold text-zinc-800">CMV total</span>
              <span className="font-mono text-base font-bold text-red-600">−{formatCurrency(liveTotalCost)}</span>
            </li>
            <li className="flex items-center justify-between">
              <span className="font-semibold text-zinc-800">Lucro</span>
              <span
                className={`font-mono text-base font-bold ${liveProfit >= 0 ? 'text-emerald-700' : 'text-red-600'}`}
              >
                {liveProfit >= 0 ? '' : '−'}
                {formatCurrency(Math.abs(liveProfit))}
              </span>
            </li>
          </ul>
        </FormSection>

        <div className="sticky bottom-0 -mx-5 mt-6 border-t border-zinc-200 bg-white/95 backdrop-blur px-5 py-3">
          <div className="mx-auto flex max-w-[1292px] flex-wrap items-center justify-between gap-2">
            <Button asChild type="button" variant="outline">
              <a href={urls.list}>Cancelar</a>
            </Button>
            <Button type="submit" className="gap-2">
              <Save className="h-4 w-4" />
              Salvar e atualizar snapshot
            </Button>
          </div>
        </div>
      </form>
    </AdminStorePageShell>
  )
}
