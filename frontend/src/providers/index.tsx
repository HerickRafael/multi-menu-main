import type { ReactNode } from 'react'
import { Toaster } from 'sonner'
import { QueryProvider } from './QueryProvider'
import { ThemeProvider } from './ThemeProvider'
import { TenantContextProvider } from '@/contexts/TenantContext'
import { RealtimeContextProvider } from '@/contexts/RealtimeContext'

export function Providers({ children }: { children: ReactNode }) {
  return (
    <QueryProvider>
      <ThemeProvider>
        <TenantContextProvider>
          <RealtimeContextProvider>
            {children}
            <Toaster
              position="top-right"
              richColors
              expand={false}
              duration={4000}
              closeButton
            />
          </RealtimeContextProvider>
        </TenantContextProvider>
      </ThemeProvider>
    </QueryProvider>
  )
}
