import { useEffect, useState, type FormEvent } from 'react'
import {
  ArrowLeft,
  CheckCircle2,
  Home,
  MapPin,
  Pencil,
  Phone,
  Plus,
  Save,
  Star,
  Trash2,
  UserCog,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
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

type Customer = {
  id: number | null
  name: string
  whatsapp: string
  whatsapp_e164: string
  email: string
  cpf: string
  birth_date: string
  created_at?: string
}

type Address = {
  id: number
  label: string
  name: string
  phone: string
  city: string
  neighborhood: string
  street: string
  number: string
  complement: string
  reference: string
  is_default: boolean
}

type RecentOrder = {
  id: number
  total: number
  status: string
  created_at: string
}

type CustomerFormPayload = {
  customer: Customer
  is_edit: boolean
  addresses: Address[]
  recent_orders: RecentOrder[]
  stats: { total_orders: number; total_spent: number; avg_ticket: number } | null
  cities: Array<{ id: number; name: string }>
  zones: Array<{ id: number; city_id: number; city_name: string; neighborhood: string }>
  errors: string[]
  urls: {
    list: string
    submit: string
    destroy?: string
    validate_whatsapp: string
    addresses_base?: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_CUSTOMER_FORM__?: CustomerFormPayload
  }
}

function maskPhone(raw: string): string {
  const d = raw.replace(/\D/g, '').slice(0, 11)
  if (d.length === 0) return ''
  if (d.length <= 2) return `(${d}`
  if (d.length <= 6) return `(${d.slice(0, 2)}) ${d.slice(2)}`
  if (d.length <= 10) return `(${d.slice(0, 2)}) ${d.slice(2, 6)}-${d.slice(6)}`
  return `(${d.slice(0, 2)}) ${d.slice(2, 7)}-${d.slice(7)}`
}

function maskCpf(raw: string): string {
  const d = raw.replace(/\D/g, '').slice(0, 11)
  if (d.length <= 3) return d
  if (d.length <= 6) return `${d.slice(0, 3)}.${d.slice(3)}`
  if (d.length <= 9) return `${d.slice(0, 3)}.${d.slice(3, 6)}.${d.slice(6)}`
  return `${d.slice(0, 3)}.${d.slice(3, 6)}.${d.slice(6, 9)}-${d.slice(9)}`
}

const EMPTY_ADDRESS: Omit<Address, 'id'> = {
  label: '',
  name: '',
  phone: '',
  city: '',
  neighborhood: '',
  street: '',
  number: '',
  complement: '',
  reference: '',
  is_default: false,
}

export default function AdminStoreCustomerFormPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_CUSTOMER_FORM__) || ({} as CustomerFormPayload)
  const { urls, customer, addresses: initialAddresses = [], recent_orders = [], stats, cities = [], zones = [] } =
    payload
  const isEdit = !!payload.is_edit

  const [name, setName] = useState(customer?.name ?? '')
  const [whatsapp, setWhatsapp] = useState(maskPhone(customer?.whatsapp ?? ''))
  const [email, setEmail] = useState(customer?.email ?? '')
  const [cpf, setCpf] = useState(maskCpf(customer?.cpf ?? ''))
  const [birthDate, setBirthDate] = useState(customer?.birth_date ?? '')
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [confirmDelete, setConfirmDelete] = useState(false)

  const [addresses, setAddresses] = useState<Address[]>(initialAddresses)
  const [editingAddress, setEditingAddress] = useState<Address | null>(null)
  const [showAddressForm, setShowAddressForm] = useState(false)
  const [addressDraft, setAddressDraft] = useState<Omit<Address, 'id'>>(EMPTY_ADDRESS)
  const [addressBusy, setAddressBusy] = useState(false)
  const [addressToDelete, setAddressToDelete] = useState<Address | null>(null)

  useEffect(() => {
    if (payload.errors && payload.errors.length > 0) {
      payload.errors.forEach((err) => showToast(err, 'error'))
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  function validate(): boolean {
    const next: Record<string, string> = {}
    if (!name.trim()) next.name = 'Informe o nome do cliente.'
    const digits = whatsapp.replace(/\D/g, '')
    if (!digits) next.whatsapp = 'Informe o WhatsApp.'
    else if (digits.length < 10) next.whatsapp = 'WhatsApp inválido (mínimo 10 dígitos com DDD).'
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) next.email = 'E-mail inválido.'
    if (cpf && cpf.replace(/\D/g, '').length !== 11) next.cpf = 'CPF deve ter 11 dígitos.'
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
        redirect: 'manual',
      })
      if (res.type === 'opaqueredirect' || res.ok || res.status === 302 || res.status === 0) {
        window.location.href = urls.list
      } else {
        showToast('Falha ao remover cliente.', 'error')
      }
    } catch {
      window.location.href = urls.list
    }
  }

  function openNewAddress() {
    setEditingAddress(null)
    setAddressDraft({ ...EMPTY_ADDRESS, is_default: addresses.length === 0 })
    setShowAddressForm(true)
  }

  function openEditAddress(addr: Address) {
    setEditingAddress(addr)
    setAddressDraft({
      label: addr.label,
      name: addr.name,
      phone: addr.phone,
      city: addr.city,
      neighborhood: addr.neighborhood,
      street: addr.street,
      number: addr.number,
      complement: addr.complement,
      reference: addr.reference,
      is_default: addr.is_default,
    })
    setShowAddressForm(true)
  }

  function closeAddressForm() {
    setShowAddressForm(false)
    setEditingAddress(null)
    setAddressDraft(EMPTY_ADDRESS)
  }

  async function saveAddress(e: FormEvent) {
    e.preventDefault()
    if (!urls.addresses_base) return
    if (!addressDraft.street.trim() || !addressDraft.number.trim()) {
      showToast('Rua e número são obrigatórios.', 'error')
      return
    }

    setAddressBusy(true)
    const targetUrl = editingAddress
      ? `${urls.addresses_base}/${editingAddress.id}`
      : urls.addresses_base

    try {
      const res = await fetch(targetUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json; charset=utf-8',
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': getCsrfToken(),
        },
        body: JSON.stringify(addressDraft),
      })
      const data = (await res.json().catch(() => null)) as
        | { success?: boolean; address?: Address; error?: string }
        | null

      if (data?.success && data.address) {
        const saved = {
          id: data.address.id,
          label: data.address.label ?? '',
          name: data.address.name ?? '',
          phone: data.address.phone ?? '',
          city: data.address.city ?? '',
          neighborhood: data.address.neighborhood ?? '',
          street: data.address.street ?? '',
          number: data.address.number ?? '',
          complement: data.address.complement ?? '',
          reference: data.address.reference ?? '',
          is_default: !!(data.address as unknown as { is_default?: number | boolean }).is_default,
        }

        setAddresses((rows) => {
          // If saved is default, unset others
          let next = rows.map((r) => (saved.is_default ? { ...r, is_default: false } : r))
          if (editingAddress) {
            next = next.map((r) => (r.id === editingAddress.id ? saved : r))
          } else {
            next = [...next, saved]
          }
          return next
        })
        showToast(editingAddress ? 'Endereço atualizado.' : 'Endereço adicionado.', 'success')
        closeAddressForm()
      } else {
        showToast(data?.error || 'Falha ao salvar endereço.', 'error')
      }
    } catch {
      showToast('Falha de rede ao salvar endereço.', 'error')
    } finally {
      setAddressBusy(false)
    }
  }

  async function deleteAddress() {
    if (!addressToDelete || !urls.addresses_base) return
    try {
      const res = await fetch(`${urls.addresses_base}/${addressToDelete.id}/delete`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': getCsrfToken(),
        },
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean; error?: string } | null
      if (data?.success) {
        setAddresses((rows) => rows.filter((r) => r.id !== addressToDelete.id))
        showToast('Endereço removido.', 'success')
      } else {
        showToast(data?.error || 'Falha ao remover endereço.', 'error')
      }
    } catch {
      showToast('Falha de rede ao remover endereço.', 'error')
    }
  }

  const availableZones =
    addressDraft.city
      ? zones.filter((z) => z.city_name.toLowerCase() === addressDraft.city.toLowerCase())
      : zones

  return (
    <AdminStorePageShell section="customers">
      <AdminPageHeader
        title={isEdit ? customer?.name || 'Editar cliente' : 'Novo cliente'}
        description={
          isEdit
            ? 'Atualize os dados, adicione endereços ou veja o histórico de pedidos deste cliente.'
            : 'Cadastre manualmente um novo cliente no sistema.'
        }
        icon={<UserCog className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <Button asChild variant="outline">
            <a href={urls.list} className="gap-2">
              <ArrowLeft className="h-4 w-4" />
              Voltar
            </a>
          </Button>
        }
      />

      {isEdit && stats && (
        <section className="grid gap-3 sm:grid-cols-3">
          <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p className="text-xs text-zinc-500">Pedidos</p>
            <p className="mt-1 text-2xl font-semibold text-zinc-800">{stats.total_orders}</p>
          </div>
          <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p className="text-xs text-zinc-500">Gasto total</p>
            <p className="mt-1 text-2xl font-semibold text-zinc-800">{formatCurrency(stats.total_spent)}</p>
          </div>
          <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p className="text-xs text-zinc-500">Ticket médio</p>
            <p className="mt-1 text-2xl font-semibold text-zinc-800">{formatCurrency(stats.avg_ticket)}</p>
          </div>
        </section>
      )}

      <div className="grid gap-5 lg:grid-cols-[1fr_360px]">
        <form action={urls.submit} method="POST" onSubmit={handleSubmit} className="space-y-5">
          <input type="hidden" name="csrf_token" value={getCsrfToken()} />

          <FormSection title="Dados do cliente" description="Nome e WhatsApp são obrigatórios.">
            <FormField label="Nome" htmlFor="cu-name" required error={errors.name}>
              <Input
                id="cu-name"
                name="name"
                value={name}
                onChange={(e) => setName(e.target.value)}
                maxLength={150}
                placeholder="Nome completo do cliente"
                autoFocus
              />
            </FormField>

            <div className="grid gap-4 md:grid-cols-2">
              <FormField label="WhatsApp" htmlFor="cu-whats" required error={errors.whatsapp}>
                <Input
                  id="cu-whats"
                  name="whatsapp"
                  value={whatsapp}
                  onChange={(e) => setWhatsapp(maskPhone(e.target.value))}
                  inputMode="tel"
                  placeholder="(11) 98888-7777"
                  maxLength={20}
                />
              </FormField>

              <FormField label="E-mail" htmlFor="cu-email" error={errors.email}>
                <Input
                  id="cu-email"
                  name="email"
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="cliente@exemplo.com"
                  maxLength={150}
                />
              </FormField>
            </div>

            <div className="grid gap-4 md:grid-cols-2">
              <FormField label="CPF" htmlFor="cu-cpf" error={errors.cpf} hint="Opcional. Formato: 000.000.000-00">
                <Input
                  id="cu-cpf"
                  name="cpf"
                  value={cpf}
                  onChange={(e) => setCpf(maskCpf(e.target.value))}
                  placeholder="000.000.000-00"
                  maxLength={14}
                />
              </FormField>

              <FormField label="Data de nascimento" htmlFor="cu-birth">
                <Input
                  id="cu-birth"
                  name="birth_date"
                  type="date"
                  value={birthDate}
                  onChange={(e) => setBirthDate(e.target.value)}
                />
              </FormField>
            </div>
          </FormSection>

          <div className="flex flex-wrap items-center justify-between gap-2">
            <div className="flex flex-wrap items-center gap-2">
              <Button type="submit" className="gap-2">
                <Save className="h-4 w-4" />
                {isEdit ? 'Salvar alterações' : 'Criar cliente'}
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
                Remover cliente
              </Button>
            )}
          </div>
        </form>

        {isEdit && (
          <div className="space-y-5">
            <section className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
              <div className="mb-3 flex items-center justify-between gap-2">
                <h2 className="text-sm font-semibold text-zinc-800 flex items-center gap-2">
                  <MapPin className="h-4 w-4 text-zinc-500" />
                  Endereços ({addresses.length})
                </h2>
                <Button size="sm" variant="outline" className="h-8 gap-1.5" onClick={openNewAddress}>
                  <Plus className="h-3.5 w-3.5" />
                  Adicionar
                </Button>
              </div>

              {addresses.length === 0 ? (
                <p className="text-sm text-zinc-500 py-3 text-center">Nenhum endereço cadastrado.</p>
              ) : (
                <ul className="space-y-2">
                  {addresses.map((addr) => (
                    <li key={addr.id} className="group rounded-lg border border-zinc-200 px-3 py-2.5">
                      <div className="flex items-start justify-between gap-2">
                        <div className="min-w-0 flex-1">
                          <div className="flex items-center gap-1.5 mb-0.5">
                            <span className="text-sm font-medium text-zinc-800 truncate">
                              {addr.label || 'Endereço'}
                            </span>
                            {addr.is_default && (
                              <Badge className="bg-amber-100 text-amber-800 border border-amber-200 hover:bg-amber-100 gap-0.5 text-[10px] h-4">
                                <Star className="h-2.5 w-2.5" />
                                Padrão
                              </Badge>
                            )}
                          </div>
                          <p className="text-xs text-zinc-600 truncate">
                            {addr.street}, {addr.number}
                            {addr.complement ? ` (${addr.complement})` : ''}
                          </p>
                          {(addr.neighborhood || addr.city) && (
                            <p className="text-xs text-zinc-500 truncate">
                              {[addr.neighborhood, addr.city].filter(Boolean).join(' • ')}
                            </p>
                          )}
                        </div>
                        <div className="flex shrink-0 items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                          <button
                            type="button"
                            onClick={() => openEditAddress(addr)}
                            className="rounded p-1 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800"
                            aria-label="Editar endereço"
                          >
                            <Pencil className="h-3.5 w-3.5" />
                          </button>
                          <button
                            type="button"
                            onClick={() => setAddressToDelete(addr)}
                            className="rounded p-1 text-red-500 hover:bg-red-50 hover:text-red-700"
                            aria-label="Remover endereço"
                          >
                            <Trash2 className="h-3.5 w-3.5" />
                          </button>
                        </div>
                      </div>
                    </li>
                  ))}
                </ul>
              )}
            </section>

            <section className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
              <h2 className="mb-3 text-sm font-semibold text-zinc-800 flex items-center gap-2">
                <Home className="h-4 w-4 text-zinc-500" />
                Pedidos recentes ({recent_orders.length})
              </h2>
              {recent_orders.length === 0 ? (
                <p className="text-sm text-zinc-500 py-3 text-center">Sem pedidos.</p>
              ) : (
                <ul className="space-y-1.5">
                  {recent_orders.map((o) => (
                    <li
                      key={o.id}
                      className="flex items-center justify-between gap-2 rounded-md px-2 py-1.5 hover:bg-zinc-50"
                    >
                      <div className="min-w-0 flex-1">
                        <p className="text-sm font-medium text-zinc-800">#{o.id}</p>
                        <p className="text-[11px] text-zinc-500">{o.created_at}</p>
                      </div>
                      <div className="text-right">
                        <p className="text-sm font-semibold text-zinc-800">{formatCurrency(o.total)}</p>
                        <p className="text-[11px] text-zinc-500 capitalize">{o.status}</p>
                      </div>
                    </li>
                  ))}
                </ul>
              )}
            </section>
          </div>
        )}
      </div>

      {/* Address create/edit modal-ish inline */}
      {showAddressForm && urls.addresses_base && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" onClick={closeAddressForm}>
          <div
            className="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-xl"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="mb-4 flex items-center justify-between gap-2">
              <h3 className="text-lg font-semibold text-zinc-800">
                {editingAddress ? 'Editar endereço' : 'Novo endereço'}
              </h3>
              <button
                type="button"
                onClick={closeAddressForm}
                className="rounded-md p-1 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800"
                aria-label="Fechar"
              >
                ✕
              </button>
            </div>
            <form onSubmit={saveAddress} className="space-y-4">
              <div className="grid gap-3 md:grid-cols-2">
                <FormField label="Etiqueta" htmlFor="ad-label" hint="Ex.: Casa, Trabalho">
                  <Input
                    id="ad-label"
                    value={addressDraft.label}
                    onChange={(e) => setAddressDraft({ ...addressDraft, label: e.target.value })}
                    placeholder="Casa"
                    maxLength={50}
                  />
                </FormField>

                <FormField label="Destinatário" htmlFor="ad-rcpt" hint="Quem vai receber (opcional)">
                  <Input
                    id="ad-rcpt"
                    value={addressDraft.name}
                    onChange={(e) => setAddressDraft({ ...addressDraft, name: e.target.value })}
                    maxLength={150}
                  />
                </FormField>
              </div>

              <div className="grid gap-3 md:grid-cols-2">
                <FormField label="Cidade" htmlFor="ad-city">
                  {cities.length > 0 ? (
                    <select
                      id="ad-city"
                      value={addressDraft.city}
                      onChange={(e) => setAddressDraft({ ...addressDraft, city: e.target.value, neighborhood: '' })}
                      className="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
                    >
                      <option value="">Selecione…</option>
                      {cities.map((c) => (
                        <option key={c.id} value={c.name}>
                          {c.name}
                        </option>
                      ))}
                    </select>
                  ) : (
                    <Input
                      id="ad-city"
                      value={addressDraft.city}
                      onChange={(e) => setAddressDraft({ ...addressDraft, city: e.target.value })}
                      maxLength={120}
                    />
                  )}
                </FormField>

                <FormField label="Bairro" htmlFor="ad-neigh">
                  {availableZones.length > 0 ? (
                    <select
                      id="ad-neigh"
                      value={addressDraft.neighborhood}
                      onChange={(e) => setAddressDraft({ ...addressDraft, neighborhood: e.target.value })}
                      className="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
                    >
                      <option value="">Selecione…</option>
                      {availableZones.map((z) => (
                        <option key={z.id} value={z.neighborhood}>
                          {z.neighborhood}
                        </option>
                      ))}
                    </select>
                  ) : (
                    <Input
                      id="ad-neigh"
                      value={addressDraft.neighborhood}
                      onChange={(e) => setAddressDraft({ ...addressDraft, neighborhood: e.target.value })}
                      maxLength={120}
                    />
                  )}
                </FormField>
              </div>

              <div className="grid gap-3 md:grid-cols-[1fr_120px]">
                <FormField label="Rua" htmlFor="ad-street" required>
                  <Input
                    id="ad-street"
                    value={addressDraft.street}
                    onChange={(e) => setAddressDraft({ ...addressDraft, street: e.target.value })}
                    maxLength={200}
                    required
                  />
                </FormField>

                <FormField label="Número" htmlFor="ad-num" required>
                  <Input
                    id="ad-num"
                    value={addressDraft.number}
                    onChange={(e) => setAddressDraft({ ...addressDraft, number: e.target.value })}
                    maxLength={30}
                    required
                  />
                </FormField>
              </div>

              <FormField label="Complemento" htmlFor="ad-cmp" hint="Apartamento, bloco, etc. (opcional)">
                <Input
                  id="ad-cmp"
                  value={addressDraft.complement}
                  onChange={(e) => setAddressDraft({ ...addressDraft, complement: e.target.value })}
                  maxLength={120}
                />
              </FormField>

              <FormField label="Referência" htmlFor="ad-ref" hint="Ex.: Próximo à padaria (opcional)">
                <Input
                  id="ad-ref"
                  value={addressDraft.reference}
                  onChange={(e) => setAddressDraft({ ...addressDraft, reference: e.target.value })}
                  maxLength={200}
                />
              </FormField>

              <FormField label="Telefone neste endereço" htmlFor="ad-phone" hint="Opcional. Usado quando entregar.">
                <Input
                  id="ad-phone"
                  value={addressDraft.phone}
                  onChange={(e) => setAddressDraft({ ...addressDraft, phone: maskPhone(e.target.value) })}
                  inputMode="tel"
                  maxLength={20}
                />
              </FormField>

              <label className="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  checked={addressDraft.is_default}
                  onChange={(e) => setAddressDraft({ ...addressDraft, is_default: e.target.checked })}
                  className="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-400"
                />
                <span className="text-sm text-zinc-700">Definir como endereço padrão</span>
              </label>

              <div className="flex items-center justify-end gap-2 pt-2">
                <Button type="button" variant="outline" onClick={closeAddressForm} disabled={addressBusy}>
                  Cancelar
                </Button>
                <Button type="submit" disabled={addressBusy} className="gap-2">
                  {addressBusy ? 'Salvando...' : <><CheckCircle2 className="h-4 w-4" /> Salvar endereço</>}
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}

      <ConfirmDialog
        open={confirmDelete}
        onOpenChange={setConfirmDelete}
        title="Remover cliente?"
        description="O cliente será removido. Pedidos antigos são mantidos no histórico."
        confirmLabel="Remover"
        destructive
        onConfirm={handleDelete}
      />

      <ConfirmDialog
        open={!!addressToDelete}
        onOpenChange={(open) => !open && setAddressToDelete(null)}
        title="Remover endereço?"
        description={
          addressToDelete
            ? `O endereço "${addressToDelete.label || `${addressToDelete.street}, ${addressToDelete.number}`}" será removido.`
            : null
        }
        confirmLabel="Remover"
        destructive
        onConfirm={deleteAddress}
      />
    </AdminStorePageShell>
  )
}
