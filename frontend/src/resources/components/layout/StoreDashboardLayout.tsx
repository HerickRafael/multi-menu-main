import { useMemo, useState, type ReactNode } from 'react'
import type { StoreHour } from '@/components/admin-store/use-store-context'
import {
  ArrowLeftRight,
  Award,
  BarChart3,
  Bell,
  Box,
  Calendar,
  ChefHat,
  ChevronDown,
  CircleHelp,
  Code,
  DollarSign,
  ExternalLink,
  Folder,
  Gift,
  Grid3x3,
  Heart,
  Home,
  LogOut,
  Menu,
  MessageSquare,
  Package,
  Plus,
  Receipt,
  Settings,
  ShoppingBag,
  SlidersHorizontal,
  Tag,
  Ticket,
  TrendingUp,
  Truck,
  UserPlus,
  Users,
  Utensils,
  Wallet,
  X,
  type LucideIcon,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar'
import {
  AlertDialog,
  AlertDialogContent,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogCancel,
  AlertDialogAction,
} from '@/components/ui/alert-dialog'
import { cn } from '@/js/lib/utils'

type StoreLayoutPalette = {
  primaryColor: string
  primaryGradient: string
  primaryForeground: string
  accentStrong?: string
}

export type StoreSection =
  | 'dashboard'
  | 'orders'
  | 'financial'
  | 'catalog'
  | 'categories'
  | 'customers'
  | 'loyalty'
  | 'analytics'
  | 'whatsapp'
  | 'settings'
  | 'ifood'

type StoreDashboardLayoutProps = {
  companyName: string
  companyLogo?: string
  storeIsOpen?: boolean
  ifoodIsActive?: boolean
  storeHours?: Record<string, StoreHour>
  settingsUrl?: string
  activeSlug: string
  currentSection: StoreSection
  palette: StoreLayoutPalette
  children: ReactNode
}

const DAY_NAMES = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado']

type StoreMenuItem = {
  key: string
  label: string
  href: string
  icon: LucideIcon
  active?: boolean
}

type StoreMenuGroup = {
  key: string
  label?: string
  items: StoreMenuItem[]
}

function SidebarNav({
  navGroups,
  palette,
  collapsed,
  groupOpen,
  onToggleGroup,
}: {
  navGroups: StoreMenuGroup[]
  palette: StoreLayoutPalette
  collapsed: boolean
  groupOpen: Record<string, boolean>
  onToggleGroup: (key: string) => void
}) {
  return (
    <nav className="flex-1 overflow-y-auto px-1 py-1.5 space-y-3">
      {navGroups.map((group, idx) => (
        <div key={`${group.label ?? 'group'}-${idx}`} className="space-y-1">
          {!collapsed && group.label && (
            <button
              type="button"
              onClick={() => onToggleGroup(group.key)}
              className="flex w-full items-center justify-between rounded-md px-3 py-1 text-[11px] font-semibold tracking-wide text-zinc-500 hover:bg-zinc-200/60"
              aria-expanded={groupOpen[group.key]}
            >
              <span>{group.label}</span>
              <ChevronDown
                className={cn('h-3.5 w-3.5 transition-transform', groupOpen[group.key] ? 'rotate-0' : '-rotate-90')}
              />
            </button>
          )}

          {group.label && !collapsed && !groupOpen[group.key] ? null : (
            <>
              {group.items.map((item) => {
                const Icon = item.icon
                const link = (
                  <a
                    href={item.href}
                    className={cn(
                      'flex h-9 items-center rounded-md px-3 text-sm font-medium transition-colors',
                      item.active
                        ? 'bg-zinc-200/90 text-zinc-950'
                        : 'text-zinc-700 hover:bg-zinc-200/70 hover:text-zinc-900',
                      collapsed ? 'justify-center' : 'gap-3',
                    )}
                    style={item.active ? { boxShadow: `inset 2px 0 0 ${palette.primaryColor}` } : undefined}
                    aria-current={item.active ? 'page' : undefined}
                  >
                    <Icon className="h-4 w-4 shrink-0" />
                    {!collapsed && item.label}
                  </a>
                )

                if (!collapsed) return <div key={item.key}>{link}</div>

                return (
                  <Tooltip key={item.key}>
                    <TooltipTrigger asChild>{link}</TooltipTrigger>
                    <TooltipContent side="right">{item.label}</TooltipContent>
                  </Tooltip>
                )
              })}
            </>
          )}
        </div>
      ))}
    </nav>
  )
}

function IFoodAvatar({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" className={className}>
      <path d="M8.428 1.67c-4.65 0-7.184 4.149-7.184 6.998 0 2.294 2.2 3.299 4.25 3.299l-.006-.006c4.244 0 7.184-3.854 7.184-6.998 0-2.29-2.175-3.293-4.244-3.293zm11.328 0c-4.65 0-7.184 4.149-7.184 6.998 0 2.294 2.2 3.299 4.25 3.299l-.006-.006C21.061 11.96 24 8.107 24 4.963c0-2.29-2.18-3.293-4.244-3.293zM14.172 14.52l2.435 1.834c-2.17 2.07-6.124 3.525-9.353 3.17A8.913 8.913 0 01.23 14.541H0a9.598 9.598 0 008.828 7.758c3.814.24 7.323-.905 9.947-3.13l-.004.007 1.08 2.988 1.555-7.623-7.234-.02Z" />
    </svg>
  )
}

const SIDEBAR_KEY = 'admin-sidebar-collapsed'

export function StoreDashboardLayout({
  companyName,
  companyLogo,
  storeIsOpen,
  ifoodIsActive,
  storeHours = {},
  settingsUrl = '',
  activeSlug,
  currentSection,
  palette,
  children,
}: StoreDashboardLayoutProps) {
  const [collapsed, setCollapsed] = useState(() => {
    try { return localStorage.getItem(SIDEBAR_KEY) === '1' } catch { return false }
  })
  const [mobileOpen, setMobileOpen] = useState(false)
  const [hoursOpen, setHoursOpen] = useState(false)

  const toggleCollapsed = () => {
    setCollapsed(prev => {
      const next = !prev
      try { localStorage.setItem(SIDEBAR_KEY, next ? '1' : '0') } catch {}
      return next
    })
  }
  const [groupOpen, setGroupOpen] = useState<Record<string, boolean>>({
    secondary: true,
  })
  const topbarHeightClass = 'h-12'
  const topbarOffsetClass = 'top-12'
  const sidebarWidth = collapsed ? 'lg:w-14' : 'lg:w-60'

  const handleToggleGroup = (key: string) => {
    setGroupOpen((prev) => ({ ...prev, [key]: !prev[key] }))
  }

  const navGroups = useMemo<StoreMenuGroup[]>(() => {
    const item = (key: string, label: string, href: string, icon: LucideIcon): StoreMenuItem => ({
      key,
      label,
      href,
      icon,
      active: key === currentSection,
    })

    // Item secundário (não casa com section principal — `active` controlado pelo path atual).
    const sub = (
      key: string,
      label: string,
      href: string,
      icon: LucideIcon,
      pathMatches: string[],
    ): StoreMenuItem => {
      const currentPath = typeof window !== 'undefined' ? window.location.pathname : ''
      const active = pathMatches.some((p) => currentPath.startsWith(p))
      return { key, label, href, icon, active }
    }

    const slug = activeSlug
    const defaultSecondary: StoreMenuItem[] = [
      sub('payment-methods', 'Pagamentos', `/admin/${slug}/payment-methods`, DollarSign, [`/admin/${slug}/payment-methods`]),
      sub('delivery-fees', 'Taxas de entrega', `/admin/${slug}/delivery-fees`, Truck, [`/admin/${slug}/delivery-fees`]),
      sub('coupons', 'Cupons', `/admin/${slug}/coupons/create`, Ticket, [`/admin/${slug}/coupons`]),
      sub('kds', 'KDS (Cozinha)', `/admin/${slug}/kds`, ChefHat, [`/admin/${slug}/kds`]),
    ]

    const secondaryBySection: Partial<Record<StoreSection, { label: string; items: StoreMenuItem[] }>> = {
      catalog: {
        label: 'Produtos',
        items: [
          sub('ingredients', 'Ingredientes', `/admin/${slug}/ingredients`, Utensils, [`/admin/${slug}/ingredients`]),
          sub('customization-templates', 'Personalizações', `/admin/${slug}/customization-templates`, SlidersHorizontal, [`/admin/${slug}/customization-templates`]),
          sub('cross-sell-groups', 'Cross-Sell', `/admin/${slug}/cross-sell-groups`, ArrowLeftRight, [`/admin/${slug}/cross-sell-groups`]),
          sub('product-costs', 'Custos Produtos', `/admin/${slug}/product-costs`, Receipt, [`/admin/${slug}/product-costs`]),
          sub('packaging', 'Embalagens', `/admin/${slug}/packaging`, Box, [`/admin/${slug}/packaging`]),
        ],
      },
      categories: {
        label: 'Produtos',
        items: [
          sub('ingredients', 'Ingredientes', `/admin/${slug}/ingredients`, Utensils, [`/admin/${slug}/ingredients`]),
          sub('customization-templates', 'Personalizações', `/admin/${slug}/customization-templates`, SlidersHorizontal, [`/admin/${slug}/customization-templates`]),
          sub('cross-sell-groups', 'Cross-Sell', `/admin/${slug}/cross-sell-groups`, ArrowLeftRight, [`/admin/${slug}/cross-sell-groups`]),
          sub('product-costs', 'Custos Produtos', `/admin/${slug}/product-costs`, Receipt, [`/admin/${slug}/product-costs`]),
          sub('packaging', 'Embalagens', `/admin/${slug}/packaging`, Box, [`/admin/${slug}/packaging`]),
        ],
      },
      orders: {
        label: 'Pedidos',
        items: [
          sub('orders-create', 'Novo Pedido', `/admin/${slug}/orders/create`, Plus, [`/admin/${slug}/orders/create`]),
          sub('kds', 'KDS (Cozinha)', `/admin/${slug}/kds`, ChefHat, [`/admin/${slug}/kds`]),
          sub('ifood-orders', 'Pedidos iFood', `/admin/${slug}/ifood/orders`, Truck, [`/admin/${slug}/ifood/orders`]),
        ],
      },
      financial: {
        label: 'Financeiro',
        items: [
          sub('financial-monthly', 'Mensal', `/admin/${slug}/financial/monthly`, Calendar, [`/admin/${slug}/financial/monthly`]),
          sub('financial-yearly', 'Anual', `/admin/${slug}/financial/yearly`, TrendingUp, [`/admin/${slug}/financial/yearly`]),
          sub('expenses', 'Despesas', `/admin/${slug}/expenses`, Wallet, [`/admin/${slug}/expenses`]),
          sub('expenses-categories', 'Cat. Despesas', `/admin/${slug}/expenses/categories`, Folder, [`/admin/${slug}/expenses/categories`]),
          sub('product-costs', 'Custos Produtos', `/admin/${slug}/product-costs`, Receipt, [`/admin/${slug}/product-costs`]),
          sub('packaging', 'Embalagens', `/admin/${slug}/packaging`, Box, [`/admin/${slug}/packaging`]),
        ],
      },
      loyalty: {
        label: 'Fidelidade',
        items: [
          sub('loyalty-program', 'Programa Fidelidade', `/admin/${slug}/loyalty-program`, Award, [`/admin/${slug}/loyalty-program`]),
          sub('coupons', 'Cupons', `/admin/${slug}/coupons/create`, Ticket, [`/admin/${slug}/coupons`]),
        ],
      },
      customers: {
        label: 'Clientes',
        items: [
          sub('customers-create', 'Novo Cliente', `/admin/${slug}/customers/create`, UserPlus, [`/admin/${slug}/customers/create`]),
        ],
      },
      whatsapp: {
        label: 'WhatsApp',
        items: [
          sub('evolution-instances', 'Instâncias', `/admin/${slug}/evolution`, MessageSquare, [`/admin/${slug}/evolution`]),
        ],
      },
      settings: {
        label: 'Configurações',
        items: [
          sub('payment-methods', 'Pagamentos', `/admin/${slug}/payment-methods`, DollarSign, [`/admin/${slug}/payment-methods`]),
          sub('delivery-fees', 'Taxas de entrega', `/admin/${slug}/delivery-fees`, Truck, [`/admin/${slug}/delivery-fees`]),
          sub('api', 'API', `/admin/${slug}/api`, Code, [`/admin/${slug}/api`]),
        ],
      },
      ifood: {
        label: 'iFood',
        items: [
          sub('ifood-config', 'Configuração', `/admin/${slug}/ifood/config`, Grid3x3, [`/admin/${slug}/ifood/config`]),
          sub('ifood-widget', 'Central iFood', `/admin/${slug}/ifood/widget`, Grid3x3, [`/admin/${slug}/ifood/widget`]),
          sub('ifood-reviews', 'Avaliações', `/admin/${slug}/ifood/reviews`, Heart, [`/admin/${slug}/ifood/reviews`]),
          sub('ifood-stock', 'Estoque', `/admin/${slug}/ifood/stock`, Package, [`/admin/${slug}/ifood/stock`]),
          sub('ifood-logs', 'Logs API', `/admin/${slug}/ifood/logs`, Receipt, [`/admin/${slug}/ifood/logs`]),
        ],
      },
    }

    const ctx = secondaryBySection[currentSection]
    const secondaryGroup: StoreMenuGroup = ctx
      ? { key: 'secondary', label: ctx.label, items: ctx.items }
      : { key: 'secondary', label: 'Mais opções', items: defaultSecondary }

    return [
      {
        key: 'main',
        items: [
          item('dashboard', 'Início', `/admin/${slug}/dashboard`, Home),
          item('orders', 'Pedidos', `/admin/${slug}/orders`, ShoppingBag),
          item('catalog', 'Produtos', `/admin/${slug}/products`, Package),
          item('categories', 'Categorias', `/admin/${slug}/categories`, Tag),
          item('customers', 'Clientes', `/admin/${slug}/customers`, Users),
          item('loyalty', 'Fidelidade', `/admin/${slug}/loyalty-discount`, Gift),
          item('analytics', 'Analytics', `/admin/${slug}/analytics`, BarChart3),
          item('financial', 'Financeiro', `/admin/${slug}/financial`, DollarSign),
          item('whatsapp', 'WhatsApp', `/admin/${slug}/evolution`, MessageSquare),
          item('ifood', 'iFood', `/admin/${slug}/ifood/config`, Grid3x3),
          item('settings', 'Configurações', `/admin/${slug}/settings`, Settings),
        ],
      },
      secondaryGroup,
    ]
  }, [activeSlug, currentSection])

  return (
    <TooltipProvider>
      <div className="admin-dashboard-light min-h-screen bg-[#efeff0] text-foreground">
        {/* Topbar */}
        <header
          className={cn('fixed inset-x-0 top-0 z-50 pr-3', topbarHeightClass)}
          style={{ background: '#efeff0', borderBottom: 'none' }}
        >
          <div className="flex h-full items-center justify-between">
            <div className="flex items-center gap-2">
              {/* w-14 = collapsed sidebar width → hamburger icon aligns with sidebar icons */}
              <div className="flex w-14 shrink-0 items-center justify-center">
                <button
                  type="button"
                  onClick={toggleCollapsed}
                  className="hidden h-8 w-8 items-center justify-center rounded-md text-zinc-600 hover:bg-zinc-200 lg:inline-flex"
                  aria-label={collapsed ? 'Expandir menu lateral' : 'Recolher menu lateral'}
                >
                  <Menu className="h-4 w-4" />
                </button>
                <button
                  type="button"
                  onClick={() => setMobileOpen(true)}
                  className="inline-flex h-8 w-8 items-center justify-center rounded-md text-zinc-600 hover:bg-zinc-200 lg:hidden"
                  aria-label="Abrir menu lateral"
                >
                  <Menu className="h-4 w-4" />
                </button>
              </div>

              {/* Store avatar pill — click opens hours dialog */}
              <button
                type="button"
                onClick={() => setHoursOpen(true)}
                className="inline-flex items-center gap-1.5 rounded-full bg-white px-2 py-1 ring-1 ring-zinc-200 transition-shadow hover:ring-zinc-300 hover:shadow-sm"
              >
                <div className="relative h-6 w-6 shrink-0">
                  <Avatar className="h-6 w-6">
                    <AvatarImage src={companyLogo} alt={companyName} />
                    <AvatarFallback className="text-[11px] font-bold" style={{ color: palette.primaryColor }}>
                      {companyName.charAt(0).toUpperCase()}
                    </AvatarFallback>
                  </Avatar>
                  <span className={cn(
                    'absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full ring-2 ring-white',
                    storeIsOpen ? 'bg-emerald-500' : 'bg-zinc-400'
                  )} />
                </div>
                <span className="max-w-[120px] truncate text-sm font-semibold text-zinc-700">{companyName}</span>
              </button>

              {/* iFood avatar pill */}
              <Tooltip>
                <TooltipTrigger asChild>
                  <a
                    href={`/admin/${activeSlug}/ifood/config`}
                    className="hidden items-center gap-1.5 rounded-full bg-white px-2 py-1 ring-1 ring-zinc-200 transition-shadow hover:ring-zinc-300 hover:shadow-sm sm:inline-flex"
                  >
                    <div className="relative h-6 w-6 shrink-0">
                      <div className="h-6 w-6 overflow-hidden rounded-full bg-red-50 flex items-center justify-center">
                        <IFoodAvatar className="h-3.5 w-3.5 text-red-500" />
                      </div>
                      <span className={cn(
                        'absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full ring-2 ring-white',
                        ifoodIsActive ? 'bg-emerald-500' : 'bg-zinc-400'
                      )} />
                    </div>
                    <span className="text-sm font-semibold text-zinc-700">iFood</span>
                  </a>
                </TooltipTrigger>
                <TooltipContent side="bottom" className="text-xs">
                  {ifoodIsActive ? 'Integração ativa — ver configurações' : 'Integração inativa — ver configurações'}
                </TooltipContent>
              </Tooltip>
            </div>

            <div className="flex items-center gap-2 text-zinc-600">
              <button type="button" className="inline-flex h-7 w-7 items-center justify-center rounded-md hover:bg-zinc-200" aria-label="Ajuda">
                <CircleHelp className="h-4 w-4" />
              </button>
              <button type="button" className="inline-flex h-7 w-7 items-center justify-center rounded-md hover:bg-zinc-200" aria-label="Notificações">
                <Bell className="h-4 w-4" />
              </button>
            </div>
          </div>
        </header>

        {/* Sidebar (desktop) */}
        <aside
          className={cn(
            'fixed bottom-0 left-0 z-40 hidden shrink-0 flex-col px-2 py-3 transition-all lg:flex',
            topbarOffsetClass,
            sidebarWidth,
          )}
          style={{ height: 'calc(100vh - 48px)', background: '#efeff0', borderRight: 'none' }}
        >
          <SidebarNav
            navGroups={navGroups}
            palette={palette}
            collapsed={collapsed}
            groupOpen={groupOpen}
            onToggleGroup={handleToggleGroup}
          />

          <div className="px-1 py-2 space-y-1">
            <a
              href={`/${activeSlug}`}
              target="_blank"
              rel="noreferrer"
              className={cn('flex h-9 items-center rounded-md px-3 text-sm text-zinc-700 hover:bg-zinc-200/70', collapsed ? 'justify-center' : 'gap-3')}
            >
              <ExternalLink className="h-4 w-4 shrink-0" />
              {!collapsed && 'Ver Cardápio'}
            </a>
            <a
              href={`/admin/${activeSlug}/logout`}
              className={cn('flex h-9 items-center rounded-md px-3 text-sm text-red-600 hover:bg-red-50', collapsed ? 'justify-center' : 'gap-3')}
            >
              <LogOut className="h-4 w-4 shrink-0" />
              {!collapsed && 'Sair'}
            </a>
          </div>
        </aside>

        {/* Main content */}
        <main
          className={cn(
            'fixed top-12 bottom-0 right-0 left-0 overflow-hidden bg-[#efeff0] transition-[left] duration-300',
            collapsed ? 'lg:left-14' : 'lg:left-60',
          )}
        >
          {/* Curved white frame — never scrolls, stays fixed */}
          <div className="absolute inset-0 rounded-tl-[28px] bg-white lg:border-l lg:border-zinc-200">
            {/* Scrollable content inside the fixed frame */}
            <div className="h-full overflow-y-auto">
              <div className="mx-auto w-full max-w-[1292px] px-3 pb-10 pt-6 sm:px-5 lg:px-8">
                <div className="min-h-[calc(100vh-48px-48px)] bg-white">
                  {children}
                </div>
              </div>
            </div>
          </div>
        </main>

        {/* Mobile sidebar overlay */}
        {mobileOpen && (
          <div className="fixed inset-0 z-40 lg:hidden">
            <button
              type="button"
              className="absolute inset-0 bg-black/40"
              onClick={() => setMobileOpen(false)}
              aria-label="Fechar menu"
            />
            <aside
              className="relative z-10 h-full w-72 max-w-[85vw] shadow-xl flex flex-col pt-12"
              style={{ background: '#efeff0', borderRight: 'none' }}
            >
              <div
                className="flex items-center justify-between gap-2.5 px-4 py-3"
                style={{ background: '#efeff0', borderBottom: 'none' }}
              >
                <div className="flex items-center gap-2.5 min-w-0">
                  <div className="h-6 w-6 rounded-md flex items-center justify-center shrink-0 bg-white ring-1 ring-zinc-300">
                    <div className="h-3.5 w-3.5 rounded-sm" style={{ background: palette.primaryGradient }} />
                  </div>
                  <span className="font-semibold text-sm truncate text-zinc-700">{companyName}</span>
                </div>
                <Button
                  type="button"
                  variant="ghost"
                  size="icon"
                  className="text-zinc-600 hover:bg-zinc-200 hover:text-zinc-700"
                  onClick={() => setMobileOpen(false)}
                  aria-label="Fechar menu lateral"
                >
                  <X className="h-4 w-4" />
                </Button>
              </div>

              <SidebarNav
                navGroups={navGroups}
                palette={palette}
                collapsed={false}
                groupOpen={groupOpen}
                onToggleGroup={handleToggleGroup}
              />

              <div className="px-2 py-3 space-y-1">
                <a
                  href={`/${activeSlug}`}
                  target="_blank"
                  rel="noreferrer"
                  className="flex h-9 items-center gap-3 rounded-md px-3 text-sm text-zinc-700 hover:bg-zinc-200/70"
                >
                  <ExternalLink className="h-4 w-4" />
                  Ver Cardápio
                </a>
                <a
                  href={`/admin/${activeSlug}/logout`}
                  className="flex h-9 items-center gap-3 rounded-md px-3 text-sm text-red-600 hover:bg-red-50"
                >
                  <LogOut className="h-4 w-4" />
                  Sair
                </a>
              </div>
            </aside>
          </div>
        )}

        {/* Hours alert-dialog */}
        <AlertDialog open={hoursOpen} onOpenChange={setHoursOpen}>
          <AlertDialogContent className="max-w-sm rounded-xl">
            <AlertDialogHeader>
              <div className="flex items-center gap-3 mb-1">
                <Avatar className="h-10 w-10">
                  <AvatarImage src={companyLogo} alt={companyName} />
                  <AvatarFallback className="text-sm font-bold" style={{ color: palette.primaryColor }}>
                    {companyName.charAt(0).toUpperCase()}
                  </AvatarFallback>
                </Avatar>
                <div>
                  <AlertDialogTitle className="text-base">{companyName}</AlertDialogTitle>
                  <span className={cn(
                    'inline-flex items-center gap-1.5 text-xs font-medium',
                    storeIsOpen ? 'text-emerald-600' : 'text-zinc-500'
                  )}>
                    <span className={cn('h-1.5 w-1.5 rounded-full', storeIsOpen ? 'bg-emerald-500' : 'bg-zinc-400')} />
                    {storeIsOpen ? 'Aberto agora' : 'Fechado agora'}
                  </span>
                </div>
              </div>
              <AlertDialogDescription className="sr-only">
                Horários de atendimento da loja
              </AlertDialogDescription>
            </AlertDialogHeader>

            <div className="space-y-0.5 py-2">
              {DAY_NAMES.map((name, idx) => {
                const h = storeHours[String(idx)]
                const isToday = new Date().getDay() === idx
                return (
                  <div
                    key={idx}
                    className={cn(
                      'flex items-center justify-between rounded-lg px-3 py-2 text-sm',
                      isToday ? 'bg-zinc-100 font-medium text-zinc-900' : 'text-zinc-500'
                    )}
                  >
                    <span>{name}</span>
                    {h?.is_open && h.open1 && h.close1 ? (
                      <span className={cn('tabular-nums', isToday ? 'text-zinc-800' : 'text-zinc-600')}>
                        {h.open1}–{h.close1}
                        {h.open2 && h.close2 ? ` / ${h.open2}–${h.close2}` : ''}
                      </span>
                    ) : (
                      <span className="text-zinc-400">Fechado</span>
                    )}
                  </div>
                )
              })}
            </div>

            <AlertDialogFooter>
              <AlertDialogCancel>Fechar</AlertDialogCancel>
              <AlertDialogAction
                className="gap-2 border-0"
                style={{ background: palette.primaryGradient, color: palette.primaryForeground }}
                onClick={() => { window.location.href = settingsUrl || '#' }}
              >
                <Settings className="h-4 w-4" />
                Alterar horários
              </AlertDialogAction>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialog>
      </div>
    </TooltipProvider>
  )
}
