import { Link, Outlet } from 'react-router-dom'
import { AppSidebar } from '@/components/layout/AppSidebar'
import { AppHeader } from '@/components/layout/AppHeader'
import { CommandMenu } from '@/components/layout/CommandMenu'
import { useTenant } from '@/contexts/TenantContext'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'

/**
 * TenantLayout - Tenant/operational layout (Tenant routes)
 * Used for: Orders, Users, WhatsApp, Queues, Dashboard, etc.
 * Shows tenant-scoped navigation with capabilities-based sidebar
 * Only accessible when tenant context is active
 */
export function TenantLayout() {
  const { tenantName, selectedTenantId, selectedTenantSlug } = useTenant()
  const tenantSlug = selectedTenantSlug || 'select-tenant'

  return (
    <div className="flex h-screen overflow-hidden bg-background">
      {/* Tenant Sidebar - Shows capabilities-based menu */}
      <AppSidebar mode="tenant" />

      {/* Main content */}
      <div className="flex flex-1 flex-col min-w-0 overflow-hidden">
        <AppHeader />

        {/* Page content - with tenant context visible in header */}
        <main className="flex-1 overflow-y-auto">
          {/* Optional: Tenant info banner for clarity */}
          {selectedTenantId && (
            <div className="sticky top-0 z-10 border-b bg-muted/30 px-4 py-2 text-xs text-muted-foreground">
              <div className="flex flex-wrap items-center justify-between gap-2">
                <div className="flex flex-wrap items-center gap-2">
                  <Badge variant="secondary">Super Admin</Badge>
                  <span>
                    Operando em: <span className="font-semibold text-foreground">{tenantName || `Tenant #${selectedTenantId}`}</span>
                  </span>
                  <span className="text-[10px] text-muted-foreground">
                    ID: {selectedTenantId} • @{tenantSlug}
                  </span>
                </div>
                <div className="flex items-center gap-2">
                  <Button asChild variant="outline" size="sm" className="h-7 text-xs">
                    <Link to="/superadmin/platform/stores">Voltar ao Super Admin</Link>
                  </Button>
                </div>
              </div>
            </div>
          )}
          <Outlet />
        </main>
      </div>

      {/* Global Command Menu */}
      <CommandMenu />
    </div>
  )
}
