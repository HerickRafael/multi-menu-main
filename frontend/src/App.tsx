import { Providers } from '@/js/providers'
import { AppRouter } from '@/js/router'

export default function App() {
  return (
    <Providers>
      <AppRouter />
    </Providers>
  )
}
