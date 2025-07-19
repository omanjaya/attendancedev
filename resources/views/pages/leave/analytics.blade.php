@extends('layouts.authenticated-unified')

@section('title', 'Analitik Cuti')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Analitik Cuti"
            subtitle="Analisis pola dan tren cuti"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Cuti', 'url' => route('leave.index')],
                ['label' => 'Analitik']
            ]">
            <x-slot name="actions">
                <x-ui.button variant="secondary" href="{{ route('leave.calendar') }}">
                    <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Tampilan Kalender
                </x-ui.button>
                <x-ui.button variant="primary" href="{{ route('leave.index') }}">
                    <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Permintaan Cuti
                </x-ui.button>
            </x-slot>
        </x-layouts.base-page>

        <!-- Analytics Dashboard -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="lg:col-span-2 group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Ikhtisar Statistik Cuti</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 rounded-lg bg-white/10">
                        <div class="text-2xl font-bold text-slate-800 dark:text-white">156</div>
                        <div class="text-sm text-slate-600 dark:text-slate-400">Total Permintaan</div>
                        <div class="text-xs text-emerald-600 mt-1">+12% vs bulan lalu</div>
                    </div>
                    
                    <div class="text-center p-4 rounded-lg bg-green-500/10">
                        <div class="text-2xl font-bold text-green-600">142</div>
                        <div class="text-sm text-slate-600 dark:text-slate-400">Disetujui</div>
                        <div class="text-xs text-slate-600 dark:text-slate-400 mt-1">Tingkat persetujuan 91.0%</div>
                    </div>
                    
                    <div class="text-center p-4 rounded-lg bg-amber-500/10">
                        <div class="text-2xl font-bold text-amber-600">8</div>
                        <div class="text-sm text-slate-600 dark:text-slate-400">Tertunda</div>
                        <div class="text-xs text-slate-600 dark:text-slate-400 mt-1">Rata-rata 2.5 hari</div>
                    </div>
                    
                    <div class="text-center p-4 rounded-lg bg-red-500/10">
                        <div class="text-2xl font-bold text-red-600">6</div>
                        <div class="text-sm text-slate-600 dark:text-slate-400">Ditolak</div>
                        <div class="text-xs text-slate-600 dark:text-slate-400 mt-1">Tingkat penolakan 3.8%</div>
                    </div>
                </div>
                
                <div class="mt-6 p-6 bg-white/10 rounded-lg">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Tren Cuti Bulanan</h3>
                    <div class="h-64 flex items-center justify-center text-slate-600 dark:text-slate-400">
                        <div class="text-center">
                            <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <p>Visualisasi grafik akan diimplementasikan</p>
                            <p class="text-sm mt-1">Integrasi dengan Chart.js atau library serupa</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div>
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mb-6">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Rincian Departemen</h3>
                    <div class="space-y-4 text-slate-600 dark:text-slate-400">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                <span class="text-sm font-medium">Matematika</span>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-bold text-slate-800 dark:text-white">24</div>
                                <div class="text-xs">15.4%</div>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                <span class="text-sm font-medium">Sains</span>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-bold text-slate-800 dark:text-white">18</div>
                                <div class="text-xs">11.5%</div>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 bg-amber-500 rounded-full"></div>
                                <span class="text-sm font-medium">Bahasa Inggris</span>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-bold text-slate-800 dark:text-white">15</div>
                                <div class="text-xs">9.6%</div>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                <span class="text-sm font-medium">Administrasi</span>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-bold text-slate-800 dark:text-white">12</div>
                                <div class="text-xs">7.7%</div>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 bg-slate-500 rounded-full"></div>
                                <span class="text-sm font-medium">Lainnya</span>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-bold text-slate-800 dark:text-white">87</div>
                                <div class="text-xs">55.8%</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Tipe Cuti</h3>
                    <div class="space-y-3 text-slate-600 dark:text-slate-400">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">Cuti Tahunan</span>
                            <div class="flex items-center gap-2">
                                <div class="w-16 bg-white/20 rounded-full h-2">
                                    <div class="w-12 bg-blue-500 rounded-full h-2"></div>
                                </div>
                                <span class="text-sm font-medium text-slate-800 dark:text-white">45%</span>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">Cuti Sakit</span>
                            <div class="flex items-center gap-2">
                                <div class="w-16 bg-white/20 rounded-full h-2">
                                    <div class="w-8 bg-amber-500 rounded-full h-2"></div>
                                </div>
                                <span class="text-sm font-medium text-slate-800 dark:text-white">32%</span>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">Darurat</span>
                            <div class="flex items-center gap-2">
                                <div class="w-16 bg-white/20 rounded-full h-2">
                                    <div class="w-4 bg-red-500 rounded-full h-2"></div>
                                </div>
                                <span class="text-sm font-medium text-slate-800 dark:text-white">15%</span>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">Pribadi</span>
                            <div class="flex items-center gap-2">
                                <div class="w-16 bg-white/20 rounded-full h-2">
                                    <div class="w-2 bg-blue-500 rounded-full h-2"></div>
                                </div>
                                <span class="text-sm font-medium text-slate-800 dark:text-white">8%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
            <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Aktivitas Cuti Terbaru</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/20">
                    <thead class="bg-white/10 backdrop-blur-sm">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Karyawan</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Tipe Cuti</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Durasi</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Dikirim</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white/5 backdrop-blur-sm divide-y divide-white/10">
                        <!-- Sample Data -->
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-sm font-bold">
                                        JD
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-slate-800 dark:text-white">John Doe</div>
                                        <div class="text-sm text-slate-500 dark:text-slate-400">Matematika</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-800 dark:text-white">Cuti Tahunan</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">3 hari</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-ui.badge variant="success">Disetujui</x-ui.badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">2 hari yang lalu</td>
                        </tr>
                        
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center text-white text-sm font-bold">
                                        JS
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-slate-800 dark:text-white">Jane Smith</div>
                                        <div class="text-sm text-slate-500 dark:text-slate-400">Sains</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-800 dark:text-white">Cuti Sakit</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">1 hari</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-ui.badge variant="warning">Tertunda</x-ui.badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">1 hari yang lalu</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
