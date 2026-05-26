import { useEffect, useRef, useState, type FormEvent } from 'react'
import {
  Activity,
  CalendarClock,
  Clock,
  ImageOff,
  Palette,
  Save,
  Settings as SettingsIcon,
  Sparkles,
  Store,
  Zap,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  AdminStorePageShell,
  AdminPageHeader,
  FormField,
  FormSection,
  showToast,
  getCsrfToken,
  useStoreContext,
} from '@/components/admin-store'

type DayKey = 'monday' | 'tuesday' | 'wednesday' | 'thursday' | 'friday' | 'saturday' | 'sunday'

type HoursRow = {
  weekday: number
  is_open: boolean
  open1: string | null
  close1: string | null
  open2: string | null
  close2: string | null
}

type SettingsPayload = {
  company: {
    id: number
    name: string
    whatsapp: string
    address: string
    min_order: number | string | null
    avg_delivery_min_from: number | string | null
    avg_delivery_min_to: number | string | null
    logo: string
    banner: string
    evolution_server_url: string
    evolution_api_key: string
    ga_measurement_id: string
  }
  colors: {
    menu_header_text_color: string
    menu_header_button_color: string
    menu_header_bg_color: string
    menu_logo_border_color: string
    menu_group_title_bg_color: string
    menu_group_title_text_color: string
    menu_welcome_bg_color: string
    menu_welcome_text_color: string
  }
  hours: Record<number, HoursRow>
  daily_highlight_texts: Record<DayKey, string>
  enabled_days: DayKey[]
  flash: { error: string | null; success: string | null }
  urls: {
    submit: string
    dashboard: string
    menu: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_SETTINGS__?: SettingsPayload
  }
}

const WEEKDAYS: Array<{ id: number; label: string; short: string }> = [
  { id: 1, label: 'Segunda-feira', short: 'Seg' },
  { id: 2, label: 'Terça-feira', short: 'Ter' },
  { id: 3, label: 'Quarta-feira', short: 'Qua' },
  { id: 4, label: 'Quinta-feira', short: 'Qui' },
  { id: 5, label: 'Sexta-feira', short: 'Sex' },
  { id: 6, label: 'Sábado', short: 'Sáb' },
  { id: 7, label: 'Domingo', short: 'Dom' },
]

const DAY_KEYS: DayKey[] = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']
const DAY_LABELS: Record<DayKey, string> = {
  monday: 'Segunda-feira',
  tuesday: 'Terça-feira',
  wednesday: 'Quarta-feira',
  thursday: 'Quinta-feira',
  friday: 'Sexta-feira',
  saturday: 'Sábado',
  sunday: 'Domingo',
}

const COLOR_FIELDS: Array<{ key: keyof SettingsPayload['colors']; label: string; description: string }> = [
  { key: 'menu_welcome_bg_color', label: 'Fundo do topo do cardápio', description: 'Cor de fundo do banner superior.' },
  { key: 'menu_welcome_text_color', label: 'Texto do topo', description: 'Cor do texto sobre o banner superior.' },
  { key: 'menu_header_bg_color', label: 'Fundo do cabeçalho fixo', description: 'Barra fixa no topo durante o scroll.' },
  { key: 'menu_header_text_color', label: 'Texto do cabeçalho fixo', description: 'Cor dos textos no header fixo.' },
  { key: 'menu_header_button_color', label: 'Cor de destaque dos botões', description: 'Botões de ação e CTA.' },
  { key: 'menu_logo_border_color', label: 'Borda do logo', description: 'Anel ao redor do logo da loja.' },
  { key: 'menu_group_title_bg_color', label: 'Fundo dos títulos de categoria', description: 'Cabeçalho das seções de categoria.' },
  { key: 'menu_group_title_text_color', label: 'Texto dos títulos de categoria', description: 'Texto sobre o cabeçalho da categoria.' },
]

