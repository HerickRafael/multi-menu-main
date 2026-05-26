import { useEffect, useRef, useState, type FormEvent } from 'react'
import { ArrowLeft, ImageOff, Save, Trash2, Utensils } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  AdminStorePageShell,
  AdminPageHeader,
  FormField,
  FormSection,
  ConfirmDialog,
  showToast,
  getCsrfToken,
  useStoreContext,
} from '@/components/admin-store'

type IngredientFormData = {
  id: number | null
  name: string
  internal_name: string
  cost: string | number
  sale_price: string | number
  unit: string
  unit_value: string | number
  image_path: string
  active?: boolean
}

type IngredientFormPayload = {
  ingredient: IngredientFormData
  flash: { error: string | null }
  urls: {
    list: string
    submit: string
    destroy?: string
    toggle?: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_INGREDIENT_FORM__?: IngredientFormPayload
  }
}

const UNIT_OPTIONS = [
  { value: 'un', label: 'Unidade (un)' },
  { value: 'kg', label: 'Quilo (kg)' },
  { value: 'g', label: 'Grama (g)' },
  { value: 'mg', label: 'Miligrama (mg)' },
  { value: 'l', label: 'Litro (L)' },
  { value: 'ml', label: 'Mililitro (mL)' },
  { value: 'pc', label: 'Peça (pc)' },
]

const UNIT_LABEL_FOR_PLACEHOLDER: Record<string, string> = {
  un: 'unidade',
  kg: 'kg',
  g: 'g',
  mg: 'mg',
  l: 'litro',
  ml: 'mililitro',
  pc: 'peça',
}

function formatBR(value: number | string | null | undefined, decimals = 2): string {
  if (value === null || value === undefined || value === '') return ''
  const num = typeof value === 'number' ? value : Number.parseFloat(String(value).replace(/\./g, '').replace(',', '.'))
  if (!Number.isFinite(num)) return String(value)
  return num.toFixed(decimals).replace('.', ',')
}

function formatUnitValueBR(value: number | string | null | undefined): string {
  if (value === null || value === undefined || value === '') return ''
  const num = typeof value === 'number' ? value : Number.parseFloat(String(value).replace(/\./g, '').replace(',', '.'))
  if (!Number.isFinite(num)) return String(value)
  // Up to 3 decimals, strip trailing zeros
  let s = num.toFixed(3).replace('.', ',')
  s = s.replace(/,?0+$/, '')
  if (!s.includes(',')) s += ',0'
  return s
}

