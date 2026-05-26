import path from 'path'
import { defineConfig } from 'vite'
import type { Plugin } from 'vite'
import react from '@vitejs/plugin-react'

const superadminHistoryFallback: Plugin = {
  name: 'superadmin-history-fallback',
  apply: 'serve',
  configureServer(server: any) {
    server.middlewares.use((req: any, res: any, next: any) => {
      void res
      const url = req.url ?? ''
      const isSuperadminRoute = url.startsWith('/superadmin')
      const isAssetRequest = /\.(js|css|json|svg|png|jpg|gif|ico|webp|woff2?)$/i.test(url)

      if (isSuperadminRoute && !isAssetRequest) {
        req.url = '/superadmin/index.html'
      }

      next()
    })
  },
}

export default defineConfig({
  base: '/superadmin/',
  plugins: [react(), superadminHistoryFallback],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src/resources'),
    },
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:8088',
        changeOrigin: true,
        secure: false,
      },
      // Don't proxy /superadmin/* - let Vite serve the SPA instead
      // The React Router will handle all /superadmin/* routes
    },
  },
  build: {
    outDir: '../public/superadmin',
    emptyOutDir: true,
    chunkSizeWarningLimit: 1000,
  },
})
