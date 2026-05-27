import { useState } from 'react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Separator } from '@/components/ui/separator'
import {
  AlertDialog,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { AdminStorePageShell } from '@/components/admin-store/page-shell'
import { useStoreContext, getCsrfToken } from '@/components/admin-store/use-store-context'
import { showToast } from '@/components/admin-store/toast'
import {
  BarChart3,
  ChevronRight,
  CircleDollarSign,
  ExternalLink,
  ImageOff,
  LogOut,
  Package,
  Pause,
  Play,
  PlusCircle,
  Receipt,
  Settings,
  ShoppingBag,
  ShoppingCart,
  Tag,
  Timer,
  TrendingUp,
  Utensils,
} from 'lucide-react'

type PauseStatus = {
  is_paused: boolean
  pause_type: 'timed' | 'scheduled' | 'indefinite' | null
  pause_until: string | null
  pause_reason: string | null
  remaining_minutes: number | null
  remaining_text: string | null
}

type PauseData = {
  status: PauseStatus
  durations: Array<{ minutes: number; label: string }>
  urls: { status: string; enable: string; disable: string; extend: string }
}

type DashboardPayload = {
  metrics?: {
    categories?: number
    products?: number
    ingredients?: number
    orders?: number
  }
  recent?: {
    categories?: Array<{ id: number; name: string }>
    products?: Array<{ id: number; name: string; price: number; promo_price?: number | null; image?: string }>
    ingredients?: Array<{ id: number; name: string; image_path?: string }>
    orders?: Array<{
      id: number
      order_number?: number
      customer_name: string
      total: number
      status: string
      created_at?: string
    }>
  }
  links?: Record<string, string>
  pause?: PauseData
  theme?: {
    primaryColor?: string
    primaryGradient?: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_CONTEXT__?: {
      slug?: string
      company_name?: string
      company_logo?: string
      company_banner?: string
      min_order?: number | null
      theme?: {
        primaryColor?: string
        primaryGradient?: string
      }
    }
    __ADMIN_STORE_DASHBOARD__?: DashboardPayload
  }
}

function fmt(value: number) {
  return `R$ ${value.toFixed(2).replace('.', ',')}`
}

function OrderStatusBadge({ status }: { status: string }) {
  if (status === 'completed' || status === 'paid')
    return <Badge className="bg-emerald-100 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 font-medium">Concluído</Badge>
  if (status === 'canceled')
    return <Badge className="bg-rose-100 text-rose-700 border border-rose-200 hover:bg-rose-100 font-medium">Cancelado</Badge>
  return <Badge className="bg-amber-100 text-amber-700 border border-amber-200 hover:bg-amber-100 font-medium">Pendente</Badge>
}

// ── Pause block ───────────────────────────────────────────────────────────────

function PauseBlock({ pause }: { pause: PauseData }) {
  const [status, setStatus] = useState<PauseStatus>(pause.status)
  const [modalOpen, setModalOpen] = useState(false)
  const [extendMode, setExtendMode] = useState(false)
  const [selectedMinutes, setSelectedMinutes] = useState(30)
  const [reason, setReason] = useState('')
  const [indefinite, setIndefinite] = useState(false)
  const [loading, setLoading] = useState(false)

  const durations = pause.durations.length
    ? pause.durations
    : [
        { minutes: 15, label: '15 min' },
        { minutes: 30, label: '30 min' },
        { minutes: 60, label: '1 hora' },
        { minutes: 120, label: '2 horas' },
        { minutes: 180, label: '3 horas' },
      ]

  function openPause() {
    setExtendMode(false)
    setIndefinite(false)
    setReason('')
    setSelectedMinutes(30)
    setModalOpen(true)
  }

  function openExtend() {
    setExtendMode(true)
    setSelectedMinutes(30)
    setModalOpen(true)
  }

  async function handleEnable() {
    setLoading(true)
    try {
      const res = await fetch(pause.urls.enable, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-Csrf-Token': getCsrfToken(),
        },
        body: JSON.stringify({
          type: indefinite ? 'indefinite' : 'timed',
          minutes: selectedMinutes,
          reason: reason.trim() || undefined,
        }),
      })
      const j = await res.json().catch(() => null)
      if (j?.success) {
        setStatus(j.data)
        setModalOpen(false)
        showToast('Loja pausada com sucesso.', 'success')
      } else {
        showToast(j?.message ?? 'Erro ao pausar.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setLoading(false)
    }
  }

  async function handleExtend() {
    setLoading(true)
    try {
      const res = await fetch(pause.urls.extend, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-Csrf-Token': getCsrfToken(),
        },
        body: JSON.stringify({ minutes: selectedMinutes }),
      })
      const j = await res.json().catch(() => null)
      if (j?.success) {
        setStatus(j.data)
        setModalOpen(false)
        showToast(`Pausa estendida por mais ${selectedMinutes} min.`, 'success')
      } else {
        showToast(j?.message ?? 'Erro ao estender.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setLoading(false)
    }
  }

  async function handleDisable() {
    setLoading(true)
    try {
      const res = await fetch(pause.urls.disable, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-Csrf-Token': getCsrfToken(),
        },
      })
      const j = await res.json().catch(() => null)
      if (j?.success) {
        setStatus(j.data)
        showToast('Loja retomada com sucesso.', 'success')
      } else {
        showToast(j?.message ?? 'Erro ao retomar.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setLoading(false)
    }
  }

  return (
    <>
      {status.is_paused ? (
        <section className="rounded-2xl border border-amber-300 bg-amber-50 p-5 shadow-sm">
          <div className="flex flex-wrap items-center justify-between gap-4">
            <div className="flex items-center gap-3">
              <div className="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                <Timer className="h-5 w-5" />
              </div>
              <div>
                <div className="flex items-center gap-2">
                  <h3 className="font-semibold text-amber-900">Loja Pausada</h3>
                  <span className="h-2 w-2 rounded-full bg-amber-500 animate-pulse" />
                </div>
                {status.remaining_text ? (
                  <p className="text-sm text-amber-700">Retoma em {status.remaining_text}</p>
                ) : status.pause_type === 'indefinite' ? (
                  <p className="text-sm text-amber-700">Pausa indefinida — retome manualmente</p>
                ) : null}
                {status.pause_reason && (
                  <p className="mt-0.5 text-xs text-amber-600">{status.pause_reason}</p>
                )}
              </div>
            </div>
            <div className="flex flex-wrap gap-2">
              {status.pause_type !== 'indefinite' && (
                <Button
                  type="button"
                  size="sm"
                  variant="outline"
                  className="border-amber-400 text-amber-800 hover:bg-amber-100"
                  onClick={openExtend}
                  disabled={loading}
                >
                  <Timer className="mr-1.5 h-3.5 w-3.5" />
                  Estender
                </Button>
              )}
              <Button
                type="button"
                size="sm"
                className="bg-emerald-600 text-white hover:bg-emerald-700"
                onClick={handleDisable}
                disabled={loading}
              >
                <Play className="mr-1.5 h-3.5 w-3.5" />
                Retomar agora
              </Button>
            </div>
          </div>
        </section>
      ) : (
        <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div className="flex items-center justify-between gap-4">
            <div className="flex items-center gap-3">
              <div className="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                <Pause className="h-5 w-5" />
              </div>
              <div>
                <h3 className="font-semibold text-slate-900">Pausa Programada</h3>
                <p className="text-sm text-slate-600">Pause temporariamente o recebimento de pedidos</p>
              </div>
            </div>
            <Button
              type="button"
              className="bg-amber-600 text-white hover:bg-amber-700"
              onClick={openPause}
            >
              Pausar Loja
            </Button>
          </div>
        </section>
      )}

      {/* Modal */}
      <AlertDialog open={modalOpen} onOpenChange={setModalOpen}>
        <AlertDialogContent className="max-w-sm rounded-2xl p-6">
          <AlertDialogHeader>
            <AlertDialogTitle className="flex items-center gap-2">
              {extendMode ? (
                <><Timer className="h-4 w-4 text-amber-600" /> Estender pausa</>
              ) : (
                <><Pause className="h-4 w-4 text-amber-600" /> Pausar loja</>
              )}
            </AlertDialogTitle>
          </AlertDialogHeader>

          {/* Duration chips */}
          <div className="space-y-3">
            <p className="text-sm font-medium text-slate-700">
              {extendMode ? 'Estender por quanto tempo?' : 'Pausar por quanto tempo?'}
            </p>
            <div className="flex flex-wrap gap-2">
              {durations.map((d) => (
                <button
                  key={d.minutes}
                  type="button"
                  disabled={indefinite}
                  onClick={() => setSelectedMinutes(d.minutes)}
                  className={[
                    'rounded-lg border px-3 py-1.5 text-sm font-medium transition',
                    selectedMinutes === d.minutes && !indefinite
                      ? 'border-amber-500 bg-amber-50 text-amber-800'
                      : 'border-slate-200 text-slate-600 hover:border-slate-300',
                    indefinite ? 'opacity-40 cursor-not-allowed' : '',
                  ].join(' ')}
                >
                  {d.label}
                </button>
              ))}
            </div>

            {!extendMode && (
              <label className="flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                <input
                  type="checkbox"
                  className="h-4 w-4 accent-amber-600"
                  checked={indefinite}
                  onChange={(e) => setIndefinite(e.target.checked)}
                />
                Pausa indefinida (retomar manualmente)
              </label>
            )}

            {!extendMode && (
              <div className="space-y-1">
                <label className="text-xs font-medium text-slate-500">Motivo (opcional)</label>
                <input
                  type="text"
                  value={reason}
                  onChange={(e) => setReason(e.target.value)}
                  placeholder="Ex: Muita demanda, aguarde..."
                  className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200"
                />
              </div>
            )}
          </div>

          <AlertDialogFooter className="mt-2">
            <AlertDialogCancel disabled={loading}>Cancelar</AlertDialogCancel>
            <Button
              type="button"
              className="bg-amber-600 text-white hover:bg-amber-700"
              disabled={loading}
              onClick={extendMode ? handleExtend : handleEnable}
            >
              {loading
                ? 'Salvando…'
                : extendMode
                ? `Estender ${selectedMinutes} min`
                : indefinite
                ? 'Pausar indefinidamente'
                : `Pausar por ${durations.find((d) => d.minutes === selectedMinutes)?.label ?? selectedMinutes + ' min'}`}
            </Button>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </>
  )
}

// ── Main page ─────────────────────────────────────────────────────────────────

export default function AdminStoreDashboardPage() {
  const data = window.__ADMIN_STORE_DASHBOARD__ ?? {}
  const { slug: activeSlug, companyName, companyLogo: logoUrl, companyBanner: bannerUrl, minOrder: minOrderValue, palette } = useStoreContext()

  const metrics = data.metrics ?? {}
  const recent = data.recent ?? {}
  const links = data.links ?? {}
  const minOrder = typeof minOrderValue === 'number' ? fmt(minOrderValue) : null

  const quickActions = [
    {
      label: 'Nova categoria',
      description: 'Organize seu cardápio por grupos.',
      href: `/admin/${activeSlug}/categories/create`,
      icon: Tag,
      iconClassName: 'bg-indigo-50 text-indigo-600 ring-indigo-100',
    },
    {
      label: 'Novo produto',
      description: 'Cadastre simples ou combos.',
      href: `/admin/${activeSlug}/products/create`,
      icon: Package,
      iconClassName: 'bg-emerald-50 text-emerald-600 ring-emerald-100',
    },
    {
      label: 'Novo ingrediente',
      description: 'Vincule aos produtos.',
      href: `/admin/${activeSlug}/ingredients/create`,
      icon: Utensils,
      iconClassName: 'bg-amber-50 text-amber-600 ring-amber-100',
    },
    {
      label: 'Novo pedido',
      description: 'Registre um pedido manualmente.',
      href: `/admin/${activeSlug}/orders/create`,
      icon: PlusCircle,
      iconClassName: 'bg-sky-50 text-sky-600 ring-sky-100',
    },
  ]

  const financeActions = [
    {
      label: 'Dashboard Financeiro',
      description: 'Lucros, vendas e métricas.',
      href: links.financial ?? `/admin/${activeSlug}/financial`,
      icon: TrendingUp,
      iconClassName: 'bg-green-50 text-green-600 ring-green-100',
    },
    {
      label: 'Despesas',
      description: 'Gerencie custos fixos e variáveis.',
      href: `/admin/${activeSlug}/expenses`,
      icon: Receipt,
      iconClassName: 'bg-red-50 text-red-600 ring-red-100',
    },
    {
      label: 'Custos de Produtos',
      description: 'Margens e lucratividade.',
      href: `/admin/${activeSlug}/product-costs`,
      icon: CircleDollarSign,
      iconClassName: 'bg-purple-50 text-purple-600 ring-purple-100',
    },
    {
      label: 'Analytics',
      description: 'Relatórios de vendas.',
      href: links.analytics ?? `/admin/${activeSlug}/analytics`,
      icon: BarChart3,
      iconClassName: 'bg-blue-50 text-blue-600 ring-blue-100',
    },
  ]

  const heroButtons = [
    { label: 'Financeiro', href: links.financial ?? `/admin/${activeSlug}/financial`, icon: CircleDollarSign },
    { label: 'API', href: `/admin/${activeSlug}/api`, icon: BarChart3 },
    { label: 'Abrir KDS', href: `/admin/${activeSlug}/kds`, icon: ExternalLink },
    { label: 'Configurações', href: links.settings ?? `/admin/${activeSlug}/settings`, icon: Settings },
    { label: 'Ver cardápio', href: links.menu ?? `/${activeSlug}`, icon: ExternalLink, target: '_blank' as const },
  ]

  return (
    <AdminStorePageShell section="dashboard">

            <section
              className="relative overflow-hidden rounded-3xl border text-white shadow-sm"
              style={{
                background: palette.primaryGradient,
                backgroundImage: bannerUrl ? `${palette.primaryGradient}, url(${bannerUrl})` : palette.primaryGradient,
                backgroundSize: 'cover',
                backgroundPosition: 'center',
              }}
            >
              <div className="absolute inset-0 bg-black/15" />
              <div className="relative z-10 grid gap-4 p-5 md:grid-cols-[auto_1fr_auto] md:items-center md:p-7">
                <div className="inline-flex h-24 w-24 items-center justify-center rounded-2xl bg-white/10 p-0.5 ring-1 ring-white/30">
                  {logoUrl ? (
                    <img
                      src={logoUrl}
                      alt="Logo da loja"
                      className="h-24 w-24 rounded-[0.9rem] object-cover ring-1 ring-black/10"
                    />
                  ) : (
                    <div className="flex h-24 w-24 items-center justify-center rounded-[0.9rem] bg-white/5 ring-1 ring-black/10">
                      <ShoppingBag className="h-10 w-10 text-white/50" />
                    </div>
                  )}
                </div>

                <div className="text-white">
                  <h1 className="text-2xl font-semibold leading-tight">{companyName}</h1>
                  <p className="mt-0.5 text-sm text-white/85">
                    Categorias: {metrics.categories ?? 0} • Produtos: {metrics.products ?? 0}
                    {minOrder ? ` • Mín.: ${minOrder}` : ''}
                  </p>
                </div>

                <div className="flex flex-wrap gap-2">
                  {heroButtons.map((button) => {
                    const Icon = button.icon
                    return (
                      <a
                        key={button.label}
                        href={button.href}
                        target={button.target}
                        rel={button.target === '_blank' ? 'noreferrer' : undefined}
                        className="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-sm text-white ring-1 ring-white/30 transition hover:bg-white/15"
                      >
                        <Icon className="h-4 w-4" />
                        {button.label}
                      </a>
                    )
                  })}
                  <a
                    href={links.logout ?? `/admin/${activeSlug}/logout`}
                    className="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-sm font-medium text-slate-900 shadow transition hover:opacity-95"
                  >
                    <LogOut className="h-4 w-4" />
                    Sair
                  </a>
                </div>
              </div>
            </section>

            {/* Pausa programada */}
            {data.pause ? (
              <PauseBlock pause={data.pause} />
            ) : (
              <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div className="flex items-center justify-between gap-4">
                  <div className="flex items-center gap-3">
                    <div className="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                      <Pause className="h-5 w-5" />
                    </div>
                    <div>
                      <h3 className="font-semibold text-slate-900">Pausa Programada</h3>
                      <p className="text-sm text-slate-600">Pause temporariamente o recebimento de pedidos</p>
                    </div>
                  </div>
                </div>
              </section>
            )}

            {/* Ações rápidas */}
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
              {quickActions.map((action) => {
                const Icon = action.icon
                return (
                  <a
                    key={action.label}
                    href={action.href}
                    className="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                  >
                    <div className={`mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl ring-1 ${action.iconClassName}`}>
                      <Icon className="h-4 w-4" />
                    </div>
                    <div className="font-semibold text-slate-900">{action.label}</div>
                    <p className="text-sm text-slate-500">{action.description}</p>
                  </a>
                )
              })}
            </div>

            {/* Gestão Financeira */}
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
              {financeActions.map((action) => {
                const Icon = action.icon
                return (
                  <a
                    key={action.label}
                    href={action.href}
                    className="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                  >
                    <div className={`mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl ring-1 ${action.iconClassName}`}>
                      <Icon className="h-4 w-4" />
                    </div>
                    <div className="font-semibold text-slate-900">{action.label}</div>
                    <p className="text-sm text-slate-500">{action.description}</p>
                  </a>
                )
              })}
            </div>

            {/* Listas */}
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">

              {/* Categorias */}
              <Card className="flex flex-col border-t-4" style={{ borderTopColor: palette.primaryColor }}>
                <CardHeader className="flex-row items-center justify-between space-y-0 pb-2 pt-4">
                  <CardTitle className="text-sm font-semibold flex items-center gap-1.5">
                    <Tag className="h-4 w-4" style={{ color: palette.primaryColor }} />
                    Categorias
                  </CardTitle>
                  <Badge className="font-semibold text-white" style={{ background: palette.primaryGradient }}>{metrics.categories ?? 0}</Badge>
                </CardHeader>
                <Separator />
                <ScrollArea className="flex-1 max-h-56">
                  <div className="p-1">
                    {(recent.categories ?? []).map((c) => (
                      <a
                        key={c.id}
                        href={`/admin/${activeSlug}/categories/${c.id}/edit`}
                        className="flex items-center justify-between rounded-lg px-3 py-2 text-sm hover:bg-slate-50"
                      >
                        <span className="truncate text-slate-700">{c.name}</span>
                        <ChevronRight className="h-3.5 w-3.5 shrink-0 text-slate-400" />
                      </a>
                    ))}
                    {(recent.categories ?? []).length === 0 && (
                      <p className="px-3 py-2 text-xs text-slate-400">Nenhuma categoria ainda</p>
                    )}
                  </div>
                </ScrollArea>
                <CardContent className="pt-2 pb-3">
                  <a href={`/admin/${activeSlug}/categories`} className="flex items-center gap-1 text-xs font-medium" style={{ color: palette.primaryColor }}>
                    Ver todas <ChevronRight className="h-3 w-3" />
                  </a>
                </CardContent>
              </Card>

              {/* Produtos */}
              <Card className="flex flex-col border-t-4" style={{ borderTopColor: palette.primaryColor }}>
                <CardHeader className="flex-row items-center justify-between space-y-0 pb-2 pt-4">
                  <CardTitle className="text-sm font-semibold flex items-center gap-1.5">
                    <Package className="h-4 w-4" style={{ color: palette.primaryColor }} />
                    Produtos
                  </CardTitle>
                  <Badge className="font-semibold text-white" style={{ background: palette.primaryGradient }}>{metrics.products ?? 0}</Badge>
                </CardHeader>
                <Separator />
                <ScrollArea className="flex-1 max-h-56">
                  <div className="p-1">
                    {(recent.products ?? []).map((p) => (
                      <a
                        key={p.id}
                        href={`/admin/${activeSlug}/products/${p.id}/edit`}
                        className="flex items-center justify-between rounded-lg px-3 py-2 text-sm hover:bg-slate-50"
                      >
                        <div className="flex items-center gap-2 min-w-0">
                          {p.image ? (
                            <img src={p.image} alt={p.name} className="h-7 w-7 rounded object-cover shrink-0" />
                          ) : (
                            <div className="h-7 w-7 rounded bg-slate-100 flex items-center justify-center shrink-0">
                              <ImageOff className="h-3.5 w-3.5 text-slate-400" />
                            </div>
                          )}
                          <span className="truncate text-slate-700">{p.name}</span>
                        </div>
                        <span className="shrink-0 text-xs text-slate-500 ml-2">
                          {p.promo_price ? fmt(p.promo_price) : fmt(p.price)}
                        </span>
                      </a>
                    ))}
                    {(recent.products ?? []).length === 0 && (
                      <p className="px-3 py-2 text-xs text-slate-400">Nenhum produto ainda</p>
                    )}
                  </div>
                </ScrollArea>
                <CardContent className="pt-2 pb-3">
                  <a href={`/admin/${activeSlug}/products`} className="flex items-center gap-1 text-xs font-medium" style={{ color: palette.primaryColor }}>
                    Ver todos <ChevronRight className="h-3 w-3" />
                  </a>
                </CardContent>
              </Card>

              {/* Ingredientes */}
              <Card className="flex flex-col border-t-4" style={{ borderTopColor: palette.primaryColor }}>
                <CardHeader className="flex-row items-center justify-between space-y-0 pb-2 pt-4">
                  <CardTitle className="text-sm font-semibold flex items-center gap-1.5">
                    <Utensils className="h-4 w-4" style={{ color: palette.primaryColor }} />
                    Ingredientes
                  </CardTitle>
                  <Badge className="font-semibold text-white" style={{ background: palette.primaryGradient }}>{metrics.ingredients ?? 0}</Badge>
                </CardHeader>
                <Separator />
                <ScrollArea className="flex-1 max-h-56">
                  <div className="p-1">
                    {(recent.ingredients ?? []).map((i) => (
                      <a
                        key={i.id}
                        href={`/admin/${activeSlug}/ingredients/${i.id}/edit`}
                        className="flex items-center justify-between rounded-lg px-3 py-2 text-sm hover:bg-slate-50"
                      >
                        <span className="truncate text-slate-700">{i.name}</span>
                        <ChevronRight className="h-3.5 w-3.5 shrink-0 text-slate-400" />
                      </a>
                    ))}
                    {(recent.ingredients ?? []).length === 0 && (
                      <p className="px-3 py-2 text-xs text-slate-400">Nenhum ingrediente ainda</p>
                    )}
                  </div>
                </ScrollArea>
                <CardContent className="pt-2 pb-3">
                  <a href={`/admin/${activeSlug}/ingredients`} className="flex items-center gap-1 text-xs font-medium" style={{ color: palette.primaryColor }}>
                    Ver todos <ChevronRight className="h-3 w-3" />
                  </a>
                </CardContent>
              </Card>

              {/* Pedidos recentes */}
              <Card className="flex flex-col border-t-4" style={{ borderTopColor: palette.primaryColor }}>
                <CardHeader className="flex-row items-center justify-between space-y-0 pb-2 pt-4">
                  <CardTitle className="text-sm font-semibold flex items-center gap-1.5">
                    <ShoppingCart className="h-4 w-4" style={{ color: palette.primaryColor }} />
                    Pedidos
                  </CardTitle>
                  <Badge className="font-semibold text-white" style={{ background: palette.primaryGradient }}>{metrics.orders ?? 0}</Badge>
                </CardHeader>
                <Separator />
                <ScrollArea className="flex-1 max-h-56">
                  <div className="p-1">
                    {(recent.orders ?? []).map((o) => (
                      <a
                        key={o.id}
                        href={`/admin/${activeSlug}/orders/${o.id}`}
                        className="flex items-center justify-between rounded-lg px-3 py-2 text-sm hover:bg-slate-50"
                      >
                        <div className="min-w-0">
                          <div className="flex items-center gap-1.5">
                            <span className="font-medium text-slate-700">#{o.order_number ?? o.id}</span>
                            <OrderStatusBadge status={o.status} />
                          </div>
                          <p className="truncate text-xs text-slate-500">{o.customer_name}</p>
                        </div>
                        <span className="shrink-0 text-xs font-medium text-slate-700 ml-2">{fmt(o.total)}</span>
                      </a>
                    ))}
                    {(recent.orders ?? []).length === 0 && (
                      <p className="px-3 py-2 text-xs text-slate-400">Nenhum pedido ainda</p>
                    )}
                  </div>
                </ScrollArea>
                <CardContent className="pt-2 pb-3">
                  <a href={links.orders ?? `/admin/${activeSlug}/orders`} className="flex items-center gap-1 text-xs font-medium" style={{ color: palette.primaryColor }}>
                    Ver todos <ChevronRight className="h-3 w-3" />
                  </a>
                </CardContent>
              </Card>

            </div>
    </AdminStorePageShell>
  )
}
