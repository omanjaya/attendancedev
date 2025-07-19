@extends('layouts.authenticated-unified')

@section('title', 'Demo Status Loading & Kinerja')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <!-- Performance Monitor -->
    <x-ui.performance.monitor 
        :enabled="true" 
        :show-debug="true" 
        :track-page-load="true" 
        :track-user-interactions="true" 
        :track-resource-timing="true" />

    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Demo Status Loading & Kinerja"
            subtitle="Komponen loading komprehensif dengan pemantauan kinerja dan optimasi"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Demo'],
                ['label' => 'Kinerja']
            ]">
        </x-layouts.base-page>

        <!-- Spinner Showcase -->
        <section class="mb-8">
            <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">
                Spinner Loading
            </h2>
            
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Spin Types -->
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Tipe Spinner</h3>
                        <div class="space-y-4">
                            <div class="flex items-center space-x-4">
                                <x-ui.loading.spinner type="spin" size="md" color="emerald" />
                                <span class="text-sm text-slate-600 dark:text-slate-400">Spin</span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <x-ui.loading.spinner type="dots" size="md" color="emerald" />
                                <span class="text-sm text-slate-600 dark:text-slate-400">Dots</span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <x-ui.loading.spinner type="bars" size="md" color="emerald" />
                                <span class="text-sm text-slate-600 dark:text-slate-400">Bars</span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <x-ui.loading.spinner type="ring" size="md" color="emerald" />
                                <span class="text-sm text-slate-600 dark:text-slate-400">Ring</span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <x-ui.loading.spinner type="pulse" size="md" color="emerald" />
                                <span class="text-sm text-slate-600 dark:text-slate-400">Pulse</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sizes -->
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Ukuran Spinner</h3>
                        <div class="space-y-4">
                            <div class="flex items-center space-x-4">
                                <x-ui.loading.spinner type="spin" size="xs" color="emerald" />
                                <span class="text-sm text-slate-600 dark:text-slate-400">Ekstra Kecil</span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <x-ui.loading.spinner type="spin" size="sm" color="emerald" />
                                <span class="text-sm text-slate-600 dark:text-slate-400">Kecil</span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <x-ui.loading.spinner type="spin" size="md" color="emerald" />
                                <span class="text-sm text-slate-600 dark:text-slate-400">Sedang</span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <x-ui.loading.spinner type="spin" size="lg" color="emerald" />
                                <span class="text-sm text-slate-600 dark:text-slate-400">Besar</span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <x-ui.loading.spinner type="spin" size="xl" color="emerald" />
                                <span class="text-sm text-slate-600 dark:text-slate-400">Ekstra Besar</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- With Text -->
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Dengan Teks</h3>
                        <div class="space-y-4">
                            <x-ui.loading.spinner type="spin" size="md" color="emerald" text="Memuat..." />
                            <x-ui.loading.spinner type="dots" size="md" color="blue" text="Memproses..." />
                            <x-ui.loading.spinner type="bars" size="md" color="gray" text="Mengunggah..." />
                            <div class="pt-4">
                                <x-ui.loading.spinner type="spin" size="lg" color="emerald" text="Mohon tunggu" centered="true" />
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Overlay Demo -->
                <div class="mt-8 pt-8 border-t border-white/20">
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Spinner Overlay</h3>
                    <div x-data="{ showOverlay: false }">
                        <x-ui.button @click="showOverlay = true">Tampilkan Loading Overlay</x-ui.button>
                        
                        <div x-show="showOverlay">
                            <x-ui.loading.spinner type="spin" size="lg" color="white" text="Memproses permintaan..." overlay="true" />
                        </div>
                        
                        <script>
                            document.addEventListener('alpine:init', () => {
                                Alpine.data('overlayDemo', () => ({
                                    showOverlay: false,
                                    
                                    showDemo() {
                                        this.showOverlay = true;
                                        setTimeout(() => {
                                            this.showOverlay = false;
                                        }, 3000);
                                    }
                                }));
                            });
                        </script>
                    </div>
                </div>
            </div>
        </section>

        <!-- Skeleton Loading -->
        <section class="mb-8">
            <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">
                Status Loading Skeleton
            </h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Basic Skeletons -->
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Bentuk Dasar</h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">Garis</p>
                            <x-ui.loading.skeleton type="line" />
                            <div class="mt-2">
                                <x-ui.loading.skeleton type="line" width="3/4" />
                            </div>
                        </div>
                        
                        <div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">Lingkaran</p>
                            <x-ui.loading.skeleton type="circle" width="12" />
                        </div>
                        
                        <div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">Avatar</p>
                            <x-ui.loading.skeleton type="avatar" width="10" />
                        </div>
                        
                        <div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">Tombol</p>
                            <x-ui.loading.skeleton type="button" width="32" />
                        </div>
                        
                        <div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">Blok Teks</p>
                            <x-ui.loading.skeleton type="text" lines="3" />
                        </div>
                    </div>
                </div>
                
                <!-- Complex Skeletons -->
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Tata Letak Kompleks</h3>
                    <div class="space-y-6">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">Kartu</p>
                            <x-ui.loading.skeleton type="card" lines="2" />
                        </div>
                        
                        <div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">Formulir</p>
                            <x-ui.loading.skeleton type="form" lines="3" />
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Full Layout Skeletons -->
            <div class="mt-6 space-y-6">
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Dasbor Statistik</h3>
                    <x-ui.loading.skeleton type="stats" />
                </div>
                
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Tabel Data</h3>
                    <x-ui.loading.skeleton type="table" lines="5" />
                </div>
                
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Item Daftar</h3>
                    <x-ui.loading.skeleton type="list" lines="4" />
                </div>
            </div>
        </section>

        <!-- Lazy Loading -->
        <section class="mb-8">
            <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">
                Lazy Loading
            </h2>
            
            <div class="space-y-6">
                <!-- Image Lazy Loading -->
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Gambar yang Dimuat Secara Lazy</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <x-ui.loading.lazy-load 
                            src="https://picsum.photos/400/300?random=1"
                            alt="Gambar acak 1"
                            placeholder="image"
                            :fade="true"
                            :blur="true"
                            class="rounded-lg overflow-hidden" />
                            
                        <x-ui.loading.lazy-load 
                            src="https://picsum.photos/400/300?random=2"
                            alt="Gambar acak 2"
                            placeholder="image"
                            :fade="true"
                            :blur="true"
                            class="rounded-lg overflow-hidden" />
                            
                        <x-ui.loading.lazy-load 
                            src="https://picsum.photos/400/300?random=3"
                            alt="Gambar acak 3"
                            placeholder="image"
                            :fade="true"
                            :blur="true"
                            class="rounded-lg overflow-hidden" />
                    </div>
                </div>
                
                <!-- Content Lazy Loading -->
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Konten yang Dimuat Secara Lazy</h3>
                    <div class="space-y-4">
                        <x-ui.loading.lazy-load 
                            placeholder="skeleton"
                            threshold="100px"
                            :fade="true">
                            <div class="p-4 bg-emerald-500/10 rounded-lg">
                                <h4 class="font-medium text-emerald-800 dark:text-emerald-200 mb-2">Konten yang Dimuat Secara Lazy</h4>
                                <p class="text-emerald-700 dark:text-emerald-300 text-sm">
                                    Konten ini dimuat saat terlihat. Teknik ini menghemat waktu muat halaman awal 
                                    dan bandwidth dengan hanya memuat konten saat dibutuhkan.
                                </p>
                            </div>
                        </x-ui.loading.lazy-load>
                        
                        <x-ui.loading.lazy-load 
                            placeholder="skeleton"
                            threshold="50px"
                            :fade="true">
                            <div class="p-4 bg-blue-500/10 rounded-lg">
                                <h4 class="font-medium text-blue-800 dark:text-blue-200 mb-2">Bagian Lazy Lainnya</h4>
                                <p class="text-blue-700 dark:text-blue-300 text-sm">
                                    Ini adalah bagian lain yang dimuat secara lazy. Perhatikan bagaimana setiap bagian dimuat secara independen 
                                    saat memasuki viewport.
                                </p>
                            </div>
                        </x-ui.loading.lazy-load>
                    </div>
                </div>
            </div>
        </section>

        <!-- Performance Insights -->
        <section class="mb-8">
            <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">
                Wawasan Kinerja
            </h2>
            
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-emerald-600 mb-1" id="metric-page-load">-</div>
                        <div class="text-sm text-slate-600 dark:text-slate-400">Waktu Muat Halaman (ms)</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600 mb-1" id="metric-dom-ready">-</div>
                        <div class="text-sm text-slate-600 dark:text-slate-400">DOM Siap (ms)</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600 mb-1" id="metric-resources">-</div>
                        <div class="text-sm text-slate-600 dark:text-slate-400">Sumber Daya Dimuat</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600 mb-1" id="metric-interactions">-</div>
                        <div class="text-sm text-slate-600 dark:text-slate-400">Interaksi Pengguna</div>
                    </div>
                </div>
                
                <div class="mt-6 pt-6 border-t border-white/20">
                    <h4 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Tips Kinerja</h4>
                    <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-emerald-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            Gunakan skeleton loading untuk kinerja yang dirasakan
                        </li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-emerald-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            Muat gambar dan konten berat secara lazy
                        </li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-emerald-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            Pantau Core Web Vitals (LCP, FID, CLS)
                        </li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-emerald-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            Gunakan status loading yang sesuai untuk umpan balik pengguna
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Interactive Demo -->
        <section x-data="performanceDemo()" class="mb-8">
            <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">
                Demo Kinerja Interaktif
            </h2>
            
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <x-ui.button @click="simulateLoading" :loading="loading" class="w-full">
                        <span x-show="!loading">Simulasikan Panggilan API</span>
                    </x-ui.button>
                    
                    <x-ui.button @click="triggerLazyLoad" variant="secondary" class="w-full">
                        Picu Lazy Load
                    </x-ui.button>
                    
                    <x-ui.button @click="generateMetrics" variant="primary" class="w-full">
                        Hasilkan Metrik
                    </x-ui.button>
                </div>
                
                <div x-show="showResults" class="mt-4 p-4 bg-white/10 rounded-lg">
                    <h4 class="font-medium text-slate-800 dark:text-white mb-2">Hasil Demo</h4>
                    <p class="text-sm text-slate-600 dark:text-slate-400" x-text="resultMessage"></p>
                </div>
            </div>
        </section>

        <!-- Back to Dashboard -->
        <div class="mt-8 text-center">
            <a href="{{ route('dashboard') }}" class="group relative px-6 py-3 bg-white/20 backdrop-blur-sm border border-white/30 text-slate-700 dark:text-slate-300 rounded-xl shadow-lg hover:shadow-xl hover:bg-white/30 transition-all duration-300 ease-out">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span class="font-medium">Kembali ke Dashboard</span>
                </div>
            </a>
        </div>
    </div>
