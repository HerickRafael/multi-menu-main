import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Separator } from '@/components/ui/separator'
import { AdminStorePageShell } from '@/components/admin-store/page-shell'
import { useStoreContext } from '@/components/admin-store/use-store-context'
import {
  BarChart3,
  ChevronRight,
  CircleDollarSign,
  ExternalLink,
  ImageOff,
  LogOut,
  Package,
  Pause,
  PlusCircle,
  Receipt,
  Settings,
  ShoppingBag,
  ShoppingCart,
  Tag,
  TrendingUp,
  Utensils,
} from 'lucide-react'

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
                <Button className="text-white bg-amber-600 hover:bg-amber-700">Pausar Loja</Button>
              </div>
            </section>

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
                    {!(recent.categories ?? []).length && (
                      <p className="text-xs text-muted-foreground px-3 py-3">Nenhuma categoria ainda.</p>
                    )}
                    {(recent.categories ?? []).slice(0, 8).map((c) => (
                      <a key={c.id} href={`/admin/${activeSlug}/categories/${c.id}/edit`}
                        className="flex items-center justify-between rounded-md px-3 py-2 text-sm hover:bg-muted transition-colors group">
                        <span className="truncate">{c.name}</span>
                        <ChevronRight className="h-3.5 w-3.5 text-muted-foreground/50 opacity-0 group-hover:opacity-100 shrink-0" />
                      </a>
                    ))}
                  </div>
                </ScrollArea>
                <Separator />
                <div className="p-2">
                  <Button asChild variant="ghost" size="sm" className="w-full text-xs gap-1 text-muted-foreground h-8">
                    <a href={links.categories ?? `/admin/${activeSlug}/categories`}>Ver todas <ChevronRight className="h-3 w-3" /></a>
                  </Button>
                </div>
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
                    {!(recent.products ?? []).length && (
                      <p className="text-xs text-muted-foreground px-3 py-3">Sem produtos ainda.</p>
                    )}
                    {(recent.products ?? []).slice(0, 8).map((p) => (
                      <a key={p.id} href={`/admin/${activeSlug}/products/${p.id}/edit`}
                        className="flex items-center gap-2.5 rounded-md px-2 py-1.5 hover:bg-muted transition-colors group">
                        {p.image ? (
                          <img src={p.image} alt="" className="h-9 w-9 rounded-md object-cover border shrink-0" />
                        ) : (
                          <div className="h-9 w-9 rounded-md bg-muted border flex items-center justify-center shrink-0">
                            <ImageOff className="h-3.5 w-3.5 text-muted-foreground" />
                          </div>
                        )}
                        <div className="min-w-0 flex-1">
                          <p className="text-sm font-medium truncate leading-tight">{p.name}</p>
                          <p className="text-[11px] text-muted-foreground">
                            {p.promo_price != null ? (
                              <><span className="line-through mr-1">{fmt(p.price)}</span><span className="text-emerald-600 font-semibold">{fmt(p.promo_price)}</span></>
                            ) : fmt(p.price)}
                          </p>
                        </div>
                        <ChevronRight className="h-3.5 w-3.5 text-muted-foreground/50 opacity-0 group-hover:opacity-100 shrink-0" />
                      </a>
                    ))}
                  </div>
                </ScrollArea>
                <Separator />
                <div className="p-2">
                  <Button asChild variant="ghost" size="sm" className="w-full text-xs gap-1 text-muted-foreground h-8">
                    <a href={links.products ?? `/admin/${activeSlug}/products`}>Ver todos <ChevronRight className="h-3 w-3" /></a>
                  </Button>
                </div>
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
                    {!(recent.ingredients ?? []).length && (
                      <p className="text-xs text-muted-foreground px-3 py-3">Sem ingredientes.</p>
                    )}
                    {(recent.ingredients ?? []).slice(0, 8).map((i) => (
                      <a key={i.id} href={`/admin/${activeSlug}/ingredients/${i.id}/edit`}
                        className="flex items-center gap-2.5 rounded-md px-2 py-1.5 hover:bg-muted transition-colors group">
                        {i.image_path ? (
                          <img src={i.image_path} alt="" className="h-9 w-9 rounded-md object-cover border shrink-0" />
                        ) : (
                          <div className="h-9 w-9 rounded-md bg-muted border flex items-center justify-center shrink-0">
                            <ImageOff className="h-3.5 w-3.5 text-muted-foreground" />
                          </div>
                        )}
                        <p className="text-sm font-medium truncate flex-1">{i.name}</p>
                        <ChevronRight className="h-3.5 w-3.5 text-muted-foreground/50 opacity-0 group-hover:opacity-100 shrink-0" />
                      </a>
                    ))}
                  </div>
                </ScrollArea>
                <Separator />
                <div className="p-2">
                  <Button asChild variant="ghost" size="sm" className="w-full text-xs gap-1 text-muted-foreground h-8">
                    <a href={`/admin/${activeSlug}/ingredients`}>Ver todos <ChevronRight className="h-3 w-3" /></a>
                  </Button>
                </div>
              </Card>

              {/* Pedidos recentes */}
              <Card className="flex flex-col border-t-4" style={{ borderTopColor: palette.primaryColor }}>
                <CardHeader className="flex-row items-center justify-between space-y-0 pb-2 pt-4">
                  <CardTitle className="text-sm font-semibold flex items-center gap-1.5">
                    <ShoppingCart className="h-4 w-4" style={{ color: palette.primaryColor }} />
                    Pedidos Recentes
                  </CardTitle>
                  <Badge className="font-semibold text-white" style={{ background: palette.primaryGradient }}>{metrics.orders ?? 0}</Badge>
                </CardHeader>
                <Separator />
                <ScrollArea className="flex-1 max-h-56">
                  <div className="p-1">
                    {!(recent.orders ?? []).length && (
                      <p className="text-xs text-muted-foreground px-3 py-3">Sem pedidos recentes.</p>
                    )}
                    {(recent.orders ?? []).slice(0, 8).map((o) => (
                      <a key={o.id} href={`/admin/${activeSlug}/orders/show?id=${o.id}`}
                        className="flex items-start gap-2 rounded-md px-3 py-2 hover:bg-muted transition-colors group">
                        <div className="flex-1 min-w-0">
                          <p className="text-sm font-medium truncate leading-tight">#{o.order_number ?? o.id} · {o.customer_name}</p>
                          <div className="flex items-center gap-2 mt-1">
                            <OrderStatusBadge status={o.status} />
                            <span className="text-xs font-semibold">{fmt(o.total)}</span>
                          </div>
                          {o.created_at && <p className="text-[11px] text-muted-foreground mt-0.5">{o.created_at}</p>}
                        </div>
                        <ChevronRight className="h-3.5 w-3.5 text-muted-foreground/50 opacity-0 group-hover:opacity-100 shrink-0 mt-1" />
                      </a>
                    ))}
                  </div>
                </ScrollArea>
                <Separator />
                <div className="p-2">
                  <Button asChild variant="ghost" size="sm" className="w-full text-xs gap-1 text-muted-foreground h-8">
                    <a href={links.orders ?? `/admin/${activeSlug}/orders`}>Ver todos <ChevronRight className="h-3 w-3" /></a>
                  </Button>
                </div>
              </Card>

            </div>
    </AdminStorePageShell>
  )
}
