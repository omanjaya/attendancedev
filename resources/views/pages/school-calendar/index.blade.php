@extends('layouts.authenticated-unified')

@section('title', 'Kalender Sekolah')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Kalender Sekolah {{ $currentYear }}"
            subtitle="Kalender tahun ajaran termasuk hari libur, jeda, dan acara sekolah"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Kalender Sekolah']
            ]">
            <x-slot name="actions">
                <x-ui.button variant="primary" href="{{ route('school-calendar.academic') }}">
                    <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                    Kalender Akademik
                </x-ui.button>
                
                <x-ui.button variant="secondary" href="{{ route('school-calendar.public') }}">
                    <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Hari Libur Nasional
                </x-ui.button>
                
                @can('create_holidays')
                <x-ui.button variant="primary" href="{{ route('holidays.create') }}">
                    <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    Tambah Hari Libur
                </x-ui.button>
                @endcan
            </x-slot>
        </x-layouts.base-page>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            @php
                $totalHolidays = $holidays->flatten()->count();
                $publicHolidays = $holidays->get('public_holiday', collect())->count() + $holidays->get('religious_holiday', collect())->count();
                $schoolHolidays = $holidays->get('school_holiday', collect())->count();
                $substituteHolidays = $holidays->get('substitute_holiday', collect())->count();
            @endphp

            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Total Hari Libur</p>
                        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">{{ $totalHolidays }}</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                </div>
            </div>

            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Hari Libur Nasional</p>
                        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">{{ $publicHolidays }}</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-rose-600 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>
            </div>

            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Hari Libur Sekolah</p>
                        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">{{ $schoolHolidays }}</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                    </div>
                </div>
            </div>

            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Hari Pengganti</p>
                        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">{{ $substituteHolidays }}</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Holiday Categories -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            @foreach($holidays as $type => $typeHolidays)
                @if($typeHolidays->isNotEmpty())
                    <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                        <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4 flex items-center">
                            <div class="w-4 h-4 rounded-full mr-3
                                @switch($type)
                                    @case('public_holiday') bg-red-500 @break
                                    @case('religious_holiday') bg-green-500 @break
                                    @case('school_holiday') bg-blue-500 @break
                                    @case('substitute_holiday') bg-purple-500 @break
                                    @default bg-gray-500
                                @endswitch
                            "></div>
                            @switch($type)
                                @case('public_holiday') Hari Libur Nasional @break
                                @case('religious_holiday') Hari Libur Keagamaan @break
                                @case('school_holiday') Hari Libur Sekolah @break
                                @case('substitute_holiday') Hari Libur Pengganti @break
                                @default Hari Libur Lainnya
                            @endswitch
                            <span class="ml-2 text-sm text-slate-600 dark:text-slate-400">({{ $typeHolidays->count() }})</span>
                        </h3>
                        
                        <div class="space-y-3">
                            @foreach($typeHolidays->sortBy('date') as $holiday)
                                <div class="flex items-center justify-between p-3 bg-white/10 rounded-lg hover:bg-white/20 transition-colors">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0 h-2 w-2 rounded-full" style="background-color: {{ $holiday->color }}"></div>
                                        <div>
                                            <div class="text-sm font-medium text-slate-800 dark:text-white">{{ $holiday->name }}</div>
                                            <div class="text-sm text-slate-500 dark:text-slate-400">
                                                {{ $holiday->date->format('d M Y') }}
                                                @if($holiday->end_date && $holiday->end_date != $holiday->date)
                                                    - {{ $holiday->end_date->format('d M Y') }}
                                                @endif
                                                ({{ $holiday->date->format('l') }})
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-2">
                                        @if($holiday->is_recurring)
                                            <span class="inline-flex items-center text-green-500" title="Hari libur berulang">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" clip-rule="evenodd" /></svg>
                                            </span>
                                        @endif
                                        
                                        @if($holiday->is_paid)
                                            <span class="inline-flex items-center text-green-500" title="Hari libur berbayar">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                            </span>
                                        @endif
                                        
                                        <a href="{{ route('holidays.show', $holiday) }}" 
                                           class="text-blue-500 hover:text-blue-600 text-sm">Lihat</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        @if($holidays->flatten()->isEmpty())
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                <div class="text-center py-8 text-slate-600 dark:text-slate-400">
                    <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-2 text-xl font-semibold text-slate-800 dark:text-white">Tidak ada hari libur terjadwal</h3>
                    <p class="mt-1 text-sm">Tidak ada hari libur yang saat ini dijadwalkan untuk {{ $currentYear }}.</p>
                    @can('create_holidays')
                    <div class="mt-6">
                        <x-ui.button href="{{ route('holidays.create') }}" variant="primary">
                            <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                            Tambah Hari Libur Pertama
                        </x-ui.button>
                    </div>
                    @endcan
                </div>
            </div>
        @endif

        <!-- Calendar Navigation -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
            <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Navigasi Kalender</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('holidays.calendar') }}" 
                   class="group flex items-center p-4 border border-white/20 rounded-lg hover:bg-white/10 transition-colors">
                    <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-lg font-semibold text-slate-800 dark:text-white">Tampilan Kalender Penuh</h4>
                        <p class="text-sm text-slate-600 dark:text-slate-400">Lihat semua hari libur dalam format kalender</p>
                    </div>
                </a>
                
                <a href="{{ route('holidays.index') }}" 
                   class="group flex items-center p-4 border border-white/20 rounded-lg hover:bg-white/10 transition-colors">
                    <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-lg font-semibold text-slate-800 dark:text-white">Manajemen Hari Libur</h4>
                        <p class="text-sm text-slate-600 dark:text-slate-400">Kelola dan konfigurasikan hari libur</p>
                    </div>
                </a>
                
                @can('manage_holidays')
                <a href="{{ route('holidays.index') }}#import" 
                   class="group flex items-center p-4 border border-white/20 rounded-lg hover:bg-white/10 transition-colors">
                    <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" /></svg>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-lg font-semibold text-slate-800 dark:text-white">Impor Hari Libur</h4>
                        <p class="text-sm text-slate-600 dark:text-slate-400">Impor dari sumber pemerintah atau file</p>
                    </div>
                </a>
                @endcan
            </x-slot>
        </x-layouts.base-page>
    </div>
</div>
@endsection
