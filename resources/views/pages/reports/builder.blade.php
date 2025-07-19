@extends('layouts.authenticated-unified')

@section('title', 'Pembuat Laporan')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Pembuat Laporan Interaktif"
            subtitle="Buat laporan kustom dengan konfigurasi yang fleksibel"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Laporan', 'url' => route('reports.index')],
                ['label' => 'Pembuat Laporan']
            ]">
            <x-slot name="actions">
                <x-ui.button variant="primary" id="generateReport">
                    <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19a9 9 0 0 1 9 0a9 9 0 0 1 9 0"/><path d="M3 6a9 9 0 0 1 9 0a9 9 0 0 1 9 0"/><line x1="3" y1="6" x2="3" y2="19"/><line x1="12" y1="6" x2="12" y2="19"/><line x1="21" y1="6" x2="21" y2="19"/></svg>
                    Hasilkan Laporan
                </x-ui.button>
            </x-slot>
        </x-layouts.base-page>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Report Configuration -->
            <div class="lg:col-span-2 space-y-6">
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Konfigurasi Laporan</h3>
                    <form id="reportConfigForm">
                        <!-- Report Type -->
                        <div class="mb-3">
                            <x-ui.label for="reportType" value="Tipe Laporan" required class="text-slate-700 dark:text-slate-300" />
                            <x-ui.select id="reportType" name="report_type" required 
                                       class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                                <option value="">Pilih tipe laporan</option>
                                <option value="attendance">Laporan Absensi</option>
                                <option value="leave">Laporan Cuti</option>
                                <option value="payroll">Laporan Penggajian</option>
                                <option value="employee">Laporan Karyawan</option>
                                <option value="summary">Laporan Ringkasan</option>
                            </x-ui.select>
                        </div>

                        <!-- Date Range -->
                        <div class="mb-3">
                            <x-ui.label value="Rentang Tanggal" required class="text-slate-700 dark:text-slate-300" />
                            <div class="flex rounded-md shadow-sm mb-2">
                                <x-ui.input type="date" id="startDate" name="start_date" required 
                                           class="block w-full px-3 py-2 border border-white/40 rounded-l-md shadow-sm focus:outline-none focus:ring-blue-500/50 focus:border-blue-500/50 sm:text-sm bg-white/30 backdrop-blur-sm text-slate-800 dark:text-white" />
                                <span class="flex items-center px-3 text-sm text-slate-600 dark:text-slate-400">hingga</span>
                                <x-ui.input type="date" id="endDate" name="end_date" required 
                                           class="block w-full px-3 py-2 border border-white/40 rounded-r-md shadow-sm focus:outline-none focus:ring-blue-500/50 focus:border-blue-500/50 sm:text-sm bg-white/30 backdrop-blur-sm text-slate-800 dark:text-white" />
                            </div>
                            <div class="inline-flex rounded-md shadow-sm w-full" role="group">
                                <x-ui.button type="button" variant="secondary" class="flex-1 rounded-r-none" onclick="setDateRange('today')">Hari Ini</x-ui.button>
                                <x-ui.button type="button" variant="secondary" class="flex-1 rounded-none" onclick="setDateRange('this_week')">Minggu Ini</x-ui.button>
                                <x-ui.button type="button" variant="secondary" class="flex-1 rounded-none" onclick="setDateRange('this_month')">Bulan Ini</x-ui.button>
                                <x-ui.button type="button" variant="secondary" class="flex-1 rounded-l-none" onclick="setDateRange('last_month')">Bulan Lalu</x-ui.button>
                            </div>
                        </div>

                        <!-- Grouping -->
                        <div class="mb-3">
                            <x-ui.label for="groupBy" value="Kelompokkan Berdasarkan" class="text-slate-700 dark:text-slate-300" />
                            <x-ui.select id="groupBy" name="group_by" 
                                       class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                                <option value="">Tanpa pengelompokan</option>
                                <option value="employee">Karyawan</option>
                                <option value="department">Departemen</option>
                                <option value="location">Lokasi</option>
                                <option value="date">Tanggal</option>
                                <option value="week">Minggu</option>
                                <option value="month">Bulan</option>
                            </x-ui.select>
                        </div>

                        <!-- Export Format -->
                        <div class="mb-3">
                            <x-ui.label value="Format Ekspor" class="text-slate-700 dark:text-slate-300" />
                            <div class="flex flex-wrap gap-4">
                                <label class="flex items-center">
                                    <input type="radio" name="export_format" value="preview" class="form-radio h-4 w-4 text-blue-600" checked>
                                    <span class="ml-2 text-sm text-slate-800 dark:text-white">Pratinjau</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="export_format" value="pdf" class="form-radio h-4 w-4 text-blue-600">
                                    <span class="ml-2 text-sm text-slate-800 dark:text-white">PDF</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="export_format" value="csv" class="form-radio h-4 w-4 text-blue-600">
                                    <span class="ml-2 text-sm text-slate-800 dark:text-white">CSV</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="export_format" value="excel" class="form-radio h-4 w-4 text-blue-600">
                                    <span class="ml-2 text-sm text-slate-800 dark:text-white">Excel</span>
                                </label>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Filters -->
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Filter</h3>
                    <div id="filterContainer">
                        <!-- Dynamic filters will be loaded here based on report type -->
                    </div>
                </div>

                <!-- Columns Selection -->
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out" id="columnsCard" style="display: none;">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-slate-800 dark:text-white">Pilih Kolom</h3>
                        <div class="flex space-x-2">
                            <x-ui.button type="button" variant="secondary" size="sm" onclick="selectAllColumns()">Pilih Semua</x-ui.button>
                            <x-ui.button type="button" variant="secondary" size="sm" onclick="deselectAllColumns()">Batalkan Pilihan Semua</x-ui.button>
                        </div>
                    </div>
                    <div id="columnsContainer">
                        <!-- Dynamic columns will be loaded here based on report type -->
                    </div>
                </div>
            </div>

            <!-- Report Preview -->
            <div class="lg:col-span-1 group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white">Pratinjau Laporan</h3>
                    <div class="hidden" id="previewActions">
                        <div class="relative">
                            <x-ui.button variant="secondary" type="button" onclick="toggleExportDropdown()">
                                <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/></svg>
                                Ekspor
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </x-ui.button>
                            <div id="export-dropdown" class="hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white/80 backdrop-blur-sm ring-1 ring-white/30 focus:outline-none z-50">
                                <div class="py-1" role="menu">
                                    <a class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-white/10" href="#" onclick="exportReport('pdf')">Ekspor sebagai PDF</a>
                                    <a class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-white/10" href="#" onclick="exportReport('csv')">Ekspor sebagai CSV</a>
                                    <a class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-white/10" href="#" onclick="exportReport('excel')">Ekspor sebagai Excel</a>
                                </div>
                            </div>
                        </div>
                        <x-ui.button variant="secondary" onclick="scheduleReport()">
                            <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"/><path d="M16 3l0 4"/><path d="M8 3l0 4"/><path d="M4 11l16 0"/><path d="M11 15l1 0"/><path d="M12 15l0 3"/></svg>
                            Jadwalkan
                        </x-ui.button>
                    </div>
                </div>
                <div class="bg-white/5 backdrop-blur-sm rounded-lg">
                    <div id="reportPreviewContent">
                        <div class="text-center text-slate-600 dark:text-slate-400 py-5">
                            <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><line x1="9" y1="12" x2="9.01" y2="12"/><line x1="13" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="9.01" y2="16"/><line x1="13" y1="16" x2="15" y2="16"/></svg>
                            <p>Konfigurasi pengaturan laporan Anda dan klik "Hasilkan Laporan" untuk pratinjau</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Statistics -->
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mt-4" id="reportStats" style="display: none;">
                <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Statistik Laporan</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4" id="statsContainer">
                    <!-- Dynamic statistics will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Report Modal -->
