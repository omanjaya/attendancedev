@extends('layouts.authenticated-unified')

@section('title', 'Buat Pengguna Baru')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Buat Pengguna Baru"
            subtitle="Manajemen Pengguna - Tambah akun pengguna baru ke sistem"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Manajemen'],
                ['label' => 'Pengguna', 'url' => route('users.index')],
                ['label' => 'Buat Baru']
            ]">
            <x-slot name="actions">
                <x-ui.button variant="secondary" href="{{ route('users.index') }}">
                    <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali ke Pengguna
                </x-ui.button>
            </x-slot>
        </x-layouts.base-page>

        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
            <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Informasi Pengguna</h3>
            <form id="createUserForm" action="{{ route('users.store') }}" method="POST" class="space-y-6">
                @csrf
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="space-y-2">
                        <x-ui.label for="name" value="Nama" required class="text-slate-700 dark:text-slate-300" />
                        <x-ui.input type="text" name="name" id="name" required 
                                   class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                        @error('name')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="space-y-2">
                        <x-ui.label for="email" value="Email" required class="text-slate-700 dark:text-slate-300" />
                        <x-ui.input type="email" name="email" id="email" required 
                                   class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                        @error('email')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="space-y-2">
                        <x-ui.label for="password" value="Kata Sandi" required class="text-slate-700 dark:text-slate-300" />
                        <x-ui.input type="password" name="password" id="password" required 
                                   class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                        @error('password')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="space-y-2">
                        <x-ui.label for="password_confirmation" value="Konfirmasi Kata Sandi" required class="text-slate-700 dark:text-slate-300" />
                        <x-ui.input type="password" name="password_confirmation" id="password_confirmation" required 
                                   class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                        @error('password_confirmation')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="space-y-2">
                        <x-ui.label for="roles" value="Peran" required class="text-slate-700 dark:text-slate-300" />
                        <x-ui.select name="roles[]" id="roles" multiple required 
                                   class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ ucfirst($role->name) }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                    <div class="space-y-2">
                        <x-ui.label for="is_active" value="Status" class="text-slate-700 dark:text-slate-300" />
                        <div class="flex items-center space-x-3">
                            <input type="hidden" name="is_active" value="0">
                            <x-ui.checkbox name="is_active" value="1" checked />
                            <span class="text-sm font-medium text-slate-800 dark:text-white">Aktif</span>
                        </div>
                    </div>
                </div>
                <div class="form-footer flex justify-end space-x-3">
                    <x-ui.button type="submit" variant="primary">
                        <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                        Buat Pengguna
                    </x-ui.button>
                    <x-ui.button type="button" variant="secondary" href="{{ route('users.index') }}">
                        Batal
                    </x-ui.button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
