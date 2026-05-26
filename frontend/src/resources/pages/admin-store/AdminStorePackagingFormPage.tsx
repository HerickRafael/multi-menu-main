import { useEffect, useState, type FormEvent } from 'react'
import { AlertTriangle, ArrowLeft, Box, Package, Save, Trash2 } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  AdminStorePageShell,
  AdminPageHeader,
  ConfirmDialog,
  FormField,
  FormSection,
  formatCurrency,
  getCsrfToken,
  showToast,
  useStoreContext,
} from '@/components/admin-store'

type Supply = {
  id: number | null
  name: string
  description: string
  unit: string
  cost_per_unit: number
  stock_quantity: number
  min_stock_alert: number
  supplier: string
  active: boolean
}

type Payload = {
  is_edit: boolean
  supply: Supply
  products: Array<{ id: number; name: string }>
  urls: { list: string; submit: string; destroy: string | null }
}

declare global {
  interface Window {
    __ADMIN_STORE_PACKAGING_FORM__?: Payload
  }
}

const UNIT_OPTIONS = [
  { value: 'un', label: 'Unidade (un)' },
  { value: 'kg', label: 'Quilo (kg)' },
  { value: 'g', label: 'Grama (g)' },
  { value: 'l', label: 'Litro (L)' },
  { value: 'ml', label: 'Mililitro (mL)' },
  { value: 'cx', label: 'Caixa (cx)' },
  { value: 'pct', label: 'Pacote (pct)' },
  { value: 'rl', label: 'Rolo (rl)' },
]

function moneyMask(raw: string): string {
  const digits = raw.replace(/[^\d,]/g, '')
  if (!digits) return ''
  const parts = digits.split(',')
  const intPart = parts[0].replace(/^0+(?=\d)/, '') || '0'
  if (parts.length === 1) return intPart
  return `${intPart},${parts.slice(1).join('').slice(0, 2)}`
}

function decimalMask(raw: string, maxDecimals = 3): string {
  const digits = raw.replace(/[^\d,]/g, '')
  if (!digits) return ''
  const parts = digits.split(',')
  const intPart = parts[0].replace(/^0+(?=\d)/, '') || '0'
  if (parts.length === 1) return intPart
  return `${intPart},${parts.slice(1).join('').slice(0, maxDecimals)}`
}

function formatBR(n: number, decimals = 2): string {
  return n.toFixed(decimals).replace('.', ',')
}

