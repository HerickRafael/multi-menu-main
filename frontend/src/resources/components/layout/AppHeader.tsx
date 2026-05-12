import { memo, useMemo, useState } from 'react'
import { useLocation, Link } from 'react-router-dom'
import { ChevronRight, Moon, Sun, Monitor, Search, LogOut, User, Settings, Zap } from 'lucide-react'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuShortcut,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { useUIStore } from '@/js/stores/uiStore'
import { useAuthStore } from '@/js/stores/authStore'
import { useCompanyFilterStore } from '@/js/stores/companyFilterStore'
import { useAuth } from '@/js/hooks/useAuth'
import { useStoresData } from '@/js/hooks/usePhase3Data'
import { useTheme } from '@/js/providers/ThemeProvider'
import { useTenant } from '@/js/contexts/TenantContext'
import { TenantSwitcher } from '@/modules/tenant/components/TenantSwitcher'
import { PLATFORM_NAV_GROUPS, TENANT_NAV_GROUPS } from '@/js/lib/constants'
import { cn } from '@/js/lib/utils'

// ─── Breadcrumbs ─────────────────────────────────────────────────────────────
function Breadcrumbs({ navItemLabels, rootHref }: { navItemLabels: Record<string, string>; rootHref: string }) {
  const location = useLocation()
  const crumbs = useMemo(() => {
    const segments = location.pathname.replace('/superadmin', '').split('/').filter(Boolean)

    return segments.map((seg, idx) => {
      const path = '/superadmin/' + segments.slice(0, idx + 1).join('/')
      return { label: navItemLabels[path] ?? capitalize(seg), path }
    })
  }, [location.pathname, navItemLabels])

  if (crumbs.length === 0) return null

  return (
    <nav aria-label="breadcrumb" className="flex items-center gap-1 text-sm text-muted-foreground">
      <Link to={rootHref} className="hover:text-foreground transition-colors">
        Super Admin
      </Link>
      {crumbs.map((crumb, i) => (
        <span key={crumb.path} className="flex items-center gap-1">
          <ChevronRight className="h-3.5 w-3.5" />
          {i === crumbs.length - 1 ? (
            <span className="text-foreground font-medium">{crumb.label}</span>
          ) : (
            <Link to={crumb.path} className="hover:text-foreground transition-colors">
              {crumb.label}
            </Link>
          )}
        </span>
      ))}
    </nav>
  )
}

function capitalize(s: string) {
  return s.charAt(0).toUpperCase() + s.slice(1).replace(/-/g, ' ')
}

// ─── Theme toggle ─────────────────────────────────────────────────────────────
function ThemeToggle() {
  const { theme, setTheme } = useTheme()
  const cycles: Array<typeof theme> = ['light', 'dark', 'system']
  const next = cycles[(cycles.indexOf(theme) + 1) % cycles.length]
  const Icon = theme === 'dark' ? Moon : theme === 'light' ? Sun : Monitor

  return (
    <Button
      variant="ghost"
      size="icon-sm"
      onClick={() => setTheme(next)}
      aria-label={`Tema: ${theme}. Clique para mudar para ${next}`}
    >
      <Icon className="h-4 w-4" />
    </Button>
  )
}

// ─── User menu ────────────────────────────────────────────────────────────────
function UserMenu() {
  const user = useAuthStore((state) => state.user)
  const { logout, isLoggingOut } = useAuth()

  const initials = user?.name
    ?.split(' ')
    .slice(0, 2)
    .map(n => n[0])
    .join('')
    .toUpperCase() ?? 'SA'

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <button className={cn(
          'flex items-center gap-2 rounded-md p-1 pr-2 outline-none',
          'hover:bg-accent transition-colors focus-visible:ring-2 focus-visible:ring-ring',
        )}>
          <Avatar className="h-7 w-7">
            <AvatarImage src={user?.avatar_url} alt={user?.name ?? 'Admin'} />
            <AvatarFallback className="text-[11px] font-semibold bg-primary text-primary-foreground">
              {initials}
            </AvatarFallback>
          </Avatar>
          <div className="hidden md:block text-left">
            <p className="text-xs font-medium leading-none">{user?.name ?? 'Super Admin'}</p>
            <p className="text-[10px] text-muted-foreground leading-none mt-0.5 capitalize">{user?.role ?? 'root'}</p>
          </div>
        </button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-52">
        <DropdownMenuLabel className="font-normal">
          <p className="text-sm font-medium">{user?.name}</p>
          <p className="text-xs text-muted-foreground truncate">{user?.email}</p>
        </DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuGroup>
          <DropdownMenuItem asChild>
            <Link to="/superadmin/platform/settings">
              <Settings className="h-4 w-4" />
              Configurações
              <DropdownMenuShortcut>⌘S</DropdownMenuShortcut>
            </Link>
          </DropdownMenuItem>
          <DropdownMenuItem asChild>
            <Link to="/superadmin/platform/settings#profile">
              <User className="h-4 w-4" />
              Perfil
            </Link>
          </DropdownMenuItem>
        </DropdownMenuGroup>
        <DropdownMenuSeparator />
        <DropdownMenuItem
          className="text-destructive focus:text-destructive"
          onClick={() => logout()}
          disabled={isLoggingOut}
        >
          <LogOut className="h-4 w-4" />
          {isLoggingOut ? 'Saindo…' : 'Sair'}
          <DropdownMenuShortcut>⇧⌘Q</DropdownMenuShortcut>
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  )
}

