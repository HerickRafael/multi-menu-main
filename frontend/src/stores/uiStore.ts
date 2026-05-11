import { create } from 'zustand'
import { persist, createJSONStorage } from 'zustand/middleware'

type Theme = 'dark' | 'light' | 'system'

interface UIStore {
  // Sidebar
  sidebarCollapsed: boolean
  toggleSidebar: () => void
  setSidebarCollapsed: (collapsed: boolean) => void

  // Theme
  theme: Theme
  setTheme: (theme: Theme) => void

  // Command menu
  commandMenuOpen: boolean
  setCommandMenuOpen: (open: boolean) => void
  toggleCommandMenu: () => void

  // Active loading states
  globalLoading: boolean
  setGlobalLoading: (loading: boolean) => void

  // Notifications badge count
  notificationCount: number
  setNotificationCount: (count: number) => void
}

export const useUIStore = create<UIStore>()(
  persist(
    (set, get) => ({
      sidebarCollapsed: false,
      toggleSidebar: () => set((s) => ({ sidebarCollapsed: !s.sidebarCollapsed })),
      setSidebarCollapsed: (collapsed) => set({ sidebarCollapsed: collapsed }),

      theme: 'dark',
      setTheme: (theme) => set({ theme }),

      commandMenuOpen: false,
      setCommandMenuOpen: (open) => set({ commandMenuOpen: open }),
      toggleCommandMenu: () => set((s) => ({ commandMenuOpen: !s.commandMenuOpen })),

      globalLoading: false,
      setGlobalLoading: (loading) => set({ globalLoading: loading }),

      notificationCount: 0,
      setNotificationCount: (count) => set({ notificationCount: count }),
    }),
    {
      name: 'super-admin-ui',
      storage: createJSONStorage(() => localStorage),
      partialize: (state) => ({
        sidebarCollapsed: state.sidebarCollapsed,
        theme: state.theme,
      }),
    },
  ),
)
