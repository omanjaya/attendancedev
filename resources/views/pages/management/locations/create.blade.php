@extends('layouts.authenticated-unified')

@section('title', 'Tambah Lokasi Baru')

@section('page-content')
<div class="min-h-screen bg-background">
    <div class="bg-card border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex py-4" aria-label="Breadcrumb">
                <ol role="list" class="flex items-center space-x-2 text-sm">
                    <li class="flex items-center">
                        <a href="{{ route('dashboard') }}" class="text-muted-foreground hover:text-foreground transition-colors">
                            Dashboard
                        </a>
                    </li>
                    <li class="flex items-center">
                        <svg class="h-4 w-4 text-muted-foreground mx-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-foreground font-medium">Manajemen</span>
                    </li>
                    <li class="flex items-center">
                        <svg class="h-4 w-4 text-muted-foreground mx-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <a href="{{ route('locations.index') }}" class="text-muted-foreground hover:text-foreground transition-colors">
                            Lokasi
                        </a>
                    </li>
                    <li class="flex items-center">
                        <svg class="h-4 w-4 text-muted-foreground mx-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-foreground font-medium">Tambah Baru</span>
                    </li>
                </ol>
            </nav>
            
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between py-4">
                <div class="flex-1 min-w-0">
                    <h1 class="text-2xl font-bold text-foreground truncate">Tambah Lokasi Baru</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Buat lokasi baru untuk verifikasi absensi</p>
                </div>
            </div>
        </div>
    </div>
    
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <form action="{{ route('locations.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Form Fields -->
                <div class="bg-card rounded-lg border p-6">
                    <h3 class="text-lg font-semibold text-foreground mb-6">Informasi Lokasi</h3>
                    
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">Nama Lokasi *</label>
                            <input type="text" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                   placeholder="Contoh: SMAN 1 Denpasar"
                                   required>
                            @error('name')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">Alamat Lengkap</label>
                            <textarea name="address" 
                                      rows="3" 
                                      class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                      placeholder="Alamat lengkap lokasi">{{ old('address') }}</textarea>
                            @error('address')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-2">Latitude</label>
                                <input type="number" 
                                       name="latitude" 
                                       id="latitude"
                                       value="{{ old('latitude') }}" 
                                       step="any"
                                       class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                       placeholder="-8.6481">
                                @error('latitude')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-2">Longitude</label>
                                <input type="number" 
                                       name="longitude" 
                                       id="longitude"
                                       value="{{ old('longitude') }}" 
                                       step="any"
                                       class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                       placeholder="115.2191">
                                @error('longitude')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <button type="button" 
                                    id="getCurrentLocation" 
                                    class="btn btn-secondary btn-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Gunakan Lokasi Saat Ini
                            </button>
                            <span id="locationStatus" class="text-sm text-muted-foreground ml-2"></span>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">Radius Absensi *</label>
                            <div class="flex items-center space-x-4">
                                <input type="range" 
                                       name="radius_meters" 
                                       id="radiusSlider" 
                                       min="10" 
                                       max="500" 
                                       value="{{ old('radius_meters', 100) }}" 
                                       class="flex-1"
                                       oninput="updateRadiusDisplay(this.value)">
                                <div class="flex items-center space-x-2">
                                    <span id="radiusDisplay" class="text-sm font-medium text-foreground">100</span>
                                    <span class="text-sm text-muted-foreground">meter</span>
                                </div>
                            </div>
                            <p class="text-xs text-muted-foreground mt-1">Jarak maksimum dari titik lokasi untuk absensi valid</p>
                            @error('radius_meters')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">SSID WiFi (Opsional)</label>
                            <input type="text" 
                                   name="wifi_ssid" 
                                   value="{{ old('wifi_ssid') }}" 
                                   class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                   placeholder="Nama jaringan WiFi untuk verifikasi tambahan">
                            @error('wifi_ssid')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center space-x-2">
                            <input type="checkbox" 
                                   name="is_active" 
                                   value="1"
                                   {{ old('is_active', true) ? 'checked' : '' }} 
                                   class="rounded border-input">
                            <label class="text-sm font-medium text-foreground">Aktifkan lokasi ini</label>
                        </div>
                    </div>
                </div>

                <!-- Map Preview -->
                <div class="bg-card rounded-lg border p-6">
                    <h3 class="text-lg font-semibold text-foreground mb-6">Preview Lokasi</h3>
                    
                    <div id="mapContainer" class="w-full h-96 rounded-md border border-input bg-muted flex items-center justify-center">
                        <div class="text-center text-muted-foreground">
                            <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <p>Masukkan koordinat untuk preview lokasi</p>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <h4 class="text-sm font-medium text-foreground mb-2">Metode Verifikasi:</h4>
                        <div id="verificationMethods" class="flex flex-wrap gap-2">
                            <span class="text-sm text-muted-foreground">Belum ada yang dikonfigurasi</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-3 pt-6 border-t">
                <a href="{{ route('locations.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Simpan Lokasi
                </button>
            </div>
        </form>
    </main>
</div>

@push('scripts')
<script>
function updateRadiusDisplay(value) {
    document.getElementById('radiusDisplay').textContent = value;
}

// Get current location
document.getElementById('getCurrentLocation').addEventListener('click', function() {
    const button = this;
    const status = document.getElementById('locationStatus');
    
    if (!navigator.geolocation) {
        status.textContent = 'Geolocation tidak didukung oleh browser ini.';
        status.className = 'text-sm text-red-500 ml-2';
        return;
    }
    
    button.disabled = true;
    button.innerHTML = `
        <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Mendapatkan lokasi...
    `;
    status.textContent = 'Meminta izin lokasi...';
    status.className = 'text-sm text-muted-foreground ml-2';
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            document.getElementById('latitude').value = position.coords.latitude.toFixed(6);
            document.getElementById('longitude').value = position.coords.longitude.toFixed(6);
            
            status.textContent = 'Lokasi berhasil diambil!';
            status.className = 'text-sm text-green-600 ml-2';
            updateVerificationMethods();
            
            button.disabled = false;
            button.innerHTML = `
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Gunakan Lokasi Saat Ini
            `;
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
                    message = 'Permintaan lokasi timeout.';
                    break;
            }
            
            status.textContent = message;
            status.className = 'text-sm text-red-500 ml-2';
            button.disabled = false;
            button.innerHTML = `
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Gunakan Lokasi Saat Ini
            `;
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 60000
        }
    );
});

// Update verification methods preview
function updateVerificationMethods() {
    const latitude = document.getElementById('latitude').value;
    const longitude = document.getElementById('longitude').value;
    const wifiSsid = document.querySelector('[name="wifi_ssid"]').value;
    
    let methods = [];
    
    if (latitude && longitude) {
        methods.push('<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">GPS</span>');
    }
    
    if (wifiSsid) {
        methods.push('<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">WiFi</span>');
    }
    
    const verificationMethodsDiv = document.getElementById('verificationMethods');
    if (methods.length > 0) {
        verificationMethodsDiv.innerHTML = methods.join(' ');
    } else {
        verificationMethodsDiv.innerHTML = '<span class="text-sm text-muted-foreground">Belum ada yang dikonfigurasi</span>';
    }
}

// Event listeners
document.getElementById('latitude').addEventListener('input', updateVerificationMethods);
document.getElementById('longitude').addEventListener('input', updateVerificationMethods);
document.querySelector('[name="wifi_ssid"]').addEventListener('input', updateVerificationMethods);

// Initialize
updateVerificationMethods();
</script>
@endpush
@endsection