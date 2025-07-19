@extends('layouts.authenticated-unified')

@section('title', __('employees.title'))

@section('page-content')
<!-- Page Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ __('employees.title') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('employees.subtitle') }}</p>
        </div>
        <div class="flex items-center space-x-3">
            @can('create_employees')
            <x-ui.button variant="primary" onclick="window.location.href='{{ route('employees.create') }}'"
                class="bg-blue-600 hover:bg-blue-700 text-white">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('employees.add_employee') }}
            </x-ui.button>
            @endcan
            
            @can('export_employees_data')
            <x-ui.button variant="secondary" onclick="exportEmployees()"
                class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Ekspor
            </x-ui.button>
            @endcan
        </div>
    </div>
</div>

<!-- Statistics Cards Section -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Karyawan -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-blue-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <span class="text-sm text-blue-600">Karyawan</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">{{ $statistics['total'] ?? 0 }}</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Total Karyawan</p>
    </x-ui.card>

    <!-- Aktif Hari Ini -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-green-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="text-sm text-green-600">Aktif</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">{{ $statistics['active_today'] ?? 0 }}</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Aktif Hari Ini</p>
    </x-ui.card>

    <!-- Pegawai Tetap -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-cyan-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2z"/>
                </svg>
            </div>
            <span class="text-sm text-cyan-600">Tetap</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">{{ $statistics['permanent'] ?? 0 }}</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Pegawai Tetap</p>
    </x-ui.card>

    <!-- Guru Honorer -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-amber-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <span class="text-sm text-amber-600">Honorer</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">{{ $statistics['honorary'] ?? 0 }}</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Guru Honorer</p>
    </x-ui.card>
</div>

<!-- Employee Data Table -->
<x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <!-- Table Header -->
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
            <div class="flex-1 max-w-md">
                <div class="relative">
                    <input type="text" id="employee-search" placeholder="Cari nama, email, atau ID karyawan..." 
                           class="w-full px-4 py-3 pl-10 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <select id="department-filter" class="px-4 py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                    <option value="">Semua Departemen</option>
                    @foreach($departments ?? [] as $dept)
                        <option value="{{ $dept }}">{{ $dept }}</option>
                    @endforeach
                </select>
                <select id="status-filter" class="px-4 py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                    <option value="">Semua Status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Tidak Aktif</option>
                </select>
                <select id="type-filter" class="px-4 py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                    <option value="">Semua Tipe</option>
                    <option value="permanent">Pegawai Tetap</option>
                    <option value="honorary">Guru Honorer</option>
                    <option value="part_time">Part Time</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Table Content -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="employeesTable">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Nama</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Jabatan</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Tipe</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"></tbody>
        </table>
    </div>
</x-ui.card>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if (!$.fn.dataTable.isDataTable('#employeesTable')) {
        let table = $('#employeesTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: '{{ route("employees.data") }}',
                data: function(d) {
                    d.search_value = $('#employee-search').val();
                    d.department = $('#department-filter').val();
                    d.status = $('#status-filter').val();
                    d.type = $('#type-filter').val();
                }
            },
            columns: [
                { 
                    data: 'name', 
                    name: 'name',
                    render: function(data, type, row) {
                        const photoUrl = row.photo || `https://ui-avatars.com/api/?name=${encodeURIComponent(row.name)}&color=7F9CF5&background=EBF4FF`;
                        return `<div class="flex items-center py-2">
                            <img src="${photoUrl}" alt="${row.name}" class="w-12 h-12 rounded-full object-cover mr-4 ring-2 ring-gray-200 dark:ring-gray-700">
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">${data}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">${row.employee_id}</div>
                            </div>
                        </div>`;
                    }
                },
                { 
                    data: 'email', 
                    name: 'email',
                    render: function(data) {
                        return `<span class="text-gray-700 dark:text-gray-300">${data}</span>`;
                    }
                },
                { 
                    data: 'position', 
                    name: 'position',
                    render: function(data) {
                        return `<span class="text-gray-700 dark:text-gray-300">${data}</span>`;
                    }
                },
                { 
                    data: 'employee_type', 
                    name: 'employee_type',
                    render: function(data) {
                        const variants = { 
                            permanent: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400', 
                            honorary: 'bg-amber-100 text-amber-800 dark:bg-amber-900/20 dark:text-amber-400', 
                            part_time: 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400' 
                        };
                        const variant = variants[data] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400';
                        return `<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${variant}">${data}</span>`;
                    }
                },
                { 
                    data: 'status', 
                    name: 'status',
                    render: function(data) {
                        const variant = data === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400';
                        return `<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${variant}">${data}</span>`;
                    }
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        let viewUrl = `/employees/${row.id}`;
                        let editUrl = `/employees/${row.id}/edit`;
                        return `
                            <div class="flex justify-end space-x-2">
                                <a href="${viewUrl}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-900/40 transition-colors duration-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <a href="${editUrl}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 hover:bg-amber-200 dark:hover:bg-amber-900/40 transition-colors duration-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <button onclick="deleteEmployee('${row.id}')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/20 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/40 transition-colors duration-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>`;
                    }
                }
            ],
            pageLength: 25,
            order: [[0, 'asc']],
            language: { url: '{{ asset("js/dataTables/i18n/Indonesian.json") }}' }
        });
    }

    $('#employee-search, #department-filter, #status-filter, #type-filter').on('change keyup', function() {
        $('#employeesTable').DataTable().draw();
    });

    $('#employee-search, #department-filter, #status-filter, #type-filter').on('change keyup', function() {
        table.draw();
    });
});

function exportEmployees() { /* ... */ }
function deleteEmployee(id) { /* ... */ }
</script>
@endpush