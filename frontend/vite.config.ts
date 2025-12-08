import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'
import path from 'path'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react(), tailwindcss()],
  server: {
    host: '0.0.0.0', // Allow access from network
    port: 5173,
    strictPort: true,
    watch: {
      usePolling: true, // Required for Docker file system watching
      interval: 1000,
    },
    hmr: false, // Disable HMR in Docker (use manual refresh)
    allowedHosts: [
      'snuffiest-nydia-egregious.ngrok-free.dev',
      '.ngrok-free.dev',
      '.ngrok.io',
      'localhost',
      'nginx',
    ],
    proxy: {
      '/api': {
        target: 'http://nginx',
        changeOrigin: true,
      },
      '/nominatim': {
        target: 'http://nginx',
        changeOrigin: true,
      },
    },
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
      '@attendance/shared': path.resolve(__dirname, '../shared'),
    },
  },
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          'react-vendor': ['react', 'react-dom'],
          'router': ['@tanstack/react-router'],
          'query': ['@tanstack/react-query'],
          'charts': ['recharts'],
          'face-detection': ['face-api.js', '@tensorflow/tfjs-core', '@tensorflow/tfjs-backend-webgl'],
          'ui': [
            '@radix-ui/react-dialog',
            '@radix-ui/react-dropdown-menu',
            '@radix-ui/react-select',
            '@radix-ui/react-tabs',
            '@radix-ui/react-tooltip',
          ],
        },
      },
    },
  },
})
// Force restart 2
