@extends('layouts.authenticated-unified')

@section('title', 'Riwayat Kehadiran')

@section('page-content')
<div class="p-6 lg:p-8">
    <x-layouts.base-page
        title="Riwayat Kehadiran Saya"
        subtitle="Lacak kehadiran harian dan jam kerja Anda"
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Kehadiran', 'url' => route('attendance.index')],
            ['label' => 'Riwayat']
        ]">
        <x-slot name="actions">
            <x-ui.button variant="outline" onclick="openFilterModal()">
                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter
            </x-ui.button>
            <x-ui.button onclick="exportAttendance()">
                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Ekspor
            </x-ui.button>
        </x-slot>
    </x-layouts.base-page>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 my-8">
        <x-ui.card variant="metric" title="Hari Hadir" value="--" subtitle="Periode ini" color="success" id="stat-present"><x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot></x-ui.card>
        <x-ui.card variant="metric" title="Keterlambatan" value="--" subtitle="Hari terlambat" color="warning" id="stat-late"><x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot></x-ui.card>
        <x-ui.card variant="metric" title="Total Jam" value="--" subtitle="Jam kerja" color="primary" id="stat-hours"><x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot></x-ui.card>
        <x-ui.card variant="metric" title="Tingkat Kehadiran" value="--" subtitle="Persentase" color="info" id="stat-rate"><x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></x-slot></x-ui.card>
    </div>

    <x-ui.card>
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <span>Catatan Kehadiran Saya</span>
                <x-ui.button variant="outline" size="sm" onclick="refreshAttendanceData()" title="Refresh">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </x-ui.button>
            </div>
        </x-slot>
        <div class="overflow-x-auto">
            <table id="attendanceTable" class="min-w-full">
                <thead>
                    <tr class="border-b border-border">
                        <th class="px-6 py-3 text-left text-sm font-medium text-muted-foreground">Tanggal</th>
                        @can('view_attendance_all')
                        <th class="px-6 py-3 text-left text-sm font-medium text-muted-foreground">Karyawan</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-muted-foreground">ID Karyawan</th>
                        @endcan
                        <th class="px-6 py-3 text-left text-sm font-medium text-muted-foreground">Check In</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-muted-foreground">Check Out</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-muted-foreground">Total Jam</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-muted-foreground">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-muted-foreground">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-ui.card>

    <!-- Modals -->
    <x-ui.modal id="filterModal" title="Filter Kehadiran">
        <form id="filterForm" class="space-y-6">
            <!-- Form content from original file -->
        </form>
    </x-ui.modal>
    <x-ui.modal id="detailsModal" title="Detail Kehadiran" size="3xl">
        <div id="attendance-details"></div>
    </x-ui.modal>
    <x-ui.modal id="manualCheckoutModal" title="Check-out Manual">
        <form id="manualCheckoutForm">
            <!-- Form content from original file -->
        </form>
    </x-ui.modal>
</div>
@endsection

@push('scripts')
{{-- Scripts from original file --}}
@endpush
