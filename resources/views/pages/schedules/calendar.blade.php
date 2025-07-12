@extends('layouts.authenticated')

@section('title', 'Schedule Calendar')

@push('styles')
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
@endpush

@section('page-content')
<x-layouts.page-base 
    title="Kalender Jadwal"
    subtitle="Kelola jadwal pelajaran dalam tampilan kalender"
    :show-background="true"
    :show-welcome="false">

    <!-- Page Header dengan Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Kalender Jadwal</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Tampilan kalender untuk mengelola jadwal pelajaran</p>
        </div>
        
        <div class="flex flex-wrap gap-2">
            <button onclick="location.href='{{ route('schedules.index') }}'" 
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200 shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                Tampilan Daftar
            </button>
            <button onclick="location.href='{{ route('schedules.create') }}'" 
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors duration-200 shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Jadwal
            </button>
        </div>
    </div>

    <!-- Calendar Controls -->
    <x-layouts.glass-card class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-4">
            <div class="flex items-center space-x-4">
                <button id="prev-btn" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Sebelumnya
                </button>
                <h3 id="calendar-title" class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ date('F Y') }}</h3>
                <button id="next-btn" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200">
                    Selanjutnya
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
            
            <div class="flex items-center space-x-2">
                <button id="today-btn" class="px-3 py-2 text-sm font-medium text-emerald-600 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900 dark:text-emerald-300 rounded-lg transition-colors duration-200">
                    Hari Ini
                </button>
                <select id="view-select" class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500">
                    <option value="timeGridWeek">Tampilan Minggu</option>
                    <option value="dayGridMonth">Tampilan Bulan</option>
                    <option value="timeGridDay">Tampilan Hari</option>
                </select>
            </div>
        </div>
    </x-layouts.glass-card>

    <!-- Calendar Grid -->
    <x-layouts.glass-card>
        <div id="calendar" class="p-4"></div>
    </x-layouts.glass-card>

    <!-- Legend -->
    <x-layouts.glass-card class="mt-6">
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Keterangan Jadwal</h3>
            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Kode warna untuk setiap mata pelajaran</p>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @php
            $subjects = [
                ['name' => 'Matematika', 'color' => 'bg-blue-500'],
                ['name' => 'Bahasa Indonesia', 'color' => 'bg-green-500'],
                ['name' => 'Bahasa Inggris', 'color' => 'bg-purple-500'],
                ['name' => 'IPA', 'color' => 'bg-orange-500'],
                ['name' => 'IPS', 'color' => 'bg-red-500'],
                ['name' => 'Pendidikan Jasmani', 'color' => 'bg-yellow-500'],
                ['name' => 'Seni Budaya', 'color' => 'bg-indigo-500'],
                ['name' => 'Pendidikan Agama', 'color' => 'bg-pink-500']
            ];
            @endphp
            
            @foreach($subjects as $subject)
            <div class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <div class="w-4 h-4 {{ $subject['color'] }} rounded-full shadow-sm"></div>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $subject['name'] }}</span>
            </div>
            @endforeach
        </div>
    </x-layouts.glass-card>

</x-layouts.page-base>
@endsection

@push('scripts')
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: '{{ route('schedules.calendar.data') }}',
        editable: true,
        selectable: true,
        selectMirror: true,
        eventClick: function(arg) {
            // Handle event click
            showEventDetails(arg.event);
        },
        select: function(arg) {
            // Handle date selection
            showCreateScheduleModal(arg.start, arg.end);
        },
        height: 'auto',
        businessHours: {
            daysOfWeek: [1, 2, 3, 4, 5], // Monday - Friday
            startTime: '08:00',
            endTime: '17:00'
        },
        eventDisplay: 'block',
        eventTextColor: '#ffffff',
        eventBackgroundColor: '#3b82f6',
        eventBorderColor: '#2563eb'
    });

    calendar.render();

    // Show event details modal
    function showEventDetails(event) {
        alert(`Event: ${event.title}\nTime: ${event.start.toLocaleString()} - ${event.end.toLocaleString()}\nTeacher: ${event.extendedProps.employee_type}`);
    }

    // Show create schedule modal
    function showCreateScheduleModal(start, end) {
        const confirmed = confirm(`Create new schedule from ${start.toLocaleString()} to ${end.toLocaleString()}?`);
        if (confirmed) {
            window.location.href = '{{ route('schedules.create') }}';
        }
    }
});
</script>
@endpush