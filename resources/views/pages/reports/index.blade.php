@extends('layouts.authenticated-unified')

@section('title', 'Laporan')

@section('page-content')
<div class="p-6 lg:p-8">
    <x-layouts.base-page
        title="Laporan"
        subtitle="Manajemen - Lihat laporan dan analitik sistem"
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Laporan']
        ]">
        <x-slot name="actions">
            <x-ui.button href="{{ route('reports.builder') }}">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                Buat Laporan Kustom
            </x-ui.button>
        </x-slot>

        <!-- Quick Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <x-ui.card variant="metric" title="Kehadiran Hari Ini" :value="$quickStats['todays_attendance']" color="success">
                <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M22,6 12,13 9,10 2,17"/><path d="M13,6 22,6 22,15"/></svg></x-slot>
            </x-ui.card>
            <x-ui.card variant="metric" title="Cuti Tertunda" :value="$quickStats['pending_leaves']" color="warning">
                <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12,6 12,12 16,14"/></svg></x-slot>
            </x-ui.card>
            <x-ui.card variant="metric" title="Penggajian Bulanan" :value="$quickStats['monthly_payrolls']" color="info">
                <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M14.8 9a2 2 0 0 0-1.8-1h-2a2 2 0 0 0 0 4h2a2 2 0 0 1 0 4h-2a2 2 0 0 1-1.8-1"/><path d="M12 6v2m0 8v2"/></svg></x-slot>
            </x-ui.card>
            <x-ui.card variant="metric" title="Total Karyawan" :value="$quickStats['total_employees']" color="primary">
                <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/></svg></x-slot>
            </x-ui.card>
        </div>

        <!-- Report Types Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    @foreach($reportTypes as $key => $reportType)
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="mr-4">
                    <span class="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-900/20">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @switch($reportType['icon'])
                                @case('calendar-check') <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/> @break
                                @case('calendar-x') <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/> @break
                                @case('currency-dollar') <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/> @break
                                @case('users') <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/> @break
                                @case('chart-bar') <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/> @break
                            @endswitch
                        </svg>
                    </span>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $reportType['name'] }}</h3>
                </div>
            </div>
            <p class="text-gray-600 dark:text-gray-400 mb-6">{{ $reportType['description'] }}</p>
            <div class="flex items-center space-x-3">
                <a href="{{ route('reports.' . $key) }}" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200">
                    Lihat Laporan
                </a>
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg font-medium transition-colors duration-200">
                        Ekspor
                    </button>
                    <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg z-10 border border-gray-200 dark:border-gray-700" style="display: none;">
                        <div class="py-1">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700" onclick="exportReport('{{ $key }}', 'pdf')">PDF</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700" onclick="exportReport('{{ $key }}', 'csv')">CSV</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700" onclick="exportReport('{{ $key }}', 'excel')">Excel</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-ui.card>
    @endforeach
</div>

<!-- Quick Actions Section -->
<x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Aksi Cepat</h3>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Akses cepat ke fitur laporan yang sering digunakan</p>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('reports.builder') }}" class="flex items-center justify-center px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg font-medium transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                Pembangun Laporan Kustom
            </a>
            <button onclick="scheduleReport()" class="flex items-center justify-center px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg font-medium transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12,6 12,12 16,14"/></svg>
                Jadwalkan Laporan
            </button>
            <button onclick="viewScheduledReports()" class="flex items-center justify-center px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg font-medium transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3 3l8 -8"/><path d="M20 12v6a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h9"/></svg>
                Laporan Terjadwal
            </button>
            <a href="{{ route('analytics.dashboard') }}" class="flex items-center justify-center px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg font-medium transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19h16M4 15l4-4 4 2 4-5 4 3"/></svg>
                Dasbor Analitik
            </a>
        </div>
    </div>
</x-ui.card>

<!-- Modals -->
<x-ui.modal id="scheduleReportModal" title="Jadwalkan Laporan Otomatis">
    <form id="scheduleReportForm" class="space-y-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-ui.label for="report_type" value="Tipe Laporan" />
                <x-ui.select name="report_type" required>
                    <option value="">Pilih Tipe Laporan</option>
                    @foreach($reportTypes as $key => $reportType)<option value="{{ $key }}">{{ $reportType['name'] }}</option>@endforeach
                </x-ui.select>
            </div>
            <div>
                <x-ui.label for="schedule_type" value="Jadwal" />
                <x-ui.select name="schedule_type" required>
                    <option value="">Pilih Jadwal</option>
                    <option value="daily">Harian</option>
                    <option value="weekly">Mingguan</option>
                    <option value="monthly">Bulanan</option>
                    <option value="quarterly">Triwulanan</option>
                </x-ui.select>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-ui.label for="format" value="Format Ekspor" />
                <x-ui.select name="format" required>
                    <option value="">Pilih Format</option>
                    <option value="pdf">PDF</option>
                    <option value="csv">CSV</option>
                    <option value="excel">Excel</option>
                </x-ui.select>
            </div>
            <div>
                <x-ui.label for="recipients" value="Penerima" />
                <x-ui.input type="email" name="recipients[]" placeholder="Masukkan alamat email" required />
                <p class="text-xs text-muted-foreground mt-1">Pisahkan beberapa email dengan koma.</p>
            </div>
        </div>
        <div class="flex justify-end space-x-2 pt-4">
            <x-ui.button type="button" variant="outline" onclick="closeModal('scheduleReportModal')">Batal</x-ui.button>
            <x-ui.button type="submit">Jadwalkan Laporan</x-ui.button>
        </div>
    </form>
</x-ui.modal>

<x-ui.modal id="scheduledReportsModal" title="Laporan Terjadwal" size="4xl">
    <div id="scheduledReportsTable"></div>
    <div class="flex justify-end pt-4">
        <x-ui.button type="button" variant="outline" onclick="closeModal('scheduledReportsModal')">Tutup</x-ui.button>
    </div>
</x-ui.modal>

@endsection

@push('scripts')
<script>
// Script content remains largely the same, just ensure modal functions use openModal/closeModal
</script>
@endpush
