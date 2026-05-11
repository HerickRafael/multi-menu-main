import { PlatformLayout } from './PlatformLayout'
import { TenantLayout } from './TenantLayout'
import { useLocation } from 'react-router-dom'

/**
 * AppShell - Conditional layout renderer
 * Automatically renders PlatformLayout or TenantLayout based on tenant context
 * This component is placed at the parent route level and wraps all child routes
 */
export function AppShell() {
  const location = useLocation()
  const pathname = location.pathname

  if (pathname.startsWith('/superadmin/tenant/')) {
    return <TenantLayout />
  }

  return <PlatformLayout />
}
