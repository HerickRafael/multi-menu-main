import { Outlet } from 'react-router-dom'
import { AppSidebar } from '@/components/layout/AppSidebar'
import { AppHeader } from '@/components/layout/AppHeader'
import { CommandMenu } from '@/components/layout/CommandMenu'

/**
 * PlatformLayout - Global admin layout (Platform routes)
 * Used for: Stores (tenant management), Monitoring, Audit, System, Settings, etc.
 * Shows global navigation and platform-scoped sidebar
 */
export function PlatformLayout() {
  return (
    <div className="flex h-screen overflow-hidden bg-background">
      {/* Platform Sidebar */}
      <AppSidebar mode="platform" />

      {/* Main content */}
      <div className="flex flex-1 flex-col min-w-0 overflow-hidden">
        <AppHeader />

        {/* Page content */}
        <main className="flex-1 overflow-y-auto">
          <Outlet />
        </main>
      </div>

      {/* Global Command Menu */}
      <CommandMenu />
    </div>
  )
}
