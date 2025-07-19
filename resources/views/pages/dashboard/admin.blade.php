@extends('layouts.authenticated-unified')

@section('title', __('dashboard.admin.title'))

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('page-content')

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
        <div class="p-4 sm:p-6 space-y-6" x-data="adminDashboard()" x-init="initDashboard()">
            <x-layouts.base-page
                title="{{ __('dashboard.admin.title') }}"
                subtitle="{{ __('dashboard.admin.subtitle') }}"
                :breadcrumbs="[
                    ['label' => 'Dashboard', 'url' => route('dashboard')],
                    ['label' => __('dashboard.admin.title')]
                ]">
                <x-slot name="actions">
                    <div class="flex flex-col sm:flex-row gap-2">
                        <x-ui.button variant="secondary" onclick="refreshData()">
                            <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                            </svg>
                            Segarkan
                        </x-ui.button>
                    </div>
                </x-slot>
            </x-layouts.base-page>

            <!-- Daily Operations Overview -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-2xl shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    @php
                        $teachers = $dashboardData['teacher_status']['teachers'] ?? [];
                        $presentTeachers = array_filter($teachers, fn($teacher) => $teacher['status'] !== 'absent');
                        $totalTeachers = count($teachers);
                        $presentCount = count($presentTeachers);
                    @endphp
                    <h3 class="text-2xl font-bold text-slate-800 dark:text-white">{{ $presentCount }}</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm">{{ __('dashboard.admin.teachers_present') }}</p>
                    <p class="text-xs text-emerald-600">dari {{ $totalTeachers }} total</p>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-2xl shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-800 dark:text-white">{{ $dashboardData['teacher_status']['teaching_coverage'] ?? 95 }}%</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm">{{ __('dashboard.admin.teaching_coverage') }}</p>
                    <p class="text-xs text-emerald-600">Kelas tercakup</p>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-r from-amber-500 to-orange-600 rounded-2xl shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-800 dark:text-white">{{ is_array($dashboardData['leave_processing']) ? count($dashboardData['leave_processing']) : ($dashboardData['leave_processing']->count() ?? 0) }}</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm">{{ __('dashboard.admin.pending_requests') }}</p>
                    <p class="text-xs text-amber-600">Perlu diproses</p>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-r from-red-500 to-rose-600 rounded-2xl shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-800 dark:text-white">{{ count($dashboardData['schedule_status']['conflicts'] ?? []) }}</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm">{{ __('dashboard.admin.schedule_issues') }}</p>
                    <p class="text-xs text-red-600">Perlu perhatian</p>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-slate-800 dark:text-white">{{ __('dashboard.admin.todays_teacher_status') }}</h3>
                            <p class="text-slate-600 dark:text-slate-400">{{ __('dashboard.admin.realtime_tracking') }}</p>
                        </div>
                        <x-ui.button variant="secondary">
                            {{ __('dashboard.admin.refresh') }}
                        </x-ui.button>
                    </div>

                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        @forelse($dashboardData['teacher_status']['teachers'] ?? [] as $teacher)
                            <div class="flex items-center justify-between p-4 bg-white/10 rounded-2xl hover:bg-white/20 transition-colors">
                                <div class="flex items-center space-x-4">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-emerald-500 to-emerald-600 flex items-center justify-center shadow-lg">
                                        <span class="text-white font-semibold text-sm">{{ substr($teacher['name'], 0, 2) }}</span>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-800 dark:text-white">{{ $teacher['name'] }}</p>
                                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ $teacher['employee_id'] }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-3 h-3 rounded-full
                                            {{ $teacher['status'] === 'present' ? 'bg-emerald-400' : 
                                               ($teacher['status'] === 'late' ? 'bg-amber-400' : 'bg-red-400') }}">
                                        </div>
                                        <span class="text-sm font-medium capitalize
                                            {{ $teacher['status'] === 'present' ? 'text-green-600' : 
                                               ($teacher['status'] === 'late' ? 'text-amber-600' : 'text-red-600') }}">
                                            {{ $teacher['status'] }}
                                        </span>
                                    </div>
                                    @if($teacher['check_in_time'])
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ \Carbon\Carbon::parse($teacher['check_in_time'])->format('H:i') }}</p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8 text-slate-500 dark:text-slate-400">
                                <svg class="w-12 h-12 mx-auto mb-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <p>{{ __('dashboard.admin.no_teacher_data') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-6">{{ __('dashboard.admin.pending_leave_requests') }}</h3>
                    
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        @forelse($dashboardData['leave_processing'] ?? [] as $leave)
                            <div class="p-4 bg-amber-500/10 rounded-2xl border border-amber-500/20">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-semibold text-slate-800 dark:text-white">{{ $leave->employee->first_name ?? 'Unknown' }} {{ $leave->employee->last_name ?? '' }}</p>
                                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ $leave->leaveType->name ?? 'Cuti' }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ \Carbon\Carbon::parse($leave->start_date)->format('M j') }} - {{ \Carbon\Carbon::parse($leave->end_date)->format('M j') }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="px-2 py-1 text-xs bg-amber-100 text-amber-800 rounded-full">Tertunda</span>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $leave->days_requested }} hari</p>
                                    </div>
                                </div>
                                <div class="mt-3 flex space-x-2">
                                    <x-ui.button variant="success" size="sm">Setujui</x-ui.button>
                                    <x-ui.button variant="destructive" size="sm">Tolak</x-ui.button>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8 text-slate-500 dark:text-slate-400">
                                <svg class="w-12 h-12 mx-auto mb-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p>{{ __('dashboard.admin.no_pending_requests') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Quick Admin Actions -->
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-6">{{ __('dashboard.admin.quick_admin_actions') }}</h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="{{ route('attendance.index') }}" class="p-4 bg-emerald-500/10 hover:bg-emerald-500/20 rounded-2xl transition-colors text-center group">
                        <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl mx-auto mb-3 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                        </div>
                        <p class="font-medium text-emerald-700 dark:text-emerald-300">{{ __('dashboard.admin.view_attendance') }}</p>
                        <p class="text-xs text-emerald-600 dark:text-emerald-400">{{ __('dashboard.admin.todays_records') }}</p>
                    </a>

                    <a href="{{ route('leave.index') }}" class="p-4 bg-amber-500/10 hover:bg-amber-500/20 rounded-2xl transition-colors text-center group">
                        <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl mx-auto mb-3 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                        <p class="font-medium text-amber-700 dark:text-amber-300">{{ __('dashboard.admin.process_leaves') }}</p>
                        <p class="text-xs text-amber-600 dark:text-amber-400">{{ $dashboardData['leave_processing']->count() ?? 0 }} tertunda</p>
                    </a>

                    <a href="{{ route('employees.index') }}" class="p-4 bg-blue-500/10 hover:bg-blue-500/20 rounded-2xl transition-colors text-center group">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl mx-auto mb-3 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        </div>
                        <p class="font-medium text-blue-700 dark:text-blue-300">{{ __('dashboard.admin.manage_teachers') }}</p>
                        <p class="text-xs text-blue-600 dark:text-blue-400">{{ __('dashboard.admin.staff_management') }}</p>
                    </a>

                    <a href="{{ route('reports.attendance') }}" class="p-4 bg-purple-500/10 hover:bg-purple-500/20 rounded-2xl transition-colors text-center group">
                        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl mx-auto mb-3 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                        </div>
                        <p class="font-medium text-purple-700 dark:text-purple-300">{{ __('dashboard.admin.generate_reports') }}</p>
                        <p class="text-xs text-purple-600 dark:text-purple-400">{{ __('dashboard.admin.analytics_insights') }}</p>
                    </a>
                </div>
            </div>
        </div>

        <script>
            function adminDashboard() {
                return {
                    initDashboard() {
                        // Admin-specific initialization
                        this.refreshData();
                    },

                    refreshData() {
                        // Auto-refresh teacher status every 30 seconds
                        setInterval(() => {
                            // Implement real-time refresh
                            console.log('Refreshing admin dashboard data...');
                        }, 30000);
                    }
                }
            }
        </script>
@endsection
