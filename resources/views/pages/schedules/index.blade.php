@extends('layouts.authenticated-unified')

@section('title', 'Schedule Management')

@section('page-content')
<!-- Page Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Schedule Management</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola jadwal pelajaran dan penugasan guru</p>
        </div>
        <div class="flex items-center space-x-3">
            <button onclick="openImportModal()" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                Import Jadwal
            </button>
            <button onclick="location.href='{{ route('schedules.calendar') }}'" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Tampilan Kalender
            </button>
            <button onclick="location.href='{{ route('schedules.builder') }}'" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Schedule Builder
            </button>
            <button onclick="location.href='{{ route('schedules.create') }}'" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Tambah Jadwal
            </button>
        </div>
    </div>
</div>

<!-- Statistics Cards Section -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Periods -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-blue-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <span class="text-sm text-blue-600">Periode</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">{{ $periods->count() ?? 0 }}</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Total Periode</p>
    </x-ui.card>

    <!-- Active Teachers -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-green-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg>
            </div>
            <span class="text-sm text-green-600">Guru</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">{{ $employees->where('employee_type', 'honorary')->count() ?? 0 }}</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Guru Aktif</p>
    </x-ui.card>

    <!-- Total Schedules -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-purple-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <span class="text-sm text-purple-600">Jadwal</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">{{ \App\Models\EmployeeSchedule::active()->count() ?? 0 }}</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Jadwal Aktif</p>
    </x-ui.card>

    <!-- Unassigned Periods -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-amber-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <span class="text-sm text-amber-600">Kosong</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">{{ $periods->filter(function($period) { return $period->schedules->isEmpty(); })->count() ?? 0 }}</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Periode Kosong</p>
    </x-ui.card>
</div>

<!-- Day Navigation Tabs -->
<x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Filter Hari</h3>
        <p class="text-gray-600 dark:text-gray-400">Pilih hari untuk melihat jadwal periode</p>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-5 gap-3">
            @php 
            $days = [
                1 => ['name' => 'Senin', 'short' => 'Sen'],
                2 => ['name' => 'Selasa', 'short' => 'Sel'], 
                3 => ['name' => 'Rabu', 'short' => 'Rab'],
                4 => ['name' => 'Kamis', 'short' => 'Kam'],
                5 => ['name' => 'Jumat', 'short' => 'Jum']
            ];
            @endphp
            
            @foreach($days as $dayNum => $day)
            <button data-day="{{ $dayNum }}" class="day-tab group relative p-3 rounded-lg transition-all duration-200 {{ $dayNum == 1 ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600' }}">
                <div class="text-center">
                    <div class="text-sm font-semibold">{{ $day['name'] }}</div>
                    <div class="text-xs opacity-75 mt-1">{{ $periods->where('day_of_week', $dayNum)->count() }} periode</div>
                </div>
            </button>
            @endforeach
        </div>
    </div>
</x-ui.card>

<!-- Schedule Table -->
<x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Jadwal Periode</h3>
        <p class="text-gray-600 dark:text-gray-400">Kelola periode dan penugasan guru</p>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Periode</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Waktu</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Mata Pelajaran</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Guru</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Ruang</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($periods as $period)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" data-day-of-week="{{ $period->day_of_week }}" style="{{ $period->day_of_week != 1 ? 'display: none;' : '' }}">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white text-sm font-bold">
                                {{ $period->period_number ?? substr($period->name, -1) }}
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $period->name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][$period->day_of_week] }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $period->start_time->format('H:i') }} - {{ $period->end_time->format('H:i') }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $period->duration_minutes ?? 60 }} menit
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-purple-600 rounded-md flex items-center justify-center text-white text-xs font-bold">
                                {{ substr($period->subject ?? 'NS', 0, 2) }}
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $period->subject ?? 'Tidak Diatur' }}
                                </div>
                                @if($period->class_name)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $period->class_name }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $currentSchedule = $period->schedules()->active()->current()->first();
                        @endphp
                        
                        @if($currentSchedule && $currentSchedule->employee)
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                    {{ substr($currentSchedule->employee->full_name, 0, 2) }}
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $currentSchedule->employee->full_name }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ ucfirst($currentSchedule->employee->employee_type) }}
                                    </div>
                                </div>
                            </div>
                        @else
                            <button onclick="assignTeacher('{{ $period->id }}')" 
                                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Tugaskan Guru
                            </button>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $period->room ?? 'Tidak Diatur' }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($currentSchedule)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                Tertugaskan
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">
                                Kosong
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center justify-end space-x-2">
                            @if($currentSchedule)
                                <button onclick="editSchedule('{{ $currentSchedule->id }}')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-900/40 transition-colors duration-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                
                                <button onclick="deleteSchedule('{{ $currentSchedule->id }}')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/20 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/40 transition-colors duration-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            @else
                                <button onclick="assignTeacher('{{ $period->id }}')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/20 text-green-600 dark:text-green-400 hover:bg-green-200 dark:hover:bg-green-900/40 transition-colors duration-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-600 dark:text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-lg font-medium">Belum ada periode yang dikonfigurasi</p>
                        <p class="text-sm">Tambahkan periode pertama untuk mulai mengelola jadwal</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>

