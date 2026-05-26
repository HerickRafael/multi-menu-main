import { useEffect, useState, type FormEvent } from 'react'
import { ArrowLeft, CircleDollarSign, Receipt, Save, Tag, Trash2 } from 'lucide-react'
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

type Expense = {
  id: number | null
  category_id: number | null
  description: string
  amount: number
  reference_month: string
  expense_date: string
  payment_method: string
  notes: string
}

type Payload = {
  is_edit: boolean
  expense: Expense
  categories: Array<{ id: number; name: string; type: string }>
  urls: { list: string; categories: string; submit: string; destroy: string | null }
}

declare global {
  interface Window {
    __ADMIN_STORE_EXPENSE_FORM__?: Payload
  }
}

const PAYMENT_METHODS = ['Dinheiro', 'PIX', 'Boleto', 'Cartão débito', 'Cartão crédito', 'Transferência', 'Outros']

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

export default function AdminStoreExpenseFormPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_EXPENSE_FORM__) || ({} as Payload)
  const isEdit = !!payload.is_edit
  const urls = payload.urls
  const e = payload.expense

  const [categoryId, setCategoryId] = useState<number | ''>(e?.category_id ?? '')
  const [description, setDescription] = useState(e?.description ?? '')
  const [amount, setAmount] = useState(e?.amount ? formatBR(e.amount) : '')
  const [referenceMonth, setReferenceMonth] = useState(e?.reference_month ?? '')
  const [expenseDate, setExpenseDate] = useState(e?.expense_date ?? '')
  const [paymentMethod, setPaymentMethod] = useState(e?.payment_method ?? '')
  const [notes, setNotes] = useState(e?.notes ?? '')
  const [confirmDelete, setConfirmDelete] = useState(false)

  const selectedCategory = payload.categories?.find((c) => c.id === categoryId)

  function handleSubmit(ev: FormEvent) {
    if (!description.trim()) {
      ev.preventDefault()
      showToast('Informe a descrição da despesa.', 'error')
      return
    }
    const num = Number.parseFloat(amount.replace(',', '.'))
    if (!Number.isFinite(num) || num <= 0) {
      ev.preventDefault()
      showToast('Informe um valor maior que zero.', 'error')
    }
  }

  async function handleDelete() {
    if (!urls.destroy) return
    try {
      const res = await fetch(urls.destroy, {
        method: 'GET',
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

  return (
    <AdminStorePageShell section="financial">
      <AdminPageHeader
        title={isEdit ? 'Editar despesa' : 'Nova despesa'}
        description={isEdit ? 'Atualize os dados desta despesa.' : 'Registre uma nova despesa do mês.'}
        icon={<Receipt className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <Button asChild variant="outline">
            <a href={urls.list} className="gap-2">
              <ArrowLeft className="h-4 w-4" />
              Voltar
            </a>
          </Button>
        }
      />

      <form action={urls.submit} method="POST" onSubmit={handleSubmit} className="space-y-5 max-w-3xl">
        <input type="hidden" name="csrf_token" value={getCsrfToken()} />

        <FormSection title="Dados da despesa">
          <FormField label="Descrição" htmlFor="ex-desc" required>
            <Input
              id="ex-desc"
              name="description"
              value={description}
              onChange={(ev) => setDescription(ev.target.value)}
              maxLength={200}
              placeholder="Ex.: Aluguel novembro 2025"
              autoFocus
            />
          </FormField>

          <div className="grid gap-4 md:grid-cols-2">
            <FormField label="Categoria" htmlFor="ex-cat" hint={selectedCategory ? `Tipo: ${selectedCategory.type === 'fixed' ? 'fixa' : 'variável'}` : 'Opcional'}>
              <div className="flex gap-2">
                <select
                  id="ex-cat"
                  name="category_id"
                  value={categoryId}
                  onChange={(ev) => setCategoryId(ev.target.value ? Number(ev.target.value) : '')}
                  className="flex h-9 flex-1 rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
                >
                  <option value="">Sem categoria</option>
                  {payload.categories?.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.name} ({c.type === 'fixed' ? 'fixa' : 'variável'})
                    </option>
                  ))}
                </select>
                <Button asChild type="button" variant="outline" size="sm">
                  <a href={urls.categories} target="_blank" rel="noreferrer">
                    <Tag className="h-3.5 w-3.5" />
                  </a>
                </Button>
              </div>
            </FormField>

            <FormField label="Valor" htmlFor="ex-amount" required>
              <div className="relative">
                <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">R$</span>
                <Input
                  id="ex-amount"
                  name="amount"
                  value={amount}
                  onChange={(ev) => setAmount(moneyMask(ev.target.value))}
                  inputMode="decimal"
                  placeholder="0,00"
                  className="pl-9"
                />
              </div>
            </FormField>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <FormField label="Mês de referência" htmlFor="ex-ref" hint="Formato YYYY-MM. Despesa será atribuída a este mês.">
              <Input
                id="ex-ref"
                name="reference_month"
                type="month"
                value={referenceMonth}
                onChange={(ev) => setReferenceMonth(ev.target.value)}
              />
            </FormField>

            <FormField label="Data de pagamento" htmlFor="ex-date" hint="Opcional. Em branco usa o 1º dia do mês.">
              <Input
                id="ex-date"
                name="payment_date"
                type="date"
                value={expenseDate}
                onChange={(ev) => setExpenseDate(ev.target.value)}
              />
            </FormField>
          </div>

          <FormField label="Forma de pagamento" htmlFor="ex-pay">
            <select
              id="ex-pay"
              name="payment_method"
              value={paymentMethod}
              onChange={(ev) => setPaymentMethod(ev.target.value)}
              className="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
            >
              <option value="">Não informado</option>
              {PAYMENT_METHODS.map((m) => (
                <option key={m} value={m}>
                  {m}
                </option>
              ))}
            </select>
          </FormField>

          <FormField label="Observações" htmlFor="ex-notes" hint="Opcional. Detalhes adicionais sobre a despesa.">
            <textarea
              id="ex-notes"
              name="notes"
              value={notes}
              onChange={(ev) => setNotes(ev.target.value)}
              rows={3}
              maxLength={500}
              className="w-full rounded-md border border-zinc-200 bg-white p-3 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
            />
          </FormField>
        </FormSection>

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
                  Remover
                </Button>
              )}
              <Button type="submit" className="gap-2">
                <Save className="h-4 w-4" />
                {isEdit ? 'Salvar alterações' : 'Criar despesa'}
              </Button>
            </div>
          </div>
        </div>
      </form>

      <ConfirmDialog
        open={confirmDelete}
        onOpenChange={setConfirmDelete}
        title="Remover esta despesa?"
        description="A despesa será removida permanentemente."
        confirmLabel="Remover"
        destructive
        onConfirm={handleDelete}
      />
    </AdminStorePageShell>
  )
}
