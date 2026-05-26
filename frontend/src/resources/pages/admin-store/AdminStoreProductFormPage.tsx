import { useEffect, useMemo, useRef, useState, type FormEvent } from 'react'
import {
  ArrowLeft,
  ArrowUp,
  ArrowDown,
  ImageOff,
  Layers,
  Package,
  Plus,
  Save,
  Sparkles,
  Trash2,
  Utensils,
  Wand2,
  X,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  AdminStorePageShell,
  AdminPageHeader,
  ConfirmDialog,
  FormField,
  FormSection,
  showToast,
  getCsrfToken,
  formatCurrency,
  useStoreContext,
} from '@/components/admin-store'

/* ============================================================
   Types
   ============================================================ */
type ProductFormPayload = {
  is_edit: boolean
  product: {
    id: number | null
    name: string
    description: string
    sku: string
    price: number
    promo_price: number | null
    promo_start_at: string | null
    promo_end_at: string | null
    category_id: number | null
    image: string
    type: 'simple' | 'combo'
    price_mode: 'fixed' | 'sum'
    sort_order: number
    active: boolean
    allow_customize: boolean
  }
  categories: Array<{ id: number; name: string }>
  ingredients: Array<{
    id: number
    name: string
    internal_name: string
    cost: number
    sale_price: number
    unit: string
    image_path: string
  }>
  simple_products: Array<{
    id: number
    name: string
    price: number
    allow_customize: boolean
    category_id: number | null
  }>
  customization: {
    enabled: boolean
    groups: CustomizationGroup[]
  }
  combo_groups: ComboGroup[]
  use_groups: boolean
  customization_templates: Array<{
    id: number
    name: string
    mode: 'choice' | 'pool'
    min: number
    max: number
    items: Array<{
      ingredient_id: number
      ingredient_name: string
      min_qty: number
      max_qty: number
      default: boolean
      default_qty: number
    }>
  }>
  flash: { error: string | null }
  urls: { list: string; submit: string; destroy: string | null }
}

type CustomizationGroup = {
  name: string
  sort_order: number
  mode: 'choice' | 'pool'
  min: number
  max: number
  items: CustomizationItem[]
}
type CustomizationItem = {
  ingredient_id: number
  sort_order: number
  min_qty: number
  max_qty: number
  default: boolean
  default_qty: number
}

type ComboGroup = {
  name: string
  sort_order: number
  min: number
  max: number
  items: ComboItem[]
}
type ComboItem = {
  product_id: number
  sort_order: number
  customizable: boolean
  price_override: number | null
  default_qty: number
  default: boolean
}

declare global {
  interface Window {
    __ADMIN_STORE_PRODUCT_FORM__?: ProductFormPayload
  }
}

/* ============================================================
   Helpers
   ============================================================ */
function moneyMask(raw: string): string {
  const digits = raw.replace(/[^\d,]/g, '')
  if (!digits) return ''
  const parts = digits.split(',')
  const intPart = parts[0].replace(/^0+(?=\d)/, '') || '0'
  if (parts.length === 1) return intPart
  return `${intPart},${parts.slice(1).join('').slice(0, 2)}`
}

function parseMoney(raw: string | number | null | undefined): number | null {
  if (raw === null || raw === undefined || raw === '') return null
  if (typeof raw === 'number') return Number.isFinite(raw) ? raw : null
  const cleaned = raw.replace(/\./g, '').replace(',', '.')
  const n = Number.parseFloat(cleaned)
  return Number.isFinite(n) ? n : null
}

function formatBR(n: number | null | undefined, decimals = 2): string {
  if (n === null || n === undefined || !Number.isFinite(n)) return ''
  return n.toFixed(decimals).replace('.', ',')
}

