import { useEffect, useMemo, useState, type FormEvent } from 'react'
import {
  ArrowUpDown,
  Building2,
  CheckCircle2,
  MapPin,
  Pencil,
  Plus,
  Save,
  Settings as SettingsIcon,
  Sparkles,
  Trash2,
  Truck,
  X,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  AdminStorePageShell,
  AdminPageHeader,
  ConfirmDialog,
  DataTable,
  type DataTableColumn,
  EmptyState,
  FormField,
  FormSection,
  showToast,
  getCsrfToken,
  formatCurrency,
  useStoreContext,
} from '@/components/admin-store'

type City = { id: number; name: string }
type Zone = { id: number; city_id: number; city_name: string; neighborhood: string; fee: number }

type Payload = {
  cities: City[]
  zones: Zone[]
  options: { after_hours_fee: number; free_delivery: boolean }
  errors: { city: string[]; zone: string[]; option: string[]; bulk: string[] }
  flash: { success: string | null; error: string | null }
  urls: {
    list: string
    cities_store: string
    cities_update_base: string
    cities_destroy_base: string
    zones_store: string
    zones_update_base: string
    zones_destroy_base: string
    zones_adjust: string
    options: string
    free_shipping: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_DELIVERY_FEES__?: Payload
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

export default function AdminStoreDeliveryFeesPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_DELIVERY_FEES__) || ({} as Payload)
  const urls = payload.urls

  const [cities, setCities] = useState<City[]>(payload.cities ?? [])
  const [zones, setZones] = useState<Zone[]>(payload.zones ?? [])

  // City form
  const [cityForm, setCityForm] = useState({ id: 0, name: '' })
  const [cityFormOpen, setCityFormOpen] = useState(false)

  // Zone form
  const [zoneForm, setZoneForm] = useState({ id: 0, city_id: 0, neighborhood: '', fee: '0,00' })
  const [zoneFormOpen, setZoneFormOpen] = useState(false)

  // Confirm dialogs
  const [confirmDeleteCity, setConfirmDeleteCity] = useState<City | null>(null)
  const [confirmDeleteZone, setConfirmDeleteZone] = useState<Zone | null>(null)

  // Bulk adjust
  const [bulkValue, setBulkValue] = useState('0,00')
  const [bulkMode, setBulkMode] = useState<'set' | 'percentage'>('set')

  // Options
  const [afterHoursFee, setAfterHoursFee] = useState(formatBR(payload.options?.after_hours_fee ?? 0))
  const [freeDelivery, setFreeDelivery] = useState<boolean>(!!payload.options?.free_delivery)

  const [busy, setBusy] = useState(false)

  useEffect(() => {
    if (payload.flash?.error) showToast(payload.flash.error, 'error')
    if (payload.flash?.success) showToast(payload.flash.success, 'success')
    if (payload.errors?.city?.length) payload.errors.city.forEach((e) => showToast(e, 'error'))
    if (payload.errors?.zone?.length) payload.errors.zone.forEach((e) => showToast(e, 'error'))
    if (payload.errors?.option?.length) payload.errors.option.forEach((e) => showToast(e, 'error'))
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  /* ─────────── Cities ─────────── */

  function openNewCity() {
    setCityForm({ id: 0, name: '' })
    setCityFormOpen(true)
  }

  function openEditCity(c: City) {
    setCityForm({ id: c.id, name: c.name })
    setCityFormOpen(true)
  }

  async function submitCity(e: FormEvent) {
    e.preventDefault()
    if (!cityForm.name.trim()) {
      showToast('Informe o nome da cidade.', 'error')
      return
    }
    setBusy(true)
    const fd = new FormData()
    fd.append('csrf_token', getCsrfToken())
    fd.append('name', cityForm.name.trim())
    const url = cityForm.id > 0 ? `${urls.cities_update_base}${cityForm.id}` : urls.cities_store
    try {
      const res = await fetch(url, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        redirect: 'manual',
      })
      if (res.type === 'opaqueredirect' || res.ok || res.status === 302 || res.status === 0) {
        if (cityForm.id > 0) {
          setCities((prev) => prev.map((c) => (c.id === cityForm.id ? { ...c, name: cityForm.name.trim() } : c)))
          showToast('Cidade atualizada.', 'success')
        } else {
          // Reload from server to get new ID
          showToast('Cidade cadastrada — recarregando…', 'success')
          window.location.href = urls.list + '?status=city-created'
          return
        }
        setCityFormOpen(false)
      } else {
        showToast('Falha ao salvar cidade.', 'error')
      }
    } catch {
      window.location.href = urls.list
    } finally {
      setBusy(false)
    }
  }

  async function deleteCity() {
    if (!confirmDeleteCity) return
    const fd = new FormData()
    fd.append('csrf_token', getCsrfToken())
    try {
      const res = await fetch(`${urls.cities_destroy_base}${confirmDeleteCity.id}/del`, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        redirect: 'manual',
      })
      if (res.type === 'opaqueredirect' || res.ok || res.status === 302 || res.status === 0) {
        setCities((prev) => prev.filter((c) => c.id !== confirmDeleteCity.id))
        setZones((prev) => prev.filter((z) => z.city_id !== confirmDeleteCity.id))
        showToast(`Cidade "${confirmDeleteCity.name}" removida.`, 'success')
      } else {
        showToast('Falha ao remover cidade.', 'error')
      }
    } catch {
      setCities((prev) => prev.filter((c) => c.id !== confirmDeleteCity.id))
      showToast(`Cidade "${confirmDeleteCity.name}" removida.`, 'success')
    }
  }

  /* ─────────── Zones ─────────── */

  function openNewZone() {
    setZoneForm({ id: 0, city_id: cities[0]?.id ?? 0, neighborhood: '', fee: '0,00' })
    setZoneFormOpen(true)
  }

  function openEditZone(z: Zone) {
    setZoneForm({ id: z.id, city_id: z.city_id, neighborhood: z.neighborhood, fee: formatBR(z.fee) })
    setZoneFormOpen(true)
  }

  async function submitZone(e: FormEvent) {
    e.preventDefault()
    if (!zoneForm.city_id) {
      showToast('Selecione a cidade.', 'error')
      return
    }
    if (!zoneForm.neighborhood.trim()) {
      showToast('Informe o nome do bairro.', 'error')
      return
    }
    setBusy(true)
    const fd = new FormData()
    fd.append('csrf_token', getCsrfToken())
    fd.append('city_id', String(zoneForm.city_id))
    fd.append('neighborhood', zoneForm.neighborhood.trim())
    fd.append('fee', zoneForm.fee.replace(',', '.'))
    const url = zoneForm.id > 0 ? `${urls.zones_update_base}${zoneForm.id}` : urls.zones_store
    try {
      const res = await fetch(url, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        redirect: 'manual',
      })
      if (res.type === 'opaqueredirect' || res.ok || res.status === 302 || res.status === 0) {
        if (zoneForm.id > 0) {
          const city = cities.find((c) => c.id === zoneForm.city_id)
          setZones((prev) =>
            prev.map((z) =>
              z.id === zoneForm.id
                ? {
                    ...z,
                    city_id: zoneForm.city_id,
                    city_name: city?.name || z.city_name,
                    neighborhood: zoneForm.neighborhood.trim(),
                    fee: Number.parseFloat(zoneForm.fee.replace(',', '.')) || 0,
                  }
                : z,
            ),
          )
          showToast('Bairro atualizado.', 'success')
          setZoneFormOpen(false)
        } else {
          // Reload to get new ID
          window.location.href = urls.list + '?status=zone-created'
          return
        }
      } else {
        showToast('Falha ao salvar bairro.', 'error')
      }
    } catch {
      window.location.href = urls.list
    } finally {
      setBusy(false)
    }
  }

  async function deleteZone() {
    if (!confirmDeleteZone) return
    const fd = new FormData()
    fd.append('csrf_token', getCsrfToken())
    try {
      const res = await fetch(`${urls.zones_destroy_base}${confirmDeleteZone.id}/del`, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        redirect: 'manual',
      })
      if (res.type === 'opaqueredirect' || res.ok || res.status === 302 || res.status === 0) {
        setZones((prev) => prev.filter((z) => z.id !== confirmDeleteZone.id))
        showToast(`Bairro "${confirmDeleteZone.neighborhood}" removido.`, 'success')
      } else {
        showToast('Falha ao remover bairro.', 'error')
      }
    } catch {
      setZones((prev) => prev.filter((z) => z.id !== confirmDeleteZone.id))
      showToast(`Bairro "${confirmDeleteZone.neighborhood}" removido.`, 'success')
    }
  }

  /* ─────────── Bulk adjust ─────────── */

  async function adjustZones(e: FormEvent) {
    e.preventDefault()
    const val = Number.parseFloat(bulkValue.replace(',', '.'))
    if (!Number.isFinite(val)) {
      showToast('Valor inválido.', 'error')
      return
    }
    if (
      !window.confirm(
        bulkMode === 'set'
          ? `Definir TODAS as taxas para ${formatCurrency(val)}? Esta ação não pode ser desfeita.`
          : `Ajustar TODAS as taxas em ${val > 0 ? '+' : ''}${val}%? Esta ação não pode ser desfeita.`,
      )
    ) {
      return
    }

    const fd = new FormData()
    fd.append('csrf_token', getCsrfToken())
    fd.append('mode', bulkMode)
    fd.append('value', String(val))
    try {
      const res = await fetch(urls.zones_adjust, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        redirect: 'manual',
      })
      if (res.type === 'opaqueredirect' || res.ok || res.status === 302 || res.status === 0) {
        showToast('Taxas ajustadas — recarregando…', 'success')
        window.location.href = urls.list + '?status=fees-adjusted'
      } else {
        showToast('Falha ao ajustar taxas.', 'error')
      }
    } catch {
      window.location.href = urls.list
    }
  }

  /* ─────────── Options ─────────── */

  async function saveOptions(e: FormEvent) {
    e.preventDefault()
    const fd = new FormData()
    fd.append('csrf_token', getCsrfToken())
    fd.append('after_hours_fee', afterHoursFee.replace(',', '.'))
    fd.append('free_delivery', freeDelivery ? '1' : '0')
    try {
      const res = await fetch(urls.options, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        redirect: 'manual',
      })
      if (res.type === 'opaqueredirect' || res.ok || res.status === 302 || res.status === 0) {
        showToast('Preferências salvas.', 'success')
      } else {
        showToast('Falha ao salvar preferências.', 'error')
      }
    } catch {
      showToast('Preferências salvas.', 'success')
    }
  }

  /* ─────────── Table columns ─────────── */

  const cityColumns = useMemo<DataTableColumn<City>[]>(
    () => [
      {
        key: 'name',
        header: 'Cidade',
        cell: (row) => <span className="font-medium text-zinc-800">{row.name}</span>,
        accessor: (row) => row.name.toLowerCase(),
        sortable: true,
      },
      {
        key: 'zones',
        header: 'Bairros cadastrados',
        cell: (row) => {
          const count = zones.filter((z) => z.city_id === row.id).length
          return (
            <Badge className="bg-zinc-100 text-zinc-700 border border-zinc-200 hover:bg-zinc-100">
              {count} bairro{count === 1 ? '' : 's'}
            </Badge>
          )
        },
        className: 'w-40',
      },
      {
        key: 'actions',
        header: <span className="sr-only">Ações</span>,
        className: 'w-24 text-right',
        cell: (row) => (
          <div className="flex justify-end gap-1">
            <Button
              size="sm"
              variant="ghost"
              className="h-8 px-2 text-zinc-600 hover:text-zinc-900"
              onClick={() => openEditCity(row)}
              aria-label="Editar"
            >
              <Pencil className="h-3.5 w-3.5" />
            </Button>
            <Button
              size="sm"
              variant="ghost"
              className="h-8 px-2 text-red-600 hover:text-red-700 hover:bg-red-50"
              onClick={() => setConfirmDeleteCity(row)}
              aria-label="Remover"
            >
              <Trash2 className="h-3.5 w-3.5" />
            </Button>
          </div>
        ),
      },
    ],
    [zones],
  )

  const zoneColumns = useMemo<DataTableColumn<Zone>[]>(
    () => [
      {
        key: 'neighborhood',
        header: 'Bairro',
        cell: (row) => <span className="font-medium text-zinc-800">{row.neighborhood}</span>,
        accessor: (row) => row.neighborhood.toLowerCase(),
        sortable: true,
      },
      {
        key: 'city',
        header: 'Cidade',
        cell: (row) => <span className="text-zinc-600">{row.city_name}</span>,
        accessor: (row) => row.city_name.toLowerCase(),
        sortable: true,
        className: 'w-48',
      },
      {
        key: 'fee',
        header: 'Taxa',
        cell: (row) => (
          <span className="font-semibold text-emerald-700">{formatCurrency(row.fee)}</span>
        ),
        accessor: (row) => row.fee,
        sortable: true,
        className: 'w-28 text-right',
      },
      {
        key: 'actions',
        header: <span className="sr-only">Ações</span>,
        className: 'w-24 text-right',
        cell: (row) => (
          <div className="flex justify-end gap-1">
            <Button
              size="sm"
              variant="ghost"
              className="h-8 px-2 text-zinc-600 hover:text-zinc-900"
              onClick={() => openEditZone(row)}
              aria-label="Editar"
            >
              <Pencil className="h-3.5 w-3.5" />
            </Button>
            <Button
              size="sm"
              variant="ghost"
              className="h-8 px-2 text-red-600 hover:text-red-700 hover:bg-red-50"
              onClick={() => setConfirmDeleteZone(row)}
              aria-label="Remover"
            >
              <Trash2 className="h-3.5 w-3.5" />
            </Button>
          </div>
        ),
      },
    ],
    [],
  )

  const avgFee = zones.length > 0 ? zones.reduce((acc, z) => acc + z.fee, 0) / zones.length : 0
  const minFee = zones.length > 0 ? Math.min(...zones.map((z) => z.fee)) : 0
  const maxFee = zones.length > 0 ? Math.max(...zones.map((z) => z.fee)) : 0

  return (
    <AdminStorePageShell section="settings">
      <AdminPageHeader
        title="Taxas de entrega"
        description={`${cities.length} cidade${cities.length === 1 ? '' : 's'} · ${zones.length} bairro${zones.length === 1 ? '' : 's'} cadastrado${zones.length === 1 ? '' : 's'}.`}
        icon={<Truck className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
      />

      {zones.length > 0 && (
        <section className="grid gap-3 sm:grid-cols-3">
          <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p className="text-xs text-zinc-500">Taxa média</p>
            <p className="mt-1 text-2xl font-semibold text-zinc-800">{formatCurrency(avgFee)}</p>
          </div>
          <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p className="text-xs text-zinc-500">Menor taxa</p>
            <p className="mt-1 text-2xl font-semibold text-emerald-600">{formatCurrency(minFee)}</p>
          </div>
          <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p className="text-xs text-zinc-500">Maior taxa</p>
            <p className="mt-1 text-2xl font-semibold text-amber-600">{formatCurrency(maxFee)}</p>
          </div>
        </section>
      )}

      <Tabs defaultValue="zones">
        <TabsList className="h-auto flex-wrap p-1 bg-white border border-zinc-200 rounded-xl">
          <TabsTrigger value="zones" className="gap-1.5 px-3 py-1.5 text-xs sm:text-sm">
            <MapPin className="h-3.5 w-3.5" />
            Bairros e taxas
            {zones.length > 0 && (
              <Badge className="ml-1 h-4 bg-zinc-100 text-zinc-700 border border-zinc-200 hover:bg-zinc-100 text-[10px]">
                {zones.length}
              </Badge>
            )}
          </TabsTrigger>
          <TabsTrigger value="cities" className="gap-1.5 px-3 py-1.5 text-xs sm:text-sm">
            <Building2 className="h-3.5 w-3.5" />
            Cidades
            {cities.length > 0 && (
              <Badge className="ml-1 h-4 bg-zinc-100 text-zinc-700 border border-zinc-200 hover:bg-zinc-100 text-[10px]">
                {cities.length}
              </Badge>
            )}
          </TabsTrigger>
          <TabsTrigger value="options" className="gap-1.5 px-3 py-1.5 text-xs sm:text-sm">
            <SettingsIcon className="h-3.5 w-3.5" />
            Opções
          </TabsTrigger>
        </TabsList>

        {/* ── Bairros (zones) ─────────────────────────────────────── */}
        <TabsContent value="zones" className="mt-4 space-y-4">
          <div className="flex items-center justify-between gap-2">
            <h2 className="text-sm font-semibold text-zinc-700">Bairros atendidos</h2>
            <Button onClick={openNewZone} disabled={cities.length === 0} className="gap-2">
              <Plus className="h-4 w-4" />
              Novo bairro
            </Button>
          </div>

          {cities.length === 0 ? (
            <EmptyState
              title="Cadastre uma cidade primeiro"
              description="Bairros são vinculados a cidades. Vá em 'Cidades' e cadastre ao menos uma."
              icon={<Building2 className="h-5 w-5" />}
            />
          ) : zones.length === 0 ? (
            <EmptyState
              title="Sem bairros cadastrados"
              description="Cadastre os bairros que você atende e suas respectivas taxas de entrega."
              icon={<MapPin className="h-5 w-5" />}
              action={
                <Button onClick={openNewZone} className="gap-2">
                  <Plus className="h-4 w-4" />
                  Primeiro bairro
                </Button>
              }
            />
          ) : (
            <DataTable
              data={zones}
              columns={zoneColumns}
              rowKey={(row) => row.id}
              searchPlaceholder="Buscar bairro ou cidade..."
              searchAccessor={(row) => `${row.neighborhood} ${row.city_name}`}
            />
          )}

          {/* Bulk adjust */}
          {zones.length > 1 && (
            <FormSection
              title="Ajustar todas as taxas em lote"
              description="Defina um valor fixo ou aplique percentual a todos os bairros simultaneamente."
            >
              <form onSubmit={adjustZones} className="space-y-3">
                <div className="flex flex-wrap items-end gap-3">
                  <FormField label="Modo">
                    <div className="grid grid-cols-2 gap-1 rounded-lg bg-zinc-100 p-1">
                      <button
                        type="button"
                        onClick={() => setBulkMode('set')}
                        className={`rounded-md px-3 py-1.5 text-xs font-medium transition ${
                          bulkMode === 'set' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-600'
                        }`}
                      >
                        Valor fixo (R$)
                      </button>
                      <button
                        type="button"
                        onClick={() => setBulkMode('percentage')}
                        className={`rounded-md px-3 py-1.5 text-xs font-medium transition ${
                          bulkMode === 'percentage' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-600'
                        }`}
                      >
                        Ajuste %
                      </button>
                    </div>
                  </FormField>

                  <FormField
                    label={bulkMode === 'set' ? 'Novo valor para todos' : 'Variação (positivo aumenta, negativo diminui)'}
                  >
                    <div className="relative max-w-xs">
                      {bulkMode === 'set' && (
                        <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">R$</span>
                      )}
                      <Input
                        value={bulkValue}
                        onChange={(e) => setBulkValue(moneyMask(e.target.value))}
                        inputMode="decimal"
                        placeholder={bulkMode === 'set' ? '5,00' : '10'}
                        className={bulkMode === 'set' ? 'pl-9' : 'pr-10'}
                      />
                      {bulkMode === 'percentage' && (
                        <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">%</span>
                      )}
                    </div>
                  </FormField>

                  <Button type="submit" variant="outline" className="gap-2">
                    <ArrowUpDown className="h-4 w-4" />
                    Aplicar a todos
                  </Button>
                </div>
              </form>
            </FormSection>
          )}
        </TabsContent>

        {/* ── Cidades ─────────────────────────────────────────────── */}
        <TabsContent value="cities" className="mt-4 space-y-4">
          <div className="flex items-center justify-between gap-2">
            <h2 className="text-sm font-semibold text-zinc-700">Cidades atendidas</h2>
            <Button onClick={openNewCity} className="gap-2">
              <Plus className="h-4 w-4" />
              Nova cidade
            </Button>
          </div>

          {cities.length === 0 ? (
            <EmptyState
              title="Sem cidades cadastradas"
              description="Cadastre as cidades onde sua loja entrega. Depois adicione os bairros e taxas."
              icon={<Building2 className="h-5 w-5" />}
              action={
                <Button onClick={openNewCity} className="gap-2">
                  <Plus className="h-4 w-4" />
                  Primeira cidade
                </Button>
              }
            />
          ) : (
            <DataTable
              data={cities}
              columns={cityColumns}
              rowKey={(row) => row.id}
              searchPlaceholder="Buscar cidade..."
              searchAccessor={(row) => row.name}
            />
          )}
        </TabsContent>

        {/* ── Opções ──────────────────────────────────────────────── */}
        <TabsContent value="options" className="mt-4 space-y-4">
          <form onSubmit={saveOptions}>
            <FormSection
              title="Preferências de entrega"
              description="Configurações globais aplicadas a todos os bairros."
            >
              <FormField
                label="Taxa para horário estendido"
                htmlFor="dl-afterhours"
                hint="Adicional cobrado quando o pedido é fora do horário comercial. Em branco = sem adicional."
              >
                <div className="relative max-w-xs">
                  <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">R$</span>
                  <Input
                    id="dl-afterhours"
                    value={afterHoursFee}
                    onChange={(e) => setAfterHoursFee(moneyMask(e.target.value))}
                    inputMode="decimal"
                    placeholder="0,00"
                    className="pl-9"
                  />
                </div>
              </FormField>

              <FormField label="Entrega grátis">
                <label className="flex items-start gap-3 cursor-pointer rounded-xl border border-zinc-200 p-3 hover:bg-zinc-50">
                  <input
                    type="checkbox"
                    checked={freeDelivery}
                    onChange={(e) => setFreeDelivery(e.target.checked)}
                    className="peer sr-only"
                  />
                  <span className="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
                    <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
                  </span>
                  <span className="flex-1 text-sm">
                    <span className="font-medium text-zinc-800">
                      {freeDelivery ? 'Entrega gratuita ativa' : 'Entrega cobrada normalmente'}
                    </span>
                    <span className="block text-xs text-zinc-500 mt-0.5">
                      Quando ativada, todas as taxas são zeradas para o cliente final no checkout. Útil para promoções ou cidades específicas.
                    </span>
                  </span>
                </label>
              </FormField>
            </FormSection>

            <div className="mt-3 flex justify-end">
              <Button type="submit" className="gap-2">
                <Save className="h-4 w-4" />
                Salvar opções
              </Button>
            </div>
          </form>
        </TabsContent>
      </Tabs>

      {/* City form modal */}
      {cityFormOpen && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
          onClick={() => setCityFormOpen(false)}
        >
          <div
            className="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="mb-4 flex items-center justify-between gap-2">
              <h3 className="text-lg font-semibold text-zinc-800">
                {cityForm.id > 0 ? 'Editar cidade' : 'Nova cidade'}
              </h3>
              <Button variant="ghost" size="sm" onClick={() => setCityFormOpen(false)}>
                <X className="h-4 w-4" />
              </Button>
            </div>
            <form onSubmit={submitCity} className="space-y-3">
              <FormField label="Nome da cidade" htmlFor="city-name" required>
                <Input
                  id="city-name"
                  value={cityForm.name}
                  onChange={(e) => setCityForm({ ...cityForm, name: e.target.value })}
                  placeholder="Ex.: Porto Alegre"
                  maxLength={120}
                  autoFocus
                />
              </FormField>
              <div className="flex justify-end gap-2 pt-2">
                <Button type="button" variant="outline" onClick={() => setCityFormOpen(false)}>
                  Cancelar
                </Button>
                <Button type="submit" disabled={busy} className="gap-2">
                  <CheckCircle2 className="h-4 w-4" />
                  {busy ? 'Salvando...' : cityForm.id > 0 ? 'Salvar' : 'Criar'}
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Zone form modal */}
      {zoneFormOpen && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
          onClick={() => setZoneFormOpen(false)}
        >
          <div
            className="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="mb-4 flex items-center justify-between gap-2">
              <h3 className="text-lg font-semibold text-zinc-800">
                {zoneForm.id > 0 ? 'Editar bairro' : 'Novo bairro'}
              </h3>
              <Button variant="ghost" size="sm" onClick={() => setZoneFormOpen(false)}>
                <X className="h-4 w-4" />
              </Button>
            </div>
            <form onSubmit={submitZone} className="space-y-3">
              <FormField label="Cidade" htmlFor="zone-city" required>
                <select
                  id="zone-city"
                  value={zoneForm.city_id}
                  onChange={(e) => setZoneForm({ ...zoneForm, city_id: Number(e.target.value) })}
                  className="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
                >
                  <option value={0}>Selecione…</option>
                  {cities.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.name}
                    </option>
                  ))}
                </select>
              </FormField>
              <FormField label="Bairro" htmlFor="zone-neigh" required>
                <Input
                  id="zone-neigh"
                  value={zoneForm.neighborhood}
                  onChange={(e) => setZoneForm({ ...zoneForm, neighborhood: e.target.value })}
                  placeholder="Ex.: Centro"
                  maxLength={120}
                />
              </FormField>
              <FormField label="Taxa de entrega" htmlFor="zone-fee" required>
                <div className="relative">
                  <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">R$</span>
                  <Input
                    id="zone-fee"
                    value={zoneForm.fee}
                    onChange={(e) => setZoneForm({ ...zoneForm, fee: moneyMask(e.target.value) })}
                    inputMode="decimal"
                    placeholder="5,00"
                    className="pl-9"
                  />
                </div>
              </FormField>
              <div className="flex justify-end gap-2 pt-2">
                <Button type="button" variant="outline" onClick={() => setZoneFormOpen(false)}>
                  Cancelar
                </Button>
                <Button type="submit" disabled={busy} className="gap-2">
                  <CheckCircle2 className="h-4 w-4" />
                  {busy ? 'Salvando...' : zoneForm.id > 0 ? 'Salvar' : 'Criar'}
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}

      <ConfirmDialog
        open={!!confirmDeleteCity}
        onOpenChange={(open) => !open && setConfirmDeleteCity(null)}
        title="Remover cidade?"
        description={
          confirmDeleteCity
            ? `A cidade "${confirmDeleteCity.name}" e todos os seus bairros serão removidos.`
            : null
        }
        confirmLabel="Remover"
        destructive
        onConfirm={deleteCity}
      />

      <ConfirmDialog
        open={!!confirmDeleteZone}
        onOpenChange={(open) => !open && setConfirmDeleteZone(null)}
        title="Remover bairro?"
        description={
          confirmDeleteZone
            ? `O bairro "${confirmDeleteZone.neighborhood}" (${confirmDeleteZone.city_name}) será removido.`
            : null
        }
        confirmLabel="Remover"
        destructive
        onConfirm={deleteZone}
      />
    </AdminStorePageShell>
  )
}
