import path from 'path'
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  base: '/superadmin/',
  plugins: [react()],
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
      '/superadmin': {
        target: 'http://localhost:8088',
        changeOrigin: true,
        secure: false,
      },
    },
  },
  build: {
    outDir: '../public/superadmin',
    emptyOutDir: true,
    chunkSizeWarningLimit: 1000,
  },
})
