import { memo, useCallback, useState } from 'react'
import { NavLink, useLocation } from 'react-router-dom'
import { ChevronLeft, ChevronRight, type LucideIcon, ChevronDown } from 'lucide-react'
import { cn } from '@/js/lib/utils'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Separator } from '@/components/ui/separator'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { useUIStore } from '@/js/stores/uiStore'
import { PLATFORM_NAV_GROUPS, TENANT_NAV_GROUPS, type NavItem } from '@/js/lib/constants'
import { usePermissions } from '@/js/hooks/usePermissions'
import { useTenant } from '@/js/contexts/TenantContext'
import { useAuthStore } from '@/js/stores/authStore'

// ─── Brand Logo ─────────────────────────────────────────────────────────────
function SidebarBrand({ collapsed }: { collapsed: boolean }) {
  return (
    <div className={cn(
      'flex items-center gap-2 px-3 py-4 border-b border-sidebar-border',
      collapsed ? 'justify-center px-2' : 'px-4',
    )}>
      <div className="flex-shrink-0 w-8 h-8 rounded-lg bg-sidebar-primary flex items-center justify-center">
        <span className="text-sidebar-primary-foreground font-bold text-sm">SA</span>
      </div>
      {!collapsed && (
        <div className="overflow-hidden">
          <p className="text-sm font-semibold text-sidebar-foreground truncate">Super Admin</p>
          <p className="text-xs text-muted-foreground truncate">Multi Menu Platform</p>
        </div>
      )}
    </div>
  )
}

// ─── Nav Item ────────────────────────────────────────────────────────────────
interface NavItemProps {
  item: NavItem
  collapsed: boolean
  depth?: number
  canAccess: (permission?: string) => boolean
}

const SidebarNavItem = memo(function SidebarNavItem({ item, collapsed, depth = 0, canAccess }: NavItemProps) {
  const location = useLocation()
  const [open, setOpen] = useState(false)

  const hasPermission = canAccess(item.permission)
  if (!hasPermission) return null

  const isActive = location.pathname === item.href || location.pathname.startsWith(item.href + '/')
  const hasChildren = item.children && item.children.length > 0
  const Icon: LucideIcon = item.icon

  const content = (
    <div className={cn(
      'flex items-center gap-2.5 w-full min-w-0',
      collapsed && 'justify-center',
    )}>
      <Icon className={cn(
        'shrink-0 transition-colors',
        depth === 0 ? 'h-4 w-4' : 'h-3.5 w-3.5',
        isActive ? 'text-sidebar-primary' : 'text-muted-foreground',
      )} />
      {!collapsed && (
        <>
          <span className="flex-1 truncate text-sm">{item.title}</span>
          {item.badge && (
            <span className={cn(
              'text-[10px] font-semibold px-1.5 py-0.5 rounded-full',
              item.badgeVariant === 'destructive'
                ? 'bg-destructive/15 text-destructive'
                : 'bg-muted text-muted-foreground',
            )}>
              {item.badge}
            </span>
          )}
          {hasChildren && (
            <ChevronDown className={cn(
              'h-3.5 w-3.5 shrink-0 text-muted-foreground transition-transform duration-200',
              open && 'rotate-180',
            )} />
          )}
        </>
      )}
    </div>
  )

  const itemClass = cn(
    'flex items-center w-full rounded-md px-2.5 py-2 text-sm font-medium outline-none',
    'transition-colors duration-150',
    'hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
    'focus-visible:ring-1 focus-visible:ring-sidebar-ring',
    isActive && 'bg-sidebar-accent text-sidebar-accent-foreground',
    collapsed ? 'justify-center px-2' : '',
    depth > 0 && 'pl-8 text-xs',
  )

  if (hasChildren) {
    return (
      <div>
        <button
          className={itemClass}
          onClick={() => setOpen(o => !o)}
          aria-expanded={open}
        >
          {content}
        </button>
        {open && !collapsed && (
          <div className="mt-0.5 space-y-0.5">
            {item.children!.map(child => (
              <SidebarNavItem key={child.href} item={child} collapsed={collapsed} depth={depth + 1} canAccess={canAccess} />
            ))}
          </div>
        )}
      </div>
    )
  }

  const link = (
    <NavLink to={item.href} className={itemClass} end={item.href.split('/').length <= 3}>
      {content}
    </NavLink>
  )

  if (collapsed) {
    return (
      <Tooltip delayDuration={0}>
        <TooltipTrigger asChild>{link}</TooltipTrigger>
        <TooltipContent side="right" className="flex items-center gap-4">
          {item.title}
          {item.badge && <span className="text-xs text-muted-foreground">{item.badge}</span>}
        </TooltipContent>
      </Tooltip>
    )
  }

  return link
})

