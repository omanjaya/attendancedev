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
                <button onclick="location.href='{{ route('schedules.index') }}'" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105">
                    <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali
                </button>
                
                <!-- Analytics Button -->
                <button @click="showAnalytics()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 shadow-md">
                    <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Analitik
                </button>
                
                <!-- Schedule Builder Button -->
                <button onclick="location.href='{{ route('schedules.builder') }}'" class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 shadow-md">
                    <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Schedule Builder
                </button>
            </div>
        </div>
    </div>

    <!-- Enhanced Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        <!-- Total Schedules Card -->
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg shadow-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium px-3 py-1 bg-blue-100 text-blue-800 rounded-full">Minggu Ini</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100" x-text="weekSchedules.length">0</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Total Jadwal</p>
            </div>
        </x-ui.card>

        <!-- Active Teachers Card -->
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-r from-green-600 to-emerald-600 rounded-lg shadow-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium px-3 py-1 bg-green-100 text-green-800 rounded-full">Aktif</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100" x-text="activeTeachers">0</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Guru Aktif</p>
            </div>
        </x-ui.card>

        <!-- Subjects Count Card -->
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-r from-purple-600 to-purple-700 rounded-lg shadow-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium px-3 py-1 bg-purple-100 text-purple-800 rounded-full">Total</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100" x-text="uniqueSubjects">0</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Mata Pelajaran</p>
            </div>
        </x-ui.card>

        <!-- Classes Count Card -->
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-r from-orange-600 to-orange-700 rounded-lg shadow-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium px-3 py-1 bg-orange-100 text-orange-800 rounded-full">Total</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100" x-text="uniqueClasses">0</h3>
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
                    <button @click="previousWeek()" class="flex items-center justify-center w-10 h-10 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-110" title="Minggu Sebelumnya">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button @click="currentWeek()" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg font-medium transition-all duration-200 transform hover:scale-105 shadow-md">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Hari Ini
                    </button>
                    <button @click="nextWeek()" class="flex items-center justify-center w-10 h-10 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-110" title="Minggu Selanjutnya">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
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
                        <button @click="viewMode = 'week'" :class="viewMode === 'week' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''" class="px-3 py-1.5 rounded-md text-sm font-medium transition-all duration-200">
                            Minggu
                        </button>
                        <button @click="viewMode = 'day'" :class="viewMode === 'day' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''" class="px-3 py-1.5 rounded-md text-sm font-medium transition-all duration-200">
                            Hari
                        </button>
                    </div>
                    
                    <!-- Teacher Filter -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                            <span class="text-sm font-medium">Filter</span>
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50">
                            <div class="p-4">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Filter Guru</h4>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input type="checkbox" x-model="filterAllTeachers" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Semua Guru</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" value="1A" x-model="selectedTeachers" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Guru 1A</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" value="2B" x-model="selectedTeachers" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Guru 2B</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" value="3C" x-model="selectedTeachers" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Guru 3C</span>
                                    </label>
                                </div>
                                <button @click="applyFilters(); open = false" class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                                    Terapkan Filter
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Export Button -->
                    <button @click="exportCalendar()" class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 shadow-md">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
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
            <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
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
                                    <span class="inline-block w-3 h-3 bg-gradient-to-r from-blue-500 to-blue-600 rounded mr-1"></span>
                                    Matematika
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center">
                                    <span class="inline-block w-3 h-3 bg-gradient-to-r from-green-500 to-emerald-600 rounded mr-1"></span>
                                    Sains
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center">
                                    <span class="inline-block w-3 h-3 bg-gradient-to-r from-purple-500 to-purple-600 rounded mr-1"></span>
                                    Bahasa
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center">
                                    <span class="inline-block w-3 h-3 bg-gradient-to-r from-orange-500 to-orange-600 rounded mr-1"></span>
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
                                                <div class="p-3 rounded-lg cursor-pointer transition-all duration-200 hover:shadow-lg hover:scale-105 transform relative overflow-hidden"
                                                     :class="getScheduleClass(schedule)"
                                                     @click="showScheduleDetails(schedule)">
                                                    <!-- Gradient overlay -->
                                                    <div class="absolute inset-0 bg-gradient-to-r opacity-10" :class="getGradientClass(schedule.subject)"></div>
                                                    
                                                    <div class="relative z-10">
                                                        <div class="font-semibold text-sm mb-1" x-text="schedule.subject"></div>
                                                        <div class="text-xs opacity-90 mb-1 flex items-center">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                            </svg>
                                                            <span x-text="schedule.teacher"></span>
                                                        </div>
                                                        <div class="text-xs opacity-75 flex items-center">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                            </svg>
                                                            <span x-text="schedule.class"></span>
                                                        </div>
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
                                <span x-text="filteredSchedules.length" class="font-semibold text-blue-600 dark:text-blue-400"></span>
                            </div>
                            <div class="text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Jam Terisi:</span> 
                                <span x-text="filledSlots" class="font-semibold text-green-600 dark:text-green-400"></span>
                            </div>
                            <div class="text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Slot Kosong:</span> 
                                <span x-text="emptySlots" class="font-semibold text-orange-600 dark:text-orange-400"></span>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Klik pada jadwal untuk melihat detail
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <!-- Quick Actions Sidebar -->
        <div class="xl:col-span-1">
            <!-- Quick Actions Card -->
            <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Quick Actions
                    </h3>
                </div>
                <div class="p-6 space-y-3">
                    <button @click="showAddScheduleModal()" class="w-full text-left px-4 py-3 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors duration-200">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">Tambah Jadwal</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Buat jadwal baru</div>
                            </div>
                        </div>
                    </button>
                    
                    <button @click="duplicateWeek()" class="w-full text-left px-4 py-3 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition-colors duration-200">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">Duplikasi Minggu</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Salin jadwal minggu ini</div>
                            </div>
                        </div>
                    </button>
                    
                    <button @click="clearWeek()" class="w-full text-left px-4 py-3 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors duration-200">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">Hapus Semua</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Kosongkan jadwal minggu ini</div>
                            </div>
                        </div>
                    </button>
                    
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                        <button @click="printCalendar()" class="w-full text-left px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
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
            <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Beban Mengajar
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    <template x-for="teacher in teacherWorkload" :key="teacher.id">
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-r" :class="getTeacherColorClass(teacher.name)" class="flex items-center justify-center text-white font-medium text-sm">
                                        <span x-text="teacher.name.substring(5, 7)"></span>
                                    </div>
                                    <div class="ml-3">
                                        <div class="font-medium text-gray-900 dark:text-white" x-text="teacher.name"></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400" x-text="teacher.subject"></div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white" x-text="teacher.hours + ' jam'"></div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400" x-text="teacher.percentage + '%'"></div>
                                </div>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all duration-300" 
                                     :class="teacher.percentage > 80 ? 'bg-gradient-to-r from-red-500 to-red-600' : teacher.percentage > 60 ? 'bg-gradient-to-r from-yellow-500 to-orange-600' : 'bg-gradient-to-r from-green-500 to-emerald-600'"
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