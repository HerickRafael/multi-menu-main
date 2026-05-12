import { create } from 'zustand'
import { persist, createJSONStorage } from 'zustand/middleware'

interface CompanyFilterStore {
  selectedCompanyId: number | null
  setSelectedCompanyId: (companyId: number | null) => void
  clearSelectedCompanyId: () => void
}

export const useCompanyFilterStore = create<CompanyFilterStore>()(
  persist(
    (set) => ({
      selectedCompanyId: null,
      setSelectedCompanyId: (companyId) => set({ selectedCompanyId: companyId }),
      clearSelectedCompanyId: () => set({ selectedCompanyId: null }),
    }),
    {
      name: 'super-admin-company-filter',
      storage: createJSONStorage(() => localStorage),
      partialize: (state) => ({
        selectedCompanyId: state.selectedCompanyId,
      }),
    },
  ),
)
