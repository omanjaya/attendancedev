@extends('layouts.authenticated-unified')

@section('title', 'Kalender Jadwal')

@section('page-content')
<div x-data="scheduleCalendar()">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Kalender Jadwal</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Lihat jadwal dalam tampilan kalender mingguan</p>
            </div>
            <div class="flex items-center space-x-3">
                <button onclick="location.href='{{ route('schedules.index') }}'" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali ke Jadwal
                </button>
                <button onclick="location.href='{{ route('schedules.builder') }}'" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Schedule Builder
                </button>
            </div>
        </div>
    </div>

    <!-- Calendar Controls -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-center">
                <!-- Navigation Controls -->
                <div class="flex items-center space-x-2">
                    <button @click="previousWeek()" class="flex items-center justify-center w-10 h-10 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200" title="Minggu Sebelumnya">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button @click="currentWeek()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200">
                        Hari Ini
                    </button>
                    <button @click="nextWeek()" class="flex items-center justify-center w-10 h-10 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200" title="Minggu Selanjutnya">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>

                <!-- Current Week Display -->
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white" x-text="currentWeekDisplay"></h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400" x-text="currentMonthYear"></p>
                </div>

                <!-- Filters and Actions -->
                <div class="flex items-center justify-end space-x-3">
                    <div class="flex items-center space-x-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Filter Guru:</label>
                        <select x-model="selectedTeacher" @change="filterSchedules()" class="px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            <option value="">Semua Guru</option>
                            <option value="1A">Guru 1A</option>
                            <option value="2B">Guru 2B</option>
                            <option value="3C">Guru 3C</option>
                        </select>
                    </div>
                    <button @click="exportCalendar()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                        <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export
                    </button>
                </div>
            </div>
        </div>
    </x-ui.card>

    <!-- Calendar Grid -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Jadwal Mingguan</h3>
                    <p class="text-gray-600 dark:text-gray-400">Tampilan kalender jadwal mengajar per minggu</p>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        <span class="inline-block w-3 h-3 bg-blue-100 border border-blue-200 rounded mr-1"></span>
                        Matematika
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        <span class="inline-block w-3 h-3 bg-green-100 border border-green-200 rounded mr-1"></span>
                        Fisika
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        <span class="inline-block w-3 h-3 bg-purple-100 border border-purple-200 rounded mr-1"></span>
                        Lainnya
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
                        <tr class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 border-b-2 border-gray-200 dark:border-gray-600">
                            <th class="p-4 border-r border-gray-300 dark:border-gray-500 text-left sticky left-0 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 z-10">
                                <div class="flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase">Waktu</span>
                                </div>
                            </th>
                            <template x-for="day in weekDays" :key="day.name">
                                <th class="p-4 border-r border-gray-300 dark:border-gray-500 text-center bg-white dark:bg-gray-700 min-w-[180px]">
                                    <div class="text-sm font-bold text-gray-900 dark:text-white mb-1" x-text="day.name"></div>
                                    <div class="text-xs text-blue-600 dark:text-blue-400 font-medium" x-text="day.date"></div>
                                </th>
                            </template>
                        </tr>
                    </thead>

                    <!-- Calendar Body -->
                    <tbody class="bg-white dark:bg-gray-800">
                        <template x-for="hour in timeSlots" :key="hour.id">
                            <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                                <!-- Time Slot -->
                                <td class="p-4 border-r border-gray-200 dark:border-gray-600 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 sticky left-0 z-10">
                                    <div class="text-center">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white" x-text="hour.name"></div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1 font-medium" x-text="hour.time"></div>
                                    </div>
                                </td>
                                
                                <!-- Schedule Cells for Each Day -->
                                <template x-for="day in weekDays" :key="day.name + '-' + hour.id">
                                    <td class="p-3 border-r border-gray-200 dark:border-gray-600 min-h-[100px] relative bg-white dark:bg-gray-800 align-top">
                                        <div class="space-y-2">
                                            <template x-for="schedule in getSchedulesForSlot(day.name, hour.id)" :key="schedule.id">
                                                <div class="p-3 rounded-lg text-xs cursor-pointer transition-all duration-200 hover:shadow-lg hover:scale-105 transform"
                                                     :class="getScheduleClass(schedule)"
                                                     @click="showScheduleDetails(schedule)">
                                                    <div class="font-semibold mb-1" x-text="schedule.subject"></div>
                                                    <div class="text-xs opacity-90 mb-1" x-text="schedule.teacher"></div>
                                                    <div class="text-xs opacity-75 flex items-center">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                        </svg>
                                                        <span x-text="schedule.class"></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                        
                                        <!-- Empty slot indicator -->
                                        <div x-show="getSchedulesForSlot(day.name, hour.id).length === 0" 
                                             class="h-full min-h-[80px] flex items-center justify-center text-gray-300 dark:text-gray-600 transition-colors duration-200 hover:text-gray-400 dark:hover:text-gray-500">
                                            <div class="text-center">
                                                <svg class="w-6 h-6 mx-auto mb-1 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
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
                        <span x-text="schedules.length" class="font-semibold text-blue-600 dark:text-blue-400"></span>
                    </div>
                    <div class="text-gray-600 dark:text-gray-400">
                        <span class="font-medium">Guru Aktif:</span> 
                        <span class="font-semibold text-green-600 dark:text-green-400">3</span>
                    </div>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Klik pada jadwal untuk melihat detail lengkap
                </div>
            </div>
        </div>
    </x-ui.card>

    <!-- Schedule Details Modal -->
    <div x-show="showModal" x-transition 
         class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50" 
         @click.self="closeModal()">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-lg shadow-xl">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Detail Jadwal</h3>
                        <button @click="closeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="p-6" x-show="selectedSchedule">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mata Pelajaran</label>
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
                            <p class="text-gray-900 dark:text-white" x-text="selectedSchedule?.room || 'Tidak diatur'"></p>
                        </div>
                    </div>
                </div>
                
                <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                    <button @click="closeModal()" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500 rounded-lg">
                        Tutup
                    </button>
                    <button @click="editSchedule(selectedSchedule)" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
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
        selectedTeacher: '',
        showModal: false,
        selectedSchedule: null,
        
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

        getSchedulesForSlot(day, timeSlot) {
            return this.schedules.filter(schedule => {
                const matchesDay = schedule.day === day;
                const matchesTime = schedule.timeSlot === timeSlot;
                const matchesTeacher = !this.selectedTeacher || schedule.teacher === this.selectedTeacher;
                
                return matchesDay && matchesTime && matchesTeacher;
            });
        },

        getScheduleClass(schedule) {
            const colors = [
                'bg-blue-100 text-blue-800 border-blue-200',
                'bg-green-100 text-green-800 border-green-200',
                'bg-purple-100 text-purple-800 border-purple-200',
                'bg-orange-100 text-orange-800 border-orange-200',
                'bg-pink-100 text-pink-800 border-pink-200',
                'bg-indigo-100 text-indigo-800 border-indigo-200'
            ];
            
            // Simple hash function to assign consistent colors
            const hash = schedule.teacher.split('').reduce((a, b) => {
                a = ((a << 5) - a) + b.charCodeAt(0);
                return a & a;
            }, 0);
            
            return colors[Math.abs(hash) % colors.length] + ' border';
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

        filterSchedules() {
            // Reactivity will handle the filtering automatically
        }
    }
}
</script>
@endsection