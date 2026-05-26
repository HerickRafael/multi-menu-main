import { useEffect, useState, type FormEvent } from 'react'
import { ArrowDown, ArrowLeft, ArrowUp, Plus, Save, Trash2, Wand2, X } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
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

type Item = {
  id: number | null
  ingredient_id: number | null
  ingredient_name: string
  label: string
  delta: number
  is_default: boolean
  default_qty: number
  min_qty: number
  max_qty: number
  sort_order: number
}

type Template = {
  id: number | null
  name: string
  type: 'single' | 'extra' | 'pool'
  min_qty: number
  max_qty: number
  active: boolean
  hide_duplicates: boolean
  items: Item[]
}

type Ingredient = {
  id: number
  name: string
  internal_name: string
  sale_price: number
  image_path: string
}

type Payload = {
  is_edit: boolean
  template: Template
  ingredients: Ingredient[]
  products_using: Array<{ id: number; name: string }>
  flash: { error: string | null }
  urls: { list: string; submit: string; destroy: string | null }
}

declare global {
  interface Window {
    __ADMIN_STORE_CT_FORM__?: Payload
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

function parseMoney(raw: string): number {
  const cleaned = (raw || '0').replace(/\./g, '').replace(',', '.')
  const n = Number.parseFloat(cleaned)
  return Number.isFinite(n) ? n : 0
}

function formatBR(n: number): string {
  return n.toFixed(2).replace('.', ',')
}

const TYPE_OPTIONS: Array<{ value: Template['type']; label: string; description: string }> = [
  { value: 'extra', label: 'Adicionais', description: 'Cliente escolhe vários itens (adicionar/remover livremente).' },
  { value: 'single', label: 'Escolha única', description: 'Cliente escolhe somente 1 item do grupo.' },
  { value: 'pool', label: 'Pool', description: 'Cliente escolhe quantidades de um conjunto fixo.' },
]

export default function AdminStoreCustomizationTemplateFormPage() {
  const ctx = useStoreContext()
  const payload = (typeof window !== 'undefined' && window.__ADMIN_STORE_CT_FORM__) || ({} as Payload)
  const { urls, ingredients = [] } = payload
  const isEdit = !!payload.is_edit

  const [name, setName] = useState(payload.template?.name ?? '')
  const [type, setType] = useState<Template['type']>(payload.template?.type ?? 'extra')
  const [minQty, setMinQty] = useState<number>(payload.template?.min_qty ?? 0)
  const [maxQty, setMaxQty] = useState<number>(payload.template?.max_qty ?? 99)
  const [active, setActive] = useState<boolean>(payload.template?.active ?? true)
  const [hideDuplicates, setHideDuplicates] = useState<boolean>(payload.template?.hide_duplicates ?? false)
  const [items, setItems] = useState<Item[]>(payload.template?.items ?? [])
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [confirmDelete, setConfirmDelete] = useState(false)

  useEffect(() => {
    if (payload.flash?.error) showToast(payload.flash.error, 'error')
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  function addItem() {
    setItems((prev) => [
      ...prev,
      {
        id: null,
        ingredient_id: null,
        ingredient_name: '',
        label: '',
        delta: 0,
        is_default: false,
        default_qty: 0,
        min_qty: 0,
        max_qty: 1,
        sort_order: prev.length,
      },
    ])
  }

  function updateItem(idx: number, patch: Partial<Item>) {
    setItems((prev) => prev.map((it, i) => (i === idx ? { ...it, ...patch } : it)))
  }

  function removeItem(idx: number) {
    setItems((prev) => prev.filter((_, i) => i !== idx))
  }

  function moveItem(idx: number, dir: -1 | 1) {
    setItems((prev) => {
      const j = idx + dir
      if (j < 0 || j >= prev.length) return prev
      const copy = [...prev]
      ;[copy[idx], copy[j]] = [copy[j], copy[idx]]
      return copy
    })
  }

  function validate(): boolean {
    const next: Record<string, string> = {}
    if (!name.trim()) next.name = 'Informe o nome do template.'
    if (minQty < 0) next.min_qty = 'Mínimo não pode ser negativo.'
    if (maxQty < 1) next.max_qty = 'Máximo deve ser ≥ 1.'
    if (maxQty < minQty) next.max_qty = 'Máximo deve ser ≥ mínimo.'
    if (items.length === 0) {
      next.items = 'Adicione pelo menos um item ao template.'
    } else {
      for (const it of items) {
        if (!it.ingredient_id && !it.label.trim()) {
          next.items = 'Cada item precisa de um ingrediente OU rótulo manual.'
          break
        }
      }
    }
    setErrors(next)
    return Object.keys(next).length === 0
  }

  function handleSubmit(e: FormEvent) {
    if (!validate()) {
      e.preventDefault()
      const firstErr = Object.values(errors)[0]
      if (firstErr) showToast(firstErr, 'error')
      else showToast('Corrija os campos em vermelho.', 'error')
    }
  }

  async function handleDelete() {
    if (!urls.destroy) return
    const formData = new FormData()
    formData.append('csrf_token', getCsrfToken())
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
        showToast('Falha ao remover template.', 'error')
      }
    } catch {
      window.location.href = urls.list
    }
  }

  const hasProductsUsing = (payload.products_using ?? []).length > 0

  return (
    <AdminStorePageShell section="catalog">
      <AdminPageHeader
        title={isEdit ? `Editar template · ${payload.template.name}` : 'Novo template de personalização'}
        description={
          isEdit
            ? 'Atualize os ingredientes do template. Mudanças refletem em todos os produtos que usam este template.'
            : 'Crie um grupo reutilizável que pode ser aplicado a múltiplos produtos.'
        }
        icon={<Wand2 className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <Button asChild variant="outline">
            <a href={urls.list} className="gap-2">
              <ArrowLeft className="h-4 w-4" />
              Voltar
            </a>
          </Button>
        }
      />

      {hasProductsUsing && (
        <section className="rounded-2xl border border-amber-200 bg-amber-50/70 p-4">
          <p className="text-sm font-semibold text-amber-900 mb-2">
            Em uso por {payload.products_using.length} produto{payload.products_using.length === 1 ? '' : 's'}
          </p>
          <div className="flex flex-wrap gap-1">
            {payload.products_using.slice(0, 10).map((p) => (
              <Badge
                key={p.id}
                className="bg-white border border-amber-200 text-amber-900 hover:bg-white text-xs"
              >
                {p.name}
              </Badge>
            ))}
            {payload.products_using.length > 10 && (
              <Badge className="bg-white border border-amber-200 text-amber-900 hover:bg-white text-xs">
                +{payload.products_using.length - 10} outros
              </Badge>
            )}
          </div>
          <p className="text-xs text-amber-700 mt-2">
            Alterações neste template refletem em todos os produtos listados acima.
          </p>
        </section>
      )}

      <form action={urls.submit} method="POST" onSubmit={handleSubmit} className="space-y-5 max-w-4xl">
        <input type="hidden" name="csrf_token" value={getCsrfToken()} />

        <FormSection title="Configuração do template">
          <FormField label="Nome" htmlFor="ct-name" required error={errors.name}>
            <Input
              id="ct-name"
              name="name"
              value={name}
              onChange={(e) => setName(e.target.value)}
              maxLength={150}
              placeholder="Ex.: Adicionais clássicos, Pães disponíveis, Pontos da carne..."
              autoFocus
            />
          </FormField>

          <FormField label="Tipo de grupo">
            <input type="hidden" name="type" value={type} />
            <div className="grid gap-2 sm:grid-cols-3">
              {TYPE_OPTIONS.map((opt) => {
                const selected = opt.value === type
                return (
                  <button
                    type="button"
                    key={opt.value}
                    onClick={() => setType(opt.value)}
                    className={`text-left rounded-xl border p-3 transition ${
                      selected
                        ? 'border-zinc-900 bg-zinc-50 ring-1 ring-zinc-900'
                        : 'border-zinc-200 bg-white hover:border-zinc-400'
                    }`}
                  >
                    <p className="text-sm font-semibold text-zinc-800">{opt.label}</p>
                    <p className="mt-0.5 text-xs text-zinc-500">{opt.description}</p>
                  </button>
                )
              })}
            </div>
          </FormField>

          <div className="grid gap-4 md:grid-cols-2">
            <FormField label="Quantidade mínima" htmlFor="ct-min" error={errors.min_qty}>
              <Input
                id="ct-min"
                name="min_qty"
                type="number"
                min={0}
                value={minQty}
                onChange={(e) => setMinQty(Math.max(0, Number(e.target.value) || 0))}
              />
            </FormField>
            <FormField label="Quantidade máxima" htmlFor="ct-max" error={errors.max_qty}>
              <Input
                id="ct-max"
                name="max_qty"
                type="number"
                min={1}
                value={maxQty}
                onChange={(e) => setMaxQty(Math.max(1, Number(e.target.value) || 1))}
              />
            </FormField>
          </div>

          <label className="flex items-start gap-3 cursor-pointer rounded-xl border border-zinc-200 p-3 hover:bg-zinc-50">
            <input
              type="checkbox"
              name="active"
              checked={active}
              onChange={(e) => setActive(e.target.checked)}
              className="peer sr-only"
            />
            <span className="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
              <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
            </span>
            <span className="flex-1 text-sm">
              <span className="font-medium text-zinc-800">{active ? 'Template ativo' : 'Template oculto'}</span>
              <span className="block text-xs text-zinc-500 mt-0.5">
                Quando desativado, este template fica oculto da lista de aplicação em produtos.
              </span>
            </span>
          </label>

          <label className="flex items-start gap-3 cursor-pointer rounded-xl border border-zinc-200 p-3 hover:bg-zinc-50">
            <input
              type="checkbox"
              name="hide_duplicates"
              checked={hideDuplicates}
              onChange={(e) => setHideDuplicates(e.target.checked)}
              className="peer sr-only"
            />
            <span className="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
              <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
            </span>
            <span className="flex-1 text-sm">
              <span className="font-medium text-zinc-800">Ocultar ingredientes duplicados</span>
              <span className="block text-xs text-zinc-500 mt-0.5">
                Se um ingrediente já estiver no produto, oculta-o deste grupo (evita duplicar opções).
              </span>
            </span>
          </label>
        </FormSection>

        <FormSection
          title={`Itens do template (${items.length})`}
          description="Cada item pode ser um ingrediente do cadastro OU um rótulo manual. Configure preço, padrão e limites individuais."
        >
          {errors.items && <p className="text-xs text-red-600">{errors.items}</p>}

          <div className="space-y-2">
            {items.map((it, idx) => {
              const ing = ingredients.find((i) => i.id === it.ingredient_id)
              return (
                <div key={idx} className="rounded-xl border border-zinc-200 bg-zinc-50/40 p-3 space-y-2">
                  <div className="flex items-center gap-2">
                    <div className="flex flex-col gap-0.5">
                      <button
                        type="button"
                        onClick={() => moveItem(idx, -1)}
                        className="p-0.5 text-zinc-400 hover:text-zinc-700"
                        aria-label="Mover para cima"
                      >
                        <ArrowUp className="h-3 w-3" />
                      </button>
                      <button
                        type="button"
                        onClick={() => moveItem(idx, 1)}
                        className="p-0.5 text-zinc-400 hover:text-zinc-700"
                        aria-label="Mover para baixo"
                      >
                        <ArrowDown className="h-3 w-3" />
                      </button>
                    </div>

                    <div className="flex-1 min-w-0">
                      <select
                        name={`items[${idx}][ingredient_id]`}
                        value={it.ingredient_id ?? ''}
                        onChange={(e) => updateItem(idx, { ingredient_id: e.target.value ? Number(e.target.value) : null })}
                        className="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
                      >
                        <option value="">— Sem ingrediente (use rótulo manual) —</option>
                        {ingredients.map((i) => (
                          <option key={i.id} value={i.id}>
                            {i.name}
                            {i.internal_name ? ` (${i.internal_name})` : ''}
                          </option>
                        ))}
                      </select>
                      {ing && (
                        <p className="mt-1 text-[10px] text-zinc-500">
                          Preço de venda do ingrediente: {formatCurrency(ing.sale_price)}
                        </p>
                      )}
                    </div>

                    <input type="hidden" name={`items[${idx}][sort_order]`} value={idx} />

                    <button
                      type="button"
                      onClick={() => removeItem(idx)}
                      className="p-1.5 text-red-500 hover:bg-red-50 rounded"
                      aria-label="Remover item"
                    >
                      <X className="h-3.5 w-3.5" />
                    </button>
                  </div>

                  <div className="grid gap-2 md:grid-cols-[1fr_120px_80px_80px]">
                    <Input
                      name={`items[${idx}][label]`}
                      value={it.label}
                      onChange={(e) => updateItem(idx, { label: e.target.value })}
                      placeholder={ing ? `(usa "${ing.name}")` : 'Rótulo manual (opcional se ingrediente selecionado)'}
                      maxLength={200}
                      className="h-9 text-sm"
                    />
                    <div className="relative">
                      <span className="pointer-events-none absolute left-2 top-1/2 -translate-y-1/2 text-xs text-zinc-400">+R$</span>
                      <Input
                        name={`items[${idx}][delta]`}
                        value={formatBR(it.delta)}
                        onChange={(e) => updateItem(idx, { delta: parseMoney(moneyMask(e.target.value)) })}
                        inputMode="decimal"
                        placeholder="0,00"
                        className="h-9 text-xs pl-9 font-mono"
                      />
                    </div>
                    <Input
                      type="number"
                      min={0}
                      name={`items[${idx}][min_qty]`}
                      value={it.min_qty}
                      onChange={(e) => updateItem(idx, { min_qty: Math.max(0, Number(e.target.value) || 0) })}
                      placeholder="Min"
                      className="h-9 text-center"
                    />
                    <Input
                      type="number"
                      min={0}
                      name={`items[${idx}][max_qty]`}
                      value={it.max_qty}
                      onChange={(e) => updateItem(idx, { max_qty: Math.max(0, Number(e.target.value) || 0) })}
                      placeholder="Max"
                      className="h-9 text-center"
                    />
                  </div>

                  <div className="flex items-center gap-3 text-xs">
                    <label className="flex items-center gap-1 cursor-pointer">
                      <input
                        type="checkbox"
                        name={`items[${idx}][is_default]`}
                        value="1"
                        checked={it.is_default}
                        onChange={(e) => updateItem(idx, { is_default: e.target.checked })}
                        className="h-3.5 w-3.5"
                      />
                      <span className="text-zinc-600">Padrão</span>
                    </label>
                    {it.is_default && (
                      <div className="flex items-center gap-1">
                        <span className="text-zinc-500">Qtd:</span>
                        <Input
                          type="number"
                          min={0}
                          name={`items[${idx}][default_qty]`}
                          value={it.default_qty}
                          onChange={(e) => updateItem(idx, { default_qty: Math.max(0, Number(e.target.value) || 0) })}
                          className="h-7 w-16 text-xs text-center"
                        />
                      </div>
                    )}
                  </div>
                </div>
              )
            })}

            <Button type="button" variant="outline" className="w-full gap-2" onClick={addItem}>
              <Plus className="h-4 w-4" />
              Adicionar item
            </Button>
          </div>
        </FormSection>

        <div className="sticky bottom-0 -mx-5 mt-6 border-t border-zinc-200 bg-white/95 backdrop-blur px-5 py-3">
          <div className="mx-auto flex max-w-[1292px] flex-wrap items-center justify-between gap-2">
            <Button asChild type="button" variant="outline">
              <a href={urls.list}>Cancelar</a>
            </Button>
            <div className="flex items-center gap-2">
              {isEdit && urls.destroy && !hasProductsUsing && (
                <Button
                  type="button"
                  variant="ghost"
                  className="text-red-600 hover:text-red-700 hover:bg-red-50 gap-2"
                  onClick={() => setConfirmDelete(true)}
                >
                  <Trash2 className="h-4 w-4" />
                  Remover template
                </Button>
              )}
              <Button type="submit" className="gap-2">
                <Save className="h-4 w-4" />
                {isEdit ? 'Salvar alterações' : 'Criar template'}
              </Button>
            </div>
          </div>
        </div>
      </form>

      <ConfirmDialog
        open={confirmDelete}
        onOpenChange={setConfirmDelete}
        title="Remover este template?"
        description="O template será removido permanentemente. Esta ação não pode ser desfeita."
        confirmLabel="Remover"
        destructive
        onConfirm={handleDelete}
      />
    </AdminStorePageShell>
  )
}
