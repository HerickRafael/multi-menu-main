import { Providers } from '@/js/providers'
import { AppRouter } from '@/js/router'
import { ToastContainer } from '@/components/admin-store'

export default function App() {
  return (
    <Providers>
      <AppRouter />
      <ToastContainer />
    </Providers>
  )
}
