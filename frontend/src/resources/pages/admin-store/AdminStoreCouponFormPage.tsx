import { useEffect, useState, type FormEvent } from 'react'
import { ArrowLeft, Gift, Save, Trash2, Sparkles } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
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

type Coupon = {
  id: number
  coupon_code: string
  customer_phone: string
  discount_percentage: number
  usage_limit: number
  times_used: number
  is_used: boolean
  allow_multiple_uses_per_customer: boolean
}

type CouponFormPayload = {
  coupon: Coupon | null
  usage_stats: { unique_customers: number; total_uses: number } | null
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
    __ADMIN_STORE_COUPON_FORM__?: CouponFormPayload
  }
}

function generateCode(): string {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
  let out = 'CUPOM'
  for (let i = 0; i < 6; i++) out += chars[Math.floor(Math.random() * chars.length)]
  return out
}

function maskPercent(raw: string): string {
  const digits = raw.replace(/[^\d,]/g, '')
  if (!digits) return ''
  const parts = digits.split(',')
  const intPart = parts[0].replace(/^0+(?=\d)/, '') || '0'
  if (parts.length === 1) return intPart
  return `${intPart},${parts.slice(1).join('').slice(0, 2)}`
}

function maskPhone(raw: string): string {
  const d = raw.replace(/\D/g, '').slice(0, 11)
  if (d.length === 0) return ''
  if (d.length <= 2) return `(${d}`
  if (d.length <= 6) return `(${d.slice(0, 2)}) ${d.slice(2)}`
  if (d.length <= 10) return `(${d.slice(0, 2)}) ${d.slice(2, 6)}-${d.slice(6)}`
  return `(${d.slice(0, 2)}) ${d.slice(2, 7)}-${d.slice(7)}`
}