<!-- Teacher Load Summary -->
<x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mt-8">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Ringkasan Beban Guru</h3>
        <p class="text-gray-600 dark:text-gray-400">Jam mengajar mingguan dan penugasan</p>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @php
            $teacherLoads = [];
            
            // Calculate teacher loads from actual data
            foreach($employees as $employee) {
                if ($employee->employee_type === 'honorary') {
                    $activeSchedules = $employee->schedules()->active()->current()->count();
                    $totalHours = $employee->schedules()
                        ->active()
                        ->current()
                        ->with('period')
                        ->get()
                        ->sum(function($schedule) {
                            return $schedule->period->duration_minutes / 60;
                        });
                    
                    $maxHours = 40; // Maximum working hours per week
                    $workload = $totalHours > 0 ? min(($totalHours / $maxHours) * 100, 100) : 0;
                    
                    $teacherLoads[] = [
                        'name' => $employee->full_name,
                        'subject' => $employee->department ?? 'Umum',
                        'periods' => $activeSchedules,
                        'hours' => round($totalHours, 2),
                        'load' => round($workload, 0)
                    ];
                }
            }
            
            // If no teacher data, show placeholder
            if (empty($teacherLoads)) {
                $teacherLoads = [
                    ['name' => 'Belum ada guru', 'subject' => 'N/A', 'periods' => 0, 'hours' => 0, 'load' => 0]
                ];
            }
            @endphp
            
            @foreach($teacherLoads as $teacher)
            <div class="p-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-lg">
                            {{ substr($teacher['name'], 0, 2) }}
                        </div>
                        <div>
                            <h4 class="text-base font-semibold text-gray-900 dark:text-white">{{ $teacher['name'] }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $teacher['subject'] }}</p>
                        </div>
                    </div>
                    
                    <div class="text-right">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $teacher['load'] > 80 ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' : ($teacher['load'] > 60 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400' : 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400') }}">
                            {{ $teacher['load'] > 80 ? 'Overload' : ($teacher['load'] > 60 ? 'Sibuk' : 'Tersedia') }}
                        </span>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $teacher['periods'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Periode</div>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $teacher['hours'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Jam/Minggu</div>
                    </div>
                </div>
                
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Beban Kerja</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $teacher['load'] }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                        <div class="h-2 rounded-full {{ $teacher['load'] > 80 ? 'bg-red-500' : ($teacher['load'] > 60 ? 'bg-yellow-500' : 'bg-green-500') }}" 
                             style="width: {{ $teacher['load'] }}%"></div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</x-ui.card>

<!-- Import Schedule Modal -->
<div id="import-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black bg-opacity-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-lg shadow-xl">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Import Jadwal</h3>
                    <button onclick="closeImportModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <form id="import-form" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">File Jadwal</label>
                        <input type="file" id="schedule-file" name="schedule_file" accept=".xlsx,.xls,.csv" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Format: Excel (.xlsx, .xls) atau CSV</p>
                    </div>
                    
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <div>
                                <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200">Template Jadwal</h4>
                                <p class="text-sm text-blue-700 dark:text-blue-300">Download template untuk format yang benar</p>
                            </div>
                        </div>
                        <a href="{{ route('schedules.download-template') }}" class="mt-2 inline-flex items-center text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download Template
                        </a>
                    </div>
                </form>
            </div>
            
            <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                <button type="button" onclick="closeImportModal()" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500 rounded-lg">
                    Batal
                </button>
                <button type="submit" form="import-form" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                    Import Jadwal
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Assign Teacher Modal -->
<div id="assign-teacher-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black bg-opacity-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-lg shadow-xl">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tugaskan Guru</h3>
                    <button onclick="closeAssignTeacherModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <form id="assign-teacher-form" class="space-y-4">
                    @csrf
                    <input type="hidden" id="period_id" name="period_id">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Pilih Guru</label>
                        <select id="teacher_id" name="employee_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Pilih guru...</option>
                            @foreach($employees->where('employee_type', 'honorary') as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->full_name }} - {{ ucfirst($employee->employee_type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanggal Efektif</label>
                            <input type="date" id="effective_date" name="effective_date" required value="{{ date('Y-m-d') }}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanggal Akhir (Opsional)</label>
                            <input type="date" id="end_date" name="end_date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                <button type="button" onclick="closeAssignTeacherModal()" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500 rounded-lg">
                    Batal
                </button>
                <button type="submit" form="assign-teacher-form" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                    Tugaskan Guru
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Notification function
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
    } text-white`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Schedule functions
function editSchedule(scheduleId) {
    window.location.href = `/schedules/${scheduleId}/edit`;
}

function deleteSchedule(scheduleId) {
    if (confirm('Apakah Anda yakin ingin menghapus jadwal ini?')) {
        fetch(`/schedules/${scheduleId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Terjadi kesalahan saat menghapus jadwal', 'error');
        });
    }
}