<x-ui.modal id="scheduleModal" title="Jadwalkan Laporan" size="lg">
    <form id="scheduleForm">
        @csrf
        <div class="space-y-4">
            <div>
                <x-ui.label for="schedule_type" value="Tipe Jadwal" class="text-slate-700 dark:text-slate-300" />
                <x-ui.select name="schedule_type" required class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                    <option value="daily">Harian</option>
                    <option value="weekly">Mingguan</option>
                    <option value="monthly">Bulanan</option>
                    <option value="quarterly">Triwulanan</option>
                </x-ui.select>
            </div>
            <div>
                <x-ui.label for="recipients" value="Penerima (Alamat Email)" class="text-slate-700 dark:text-slate-300" />
                <textarea name="recipients" rows="3" placeholder="email1@example.com\nemail2@example.com" required class="w-full px-3 py-2 border border-white/40 rounded-md shadow-sm focus:outline-none focus:ring-blue-500/50 focus:border-blue-500/50 sm:text-sm bg-white/30 backdrop-blur-sm text-slate-800 dark:text-white"></textarea>
                <small class="text-slate-600 dark:text-slate-400">Masukkan satu alamat email per baris</small>
            </div>
            <div>
                <x-ui.label for="format" value="Format Laporan" class="text-slate-700 dark:text-slate-300" />
                <x-ui.select name="format" required class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                    <option value="pdf">PDF</option>
                    <option value="csv">CSV</option>
                    <option value="excel">Excel</option>
                </x-ui.select>
            </div>
        </div>
    </form>
    <x-slot name="footer">
        <x-ui.button type="button" variant="secondary" onclick="closeModal('scheduleModal')">Batal</x-ui.button>
        <x-ui.button type="submit" variant="primary" form="scheduleForm">
            Jadwalkan Laporan
        </x-ui.button>
    </x-slot>
