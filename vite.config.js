import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/js/app.ts',
                'resources/js/calendar-patra.js'
            ],
            refresh: true,
        }),
        vue(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            'vue': 'vue/dist/vue.esm-bundler.js',
        },
    },
    build: {
        // Optimize bundle splitting
        rollupOptions: {
            output: {
                manualChunks(id) {
                    // Vendor libraries
                    if (id.includes('node_modules')) {
                        if (id.includes('vue') || id.includes('pinia') || id.includes('@vueuse')) {
                            return 'vendor-vue'
                        }
                        if (id.includes('@headlessui') || id.includes('@heroicons') || id.includes('lucide-vue-next')) {
                            return 'vendor-ui'
                        }
                        if (id.includes('chart.js') || id.includes('chartjs-adapter')) {
                            return 'vendor-charts'
                        }
                        if (id.includes('face-api') || id.includes('@mediapipe')) {
                            return 'vendor-face'
                        }
                        if (id.includes('axios') || id.includes('date-fns')) {
                            return 'vendor-utils'
                        }
                        // Other vendor dependencies
                        return 'vendor'
                    }
                    
                    // Component chunks based on actual files
                    if (id.includes('components/Face') || id.includes('components/face')) {
                        return 'components-face'
                    }
                    if (id.includes('components/Schedule') || id.includes('components/Jadwal')) {
                        return 'components-schedule'
                    }
                    if (id.includes('components/Security') || id.includes('components/Device')) {
                        return 'components-admin'
                    }
                }
            }
        },
        
        // Enable source maps for debugging (disable in production)
        sourcemap: process.env.NODE_ENV === 'development',
        
        // Optimize chunk size warnings
        chunkSizeWarningLimit: 1000,
        
        // Enable CSS code splitting
        cssCodeSplit: true,
        
        // Minification options
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: process.env.NODE_ENV === 'production',
                drop_debugger: process.env.NODE_ENV === 'production',
            },
        },
        
        // Target modern browsers for smaller bundle
        target: 'es2020',
    },
    
    // Optimize dependencies
    optimizeDeps: {
        include: [
            'vue',
            'pinia',
            '@vueuse/core',
            '@headlessui/vue',
            '@heroicons/vue/24/outline',
            'axios',
            'date-fns'
        ],
        exclude: [
            // Large dependencies that should be loaded on demand
            'face-api.js',
            '@mediapipe/face_detection',
            '@mediapipe/face_mesh',
            'chart.js'
        ]
    },
    
    // Preview/dev server optimizations
    server: {
        fs: {
            allow: ['..']
        }
    }
});
