@extends('layouts.authenticated-unified')

@section('title', 'Profile Settings')

@section('page-content')
<div x-data="profileManager()">
    <!-- Modern Page Header with Enhanced Actions -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Profil Saya</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola informasi akun, keamanan, dan pengaturan biometrik</p>
            </div>
            <div class="flex items-center space-x-3">
                <!-- Secondary Actions -->
                <button type="button" @click="exportProfile()" class="bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 border border-gray-300 dark:border-gray-600 px-4 py-2 rounded-lg font-medium transition-all duration-200 flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                    </svg>
                    <span>Export Data</span>
                </button>
                <button type="button" onclick="window.history.back()" class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Kembali</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Profile Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Account Status Card -->
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg shadow-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-sm text-green-600 bg-green-100 dark:bg-green-900/30 px-2 py-1 rounded-full">Aktif</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ auth()->user()->name }}</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">{{ auth()->user()->email }}</p>
            </div>
        </x-ui.card>

        <!-- Security Level Card -->
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <span class="text-sm text-blue-600 bg-blue-100 dark:bg-blue-900/30 px-2 py-1 rounded-full">Tinggi</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">85%</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Tingkat Keamanan</p>
            </div>
        </x-ui.card>

        <!-- Last Login Card -->
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-r from-purple-500 to-pink-600 rounded-lg shadow-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-sm text-purple-600 bg-purple-100 dark:bg-purple-900/30 px-2 py-1 rounded-full">Terbaru</span>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ now()->format('H:i') }}</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Login Terakhir</p>
            </div>
        </x-ui.card>

        <!-- Profile Completion Card -->
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-r from-amber-500 to-orange-600 rounded-lg shadow-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-sm text-amber-600 bg-amber-100 dark:bg-amber-900/30 px-2 py-1 rounded-full">90%</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Lengkap</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Profil Terisi</p>
            </div>
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
                    @include('pages.profile.update_profile_information_form')
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
                    @include('pages.profile.update_password_form')
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
                    @include('pages.profile.delete_user_form')
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
