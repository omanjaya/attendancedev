<!-- Glassmorphism Sidebar -->
<div class="flex h-full flex-col overflow-hidden relative">
    <!-- Glassmorphism Background -->
    <div class="absolute inset-0 bg-gradient-to-b from-emerald-50/80 via-green-50/60 to-teal-50/80 dark:from-gray-800/90 dark:via-gray-700/80 dark:to-gray-800/90 backdrop-blur-xl"></div>
    
    <!-- Animated Background Elements -->
    <div class="absolute top-10 right-4 w-20 h-20 bg-emerald-400/10 rounded-full blur-xl animate-pulse"></div>
    <div class="absolute bottom-20 left-4 w-16 h-16 bg-green-400/10 rounded-full blur-xl animate-pulse" style="animation-delay: 2s"></div>
    
    <!-- Sidebar Header -->
    <div class="relative flex items-center justify-between border-b border-white/20 dark:border-gray-600/40 px-4 py-4 backdrop-blur-sm">
        <div class="flex items-center space-x-3">
            <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 flex items-center justify-center shadow-lg shadow-emerald-500/25 transition-all duration-300 hover:scale-110 hover:shadow-emerald-500/40">
                <span class="text-sm font-bold text-white">A</span>
            </div>
            <div>
                <h1 class="text-sm font-bold text-gray-800 dark:text-white">AttendanceHub</h1>
                <p class="text-xs text-gray-600 dark:text-gray-300">School Management</p>
            </div>
        </div>
    </div>

    <!-- Sidebar Content -->
    <div class="flex-1 overflow-y-auto relative">
        <nav class="px-4 py-4">
            
            <!-- Dashboard Section -->
            <div class="mb-6">
                <a href="{{ route('dashboard') }}" 
                   class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-300 {{ request()->routeIs('dashboard*') ? 'bg-white/40 dark:bg-gray-700/60 text-emerald-700 dark:text-emerald-200 shadow-lg shadow-emerald-500/20 backdrop-blur-sm border border-white/30 dark:border-gray-500/30' : 'text-gray-700 dark:text-gray-200 hover:bg-white/30 dark:hover:bg-gray-700/40 hover:text-emerald-700 dark:hover:text-emerald-200 hover:shadow-md hover:backdrop-blur-sm' }}">
                    <svg class="mr-3 h-4 w-4 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2V7zm16 0V7a2 2 0 00-2-2H7a2 2 0 00-2 2v0"/>
                    </svg>
                    Dashboard
                </a>
            </div>

            <!-- Master Data Section -->
            <div class="mb-6">
                <div class="px-3 mb-3">
                    <div class="flex items-center gap-2">
                        <h3 class="text-xs font-semibold text-gray-600 dark:text-gray-200 uppercase tracking-wider">Master Data</h3>
                        <div class="flex-1 h-px bg-gradient-to-r from-gray-300/50 via-transparent to-transparent dark:from-gray-400/60"></div>
                    </div>
                </div>
                <div class="space-y-1">
                    <!-- Employees -->
                    @can('view_employees')
                    <a href="{{ route('employees.index') }}" 
                       class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-300 {{ request()->routeIs('employees*') ? 'bg-white/40 dark:bg-gray-700/60 text-emerald-700 dark:text-emerald-200 shadow-lg shadow-emerald-500/20 backdrop-blur-sm border border-white/30 dark:border-gray-500/30' : 'text-gray-700 dark:text-gray-200 hover:bg-white/30 dark:hover:bg-gray-700/40 hover:text-emerald-700 dark:hover:text-emerald-200 hover:shadow-md hover:backdrop-blur-sm' }}">
                        <svg class="mr-3 h-4 w-4 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                        </svg>
                        Employees
                    </a>
                    @endcan

                    <!-- Schedules -->
                    @can('view_schedules')
                    <a href="{{ route('schedules.index') }}" 
                       class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-300 {{ request()->routeIs('schedules*') ? 'bg-white/40 dark:bg-gray-700/60 text-emerald-700 dark:text-emerald-200 shadow-lg shadow-emerald-500/20 backdrop-blur-sm border border-white/30 dark:border-gray-500/30' : 'text-gray-700 dark:text-gray-200 hover:bg-white/30 dark:hover:bg-gray-700/40 hover:text-emerald-700 dark:hover:text-emerald-200 hover:shadow-md hover:backdrop-blur-sm' }}">
                        <svg class="mr-3 h-4 w-4 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Schedules
                    </a>
                    @endcan
                </div>
            </div>

            <!-- Operations Section -->
            <div class="mb-6">
                <div class="px-3 mb-3">
                    <div class="flex items-center gap-2">
                        <h3 class="text-xs font-semibold text-gray-600 dark:text-gray-200 uppercase tracking-wider">Operations</h3>
                        <div class="flex-1 h-px bg-gradient-to-r from-gray-300/50 via-transparent to-transparent dark:from-gray-400/60"></div>
                    </div>
                </div>
                <div class="space-y-1">
                    <!-- Attendance -->
                    @can('view_attendance')
                    <a href="{{ route('attendance.index') }}" 
                       class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-300 {{ request()->routeIs('attendance*') && !request()->routeIs('attendance.check-in') ? 'bg-white/40 dark:bg-gray-700/60 text-emerald-700 dark:text-emerald-200 shadow-lg shadow-emerald-500/20 backdrop-blur-sm border border-white/30 dark:border-gray-500/30' : 'text-gray-700 dark:text-gray-200 hover:bg-white/30 dark:hover:bg-gray-700/40 hover:text-emerald-700 dark:hover:text-emerald-200 hover:shadow-md hover:backdrop-blur-sm' }}">
                        <svg class="mr-3 h-4 w-4 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Attendance
                    </a>
                    @endcan

                    <!-- Leave -->
                    @can('view_leave')
                    <a href="{{ route('leave.index') }}" 
                       class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-300 {{ request()->routeIs('leave*') ? 'bg-white/40 dark:bg-gray-700/60 text-emerald-700 dark:text-emerald-200 shadow-lg shadow-emerald-500/20 backdrop-blur-sm border border-white/30 dark:border-gray-500/30' : 'text-gray-700 dark:text-gray-200 hover:bg-white/30 dark:hover:bg-gray-700/40 hover:text-emerald-700 dark:hover:text-emerald-200 hover:shadow-md hover:backdrop-blur-sm' }}">
                        <svg class="mr-3 h-4 w-4 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Leave Management
                    </a>
                    @endcan

                    <!-- Payroll -->
                    @can('view_payroll')
                    <a href="{{ route('payroll.index') }}" 
                       class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-300 {{ request()->routeIs('payroll*') ? 'bg-white/40 dark:bg-gray-700/60 text-emerald-700 dark:text-emerald-200 shadow-lg shadow-emerald-500/20 backdrop-blur-sm border border-white/30 dark:border-gray-500/30' : 'text-gray-700 dark:text-gray-200 hover:bg-white/30 dark:hover:bg-gray-700/40 hover:text-emerald-700 dark:hover:text-emerald-200 hover:shadow-md hover:backdrop-blur-sm' }}">
                        <svg class="mr-3 h-4 w-4 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Payroll
                    </a>
                    @endcan
                </div>
            </div>

            <!-- Reports Section -->
            <div class="mb-6">
                <div class="px-3 mb-3">
                    <div class="flex items-center gap-2">
                        <h3 class="text-xs font-semibold text-gray-600 dark:text-gray-200 uppercase tracking-wider">Reports</h3>
                        <div class="flex-1 h-px bg-gradient-to-r from-gray-300/50 via-transparent to-transparent dark:from-gray-400/60"></div>
                    </div>
                </div>
                <div class="space-y-1">
                    <!-- Reports -->
                    @can('view_attendance_reports')
                    <a href="{{ route('attendance.reports') }}" 
                       class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-300 {{ request()->routeIs('*.reports') ? 'bg-white/40 dark:bg-gray-700/60 text-emerald-700 dark:text-emerald-200 shadow-lg shadow-emerald-500/20 backdrop-blur-sm border border-white/30 dark:border-gray-500/30' : 'text-gray-700 dark:text-gray-200 hover:bg-white/30 dark:hover:bg-gray-700/40 hover:text-emerald-700 dark:hover:text-emerald-200 hover:shadow-md hover:backdrop-blur-sm' }}">
                        <svg class="mr-3 h-4 w-4 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Analytics & Reports
                    </a>
                    @endcan
                </div>
            </div>

            <!-- Quick Actions Section -->
            <div class="mb-6">
                <div class="px-3 mb-3">
                    <div class="flex items-center gap-2">
                        <h3 class="text-xs font-semibold text-gray-600 dark:text-gray-200 uppercase tracking-wider">Quick Actions</h3>
                        <div class="flex-1 h-px bg-gradient-to-r from-gray-300/50 via-transparent to-transparent dark:from-gray-400/60"></div>
                    </div>
                </div>
                @can('manage_own_attendance')
                <a href="{{ route('attendance.check-in') }}" 
                   class="group flex items-center rounded-xl px-4 py-3 text-sm font-semibold transition-all duration-300 bg-gradient-to-r from-emerald-500/20 to-green-500/20 dark:from-emerald-600/30 dark:to-green-600/30 backdrop-blur-sm border border-emerald-400/30 dark:border-emerald-500/40 text-emerald-700 dark:text-emerald-200 hover:from-emerald-500 hover:to-green-500 hover:text-white hover:shadow-lg hover:shadow-emerald-500/25 hover:scale-105 {{ request()->routeIs('attendance.check-in') ? 'from-emerald-500 to-green-500 text-white shadow-lg shadow-emerald-500/25' : '' }}">
                    <svg class="mr-3 h-5 w-5 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 002 2v8a2 2 0 002 2z"/>
                    </svg>
                    Face Check-in
                    <div class="ml-auto w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></div>
                </a>
                @endcan
            </div>
        </nav>
    </div>

</div>