import { useAuthStore } from '@/js/stores/authStore'

export function usePermissions() {
  const { user, hasPermission, hasAnyPermission, hasAllPermissions } = useAuthStore()

  const isSuperAdmin = user?.is_super_admin || user?.role === 'root'

  return {
    user,
    isSuperAdmin,
    can: hasPermission,
    canAny: hasAnyPermission,
    canAll: hasAllPermissions,
    permissions: user?.permissions ?? [],
  }
}
