import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { ReactQueryDevtools } from '@tanstack/react-query-devtools'
import type { ReactNode } from 'react'
import { STALE_TIME_MS } from '@/js/lib/constants'

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: STALE_TIME_MS,
      gcTime: 5 * 60 * 1000, // 5 minutes
      retry: (failureCount, error) => {
        const axiosLikeError = error as { response?: { status?: number } }
        // Don't retry on 401/403/404
        const status = axiosLikeError?.response?.status
        if (status === 401 || status === 403 || status === 404) return false
        return failureCount < 2
      },
      refetchOnWindowFocus: false,
      refetchOnReconnect: true,
    },
    mutations: {
      retry: 0,
    },
  },
})

interface QueryProviderProps {
  children: ReactNode
}

export function QueryProvider({ children }: QueryProviderProps) {
  return (
    <QueryClientProvider client={queryClient}>
      {children}
      {import.meta.env.DEV && <ReactQueryDevtools initialIsOpen={false} />}
    </QueryClientProvider>
  )
}

export { queryClient }
