@extends('layouts.authenticated-unified')

@section('title', 'Profile Settings')

@section('page-content')
<!-- Page Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">My Profile</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola informasi akun dan pengaturan keamanan Anda</p>
        </div>
        <div class="flex items-center space-x-3">
            <button type="button" onclick="window.history.back()" class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                Kembali
            </button>
        </div>
    </div>
</div>

<div class="space-y-8 max-w-4xl mx-auto">
    <!-- Profile Information Card -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Informasi Profil</h3>
            <p class="text-gray-600 dark:text-gray-400">Perbarui informasi profil dan alamat email akun Anda</p>
        </div>
        <div class="p-6">
            <div class="max-w-xl">
                @include('pages.profile.update_profile_information_form')
            </div>
        </div>
    </x-ui.card>

    <!-- Update Password Card -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Perbarui Kata Sandi</h3>
            <p class="text-gray-600 dark:text-gray-400">Pastikan akun Anda menggunakan kata sandi yang panjang dan acak agar tetap aman</p>
        </div>
        <div class="p-6">
            <div class="max-w-xl">
                @include('pages.profile.update_password_form')
            </div>
        </div>
    </x-ui.card>

    <!-- Delete Account Card -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-xl font-semibold text-red-600 dark:text-red-400 mb-2">Hapus Akun</h3>
            <p class="text-gray-600 dark:text-gray-400">Setelah akun Anda dihapus, semua sumber daya dan datanya akan dihapus secara permanen</p>
        </div>
        <div class="p-6">
            <div class="max-w-xl">
                @include('pages.profile.delete_user_form')
            </div>
        </div>
    </x-ui.card>
</div>
@endsection
