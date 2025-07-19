@extends('layouts.authenticated-unified')

@section('title', 'Penyusunan Jadwal Mengajar Guru')

@section('page-content')
<div x-data="scheduleBuilder()">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Penyusunan Jadwal Mengajar Guru</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Buat dan kelola jadwal mengajar untuk guru</p>
            </div>
            <div class="flex items-center space-x-3">
                <button @click="resetData()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Reset Data
                </button>
                <button @click="undo()" :disabled="!canUndo" class="bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                    Undo
                </button>
            </div>
        </div>
    </div>

    <!-- Control Panel -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Panel Kontrol</h3>
            <p class="text-gray-600 dark:text-gray-400">Kelola pengaturan jadwal dan data guru</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Teacher Code Input -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Masukkan Kode Guru:</label>
                    <input x-model="teacherCode" type="text" placeholder="Contoh: 1A, 2B, 3C" 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button @click="setTeacherCode()" class="mt-2 w-full bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                        Set Kode
                    </button>
                </div>

                <!-- Current Teacher Code -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kode Guru Saat Ini:</label>
                    <div class="px-3 py-2 bg-gray-100 dark:bg-gray-600 rounded-lg border border-gray-300 dark:border-gray-600">
                        <span x-text="currentTeacher || '**BELUM DISET**'" class="text-gray-900 dark:text-white font-medium"></span>
                    </div>
                    <button @click="logoutTeacher()" class="mt-2 w-full bg-amber-600 hover:bg-amber-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                        Logout
                    </button>
                </div>

                <!-- Save/Load JSON -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kelola Data:</label>
                    <button @click="saveToJSON()" class="w-full mb-2 bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                        Simpan Jadwal (JSON)
                    </button>
                    <input type="file" @change="loadFromJSON($event)" accept=".json" class="hidden" x-ref="jsonFile">
                    <button @click="$refs.jsonFile.click()" class="w-full bg-purple-600 hover:bg-purple-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                        Muat Jadwal (JSON)
                    </button>
                </div>

                <!-- Export -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Export:</label>
                    <button @click="exportToExcel()" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export ke Excel
                    </button>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Undo: Ctrl+Z</p>
                </div>
            </div>
        </div>
    </x-ui.card>

    <!-- Schedule Table -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Jadwal Mengajar</h3>
            <p class="text-gray-600 dark:text-gray-400">Klik pada sel untuk menugaskan guru ke jam dan kelas tertentu</p>
        </div>
        
        <div class="overflow-x-auto shadow-lg rounded-lg">
            <table class="w-full border-collapse bg-white dark:bg-gray-800">
                <thead class="sticky top-0 z-20">
                    <!-- Header row: HARI, JAM, and Class columns -->
                    <tr>
                        <th class="w-24 px-3 py-4 text-center text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider border-2 border-gray-400 dark:border-gray-500 bg-gradient-to-br from-gray-200 to-gray-100 dark:from-gray-700 dark:to-gray-600 sticky left-0 z-30">
                            <div class="flex items-center justify-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span class="font-extrabold text-xs">HARI</span>
                            </div>
                        </th>
                        <th class="w-16 px-3 py-4 text-center text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider border-2 border-gray-400 dark:border-gray-500 bg-gradient-to-br from-gray-200 to-gray-100 dark:from-gray-700 dark:to-gray-600 sticky left-24 z-30">
                            <div class="flex items-center justify-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="font-extrabold text-xs">JAM</span>
                            </div>
                        </th>
                        <!-- Grade level headers -->
                        <template x-for="grade in [{name: 'Kelas X', color: 'blue'}, {name: 'Kelas XI', color: 'green'}, {name: 'Kelas XII', color: 'purple'}]" :key="grade.name">
                            <th colspan="12" class="px-3 py-3 text-center text-sm font-bold uppercase tracking-wider border-2 border-gray-400 dark:border-gray-500"
                                :class="{
                                    'text-blue-800 dark:text-blue-200 bg-gradient-to-br from-blue-100 to-blue-50 dark:from-blue-800/40 dark:to-blue-700/30': grade.color === 'blue',
                                    'text-green-800 dark:text-green-200 bg-gradient-to-br from-green-100 to-green-50 dark:from-green-800/40 dark:to-green-700/30': grade.color === 'green',
                                    'text-purple-800 dark:text-purple-200 bg-gradient-to-br from-purple-100 to-purple-50 dark:from-purple-800/40 dark:to-purple-700/30': grade.color === 'purple'
                                }"
                                x-text="grade.name">
                            </th>
                        </template>
                    </tr>
                    
                    <!-- Class numbers row -->
                    <tr>
                        <th class="border-2 border-gray-400 dark:border-gray-500 sticky left-0 z-30"></th>
                        <th class="border-2 border-gray-400 dark:border-gray-500 sticky left-24 z-30"></th>
                        <template x-for="grade in [{name: 'X', color: 'blue'}, {name: 'XI', color: 'green'}, {name: 'XII', color: 'purple'}]" :key="grade.name + '-numbers'">
                            <template x-for="i in 12" :key="grade.name + '-' + i">
                                <th class="w-8 px-1 py-2 text-xs font-bold text-gray-700 dark:text-gray-300 border border-gray-400 dark:border-gray-500 text-center"
                                    :class="{
                                        'bg-blue-50/80 dark:bg-blue-900/25': grade.color === 'blue',
                                        'bg-green-50/80 dark:bg-green-900/25': grade.color === 'green',
                                        'bg-purple-50/80 dark:bg-purple-900/25': grade.color === 'purple'
                                    }"
                                    x-text="i">
                                </th>
                            </template>
                        </template>
                    </tr>
                </thead>
                
                <tbody class="bg-white dark:bg-gray-800">
                    <template x-for="(day, dayIndex) in days" :key="day">
                        <template x-for="period in [...Array(getMaxPeriods(day)).keys()].map(i => i + 1)" :key="day + '-' + period">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all duration-200">
                                <!-- Day column (only show on first period of each day) -->
                                <td x-show="period === 1" :rowspan="getMaxPeriods(day)" 
                                    class="w-24 px-3 py-2 text-center text-sm font-bold text-white border-2 border-gray-400 dark:border-gray-500 bg-gradient-to-br from-blue-600 to-blue-500 dark:from-blue-700 dark:to-blue-600 sticky left-0 z-10"
                                    x-text="day">
                                </td>
                                
                                <!-- Period column -->
                                <td class="w-16 px-3 py-2 text-center text-sm font-bold text-gray-800 dark:text-gray-200 border-2 border-gray-400 dark:border-gray-500 bg-gradient-to-r from-gray-100 to-gray-50 dark:from-gray-700 dark:to-gray-600 sticky left-24 z-10">
                                    <span class="text-lg font-extrabold" x-text="period"></span>
                                </td>
                                
                                <!-- Schedule cells for each class -->
                                <template x-for="classLevel in ['X', 'XI', 'XII']" :key="day + '-' + period + '-' + classLevel">
                                    <template x-for="classNumber in 12" :key="day + '-' + period + '-' + classLevel + '-' + classNumber">
                                        <td class="w-8 px-0.5 py-0.5 border border-gray-300 dark:border-gray-600 relative" 
                                            :class="{
                                                'bg-blue-50/40 dark:bg-blue-900/15': classLevel === 'X',
                                                'bg-green-50/40 dark:bg-green-900/15': classLevel === 'XI',
                                                'bg-purple-50/40 dark:bg-purple-900/15': classLevel === 'XII'
                                            }">
                                            <div @click="toggleCell(day, period, classLevel, classNumber)" 
                                                 :class="getCellClass(day, period, classLevel, classNumber)"
                                                 class="w-full h-10 cursor-pointer rounded-sm transition-all duration-200 flex items-center justify-center text-xs font-bold hover:shadow-lg hover:scale-110 transform">
                                                <span x-text="getCellContent(day, period, classLevel, classNumber)" class="truncate"></span>
                                            </div>
                                        </td>
                                    </template>
                                </template>
                            </tr>
                        </template>
                    </template>
                    
                    <!-- Total Hours Row -->
                    <tr class="bg-gradient-to-r from-blue-100 to-blue-50 dark:from-blue-900/30 dark:to-blue-800/20 border-t-4 border-blue-400">
                        <td class="w-24 px-3 py-3 text-center text-sm font-extrabold text-blue-900 dark:text-blue-100 border-2 border-gray-400 dark:border-gray-500 bg-gradient-to-r from-blue-200 to-blue-100 dark:from-blue-800 dark:to-blue-700 sticky left-0 z-10">
                            <div class="flex items-center justify-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-xs">TOTAL</span>
                            </div>
                        </td>
                        <td class="w-16 px-3 py-3 text-center text-sm font-extrabold text-blue-900 dark:text-blue-100 border-2 border-gray-400 dark:border-gray-500 bg-gradient-to-r from-blue-200 to-blue-100 dark:from-blue-800 dark:to-blue-700 sticky left-24 z-10">
                            <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"/>
                            </svg>
                        </td>
                        <template x-for="classLevel in ['X', 'XI', 'XII']" :key="'total-' + classLevel">
                            <template x-for="classNumber in 12" :key="'total-' + classLevel + '-' + classNumber">
                                <td class="w-8 px-0.5 py-2 text-xs font-bold text-center border border-gray-300 dark:border-gray-600"
                                    :class="{
                                        'text-blue-800 dark:text-blue-200 bg-blue-100/60 dark:bg-blue-800/20': classLevel === 'X',
                                        'text-green-800 dark:text-green-200 bg-green-100/60 dark:bg-green-800/20': classLevel === 'XI',
                                        'text-purple-800 dark:text-purple-200 bg-purple-100/60 dark:bg-purple-800/20': classLevel === 'XII'
                                    }"
                                    x-text="getTotalHours(classLevel, classNumber)">
                                </td>
                            </template>
                        </template>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <!-- Usage Instructions -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mt-8">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Cara Penggunaan:</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">Langkah-langkah:</h4>
                    <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li>Masukkan kode guru (contoh: 1A, 2B, 3C)</li>
                        <li>Klik "Set Kode" untuk mengaktifkan guru</li>
                        <li>Klik pada sel di tabel untuk menugaskan guru ke jam dan kelas</li>
                        <li>Klik lagi pada sel yang sama untuk membatalkan penugasan</li>
                        <li>Gunakan "Simpan Jadwal" untuk menyimpan data</li>
                        <li>Gunakan "Export ke Excel" untuk mengunduh jadwal</li>
                    </ol>
                </div>
                <div>
                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">Keterangan:</h4>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li><span class="inline-block w-4 h-4 bg-blue-500 rounded mr-2"></span>Sel biru: Jam mengajar guru aktif</li>
                        <li><span class="inline-block w-4 h-4 bg-gray-200 rounded mr-2"></span>Sel kosong: Belum ada penugasan</li>
                        <li><span class="font-medium">Ctrl+Z:</span> Membatalkan aksi terakhir</li>
                        <li><span class="font-medium">Total Jam Guru:</span> Jumlah jam mengajar per kelas</li>
                        <li><span class="font-medium">Total Jam Kelas:</span> Total jam pelajaran per kelas</li>
                    </ul>
                </div>
            </div>
        </div>
    </x-ui.card>
