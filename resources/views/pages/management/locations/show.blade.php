@extends('layouts.authenticated-unified')

@section('title', 'Detail Lokasi - ' . $location->name)

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Detail Lokasi: {{ $location->name }}"
            subtitle="Informasi lengkap tentang lokasi ini"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Manajemen'],
                ['label' => 'Lokasi', 'url' => route('locations.index')],
                ['label' => 'Detail Lokasi']
            ]">
            <x-slot name="actions">
                @can('manage_system_settings')
                <x-ui.button href="{{ route('locations.edit', $location) }}">
                    <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"/><path d="M16 5l3 3"/></svg>
                    Edit Lokasi
                </x-ui.button>
                @endcan
            </x-slot>
        </x-layouts.base-page>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Informasi Lokasi</h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="space-y-2">
                            <x-ui.label value="Nama Lokasi" class="text-slate-700 dark:text-slate-300" />
                            <div class="p-3 bg-white/10 rounded-lg border border-white/20 text-slate-800 dark:text-white">{{ $location->name }}</div>
                        </div>
                        
                        <div class="space-y-2">
                            <x-ui.label value="Alamat" class="text-slate-700 dark:text-slate-300" />
                            <div class="p-3 bg-white/10 rounded-lg border border-white/20 text-slate-800 dark:text-white">
                                @if($location->address)
                                    {{ $location->address }}
                                @else
                                    <span class="text-slate-600 dark:text-slate-400">Tidak disediakan</span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="space-y-2">
                            <x-ui.label value="Status" class="text-slate-700 dark:text-slate-300" />
                            <div class="p-3 bg-white/10 rounded-lg border border-white/20">
                                @if($location->is_active)
                                    <x-ui.badge variant="success">Aktif</x-ui.badge>
                                @else
                                    <x-ui.badge variant="destructive">Tidak Aktif</x-ui.badge>
                                @endif
                            </div>
                        </div>
                        
                        <div class="space-y-2">
                            <x-ui.label value="Koordinat GPS" class="text-slate-700 dark:text-slate-300" />
                            <div class="p-3 bg-white/10 rounded-lg border border-white/20 text-slate-800 dark:text-white">
                                @if($location->latitude && $location->longitude)
                                    {{ number_format($location->latitude, 6) }}, {{ number_format($location->longitude, 6) }}
                                    <a href="https://www.google.com/maps?q={{ $location->latitude }},{{ $location->longitude }}" 
                                       target="_blank" class="ml-2 text-blue-500 hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-300">
                                        <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        Lihat di Peta
                                    </a>
                                @else
                                    <span class="text-slate-600 dark:text-slate-400">Tidak diatur</span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="space-y-2">
                            <x-ui.label value="Radius yang Diizinkan" class="text-slate-700 dark:text-slate-300" />
                            <div class="p-3 bg-white/10 rounded-lg border border-white/20 text-slate-800 dark:text-white">{{ $location->radius_meters }} meter</div>
                        </div>
                        
                        <div class="space-y-2">
                            <x-ui.label value="Jaringan WiFi" class="text-slate-700 dark:text-slate-300" />
                            <div class="p-3 bg-white/10 rounded-lg border border-white/20 text-slate-800 dark:text-white">
                                @if($location->wifi_ssid)
                                    <code>{{ $location->wifi_ssid }}</code>
                                @else
                                    <span class="text-slate-600 dark:text-slate-400">Tidak dikonfigurasi</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Karyawan Ditugaskan ({{ $location->employees->count() }})</h3>
                    <div class="overflow-x-auto">
                        @if($location->employees->count() > 0)
                            <table class="min-w-full divide-y divide-white/20">
                                <thead class="bg-white/10 backdrop-blur-sm">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">ID Karyawan</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Nama</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Tipe</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Peran</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white/5 backdrop-blur-sm divide-y divide-white/10">
                                    @foreach($location->employees as $employee)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-800 dark:text-white">{{ $employee->employee_id }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ route('employees.show', $employee) }}" class="text-blue-500 hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-300">
                                                {{ $employee->full_name }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $colors = [
                                                    'permanent' => 'green',
                                                    'honorary' => 'blue',
                                                    'staff' => 'gray'
                                                ];
                                                $color = $colors[$employee->employee_type] ?? 'secondary';
                                            @endphp
                                            <x-ui.badge variant="{{ $color }}">{{ ucfirst($employee->employee_type) }}</x-ui.badge>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @foreach($employee->user->roles as $role)
                                                <x-ui.badge variant="info">{{ ucfirst($role->name) }}</x-ui.badge>
                                            @endforeach
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($employee->is_active)
                                                <x-ui.badge variant="success">Aktif</x-ui.badge>
                                            @else
                                                <x-ui.badge variant="destructive">Tidak Aktif</x-ui.badge>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="text-center py-8 text-slate-600 dark:text-slate-400">
                                <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                <div>Tidak ada karyawan yang ditugaskan</div>
                                <small>Karyawan dapat ditugaskan ke lokasi ini di pengaturan profil mereka</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="lg:col-span-1 space-y-6">
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Metode Verifikasi</h3>
                    <div class="space-y-3 text-slate-600 dark:text-slate-400">
                        @php
                            $hasGPS = $location->latitude && $location->longitude;
                            $hasWiFi = $location->wifi_ssid;
                        @endphp
                        
                        @if($hasGPS)
                        <div class="flex items-center mb-3">
                            <x-ui.badge variant="info" class="mr-2">GPS</x-ui.badge>
                            <div>
                                <div class="font-medium text-slate-800 dark:text-white">Verifikasi berbasis lokasi</div>
                                <div class="text-sm">Dalam radius {{ $location->radius_meters }}m</div>
                            </div>
                        </div>
                        @endif
                        
                        @if($hasWiFi)
                        <div class="flex items-center mb-3">
                            <x-ui.badge variant="success" class="mr-2">WiFi</x-ui.badge>
                            <div>
                                <div class="font-medium text-slate-800 dark:text-white">Verifikasi berbasis jaringan</div>
                                <div class="text-sm">Terhubung ke "{{ $location->wifi_ssid }}"</div>
                            </div>
                        </div>
                        @endif
                        
                        @if(!$hasGPS && !$hasWiFi)
                        <div class="text-center py-4">
                            <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <div>Tidak ada metode verifikasi</div>
                            <small>Konfigurasi verifikasi GPS atau WiFi</small>
                        </div>
                        @endif
                    </div>
                </div>
                
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Statistik Cepat</h3>
                    <div class="grid grid-cols-2 gap-4 text-slate-600 dark:text-slate-400">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-slate-800 dark:text-white">{{ $location->employees->count() }}</div>
                            <div class="text-sm">Total Karyawan</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-slate-800 dark:text-white">{{ $location->employees->where('is_active', true)->count() }}</div>
                            <div class="text-sm">Aktif</div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="font-medium text-slate-800 dark:text-white">Metode Verifikasi</div>
                        <div class="text-3xl font-bold text-blue-500">{{ ($hasGPS ? 1 : 0) + ($hasWiFi ? 1 : 0) }}</div>
                        <div class="w-full bg-white/20 rounded-full h-2 mt-2">
                            @php $completeness = (($hasGPS ? 50 : 0) + ($hasWiFi ? 50 : 0)); @endphp
                            <div class="bg-gradient-to-r from-blue-500 to-cyan-500 h-2 rounded-full" style="width: {{ $completeness }}%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Detail Lokasi</h3>
                    <div class="space-y-3 text-slate-600 dark:text-slate-400">
                        <div class="flex justify-between"><span class="font-medium">Dibuat:</span><span class="text-slate-800 dark:text-white">{{ $location->created_at->format('M j, Y') }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Terakhir Diperbarui:</span><span class="text-slate-800 dark:text-white">{{ $location->updated_at->format('M j, Y g:i A') }}</span></div>
                        @if($location->metadata)
                        <div class="flex justify-between"><span class="font-medium">Data Tambahan:</span><span class="text-slate-800 dark:text-white">Tersedia</span></div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