function assignTeacher(periodId) {
    openAssignTeacherModal(periodId);
}

// Import Modal
function openImportModal() {
    document.getElementById('import-modal').classList.remove('hidden');
}

function closeImportModal() {
    document.getElementById('import-modal').classList.add('hidden');
    document.getElementById('import-form').reset();
}

// Assign Teacher Modal
function openAssignTeacherModal(periodId) {
    document.getElementById('period_id').value = periodId;
    document.getElementById('assign-teacher-modal').classList.remove('hidden');
}

function closeAssignTeacherModal() {
    document.getElementById('assign-teacher-modal').classList.add('hidden');
    document.getElementById('assign-teacher-form').reset();
}

// Day tab switching
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.day-tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active state from all tabs
            tabs.forEach(t => {
                t.classList.remove('bg-blue-600', 'text-white', 'shadow-sm');
                t.classList.add('text-gray-600', 'dark:text-gray-400', 'hover:bg-gray-100', 'dark:hover:bg-gray-600');
            });
            
            // Add active state to clicked tab
            this.classList.remove('text-gray-600', 'dark:text-gray-400', 'hover:bg-gray-100', 'dark:hover:bg-gray-600');
            this.classList.add('bg-blue-600', 'text-white', 'shadow-sm');
            
            // Get day number from data attribute
            const dayOfWeek = this.getAttribute('data-day');
            
            // Filter table rows by day
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const periodDayOfWeek = row.getAttribute('data-day-of-week');
                if (periodDayOfWeek && periodDayOfWeek == dayOfWeek) {
                    row.style.display = '';
                } else if (periodDayOfWeek) {
                    row.style.display = 'none';
                }
            });
            
            const dayName = this.textContent.trim().split('\n')[0].trim();
            showNotification(`Menampilkan jadwal ${dayName}`, 'info');
        });
    });
});

// Import form submission
document.getElementById('import-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const fileInput = document.getElementById('schedule-file');
    if (!fileInput.files.length) {
        showNotification('Pilih file terlebih dahulu', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('schedule_file', fileInput.files[0]);
    formData.append('_token', '{{ csrf_token() }}');
    
    fetch('/schedules/import', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message || 'Jadwal berhasil diimpor', 'success');
            closeImportModal();
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(data.message || 'Import gagal', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan saat import', 'error');
    });
});

// Assign Teacher form submission
document.getElementById('assign-teacher-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {
        period_id: formData.get('period_id'),
        employee_ids: [formData.get('employee_id')],
        effective_date: formData.get('effective_date'),
        end_date: formData.get('end_date'),
        _token: '{{ csrf_token() }}'
    };
    
    fetch('{{ route('schedules.assign-employees') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            closeAssignTeacherModal();
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan saat menugaskan guru', 'error');
    });
});

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.id === 'import-modal') {
        closeImportModal();
    }
    if (e.target.id === 'assign-teacher-modal') {
        closeAssignTeacherModal();
    }
});
</script>
@endpush