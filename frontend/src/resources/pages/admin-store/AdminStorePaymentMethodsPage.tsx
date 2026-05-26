import { useEffect, useMemo, useState, type FormEvent } from 'react'
import {
  Banknote,
  CreditCard,
  ImageOff,
  Landmark,
  Pencil,
  Plus,
  QrCode,
  Save,
  ShieldCheck,
  Trash2,
  Upload,
  Utensils,
  X,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  AdminStorePageShell,
  AdminPageHeader,
  ConfirmDialog,
  EmptyState,
  FormField,
  FormSection,
  showToast,
  getCsrfToken,
  useStoreContext,
} from '@/components/admin-store'

type PaymentType = 'credit' | 'debit' | 'pix' | 'cash' | 'voucher' | 'others'

type Method = {
  id: number
  name: string
  instructions: string
  sort_order: number
  active: boolean
  type: PaymentType
  pix_key: string
  meta: Record<string, unknown>
  icon_url: string
}

type Brand = { slug: string; label: string; value: string; url: string }

type Payload = {
  methods: Method[]
  next_sort_order: number
  brand_library: Brand[]
  flash: { type: string | null; message: string | null }
  errors: string[]
  urls: {
    list: string
    store: string
    update_base: string
    destroy_base: string
    batch: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_PAYMENT_METHODS__?: Payload
  }
}

const TYPE_INFO: Record<PaymentType, { label: string; icon: typeof CreditCard; color: string }> = {
  credit: { label: 'Crédito', icon: CreditCard, color: 'bg-indigo-100 text-indigo-700 border-indigo-200' },
  debit: { label: 'Débito', icon: CreditCard, color: 'bg-purple-100 text-purple-700 border-purple-200' },
  pix: { label: 'PIX', icon: QrCode, color: 'bg-cyan-100 text-cyan-700 border-cyan-200' },
  cash: { label: 'Dinheiro', icon: Banknote, color: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
  voucher: { label: 'Vale-refeição', icon: Utensils, color: 'bg-orange-100 text-orange-700 border-orange-200' },
  others: { label: 'Outros', icon: Landmark, color: 'bg-zinc-100 text-zinc-700 border-zinc-200' },
}

function detectPixKeyType(key: string): string {
  const trimmed = key.trim()
  if (!trimmed) return ''
  if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed)) return 'E-mail'
  const digits = trimmed.replace(/\D/g, '')
  if (digits.length === 11) return 'CPF'
  if (digits.length === 14) return 'CNPJ'
  if (digits.length >= 10 && digits.length <= 13) return 'Telefone'
  return 'Aleatória'
}

export default function AdminStorePaymentMethodsPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_PAYMENT_METHODS__) || ({} as Payload)
  const urls = payload.urls
  const brandLibrary = payload.brand_library ?? []

  const [methods, setMethods] = useState<Method[]>(payload.methods ?? [])
  const [showForm, setShowForm] = useState(false)
  const [editing, setEditing] = useState<Method | null>(null)
  const [confirmDelete, setConfirmDelete] = useState<Method | null>(null)
  const [busy, setBusy] = useState(false)

  // Form state
  const [type, setType] = useState<PaymentType>('credit')
  const [name, setName] = useState('')
  const [instructions, setInstructions] = useState('')
  const [iconValue, setIconValue] = useState('')
  const [pixKey, setPixKey] = useState('')
  const [pixHolder, setPixHolder] = useState('')
  const [iconFile, setIconFile] = useState<File | null>(null)
  const [iconPreview, setIconPreview] = useState('')

  useEffect(() => {
    if (payload.flash?.message) {
      showToast(payload.flash.message, payload.flash.type === 'error' ? 'error' : 'success')
    }
    if (payload.errors?.length) {
      payload.errors.forEach((err) => showToast(err, 'error'))
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  function openNewForm(presetType: PaymentType = 'credit') {
    setEditing(null)
    setType(presetType)
    setName(presetType === 'pix' ? 'Pix' : '')
    setInstructions('')
    setIconValue('')
    setPixKey('')
    setPixHolder('')
    setIconFile(null)
    setIconPreview('')
    setShowForm(true)
  }

  function openEditForm(method: Method) {
    setEditing(method)
    setType(method.type)
    setName(method.name)
    setInstructions(method.instructions)
    setIconValue(String(method.meta?.icon ?? ''))
    setPixKey(String(method.meta?.px_key ?? method.pix_key ?? ''))
    setPixHolder(String(method.meta?.px_holder_name ?? ''))
    setIconFile(null)
    setIconPreview(method.icon_url)
    setShowForm(true)
  }

  function closeForm() {
    setShowForm(false)
    setEditing(null)
  }

  function handleIconFileChange(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0]
    if (!file) return
    setIconFile(file)
    setIconValue('') // clear library selection
    const reader = new FileReader()
    reader.onload = () => setIconPreview(String(reader.result || ''))
    reader.readAsDataURL(file)
  }

  function pickBrand(brand: Brand) {
    setIconValue(brand.value)
    setIconFile(null)
    setIconPreview(brand.url)
    if (!name || (editing && name === editing.name)) {
      setName(brand.label)
    }
  }

  async function submitForm(e: FormEvent) {
    e.preventDefault()
    if (!name.trim() && type !== 'pix') {
      showToast('Informe o nome do método.', 'error')
      return
    }
    if (type === 'pix' && !pixKey.trim()) {
      showToast('Informe a chave Pix.', 'error')
      return
    }

    setBusy(true)
    const fd = new FormData()
    fd.append('csrf_token', getCsrfToken())
    fd.append('name', name.trim() || (type === 'pix' ? 'Pix' : ''))
    fd.append('type', type)
    fd.append('instructions', instructions)

    if (type === 'pix') {
      fd.append('pix_key', pixKey.trim())
      fd.append('meta[px_key]', pixKey.trim())
      if (pixHolder.trim()) fd.append('meta[px_holder_name]', pixHolder.trim())
    } else {
      if (iconFile) {
        fd.append('brand_icon', iconFile)
      } else if (iconValue) {
        fd.append('meta[icon]', iconValue)
      }
    }

    const url = editing ? `${urls.update_base}${editing.id}` : urls.store

    try {
      const res = await fetch(url, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const data = (await res.json().catch(() => null)) as
        | { success?: boolean; method?: Method; message?: string }
        | null
      if (data?.success && data.method) {
        const saved = data.method as Method
        setMethods((prev) => {
          if (editing) {
            return prev.map((m) => (m.id === saved.id ? saved : m))
          }
          return [...prev, saved]
        })
        showToast(data.message || 'Salvo.', 'success')
        closeForm()
      } else {
        showToast(data?.message || 'Falha ao salvar método.', 'error')
      }
    } catch {
      showToast('Falha de rede ao salvar.', 'error')
    } finally {
      setBusy(false)
    }
  }

  async function toggleActive(method: Method) {
    setBusy(true)
    try {
      const fd = new FormData()
      fd.append('csrf_token', getCsrfToken())
      fd.append('active', method.active ? '0' : '1')
      const res = await fetch(`${urls.update_base}${method.id}`, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean; method?: Method } | null
      if (data?.success && data.method) {
        setMethods((prev) => prev.map((m) => (m.id === method.id ? data.method! : m)))
        showToast(method.active ? 'Método desativado.' : 'Método ativado.', 'success')
      } else {
        showToast('Falha ao alternar status.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setBusy(false)
    }
  }

  async function batchToggle(active: boolean) {
    setBusy(true)
    try {
      const fd = new FormData()
      fd.append('csrf_token', getCsrfToken())
      fd.append('active', active ? '1' : '0')
      const res = await fetch(urls.batch, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean } | null
      if (data?.success) {
        setMethods((prev) => prev.map((m) => ({ ...m, active })))
        showToast(active ? 'Todos os métodos ativados.' : 'Todos os métodos desativados.', 'success')
      } else {
        showToast('Falha ao atualizar em lote.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setBusy(false)
    }
  }

  async function handleDelete() {
    if (!confirmDelete) return
    try {
      const fd = new FormData()
      fd.append('csrf_token', getCsrfToken())
      const res = await fetch(`${urls.destroy_base}${confirmDelete.id}/delete`, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean } | null
      if (data?.success) {
        setMethods((prev) => prev.filter((m) => m.id !== confirmDelete.id))
        showToast(`Método "${confirmDelete.name}" removido.`, 'success')
      } else {
        showToast('Falha ao remover método.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    }
  }

  // Group by type for display
  const groupedMethods = useMemo(() => {
    const groups: Record<PaymentType, Method[]> = {
      credit: [],
      debit: [],
      pix: [],
      cash: [],
      voucher: [],
      others: [],
    }
    for (const m of methods) {
      const t: PaymentType = (groups[m.type] ? m.type : 'others') as PaymentType
      groups[t].push(m)
    }
    for (const k of Object.keys(groups)) {
      groups[k as PaymentType].sort((a, b) => a.sort_order - b.sort_order || a.id - b.id)
    }
    return groups
  }, [methods])

  const totalActive = methods.filter((m) => m.active).length

  return (
    <AdminStorePageShell section="settings">
      <AdminPageHeader
        title="Métodos de pagamento"
        description={`${methods.length} método${methods.length === 1 ? '' : 's'} cadastrado${methods.length === 1 ? '' : 's'} · ${totalActive} ativo${totalActive === 1 ? '' : 's'}.`}
        icon={<CreditCard className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <div className="flex items-center gap-2">
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={() => batchToggle(true)}
              disabled={busy}
              className="gap-1.5"
            >
              <ShieldCheck className="h-3.5 w-3.5" />
              Ativar todos
            </Button>
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={() => batchToggle(false)}
              disabled={busy}
            >
              Desativar todos
            </Button>
            <Button type="button" onClick={() => openNewForm()} className="gap-2">
              <Plus className="h-4 w-4" />
              Novo método
            </Button>
          </div>
        }
      />

      {methods.length === 0 ? (
        <EmptyState
          title="Sem métodos de pagamento"
          description="Cadastre os métodos aceitos pela sua loja. Comece adicionando Pix, dinheiro ou cartões."
          icon={<CreditCard className="h-5 w-5" />}
          action={
            <Button onClick={() => openNewForm()} className="gap-2">
              <Plus className="h-4 w-4" />
              Adicionar primeiro método
            </Button>
          }
        />
      ) : (
        <section className="space-y-5">
          {(Object.entries(groupedMethods) as Array<[PaymentType, Method[]]>).map(
            ([t, items]) =>
              items.length > 0 && (
                <div key={t}>
                  {(() => {
                    const info = TYPE_INFO[t]
                    const Icon = info.icon
                    return (
                      <h2 className="mb-2 flex items-center gap-2 text-sm font-semibold text-zinc-700">
                        <Icon className="h-4 w-4 text-zinc-500" />
                        {info.label}
                        <Badge className={`${info.color} border hover:${info.color.split(' ').slice(0, 2).join(' ')}`}>
                          {items.length}
                        </Badge>
                      </h2>
                    )
                  })()}
                  <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    {items.map((m) => (
                      <div
                        key={m.id}
                        className={`rounded-xl border bg-white p-3 shadow-sm transition ${
                          m.active ? 'border-zinc-200' : 'border-zinc-200 opacity-60'
                        }`}
                      >
                        <div className="flex items-start gap-2.5">
                          {m.icon_url ? (
                            <img
                              src={m.icon_url}
                              alt=""
                              className="h-10 w-10 shrink-0 rounded border border-zinc-200 bg-white object-contain p-1"
                            />
                          ) : (
                            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded border border-zinc-200 bg-zinc-50 text-zinc-400">
                              {(() => {
                                const Icon = TYPE_INFO[m.type].icon
                                return <Icon className="h-4 w-4" />
                              })()}
                            </span>
                          )}
                          <div className="min-w-0 flex-1">
                            <p className="text-sm font-semibold text-zinc-800 truncate">{m.name}</p>
                            {m.type === 'pix' && m.meta?.px_key ? (
                              <p className="font-mono text-[11px] text-zinc-500 truncate">
                                {String(m.meta.px_key)} · {detectPixKeyType(String(m.meta.px_key))}
                              </p>
                            ) : m.instructions ? (
                              <p className="text-[11px] text-zinc-500 truncate">{m.instructions}</p>
                            ) : null}
                          </div>
                        </div>

                        <div className="mt-2 flex items-center justify-between border-t border-zinc-100 pt-2">
                          <button
                            type="button"
                            onClick={() => toggleActive(m)}
                            disabled={busy}
                            className="text-left disabled:opacity-50"
                          >
                            {m.active ? (
                              <Badge className="bg-emerald-100 text-emerald-700 border border-emerald-200 hover:bg-emerald-200 text-[10px] h-5">
                                Ativo
                              </Badge>
                            ) : (
                              <Badge className="bg-zinc-100 text-zinc-700 border border-zinc-200 hover:bg-zinc-200 text-[10px] h-5">
                                Oculto
                              </Badge>
                            )}
                          </button>
                          <div className="flex items-center gap-0.5">
                            <Button
                              size="sm"
                              variant="ghost"
                              className="h-7 px-1.5 text-zinc-600 hover:text-zinc-900"
                              onClick={() => openEditForm(m)}
                              aria-label="Editar"
                            >
                              <Pencil className="h-3 w-3" />
                            </Button>
                            <Button
                              size="sm"
                              variant="ghost"
                              className="h-7 px-1.5 text-red-600 hover:text-red-700 hover:bg-red-50"
                              onClick={() => setConfirmDelete(m)}
                              aria-label="Remover"
                            >
                              <Trash2 className="h-3 w-3" />
                            </Button>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              ),
          )}
        </section>
      )}

      {/* Form modal */}
      {showForm && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" onClick={closeForm}>
          <div
            className="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-xl"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="mb-4 flex items-center justify-between gap-2">
              <h3 className="text-lg font-semibold text-zinc-800">
                {editing ? `Editar método · ${editing.name}` : 'Novo método de pagamento'}
              </h3>
              <Button variant="ghost" size="sm" onClick={closeForm}>
                <X className="h-4 w-4" />
              </Button>
            </div>

            <form onSubmit={submitForm} className="space-y-4">
              <FormField label="Tipo">
                <div className="grid gap-2 sm:grid-cols-3">
                  {(Object.keys(TYPE_INFO) as PaymentType[]).map((t) => {
                    const info = TYPE_INFO[t]
                    const Icon = info.icon
                    const selected = t === type
                    return (
                      <button
                        type="button"
                        key={t}
                        onClick={() => {
                          setType(t)
                          if (t === 'pix') setName('Pix')
                        }}
                        disabled={!!editing && t !== editing.type}
                        className={`flex items-center gap-2 rounded-lg border p-2 text-sm transition ${
                          selected
                            ? 'border-zinc-900 bg-zinc-50 ring-1 ring-zinc-900'
                            : 'border-zinc-200 hover:border-zinc-400'
                        } disabled:opacity-50 disabled:cursor-not-allowed`}
                      >
                        <Icon className="h-4 w-4 text-zinc-500" />
                        <span className="font-medium text-zinc-800">{info.label}</span>
                      </button>
                    )
                  })}
                </div>
                {editing && (
                  <p className="text-xs text-zinc-500 mt-1">O tipo não pode ser alterado após criação.</p>
                )}
              </FormField>

              {type !== 'pix' && (
                <FormField label="Nome do método" htmlFor="pm-name" required>
                  <Input
                    id="pm-name"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    maxLength={150}
                    placeholder="Ex.: Visa, Mastercard, VR..."
                  />
                </FormField>
              )}

              {type === 'pix' && (
                <>
                  <FormField
                    label="Chave Pix"
                    htmlFor="pm-pix"
                    required
                    hint={pixKey ? `Detectado como: ${detectPixKeyType(pixKey)}` : 'CPF, CNPJ, e-mail, telefone ou chave aleatória.'}
                  >
                    <Input
                      id="pm-pix"
                      value={pixKey}
                      onChange={(e) => setPixKey(e.target.value)}
                      placeholder="000.000.000-00, e-mail@exemplo.com, etc."
                      className="font-mono"
                    />
                  </FormField>
                  <FormField label="Nome do titular" htmlFor="pm-holder" hint="Opcional — aparece na confirmação do pedido.">
                    <Input
                      id="pm-holder"
                      value={pixHolder}
                      onChange={(e) => setPixHolder(e.target.value)}
                      maxLength={150}
                      placeholder="Como aparece no banco"
                    />
                  </FormField>
                </>
              )}

              {type !== 'pix' && brandLibrary.length > 0 && (
                <FormField label="Bandeira" hint="Selecione um ícone padrão ou faça upload custom.">
                  <div className="grid grid-cols-3 gap-2 sm:grid-cols-4 max-h-48 overflow-y-auto">
                    {brandLibrary.map((b) => {
                      const selected = b.value === iconValue
                      return (
                        <button
                          type="button"
                          key={b.slug}
                          onClick={() => pickBrand(b)}
                          className={`flex flex-col items-center gap-1 rounded-lg border p-2 transition ${
                            selected
                              ? 'border-zinc-900 bg-zinc-50 ring-1 ring-zinc-900'
                              : 'border-zinc-200 hover:border-zinc-400'
                          }`}
                        >
                          <img src={b.url} alt={b.label} className="h-8 w-12 object-contain" />
                          <span className="text-[10px] text-zinc-600 truncate w-full text-center">{b.label}</span>
                        </button>
                      )
                    })}
                  </div>
                </FormField>
              )}

              {type !== 'pix' && (
                <FormField label="OU enviar ícone custom" hint="PNG/SVG/WEBP até 2MB. Sobrescreve a bandeira selecionada.">
                  <div className="flex items-center gap-3">
                    <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white">
                      {iconPreview ? (
                        <img src={iconPreview} alt="" className="h-full w-full object-contain p-1" />
                      ) : (
                        <ImageOff className="h-4 w-4 text-zinc-400" />
                      )}
                    </div>
                    <label className="flex items-center gap-2 cursor-pointer rounded-md border border-zinc-200 px-3 py-2 text-xs hover:bg-zinc-50">
                      <Upload className="h-3.5 w-3.5" />
                      Escolher arquivo
                      <input
                        type="file"
                        accept="image/png,image/svg+xml,image/jpeg,image/webp"
                        onChange={handleIconFileChange}
                        className="sr-only"
                      />
                    </label>
                    {iconFile && (
                      <span className="text-xs text-zinc-600 truncate">{iconFile.name}</span>
                    )}
                  </div>
                </FormField>
              )}

              <FormField label="Instruções para o cliente" htmlFor="pm-instr" hint="Texto exibido no checkout (opcional).">
                <textarea
                  id="pm-instr"
                  value={instructions}
                  onChange={(e) => setInstructions(e.target.value)}
                  rows={2}
                  maxLength={500}
                  className="w-full rounded-md border border-zinc-200 bg-white p-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
                  placeholder="Ex.: Pague na entrega (precisa de troco?)"
                />
              </FormField>

              <div className="flex items-center justify-end gap-2 border-t border-zinc-100 pt-3">
                <Button type="button" variant="outline" onClick={closeForm}>
                  Cancelar
                </Button>
                <Button type="submit" disabled={busy} className="gap-2">
                  <Save className="h-4 w-4" />
                  {busy ? 'Salvando...' : editing ? 'Salvar alterações' : 'Adicionar método'}
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}

      <ConfirmDialog
        open={!!confirmDelete}
        onOpenChange={(open) => !open && setConfirmDelete(null)}
        title="Remover método?"
        description={
          confirmDelete
            ? `O método "${confirmDelete.name}" será removido. Pedidos antigos mantêm o histórico.`
            : null
        }
        confirmLabel="Remover"
        destructive
        onConfirm={handleDelete}
      />
    </AdminStorePageShell>
  )
}