</x-ui.modal>

@push('scripts')
<script type="module">
const reportConfig = {
    attendance: {
        filters: ['employees', 'status', 'locations', 'departments'],
        columns: ['date', 'employee_name', 'employee_id', 'check_in', 'check_out', 'total_hours', 'status', 'late_minutes', 'notes'],
        defaultColumns: ['date', 'employee_name', 'check_in', 'check_out', 'total_hours', 'status']
    },
    leave: {
        filters: ['employees', 'leave_types', 'status', 'approved_by'],
        columns: ['employee_name', 'employee_id', 'leave_type', 'start_date', 'end_date', 'days_requested', 'status', 'reason', 'approved_by', 'approval_date'],
        defaultColumns: ['employee_name', 'leave_type', 'start_date', 'end_date', 'days_requested', 'status']
    },
    payroll: {
        filters: ['employees', 'status', 'salary_range'],
        columns: ['employee_name', 'employee_id', 'period', 'gross_salary', 'deductions', 'bonuses', 'net_salary', 'worked_hours', 'overtime_hours', 'status'],
        defaultColumns: ['employee_name', 'period', 'gross_salary', 'net_salary', 'status']
    },
    employee: {
        filters: ['employee_type', 'is_active', 'locations', 'hire_date'],
        columns: ['employee_id', 'full_name', 'email', 'phone', 'employee_type', 'hire_date', 'salary_type', 'salary_amount', 'location', 'status'],
        defaultColumns: ['employee_id', 'full_name', 'email', 'employee_type', 'location', 'status']
    },
    summary: {
        filters: ['grouping', 'metrics'],
        columns: [],
        defaultColumns: []
    }
};

let currentReportType = null;
let reportData = null;

function toggleExportDropdown() {
    const dropdown = document.getElementById('export-dropdown');
    dropdown.classList.toggle('hidden');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('export-dropdown');
    const button = event.target.closest('button');
    if (!button || !button.onclick || button.onclick.toString().indexOf('toggleExportDropdown') === -1) {
        dropdown.classList.add('hidden');
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Initialize date inputs with current month
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    
    document.getElementById('startDate').value = firstDay.toISOString().split('T')[0];
    document.getElementById('endDate').value = lastDay.toISOString().split('T')[0];
    
    // Report type change handler
    document.getElementById('reportType').addEventListener('change', function() {
        currentReportType = this.value;
        if (currentReportType) {
            loadFilters(currentReportType);
            loadColumns(currentReportType);
            document.getElementById('columnsCard').style.display = 'block';
        } else {
            document.getElementById('filterContainer').innerHTML = '';
            document.getElementById('columnsCard').style.display = 'none';
        }
    });
    
    // Generate report button handler
    document.getElementById('generateReport').addEventListener('click', generateReport);
    
    // Schedule form handler
    document.getElementById('scheduleForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitSchedule();
    });
});

