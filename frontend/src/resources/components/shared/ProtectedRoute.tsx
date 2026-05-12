import { Navigate, useLocation } from 'react-router-dom'
import type { ReactNode } from 'react'
import { useAuthStore } from '@/js/stores/authStore'

interface ProtectedRouteProps {
  children: ReactNode
  permission?: string
  permissions?: string[]
  requireAll?: boolean
}

export function ProtectedRoute({ children, permission, permissions, requireAll = false }: ProtectedRouteProps) {
  const { isAuthenticated, hasPermission, hasAnyPermission, hasAllPermissions } = useAuthStore()
  const location = useLocation()

  if (!isAuthenticated) {
    return <Navigate to="/superadmin/login" state={{ from: location }} replace />
  }

  // Permission check
  if (permission && !hasPermission(permission)) {
    return <Navigate to="/superadmin/platform/stores" replace />
  }

  if (permissions?.length) {
    const allowed = requireAll
      ? hasAllPermissions(permissions)
      : hasAnyPermission(permissions)
    if (!allowed) {
      return <Navigate to="/superadmin/platform/stores" replace />
    }
  }

  return <>{children}</>
}

interface PermissionGuardProps {
  permission?: string
  permissions?: string[]
  requireAll?: boolean
  children: ReactNode
  fallback?: ReactNode
}

export function PermissionGuard({ permission, permissions, requireAll = false, children, fallback = null }: PermissionGuardProps) {
  const { hasPermission, hasAnyPermission, hasAllPermissions } = useAuthStore()

  if (permission && !hasPermission(permission)) return <>{fallback}</>

  if (permissions?.length) {
    const allowed = requireAll ? hasAllPermissions(permissions) : hasAnyPermission(permissions)
    if (!allowed) return <>{fallback}</>
  }

  return <>{children}</>
}