</div>

<script>
function scheduleBuilder() {
    return {
        teacherCode: '',
        currentTeacher: '',
        days: ['SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT'],
        periods: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
        schedule: {},
        history: [],
        canUndo: false,

        init() {
            // Initialize empty schedule
            this.initializeSchedule();
            
            // Load from localStorage if available
            const saved = localStorage.getItem('teacherSchedule');
            if (saved) {
                try {
                    const data = JSON.parse(saved);
                    this.schedule = data.schedule || {};
                    this.currentTeacher = data.currentTeacher || '';
                } catch (e) {
                    console.error('Error loading saved schedule:', e);
                }
            }

            // Add keyboard listener for Ctrl+Z
            document.addEventListener('keydown', (e) => {
                if (e.ctrlKey && e.key === 'z') {
                    e.preventDefault();
                    this.undo();
                }
            });
        },

        initializeSchedule() {
            this.schedule = {};
            this.days.forEach(day => {
                this.schedule[day] = {};
                this.periods.forEach(period => {
                    this.schedule[day][period] = {};
                    ['X', 'XI', 'XII'].forEach(classLevel => {
                        this.schedule[day][period][classLevel] = {};
                        for (let i = 1; i <= 12; i++) {
                            this.schedule[day][period][classLevel][i] = null;
                        }
                    });
                });
            });
        },

        setTeacherCode() {
            if (this.teacherCode.trim()) {
                this.currentTeacher = this.teacherCode.trim();
                this.teacherCode = '';
                this.saveToStorage();
                this.showNotification('Kode guru berhasil diset: ' + this.currentTeacher, 'success');
            }
        },

        logoutTeacher() {
            this.currentTeacher = '';
            this.saveToStorage();
            this.showNotification('Guru berhasil logout', 'info');
        },

        getMaxPeriods(day) {
            return day === 'JUMAT' ? 7 : 10;
        },

        toggleCell(day, period, classLevel, classNumber) {
            if (!this.currentTeacher) {
                this.showNotification('Silakan set kode guru terlebih dahulu', 'warning');
                return;
            }

            // Save current state for undo
            this.saveToHistory();

            const currentValue = this.schedule[day][period][classLevel][classNumber];
            
            if (currentValue === this.currentTeacher) {
                // Remove assignment
                this.schedule[day][period][classLevel][classNumber] = null;
            } else {
                // Add assignment
                this.schedule[day][period][classLevel][classNumber] = this.currentTeacher;
            }

            this.saveToStorage();
        },

        getCellClass(day, period, classLevel, classNumber) {
            const assignment = this.schedule[day][period][classLevel][classNumber];
            if (assignment === this.currentTeacher) {
                return 'bg-blue-500 text-white hover:bg-blue-600';
            } else if (assignment) {
                return 'bg-gray-300 text-gray-700 hover:bg-gray-400';
            } else {
                return 'bg-gray-100 dark:bg-gray-600 hover:bg-blue-100 dark:hover:bg-blue-800 border border-gray-300 dark:border-gray-500';
            }
        },

        getCellContent(day, period, classLevel, classNumber) {
            const assignment = this.schedule[day][period][classLevel][classNumber];
            return assignment || '';
        },

        getTotalHours(classLevel, classNumber) {
            let total = 0;
            this.days.forEach(day => {
                const maxPeriods = this.getMaxPeriods(day);
                for (let period = 1; period <= maxPeriods; period++) {
                    if (this.schedule[day][period][classLevel][classNumber]) {
                        total++;
                    }
                }
            });
            return total;
        },

        getTotalHoursForDayClass(day, classLevel, classNumber) {
            let total = 0;
            const maxPeriods = this.getMaxPeriods(day);
            for (let period = 1; period <= maxPeriods; period++) {
                if (this.schedule[day] && this.schedule[day][period] && this.schedule[day][period][classLevel] && this.schedule[day][period][classLevel][classNumber]) {
                    total++;
                }
            }
            return total;
        },

        getClassTotalHours(classLevel, classNumber) {
            // For now, return same as getTotalHours
            // In real implementation, this would count all teachers
            return this.getTotalHours(classLevel, classNumber);
        },

        saveToHistory() {
            this.history.push(JSON.parse(JSON.stringify(this.schedule)));
            if (this.history.length > 50) {
                this.history.shift(); // Keep only last 50 states
            }
            this.canUndo = this.history.length > 0;
        },

        undo() {
            if (this.history.length > 0) {
                this.schedule = this.history.pop();
                this.canUndo = this.history.length > 0;
                this.saveToStorage();
                this.showNotification('Undo berhasil', 'info');
            }
        },

        resetData() {
            if (confirm('Apakah Anda yakin ingin mereset semua data jadwal?')) {
                this.initializeSchedule();
                this.history = [];
                this.canUndo = false;
                this.saveToStorage();
                this.showNotification('Data berhasil direset', 'success');
            }
        },

        saveToJSON() {
            const data = {
                schedule: this.schedule,
                currentTeacher: this.currentTeacher,
                timestamp: new Date().toISOString()
            };
            
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `jadwal-mengajar-${this.currentTeacher || 'backup'}-${new Date().toISOString().split('T')[0]}.json`;
            a.click();
            URL.revokeObjectURL(url);
            
            this.showNotification('Jadwal berhasil disimpan', 'success');
        },

        loadFromJSON(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        const data = JSON.parse(e.target.result);
                        if (data.schedule) {
                            this.schedule = data.schedule;
                            this.currentTeacher = data.currentTeacher || '';
                            this.saveToStorage();
                            this.showNotification('Jadwal berhasil dimuat', 'success');
                        }
                    } catch (error) {
                        this.showNotification('Error loading file: ' + error.message, 'error');
                    }
                };
                reader.readAsText(file);
            }
        },

        exportToExcel() {
            // Create CSV content
            let csv = 'HARI,JAM,';
            
            // Headers
            ['X', 'XI', 'XII'].forEach(classLevel => {
                for (let i = 1; i <= 12; i++) {
                    csv += `${classLevel}-${i},`;
                }
            });
            csv += '\n';
            
            // Data rows
            this.days.forEach(day => {
                const maxPeriods = this.getMaxPeriods(day);
                for (let period = 1; period <= maxPeriods; period++) {
                    csv += `${day},${period},`;
                    ['X', 'XI', 'XII'].forEach(classLevel => {
                        for (let classNumber = 1; classNumber <= 12; classNumber++) {
                            const assignment = this.schedule[day][period][classLevel][classNumber];
                            csv += `${assignment || ''},`;
                        }
                    });
                    csv += '\n';
                }
            });
            
            // Download
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `jadwal-mengajar-${this.currentTeacher || 'export'}-${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            URL.revokeObjectURL(url);
            
            this.showNotification('Excel berhasil diexport', 'success');
        },

        saveToStorage() {
            const data = {
                schedule: this.schedule,
                currentTeacher: this.currentTeacher
            };
            localStorage.setItem('teacherSchedule', JSON.stringify(data));
        },

        showNotification(message, type = 'info') {
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-yellow-500',
                info: 'bg-blue-500'
            };
            
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg ${colors[type]} text-white`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    }
}
</script>
@endsection