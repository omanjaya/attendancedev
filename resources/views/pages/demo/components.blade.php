@extends('layouts.authenticated-unified')

@section('title', 'Demo Komponen Standar')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Demo Komponen Standar"
            subtitle="Semua komponen sekarang menggunakan Tailwind CSS murni dengan tema glassmorphism yang konsisten"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Demo'],
                ['label' => 'Komponen']
            ]">
        </x-layouts.base-page>
        
        <!-- Buttons Section -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mb-8">
            <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Varian Tombol</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="space-y-2">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Default</p>
                    <x-ui.button>Tombol Default</x-ui.button>
                    <x-ui.button size="sm">Tombol Kecil</x-ui.button>
                    <x-ui.button size="lg">Tombol Besar</x-ui.button>
                </div>
                
                <div class="space-y-2">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Sekunder</p>
                    <x-ui.button variant="secondary">Sekunder</x-ui.button>
                    <x-ui.button variant="outline">Garis Luar</x-ui.button>
                    <x-ui.button variant="ghost">Ghost</x-ui.button>
                </div>
                
                <div class="space-y-2">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Status</p>
                    <x-ui.button variant="success">Sukses</x-ui.button>
                    <x-ui.button variant="warning">Peringatan</x-ui.button>
                    <x-ui.button variant="destructive">Destruktif</x-ui.button>
                </div>
                
                <div class="space-y-2">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Status</p>
                    <x-ui.button :loading="true">Memuat</x-ui.button>
                    <x-ui.button disabled>Dinonaktifkan</x-ui.button>
                    <x-ui.button variant="link">Tombol Tautan</x-ui.button>
                </div>
            </div>
        </div>

        <!-- Badges Section -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mb-8">
            <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Varian Badge</h3>
            <div class="flex flex-wrap gap-3">
                <x-ui.badge>Badge Default</x-ui.badge>
                <x-ui.badge variant="secondary">Sekunder</x-ui.badge>
                <x-ui.badge variant="success">Sukses</x-ui.badge>
                <x-ui.badge variant="warning">Peringatan</x-ui.badge>
                <x-ui.badge variant="destructive">Error</x-ui.badge>
                <x-ui.badge variant="info">Info</x-ui.badge>
                <x-ui.badge variant="outline">Garis Luar</x-ui.badge>
                <x-ui.badge size="sm">Kecil</x-ui.badge>
                <x-ui.badge size="lg">Besar</x-ui.badge>
            </div>
        </div>

        <!-- Form Components Section -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mb-8">
            <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Komponen Formulir</h3>
            <form class="space-y-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <x-ui.label for="demo_name" value="Nama" class="text-slate-700 dark:text-slate-300" />
                        <x-ui.input id="demo_name" name="demo_name" type="text" placeholder="Masukkan nama Anda" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                    </div>
                    
                    <div>
                        <x-ui.label for="demo_email" value="Email" class="text-slate-700 dark:text-slate-300" />
                        <x-ui.input id="demo_email" name="demo_email" type="email" placeholder="Masukkan email Anda" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                    </div>
                </div>
                
                <div>
                    <x-ui.label for="demo_message" value="Pesan" class="text-slate-700 dark:text-slate-300" />
                    <textarea 
                        id="demo_message" 
                        name="demo_message" 
                        rows="4"
                        class="flex min-h-[80px] w-full rounded-md border border-white/40 bg-white/30 backdrop-blur-sm px-3 py-2 text-sm ring-offset-background placeholder:text-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50 focus-visible:border-blue-500/50 disabled:cursor-not-allowed disabled:opacity-50 text-slate-800 dark:text-white transition-all duration-300"
                        placeholder="Masukkan pesan Anda"></textarea>
                </div>
                
                <div class="flex gap-4">
                    <x-ui.button type="submit">Kirim Formulir</x-ui.button>
                    <x-ui.button variant="secondary" type="button">Batal</x-ui.button>
                </div>
            </form>
        </div>

        <!-- Cards Section -->
        <div class="grid lg:grid-cols-3 gap-6 mb-8">
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Total Pengguna</p>
                        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">1,234</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                </div>
                <div class="text-sm text-slate-600 dark:text-slate-400 mt-2">
                    <span class="text-emerald-600">+12%</span> dari bulan lalu
                </div>
            </div>
            
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Pemberitahuan Penting</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Ini adalah kartu unggulan dengan aksen batas zamrud dan gaya yang ditingkatkan.
                </p>
                <div class="mt-4">
                    <x-ui.button size="sm" variant="secondary">Aksi</x-ui.button>
                </div>
            </div>
            
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Statistik Cepat</h3>
                <div class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                    <div class="flex justify-between">
                        <span>Aktif</span>
                        <span class="font-medium text-slate-800 dark:text-white">89%</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Tertunda</span>
                        <span class="font-medium text-slate-800 dark:text-white">11%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mb-8">
            <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Tabel Data</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/20">
                    <thead class="bg-white/10 backdrop-blur-sm">
                        <tr>
                            <th class="h-12 px-4 text-left align-middle font-semibold text-slate-700 dark:text-slate-300">Nama</th>
                            <th class="h-12 px-4 text-left align-middle font-semibold text-slate-700 dark:text-slate-300">Peran</th>
                            <th class="h-12 px-4 text-left align-middle font-semibold text-slate-700 dark:text-slate-300">Status</th>
                            <th class="h-12 px-4 text-left align-middle font-semibold text-slate-700 dark:text-slate-300">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white/5 backdrop-blur-sm divide-y divide-white/10">
                        <tr class="hover:bg-white/10 transition-colors">
                            <td class="p-4 align-middle text-slate-800 dark:text-white">John Doe</td>
                            <td class="p-4 align-middle text-slate-800 dark:text-white">Administrator</td>
                            <td class="p-4 align-middle">
                                <x-ui.badge variant="success">Aktif</x-ui.badge>
                            </td>
                            <td class="p-4 align-middle">
                                <div class="flex gap-2">
                                    <x-ui.button size="sm" variant="secondary">Edit</x-ui.button>
                                    <x-ui.button size="sm" variant="destructive">Hapus</x-ui.button>
                                </div>
                            </td>
                        </tr>
                        <tr class="hover:bg-white/10 transition-colors">
                            <td class="p-4 align-middle text-slate-800 dark:text-white">Jane Smith</td>
                            <td class="p-4 align-middle text-slate-800 dark:text-white">Manajer</td>
                            <td class="p-4 align-middle">
                                <x-ui.badge variant="warning">Tertunda</x-ui.badge>
                            </td>
                            <td class="p-4 align-middle">
                                <div class="flex gap-2">
                                    <x-ui.button size="sm" variant="secondary">Edit</x-ui.button>
                                    <x-ui.button size="sm" variant="destructive">Hapus</x-ui.button>
                                </div>
                            </td>
                        </tr>
                        <tr class="hover:bg-white/10 transition-colors">
                            <td class="p-4 align-middle text-slate-800 dark:text-white">Mike Johnson</td>
                            <td class="p-4 align-middle text-slate-800 dark:text-white">Karyawan</td>
                            <td class="p-4 align-middle">
                                <x-ui.badge variant="destructive">Tidak Aktif</x-ui.badge>
                            </td>
                            <td class="p-4 align-middle">
                                <div class="flex gap-2">
                                    <x-ui.button size="sm" variant="secondary">Edit</x-ui.button>
                                    <x-ui.button size="sm" variant="destructive">Hapus</x-ui.button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Alert Examples -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mb-8">
            <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Pesan Peringatan</h3>
            <div class="space-y-4">
                <x-ui.alerts.success message="Ini adalah peringatan sukses menggunakan warna zamrud" />
                
                <div class="rounded-md bg-red-500/20 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800 dark:text-red-200">Ini adalah peringatan error dengan gaya yang konsisten</p>
                        </div>
                    </div>
                </div>
                
                <div class="rounded-md bg-amber-500/20 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-amber-800 dark:text-amber-200">Ini adalah peringatan warning dengan gaya Tailwind murni</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Theme Toggle -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mb-8">
            <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Kontrol Tema</h3>
            <div class="flex items-center gap-4">
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Tema:</span>
                <x-ui.theme-toggle :show-label="true" />
                <span class="text-xs text-slate-600 dark:text-slate-400">Beralih antara mode terang dan gelap</span>
            </div>
        </div>

        <!-- Implementation Guide -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
            <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Standardisasi Selesai</h3>
            <div class="prose prose-sm max-w-none dark:prose-invert text-slate-600 dark:text-slate-400">
                <p>Semua komponen telah distandarisasi untuk menggunakan:</p>
                <ul>
                    <li><strong>Kelas Tailwind CSS murni</strong> - Tanpa dependensi CSS kustom</li>
                    <li><strong>Skema warna zamrud</strong> - Konsisten dengan branding sistem</li>
                    <li><strong>Dukungan mode gelap</strong> - Deteksi dan peralihan tema otomatis</li>
                    <li><strong>Fitur aksesibilitas</strong> - Status fokus, label ARIA, navigasi keyboard</li>
                    <li><strong>Desain mobile-first</strong> - Responsif dan ramah sentuhan</li>
                </ul>
                <p class="text-emerald-600 dark:text-emerald-400 font-medium">
                    Fase 4 Selesai: Semua komponen sekarang menggunakan kelas Tailwind standar!
                </p>
            </div>
        </div>

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
// Test notification integration
document.addEventListener('DOMContentLoaded', function() {
    // Show a welcome notification
    setTimeout(() => {
        // Assuming toast is globally available or imported
        if (typeof toast !== 'undefined') {
            toast.success('Standardisasi komponen berhasil diselesaikan!', {
                title: 'Fase 4 Selesai',
                duration: 5000,
                progress: true
            });
        } else {
            console.warn('Toast notification system not found.');
        }
    }, 1000);
});
</script>
@endsection
