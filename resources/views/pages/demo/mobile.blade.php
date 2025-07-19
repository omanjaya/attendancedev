@extends('layouts.authenticated-unified')

@section('title', 'Demo Desain Responsif Mobile-First')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Desain Responsif Mobile-First"
            subtitle="Dioptimalkan untuk perangkat seluler dengan antarmuka yang ramah sentuhan dan peningkatan progresif"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Demo'],
                ['label' => 'Mobile']
            ]">
        </x-layouts.base-page>

        <!-- Responsive Cards Grid -->
        <section class="mb-8">
            <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Kartu Responsif</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Pengguna Aktif</p>
                            <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">1,247</p>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-2">Dibandingkan bulan lalu</p>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Tingkat Kehadiran</p>
                            <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">94.2%</p>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                        </div>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-2">Di atas target 90%</p>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Permintaan Tertunda</p>
                            <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">8</p>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-2">Perlu ditinjau</p>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Pendapatan</p>
                            <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">$45,280</p>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-2">Bulan ini</p>
                </div>
            </div>
        </section>

        <!-- Responsive Table -->
        <section class="mb-8">
            <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Tabel Data Responsif</h2>
            
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                @php
                    $tableHeaders = [
                        ['label' => 'Nama', 'field' => 'name', 'sortable' => true],
                        ['label' => 'Posisi', 'field' => 'position'],
                        ['label' => 'Departemen', 'field' => 'department'],
                        ['label' => 'Status', 'field' => 'status', 'component' => 'partials.status-badge'],
                        ['label' => 'Terakhir Dilihat', 'field' => 'last_seen']
                    ];
                    
                    $tableData = [
                        [
                            'id' => 1,
                            'name' => 'John Doe',
                            'position' => 'Pengembang Senior',
                            'department' => 'Teknik',
                            'status' => 'active',
                            'last_seen' => '2 jam yang lalu'
                        ],
                        [
                            'id' => 2,
                            'name' => 'Jane Smith',
                            'position' => 'Manajer Proyek',
                            'department' => 'Operasi',
                            'status' => 'away',
                            'last_seen' => '1 hari yang lalu'
                        ],
                        [
                            'id' => 3,
                            'name' => 'Mike Johnson',
                            'position' => 'Desainer',
                            'department' => 'Kreatif',
                            'status' => 'offline',
                            'last_seen' => '3 hari yang lalu'
                        ]
                    ];
                    
                    $tableActions = function($row) {
                        return '
                            <x-ui.button size="sm" variant="secondary">Edit</x-ui.button>
                            <x-ui.button size="sm" variant="destructive">Hapus</x-ui.button>
                        ';
                    };
                @endphp
                
                <x-ui.table
                    :headers="$tableHeaders"
                    :data="$tableData"
                    :actions="$tableActions"
                    :searchable="true"
                    :sortable="true"
                    empty-message="Tidak ada karyawan ditemukan" />
            </div>
        </section>

        <!-- Mobile Form -->
        <section class="mb-8">
            <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Formulir yang Dioptimalkan untuk Seluler</h2>
            
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Pendaftaran Karyawan</h3>
                <form action="{{ route('demo.components') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="space-y-2">
                        <x-ui.label for="form_name" value="Nama Lengkap" class="text-slate-700 dark:text-slate-300" />
                        <x-ui.input id="form_name" name="name" type="text" placeholder="Masukkan nama lengkap" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                    </div>

                    <div class="space-y-2">
                        <x-ui.label for="form_email" value="Alamat Email" class="text-slate-700 dark:text-slate-300" />
                        <x-ui.input id="form_email" name="email" type="email" placeholder="Masukkan alamat email" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                        <p class="text-xs text-slate-600 dark:text-slate-400">Ini akan digunakan untuk login dan notifikasi</p>
                    </div>

                    <div class="space-y-2">
                        <x-ui.label for="form_phone" value="Nomor Telepon" class="text-slate-700 dark:text-slate-300" />
                        <x-ui.input id="form_phone" name="phone" type="tel" placeholder="Masukkan nomor telepon" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                    </div>

                    <div class="space-y-2">
                        <x-ui.label for="form_department" value="Departemen" class="text-slate-700 dark:text-slate-300" />
                        <x-ui.select id="form_department" name="department" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                            <option value="">Pilih departemen</option>
                            <option value="engineering">Teknik</option>
                            <option value="operations">Operasi</option>
                            <option value="creative">Kreatif</option>
                            <option value="hr">Sumber Daya Manusia</option>
                            <option value="finance">Keuangan</option>
                        </x-ui.select>
                    </div>

                    <div class="space-y-2">
                        <x-ui.label for="form_type" value="Tipe Karyawan" class="text-slate-700 dark:text-slate-300" />
                        <x-ui.select id="form_type" name="type" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                            <option value="">Pilih tipe karyawan</option>
                            <option value="permanent">Staf Tetap</option>
                            <option value="honorary">Guru Honorer</option>
                            <option value="contract">Pekerja Kontrak</option>
                        </x-ui.select>
                    </div>

                    <div class="space-y-2">
                        <x-ui.label for="form_avatar" value="Gambar Profil" class="text-slate-700 dark:text-slate-300" />
                        <x-ui.input id="form_avatar" name="avatar" type="file" accept="image/*" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                        <p class="text-xs text-slate-600 dark:text-slate-400">Unggah gambar profil (opsional)</p>
                    </div>

                    <div class="space-y-2">
                        <x-ui.label for="form_bio" value="Bio" class="text-slate-700 dark:text-slate-300" />
                        <textarea id="form_bio" name="bio" rows="4" class="flex min-h-[80px] w-full rounded-md border border-white/40 bg-white/30 backdrop-blur-sm px-3 py-2 text-sm ring-offset-background placeholder:text-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50 focus-visible:border-blue-500/50 disabled:cursor-not-allowed disabled:opacity-50 text-slate-800 dark:text-white transition-all duration-300" placeholder="Ceritakan tentang diri Anda"></textarea>
                        <p class="text-xs text-slate-600 dark:text-slate-400">Deskripsi singkat tentang karyawan</p>
                    </div>

                    <div class="flex items-center space-x-2">
                        <x-ui.checkbox id="form_terms" name="terms" />
                        <x-ui.label for="form_terms" value="Saya menyetujui syarat dan ketentuan" class="text-slate-700 dark:text-slate-300" />
                    </div>

                    <div class="flex gap-4">
                        <x-ui.button type="submit">Buat Karyawan</x-ui.button>
                        <x-ui.button variant="secondary" type="button">Batal</x-ui.button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Mobile Features -->
        <section class="mb-8">
            <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Fitur Khusus Seluler</h2>
            
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-3">Gestur & Interaksi Sentuh</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-slate-600 dark:text-slate-400">
                    <div class="p-4 bg-white/10 rounded-lg">
                        <h4 class="font-medium text-slate-800 dark:text-white mb-2">Aksi Geser</h4>
                        <p class="text-sm">Geser ke kiri pada baris tabel untuk aksi cepat</p>
                    </div>
                    <div class="p-4 bg-white/10 rounded-lg">
                        <h4 class="font-medium text-slate-800 dark:text-white mb-2">Tarik untuk Menyegarkan</h4>
                        <p class="text-sm">Tarik ke bawah pada daftar untuk menyegarkan konten</p>
                    </div>
                    <div class="p-4 bg-white/10 rounded-lg">
                        <h4 class="font-medium text-slate-800 dark:text-white mb-2">Umpan Balik Haptik</h4>
                        <p class="text-sm">Umpan balik getaran untuk tindakan penting</p>
                    </div>
                    <div class="p-4 bg-white/10 rounded-lg">
                        <h4 class="font-medium text-slate-800 dark:text-white mb-2">Target Sentuh Besar</h4>
                        <p class="text-sm">Target sentuh minimum 44px untuk aksesibilitas</p>
                    </div>
                </div>

                <h3 class="text-lg font-semibold text-slate-800 dark:text-white mt-6 mb-3">Breakpoint Responsif</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-white/10">
                            <tr class="border-b border-white/20">
                                <th class="text-left py-2 font-semibold text-slate-700 dark:text-slate-300">Perangkat</th>
                                <th class="text-left py-2 font-semibold text-slate-700 dark:text-slate-300">Breakpoint</th>
                                <th class="text-left py-2 font-semibold text-slate-700 dark:text-slate-300">Perilaku</th>
                            </tr>
                        </thead>
                        <tbody class="text-slate-600 dark:text-slate-400">
                            <tr class="border-b border-white/10">
                                <td class="py-2">Seluler</td>
                                <td class="py-2">&lt; 640px</td>
                                <td class="py-2">Satu kolom, navigasi bawah</td>
                            </tr>
                            <tr class="border-b border-white/10">
                                <td class="py-2">Tablet</td>
                                <td class="py-2">640px - 1024px</td>
                                <td class="py-2">Dua kolom, spasi ditingkatkan</td>
                            </tr>
                            <tr>
                                <td class="py-2">Desktop</td>
                                <td class="py-2">&gt; 1024px</td>
                                <td class="py-2">Tata letak penuh, navigasi sidebar</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3 class="text-lg font-semibold text-slate-800 dark:text-white mt-6 mb-3">Optimasi Kinerja</h3>
                <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-emerald-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        Lazy loading untuk gambar dan komponen
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-emerald-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        Kemampuan Progressive Web App
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-emerald-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        Ukuran bundle yang dioptimalkan dengan tree shaking
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-emerald-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        Kemampuan offline dengan service workers
                    </li>
                </ul>
            </div>
        </section>

        <!-- Device Testing -->
        <section class="mb-8">
            <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Pengujian Perangkat</h2>
            
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                <div class="text-center space-y-4 text-slate-600 dark:text-slate-400">
                    <div class="text-sm">
                        Ukuran viewport saat ini: <span id="viewport-size" class="font-medium text-slate-800 dark:text-white"></span>
                    </div>
                    <div class="text-sm">
                        Tipe perangkat: <span id="device-type" class="font-medium text-slate-800 dark:text-white"></span>
                    </div>
                    <div class="text-sm">
                        Dukungan sentuh: <span id="touch-support" class="font-medium text-slate-800 dark:text-white"></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Back to Dashboard -->
        <div class="mt-8 text-center">
            <a href="{{ route('dashboard') }}" class="group relative px-6 py-3 bg-gradient-to-r from-emerald-500 to-green-500 text-white rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 ease-out">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span class="font-medium">Kembali ke Dashboard</span>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- Mobile Bottom Navigation -->
<x-navigation.unified-nav variant="mobile-bottom" />

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update viewport info
    function updateViewportInfo() {
        const width = window.innerWidth;
        const height = window.innerHeight;
        document.getElementById('viewport-size').textContent = `${width} × ${height}`;
        
        // Determine device type
        let deviceType = 'Desktop';
        if (width < 640) {
            deviceType = 'Mobile';
        } else if (width < 1024) {
            deviceType = 'Tablet';
        }
        document.getElementById('device-type').textContent = deviceType;
        
        // Check touch support
        const hasTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        document.getElementById('touch-support').textContent = hasTouch ? 'Ya' : 'Tidak';
    }
    
    updateViewportInfo();
    window.addEventListener('resize', updateViewportInfo);
    
    // Demo notification
    setTimeout(() => {
        // Assuming toast is globally available or imported
        if (typeof toast !== 'undefined') {
            toast.success('Desain mobile-first berhasil dimuat!', {
                title: 'Selamat Datang',
                duration: 4000,
                progress: true
            });
        } else {
            console.warn('Sistem notifikasi toast tidak ditemukan.');
        }
    }, 1000);
});
</script>
@endsection
