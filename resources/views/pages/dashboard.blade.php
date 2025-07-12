@extends('layouts.authenticated')

@section('title', 'Dashboard')

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Glassmorphism Animations */
        @keyframes blob {
            0% {
                transform: translate(0px, 0px) scale(1);
            }
            33% {
                transform: translate(30px, -50px) scale(1.1);
            }
            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }
            100% {
                transform: translate(0px, 0px) scale(1);
            }
        }
        
        .animate-blob {
            animation: blob 7s infinite;
        }
        
        .animation-delay-2000 {
            animation-delay: 2s;
        }
        
        .animation-delay-4000 {
            animation-delay: 4s;
        }
        
        /* Enhanced Glassmorphism */
        .backdrop-blur-xl {
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
        }
        
        /* Better glass effect on hover */
        .hover\:bg-white\/40:hover {
            background-color: rgba(255, 255, 255, 0.4);
        }
        
        .dark .hover\:bg-gray-800\/40:hover {
            background-color: rgba(31, 41, 55, 0.4);
        }
        
        /* Enhanced shadow effects */
        .hover\:shadow-emerald-500\/25:hover {
            box-shadow: 0 25px 50px -12px rgba(16, 185, 129, 0.25);
        }
        
        .hover\:shadow-emerald-500\/20:hover {
            box-shadow: 0 25px 50px -12px rgba(16, 185, 129, 0.2);
        }
        
        .hover\:shadow-emerald-500\/30:hover {
            box-shadow: 0 25px 50px -12px rgba(16, 185, 129, 0.3);
        }
        
        /* Glass border animation */
        .group:hover .border-white\/20 {
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        .dark .group:hover .border-gray-700\/30 {
            border-color: rgba(55, 65, 81, 0.4);
        }
        
        /* Responsive spacing improvements */
        @media (max-width: 640px) {
            .space-y-6 > :not([hidden]) ~ :not([hidden]) {
                --tw-space-y-reverse: 0;
                margin-top: calc(1rem * calc(1 - var(--tw-space-y-reverse)));
                margin-bottom: calc(1rem * var(--tw-space-y-reverse));
            }
            
            .gap-3 {
                gap: 0.75rem;
            }
        }
    </style>
@endpush

@section('page-content')
    <!-- Glassmorphism Background -->
    <div class="fixed inset-0 -z-10">
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-50/40 via-green-50/30 to-teal-50/40 dark:from-gray-900/80 dark:via-gray-800/70 dark:to-gray-900/80"></div>
        <div class="absolute top-20 left-20 w-96 h-96 bg-emerald-400/20 dark:bg-emerald-400/30 rounded-full mix-blend-multiply dark:mix-blend-soft-light filter blur-xl opacity-70 animate-blob"></div>
        <div class="absolute top-40 right-20 w-96 h-96 bg-green-400/20 dark:bg-green-400/30 rounded-full mix-blend-multiply dark:mix-blend-soft-light filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
        <div class="absolute bottom-20 left-40 w-96 h-96 bg-teal-400/20 dark:bg-teal-400/30 rounded-full mix-blend-multiply dark:mix-blend-soft-light filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
    </div>

    <div class="p-4 sm:p-6 space-y-6 sm:space-y-8" x-data="modernDashboard()" x-init="initDashboard()">
        <!-- Glassmorphism Welcome Section -->
        <div class="relative">
            <div class="absolute inset-0 bg-gradient-to-r from-emerald-500/80 to-green-600/80 dark:from-emerald-600/95 dark:to-green-700/95 rounded-2xl sm:rounded-3xl backdrop-blur-xl border border-white/20 dark:border-gray-600/40"></div>
            <div class="relative px-6 py-4 sm:px-8 sm:py-6 text-white">
                @php
                    $currentTime = now();
                    $greeting = $currentTime->hour < 12 ? 'Good Morning' : ($currentTime->hour < 17 ? 'Good Afternoon' : 'Good Evening');
                    $user = auth()->user();
                @endphp
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold mb-1 sm:mb-2 drop-shadow-lg">{{ $greeting }}, {{ $user->name }}! 👋</h1>
                <p class="text-sm sm:text-base text-emerald-100 drop-shadow">{{ $currentTime->format('F j, Y') }} • Here's what's happening at your school today</p>
            </div>
        </div>

        <!-- Stats Cards -->
        @php
            $stats = [
                'present_today' => 28,
                'total_employees' => 34,
                'attendance_rate' => 82.4,
                'pending_requests' => 5,
            ];
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-6">
                <!-- Present Today -->
                @can('view_attendance_reports')
                <div class="relative backdrop-blur-xl bg-white/30 dark:bg-gray-800/30 border border-white/20 dark:border-gray-700/30 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-2xl transition-all duration-500 hover:shadow-emerald-500/25 hover:shadow-2xl hover:-translate-y-2 hover:bg-white/40 dark:hover:bg-gray-800/40 group">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <div class="p-2 sm:p-3 bg-gradient-to-r from-green-400 to-emerald-500 rounded-xl sm:rounded-xl sm:rounded-2xl group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="text-right">
                            <div class="flex items-center text-green-500 text-xs sm:text-sm font-semibold">
                                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                                </svg>
                                <span>+2</span>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-1 sm:space-y-2">
                        <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800 dark:text-white">{{ $stats['present_today'] }}</p>
                        <p class="text-gray-600 dark:text-gray-400 text-xs sm:text-sm font-medium">Present Today</p>
                        <p class="text-xs text-gray-500">out of {{ $stats['total_employees'] }} employees</p>
                    </div>
                    <div class="mt-3 sm:mt-4 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 sm:h-2">
                        <div class="bg-gradient-to-r from-green-400 to-emerald-500 h-1.5 sm:h-2 rounded-full transition-all duration-1000 ease-out"
                             style="width: {{ round(($stats['present_today'] / $stats['total_employees']) * 100) }}%"></div>
                    </div>
                </div>
                @endcan

                <!-- Attendance Rate -->
                @can('view_attendance_reports')
                <div class="relative backdrop-blur-xl bg-white/30 dark:bg-gray-800/30 border border-white/20 dark:border-gray-700/30 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-2xl transition-all duration-500 hover:shadow-emerald-500/25 hover:shadow-2xl hover:-translate-y-2 hover:bg-white/40 dark:hover:bg-gray-800/40 group">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <div class="p-2 sm:p-3 bg-gradient-to-r from-emerald-400 to-emerald-600 rounded-xl sm:rounded-2xl group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div class="text-right">
                            <div class="flex items-center text-emerald-500 text-sm font-semibold">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                                </svg>
                                <span>+3.2%</span>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-1 sm:space-y-2">
                        <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800 dark:text-white">{{ $stats['attendance_rate'] }}%</p>
                        <p class="text-gray-600 dark:text-gray-400 text-xs sm:text-sm font-medium">Attendance Rate</p>
                        <p class="text-xs text-green-600 font-medium">Above target</p>
                    </div>
                    <!-- Circular Progress -->
                    <div class="mt-4 flex justify-center">
                        <div class="relative w-16 h-16">
                            <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="40" stroke="currentColor" stroke-width="8" fill="none" class="text-gray-200 dark:text-gray-700"/>
                                <circle cx="50" cy="50" r="40" stroke="currentColor" stroke-width="8" fill="none" 
                                        class="text-emerald-500 progress-ring" 
                                        stroke-dasharray="{{ $stats['attendance_rate'] * 2.51 }} 251"/>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">{{ round($stats['attendance_rate']) }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
                @endcan

                <!-- Pending Requests -->
                @can('approve_leave')
                <div class="relative backdrop-blur-xl bg-white/30 dark:bg-gray-800/30 border border-white/20 dark:border-gray-700/30 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-2xl transition-all duration-500 hover:shadow-emerald-500/25 hover:shadow-2xl hover:-translate-y-2 hover:bg-white/40 dark:hover:bg-gray-800/40 group">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <div class="p-2 sm:p-3 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-xl sm:rounded-2xl group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="text-right">
                            <div class="flex items-center text-green-500 text-sm font-semibold">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                                </svg>
                                <span>-2</span>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-1 sm:space-y-2">
                        <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800 dark:text-white">{{ $stats['pending_requests'] }}</p>
                        <p class="text-gray-600 dark:text-gray-400 text-xs sm:text-sm font-medium">Pending Requests</p>
                        <p class="text-xs text-yellow-600 font-medium">Need review</p>
                    </div>
                    <div class="mt-4 flex space-x-1">
                        @for($i = 0; $i < $stats['pending_requests']; $i++)
                            <div class="flex-1 h-2 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-full animate-pulse-slow"></div>
                        @endfor
                    </div>
                </div>
                @endcan

                <!-- Quick Actions -->
                <div class="relative backdrop-blur-xl bg-white/30 dark:bg-gray-800/30 border border-white/20 dark:border-gray-700/30 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-2xl transition-all duration-500 hover:shadow-emerald-500/25 hover:shadow-2xl hover:-translate-y-2 hover:bg-white/40 dark:hover:bg-gray-800/40 group">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <div class="p-2 sm:p-3 bg-gradient-to-r from-green-400 to-teal-500 rounded-xl sm:rounded-2xl group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-green-500 font-medium">Available</span>
                        </div>
                    </div>
                    <div class="space-y-1 sm:space-y-2">
                        <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800 dark:text-white">3</p>
                        <p class="text-gray-600 dark:text-gray-400 text-xs sm:text-sm font-medium">Quick Actions</p>
                        <p class="text-xs text-gray-500">Ready to use</p>
                    </div>
                    <div class="mt-4 grid grid-cols-3 gap-1">
                        <div class="h-6 bg-gradient-to-r from-green-400 to-teal-500 rounded opacity-80"></div>
                        <div class="h-6 bg-gradient-to-r from-green-400 to-teal-500 rounded opacity-60"></div>
                        <div class="h-6 bg-gradient-to-r from-green-400 to-teal-500 rounded opacity-40"></div>
                    </div>
                </div>
            </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                <!-- Attendance Chart -->
                <div class="lg:col-span-2 relative backdrop-blur-xl bg-white/25 dark:bg-gray-800/25 border border-white/20 dark:border-gray-700/30 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-2xl transition-all duration-500 hover:shadow-emerald-500/20 hover:shadow-2xl hover:-translate-y-1 hover:bg-white/35 dark:hover:bg-gray-800/35">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 dark:text-white">Attendance Trends</h3>
                            <p class="text-gray-600 dark:text-gray-400">Weekly overview with predictions</p>
                        </div>
                        <div class="flex space-x-2">
                            <button class="px-4 py-2 text-sm font-medium text-emerald-600 bg-emerald-50 rounded-lg hover:bg-emerald-100 transition-colors">7D</button>
                            <button class="px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">30D</button>
                            <button class="px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">90D</button>
                        </div>
                    </div>
                    <div class="h-80">
                        <canvas id="attendanceChart" class="w-full h-full"></canvas>
                    </div>
                </div>

                <!-- Today's Schedule -->
                <div class="relative backdrop-blur-xl bg-white/25 dark:bg-gray-800/25 border border-white/20 dark:border-gray-700/30 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-2xl transition-all duration-500 hover:shadow-emerald-500/20 hover:shadow-2xl hover:-translate-y-1 hover:bg-white/35 dark:hover:bg-gray-800/35">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 dark:text-white">Today's Schedule</h3>
                            <p class="text-gray-600 dark:text-gray-400">4 events scheduled</p>
                        </div>
                        <button class="p-2 hover:bg-gray-100/80 dark:hover:bg-gray-700/80 rounded-lg transition-colors">
                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                            </svg>
                        </button>
                    </div>

                    @php
                        $todaySchedule = [
                            [
                                'title' => 'Mathematics Grade 10',
                                'time' => '09:00 AM',
                                'location' => 'Room 101',
                                'type' => 'class',
                                'status' => 'upcoming'
                            ],
                            [
                                'title' => 'English Grade 9',
                                'time' => '10:30 AM',
                                'location' => 'Room 205',
                                'type' => 'class',
                                'status' => 'active'
                            ],
                            [
                                'title' => 'Staff Meeting',
                                'time' => '02:00 PM',
                                'location' => 'Conference Room',
                                'type' => 'meeting',
                                'status' => 'upcoming'
                            ],
                            [
                                'title' => 'Science Lab Grade 11',
                                'time' => '03:30 PM',
                                'location' => 'Lab 1',
                                'type' => 'lab',
                                'status' => 'upcoming'
                            ]
                        ];
                    @endphp

                    <div class="space-y-4 overflow-y-auto max-h-96 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 scrollbar-track-transparent">
                        @foreach($todaySchedule as $event)
                            <div class="flex items-start space-x-4 p-4 rounded-2xl hover:bg-gray-50/80 dark:hover:bg-gray-700/50 transition-all duration-300 cursor-pointer group relative">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-2xl flex items-center justify-center
                                         {{ $event['type'] === 'class' ? 'bg-emerald-100 text-emerald-600' : 
                                            ($event['type'] === 'meeting' ? 'bg-green-100 text-green-600' : 
                                            'bg-teal-100 text-teal-600') }}">
                                        @if($event['type'] === 'class')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                            </svg>
                                        @elseif($event['type'] === 'meeting')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                            </svg>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <p class="font-semibold text-gray-800 dark:text-white text-sm">{{ $event['title'] }}</p>
                                        <span class="text-xs text-gray-500 font-medium">{{ $event['time'] }}</span>
                                    </div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ $event['location'] }}</p>
                                    <div class="flex items-center mt-2">
                                        <div class="w-2 h-2 rounded-full mr-2
                                             {{ $event['status'] === 'upcoming' ? 'bg-emerald-400' : 
                                                ($event['status'] === 'active' ? 'bg-green-400 animate-pulse' : 
                                                'bg-gray-400') }}"></div>
                                        <span class="text-xs font-medium capitalize
                                              {{ $event['status'] === 'upcoming' ? 'text-emerald-600' : 
                                                 ($event['status'] === 'active' ? 'text-green-600' : 
                                                 'text-gray-500') }}">{{ $event['status'] }}</span>
                                    </div>
                                </div>
                                <button class="opacity-0 group-hover:opacity-100 p-2 hover:bg-gray-100/80 dark:hover:bg-gray-700/80 rounded-lg transition-all duration-300">
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-200/50 dark:border-gray-700/50">
                        <button class="w-full py-3 bg-gradient-to-r from-emerald-500 to-green-600 text-white rounded-2xl hover:shadow-lg transform hover:scale-105 transition-all duration-300">
                            <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add New Event
                        </button>
                    </div>
                </div>
            </div>

        <!-- Bottom Section -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 sm:gap-6">
                <!-- Recent Activity -->
                <div class="lg:col-span-2 relative backdrop-blur-xl bg-white/25 dark:bg-gray-800/25 border border-white/20 dark:border-gray-700/30 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-2xl transition-all duration-500 hover:shadow-emerald-500/20 hover:shadow-2xl hover:-translate-y-1 hover:bg-white/35 dark:hover:bg-gray-800/35">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-800 dark:text-white">Recent Activity</h3>
                        <button class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">View all</button>
                    </div>

                    @php
                        $recentActivity = [
                            [
                                'icon' => 'fas fa-sign-in-alt',
                                'action' => 'Mike Johnson checked in',
                                'time' => '2 minutes ago',
                                'status' => 'Present'
                            ],
                            [
                                'icon' => 'fas fa-calendar-times',
                                'action' => 'Leave request submitted',
                                'time' => '15 minutes ago',
                                'status' => 'Pending'
                            ],
                            [
                                'icon' => 'fas fa-user-edit',
                                'action' => 'Profile updated',
                                'time' => '1 hour ago',
                                'status' => 'Completed'
                            ]
                        ];
                    @endphp

                    <div class="space-y-4">
                        @foreach($recentActivity as $activity)
                            <div class="flex items-center space-x-4 p-3 hover:bg-gray-50/80 dark:hover:bg-gray-700/50 rounded-xl transition-colors">
                                <div class="w-10 h-10 rounded-2xl bg-gradient-to-r from-emerald-400 to-green-500 flex items-center justify-center">
                                    @if($activity['icon'] === 'fas fa-sign-in-alt')
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                        </svg>
                                    @elseif($activity['icon'] === 'fas fa-calendar-times')
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $activity['action'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $activity['time'] }}</p>
                                </div>
                                <div class="text-xs text-gray-400">{{ $activity['status'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Weather Widget -->
                <div class="relative backdrop-blur-xl bg-gradient-to-br from-emerald-500/90 to-green-600/90 border border-white/20 rounded-3xl p-6 text-white shadow-2xl transition-all duration-500 hover:shadow-emerald-500/30 hover:shadow-2xl hover:-translate-y-1 hover:from-emerald-500 hover:to-green-600">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <div>
                            <p class="text-sm opacity-90">Today's Weather</p>
                            <p class="text-2xl font-bold">24°C</p>
                        </div>
                        <div class="text-right">
                            <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="space-y-2 text-sm opacity-90">
                        <div class="flex justify-between">
                            <span>Humidity</span>
                            <span>65%</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Wind</span>
                            <span>12 km/h</span>
                        </div>
                    </div>
                    <p class="text-xs opacity-75 mt-4">Perfect weather for school activities!</p>
                </div>

                <!-- Quick Stats -->
                <div class="relative backdrop-blur-xl bg-white/25 dark:bg-gray-800/25 border border-white/20 dark:border-gray-700/30 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-2xl transition-all duration-500 hover:shadow-emerald-500/20 hover:shadow-2xl hover:-translate-y-1 hover:bg-white/35 dark:hover:bg-gray-800/35">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Quick Stats</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Total Teachers</span>
                            <span class="font-semibold text-gray-800 dark:text-white">{{ $stats['total_employees'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Active Classes</span>
                            <span class="font-semibold text-gray-800 dark:text-white">12</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">This Month</span>
                            <span class="font-semibold text-green-600">+5.2%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-4">
                            <div class="bg-gradient-to-r from-emerald-400 to-green-500 h-2 rounded-full transition-all duration-1000 ease-out" style="width: 78%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function modernDashboard() {
            return {
                darkMode: false,
                showNotifications: false,
                showQuickActions: false,
                searchQuery: '',

                initDashboard() {
                    this.initChart();
                    // Check for saved dark mode preference
                    this.darkMode = localStorage.getItem('darkMode') === 'true';
                    this.applyDarkMode();
                    
                    // Show welcome notification
                    setTimeout(() => {
                        if (typeof toast !== 'undefined') {
                            toast.success('Dashboard and sidebar loaded with unified glassmorphism design!', {
                                title: 'Welcome Back',
                                duration: 4000,
                                progress: true
                            });
                        }
                    }, 1000);
                },

                toggleDarkMode() {
                    this.darkMode = !this.darkMode;
                    localStorage.setItem('darkMode', this.darkMode);
                    this.applyDarkMode();
                },

                applyDarkMode() {
                    if (this.darkMode) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                },

                initChart() {
                    const ctx = document.getElementById('attendanceChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                            datasets: [{
                                label: 'Present',
                                data: [28, 30, 25, 32, 28, 15, 8],
                                borderColor: 'rgb(16, 185, 129)',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4
                            }, {
                                label: 'Expected',
                                data: [32, 32, 32, 32, 32, 20, 12],
                                borderColor: 'rgb(52, 211, 153)',
                                backgroundColor: 'rgba(52, 211, 153, 0.05)',
                                borderWidth: 2,
                                borderDash: [5, 5],
                                fill: false,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        usePointStyle: true,
                                        padding: 20
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(156, 163, 175, 0.1)'
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    }
                                }
                            },
                            elements: {
                                point: {
                                    radius: 6,
                                    hoverRadius: 8
                                }
                            }
                        }
                    });
                }
            }
        }
    </script>
@endsection