import { create } from 'zustand'
import { persist, createJSONStorage } from 'zustand/middleware'
import type { AuthUser } from '@/js/types/auth'

function normalizeAuthUser(value: unknown): AuthUser | null {
  if (!value || typeof value !== 'object') {
    return null
  }

  const raw = value as Partial<AuthUser>
  if (typeof raw.id !== 'number') return null
  if (typeof raw.name !== 'string' || raw.name.trim() === '') return null
  if (typeof raw.email !== 'string' || raw.email.trim() === '') return null
  if (typeof raw.role !== 'string' || raw.role.trim() === '') return null

  return {
    id: raw.id,
    name: raw.name,
    email: raw.email,
    role: raw.role,
    avatar_url: typeof raw.avatar_url === 'string' ? raw.avatar_url : undefined,
    permissions: Array.isArray(raw.permissions) ? raw.permissions : [],
    company_id: typeof raw.company_id === 'number' || raw.company_id === null ? raw.company_id : null,
    is_super_admin: raw.is_super_admin === true || raw.role === 'root',
    last_login_at: typeof raw.last_login_at === 'string' ? raw.last_login_at : undefined,
  }
}

interface AuthStore {
  token: string | null
  user: AuthUser | null
  isAuthenticated: boolean
  setAuth: (token: string, user: AuthUser) => void
  setUser: (user: AuthUser) => void
  logout: () => void
  hasPermission: (permission: string) => boolean
  hasAnyPermission: (permissions: string[]) => boolean
  hasAllPermissions: (permissions: string[]) => boolean
}

export const useAuthStore = create<AuthStore>()(
  persist(
    (set, get) => ({
      token: null,
      user: null,
      isAuthenticated: false,

      setAuth: (token, user) => {
        const safeUser = normalizeAuthUser(user)
        set({ token, user: safeUser, isAuthenticated: !!token && !!safeUser })
      },

      setUser: (user) => {
        const safeUser = normalizeAuthUser(user)
        set({ user: safeUser, isAuthenticated: !!get().token && !!safeUser })
      },

      logout: () => {
        set({ token: null, user: null, isAuthenticated: false })
      },

      hasPermission: (permission: string) => {
        const { user } = get()
        if (!user) return false
        if (user.is_super_admin || user.role === 'root') return true
        return Array.isArray(user.permissions) && user.permissions.includes(permission)
      },

      hasAnyPermission: (permissions: string[]) => {
        const { user } = get()
        if (!user) return false
        if (user.is_super_admin || user.role === 'root') return true
        if (!Array.isArray(user.permissions)) return false
        return permissions.some(p => user.permissions.includes(p))
      },

      hasAllPermissions: (permissions: string[]) => {
        const { user } = get()
        if (!user) return false
        if (user.is_super_admin || user.role === 'root') return true
        if (!Array.isArray(user.permissions)) return false
        return permissions.every(p => user.permissions.includes(p))
      },
    }),
    {
      name: 'super-admin-auth',
      version: 2,
      storage: createJSONStorage(() => localStorage),
      migrate: (persistedState) => {
        const state = persistedState as Partial<AuthStore> | undefined
        const safeUser = normalizeAuthUser(state?.user)
        const safeToken = typeof state?.token === 'string' && state.token.trim() !== '' ? state.token : null

        return {
          token: safeToken,
          user: safeUser,
          isAuthenticated: !!safeToken && !!safeUser,
        }
      },
      partialize: (state) => ({
        token: state.token,
        user: state.user,
        isAuthenticated: state.isAuthenticated,
      }),
    },
  ),
)