</div>

<script>
function performanceDemo() {
    return {
        loading: false,
        showResults: false,
        resultMessage: '',
        
        async simulateLoading() {
            this.loading = true;
            this.showResults = false;
            
            // Simulate API call with loading spinner
            await new Promise(resolve => setTimeout(resolve, 2000));
            
            this.loading = false;
            this.showResults = true;
            this.resultMessage = 'Panggilan API berhasil diselesaikan! Spinner loading memberikan umpan balik kepada pengguna selama menunggu.';
            
            // Track performance metric
            if (window.trackPerformance) {
                window.trackPerformance('demo-api-call', 2000);
            }
        },
        
        triggerLazyLoad() {
            // Scroll to lazy load section to trigger loading
            const lazySection = document.querySelector('[x-data*="lazyLoader"]');
            if (lazySection) {
                lazySection.scrollIntoView({ behavior: 'smooth' });
                this.showResults = true;
                this.resultMessage = 'Digulir ke bagian lazy load. Komponen akan dimuat saat memasuki viewport.';
            }
        },
        
        generateMetrics() {
            if (window.performanceMonitor) {
                const metrics = window.performanceMonitor.getMetrics();
                
                // Update metric displays
                document.getElementById('metric-page-load').textContent = 
                    metrics.pageLoad.pageLoad ? Math.round(metrics.pageLoad.pageLoad) : '-';
                document.getElementById('metric-dom-ready').textContent = 
                    metrics.pageLoad.domReady ? Math.round(metrics.pageLoad.domReady) : '-';
                document.getElementById('metric-resources').textContent = 
                    metrics.resourceTiming.length || '-';
                document.getElementById('metric-interactions').textContent = 
                    metrics.userInteractions.length || '-';
                
                this.showResults = true;
                this.resultMessage = `Metrik kinerja diperbarui! Muat halaman: ${Math.round(metrics.pageLoad.pageLoad || 0)}ms, Sumber Daya: ${metrics.resourceTiming.length}, Interaksi: ${metrics.userInteractions.length}`;
            }
        }
    }
}

// Initialize demo
document.addEventListener('DOMContentLoaded', function() {
    // Show success notification after page loads
    setTimeout(() => {
        // Assuming toast is globally available or imported
        if (typeof toast !== 'undefined') {
            toast.success('Demo kinerja dimuat dengan pemantauan diaktifkan!', {
                title: 'Demo Siap',
                duration: 4000,
                progress: true
            });
        } else {
            console.warn('Sistem notifikasi toast tidak ditemukan.');
        }
    }, 1000);
    
    // Track demo page view
    if (window.trackPerformance) {
        window.trackPerformance('demo-page-view', performance.now());
    }
});
</script>
@endsection