// ─── App Header ──────────────────────────────────────────────────────────────
export const AppHeader = memo(function AppHeader() {
  const [tenantSwitcherOpen, setTenantSwitcherOpen] = useState(false)
  const setCommandMenuOpen = useUIStore((state) => state.setCommandMenuOpen)
  const selectedCompanyId = useCompanyFilterStore((state) => state.selectedCompanyId)
  const setSelectedCompanyId = useCompanyFilterStore((state) => state.setSelectedCompanyId)
  const { selectedTenantId, tenantName, selectedTenantSlug } = useTenant()
  const location = useLocation()
  const navMode = location.pathname.startsWith('/superadmin/tenant/') ? 'tenant' : 'platform'
  const storesQuery = useStoresData({ page: 1, per_page: 300, search: '', status: '' })
  const storeItems = storesQuery.data?.items ?? []
  const tenantSlug = selectedTenantSlug || 'select-tenant'

  const navGroups = useMemo(() => {
    const baseGroups = navMode === 'tenant' ? TENANT_NAV_GROUPS : PLATFORM_NAV_GROUPS
    return baseGroups.map((group) => ({
      ...group,
      items: group.items.map((item) => ({
        ...item,
        href: item.href.replace(':slug', tenantSlug),
      })),
    }))
  }, [navMode, tenantSlug])

  const navItemLabels = useMemo(() => {
    return navGroups
      .flatMap((group) => group.items)
      .reduce<Record<string, string>>((acc, item) => {
        acc[item.href] = item.title
        return acc
      }, {})
  }, [navGroups])

  const rootHref = navMode === 'tenant'
    ? `/superadmin/tenant/${tenantSlug}/dashboard`
    : '/superadmin/platform/stores'

  return (
    <>
      <header className={cn(
        'sticky top-0 z-40 flex h-12 items-center gap-4 px-4',
        'border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60',
      )}>
        {/* Breadcrumbs */}
        <div className="flex-1 min-w-0">
          <Breadcrumbs navItemLabels={navItemLabels} rootHref={rootHref} />
        </div>

        <div className="hidden md:flex items-center gap-2">
          {/* Tenant Switcher Button */}
          {selectedTenantId && (
            <Button
              variant="outline"
              size="sm"
              onClick={() => setTenantSwitcherOpen(true)}
              className="h-7 text-xs flex items-center gap-1.5 px-2"
              title="Mudar empresa"
            >
              <Zap className="w-3 h-3 text-amber-500" />
              <span className="truncate max-w-[120px]">{tenantName}</span>
            </Button>
          )}

          {navMode === 'platform' && (
            <>
              <label htmlFor="global-company-filter" className="sr-only">Filtro global de loja</label>
              <select
                id="global-company-filter"
                value={selectedCompanyId ?? ''}
                onChange={(e) => {
                  const nextValue = e.target.value
                  setSelectedCompanyId(nextValue === '' ? null : Number(nextValue))
                }}
                className="h-7 rounded-md border border-input bg-background px-2 text-xs"
              >
                <option value="">Todas as lojas</option>
                {storeItems.map((store) => (
                  <option key={store.id} value={store.id}>{store.name}</option>
                ))}
              </select>
            </>
          )}
        </div>

        {/* Actions */}
        <div className="flex items-center gap-1.5">
          {/* Command menu trigger */}
          <Button
            variant="outline"
            size="sm"
            onClick={() => setCommandMenuOpen(true)}
            className="hidden sm:flex items-center gap-2 h-7 text-xs text-muted-foreground px-2"
            aria-label="Abrir menu de comandos"
          >
            <Search className="h-3 w-3" />
            <span>Buscar...</span>
            <kbd className="pointer-events-none ml-1 flex h-5 select-none items-center gap-0.5 rounded border bg-muted px-1.5 font-mono text-[10px] opacity-100">
              <span>⌘K</span>
            </kbd>
          </Button>

          <ThemeToggle />
          <UserMenu />
        </div>
      </header>

      {/* Tenant Switcher Modal */}
      <TenantSwitcher open={tenantSwitcherOpen} onOpenChange={setTenantSwitcherOpen} />
    </>
  )
})
