@extends('layouts.authenticated-unified')

@section('title', 'Kalender Jadwal')

@section('page-content')
<div x-data="scheduleCalendar()">
    <!-- Modern Page Header with Enhanced Actions -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Kalender Jadwal</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Lihat dan kelola jadwal dalam tampilan kalender interaktif</p>
            </div>
            <div class="flex items-center space-x-3">
                <!-- Back Button -->
                <button onclick="location.href='{{ route('schedules.index') }}'" class="btn-analytics">
                    <x-icons.arrow-left class="w-5 h-5 mr-2 inline" />
                    Kembali
                </button>
                
                <!-- Analytics Button -->
                <button @click="showAnalytics()" class="btn-test">
                    <x-icons.chart-bar class="w-5 h-5 mr-2 inline" />
                    Analitik
                </button>
                
                <!-- Schedule Builder Button -->
                <button onclick="location.href='{{ route('schedules.builder') }}'" class="btn-analytics-gradient">
                    <x-icons.edit class="w-5 h-5 mr-2 inline" />
                    Schedule Builder
                </button>
            </div>
        </div>
    </div>

    <!-- Enhanced Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        <!-- Total Schedules Card -->
        <x-ui.card>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg shadow-md">
                        <x-icons.calendar class="w-6 h-6 text-white" />
                    </div>
                    <span class="text-sm font-medium px-3 py-1 bg-blue-100 text-blue-800 rounded-full">Minggu Ini</span>
                </div>
                <h3 class="metric-heading" x-text="weekSchedules.length">0</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Total Jadwal</p>
            </div>
        </x-ui.card>

        <!-- Active Teachers Card -->
        <x-ui.card>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-r from-green-600 to-emerald-600 rounded-lg shadow-md">
                        <x-icons.users class="w-6 h-6 text-white" />
                    </div>
                    <span class="text-sm font-medium px-3 py-1 bg-green-100 text-green-800 rounded-full">Aktif</span>
                </div>
                <h3 class="metric-heading" x-text="activeTeachers">0</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Guru Aktif</p>
            </div>
        </x-ui.card>

        <!-- Subjects Count Card -->
        <x-ui.card>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-r from-purple-600 to-purple-700 rounded-lg shadow-md">
                        <x-icons.book class="w-6 h-6 text-white" />
                    </div>
                    <span class="text-sm font-medium px-3 py-1 bg-purple-100 text-purple-800 rounded-full">Total</span>
                </div>
                <h3 class="metric-heading" x-text="uniqueSubjects">0</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Mata Pelajaran</p>
            </div>
        </x-ui.card>

        <!-- Classes Count Card -->
        <x-ui.card>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-r from-orange-600 to-orange-700 rounded-lg shadow-md">
                        <x-icons.building class="w-6 h-6 text-white" />
                    </div>
                    <span class="text-sm font-medium px-3 py-1 bg-orange-100 text-orange-800 rounded-full">Total</span>
                </div>
                <h3 class="metric-heading" x-text="uniqueClasses">0</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Kelas</p>
            </div>
        </x-ui.card>
    </div>

    <!-- Enhanced Calendar Controls with Modern Design -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-center">
                <!-- Navigation Controls -->
                <div class="flex items-center space-x-3">
                    <button @click="previousWeek()" class="btn-nav" title="Minggu Sebelumnya">
                        <x-icons.chevron-left class="w-5 h-5" />
                    </button>
                    <button @click="currentWeek()" class="btn-today">
                        <x-icons.calendar class="w-4 h-4 mr-2 inline" />
                        Hari Ini
                    </button>
                    <button @click="nextWeek()" class="btn-nav" title="Minggu Selanjutnya">
                        <x-icons.chevron-right class="w-5 h-5" />
                    </button>
                </div>

                <!-- Current Week Display -->
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white" x-text="currentWeekDisplay"></h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="currentMonthYear"></p>
                </div>

                <!-- Filters and Actions -->
                <div class="flex items-center justify-end space-x-3">
                    <!-- View Mode Toggle -->
                    <div class="flex items-center bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                        <button @click="viewMode = 'week'" :class="viewMode === 'week' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''" class="btn-view-mode">
                            Minggu
                        </button>
                        <button @click="viewMode = 'day'" :class="viewMode === 'day' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''" class="btn-view-mode">
                            Hari
                        </button>
                    </div>
                    
                    <!-- Teacher Filter -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="btn-filter">
                            <x-icons.filter class="w-4 h-4 mr-2" />
                            <span class="text-sm font-medium">Filter</span>
                            <x-icons.chevron-down class="w-4 h-4 ml-2" />
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div x-show="open" @click.away="open = false" x-transition class="filter-dropdown">
                            <div class="p-4">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Filter Guru</h4>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input type="checkbox" x-model="filterAllTeachers" class="form-checkbox">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Semua Guru</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" value="1A" x-model="selectedTeachers" class="form-checkbox">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Guru 1A</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" value="2B" x-model="selectedTeachers" class="form-checkbox">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Guru 2B</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" value="3C" x-model="selectedTeachers" class="form-checkbox">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Guru 3C</span>
                                    </label>
                                </div>
                                <button @click="applyFilters(); open = false" class="mt-4 w-full btn-primary">
                                    Terapkan Filter
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Export Button -->
                    <button @click="exportCalendar()" class="btn-export">
                        <x-icons.download class="w-4 h-4 mr-2 inline" />
                        Export
                    </button>
                </div>
            </div>
        </div>
    </x-ui.card>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-4 gap-8">
        <!-- Calendar Grid (Main Panel) -->
        <div class="xl:col-span-3">
            <x-ui.card>
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Jadwal Mingguan</h3>
                            <p class="text-gray-600 dark:text-gray-400">Klik pada slot jadwal untuk melihat detail atau mengedit</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <!-- Legend -->
                            <div class="flex items-center space-x-2">
                                <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center">
                                    <span class="legend-color legend-blue"></span>
                                    Matematika
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center">
                                    <span class="legend-color legend-green"></span>
                                    Sains
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center">
                                    <span class="legend-color legend-purple"></span>
                                    Bahasa
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center">
                                    <span class="legend-color legend-orange"></span>
                                    Lainnya
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        
        <div class="overflow-x-auto">
            <div class="min-w-full">
                <!-- Calendar Table -->
                <table class="w-full">
                    <!-- Calendar Header -->
                    <thead>
                        <tr class="calendar-header-row">
                            <th class="calendar-time-header">
                                <div class="flex items-center justify-center">
                                    <x-icons.clock class="w-4 h-4 mr-2 text-gray-600 dark:text-gray-400" />
                                    <span class="calendar-header-text">Waktu</span>
                                </div>
                            </th>
                            <template x-for="day in weekDays" :key="day.name">
                                <th class="calendar-day-header">
                                    <div class="calendar-day-name" x-text="day.name"></div>
                                    <div class="calendar-day-date" x-text="day.date"></div>
                                </th>
                            </template>
                        </tr>
                    </thead>

                    <!-- Calendar Body -->
                    <tbody class="bg-white dark:bg-gray-800">
                        <template x-for="hour in timeSlots" :key="hour.id">
                            <tr class="calendar-row">
                                <!-- Time Slot -->
                                <td class="calendar-time-cell">
                                    <div class="text-center">
                                        <div class="time-slot-name" x-text="hour.name"></div>
                                        <div class="time-slot-time" x-text="hour.time"></div>
                                    </div>
                                </td>
                                
                                <!-- Schedule Cells for Each Day -->
                                <template x-for="day in weekDays" :key="day.name + '-' + hour.id">
                                    <td class="calendar-schedule-cell">
                                        <div class="space-y-2">
                                            <template x-for="schedule in getSchedulesForSlot(day.name, hour.id)" :key="schedule.id">
                                                <div class="schedule-block"
                                                     :class="getScheduleClass(schedule)"
                                                     @click="showScheduleDetails(schedule)">
                                                    <!-- Gradient overlay -->
                                                    <div class="schedule-block-bg" :class="getGradientClass(schedule.subject)"></div>
                                                    
                                                    <div class="relative z-10">
                                                        <div class="schedule-subject" x-text="schedule.subject"></div>
                                                        <div class="text-xs opacity-90 mb-1 flex items-center">
                                                            <x-icons.user class="w-3 h-3 mr-1" />
                                                            <span x-text="schedule.teacher"></span>
                                                        </div>
                                                        <div class="text-xs opacity-75 flex items-center">
                                                            <x-icons.building class="w-3 h-3 mr-1" />
                                                            <span x-text="schedule.class"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                        
                                        <!-- Empty slot indicator -->
                                        <div x-show="getSchedulesForSlot(day.name, hour.id).length === 0" 
                                             class="empty-slot">
                                            <div class="text-center">
                                                <x-icons.plus class="w-6 h-6 mx-auto mb-1 opacity-50" />
                                                <span class="text-xs opacity-75">Kosong</span>
                                            </div>
                                        </div>
                                    </td>
                                </template>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
        
                <!-- Calendar Footer with Quick Stats -->
                <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 rounded-b-lg">
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center space-x-6">
                            <div class="text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Total Jadwal:</span> 
                                <span x-text="filteredSchedules.length" class="stat-number text-blue-600 dark:text-blue-400"></span>
                            </div>
                            <div class="text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Jam Terisi:</span> 
                                <span x-text="filledSlots" class="stat-number text-green-600 dark:text-green-400"></span>
                            </div>
                            <div class="text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Slot Kosong:</span> 
                                <span x-text="emptySlots" class="stat-number text-orange-600 dark:text-orange-400"></span>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            <x-icons.info-circle class="w-4 h-4 inline mr-1" />
                            Klik pada jadwal untuk melihat detail
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <!-- Quick Actions Sidebar -->
        <div class="xl:col-span-1">
            <!-- Quick Actions Card -->
            <x-ui.card class="mb-6">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <x-icons.lightning-bolt class="w-5 h-5 mr-2 text-blue-600" />
                        Quick Actions
                    </h3>
                </div>
                <div class="p-6 space-y-3">
                    <button @click="showAddScheduleModal()" class="action-btn action-btn-blue">
                        <div class="flex items-center">
                            <x-icons.plus class="w-5 h-5 mr-3 text-blue-600" />
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">Tambah Jadwal</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Buat jadwal baru</div>
                            </div>
                        </div>
                    </button>
                    
                    <button @click="duplicateWeek()" class="action-btn action-btn-green">
                        <div class="flex items-center">
                            <x-icons.duplicate class="w-5 h-5 mr-3 text-green-600" />
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">Duplikasi Minggu</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Salin jadwal minggu ini</div>
                            </div>
                        </div>
                    </button>
                    
                    <button @click="clearWeek()" class="action-btn action-btn-red">
                        <div class="flex items-center">
                            <x-icons.trash class="w-5 h-5 mr-3 text-red-600" />
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">Hapus Semua</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Kosongkan jadwal minggu ini</div>
                            </div>
                        </div>
                    </button>
                    
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                        <button @click="printCalendar()" class="action-btn action-btn-gray">
                            <div class="flex items-center">
                                <x-icons.printer class="w-5 h-5 mr-3 text-gray-600 dark:text-gray-400" />
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">Cetak Kalender</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Print jadwal minggu ini</div>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </x-ui.card>

            <!-- Teacher Workload Summary -->
            <x-ui.card>
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <x-icons.chart-bar class="w-5 h-5 mr-2 text-purple-600" />
                        Beban Mengajar
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    <template x-for="teacher in teacherWorkload" :key="teacher.id">
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center">
                                    <div class="teacher-avatar" :class="getTeacherColorClass(teacher.name)">
                                        <span x-text="teacher.name.substring(5, 7)"></span>
                                    </div>
                                    <div class="ml-3">
                                        <div class="teacher-name" x-text="teacher.name"></div>
                                        <div class="teacher-subject" x-text="teacher.subject"></div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="teacher-hours" x-text="teacher.hours + ' jam'"></div>
                                    <div class="teacher-percentage" x-text="teacher.percentage + '%'"></div>
                                </div>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all duration-300" 
                                     :class="getWorkloadColorClass(teacher.percentage)"
                                     :style="`width: ${teacher.percentage}%`"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </x-ui.card>
        </div>
    </div>

    <!-- Schedule Details Modal -->
    <div x-show="showModal" x-transition 
         class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50" 
         @click.self="closeModal()">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-lg shadow-xl">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Detail Jadwal</h3>
                        <button @click="closeModal()" class="modal-close-btn">
                            <x-icons.x class="w-5 h-5" />
                        </button>
                    </div>
                </div>
                
                <div class="p-6" x-show="selectedSchedule">
                    <div class="space-y-4">
                        <div>
                            <label class="form-label">Mata Pelajaran</label>
                            <p class="text-gray-900 dark:text-white font-medium" x-text="selectedSchedule?.subject"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Guru</label>
                            <p class="text-gray-900 dark:text-white" x-text="selectedSchedule?.teacher"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kelas</label>
                            <p class="text-gray-900 dark:text-white" x-text="selectedSchedule?.class"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Waktu</label>
                            <p class="text-gray-900 dark:text-white" x-text="selectedSchedule?.time"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ruang</label>
                            <p class="form-value" x-text="selectedSchedule?.room || 'Tidak diatur'"></p>
                        </div>
                    </div>
                </div>
                
                <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                    <button @click="closeModal()" class="btn-modal-cancel">
                        Tutup
                    </button>
                    <button @click="editSchedule(selectedSchedule)" class="btn-modal-primary">
                        Edit Jadwal
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function scheduleCalendar() {
    return {
        currentDate: new Date(),
        selectedTeachers: [],
        filterAllTeachers: true,
        showModal: false,
        selectedSchedule: null,
        viewMode: 'week',
        
        // Sample data - in real app this would come from backend
        schedules: [
            {
                id: 1,
                subject: 'Matematika',
                teacher: 'Guru 1A',
                class: 'X IPA 1',
                day: 'SENIN',
                timeSlot: 1,
                time: '07:00 - 07:45',
                room: 'R-101'
            },
            {
                id: 2,
                subject: 'Fisika',
                teacher: 'Guru 2B',
                class: 'XI IPA 1',
                day: 'SENIN',
                timeSlot: 2,
                time: '07:45 - 08:30',
                room: 'Lab Fisika'
            },
            {
                id: 3,
                subject: 'Kimia',
                teacher: 'Guru 3C',
                class: 'XII IPA 1',
                day: 'SELASA',
                timeSlot: 1,
                time: '07:00 - 07:45',
                room: 'Lab Kimia'
            },
            {
                id: 4,
                subject: 'Biologi',
                teacher: 'Guru 1A',
                class: 'X IPA 2',
                day: 'RABU',
                timeSlot: 3,
                time: '08:30 - 09:15',
                room: 'Lab Biologi'
            },
            {
                id: 5,
                subject: 'Bahasa Indonesia',
                teacher: 'Guru 2B',
                class: 'XI IPS 1',
                day: 'KAMIS',
                timeSlot: 1,
                time: '07:00 - 07:45',
                room: 'R-201'
            },
            {
                id: 6,
                subject: 'Sejarah',
                teacher: 'Guru 3C',
                class: 'XII IPS 1',
                day: 'JUMAT',
                timeSlot: 2,
                time: '07:45 - 08:30',
                room: 'R-301'
            }
        ],

        timeSlots: [
            { id: 1, name: 'Jam 1', time: '07:00 - 07:45' },
            { id: 2, name: 'Jam 2', time: '07:45 - 08:30' },
            { id: 3, name: 'Jam 3', time: '08:30 - 09:15' },
            { id: 4, name: 'Jam 4', time: '09:35 - 10:20' },
            { id: 5, name: 'Jam 5', time: '10:20 - 11:05' },
            { id: 6, name: 'Jam 6', time: '11:05 - 11:50' },
            { id: 7, name: 'Jam 7', time: '12:30 - 13:15' },
            { id: 8, name: 'Jam 8', time: '13:15 - 14:00' },
            { id: 9, name: 'Jam 9', time: '14:00 - 14:45' },
            { id: 10, name: 'Jam 10', time: '14:45 - 15:30' }
        ],

        init() {
            this.updateWeekDisplay();
        },

        get weekDays() {
            const days = [];
            const startOfWeek = new Date(this.currentDate);
            startOfWeek.setDate(this.currentDate.getDate() - this.currentDate.getDay() + 1); // Start on Monday
            
            for (let i = 0; i < 5; i++) { // Monday to Friday
                const day = new Date(startOfWeek);
                day.setDate(startOfWeek.getDate() + i);
                
                const dayNames = ['MINGGU', 'SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU'];
                days.push({
                    name: dayNames[day.getDay()],
                    date: day.getDate() + '/' + (day.getMonth() + 1),
                    fullDate: day
                });
            }
            return days;
        },

        get currentWeekDisplay() {
            const startOfWeek = new Date(this.currentDate);
            startOfWeek.setDate(this.currentDate.getDate() - this.currentDate.getDay() + 1);
            const endOfWeek = new Date(startOfWeek);
            endOfWeek.setDate(startOfWeek.getDate() + 4);
            
            return `${startOfWeek.getDate()} - ${endOfWeek.getDate()}`;
        },

        get currentMonthYear() {
            const months = [
                'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
            ];
            return `${months[this.currentDate.getMonth()]} ${this.currentDate.getFullYear()}`;
        },

        previousWeek() {
            this.currentDate.setDate(this.currentDate.getDate() - 7);
            this.updateWeekDisplay();
        },

        nextWeek() {
            this.currentDate.setDate(this.currentDate.getDate() + 7);
            this.updateWeekDisplay();
        },

        currentWeek() {
            this.currentDate = new Date();
            this.updateWeekDisplay();
        },

        updateWeekDisplay() {
            // Force reactivity update
            this.$nextTick(() => {
                // Update display
            });
        },

        get filteredSchedules() {
            if (this.filterAllTeachers || this.selectedTeachers.length === 0) {
                return this.schedules;
            }
            return this.schedules.filter(s => this.selectedTeachers.includes(s.teacher));
        },

        get weekSchedules() {
            // Get schedules for current week
            return this.filteredSchedules;
        },

        get activeTeachers() {
            return [...new Set(this.weekSchedules.map(s => s.teacher))].length;
        },

        get uniqueSubjects() {
            return [...new Set(this.weekSchedules.map(s => s.subject))].length;
        },

        get uniqueClasses() {
            return [...new Set(this.weekSchedules.map(s => s.class))].length;
        },

        get filledSlots() {
            return this.weekSchedules.length;
        },

        get emptySlots() {
            // 5 days * 10 time slots = 50 total slots
            return 50 - this.filledSlots;
        },

        get teacherWorkload() {
            const workload = {};
            this.weekSchedules.forEach(schedule => {
                if (!workload[schedule.teacher]) {
                    workload[schedule.teacher] = {
                        id: schedule.teacher,
                        name: schedule.teacher,
                        subject: schedule.subject,
                        hours: 0
                    };
                }
                workload[schedule.teacher].hours++;
            });
            
            return Object.values(workload).map(teacher => ({
                ...teacher,
                percentage: Math.round((teacher.hours / 30) * 100) // Assuming 30 hours is 100%
            }));
        },

        getSchedulesForSlot(day, timeSlot) {
            return this.filteredSchedules.filter(schedule => {
                const matchesDay = schedule.day === day;
                const matchesTime = schedule.timeSlot === timeSlot;
                return matchesDay && matchesTime;
            });
        },

        getScheduleClass(schedule) {
            const subjectColors = {
                'Matematika': 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-900/30 dark:text-blue-200 dark:border-blue-700',
                'Fisika': 'bg-green-100 text-green-800 border-green-300 dark:bg-green-900/30 dark:text-green-200 dark:border-green-700',
                'Kimia': 'bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-900/30 dark:text-purple-200 dark:border-purple-700',
                'Biologi': 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-900/30 dark:text-emerald-200 dark:border-emerald-700',
                'Bahasa Indonesia': 'bg-orange-100 text-orange-800 border-orange-300 dark:bg-orange-900/30 dark:text-orange-200 dark:border-orange-700',
                'Sejarah': 'bg-pink-100 text-pink-800 border-pink-300 dark:bg-pink-900/30 dark:text-pink-200 dark:border-pink-700'
            };
            
            return subjectColors[schedule.subject] || 'bg-gray-100 text-gray-800 border-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600';
        },

        getGradientClass(subject) {
            const gradients = {
                'Matematika': 'from-blue-500 to-blue-600',
                'Fisika': 'from-green-500 to-emerald-600',
                'Kimia': 'from-purple-500 to-purple-600',
                'Biologi': 'from-emerald-500 to-emerald-600',
                'Bahasa Indonesia': 'from-orange-500 to-orange-600',
                'Sejarah': 'from-pink-500 to-pink-600'
            };
            return gradients[subject] || 'from-gray-500 to-gray-600';
        },

        getTeacherColorClass(name) {
            const colors = [
                'from-blue-500 to-blue-600',
                'from-green-500 to-emerald-600',
                'from-purple-500 to-purple-600',
                'from-orange-500 to-orange-600',
                'from-pink-500 to-pink-600',
                'from-indigo-500 to-indigo-600'
            ];
            
            const hash = name.split('').reduce((a, b) => {
                a = ((a << 5) - a) + b.charCodeAt(0);
                return a & a;
            }, 0);
            
            return colors[Math.abs(hash) % colors.length];
        },
        
        getWorkloadColorClass(percentage) {
            if (percentage > 80) {
                return 'bg-gradient-to-r from-red-500 to-red-600';
            } else if (percentage > 60) {
                return 'bg-gradient-to-r from-yellow-500 to-orange-600';
            } else {
                return 'bg-gradient-to-r from-green-500 to-emerald-600';
            }
        },

        showScheduleDetails(schedule) {
            this.selectedSchedule = schedule;
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.selectedSchedule = null;
        },

        editSchedule(schedule) {
            // In real app, this would navigate to edit page
            alert(`Edit jadwal: ${schedule.subject} - ${schedule.class}`);
            this.closeModal();
        },

        applyFilters() {
            // Filters are applied through computed properties
        },

        showAnalytics() {
            alert('Analytics view akan segera hadir!');
        },

        showAddScheduleModal() {
            alert('Tambah jadwal modal akan segera hadir!');
        },

        duplicateWeek() {
            if (confirm('Duplikasi jadwal minggu ini ke minggu depan?')) {
                alert('Jadwal berhasil diduplikasi!');
            }
        },

        clearWeek() {
            if (confirm('Hapus semua jadwal minggu ini?')) {
                this.schedules = this.schedules.filter(s => {
                    // Filter out schedules for current week
                    return false; // Placeholder logic
                });
                alert('Jadwal minggu ini telah dihapus!');
            }
        },

        printCalendar() {
            window.print();
        },

        exportCalendar() {
            const format = prompt('Pilih format export:\n1. Excel\n2. PDF\n3. iCalendar', '1');
            if (format) {
                alert(`Export ke format ${format === '1' ? 'Excel' : format === '2' ? 'PDF' : 'iCalendar'} akan segera dimulai!`);
            }
        }
    }
}
</script>
@endsection