function moneyMask(raw: string): string {
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

function timeOrEmpty(value: string | null | undefined): string {
  if (!value) return ''
  // Server returns either HH:MM:SS or HH:MM
  return value.length >= 5 ? value.slice(0, 5) : value
}

function resolveImage(path: string): string {
  if (!path) return ''
  if (/^(https?:)?\/\//i.test(path) || path.startsWith('data:')) return path
  return path.startsWith('/') ? path : `/${path}`
}

export default function AdminStoreSettingsPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_SETTINGS__) || ({} as SettingsPayload)

  const initialMin =
    payload.company?.min_order != null && payload.company.min_order !== ''
      ? String(payload.company.min_order).replace('.', ',')
      : ''
  const initialAvgFrom =
    payload.company?.avg_delivery_min_from != null && payload.company.avg_delivery_min_from !== ''
      ? String(payload.company.avg_delivery_min_from)
      : ''
  const initialAvgTo =
    payload.company?.avg_delivery_min_to != null && payload.company.avg_delivery_min_to !== ''
      ? String(payload.company.avg_delivery_min_to)
      : ''

  // Company core fields
  const [name, setName] = useState(payload.company?.name ?? '')
  const [whatsapp, setWhatsapp] = useState(maskPhone(payload.company?.whatsapp ?? ''))
  const [address, setAddress] = useState(payload.company?.address ?? '')
  const [minOrder, setMinOrder] = useState(initialMin)
  const [avgFrom, setAvgFrom] = useState(initialAvgFrom)
  const [avgTo, setAvgTo] = useState(initialAvgTo)

  // Images
  const [logoFile, setLogoFile] = useState<File | null>(null)
  const [logoPreview, setLogoPreview] = useState(resolveImage(payload.company?.logo ?? ''))
  const logoRef = useRef<HTMLInputElement>(null)

  const [bannerFile, setBannerFile] = useState<File | null>(null)
  const [bannerPreview, setBannerPreview] = useState(resolveImage(payload.company?.banner ?? ''))
  const bannerRef = useRef<HTMLInputElement>(null)

  // Colors
  const [colors, setColors] = useState(payload.colors)

  // Hours
  const [hours, setHours] = useState<Record<number, HoursRow>>(() => {
    const next: Record<number, HoursRow> = {}
    for (const d of WEEKDAYS) {
      const row = payload.hours?.[d.id]
      next[d.id] = {
        weekday: d.id,
        is_open: !!row?.is_open,
        open1: timeOrEmpty(row?.open1 ?? null),
        close1: timeOrEmpty(row?.close1 ?? null),
        open2: timeOrEmpty(row?.open2 ?? null),
        close2: timeOrEmpty(row?.close2 ?? null),
      }
    }
    return next
  })

  // Daily highlight texts + enabled days
  const [dailyTexts, setDailyTexts] = useState<Record<DayKey, string>>(() => {
    const base: Record<DayKey, string> = {
      monday: '', tuesday: '', wednesday: '', thursday: '', friday: '', saturday: '', sunday: '',
    }
    return { ...base, ...(payload.daily_highlight_texts ?? {}) }
  })
  const [enabledDays, setEnabledDays] = useState<Set<DayKey>>(() => new Set(payload.enabled_days ?? []))

  // Integrations
  const [evolutionUrl, setEvolutionUrl] = useState(payload.company?.evolution_server_url ?? '')
  const [evolutionKey, setEvolutionKey] = useState(payload.company?.evolution_api_key ?? '')
  const [gaId, setGaId] = useState(payload.company?.ga_measurement_id ?? '')

  const [errors, setErrors] = useState<Record<string, string>>({})

  useEffect(() => {
    if (payload.flash?.error) showToast(payload.flash.error, 'error')
    if (payload.flash?.success) showToast(payload.flash.success, 'success')
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  function handleImageChange(
    e: React.ChangeEvent<HTMLInputElement>,
    setFile: (f: File | null) => void,
    setPreview: (p: string) => void,
  ) {
    const file = e.target.files?.[0]
    if (!file) {
      setFile(null)
      return
    }
    setFile(file)
    const reader = new FileReader()
    reader.onload = () => setPreview(String(reader.result || ''))
    reader.readAsDataURL(file)
  }

  function updateHours(day: number, patch: Partial<HoursRow>) {
    setHours((prev) => ({ ...prev, [day]: { ...prev[day], ...patch } }))
  }

  function toggleEnabledDay(day: DayKey, on: boolean) {
    setEnabledDays((prev) => {
      const next = new Set(prev)
      if (on) next.add(day)
      else next.delete(day)
      return next
    })
  }

  function validate(): boolean {
    const next: Record<string, string> = {}
    if (!name.trim()) next.name = 'O nome da loja é obrigatório.'

    if (gaId && !/^G-[A-Z0-9]{6,12}$/i.test(gaId.trim())) {
      next.ga_measurement_id = 'Formato esperado: G-XXXXXXX'
    }

    if (avgFrom && avgTo) {
      const from = Number.parseInt(avgFrom, 10)
      const to = Number.parseInt(avgTo, 10)
      if (Number.isFinite(from) && Number.isFinite(to) && from > to) {
        next.avg_delivery_min_to = 'O tempo máximo deve ser ≥ tempo mínimo.'
      }
    }

    setErrors(next)
    return Object.keys(next).length === 0
  }

  function handleSubmit(e: FormEvent) {
    if (!validate()) {
      e.preventDefault()
      showToast('Existem campos inválidos. Verifique os destaques em vermelho.', 'error')
    }
  }

  return (
    <AdminStorePageShell section="settings">
      <AdminPageHeader
        title="Configurações"
        description="Gerencie informações da loja, horários, aparência do cardápio e integrações."
        icon={<SettingsIcon className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
      />

      <form
        action={payload.urls?.submit}
        method="POST"
        encType="multipart/form-data"
        onSubmit={handleSubmit}
        className="space-y-5"
      >
        <input type="hidden" name="csrf_token" value={getCsrfToken()} />

        <Tabs defaultValue="dados">
          <TabsList className="h-auto flex-wrap p-1 bg-white border border-zinc-200 rounded-xl">
            <TabsTrigger value="dados" className="gap-1.5 px-3 py-1.5 text-xs sm:text-sm">
              <Store className="h-3.5 w-3.5" />
              Dados da loja
            </TabsTrigger>
            <TabsTrigger value="horarios" className="gap-1.5 px-3 py-1.5 text-xs sm:text-sm">
              <Clock className="h-3.5 w-3.5" />
              Horários
            </TabsTrigger>
            <TabsTrigger value="aparencia" className="gap-1.5 px-3 py-1.5 text-xs sm:text-sm">
              <Palette className="h-3.5 w-3.5" />
              Aparência
            </TabsTrigger>
            <TabsTrigger value="destaques" className="gap-1.5 px-3 py-1.5 text-xs sm:text-sm">
              <Sparkles className="h-3.5 w-3.5" />
              Destaques diários
            </TabsTrigger>
            <TabsTrigger value="integracoes" className="gap-1.5 px-3 py-1.5 text-xs sm:text-sm">
              <Zap className="h-3.5 w-3.5" />
              Integrações
            </TabsTrigger>
          </TabsList>

          {/* ── Dados da loja ───────────────────────────────────────────── */}
          <TabsContent value="dados" className="mt-4 space-y-5">
            <FormSection title="Informações básicas">
              <FormField label="Nome da loja" htmlFor="st-name" required error={errors.name}>
                <Input
                  id="st-name"
                  name="name"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  maxLength={150}
                  placeholder="Ex.: Wollburger Lanches"
                />
              </FormField>

              <div className="grid gap-4 md:grid-cols-2">
                <FormField label="WhatsApp" htmlFor="st-wp" hint="Número exibido aos clientes.">
                  <Input
                    id="st-wp"
                    name="whatsapp"
                    value={whatsapp}
                    onChange={(e) => setWhatsapp(maskPhone(e.target.value))}
                    inputMode="tel"
                    placeholder="(11) 98888-7777"
                  />
                </FormField>

                <FormField label="Endereço" htmlFor="st-addr">
                  <Input
                    id="st-addr"
                    name="address"
                    value={address}
                    onChange={(e) => setAddress(e.target.value)}
                    maxLength={200}
                    placeholder="Rua, número - Bairro - Cidade/UF"
                  />
                </FormField>
              </div>
            </FormSection>

            <FormSection title="Pedido e entrega" description="Valor mínimo e tempo médio exibidos no cardápio.">
              <div className="grid gap-4 md:grid-cols-3">
                <FormField label="Pedido mínimo" htmlFor="st-min" hint="Em branco = sem mínimo">
                  <div className="relative">
                    <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">R$</span>
                    <Input
                      id="st-min"
                      name="min_order"
                      value={minOrder}
                      onChange={(e) => setMinOrder(moneyMask(e.target.value))}
                      inputMode="decimal"
                      placeholder="0,00"
                      className="pl-9"
                    />
                  </div>
                </FormField>

                <FormField label="Tempo médio (mín)" htmlFor="st-from">
                  <div className="relative">
                    <Input
                      id="st-from"
                      name="avg_delivery_min_from"
                      type="number"
                      value={avgFrom}
                      onChange={(e) => setAvgFrom(e.target.value.replace(/\D/g, ''))}
                      min={0}
                      max={999}
                      placeholder="30"
                      className="pr-10"
                    />
                    <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs text-zinc-500">min</span>
                  </div>
                </FormField>

                <FormField label="Tempo médio (máx)" htmlFor="st-to" error={errors.avg_delivery_min_to}>
                  <div className="relative">
                    <Input
                      id="st-to"
                      name="avg_delivery_min_to"
                      type="number"
                      value={avgTo}
                      onChange={(e) => setAvgTo(e.target.value.replace(/\D/g, ''))}
                      min={0}
                      max={999}
                      placeholder="45"
                      className="pr-10"
                    />
                    <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs text-zinc-500">min</span>
                  </div>
                </FormField>
              </div>
            </FormSection>

            <FormSection title="Logo e banner" description="JPG, PNG ou WEBP. A imagem é aplicada imediatamente após salvar.">
              <div className="grid gap-5 md:grid-cols-2">
                {/* Logo */}
                <div>
                  <Label className="text-sm font-medium text-zinc-700 mb-2 block">Logo</Label>
                  <div className="flex items-start gap-3">
                    <div className="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50">
                      {logoPreview ? (
                        <img src={logoPreview} alt="Logo" className="h-full w-full object-cover" />
                      ) : (
                        <ImageOff className="h-6 w-6 text-zinc-400" />
                      )}
                    </div>
                    <div className="flex-1 space-y-2">
                      <input
                        ref={logoRef}
                        type="file"
                        name="logo"
                        accept="image/jpeg,image/png,image/webp"
                        onChange={(e) => handleImageChange(e, setLogoFile, setLogoPreview)}
                        className="block w-full text-sm text-zinc-700 file:mr-3 file:rounded-md file:border-0 file:bg-zinc-900 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white hover:file:bg-zinc-800"
                      />
                      {logoFile && (
                        <p className="text-xs text-zinc-500">
                          Selecionado: <span className="font-medium text-zinc-700">{logoFile.name}</span>
                        </p>
                      )}
                    </div>
                  </div>
                </div>

                {/* Banner */}
                <div>
                  <Label className="text-sm font-medium text-zinc-700 mb-2 block">Banner</Label>
                  <div className="flex items-start gap-3">
                    <div className="flex h-24 w-32 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50">
                      {bannerPreview ? (
                        <img src={bannerPreview} alt="Banner" className="h-full w-full object-cover" />
                      ) : (
                        <ImageOff className="h-6 w-6 text-zinc-400" />
                      )}
                    </div>
                    <div className="flex-1 space-y-2">
                      <input
                        ref={bannerRef}
                        type="file"
                        name="banner"
                        accept="image/jpeg,image/png,image/webp"
                        onChange={(e) => handleImageChange(e, setBannerFile, setBannerPreview)}
                        className="block w-full text-sm text-zinc-700 file:mr-3 file:rounded-md file:border-0 file:bg-zinc-900 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white hover:file:bg-zinc-800"
                      />
                      {bannerFile && (
                        <p className="text-xs text-zinc-500">
                          Selecionado: <span className="font-medium text-zinc-700">{bannerFile.name}</span>
                        </p>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            </FormSection>
          </TabsContent>

          {/* ── Horários ────────────────────────────────────────────────── */}
          <TabsContent value="horarios" className="mt-4 space-y-5">
            <FormSection
              title="Horário de funcionamento"
              description="Ative os dias e configure até 2 turnos por dia. Turno 2 é opcional."
            >
              <div className="space-y-2">
                {WEEKDAYS.map((day) => {
                  const row = hours[day.id]
                  return (
                    <div
                      key={day.id}
                      className="rounded-xl border border-zinc-200 bg-white p-3 transition-colors"
                    >
                      <div className="flex flex-wrap items-center gap-3">
                        <label className="flex items-center gap-2 min-w-[160px] cursor-pointer">
                          <input
                            type="checkbox"
                            name={`is_open[${day.id}]`}
                            value="1"
                            checked={row.is_open}
                            onChange={(e) => updateHours(day.id, { is_open: e.target.checked })}
                            className="peer sr-only"
                          />
                          <span className="relative inline-flex h-5 w-9 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
                            <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
                          </span>
                          <span className="text-sm font-medium text-zinc-800">{day.label}</span>
                        </label>

                        <div className="flex flex-wrap items-center gap-2 ml-auto">
                          <span className="text-xs text-zinc-500">Turno 1:</span>
                          <Input
                            type="time"
                            name={`open1[${day.id}]`}
                            value={row.open1 ?? ''}
                            onChange={(e) => updateHours(day.id, { open1: e.target.value })}
                            disabled={!row.is_open}
                            className="h-8 w-28"
                          />
                          <span className="text-xs text-zinc-400">às</span>
                          <Input
                            type="time"
                            name={`close1[${day.id}]`}
                            value={row.close1 ?? ''}
                            onChange={(e) => updateHours(day.id, { close1: e.target.value })}
                            disabled={!row.is_open}
                            className="h-8 w-28"
                          />

                          <span className="text-xs text-zinc-500 ml-3">Turno 2:</span>
                          <Input
                            type="time"
                            name={`open2[${day.id}]`}
                            value={row.open2 ?? ''}
                            onChange={(e) => updateHours(day.id, { open2: e.target.value })}
                            disabled={!row.is_open}
                            className="h-8 w-28"
                          />
                          <span className="text-xs text-zinc-400">às</span>
                          <Input
                            type="time"
                            name={`close2[${day.id}]`}
                            value={row.close2 ?? ''}
                            onChange={(e) => updateHours(day.id, { close2: e.target.value })}
                            disabled={!row.is_open}
                            className="h-8 w-28"
                          />
                        </div>
                      </div>
                    </div>
                  )
                })}
              </div>
            </FormSection>
          </TabsContent>

          {/* ── Aparência (cores) ───────────────────────────────────────── */}
          <TabsContent value="aparencia" className="mt-4 space-y-5">
            <FormSection
              title="Cores do cardápio público"
              description="Personalize cores exibidas no cardápio que os clientes acessam."
            >
              <div className="grid gap-4 md:grid-cols-2">
                {COLOR_FIELDS.map((field) => (
                  <div key={field.key} className="rounded-lg border border-zinc-200 p-3">
                    <div className="flex items-center gap-3">
                      <input
                        type="color"
                        name={field.key}
                        value={colors[field.key] || '#000000'}
                        onChange={(e) =>
                          setColors((prev) => ({ ...prev, [field.key]: e.target.value.toUpperCase() }))
                        }
                        className="h-10 w-12 cursor-pointer rounded border border-zinc-200 bg-white"
                      />
                      <div className="flex-1 min-w-0">
                        <p className="text-sm font-medium text-zinc-800 truncate">{field.label}</p>
                        <p className="text-xs text-zinc-500 truncate">{field.description}</p>
                      </div>
                      <Input
                        type="text"
                        value={colors[field.key] || ''}
                        onChange={(e) =>
                          setColors((prev) => ({ ...prev, [field.key]: e.target.value.toUpperCase() }))
                        }
                        maxLength={7}
                        className="w-24 font-mono text-xs uppercase"
                      />
                    </div>
                  </div>
                ))}
              </div>
            </FormSection>
          </TabsContent>

          {/* ── Destaques diários ───────────────────────────────────────── */}
          <TabsContent value="destaques" className="mt-4 space-y-5">
            <FormSection
              title="Mensagens de destaque por dia"
              description="Exibe uma mensagem destacada no topo do cardápio nos dias habilitados. Útil para promoções recorrentes."
            >
              <div className="space-y-3">
                {DAY_KEYS.map((day) => {
                  const enabled = enabledDays.has(day)
                  return (
                    <div
                      key={day}
                      className="rounded-xl border border-zinc-200 bg-white p-3"
                    >
                      <div className="mb-2 flex items-center justify-between gap-2">
                        <span className="text-sm font-medium text-zinc-800">{DAY_LABELS[day]}</span>
                        <label className="flex items-center gap-2 cursor-pointer">
                          <input
                            type="checkbox"
                            name={`highlight_enabled[${day}]`}
                            value="1"
                            checked={enabled}
                            onChange={(e) => toggleEnabledDay(day, e.target.checked)}
                            className="peer sr-only"
                          />
                          <span className="relative inline-flex h-4 w-8 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
                            <span className="ml-0.5 h-3 w-3 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
                          </span>
                          <span className="text-xs text-zinc-600">{enabled ? 'Exibindo' : 'Oculto'}</span>
                        </label>
                      </div>
                      <Input
                        name={`highlight_text_${day}`}
                        value={dailyTexts[day] ?? ''}
                        onChange={(e) => setDailyTexts((prev) => ({ ...prev, [day]: e.target.value }))}
                        maxLength={200}
                        placeholder={enabled ? 'Ex.: Hoje é dia de combo!' : 'Texto da promoção...'}
                      />
                    </div>
                  )
                })}
              </div>
            </FormSection>
          </TabsContent>

          {/* ── Integrações ─────────────────────────────────────────────── */}
          <TabsContent value="integracoes" className="mt-4 space-y-5">
            <FormSection
              title="Evolution API (WhatsApp)"
              description="Servidor da Evolution API usado para enviar notificações via WhatsApp."
            >
              <FormField label="URL do servidor Evolution" htmlFor="st-evo-url" hint="Ex.: https://evolution.minhaempresa.com">
                <Input
                  id="st-evo-url"
                  name="evolution_server_url"
                  value={evolutionUrl}
                  onChange={(e) => setEvolutionUrl(e.target.value)}
                  type="url"
                  placeholder="https://"
                />
              </FormField>

              <FormField label="Chave de API (Evolution)" htmlFor="st-evo-key">
                <Input
                  id="st-evo-key"
                  name="evolution_api_key"
                  value={evolutionKey}
                  onChange={(e) => setEvolutionKey(e.target.value)}
                  type="password"
                  autoComplete="off"
                  placeholder="API Key"
                />
              </FormField>
            </FormSection>

            <FormSection
              title="Google Analytics 4"
              description="ID de medição do GA4 (formato G-XXXXXXX). Quando preenchido, eventos do cardápio são enviados ao GA4."
            >
              <FormField label="Measurement ID" htmlFor="st-ga" error={errors.ga_measurement_id}>
                <div className="flex items-center gap-2">
                  <Input
                    id="st-ga"
                    name="ga_measurement_id"
                    value={gaId}
                    onChange={(e) => setGaId(e.target.value.toUpperCase())}
                    placeholder="G-XXXXXXX"
                    maxLength={20}
                    className="font-mono"
                  />
                  {gaId && /^G-[A-Z0-9]{6,12}$/i.test(gaId.trim()) && (
                    <Badge className="bg-emerald-100 text-emerald-700 border border-emerald-200 hover:bg-emerald-100">
                      <Activity className="h-3 w-3 mr-1" />
                      Válido
                    </Badge>
                  )}
                </div>
              </FormField>
            </FormSection>
          </TabsContent>
        </Tabs>

        <div className="sticky bottom-0 -mx-5 mt-6 border-t border-zinc-200 bg-white/95 backdrop-blur px-5 py-3">
          <div className="mx-auto flex max-w-[1292px] flex-wrap items-center justify-end gap-2">
            <Button asChild type="button" variant="outline">
              <a href={payload.urls?.dashboard}>Voltar para o painel</a>
            </Button>
            <Button asChild type="button" variant="ghost">
              <a href={payload.urls?.menu} target="_blank" rel="noreferrer" className="gap-2">
                <CalendarClock className="h-4 w-4" />
                Ver cardápio público
              </a>
            </Button>
            <Button type="submit" className="gap-2">
              <Save className="h-4 w-4" />
              Salvar configurações
            </Button>
          </div>
        </div>
      </form>
    </AdminStorePageShell>
  )
}
