import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],
  test: {
    globals: true,
    environment: 'happy-dom',
    setupFiles: ['./resources/js/tests/setup.ts'],
    include: ['resources/js/**/*.{test,spec}.{js,ts,vue}'],
    exclude: ['node_modules', 'vendor', 'storage', 'bootstrap'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html'],
      exclude: [
        'node_modules/',
        'resources/js/tests/',
        '**/*.d.ts',
        '**/*.config.*'
      ]
    }
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, 'resources/js'),
      '@/components': resolve(__dirname, 'resources/js/components'),
      '@/services': resolve(__dirname, 'resources/js/services'),
      '@/composables': resolve(__dirname, 'resources/js/composables'),
      '@/utils': resolve(__dirname, 'resources/js/utils'),
      '@/types': resolve(__dirname, 'resources/js/types')
    }
  }
})