function loadFilters(reportType) {
    const filterContainer = document.getElementById('filterContainer');
    filterContainer.innerHTML = '';
    
    const filters = reportConfig[reportType].filters;
    const filterOptions = @json($filterOptions);
    
    filters.forEach(filter => {
        let filterHtml = '';
        
        switch(filter) {
            case 'employees':
                filterHtml = createMultiSelectFilter('Karyawan', 'employee_ids[]', 
                    filterOptions.employees.map(e => ({value: e.id, label: `${e.first_name} ${e.last_name} (${e.employee_id})`})));
                break;
            case 'status':
                if (reportType === 'attendance') {
                    filterHtml = createMultiSelectFilter('Status', 'status[]', 
                        filterOptions.attendance_statuses.map(s => ({value: s, label: s.charAt(0).toUpperCase() + s.slice(1)})));
                } else if (reportType === 'leave') {
                    filterHtml = createMultiSelectFilter('Status', 'status[]', 
                        filterOptions.leave_statuses.map(s => ({value: s, label: s.charAt(0).toUpperCase() + s.slice(1)})));
                } else if (reportType === 'payroll') {
                    filterHtml = createMultiSelectFilter('Status', 'status[]', 
                        filterOptions.payroll_statuses.map(s => ({value: s, label: s.charAt(0).toUpperCase() + s.slice(1)})));
                }
                break;
            case 'locations':
                filterHtml = createMultiSelectFilter('Lokasi', 'location_ids[]', 
                    filterOptions.locations.map(l => ({value: l.id, label: l.name})));
                break;
            case 'leave_types':
                filterHtml = createMultiSelectFilter('Tipe Cuti', 'leave_type_ids[]', 
                    filterOptions.leave_types.map(lt => ({value: lt.id, label: lt.name})));
                break;
            case 'employee_type':
                filterHtml = createMultiSelectFilter('Tipe Karyawan', 'employee_types[]', 
                    filterOptions.employee_types.map(et => ({value: et, label: et.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')})));
                break;
            case 'is_active':
                filterHtml = `
                    <div class="mb-3">
                        <x-ui.label value="Status" class="text-slate-700 dark:text-slate-300" />
                        <x-ui.select name="is_active" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                            <option value="">Semua</option>
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </x-ui.select>
                    </div>
                `;
                break;
            case 'salary_range':
                filterHtml = `
                    <div class="mb-3">
                        <x-ui.label value="Rentang Gaji" class="text-slate-700 dark:text-slate-300" />
                        <div class="grid grid-cols-2 gap-4">
                            <x-ui.input type="number" name="min_salary" placeholder="Min" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                            <x-ui.input type="number" name="max_salary" placeholder="Max" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                        </div>
                    </div>
                `;
                break;
            case 'hire_date':
                filterHtml = `
                    <div class="mb-3">
                        <x-ui.label value="Rentang Tanggal Rekrutmen" class="text-slate-700 dark:text-slate-300" />
                        <div class="grid grid-cols-2 gap-4">
                            <x-ui.input type="date" name="hire_date_start" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                            <x-ui.input type="date" name="hire_date_end" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                        </div>
                    </div>
                `;
                break;
            case 'grouping':
                filterHtml = `
                    <div class="mb-3">
                        <x-ui.label value="Periode Pengelompokan" class="text-slate-700 dark:text-slate-300" />
                        <x-ui.select name="grouping" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                            <option value="daily">Harian</option>
                            <option value="weekly">Mingguan</option>
                            <option value="monthly" selected>Bulanan</option>
                        </x-ui.select>
                    </div>
                `;
                break;
            case 'metrics':
                filterHtml = `
                    <div class="mb-3">
                        <x-ui.label value="Sertakan Metrik" class="text-slate-700 dark:text-slate-300" />
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="metrics[]" value="attendance" class="form-checkbox h-4 w-4 text-blue-600 rounded" checked>
                                <span class="ml-2 text-sm text-slate-800 dark:text-white">Absensi</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="metrics[]" value="leave" class="form-checkbox h-4 w-4 text-blue-600 rounded" checked>
                                <span class="ml-2 text-sm text-slate-800 dark:text-white">Cuti</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="metrics[]" value="payroll" class="form-checkbox h-4 w-4 text-blue-600 rounded" checked>
                                <span class="ml-2 text-sm text-slate-800 dark:text-white">Penggajian</span>
                            </label>
                        </div>
                    </div>
                `;
                break;
        }
        
        filterContainer.innerHTML += filterHtml;
    });
    
    // Initialize select2 for multi-select filters
    setTimeout(() => {
        filterContainer.querySelectorAll('.multi-select').forEach(select => {
            $(select).select2({
                theme: 'default',
                width: '100%',
                placeholder: 'Pilih opsi...'
            });
        });
    }, 100);
}

function createMultiSelectFilter(label, name, options) {
    const optionsHtml = options.map(opt => `<option value="${opt.value}">${opt.label}</option>`).join('');
    return `
        <div class="mb-3">
            <x-ui.label value="${label}" class="text-slate-700 dark:text-slate-300" />
            <x-ui.select name="${name}" multiple class="multi-select bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                ${optionsHtml}
            </x-ui.select>
        </div>
    `;
}

function loadColumns(reportType) {
    const columnsContainer = document.getElementById('columnsContainer');
    columnsContainer.innerHTML = '<div class="columns-list space-y-2">';
    
    const columns = reportConfig[reportType].columns;
    const defaultColumns = reportConfig[reportType].defaultColumns;
    
    columns.forEach(column => {
        const isChecked = defaultColumns.includes(column);
        const label = column.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
        
        columnsContainer.innerHTML += `
            <label class="flex items-center">
                <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 rounded" name="columns[]" value="${column}" ${isChecked ? 'checked' : ''}>
                <span class="ml-2 text-sm text-slate-800 dark:text-white">${label}</span>
            </label>
        `;
    });
    
    columnsContainer.innerHTML += '</div>';
}

function selectAllColumns() {
    document.querySelectorAll('input[name="columns[]"]').forEach(checkbox => {
        checkbox.checked = true;
    });
}

function deselectAllColumns() {
    document.querySelectorAll('input[name="columns[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
}

function setDateRange(range) {
    const today = new Date();
    let startDate, endDate;
    
    switch(range) {
        case 'today':
            startDate = endDate = today;
            break;
        case 'this_week':
            startDate = new Date(today.setDate(today.getDate() - today.getDay()));
            endDate = new Date(today.setDate(today.getDate() - today.getDay() + 6));
            break;
        case 'this_month':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            break;
        case 'last_month':
            startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            endDate = new Date(today.getFullYear(), today.getMonth(), 0);
            break;
    }
    
    document.getElementById('startDate').value = startDate.toISOString().split('T')[0];
    document.getElementById('endDate').value = endDate.toISOString().split('T')[0];
}

function generateReport() {
    const reportType = document.getElementById('reportType').value;
    if (!reportType) {
        alert('Mohon pilih tipe laporan');
        return;
    }
    
    // Collect form data
    const formData = new FormData(document.getElementById('reportConfigForm'));
    
    // Add date range
    formData.append('date_range[start]', document.getElementById('startDate').value);
    formData.append('date_range[end]', document.getElementById('endDate').value);
    
    // Add filters
    const filters = {};
    document.querySelectorAll('#filterContainer input, #filterContainer select').forEach(input => {
        if (input.type === 'checkbox' && input.checked) {
            const name = input.name.replace('[]', '');
            if (!filters[name]) filters[name] = [];
            filters[name].push(input.value);
        } else if (input.type !== 'checkbox' && input.value) {
            if (input.name.includes('[]')) {
                const name = input.name.replace('[]', '');
                filters[name] = $(input).val(); // For select2 multi-select
            } else {
                filters[input.name] = input.value;
            }
        }
    });
    
    Object.keys(filters).forEach(key => {
        if (Array.isArray(filters[key])) {
            filters[key].forEach(value => {
                formData.append(`filters[${key}][]`, value);
            });
        } else {
            formData.append(`filters[${key}]`, filters[key]);
        }
    });
    
    // Add columns
    const columns = [];
    document.querySelectorAll('input[name="columns[]"]:checked').forEach(checkbox => {
        columns.push(checkbox.value);
    });
    columns.forEach(col => formData.append('columns[]', col));
    
    // Add grouping
    formData.append('grouping', document.getElementById('groupBy').value);
    
    // Show loading state
    const previewContainer = document.getElementById('reportPreviewContent');
    previewContainer.innerHTML = `
        <div class="text-center py-5">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-4 text-slate-600 dark:text-slate-400">Menghasilkan laporan...</p>
        </div>
    `;
    
    // Send request
    fetch('{{ route("reports.generate-custom") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            reportData = data;
            displayReport(data);
            displayStats(data);
            document.getElementById('previewActions').style.display = 'block';
            document.getElementById('reportStats').style.display = 'block';
        } else {
            throw new Error(data.error || 'Gagal menghasilkan laporan');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        previewContainer.innerHTML = `
            <div class="bg-red-500/20 border border-red-500/30 text-red-800 dark:text-red-200 px-4 py-3 rounded-lg">
                <h4 class="font-medium text-base">Error menghasilkan laporan</h4>
                <div class="text-slate-600 dark:text-slate-400">${error.message}</div>
            </div>
        `;
    });
}

function displayReport(data) {
    const reportPreviewContent = document.getElementById('reportPreviewContent');
    const reportType = data.report_config.type;
    
    if (!data.data.records || data.data.records.length === 0) {
        reportPreviewContent.innerHTML = `
            <div class="bg-blue-500/20 border border-blue-500/30 text-blue-800 dark:text-blue-200 px-4 py-3 rounded-lg">
                <h4 class="font-medium text-base">Tidak ada data ditemukan</h4>
                <div class="text-slate-600 dark:text-slate-400">Tidak ada catatan yang cocok dengan kriteria yang Anda pilih.</div>
            </div>
        `;
        return;
    }
    
    // Build table HTML
    let tableHtml = `
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/20">
                <thead class="bg-white/10 backdrop-blur-sm">
                    <tr>
    `;
    
    // Add table headers
    const columns = data.report_config.columns || Object.keys(data.data.records[0]);
    columns.forEach(col => {
        const label = col.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
        tableHtml += `<th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">${label}</th>`;
    });
    
    tableHtml += `
                    </tr>
                </thead>
                <tbody class="bg-white/5 backdrop-blur-sm divide-y divide-white/10">
    `;
    
    // Add table rows
    data.data.records.forEach(record => {
        tableHtml += '<tr class="hover:bg-white/5 transition-colors">';
        columns.forEach(col => {
            let value = getNestedValue(record, col);
            
            // Format specific columns
            if (col.includes('date') && value) {
                value = new Date(value).toLocaleDateString();
            } else if ((col.includes('salary') || col.includes('amount')) && value) {
                value = '$' + parseFloat(value).toFixed(2);
            } else if (col === 'status') {
                const statusClass = {
                    'present': 'from-green-500 to-emerald-600',
                    'approved': 'from-green-500 to-emerald-600',
                    'paid': 'from-green-500 to-emerald-600',
                    'active': 'from-green-500 to-emerald-600',
                    'late': 'from-amber-500 to-orange-600',
                    'pending': 'from-amber-500 to-orange-600',
                    'absent': 'from-red-500 to-rose-600',
                    'rejected': 'from-red-500 to-rose-600',
                    'cancelled': 'from-red-500 to-rose-600',
                    'inactive': 'from-red-500 to-rose-600'
                }[value] || 'from-gray-500 to-slate-600';
                value = `<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white bg-gradient-to-r ${statusClass} shadow-lg">${value}</span>`;
            }
            
            tableHtml += `<td class="px-6 py-4 text-sm text-slate-800 dark:text-white">${value || '-'}</td>`;
        });
        tableHtml += '</tr>';
    });
    
    tableHtml += `
                </tbody>
            </table>
        </div>
    `;
    
    reportPreviewContent.innerHTML = tableHtml;
}

function getNestedValue(obj, path) {
    const keys = path.split('.');
    let value = obj;
    
    for (const key of keys) {
        if (value && typeof value === 'object' && key in value) {
            value = value[key];
        } else {
            // Try alternative naming conventions
            if (key === 'employee_name' && value.employee) {
                value = value.employee.full_name || `${value.employee.first_name} ${value.employee.last_name}`;
            } else if (key === 'employee_id' && value.employee) {
                value = value.employee.employee_id;
            } else {
                return null;
            }
        }
    }
    
    return value;
}

function displayStats(data) {
    const statsContainer = document.getElementById('statsContainer');
    statsContainer.innerHTML = '';
    
    if (data.data.stats) {
        Object.entries(data.data.stats).forEach(([key, value]) => {
            const label = key.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
            let displayValue = value;
            
            if (key.includes('amount') || key.includes('salary')) {
                displayValue = '$' + parseFloat(value).toFixed(2);
            } else if (key.includes('rate') || key.includes('percentage')) {
                displayValue = parseFloat(value).toFixed(1) + '%';
            } else if (key.includes('hours')) {
                displayValue = parseFloat(value).toFixed(1) + ' jam';
            }
            
            statsContainer.innerHTML += `
                <div class="col-span-1">
                    <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-4 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                        <div class="text-2xl font-bold text-slate-800 dark:text-white">${displayValue}</div>
                        <div class="text-sm text-slate-600 dark:text-slate-400">${label}</div>
                    </div>
                </div>
            `;
        });
    }
}

function exportReport(format) {
    if (!reportData) {
        alert('Mohon hasilkan laporan terlebih dahulu');
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `{{ url('/reports/export') }}/${reportData.report_config.type}`;
    
    // Add CSRF token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
    form.appendChild(csrfInput);
    
    // Add format
    const formatInput = document.createElement('input');
    formatInput.type = 'hidden';
    formatInput.name = 'format';
    formatInput.value = format;
    form.appendChild(formatInput);
    
    // Add filters and date range
    const config = reportData.report_config;
    
    // Date range
    const startDateInput = document.createElement('input');
    startDateInput.type = 'hidden';
    startDateInput.name = 'start_date';
    startDateInput.value = config.date_range.start;
    form.appendChild(startDateInput);
    
    const endDateInput = document.createElement('input');
    endDateInput.type = 'hidden';
    endDateInput.name = 'end_date';
    endDateInput.value = config.date_range.end;
    form.appendChild(endDateInput);
    
    // Filters
    if (config.filters) {
        Object.entries(config.filters).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value.forEach(v => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `${key}[]`;
                    input.value = v;
                    form.appendChild(input);
                });
            } else {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }
        });
    }
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function scheduleReport() {
    if (!reportData) {
        alert('Mohon hasilkan laporan terlebih dahulu');
        return;
    }
    
    openModal('scheduleModal');
}

function submitSchedule() {
    const formData = new FormData(document.getElementById('scheduleForm'));
    formData.append('report_type', reportData.report_config.type);
    
    // Add report configuration
    formData.append('filters', JSON.stringify(reportData.report_config.filters));
    
    // Parse recipients
    const recipientsText = formData.get('recipients');
    const recipients = recipientsText.split('\n').map(email => email.trim()).filter(email => email);
    formData.delete('recipients');
    recipients.forEach(email => formData.append('recipients[]', email));
    
    fetch('{{ route("reports.schedule") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal('scheduleModal');
            alert('Laporan berhasil dijadwalkan!');
        } else {
            throw new Error(data.message || 'Gagal menjadwalkan laporan');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error menjadwalkan laporan: ' + error.message);
    });
}
</script>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endpush
