@extends('layouts.authenticated-unified')

@section('title', 'Demo Sistem Notifikasi')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Demo Sistem Notifikasi"
            subtitle="Uji sistem notifikasi toast yang komprehensif dengan berbagai jenis dan konfigurasi"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Demo'],
                ['label' => 'Notifikasi']
            ]">
        </x-layouts.base-page>
        
        <!-- Grid Layout -->
        <div class="grid lg:grid-cols-2 gap-8">
            <!-- Interactive Demo Form -->
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">
                    Demo Formulir Interaktif
                </h2>
                <p class="text-slate-600 dark:text-slate-400 mb-6">
                    Formulir ini mendemonstrasikan penggunaan notifikasi dunia nyata dengan status loading, validasi, dan umpan balik sukses.
                </p>
                
                <x-forms.demo-form />
            </div>
            
            <!-- Server-side Flash Messages -->
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">
                    Pesan Flash Laravel
                </h2>
                <p class="text-slate-600 dark:text-slate-400 mb-6">
                    Uji pesan flash sisi server yang secara otomatis dikonversi menjadi notifikasi toast.
                </p>
                
                <form action="{{ route('demo.notifications.test') }}" method="POST" class="space-y-4">
                    @csrf
                    
                    <div>
                        <x-ui.label for="type" value="Tipe Pesan" class="text-slate-700 dark:text-slate-300" />
                        <x-ui.select name="type" id="type" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                            <option value="success">Sukses</option>
                            <option value="error">Error</option>
                            <option value="warning">Peringatan</option>
                            <option value="info">Info</option>
                        </x-ui.select>
                    </div>
                    
                    <div>
                        <x-ui.label for="message" value="Konten Pesan" class="text-slate-700 dark:text-slate-300" />
                        <x-ui.input 
                            type="text" 
                            name="message" 
                            id="message"
                            value="Ini adalah notifikasi tes dari sesi flash Laravel!"
                            class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                    </div>
                    
                    <x-ui.button type="submit" variant="primary" class="w-full">
                        Kirim Pesan Flash
                    </x-ui.button>
                </form>
            </div>
            
            <!-- Direct JavaScript API -->
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">
                    Demo API JavaScript
                </h2>
                <p class="text-slate-600 dark:text-slate-400 mb-6">
                    Gunakan API JavaScript global untuk notifikasi instan di komponen Vue Anda atau JS murni.
                </p>
                
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.button 
                        onclick="toast.success('Operasi berhasil diselesaikan!', { title: 'Kerja bagus!', progress: true })"
                        variant="success">
                        Sukses
                    </x-ui.button>
                    
                    <x-ui.button 
                        onclick="toast.error('Terjadi kesalahan!', { title: 'Ups!', duration: 7000 })"
                        variant="destructive">
                        Error
                    </x-ui.button>
                    
                    <x-ui.button 
                        onclick="toast.warning('Mohon periksa input Anda!', { title: 'Peringatan', progress: true })"
                        variant="warning">
                        Peringatan
                    </x-ui.button>
                    
                    <x-ui.button 
                        onclick="toast.info('Berikut adalah beberapa informasi berguna', { title: 'FYI', duration: 6000 })"
                        variant="info">
                        Info
                    </x-ui.button>
                </div>
                
                <div class="mt-4">
                    <x-ui.button 
                        onclick="showAdvancedNotification()"
                        variant="secondary" class="w-full">
                        Notifikasi Lanjutan dengan Aksi
                    </x-ui.button>
                </div>
            </div>
        </div>
        
        <!-- Code Examples -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mt-8">
            <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">
                Contoh Implementasi
            </h2>
            
            <div class="grid lg:grid-cols-2 gap-6">
                <!-- JavaScript Usage -->
                <div>
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-2">Penggunaan JavaScript</h3>
                    <pre class="bg-white/10 rounded-lg p-4 text-sm overflow-x-auto text-slate-600 dark:text-slate-400"><code>// Notifikasi sederhana
toast.success('Pesan sukses');
toast.error('Pesan error');
toast.warning('Pesan peringatan');
toast.info('Pesan info');

// Konfigurasi lanjutan
showToast({
    type: 'success',
    title: 'Unggah Selesai',
    message: 'File berhasil diunggah',
    duration: 5000,
    progress: true,
    actions: [
        {
            label: 'Lihat File',
            callback: () => console.log('Lihat diklik')
        }
    ]
});</code></pre>
                </div>
                
                <!-- Laravel Usage -->
                <div>
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-2">Penggunaan Laravel</h3>
                    <pre class="bg-white/10 rounded-lg p-4 text-sm overflow-x-auto text-slate-600 dark:text-slate-400"><code>// Di controller
return redirect()
    ->route('dashboard')
    ->with('success', 'Data berhasil disimpan');

return redirect()
    ->back()
    ->with('error', 'Validasi gagal');

// Penggunaan komponen langsung
&lt;x-ui.notification 
    type="success"
    title="Selamat Datang!"
    message="Terima kasih telah bergabung dengan kami"
    :progress="true" /&gt;</code></pre>
                </div>
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
function showAdvancedNotification() {
    // Assuming toast is globally available or imported
    if (typeof toast === 'undefined') {
        console.warn('Toast notification system not found.');
        return;
    }

    toast.warning('Apakah Anda yakin ingin menghapus item ini? Tindakan ini tidak dapat dibatalkan.', {
        title: 'Konfirmasi Aksi',
        duration: 15000,
        progress: true,
        actions: [
            {
                label: 'Hapus',
                style: 'destructive',
                onClick: () => {
                    toast.success('Item berhasil dihapus');
                }
            },
            {
                label: 'Batal',
                style: 'secondary',
                onClick: () => {
                    toast.info('Penghapusan dibatalkan');
                }
            }
        ]
    });
}
</script>
@endsection