function resolveImage(path: string): string {
  if (!path) return ''
  if (/^(https?:)?\/\//i.test(path) || path.startsWith('data:')) return path
  return path.startsWith('/') ? path : `/${path}`
}

function moneyMask(raw: string): string {
  // Keep digits and one comma
  const digits = raw.replace(/[^\d,]/g, '')
  if (!digits) return ''
  const parts = digits.split(',')
  const intPart = parts[0].replace(/\D/g, '').replace(/^0+(?=\d)/, '') || '0'
  if (parts.length === 1) return intPart
  const decPart = parts.slice(1).join('').slice(0, 2)
  return `${intPart},${decPart}`
}

export default function AdminStoreIngredientFormPage() {
  const ctx = useStoreContext()
  const payload = (typeof window !== 'undefined' && window.__ADMIN_STORE_INGREDIENT_FORM__) || ({} as IngredientFormPayload)
  const { urls, ingredient } = payload
  const isEdit = !!ingredient?.id

  const initialUnitSelect = (() => {
    const raw = (ingredient?.unit || '').toLowerCase().trim()
    if (!raw) return ''
    const match = UNIT_OPTIONS.find((o) => o.value === raw)
    return match ? match.value : 'custom'
  })()
  const initialUnitCustom = initialUnitSelect === 'custom' ? (ingredient?.unit || '') : ''

  const [name, setName] = useState(ingredient?.name ?? '')
  const [internalName, setInternalName] = useState(ingredient?.internal_name ?? '')
  const [cost, setCost] = useState(formatBR(ingredient?.cost ?? ''))
  const [salePrice, setSalePrice] = useState(formatBR(ingredient?.sale_price ?? ''))
  const [unitSelect, setUnitSelect] = useState(initialUnitSelect)
  const [unitCustom, setUnitCustom] = useState(initialUnitCustom)
  const [unitValue, setUnitValue] = useState(formatUnitValueBR(ingredient?.unit_value ?? ''))
  const [imageFile, setImageFile] = useState<File | null>(null)
  const [imagePreview, setImagePreview] = useState<string>(resolveImage(ingredient?.image_path || ''))
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [confirmDelete, setConfirmDelete] = useState(false)
  const fileRef = useRef<HTMLInputElement>(null)
  const formRef = useRef<HTMLFormElement>(null)

  useEffect(() => {
    if (payload.flash?.error) showToast(payload.flash.error, 'error')
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  const unitLabel =
    unitSelect === 'custom'
      ? unitCustom || 'unidade'
      : UNIT_LABEL_FOR_PLACEHOLDER[unitSelect] || (unitSelect || 'unidade')

  function handleImageChange(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0]
    if (!file) {
      setImageFile(null)
      return
    }
    setImageFile(file)
    const reader = new FileReader()
    reader.onload = () => setImagePreview(String(reader.result || ''))
    reader.readAsDataURL(file)
  }

  function validate(): boolean {
    const next: Record<string, string> = {}
    if (!name.trim()) next.name = 'Informe o nome do ingrediente.'
    if (!cost.trim()) next.cost = 'Informe o custo.'
    if (!salePrice.trim()) next.sale_price = 'Informe o valor de venda.'
    const finalUnit = unitSelect === 'custom' ? unitCustom.trim() : unitSelect
    if (!finalUnit) next.unit = 'Selecione a unidade de medida.'
    if (!unitValue.trim()) {
      next.unit_value = 'Informe o valor da unidade.'
    } else {
      const num = Number.parseFloat(unitValue.replace(',', '.'))
      if (!Number.isFinite(num) || num <= 0) next.unit_value = 'O valor da unidade deve ser maior que zero.'
    }
    setErrors(next)
    return Object.keys(next).length === 0
  }

  function handleSubmit(e: FormEvent) {
    if (!validate()) {
      e.preventDefault()
    }
    // Otherwise, let the native form POST proceed (multipart for image)
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
        showToast('Falha ao remover ingrediente.', 'error')
      }
    } catch {
      window.location.href = urls.list
    }
  }

  return (
    <AdminStorePageShell section="catalog">
      <AdminPageHeader
        title={isEdit ? 'Editar ingrediente' : 'Novo ingrediente'}
        description={
          isEdit
            ? 'Atualize os dados deste ingrediente.'
            : 'Cadastre um novo ingrediente para usar em produtos personalizáveis.'
        }
        icon={<Utensils className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
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
        ref={formRef}
        action={urls.submit}
        method="POST"
        encType="multipart/form-data"
        onSubmit={handleSubmit}
        className="space-y-5 max-w-3xl"
      >
        <input type="hidden" name="csrf_token" value={getCsrfToken()} />

        <FormSection title="Identificação" description="Nome exibido aos clientes e nome interno (opcional).">
          <FormField label="Nome" htmlFor="ing-name" required error={errors.name}>
            <Input
              id="ing-name"
              name="name"
              value={name}
              onChange={(e) => setName(e.target.value)}
              maxLength={150}
              placeholder="Ex.: Bacon, Queijo cheddar, Maionese..."
              autoFocus
            />
          </FormField>

          <FormField
            label="Nome interno"
            htmlFor="ing-internal"
            hint='Complemento visível apenas no painel admin. Ex.: "Batata frita (Big Fries)"'
          >
            <Input
              id="ing-internal"
              name="internal_name"
              value={internalName}
              onChange={(e) => setInternalName(e.target.value)}
              maxLength={150}
              placeholder="Ex.: Big Fries, Porção G, 180g..."
            />
          </FormField>
        </FormSection>

        <FormSection title="Valores" description="Custo de aquisição e valor cobrado pelo ingrediente quando vendido isolado.">
          <div className="grid gap-4 md:grid-cols-2">
            <FormField label="Custo" htmlFor="ing-cost" required error={errors.cost}>
              <div className="relative">
                <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">R$</span>
                <Input
                  id="ing-cost"
                  name="cost"
                  value={cost}
                  onChange={(e) => setCost(moneyMask(e.target.value))}
                  inputMode="decimal"
                  placeholder="3,50"
                  className="pl-9"
                />
              </div>
            </FormField>

            <FormField label="Valor de venda" htmlFor="ing-sale" required error={errors.sale_price}>
              <div className="relative">
                <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">R$</span>
                <Input
                  id="ing-sale"
                  name="sale_price"
                  value={salePrice}
                  onChange={(e) => setSalePrice(moneyMask(e.target.value))}
                  inputMode="decimal"
                  placeholder="5,90"
                  className="pl-9"
                />
              </div>
            </FormField>
          </div>
        </FormSection>

        <FormSection title="Unidade de medida" description="Define como o ingrediente é dosado.">
          <div className="grid gap-4 md:grid-cols-2">
            <FormField label="Tipo de unidade" htmlFor="ing-unit-select" required error={errors.unit}>
              <select
                id="ing-unit-select"
                name="unit_select"
                value={unitSelect}
                onChange={(e) => setUnitSelect(e.target.value)}
                className="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-zinc-400"
              >
                <option value="">Selecione</option>
                {UNIT_OPTIONS.map((opt) => (
                  <option key={opt.value} value={opt.value}>
                    {opt.label}
                  </option>
                ))}
                <option value="custom">Outra unidade…</option>
              </select>
              {unitSelect === 'custom' && (
                <Input
                  name="unit_custom"
                  value={unitCustom}
                  onChange={(e) => setUnitCustom(e.target.value)}
                  maxLength={30}
                  placeholder="Informe a unidade"
                  className="mt-2"
                />
              )}
              {/* Hidden input ensures unit_custom is always present so backend logic works */}
              {unitSelect !== 'custom' && <input type="hidden" name="unit_custom" value="" />}
            </FormField>

            <FormField
              label="Valor da unidade"
              htmlFor="ing-unit-value"
              required
              hint={`Ex.: 1 ${unitLabel}`}
              error={errors.unit_value}
            >
              <Input
                id="ing-unit-value"
                name="unit_value"
                value={unitValue}
                onChange={(e) => setUnitValue(e.target.value.replace(/[^\d,.-]/g, ''))}
                inputMode="decimal"
                placeholder={`Ex.: 1 ${unitLabel}`}
              />
            </FormField>
          </div>
        </FormSection>

        <FormSection title="Imagem" description="Opcional. JPG, PNG ou WEBP até 5 MB.">
          <div className="flex items-start gap-4">
            <div className="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50">
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
              {imagePreview && (
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  className="h-7 px-2 text-xs text-red-600 hover:text-red-700 hover:bg-red-50"
                  onClick={() => {
                    setImageFile(null)
                    setImagePreview('')
                    if (fileRef.current) fileRef.current.value = ''
                  }}
                >
                  Remover seleção
                </Button>
              )}
            </div>
          </div>
        </FormSection>

        <div className="flex flex-wrap items-center justify-between gap-2">
          <div className="flex flex-wrap items-center gap-2">
            <Button type="submit" className="gap-2">
              <Save className="h-4 w-4" />
              {isEdit ? 'Salvar alterações' : 'Criar ingrediente'}
            </Button>
            <Button asChild type="button" variant="outline">
              <a href={urls.list}>Cancelar</a>
            </Button>
          </div>
          {isEdit && urls.destroy && (
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
        </div>
      </form>

      <ConfirmDialog
        open={confirmDelete}
        onOpenChange={setConfirmDelete}
        title="Remover ingrediente?"
        description="O ingrediente será removido. Produtos que o utilizam podem ser afetados."
        confirmLabel="Remover"
        destructive
        onConfirm={handleDelete}
      />
    </AdminStorePageShell>
  )
}
