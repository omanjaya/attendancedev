@extends('layouts.authenticated')

@section('title', 'Profile Settings')

@section('page-content')
<div x-data="profileManager()">
    <!-- Page Header - macOS Style -->
    <div class="page-header">
        <div class="page-header-flex">
            <div>
                <h1 class="page-title">Profil Saya</h1>
                <p class="page-desc">Kelola informasi akun, keamanan, dan pengaturan biometrik</p>
            </div>
            <div class="page-actions">
                <x-ui.button variant="outline" @click="exportProfile()">
                    <x-icons.arrow-down-tray class="w-4 h-4" />
                    <span class="hidden sm:inline">Export Data</span>
                </x-ui.button>
                <x-ui.button variant="secondary" onclick="window.history.back()">
                    <x-icons.arrow-left class="w-4 h-4" />
                    <span class="hidden sm:inline">Kembali</span>
                </x-ui.button>
            </div>
        </div>
    </div>

    <!-- Profile Statistics Cards - macOS Style (Responsive) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <!-- Account Status Card -->
        <x-ui.card class="p-3 sm:p-4">
            <div class="flex items-center justify-between mb-2 sm:mb-3">
                <x-icons.user-circle class="w-4 h-4 sm:w-5 sm:h-5 text-success" />
                <span class="badge badge-success text-[10px] sm:text-xs">Aktif</span>
            </div>
            <p class="metric-label mb-0.5 sm:mb-1 truncate">{{ auth()->user()->name }}</p>
            <p class="text-[10px] sm:text-xs text-muted-foreground truncate">{{ auth()->user()->email }}</p>
        </x-ui.card>

        <!-- Security Level Card -->
        <x-ui.card class="p-3 sm:p-4">
            <div class="flex items-center justify-between mb-2 sm:mb-3">
                <x-icons.shield class="w-4 h-4 sm:w-5 sm:h-5 text-primary" />
                <span class="badge badge-info text-[10px] sm:text-xs">Tinggi</span>
            </div>
            <p class="metric-label mb-0.5 sm:mb-1">Tingkat Keamanan</p>
            <p class="metric-value">85%</p>
        </x-ui.card>

        <!-- Last Login Card -->
        <x-ui.card class="p-3 sm:p-4">
            <div class="flex items-center justify-between mb-2 sm:mb-3">
                <x-icons.clock class="w-4 h-4 sm:w-5 sm:h-5 text-purple-500" />
                <span class="badge badge-purple text-[10px] sm:text-xs">Terbaru</span>
            </div>
            <p class="metric-label mb-0.5 sm:mb-1">Login Terakhir</p>
            <p class="metric-value">{{ now()->format('H:i') }}</p>
        </x-ui.card>

        <!-- Profile Completion Card -->
        <x-ui.card class="p-3 sm:p-4">
            <div class="flex items-center justify-between mb-2 sm:mb-3">
                <x-icons.check-circle class="w-4 h-4 sm:w-5 sm:h-5 text-warning" />
                <span class="badge badge-warning text-[10px] sm:text-xs">90%</span>
            </div>
            <p class="metric-label mb-0.5 sm:mb-1">Profil Terisi</p>
            <p class="metric-value">Lengkap</p>
        </x-ui.card>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Profile Section (2 columns) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Profile Information Card -->
            <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Informasi Profil</h3>
                            <p class="text-gray-600 dark:text-gray-400">Perbarui informasi profil dan pengaturan biometrik</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-gray-500 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-full">Dengan Face Recognition</span>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    @include('pages.profile.update-profile-information-form')
                </div>
            </x-ui.card>

            <!-- Security Settings Card -->
            <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Keamanan Akun</h3>
                            <p class="text-gray-600 dark:text-gray-400">Pastikan akun menggunakan kata sandi yang kuat</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            <span class="text-xs text-green-600">Aman</span>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    @include('pages.profile.update-password-form')
                </div>
            </x-ui.card>

            <!-- Danger Zone Card -->
            <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-red-200 dark:border-red-800">
                <div class="p-6 border-b border-red-200 dark:border-red-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-semibold text-red-600 dark:text-red-400 mb-2">Zona Berbahaya</h3>
                            <p class="text-gray-600 dark:text-gray-400">Tindakan irreversible yang memerlukan konfirmasi</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    @include('pages.profile.delete-user-form')
                </div>
            </x-ui.card>
        </div>

        <!-- Quick Actions Sidebar -->
        <div class="space-y-6">
            <!-- Profile Quick Actions -->
            <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Aksi Cepat</h3>
                </div>
                <div class="p-6 space-y-3">
                    <button type="button" @click="changePassword()" class="w-full text-left px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-3.586l6.879-6.879A6 6 0 0121 9z"/>
                            </svg>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">Ubah Password</div>
                                <div class="text-xs text-gray-500">Update keamanan akun</div>
                            </div>
                        </div>
                    </button>

                    <button type="button" @click="manageBiometric()" class="w-full text-left px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">Kelola Biometrik</div>
                                <div class="text-xs text-gray-500">Face recognition setup</div>
                            </div>
                        </div>
                    </button>

                    <button type="button" @click="downloadData()" class="w-full text-left px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">Download Data</div>
                                <div class="text-xs text-gray-500">Export informasi profil</div>
                            </div>
                        </div>
                    </button>

                    <button type="button" @click="viewActivity()" class="w-full text-left px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">Log Aktivitas</div>
                                <div class="text-xs text-gray-500">Riwayat aktivitas akun</div>
                            </div>
                        </div>
                    </button>
                </div>
            </x-ui.card>

            <!-- Security Summary -->
            <x-ui.card class="bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800">
                <div class="p-6">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="p-2 bg-blue-600 rounded-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-blue-900 dark:text-blue-100">Status Keamanan</h4>
                            <p class="text-sm text-blue-700 dark:text-blue-300">Tingkat perlindungan akun</p>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-blue-700 dark:text-blue-300">Password Strong</span>
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-blue-700 dark:text-blue-300">Email Verified</span>
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-blue-700 dark:text-blue-300">Face Recognition</span>
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            <!-- Recent Activity -->
            <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Aktivitas Terbaru</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Profile updated</div>
                                <div class="text-xs text-gray-500">2 jam yang lalu</div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Login successful</div>
                                <div class="text-xs text-gray-500">1 hari yang lalu</div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Password changed</div>
                                <div class="text-xs text-gray-500">3 hari yang lalu</div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>
</div>

@push('scripts')
<script>
function profileManager() {
    return {
        init() {
            console.log('Profile manager initialized');
        },
        
        exportProfile() {
            // Export profile functionality
            console.log('Exporting profile data...');
        },
        
        changePassword() {
            // Scroll to password section
            document.querySelector('[name="current_password"]').focus();
        },
        
        manageBiometric() {
            // Scroll to biometric section
            document.querySelector('#face_photo').focus();
        },
        
        downloadData() {
            // Download user data
            console.log('Downloading user data...');
        },
        
        viewActivity() {
            // Show activity log
            console.log('Viewing activity log...');
        }
    }
}
</script>
@endpush
@endsection