export default function AdminStoreCouponFormPage() {
  const ctx = useStoreContext()
  const payload = (typeof window !== 'undefined' && window.__ADMIN_STORE_COUPON_FORM__) || ({} as CouponFormPayload)
  const { urls, coupon, usage_stats } = payload
  const isEdit = !!coupon?.id

  const [code, setCode] = useState(coupon?.coupon_code ?? '')
  const [phone, setPhone] = useState(maskPhone(coupon?.customer_phone ?? ''))
  const [discountPct, setDiscountPct] = useState(
    coupon?.discount_percentage != null
      ? String(coupon.discount_percentage).replace('.', ',')
      : '10',
  )
  const [usageLimit, setUsageLimit] = useState<string>(String(coupon?.usage_limit ?? 1))
  const [allowMultiple, setAllowMultiple] = useState<boolean>(!!coupon?.allow_multiple_uses_per_customer)
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [confirmDelete, setConfirmDelete] = useState(false)

  useEffect(() => {
    if (payload.flash?.error) showToast(payload.flash.error, 'error')
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  function validate(): boolean {
    const next: Record<string, string> = {}
    if (isEdit && !code.trim()) next.code = 'Informe o código do cupom.'

    const pct = Number.parseFloat((discountPct || '').replace(',', '.'))
    if (!Number.isFinite(pct) || pct <= 0 || pct > 100) {
      next.discount_percentage = 'O desconto deve estar entre 1% e 100%.'
    }

    const lim = Number.parseInt(usageLimit, 10)
    if (!Number.isFinite(lim) || lim < 0) {
      next.usage_limit = 'O limite deve ser um número inteiro >= 0 (0 = ilimitado).'
    }

    // phone optional but if provided, must have 10-11 digits
    const digits = phone.replace(/\D/g, '')
    if (digits.length > 0 && digits.length < 10) {
      next.customer_phone = 'WhatsApp deve ter pelo menos 10 dígitos (incluindo DDD).'
    }

    setErrors(next)
    return Object.keys(next).length === 0
  }

  function handleSubmit(e: FormEvent) {
    if (!validate()) {
      e.preventDefault()
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
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean; message?: string } | null
      if (data?.success) {
        showToast('Cupom removido.', 'success')
        window.location.href = urls.list
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
        title={isEdit ? 'Editar cupom' : 'Novo cupom de desconto'}
        description={
          isEdit
            ? 'Atualize as informações do cupom.'
            : 'Crie um cupom para clientes utilizarem nos pedidos. Você pode restringir a um cliente específico ou liberar geral.'
        }
        icon={<Gift className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <Button asChild variant="outline">
            <a href={urls.list} className="gap-2">
              <ArrowLeft className="h-4 w-4" />
              Voltar
            </a>
          </Button>
        }
      />

      {isEdit && usage_stats && (
        <section className="grid gap-3 sm:grid-cols-3">
          <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p className="text-xs text-zinc-500">Clientes únicos</p>
            <p className="mt-1 text-2xl font-semibold text-zinc-800">{usage_stats.unique_customers}</p>
          </div>
          <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p className="text-xs text-zinc-500">Total de usos</p>
            <p className="mt-1 text-2xl font-semibold text-zinc-800">{usage_stats.total_uses}</p>
          </div>
          <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p className="text-xs text-zinc-500">Status</p>
            <p className="mt-1">
              {coupon?.is_used ? (
                <Badge className="bg-zinc-100 text-zinc-700 border border-zinc-200 hover:bg-zinc-200">Utilizado</Badge>
              ) : (
                <Badge className="bg-emerald-100 text-emerald-700 border border-emerald-200 hover:bg-emerald-200">Ativo</Badge>
              )}
            </p>
          </div>
        </section>
      )}

      <form action={urls.submit} method="POST" onSubmit={handleSubmit} className="space-y-5 max-w-3xl">
        <input type="hidden" name="csrf_token" value={getCsrfToken()} />

        <FormSection
          title="Código e cliente"
          description={
            isEdit
              ? 'Cada cupom tem um código único. Mantenha em branco o WhatsApp para deixar o cupom liberado para qualquer cliente.'
              : 'Deixe o código em branco para gerar automaticamente. O WhatsApp restringe o cupom a um cliente específico (opcional).'
          }
        >
          <FormField
            label="Código"
            htmlFor="cp-code"
            required={isEdit}
            error={errors.code}
            hint={isEdit ? undefined : 'Deixe em branco para gerar automaticamente.'}
          >
            <div className="flex gap-2">
              <Input
                id="cp-code"
                name="code"
                value={code}
                onChange={(e) => setCode(e.target.value.toUpperCase().replace(/[^A-Z0-9_-]/g, '').slice(0, 30))}
                placeholder="CUPOM10OFF"
                className="font-mono uppercase"
              />
              {!isEdit && (
                <Button
                  type="button"
                  variant="outline"
                  className="gap-2 whitespace-nowrap"
                  onClick={() => setCode(generateCode())}
                >
                  <Sparkles className="h-4 w-4" />
                  Gerar
                </Button>
              )}
            </div>
          </FormField>

          <FormField
            label="WhatsApp do cliente"
            htmlFor="cp-phone"
            hint="Opcional. Quando preenchido, somente esse cliente pode usar o cupom."
            error={errors.customer_phone}
          >
            <Input
              id="cp-phone"
              name="customer_phone"
              value={phone}
              onChange={(e) => setPhone(maskPhone(e.target.value))}
              placeholder="(11) 98888-7777"
              inputMode="tel"
              maxLength={20}
            />
          </FormField>
        </FormSection>

        <FormSection
          title="Desconto e limites"
          description="Defina o percentual de desconto e quantas vezes esse cupom pode ser resgatado."
        >
          <div className="grid gap-4 md:grid-cols-2">
            <FormField label="Desconto" htmlFor="cp-discount" required error={errors.discount_percentage}>
              <div className="relative">
                <Input
                  id="cp-discount"
                  name="discount_percentage"
                  value={discountPct}
                  onChange={(e) => setDiscountPct(maskPercent(e.target.value))}
                  inputMode="decimal"
                  placeholder="10"
                  className="pr-10"
                />
                <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">%</span>
              </div>
            </FormField>

            <FormField
              label="Limite de usos"
              htmlFor="cp-limit"
              required
              error={errors.usage_limit}
              hint="0 = ilimitado. Ex.: 1 = uso único."
            >
              <Input
                id="cp-limit"
                name="usage_limit"
                type="number"
                min={0}
                max={9999}
                value={usageLimit}
                onChange={(e) => setUsageLimit(e.target.value.replace(/[^\d]/g, ''))}
                placeholder="1"
              />
            </FormField>
          </div>

          <FormField label="Reutilização por cliente">
            <label className="flex items-start gap-3 cursor-pointer rounded-xl border border-zinc-200 p-3 hover:bg-zinc-50">
              <input
                type="checkbox"
                name="allow_multiple_uses_per_customer"
                value="1"
                checked={allowMultiple}
                onChange={(e) => setAllowMultiple(e.target.checked)}
                className="peer sr-only"
              />
              <span className="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
                <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
              </span>
              <span className="flex-1 text-sm">
                <span className="font-medium text-zinc-800">Permitir múltiplos usos pelo mesmo cliente</span>
                <span className="block text-xs text-zinc-500 mt-0.5">
                  Quando desativado, cada cliente só pode usar este cupom uma única vez (mesmo que o limite global não tenha sido atingido).
                </span>
              </span>
            </label>
          </FormField>
        </FormSection>

        <div className="flex flex-wrap items-center justify-between gap-2">
          <div className="flex flex-wrap items-center gap-2">
            <Button type="submit" className="gap-2">
              <Save className="h-4 w-4" />
              {isEdit ? 'Salvar alterações' : 'Criar cupom'}
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
              Remover cupom
            </Button>
          )}
        </div>
      </form>

      <ConfirmDialog
        open={confirmDelete}
        onOpenChange={setConfirmDelete}
        title="Remover este cupom?"
        description="O cupom será excluído permanentemente. Esta ação não pode ser desfeita."
        confirmLabel="Remover"
        destructive
        onConfirm={handleDelete}
      />
    </AdminStorePageShell>
  )
}