// ─── Main Sidebar ────────────────────────────────────────────────────────────
interface AppSidebarProps {
  mode?: 'platform' | 'tenant'
}

export const AppSidebar = memo(function AppSidebar({ mode = 'platform' }: AppSidebarProps) {
  const sidebarCollapsed = useUIStore((state) => state.sidebarCollapsed)
  const toggleSidebar = useUIStore((state) => state.toggleSidebar)
  const { can } = usePermissions()
  const { selectedTenantSlug, permissions: tenantPermissions } = useTenant()
  const isSuperAdmin = useAuthStore((state) => state.user?.is_super_admin || state.user?.role === 'root')

  const canAccess = useCallback((permission?: string): boolean => {
    if (!permission) return true

    if (isSuperAdmin) {
      return true
    }

    // In tenant mode, prioritize tenant-scoped capabilities hydrated by context switch.
    if (mode === 'tenant' && tenantPermissions.length > 0) {
      return tenantPermissions.includes(permission)
    }

    // Fallback to global auth permissions (platform mode or empty tenant capabilities).
    return can(permission)
  }, [can, isSuperAdmin, mode, tenantPermissions])

  // Select navigation groups based on mode
  const navGroups = mode === 'tenant' ? TENANT_NAV_GROUPS : PLATFORM_NAV_GROUPS

  // Compute navigation items with tenant slug interpolation if needed
  const computedItems = navGroups.map((group) => ({
    ...group,
    items: group.items.map((item) => ({
      ...item,
      href: item.href.replace(':slug', selectedTenantSlug || 'select-tenant'),
    })),
  }))

  return (
    <TooltipProvider>
      <aside
        className={cn(
          'relative flex flex-col h-screen bg-sidebar border-r border-sidebar-border sidebar-transition',
          'flex-shrink-0 overflow-hidden',
          sidebarCollapsed ? 'w-[56px]' : 'w-[240px]',
        )}
      >
        {/* Brand */}
        <SidebarBrand collapsed={sidebarCollapsed} />

        {/* Navigation */}
        <ScrollArea className="flex-1 py-2">
          <nav className="space-y-4 px-2">
            {computedItems.map((group) => (
              <div key={group.label}>
                {!sidebarCollapsed && (
                  <p className="mb-1 px-2 text-[10px] font-semibold uppercase tracking-widest text-muted-foreground/60">
                    {group.label}
                  </p>
                )}
                {sidebarCollapsed && (
                  <Separator className="my-1 mx-1 bg-sidebar-border" />
                )}
                <div className="space-y-0.5">
                  {group.items.map((item) => (
                    <SidebarNavItem
                      key={item.href}
                      item={item}
                      collapsed={sidebarCollapsed}
                      canAccess={canAccess}
                    />
                  ))}
                </div>
              </div>
            ))}
          </nav>
        </ScrollArea>

        {/* Collapse toggle */}
        <div className="border-t border-sidebar-border p-2">
          <button
            onClick={toggleSidebar}
            className={cn(
              'flex w-full items-center rounded-md p-2 text-xs text-muted-foreground',
              'hover:bg-sidebar-accent hover:text-sidebar-accent-foreground transition-colors',
              sidebarCollapsed ? 'justify-center' : 'gap-2',
            )}
            aria-label={sidebarCollapsed ? 'Expandir sidebar' : 'Recolher sidebar'}
          >
            {sidebarCollapsed
              ? <ChevronRight className="h-4 w-4" />
              : <><ChevronLeft className="h-4 w-4" /><span>Recolher</span></>
            }
          </button>
        </div>
      </aside>
    </TooltipProvider>
  )
})
