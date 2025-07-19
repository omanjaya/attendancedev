@extends('layouts.authenticated-unified')

@section('title', 'Tambah Lokasi Baru')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Tambah Lokasi Baru"
            subtitle="Kelola lokasi verifikasi absensi dan pengaturan GPS"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Manajemen'],
                ['label' => 'Lokasi', 'url' => route('locations.index')],
                ['label' => 'Tambah Baru']
            ]">
        </x-layouts.base-page>

        <form action="{{ route('locations.store') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Informasi Lokasi</h3>
                    
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div class="space-y-2">
                                <x-ui.label for="name" value="Nama Lokasi" required class="text-slate-700 dark:text-slate-300" />
                                <x-ui.input type="text" name="name" 
                                           value="{{ old('name') }}" placeholder="Kampus Utama" required 
                                           class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                                @error('name')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div class="space-y-2">
                                <x-ui.label for="radius_meters" value="Radius yang Diizinkan (meter)" required class="text-slate-700 dark:text-slate-300" />
                                <div class="flex">
                                    <x-ui.input type="number" name="radius_meters" 
                                               value="{{ old('radius_meters', 100) }}" min="10" max="1000" required 
                                               class="flex-1 rounded-r-none bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                                    <span class="inline-flex items-center px-3 py-2 rounded-r-xl border border-l-0 border-white/40 bg-white/30 text-slate-700 dark:text-slate-300 text-sm">meter</span>
                                </div>
                                @error('radius_meters')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-slate-600 dark:text-slate-400">Seberapa dekat karyawan harus berada untuk verifikasi lokasi</p>
                            </div>
                        </div>
                        
                        <div class="space-y-2">
                            <x-ui.label for="address" value="Alamat" class="text-slate-700 dark:text-slate-300" />
                            <textarea name="address" 
                                      class="flex min-h-[80px] w-full rounded-md border border-white/40 bg-white/30 backdrop-blur-sm px-3 py-2 text-sm ring-offset-background placeholder:text-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50 focus-visible:border-blue-500/50 disabled:cursor-not-allowed disabled:opacity-50 text-slate-800 dark:text-white transition-all duration-300" 
                                      rows="2" placeholder="123 Jalan Utama, Kota, Provinsi, Kode Pos">{{ old('address') }}</textarea>
                            @error('address')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div class="space-y-2">
                                <x-ui.label for="latitude" value="Lintang" class="text-slate-700 dark:text-slate-300" />
                                <x-ui.input type="number" name="latitude" id="latitude" 
                                           value="{{ old('latitude') }}" step="0.000001" placeholder="-6.2088" 
                                           class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                                @error('latitude')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-slate-600 dark:text-slate-400">Untuk verifikasi absensi berbasis GPS</p>
                            </div>
                            
                            <div class="space-y-2">
                                <x-ui.label for="longitude" value="Bujur" class="text-slate-700 dark:text-slate-300" />
                                <x-ui.input type="number" name="longitude" id="longitude" 
                                           value="{{ old('longitude') }}" step="0.000001" placeholder="106.8456" 
                                           class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                                @error('longitude')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-slate-600 dark:text-slate-400">Untuk verifikasi absensi berbasis GPS</p>
                            </div>
                        </div>
                        
                        <div class="space-y-2">
                            <x-ui.button type="button" id="getCurrentLocation" variant="secondary">
                                <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-4.5 -7l-7 -4.5a.55 .55 0 0 1 0 -1l18 -6.5"/></svg>
                                Dapatkan Lokasi Saat Ini
                            </x-ui.button>
                            <span id="locationStatus" class="text-slate-600 dark:text-slate-400 ml-2"></span>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div class="space-y-2">
                                <x-ui.label for="wifi_ssid" value="Nama Jaringan WiFi (SSID)" class="text-slate-700 dark:text-slate-300" />
                                <x-ui.input type="text" name="wifi_ssid" 
                                           value="{{ old('wifi_ssid') }}" placeholder="School-WiFi" 
                                           class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                                @error('wifi_ssid')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-slate-600 dark:text-slate-400">Opsional: Untuk verifikasi lokasi berbasis WiFi</p>
                            </div>
                            
                            <div class="space-y-2">
                                <x-ui.label value="Status" class="text-slate-700 dark:text-slate-300" />
                                <div class="flex items-center space-x-3">
                                    <input type="hidden" name="is_active" value="0">
                                    <x-ui.checkbox name="is_active" value="1" 
                                                   {{ old('is_active', true) ? 'checked' : '' }} />
                                    <span class="text-sm font-medium text-slate-800 dark:text-white">Lokasi Aktif</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="lg:col-span-1 space-y-6">
                    <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                        <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Pratinjau Lokasi</h3>
                        <div id="mapContainer" style="height: 300px;" class="bg-white/10 rounded-lg flex items-center justify-center border border-white/20 text-slate-600 dark:text-slate-400">
                            <div class="text-center">
                                <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-4.5 -7l-7 -4.5a.55 .55 0 0 1 0 -1l18 -6.5"/></svg>
                                <div>Masukkan koordinat untuk pratinjau lokasi</div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h4 class="text-lg font-semibold text-slate-800 dark:text-white mb-2">Metode Verifikasi:</h4>
                            <div id="verificationMethods" class="flex flex-wrap gap-2">
                                <span class="text-slate-600 dark:text-slate-400">Tidak ada yang dikonfigurasi</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                        <x-ui.button type="submit" variant="primary" class="w-full">
                            <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                            Buat Lokasi
                        </x-ui.button>
                        <x-ui.button type="button" variant="secondary" class="w-full mt-2" onclick="window.location.href='{{ route('locations.index') }}'">
                            Batal
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Update verification methods preview
    function updateVerificationMethods() {
        const latitude = $('#latitude').val();
        const longitude = $('#longitude').val();
        const wifiSsid = $('[name="wifi_ssid"]').val();
        
        let methods = [];
        
        if (latitude && longitude) {
            methods.push('<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white bg-gradient-to-r from-green-500 to-emerald-600 shadow-lg">Lokasi GPS</span>');
        }
        
        if (wifiSsid) {
            methods.push('<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white bg-gradient-to-r from-blue-500 to-cyan-500 shadow-lg">Jaringan WiFi</span>');
        }
        
        const verificationMethodsDiv = $('#verificationMethods');
        if (methods.length > 0) {
            verificationMethodsDiv.html(methods.join(' '));
        } else {
            verificationMethodsDiv.html('<span class="text-slate-600 dark:text-slate-400">Tidak ada yang dikonfigurasi</span>');
        }
    }
    
    // Update map preview (placeholder)
    function updateMapPreview() {
        const latitude = $('#latitude').val();
        const longitude = $('#longitude').val();
        
        const mapContainer = $('#mapContainer');
        
        if (latitude && longitude) {
            mapContainer.html(`
                <div class="text-center text-emerald-600">
                    <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-4.5 -7l-7 -4.5a.55 .55 0 0 1 0 -1l18 -6.5"/></svg>
                    <div><strong>Lokasi Ditetapkan</strong></div>
                    <small>${latitude}, ${longitude}</small>
                </div>
            `);
        } else {
            mapContainer.html(`
                <div class="text-center text-slate-600 dark:text-slate-400">
                    <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-4.5 -7l-7 -4.5a.55 .55 0 0 1 0 -1l18 -6.5"/></svg>
                    <div>Masukkan koordinat untuk pratinjau lokasi</div>
                </div>
            `);
        }
    }
    
    // Event listeners
    $('#latitude, #longitude, [name="wifi_ssid"]').on('input', function() {
        updateVerificationMethods();
        updateMapPreview();
    });
    
    // Get current location
    $('#getCurrentLocation').on('click', function() {
        const button = $(this);
        const status = $('#locationStatus');
        
        if (!navigator.geolocation) {
            status.text('Geolocation tidak didukung oleh browser ini.').removeClass('text-slate-600 dark:text-slate-400').addClass('text-red-500');
            return;
        }
        
        button.prop('disabled', true).html(`
            <span class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
            Mendapatkan lokasi...
        `);
        status.text('Meminta izin lokasi...').removeClass('text-red-500').addClass('text-slate-600 dark:text-slate-400');
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                $('#latitude').val(position.coords.latitude.toFixed(6));
                $('#longitude').val(position.coords.longitude.toFixed(6));
                
                status.text('Lokasi berhasil diambil!').removeClass('text-red-500').addClass('text-green-500');
                updateVerificationMethods();
                updateMapPreview();
                
                button.prop('disabled', false).html(`
                    <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-4.5 -7l-7 -4.5a.55 .55 0 0 1 0 -1l18 -6.5"/></svg>
                    Dapatkan Lokasi Saat Ini
                `);
            },
            function(error) {
                let message = 'Tidak dapat mengambil lokasi.';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        message = 'Akses lokasi ditolak oleh pengguna.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        message = 'Informasi lokasi tidak tersedia.';
                        break;
                    case error.TIMEOUT:
                        message = 'Permintaan lokasi habis waktu.';
                        break;
                }
                
                status.text(message).removeClass('text-slate-600 dark:text-slate-400').addClass('text-red-500');
                button.prop('disabled', false).html(`
                    <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-4.5 -7l-7 -4.5a.55 .55 0 0 1 0 -1l18 -6.5"/></svg>
                    Dapatkan Lokasi Saat Ini
                `);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000
            }
        );
    });
    
    // Initialize
    updateVerificationMethods();
    updateMapPreview();
});
</script>
@endpush
