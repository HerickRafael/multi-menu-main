import { memo, useState } from 'react'
import { Link } from 'react-router-dom'
import { Menu, X, Search, LogOut, User, Settings, Moon, Sun, Monitor, Bell, HelpCircle, ChevronRight } from 'lucide-react'
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
import { useAuth } from '@/js/hooks/useAuth'
import { useTheme } from '@/js/providers/ThemeProvider'
import { useTenant } from '@/js/contexts/TenantContext'
import { TenantSwitcher } from '@/modules/tenant/components/TenantSwitcher'
import { cn } from '@/js/lib/utils'


// ─── Logo + Brand Section ────────────────────────────────────────────────────
function BrandSection({ isCollapsed }: { isCollapsed: boolean }) {
  return (
    <Link 
      to="/superadmin/platform/stores" 
      className="flex items-center gap-2.5 hover:opacity-80 transition-opacity py-2"
    >
      <div className="flex-shrink-0 w-7 h-7 rounded-lg bg-primary flex items-center justify-center">
        <span className="text-primary-foreground font-bold text-xs">MM</span>
      </div>
      {!isCollapsed && (
        <div className="hidden sm:flex flex-col">
          <span className="text-sm font-semibold text-foreground leading-none">Multi Menu</span>
          <span className="text-[10px] text-muted-foreground leading-none">Admin</span>
        </div>
      )}
    </Link>
  )
}


// ─── Theme Toggle ─────────────────────────────────────────────────────────────
function ThemeToggle() {
  const { theme, setTheme } = useTheme()
  const cycles: Array<typeof theme> = ['light', 'dark', 'system']
  const next = cycles[(cycles.indexOf(theme) + 1) % cycles.length]
  const Icon = theme === 'dark' ? Moon : theme === 'light' ? Sun : Monitor

  return (
    <Button
      variant="ghost"
      size="sm"
      className="h-8 w-8 p-0"
      onClick={() => setTheme(next)}
      title={`Tema: ${theme}`}
      aria-label={`Mudar tema (atual: ${theme})`}
    >
      <Icon className="h-4 w-4" />
    </Button>
  )
}

// ─── Help Button ──────────────────────────────────────────────────────────────
function HelpButton() {
  return (
    <Button
      variant="ghost"
      size="sm"
      className="h-8 w-8 p-0"
      title="Ajuda"
      aria-label="Abrir ajuda"
    >
      <HelpCircle className="h-4 w-4" />
    </Button>
  )
}

// ─── Notifications Button ─────────────────────────────────────────────────────
function NotificationsButton() {
  return (
    <Button
      variant="ghost"
      size="sm"
      className="h-8 w-8 p-0 relative"
      title="Notificações"
      aria-label="Abrir notificações"
    >
      <Bell className="h-4 w-4" />
      <span className="absolute top-1 right-1 h-2 w-2 rounded-full bg-destructive animate-pulse" />
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
          className="text-destructive focus:text-destructive cursor-pointer"
          onClick={() => logout()}
          disabled={isLoggingOut}
        >
          <LogOut className="h-4 w-4 mr-2" />
          {isLoggingOut ? 'Saindo…' : 'Sair'}
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  )
}

// ─── Main App Header (iFood Style) ────────────────────────────────────────────
export const AppHeader = memo(function AppHeader() {
  const [tenantSwitcherOpen, setTenantSwitcherOpen] = useState(false)
  const sidebarCollapsed = useUIStore((state) => state.sidebarCollapsed)
  const toggleSidebar = useUIStore((state) => state.toggleSidebar)
  const setCommandMenuOpen = useUIStore((state) => state.setCommandMenuOpen)
  const { selectedTenantId, tenantName } = useTenant()

  return (
    <>
      <header className={cn(
        'sticky top-0 z-40 h-12 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60',
        'border-b border-border flex items-center gap-3 px-3 sm:px-4',
      )}>
        {/* Left: Sidebar Toggle + Brand Logo */}
        <div className="flex items-center gap-2">
          <Button
            variant="ghost"
            size="sm"
            className="h-8 w-8 p-0 lg:hidden"
            onClick={toggleSidebar}
            title={sidebarCollapsed ? 'Abrir menu' : 'Fechar menu'}
            aria-label="Menu"
          >
            {sidebarCollapsed ? <Menu className="h-5 w-5" /> : <X className="h-5 w-5" />}
          </Button>
          <BrandSection isCollapsed={sidebarCollapsed} />
        </div>

        {/* Center: Tenant Switcher (Desktop only) */}
        <div className="hidden md:flex flex-1 items-center justify-center gap-2">
          {selectedTenantId && (
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setTenantSwitcherOpen(true)}
              className="h-8 text-xs flex items-center gap-2 px-3 hover:bg-accent"
              title="Mudar empresa"
            >
              <span className="h-2 w-2 rounded-full bg-green-500 flex-shrink-0" />
              <span className="truncate max-w-[200px] font-medium">{tenantName}</span>
              <ChevronRight className="h-3 w-3 opacity-50 flex-shrink-0" />
            </Button>
          )}
        </div>

        {/* Right: Actions */}
        <div className="ml-auto flex items-center gap-1">
          {/* Search */}
          <Button
            variant="ghost"
            size="sm"
            onClick={() => setCommandMenuOpen(true)}
            className="h-8 w-8 p-0"
            title="Buscar (⌘K)"
            aria-label="Buscar"
          >
            <Search className="h-4 w-4" />
          </Button>

          {/* Help */}
          <HelpButton />

          {/* Notifications */}
          <NotificationsButton />

          {/* Theme Toggle */}
          <ThemeToggle />

          {/* User Menu */}
          <UserMenu />
        </div>
      </header>

      {/* Tenant Switcher Modal */}
      <TenantSwitcher open={tenantSwitcherOpen} onOpenChange={setTenantSwitcherOpen} />
    </>
  )
})