export default function AdminStorePackagingFormPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_PACKAGING_FORM__) || ({} as Payload)
  const isEdit = !!payload.is_edit
  const urls = payload.urls
  const s = payload.supply

  const [name, setName] = useState(s?.name ?? '')
  const [description, setDescription] = useState(s?.description ?? '')
  const [unit, setUnit] = useState(s?.unit ?? 'un')
  const [costPerUnit, setCostPerUnit] = useState(s?.cost_per_unit ? formatBR(s.cost_per_unit, 4).replace(/,?0+$/, '') : '')
  const [stockQuantity, setStockQuantity] = useState(
    s?.stock_quantity ? formatBR(s.stock_quantity, 2).replace(/,?0+$/, '') : '0',
  )
  const [minStockAlert, setMinStockAlert] = useState(
    s?.min_stock_alert ? formatBR(s.min_stock_alert, 2).replace(/,?0+$/, '') : '0',
  )
  const [supplier, setSupplier] = useState(s?.supplier ?? '')
  const [active, setActive] = useState<boolean>(s?.active ?? true)
  const [confirmDelete, setConfirmDelete] = useState(false)

  // Live preview
  const stock = Number.parseFloat(stockQuantity.replace(',', '.')) || 0
  const minStock = Number.parseFloat(minStockAlert.replace(',', '.')) || 0
  const costNum = Number.parseFloat(costPerUnit.replace(',', '.')) || 0
  const isLowStock = minStock > 0 && stock <= minStock
  const stockValue = stock * costNum

  function handleSubmit(e: FormEvent) {
    if (!name.trim()) {
      e.preventDefault()
      showToast('Informe o nome do insumo.', 'error')
    }
  }

  async function handleDelete() {
    if (!urls.destroy) return
    const fd = new FormData()
    fd.append('csrf_token', getCsrfToken())
    try {
      const res = await fetch(urls.destroy, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        redirect: 'manual',
      })
      if (res.type === 'opaqueredirect' || res.ok || res.status === 302 || res.status === 0) {
        window.location.href = urls.list
      } else {
        showToast('Falha ao remover.', 'error')
      }
    } catch {
      window.location.href = urls.list
    }
  }

  const hasProducts = (payload.products ?? []).length > 0

  return (
    <AdminStorePageShell section="financial">
      <AdminPageHeader
        title={isEdit ? `Editar insumo · ${payload.supply.name}` : 'Novo insumo'}
        description={isEdit ? 'Atualize os dados deste insumo.' : 'Cadastre uma embalagem ou material descartável usado pelos produtos.'}
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

      {hasProducts && (
        <section className="rounded-2xl border border-amber-200 bg-amber-50/70 p-4">
          <p className="text-sm font-semibold text-amber-900 mb-2">
            Em uso por {payload.products.length} produto{payload.products.length === 1 ? '' : 's'}
          </p>
          <div className="flex flex-wrap gap-1">
            {payload.products.slice(0, 12).map((p) => (
              <Badge key={p.id} className="bg-white border border-amber-200 text-amber-900 hover:bg-white text-xs">
                {p.name}
              </Badge>
            ))}
            {payload.products.length > 12 && (
              <Badge className="bg-white border border-amber-200 text-amber-900 hover:bg-white text-xs">
                +{payload.products.length - 12} outros
              </Badge>
            )}
          </div>
          <p className="text-xs text-amber-700 mt-2">
            Alterar o custo deste insumo afeta o CMV de todos os produtos vinculados.
          </p>
        </section>
      )}

      {/* Live preview */}
      <section className="grid gap-3 sm:grid-cols-3">
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Custo por unidade</p>
          <p className="mt-1 text-2xl font-semibold text-zinc-800">{formatCurrency(costNum)}</p>
          <p className="mt-1 text-[11px] text-zinc-500">por {unit}</p>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Estoque atual</p>
          <p className={`mt-1 text-2xl font-semibold ${isLowStock ? 'text-red-600' : 'text-zinc-800'}`}>
            {stock.toFixed(stock % 1 === 0 ? 0 : 2)} {unit}
          </p>
          {isLowStock && (
            <Badge className="mt-1 bg-red-100 text-red-700 border-red-200 hover:bg-red-100 text-[10px] h-4 gap-0.5">
              <AlertTriangle className="h-2.5 w-2.5" />
              Abaixo do mínimo ({minStock})
            </Badge>
          )}
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
          <p className="text-xs text-zinc-500">Valor em estoque</p>
          <p className="mt-1 text-2xl font-semibold text-zinc-800">{formatCurrency(stockValue)}</p>
        </div>
      </section>

      <form action={urls.submit} method="POST" onSubmit={handleSubmit} className="space-y-5 max-w-3xl">
        <input type="hidden" name="csrf_token" value={getCsrfToken()} />
        {isEdit && <input type="hidden" name="id" value={payload.supply.id ?? ''} />}

        <FormSection title="Identificação">
          <FormField label="Nome" htmlFor="pk-name" required>
            <Input
              id="pk-name"
              name="name"
              value={name}
              onChange={(e) => setName(e.target.value)}
              maxLength={150}
              placeholder="Ex.: Caixa de pizza grande, Sacola plástica, Copo descartável 300ml..."
              autoFocus
            />
          </FormField>

          <FormField label="Descrição" htmlFor="pk-desc" hint="Detalhes adicionais como tamanho, cor, especificações.">
            <textarea
              id="pk-desc"
              name="description"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              rows={2}
              maxLength={500}
              className="w-full rounded-md border border-zinc-200 bg-white p-3 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
            />
          </FormField>

          <FormField label="Fornecedor" htmlFor="pk-supplier" hint="Opcional — para controle interno.">
            <Input
              id="pk-supplier"
              name="supplier"
              value={supplier}
              onChange={(e) => setSupplier(e.target.value)}
              maxLength={150}
              placeholder="Ex.: Distribuidora ABC"
            />
          </FormField>
        </FormSection>

        <FormSection title="Custo e estoque">
          <div className="grid gap-4 md:grid-cols-2">
            <FormField label="Unidade de medida" htmlFor="pk-unit" required>
              <select
                id="pk-unit"
                name="unit"
                value={unit}
                onChange={(e) => setUnit(e.target.value)}
                className="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
              >
                {UNIT_OPTIONS.map((opt) => (
                  <option key={opt.value} value={opt.value}>
                    {opt.label}
                  </option>
                ))}
              </select>
            </FormField>

            <FormField label="Custo por unidade" htmlFor="pk-cost" required>
              <div className="relative">
                <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">R$</span>
                <Input
                  id="pk-cost"
                  name="cost_per_unit"
                  value={costPerUnit}
                  onChange={(e) => setCostPerUnit(decimalMask(e.target.value, 4))}
                  inputMode="decimal"
                  placeholder="0,00"
                  className="pl-9 font-mono"
                />
              </div>
            </FormField>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <FormField label="Quantidade em estoque" htmlFor="pk-stock" hint="Quantos você tem agora.">
              <Input
                id="pk-stock"
                name="stock_quantity"
                value={stockQuantity}
                onChange={(e) => setStockQuantity(decimalMask(e.target.value, 2))}
                inputMode="decimal"
                placeholder="0"
                className="font-mono"
              />
            </FormField>

            <FormField
              label="Alerta de estoque mínimo"
              htmlFor="pk-min"
              hint="Quando o estoque atingir esse valor, será marcado como 'baixo'."
            >
              <Input
                id="pk-min"
                name="min_stock_alert"
                value={minStockAlert}
                onChange={(e) => setMinStockAlert(decimalMask(e.target.value, 2))}
                inputMode="decimal"
                placeholder="0"
                className="font-mono"
              />
            </FormField>
          </div>
        </FormSection>

        <FormSection title="Status">
          <label className="flex items-start gap-3 cursor-pointer rounded-xl border border-zinc-200 p-3 hover:bg-zinc-50">
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
              <span className="font-medium text-zinc-800">{active ? 'Insumo ativo' : 'Insumo oculto'}</span>
              <span className="block text-xs text-zinc-500 mt-0.5">
                Quando desativado, o insumo não aparece nas opções de vinculação aos produtos.
              </span>
            </span>
          </label>
        </FormSection>

        <div className="sticky bottom-0 -mx-5 mt-6 border-t border-zinc-200 bg-white/95 backdrop-blur px-5 py-3">
          <div className="mx-auto flex max-w-[1292px] flex-wrap items-center justify-between gap-2">
            <Button asChild type="button" variant="outline">
              <a href={urls.list}>Cancelar</a>
            </Button>
            <div className="flex items-center gap-2">
              {isEdit && urls.destroy && !hasProducts && (
                <Button
                  type="button"
                  variant="ghost"
                  className="text-red-600 hover:text-red-700 hover:bg-red-50 gap-2"
                  onClick={() => setConfirmDelete(true)}
                >
                  <Trash2 className="h-4 w-4" />
                  Remover
                </Button>
              )}
              <Button type="submit" className="gap-2">
                <Save className="h-4 w-4" />
                {isEdit ? 'Salvar alterações' : 'Criar insumo'}
              </Button>
            </div>
          </div>
        </div>
      </form>

      <ConfirmDialog
        open={confirmDelete}
        onOpenChange={setConfirmDelete}
        title="Remover este insumo?"
        description="O insumo será removido permanentemente."
        confirmLabel="Remover"
        destructive
        onConfirm={handleDelete}
      />
    </AdminStorePageShell>
  )
}
