@extends('layouts.authenticated-unified')

@section('title', 'Demo Pengalih Peran')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Demo Pengalih Peran"
            subtitle="Menguji fitur pengalih peran untuk superadmin"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Demo'],
                ['label' => 'Pengalih Peran']
            ]">
        </x-layouts.base-page>

        <!-- Current Status -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mb-8">
            <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Status Saat Ini</h2>
            
            @php
                $currentRole = Auth::user()->roles->first()->name ?? 'Unknown';
                $originalRole = Session::get('original_role');
            @endphp
            
            @if($originalRole)
                <div class="bg-amber-500/20 backdrop-blur-sm border border-amber-500/30 rounded-lg p-4 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                            <div>
                                <p class="font-medium text-amber-800 dark:text-amber-200">Mode Pengalih Peran Aktif</p>
                                <p class="text-sm text-amber-700 dark:text-amber-300">
                                    Peran saat ini: <strong>{{ ucfirst($currentRole) }}</strong>
                                </p>
                                <p class="text-xs text-amber-600 dark:text-amber-400">
                                    Peran asli: {{ ucfirst($originalRole) }}
                                </p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('role.restore') }}">
                            @csrf
                            <x-ui.button type="submit" variant="warning">Kembali ke {{ ucfirst($originalRole) }}</x-ui.button>
                        </form>
                    </div>
                </div>
            @else
                <div class="bg-blue-500/20 backdrop-blur-sm border border-blue-500/30 rounded-lg p-4 shadow-lg">
                    <div class="flex items-center space-x-3">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="font-medium text-blue-800 dark:text-blue-200">Mode Normal</p>
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                Peran saat ini: <strong>{{ ucfirst($currentRole) }}</strong>
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Quick Role Switch -->
        @if(Auth::user()->hasRole('superadmin') || Session::has('original_role'))
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mb-8">
            <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Pengalih Peran Cepat</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 border-2 {{ $currentRole === 'teacher' ? 'border-emerald-500 bg-emerald-500/10' : 'border-white/20' }} rounded-lg">
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-medium text-slate-800 dark:text-white">Guru</h3>
                            <p class="text-sm text-slate-600 dark:text-slate-400">Pembelajaran & Pengajaran</p>
                        </div>
                    </div>
                    @if($currentRole === 'teacher')
                        <span class="px-3 py-1 bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 text-sm rounded-full">Aktif</span>
                    @else
                        <form method="POST" action="{{ route('role.switch', 'teacher') }}" class="inline">
                            @csrf
                            <x-ui.button type="submit" variant="primary">Alihkan</x-ui.button>
                        </form>
                    @endif
                </div>

                <div class="p-4 border-2 {{ $currentRole === 'admin' ? 'border-blue-500 bg-blue-500/10' : 'border-white/20' }} rounded-lg">
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-medium text-slate-800 dark:text-white">Admin</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400">Administrasi & Operasional</p>
                    </div>
                </div>
                @if($currentRole === 'admin')
                    <span class="px-3 py-1 bg-blue-500/20 text-blue-700 dark:text-blue-300 text-sm rounded-full">Aktif</span>
                @else
                    <form method="POST" action="{{ route('role.switch', 'admin') }}" class="inline">
                        @csrf
                        <x-ui.button type="submit" variant="primary">Alihkan</x-ui.button>
                    </form>
                @endif
            </div>

            <div class="p-4 border-2 {{ $currentRole === 'staff' ? 'border-slate-500 bg-slate-500/10' : 'border-white/20' }} rounded-lg">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-gray-500 to-slate-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                    <div>
                        <h3 class="font-medium text-slate-800 dark:text-white">Staf</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400">Dukungan & Layanan</p>
                    </div>
                </div>
                @if($currentRole === 'staff')
                    <span class="px-3 py-1 bg-slate-500/20 text-slate-700 dark:text-slate-300 text-sm rounded-full">Aktif</span>
                @else
                    <form method="POST" action="{{ route('role.switch', 'staff') }}" class="inline">
                        @csrf
                        <x-ui.button type="submit" variant="primary">Alihkan</x-ui.button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Header Preview -->
    <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mb-8">
        <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Pratinjau Header</h2>
        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Lihat bagaimana header berubah sesuai peran:</p>
        
        <div class="bg-white/10 p-4 rounded-lg text-slate-800 dark:text-white">
            <p class="text-sm"><strong>Peran Saat Ini:</strong> {{ ucfirst($currentRole) }}</p>
            <p class="text-sm"><strong>Warna Header:</strong> 
                @if($currentRole === 'teacher')
                    <span class="text-emerald-600">Zamrud (Guru)</span>
                @elseif($currentRole === 'admin')
                    <span class="text-blue-600">Biru (Admin)</span>
                @elseif($currentRole === 'staff')
                    <span class="text-slate-600">Abu-abu (Staf)</span>
                @else
                    <span class="text-purple-600">Ungu (Superadmin)</span>
                @endif
            </p>
            <p class="text-sm"><strong>Akses Navigasi:</strong> Izin sesuai peran</p>
        </div>
    </div>

    <!-- Instructions -->
    <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mb-8">
        <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Cara Menguji</h2>
        
        <div class="space-y-3 text-slate-600 dark:text-slate-400">
            <div class="flex items-start space-x-3">
                <span class="flex-shrink-0 w-6 h-6 bg-blue-500/20 text-blue-500 text-sm rounded-full flex items-center justify-center font-medium">1</span>
                <p class="text-sm">Klik tombol "Alihkan" pada salah satu peran di atas</p>
            </div>
            
            <div class="flex items-start space-x-3">
                <span class="flex-shrink-0 w-6 h-6 bg-blue-500/20 text-blue-500 text-sm rounded-full flex items-center justify-center font-medium">2</span>
                <p class="text-sm">Perhatikan header berubah warna dan ikon sesuai peran</p>
            </div>
            
            <div class="flex items-start space-x-3">
                <span class="flex-shrink-0 w-6 h-6 bg-blue-500/20 text-blue-500 text-sm rounded-full flex items-center justify-center font-medium">3</span>
                <p class="text-sm">Cek dasbor untuk melihat perubahan konten sesuai peran</p>
            </div>
            
            <div class="flex items-start space-x-3">
                <span class="flex-shrink-0 w-6 h-6 bg-blue-500/20 text-blue-500 text-sm rounded-full flex items-center justify-center font-medium">4</span>
                <p class="text-sm">Klik "Kembali ke Superadmin" di banner kuning untuk restore</p>
            </div>
        </div>
    </div>
</div>
@endsection