function resolveImage(path: string): string {
  if (!path) return ''
  if (/^(https?:)?\/\//i.test(path) || path.startsWith('data:')) return path
  return path.startsWith('/') ? path : `/${path}`
}

function toDatetimeLocal(iso: string | null | undefined): string {
  if (!iso) return ''
  // accept "YYYY-MM-DD HH:MM:SS" or ISO and produce "YYYY-MM-DDTHH:MM"
  const s = String(iso).replace(' ', 'T').slice(0, 16)
  return /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(s) ? s : ''
}

/* ============================================================
   Customization group builder (for type=simple)
   ============================================================ */
function CustomizationGroupCard({
  group,
  groupIndex,
  ingredients,
  templates,
  onChange,
  onRemove,
  onMove,
}: {
  group: CustomizationGroup
  groupIndex: number
  ingredients: ProductFormPayload['ingredients']
  templates: ProductFormPayload['customization_templates']
  onChange: (next: CustomizationGroup) => void
  onRemove: () => void
  onMove: (dir: -1 | 1) => void
}) {
  const [showTpl, setShowTpl] = useState(false)

  function update<K extends keyof CustomizationGroup>(key: K, value: CustomizationGroup[K]) {
    onChange({ ...group, [key]: value })
  }

  function updateItem(itemIndex: number, patch: Partial<CustomizationItem>) {
    const items = [...group.items]
    items[itemIndex] = { ...items[itemIndex], ...patch }
    onChange({ ...group, items })
  }

  function addItem() {
    const next: CustomizationItem = {
      ingredient_id: 0,
      sort_order: group.items.length,
      min_qty: 0,
      max_qty: 1,
      default: false,
      default_qty: 0,
    }
    onChange({ ...group, items: [...group.items, next] })
  }

  function removeItem(idx: number) {
    onChange({ ...group, items: group.items.filter((_, i) => i !== idx) })
  }

  function moveItem(idx: number, dir: -1 | 1) {
    const j = idx + dir
    if (j < 0 || j >= group.items.length) return
    const items = [...group.items]
    ;[items[idx], items[j]] = [items[j], items[idx]]
    onChange({ ...group, items })
  }

  function applyTemplate(tplId: number) {
    const tpl = templates.find((t) => t.id === tplId)
    if (!tpl) return
    onChange({
      ...group,
      name: group.name || tpl.name,
      mode: tpl.mode,
      min: tpl.min,
      max: tpl.max,
      items: tpl.items.map((it, i) => ({
        ingredient_id: it.ingredient_id,
        sort_order: i,
        min_qty: it.min_qty,
        max_qty: it.max_qty,
        default: it.default,
        default_qty: it.default_qty,
      })),
    })
    setShowTpl(false)
    showToast(`Template "${tpl.name}" aplicado.`, 'success')
  }

  return (
    <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
      <div className="mb-3 flex items-center gap-2">
        <div className="flex flex-col gap-0.5 mr-1">
          <button
            type="button"
            onClick={() => onMove(-1)}
            className="p-0.5 text-zinc-400 hover:text-zinc-700"
            aria-label="Mover para cima"
          >
            <ArrowUp className="h-3 w-3" />
          </button>
          <button
            type="button"
            onClick={() => onMove(1)}
            className="p-0.5 text-zinc-400 hover:text-zinc-700"
            aria-label="Mover para baixo"
          >
            <ArrowDown className="h-3 w-3" />
          </button>
        </div>

        <Input
          value={group.name}
          onChange={(e) => update('name', e.target.value)}
          placeholder="Nome do grupo (ex.: Escolha o pão)"
          className="flex-1"
          maxLength={120}
        />

        <select
          value={group.mode}
          onChange={(e) => update('mode', e.target.value as 'choice' | 'pool')}
          className="flex h-9 rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
        >
          <option value="choice">Escolha (1 por item)</option>
          <option value="pool">Pool (quantidade)</option>
        </select>

        <div className="relative">
          <Button
            type="button"
            variant="outline"
            size="sm"
            className="h-9 gap-1.5"
            onClick={() => setShowTpl((s) => !s)}
            disabled={templates.length === 0}
          >
            <Wand2 className="h-3.5 w-3.5" />
            Template
          </Button>
          {showTpl && templates.length > 0 && (
            <div className="absolute right-0 top-10 z-10 w-72 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg">
              <p className="border-b border-zinc-100 bg-zinc-50 px-3 py-1.5 text-xs font-medium text-zinc-700">
                Aplicar template
              </p>
              <ul className="max-h-64 overflow-y-auto">
                {templates.map((tpl) => (
                  <li key={tpl.id}>
                    <button
                      type="button"
                      onClick={() => applyTemplate(tpl.id)}
                      className="block w-full px-3 py-2 text-left text-sm hover:bg-zinc-50"
                    >
                      <p className="font-medium text-zinc-800">{tpl.name}</p>
                      <p className="text-[11px] text-zinc-500">
                        {tpl.mode === 'choice' ? 'Escolha' : 'Pool'} · {tpl.items.length} itens
                      </p>
                    </button>
                  </li>
                ))}
              </ul>
            </div>
          )}
        </div>

        <Button
          type="button"
          variant="ghost"
          size="sm"
          className="h-9 px-2 text-red-600 hover:text-red-700 hover:bg-red-50"
          onClick={onRemove}
          aria-label="Remover grupo"
        >
          <Trash2 className="h-3.5 w-3.5" />
        </Button>
      </div>

      <div className="mb-3 grid gap-2 sm:grid-cols-2">
        <FormField label={`Mínimo ${group.mode === 'choice' ? 'de escolhas' : 'do pool'}`}>
          <Input
            type="number"
            min={0}
            value={group.min}
            onChange={(e) => update('min', Math.max(0, Number(e.target.value) || 0))}
            className="w-full"
          />
        </FormField>
        <FormField label={`Máximo ${group.mode === 'choice' ? 'de escolhas' : 'do pool'}`}>
          <Input
            type="number"
            min={1}
            value={group.max}
            onChange={(e) => update('max', Math.max(1, Number(e.target.value) || 1))}
            className="w-full"
          />
        </FormField>
      </div>

      <div className="space-y-2">
        {group.items.map((it, ii) => {
          const ing = ingredients.find((i) => i.id === it.ingredient_id)
          return (
            <div key={ii} className="grid grid-cols-12 items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50/50 p-2">
              <div className="col-span-12 sm:col-span-5">
                <select
                  value={it.ingredient_id}
                  onChange={(e) => updateItem(ii, { ingredient_id: Number(e.target.value) })}
                  className="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
                >
                  <option value={0}>Selecione ingrediente…</option>
                  {ingredients.map((ing) => (
                    <option key={ing.id} value={ing.id}>
                      {ing.name}
                      {ing.internal_name ? ` (${ing.internal_name})` : ''}
                    </option>
                  ))}
                </select>
                {ing && (
                  <p className="mt-1 text-[10px] text-zinc-500">
                    Custo {formatCurrency(ing.cost)} · Venda {formatCurrency(ing.sale_price)}
                  </p>
                )}
              </div>
              <div className="col-span-3 sm:col-span-2">
                <Input
                  type="number"
                  min={0}
                  value={it.min_qty}
                  onChange={(e) => updateItem(ii, { min_qty: Math.max(0, Number(e.target.value) || 0) })}
                  placeholder="Min"
                  className="h-9 text-center"
                />
              </div>
              <div className="col-span-3 sm:col-span-2">
                <Input
                  type="number"
                  min={0}
                  value={it.max_qty}
                  onChange={(e) => updateItem(ii, { max_qty: Math.max(0, Number(e.target.value) || 0) })}
                  placeholder="Max"
                  className="h-9 text-center"
                />
              </div>
              <div className="col-span-3 sm:col-span-1 flex items-center justify-center">
                <label className="flex items-center gap-1 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={it.default}
                    onChange={(e) => updateItem(ii, { default: e.target.checked })}
                    className="h-3.5 w-3.5"
                  />
                  <span className="text-[11px] text-zinc-600">Padrão</span>
                </label>
              </div>
              <div className="col-span-3 sm:col-span-1">
                <Input
                  type="number"
                  min={0}
                  value={it.default_qty}
                  disabled={!it.default}
                  onChange={(e) => updateItem(ii, { default_qty: Math.max(0, Number(e.target.value) || 0) })}
                  placeholder="Qtd."
                  className="h-9 text-center text-xs"
                />
              </div>
              <div className="col-span-12 sm:col-span-1 flex justify-end gap-0.5">
                <button
                  type="button"
                  onClick={() => moveItem(ii, -1)}
                  className="p-1 text-zinc-400 hover:text-zinc-700"
                  aria-label="Mover item para cima"
                >
                  <ArrowUp className="h-3 w-3" />
                </button>
                <button
                  type="button"
                  onClick={() => moveItem(ii, 1)}
                  className="p-1 text-zinc-400 hover:text-zinc-700"
                  aria-label="Mover item para baixo"
                >
                  <ArrowDown className="h-3 w-3" />
                </button>
                <button
                  type="button"
                  onClick={() => removeItem(ii)}
                  className="p-1 text-red-500 hover:bg-red-50 rounded"
                  aria-label="Remover item"
                >
                  <X className="h-3 w-3" />
                </button>
              </div>
            </div>
          )
        })}

        <Button type="button" variant="outline" size="sm" className="w-full gap-1.5" onClick={addItem}>
          <Plus className="h-3.5 w-3.5" />
          Adicionar ingrediente
        </Button>
      </div>
    </div>
  )
}

/* ============================================================
   Combo group builder (for type=combo)
   ============================================================ */
function ComboGroupCard({
  group,
  groupIndex,
  simpleProducts,
  onChange,
  onRemove,
  onMove,
}: {
  group: ComboGroup
  groupIndex: number
  simpleProducts: ProductFormPayload['simple_products']
  onChange: (next: ComboGroup) => void
  onRemove: () => void
  onMove: (dir: -1 | 1) => void
}) {
  function update<K extends keyof ComboGroup>(key: K, value: ComboGroup[K]) {
    onChange({ ...group, [key]: value })
  }

  function updateItem(itemIndex: number, patch: Partial<ComboItem>) {
    const items = [...group.items]
    items[itemIndex] = { ...items[itemIndex], ...patch }
    onChange({ ...group, items })
  }

  function addItem() {
    const next: ComboItem = {
      product_id: 0,
      sort_order: group.items.length,
      customizable: false,
      price_override: null,
      default_qty: 0,
      default: false,
    }
    onChange({ ...group, items: [...group.items, next] })
  }

  function removeItem(idx: number) {
    onChange({ ...group, items: group.items.filter((_, i) => i !== idx) })
  }

  return (
    <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
      <div className="mb-3 flex items-center gap-2">
        <div className="flex flex-col gap-0.5 mr-1">
          <button type="button" onClick={() => onMove(-1)} className="p-0.5 text-zinc-400 hover:text-zinc-700">
            <ArrowUp className="h-3 w-3" />
          </button>
          <button type="button" onClick={() => onMove(1)} className="p-0.5 text-zinc-400 hover:text-zinc-700">
            <ArrowDown className="h-3 w-3" />
          </button>
        </div>

        <Input
          value={group.name}
          onChange={(e) => update('name', e.target.value)}
          placeholder="Nome do grupo (ex.: Escolha o lanche)"
          className="flex-1"
          maxLength={120}
        />

        <Button
          type="button"
          variant="ghost"
          size="sm"
          className="h-9 px-2 text-red-600 hover:text-red-700 hover:bg-red-50"
          onClick={onRemove}
          aria-label="Remover grupo"
        >
          <Trash2 className="h-3.5 w-3.5" />
        </Button>
      </div>

      <div className="mb-3 grid gap-2 sm:grid-cols-2">
        <FormField label="Mínimo de itens">
          <Input
            type="number"
            min={0}
            value={group.min}
            onChange={(e) => update('min', Math.max(0, Number(e.target.value) || 0))}
          />
        </FormField>
        <FormField label="Máximo de itens">
          <Input
            type="number"
            min={1}
            value={group.max}
            onChange={(e) => update('max', Math.max(1, Number(e.target.value) || 1))}
          />
        </FormField>
      </div>

      <div className="space-y-2">
        {group.items.map((it, ii) => {
          const prod = simpleProducts.find((p) => p.id === it.product_id)
          return (
            <div key={ii} className="grid grid-cols-12 items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50/50 p-2">
              <div className="col-span-12 sm:col-span-5">
                <select
                  value={it.product_id}
                  onChange={(e) => updateItem(ii, { product_id: Number(e.target.value) })}
                  className="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
                >
                  <option value={0}>Selecione produto…</option>
                  {simpleProducts.map((p) => (
                    <option key={p.id} value={p.id}>
                      {p.name} ({formatCurrency(p.price)})
                    </option>
                  ))}
                </select>
                {prod?.allow_customize && (
                  <p className="mt-0.5 text-[10px] text-amber-600">
                    Esse produto tem customização — habilite "Customizável" para deixar o cliente personalizar no combo.
                  </p>
                )}
              </div>
              <div className="col-span-6 sm:col-span-2">
                <Input
                  value={it.price_override !== null ? formatBR(it.price_override) : ''}
                  onChange={(e) =>
                    updateItem(ii, { price_override: parseMoney(moneyMask(e.target.value)) })
                  }
                  inputMode="decimal"
                  placeholder={prod ? `${formatBR(prod.price)} (padrão)` : 'Preço'}
                  className="h-9 text-right"
                />
              </div>
              <div className="col-span-3 sm:col-span-2">
                <Input
                  type="number"
                  min={0}
                  value={it.default_qty}
                  onChange={(e) => updateItem(ii, { default_qty: Math.max(0, Number(e.target.value) || 0) })}
                  placeholder="Qtd."
                  className="h-9 text-center"
                />
              </div>
              <div className="col-span-3 sm:col-span-2 flex items-center gap-2">
                <label className="flex items-center gap-1 cursor-pointer text-[11px]">
                  <input
                    type="checkbox"
                    checked={it.customizable}
                    onChange={(e) => updateItem(ii, { customizable: e.target.checked })}
                  />
                  <span className="text-zinc-600">Custom.</span>
                </label>
                <label className="flex items-center gap-1 cursor-pointer text-[11px]">
                  <input
                    type="checkbox"
                    checked={it.default}
                    onChange={(e) => updateItem(ii, { default: e.target.checked })}
                  />
                  <span className="text-zinc-600">Padrão</span>
                </label>
              </div>
              <div className="col-span-12 sm:col-span-1 flex justify-end">
                <button
                  type="button"
                  onClick={() => removeItem(ii)}
                  className="p-1 text-red-500 hover:bg-red-50 rounded"
                  aria-label="Remover"
                >
                  <X className="h-3 w-3" />
                </button>
              </div>
            </div>
          )
        })}

        <Button type="button" variant="outline" size="sm" className="w-full gap-1.5" onClick={addItem}>
          <Plus className="h-3.5 w-3.5" />
          Adicionar produto
        </Button>
      </div>
    </div>
  )
}

/* ============================================================
   Main page
   ============================================================ */
export default function AdminStoreProductFormPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_PRODUCT_FORM__) || ({} as ProductFormPayload)
  const { urls, product, ingredients = [], simple_products = [], categories = [], customization_templates = [] } = payload
  const isEdit = !!payload.is_edit

  // Basic fields
  const [name, setName] = useState(product?.name ?? '')
  const [sku, setSku] = useState(product?.sku ?? '')
  const [description, setDescription] = useState(product?.description ?? '')
  const [categoryId, setCategoryId] = useState<number | ''>(product?.category_id ?? '')
  const [type, setType] = useState<'simple' | 'combo'>(product?.type ?? 'simple')
  const [priceMode, setPriceMode] = useState<'fixed' | 'sum'>(product?.price_mode ?? 'fixed')
  const [price, setPrice] = useState(formatBR(product?.price ?? 0))
  const [promoPrice, setPromoPrice] = useState(
    product?.promo_price !== null && product?.promo_price !== undefined ? formatBR(product.promo_price) : '',
  )
  const [promoPercentage, setPromoPercentage] = useState('')
  const [promoStart, setPromoStart] = useState(toDatetimeLocal(product?.promo_start_at))
  const [promoEnd, setPromoEnd] = useState(toDatetimeLocal(product?.promo_end_at))
  const [sortOrder, setSortOrder] = useState<number>(product?.sort_order ?? 0)
  const [active, setActive] = useState<boolean>(product?.active ?? true)

  // Image
  const [imageFile, setImageFile] = useState<File | null>(null)
  const [imagePreview, setImagePreview] = useState(resolveImage(product?.image ?? ''))
  const fileRef = useRef<HTMLInputElement>(null)

  // Customization (simple)
  const [customizationEnabled, setCustomizationEnabled] = useState<boolean>(!!payload.customization?.enabled)
  const [customizationGroups, setCustomizationGroups] = useState<CustomizationGroup[]>(
    payload.customization?.groups ?? [],
  )

  // Combo
  const [useGroups, setUseGroups] = useState<boolean>(!!payload.use_groups)
  const [comboGroups, setComboGroups] = useState<ComboGroup[]>(payload.combo_groups ?? [])

  const [errors, setErrors] = useState<Record<string, string>>({})
  const [confirmDelete, setConfirmDelete] = useState(false)

  useEffect(() => {
    if (payload.flash?.error) showToast(payload.flash.error, 'error')
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  function handleImageChange(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0]
    if (!file) return
    setImageFile(file)
    const reader = new FileReader()
    reader.onload = () => setImagePreview(String(reader.result || ''))
    reader.readAsDataURL(file)
  }

  function addCustGroup() {
    setCustomizationGroups((prev) => [
      ...prev,
      {
        name: '',
        sort_order: prev.length,
        mode: 'choice',
        min: 0,
        max: 1,
        items: [],
      },
    ])
  }

  function addComboGroup() {
    setComboGroups((prev) => [
      ...prev,
      { name: '', sort_order: prev.length, min: 1, max: 1, items: [] },
    ])
  }

  function moveGroup(arr: 'cust' | 'combo', idx: number, dir: -1 | 1) {
    if (arr === 'cust') {
      setCustomizationGroups((prev) => {
        const j = idx + dir
        if (j < 0 || j >= prev.length) return prev
        const copy = [...prev]
        ;[copy[idx], copy[j]] = [copy[j], copy[idx]]
        return copy
      })
    } else {
      setComboGroups((prev) => {
        const j = idx + dir
        if (j < 0 || j >= prev.length) return prev
        const copy = [...prev]
        ;[copy[idx], copy[j]] = [copy[j], copy[idx]]
        return copy
      })
    }
  }

  function validate(): boolean {
    const next: Record<string, string> = {}
    if (!name.trim()) next.name = 'Informe o nome do produto.'
    const priceNum = parseMoney(price)
    if (type === 'simple' || priceMode === 'fixed') {
      if (priceNum === null || priceNum <= 0) next.price = 'Informe um preço maior que zero.'
    }
    if (customizationEnabled && type === 'simple') {
      if (customizationGroups.length === 0) {
        next.customization = 'Adicione pelo menos um grupo de personalização ou desative a opção.'
      } else {
        for (const g of customizationGroups) {
          if (!g.name.trim()) {
            next.customization = 'Todos os grupos de personalização precisam de um nome.'
            break
          }
          if (g.items.length === 0) {
            next.customization = `O grupo "${g.name}" precisa de pelo menos um ingrediente.`
            break
          }
          if (g.items.some((it) => it.ingredient_id === 0)) {
            next.customization = `Selecione o ingrediente em todos os itens do grupo "${g.name}".`
            break
          }
        }
      }
    }
    if (type === 'combo' && useGroups) {
      if (comboGroups.length === 0) {
        next.combo = 'Adicione pelo menos um grupo no combo ou desative a opção.'
      } else {
        for (const g of comboGroups) {
          if (!g.name.trim()) {
            next.combo = 'Todos os grupos de combo precisam de um nome.'
            break
          }
          if (g.items.length === 0) {
            next.combo = `O grupo "${g.name}" precisa de pelo menos um produto.`
            break
          }
          if (g.items.some((it) => it.product_id === 0)) {
            next.combo = `Selecione o produto em todos os itens do grupo "${g.name}".`
            break
          }
        }
      }
    }
    setErrors(next)
    return Object.keys(next).length === 0
  }

  function handleSubmit(e: FormEvent) {
    if (!validate()) {
      e.preventDefault()
      const firstError = Object.values(errors)[0]
      if (firstError) showToast(firstError, 'error')
      else showToast('Corrija os campos em vermelho.', 'error')
    }
  }

  async function handleDelete() {
    if (!urls.destroy) return
    const formData = new FormData()
    const csrf = getCsrfToken()
    if (csrf) formData.append('csrf_token', csrf)
    try {
      const res = await fetch(urls.destroy, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        redirect: 'manual',
      })
      if (res.type === 'opaqueredirect' || res.ok || res.status === 302 || res.status === 0) {
        window.location.href = urls.list
      } else {
        showToast('Falha ao remover produto.', 'error')
      }
    } catch {
      window.location.href = urls.list
    }
  }

  const showCombo = type === 'combo'
  const showCustomization = type === 'simple'

  return (
    <AdminStorePageShell section="catalog">
      <AdminPageHeader
        title={isEdit ? `Editar produto · ${product?.name || ''}` : 'Novo produto'}
        description={isEdit ? 'Atualize os dados, personalização e combos.' : 'Cadastre um produto simples ou um combo.'}
        icon={<Package className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <Button asChild variant="outline">
            <a href={urls.list} className="gap-2">
              <ArrowLeft className="h-4 w-4" />
              Voltar
            </a>
          </Button>
        }
      />

      <form
        action={urls.submit}
        method="POST"
        encType="multipart/form-data"
        onSubmit={handleSubmit}
        className="space-y-5"
      >
        <input type="hidden" name="csrf_token" value={getCsrfToken()} />

        {/* ── Hidden fields generated from state (matching PHP names) ── */}
        <input type="hidden" name="use_groups" value={showCombo && useGroups ? '1' : '0'} />
        <input type="hidden" name="customization[enabled]" value={showCustomization && customizationEnabled ? '1' : '0'} />
        {showCustomization &&
          customizationEnabled &&
          customizationGroups.map((g, gi) => (
            <div key={`cust-${gi}`} className="hidden">
              <input type="hidden" name={`customization[groups][${gi}][name]`} value={g.name} />
              <input type="hidden" name={`customization[groups][${gi}][sort_order]`} value={gi} />
              <input type="hidden" name={`customization[groups][${gi}][mode]`} value={g.mode} />
              <input type="hidden" name={`customization[groups][${gi}][${g.mode}][min]`} value={g.min} />
              <input type="hidden" name={`customization[groups][${gi}][${g.mode}][max]`} value={g.max} />
              {g.items.map((it, ii) => (
                <div key={`cust-${gi}-${ii}`}>
                  <input type="hidden" name={`customization[groups][${gi}][items][${ii}][ingredient_id]`} value={it.ingredient_id} />
                  <input type="hidden" name={`customization[groups][${gi}][items][${ii}][sort_order]`} value={ii} />
                  <input type="hidden" name={`customization[groups][${gi}][items][${ii}][min_qty]`} value={it.min_qty} />
                  <input type="hidden" name={`customization[groups][${gi}][items][${ii}][max_qty]`} value={it.max_qty} />
                  <input type="hidden" name={`customization[groups][${gi}][items][${ii}][default]`} value={it.default ? '1' : '0'} />
                  <input type="hidden" name={`customization[groups][${gi}][items][${ii}][default_qty]`} value={it.default_qty} />
                </div>
              ))}
            </div>
          ))}
        {showCombo &&
          useGroups &&
          comboGroups.map((g, gi) => (
            <div key={`combo-${gi}`} className="hidden">
              <input type="hidden" name={`groups[${gi}][name]`} value={g.name} />
              <input type="hidden" name={`groups[${gi}][sort_order]`} value={gi} />
              <input type="hidden" name={`groups[${gi}][min]`} value={g.min} />
              <input type="hidden" name={`groups[${gi}][max]`} value={g.max} />
              {g.items.map((it, ii) => (
                <div key={`combo-${gi}-${ii}`}>
                  <input type="hidden" name={`groups[${gi}][items][${ii}][product_id]`} value={it.product_id} />
                  <input type="hidden" name={`groups[${gi}][items][${ii}][sort_order]`} value={ii} />
                  <input type="hidden" name={`groups[${gi}][items][${ii}][customizable]`} value={it.customizable ? '1' : '0'} />
                  <input type="hidden" name={`groups[${gi}][items][${ii}][price_override]`} value={it.price_override ?? ''} />
                  <input type="hidden" name={`groups[${gi}][items][${ii}][default_qty]`} value={it.default_qty} />
                  <input type="hidden" name={`groups[${gi}][items][${ii}][default]`} value={it.default ? '1' : '0'} />
                </div>
              ))}
            </div>
          ))}

        <Tabs defaultValue="basic">
          <TabsList className="h-auto flex-wrap p-1 bg-white border border-zinc-200 rounded-xl">
            <TabsTrigger value="basic" className="gap-1.5 px-3 py-1.5 text-xs sm:text-sm">
              <Package className="h-3.5 w-3.5" />
              Básico
            </TabsTrigger>
            {showCustomization && (
              <TabsTrigger value="customization" className="gap-1.5 px-3 py-1.5 text-xs sm:text-sm">
                <Sparkles className="h-3.5 w-3.5" />
                Personalização
                {customizationEnabled && customizationGroups.length > 0 && (
                  <Badge className="ml-1 h-4 bg-amber-100 text-amber-700 border border-amber-200 hover:bg-amber-100 text-[10px]">
                    {customizationGroups.length}
                  </Badge>
                )}
              </TabsTrigger>
            )}
            {showCombo && (
              <TabsTrigger value="combo" className="gap-1.5 px-3 py-1.5 text-xs sm:text-sm">
                <Layers className="h-3.5 w-3.5" />
                Grupos do combo
                {useGroups && comboGroups.length > 0 && (
                  <Badge className="ml-1 h-4 bg-violet-100 text-violet-700 border border-violet-200 hover:bg-violet-100 text-[10px]">
                    {comboGroups.length}
                  </Badge>
                )}
              </TabsTrigger>
            )}
          </TabsList>

          {/* ── Básico ─────────────────────────────────────────────── */}
          <TabsContent value="basic" className="mt-4 space-y-5">
            <FormSection title="Identificação">
              <div className="grid gap-4 md:grid-cols-[1fr_auto]">
                <FormField label="Nome" htmlFor="pf-name" required error={errors.name}>
                  <Input
                    id="pf-name"
                    name="name"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    maxLength={200}
                    placeholder="Ex.: X-Burger Especial"
                    autoFocus
                  />
                </FormField>
                <FormField label="SKU" htmlFor="pf-sku" hint="Auto-gerado se vazio">
                  <Input
                    id="pf-sku"
                    name="sku"
                    value={sku}
                    onChange={(e) => setSku(e.target.value)}
                    maxLength={60}
                    placeholder="Auto"
                    className="font-mono w-40"
                  />
                </FormField>
              </div>

              <FormField label="Categoria" htmlFor="pf-cat">
                <select
                  id="pf-cat"
                  name="category_id"
                  value={categoryId}
                  onChange={(e) => setCategoryId(e.target.value ? Number(e.target.value) : '')}
                  className="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400 max-w-md"
                >
                  <option value="">Sem categoria</option>
                  {categories.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.name}
                    </option>
                  ))}
                </select>
              </FormField>

              <FormField label="Descrição" htmlFor="pf-desc" hint="Texto exibido no cardápio.">
                <textarea
                  id="pf-desc"
                  name="description"
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                  rows={3}
                  maxLength={1000}
                  className="w-full rounded-md border border-zinc-200 bg-white p-3 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
                  placeholder="Ex.: Pão artesanal, burger 180g, queijo prato e molho especial."
                />
              </FormField>
            </FormSection>

            <FormSection title="Tipo e preço">
              <div className="grid gap-4 md:grid-cols-3">
                <FormField label="Tipo" htmlFor="pf-type">
                  <select
                    id="pf-type"
                    name="type"
                    value={type}
                    onChange={(e) => setType(e.target.value as 'simple' | 'combo')}
                    className="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
                  >
                    <option value="simple">Simples</option>
                    <option value="combo">Combo</option>
                  </select>
                </FormField>

                {showCombo && (
                  <FormField label="Modo de preço" htmlFor="pf-pmode" hint="Fixo = um valor único · Soma = soma dos itens">
                    <select
                      id="pf-pmode"
                      name="price_mode"
                      value={priceMode}
                      onChange={(e) => setPriceMode(e.target.value as 'fixed' | 'sum')}
                      className="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
                    >
                      <option value="fixed">Fixo</option>
                      <option value="sum">Soma dos itens</option>
                    </select>
                  </FormField>
                )}
                {!showCombo && <input type="hidden" name="price_mode" value="fixed" />}

                <FormField label="Ordem" htmlFor="pf-sort">
                  <Input
                    id="pf-sort"
                    name="sort_order"
                    type="number"
                    value={sortOrder}
                    onChange={(e) => setSortOrder(Math.max(0, Number(e.target.value) || 0))}
                    min={0}
                    max={9999}
                  />
                </FormField>
              </div>

              {(type === 'simple' || priceMode === 'fixed') && (
                <FormField label="Preço" htmlFor="pf-price" required error={errors.price}>
                  <div className="relative max-w-xs">
                    <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">R$</span>
                    <Input
                      id="pf-price"
                      name="price"
                      value={price}
                      onChange={(e) => setPrice(moneyMask(e.target.value))}
                      inputMode="decimal"
                      placeholder="0,00"
                      className="pl-9"
                    />
                  </div>
                </FormField>
              )}
              {priceMode === 'sum' && type === 'combo' && (
                <p className="text-xs text-zinc-500">
                  Preço fica como a soma dos itens dos grupos. Defina o preço base zero ou use 0,00 — o backend calcula automaticamente.
                </p>
              )}
              {/* Always include price field even in sum mode to avoid empty submission */}
              {priceMode === 'sum' && type === 'combo' && (
                <input type="hidden" name="price" value={price || '0'} />
              )}

              <div className="grid gap-3 md:grid-cols-3">
                {priceMode !== 'sum' && (
                  <FormField label="Preço promocional" htmlFor="pf-promo" hint="Deixe vazio para sem promoção">
                    <div className="relative">
                      <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">R$</span>
                      <Input
                        id="pf-promo"
                        name="promo_price"
                        value={promoPrice}
                        onChange={(e) => setPromoPrice(moneyMask(e.target.value))}
                        inputMode="decimal"
                        className="pl-9"
                      />
                    </div>
                  </FormField>
                )}
                {priceMode === 'sum' && (
                  <FormField label="Desconto promocional" htmlFor="pf-promo-pct" hint="% sobre a soma dos itens">
                    <div className="relative">
                      <Input
                        id="pf-promo-pct"
                        name="promo_percentage"
                        value={promoPercentage}
                        onChange={(e) => setPromoPercentage(e.target.value.replace(/[^\d.,]/g, ''))}
                        inputMode="decimal"
                        placeholder="0"
                        className="pr-10"
                      />
                      <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">%</span>
                    </div>
                  </FormField>
                )}

                <FormField label="Promoção a partir de" htmlFor="pf-pstart" hint="Opcional">
                  <Input
                    id="pf-pstart"
                    name="promo_start_at"
                    type="datetime-local"
                    value={promoStart}
                    onChange={(e) => setPromoStart(e.target.value)}
                  />
                </FormField>

                <FormField label="Promoção até" htmlFor="pf-pend" hint="Opcional">
                  <Input
                    id="pf-pend"
                    name="promo_end_at"
                    type="datetime-local"
                    value={promoEnd}
                    onChange={(e) => setPromoEnd(e.target.value)}
                  />
                </FormField>
              </div>
            </FormSection>

            <FormSection title="Imagem" description="JPG, PNG ou WEBP até 5 MB. Recomendado: 800×800.">
              <div className="flex items-start gap-4">
                <div className="flex h-28 w-28 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50">
                  {imagePreview ? (
                    <img src={imagePreview} alt="Pré-visualização" className="h-full w-full object-cover" />
                  ) : (
                    <ImageOff className="h-6 w-6 text-zinc-400" />
                  )}
                </div>
                <div className="flex-1 space-y-2">
                  <input
                    ref={fileRef}
                    type="file"
                    name="image"
                    accept="image/jpeg,image/png,image/webp"
                    onChange={handleImageChange}
                    className="block w-full text-sm text-zinc-700 file:mr-3 file:rounded-md file:border-0 file:bg-zinc-900 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white hover:file:bg-zinc-800"
                  />
                  {imageFile && (
                    <p className="text-xs text-zinc-500">
                      Selecionado: <span className="font-medium text-zinc-700">{imageFile.name}</span>
                    </p>
                  )}
                </div>
              </div>
            </FormSection>

            <FormSection title="Status">
              <label className="flex items-start gap-3 cursor-pointer">
                <input
                  type="checkbox"
                  name="active"
                  value="1"
                  checked={active}
                  onChange={(e) => setActive(e.target.checked)}
                  className="peer sr-only"
                />
                <span className="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
                  <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
                </span>
                <span className="flex-1 text-sm">
                  <span className="font-medium text-zinc-800">{active ? 'Produto ativo' : 'Produto oculto'}</span>
                  <span className="block text-xs text-zinc-500 mt-0.5">
                    {active ? 'Visível no cardápio público.' : 'Oculto do cardápio mas mantido no catálogo.'}
                  </span>
                </span>
              </label>
            </FormSection>
          </TabsContent>

          {/* ── Personalização (simple) ────────────────────────────── */}
          {showCustomization && (
            <TabsContent value="customization" className="mt-4 space-y-4">
              <FormSection
                title="Personalização do produto"
                description="Permita que o cliente escolha ingredientes adicionais, troque pães, etc."
              >
                <label className="flex items-start gap-3 cursor-pointer rounded-xl border border-zinc-200 p-3 hover:bg-zinc-50">
                  <input
                    type="checkbox"
                    checked={customizationEnabled}
                    onChange={(e) => setCustomizationEnabled(e.target.checked)}
                    className="peer sr-only"
                  />
                  <span className="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
                    <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
                  </span>
                  <span className="flex-1 text-sm">
                    <span className="font-medium text-zinc-800">
                      {customizationEnabled ? 'Personalização ativa' : 'Sem personalização'}
                    </span>
                    <span className="block text-xs text-zinc-500 mt-0.5">
                      Quando ativa, o cliente verá grupos de escolha (ex.: "Escolha o pão", "Adicionais").
                    </span>
                  </span>
                </label>
                {errors.customization && <p className="text-xs text-red-600">{errors.customization}</p>}
              </FormSection>

              {customizationEnabled && (
                <>
                  {customizationGroups.map((g, gi) => (
                    <CustomizationGroupCard
                      key={gi}
                      group={g}
                      groupIndex={gi}
                      ingredients={ingredients}
                      templates={customization_templates}
                      onChange={(next) => {
                        const arr = [...customizationGroups]
                        arr[gi] = next
                        setCustomizationGroups(arr)
                      }}
                      onRemove={() => setCustomizationGroups(customizationGroups.filter((_, i) => i !== gi))}
                      onMove={(dir) => moveGroup('cust', gi, dir)}
                    />
                  ))}

                  <Button type="button" variant="outline" className="w-full gap-2" onClick={addCustGroup}>
                    <Plus className="h-4 w-4" />
                    Adicionar grupo de personalização
                  </Button>
                </>
              )}
            </TabsContent>
          )}

          {/* ── Combo groups ───────────────────────────────────────── */}
          {showCombo && (
            <TabsContent value="combo" className="mt-4 space-y-4">
              <FormSection
                title="Grupos do combo"
                description="Defina grupos de escolha (ex.: Lanche, Acompanhamento, Bebida). Cada grupo tem mín/máx de seleções."
              >
                <label className="flex items-start gap-3 cursor-pointer rounded-xl border border-zinc-200 p-3 hover:bg-zinc-50">
                  <input
                    type="checkbox"
                    checked={useGroups}
                    onChange={(e) => setUseGroups(e.target.checked)}
                    className="peer sr-only"
                  />
                  <span className="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
                    <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
                  </span>
                  <span className="flex-1 text-sm">
                    <span className="font-medium text-zinc-800">
                      {useGroups ? 'Combo com grupos de escolha' : 'Combo simples (sem grupos)'}
                    </span>
                    <span className="block text-xs text-zinc-500 mt-0.5">
                      Use grupos quando o cliente precisar escolher entre opções (ex.: Coca ou Guaraná).
                    </span>
                  </span>
                </label>
                {errors.combo && <p className="text-xs text-red-600">{errors.combo}</p>}
              </FormSection>

              {useGroups && (
                <>
                  {comboGroups.map((g, gi) => (
                    <ComboGroupCard
                      key={gi}
                      group={g}
                      groupIndex={gi}
                      simpleProducts={simple_products}
                      onChange={(next) => {
                        const arr = [...comboGroups]
                        arr[gi] = next
                        setComboGroups(arr)
                      }}
                      onRemove={() => setComboGroups(comboGroups.filter((_, i) => i !== gi))}
                      onMove={(dir) => moveGroup('combo', gi, dir)}
                    />
                  ))}

                  <Button type="button" variant="outline" className="w-full gap-2" onClick={addComboGroup}>
                    <Plus className="h-4 w-4" />
                    Adicionar grupo
                  </Button>
                </>
              )}
            </TabsContent>
          )}
        </Tabs>

        <div className="sticky bottom-0 -mx-5 mt-6 border-t border-zinc-200 bg-white/95 backdrop-blur px-5 py-3">
          <div className="mx-auto flex max-w-[1292px] flex-wrap items-center justify-between gap-2">
            <Button asChild type="button" variant="outline">
              <a href={urls.list}>Cancelar</a>
            </Button>
            <div className="flex items-center gap-2">
              {isEdit && urls.destroy && (
                <Button
                  type="button"
                  variant="ghost"
                  className="text-red-600 hover:text-red-700 hover:bg-red-50 gap-2"
                  onClick={() => setConfirmDelete(true)}
                >
                  <Trash2 className="h-4 w-4" />
                  Remover produto
                </Button>
              )}
              <Button type="submit" className="gap-2">
                <Save className="h-4 w-4" />
                {isEdit ? 'Salvar alterações' : 'Criar produto'}
              </Button>
            </div>
          </div>
        </div>
      </form>

      <ConfirmDialog
        open={confirmDelete}
        onOpenChange={setConfirmDelete}
        title="Remover este produto?"
        description="O produto será removido. Pedidos existentes mantêm o histórico desse item."
        confirmLabel="Remover"
        destructive
        onConfirm={handleDelete}
      />
    </AdminStorePageShell>
  )
}
