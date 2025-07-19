@extends('layouts.authenticated-unified')

@section('title', 'Manajemen Hari Libur')

@section('page-content')
<div class="p-6 lg:p-8">
    <x-layouts.base-page
        title="Manajemen Hari Libur"
        subtitle="Kelola hari libur nasional, libur sekolah, dan kalender cuti"
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Manajemen Hari Libur']
        ]">

        <x-slot name="actions">
            @can('create_holidays')
            <x-ui.button href="{{ route('holidays.create') }}">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Tambah Hari Libur
            </x-ui.button>
            @endcan
            @can('manage_holidays')
            <x-ui.button variant="outline" onclick="openModal('importModal')">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                </svg>
                Impor
            </x-ui.button>
            <x-ui.button variant="outline" onclick="openModal('exportModal')">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                Ekspor
            </x-ui.button>
            @endcan
            <x-ui.button variant="secondary" href="{{ route('holidays.calendar') }}">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Tampilan Kalender
            </x-ui.button>
        </x-slot>

        <!-- Filters -->
        <x-ui.card class="mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <x-ui.label for="type" value="Tipe" />
                    <x-ui.select id="type" name="type">
                        <option value="">Semua Tipe</option>
                        @foreach($types as $typeKey => $typeLabel)
                            <option value="{{ $typeKey }}" {{ request('type') == $typeKey ? 'selected' : '' }}>
                                {{ $typeLabel }}
                            </option>
                        @endforeach
                    </x-ui.select>
                </div>
                <div>
                    <x-ui.label for="status" value="Status" />
                    <x-ui.select id="status" name="status">
                        <option value="">Semua Status</option>
                        @foreach($statuses as $statusKey => $statusLabel)
                            <option value="{{ $statusKey }}" {{ request('status') == $statusKey ? 'selected' : '' }}>
                                {{ $statusLabel }}
                            </option>
                        @endforeach
                    </x-ui.select>
                </div>
                <div>
                    <x-ui.label for="year" value="Tahun" />
                    <x-ui.select id="year" name="year">
                        <option value="">Semua Tahun</option>
                        @foreach($years as $year)
                            <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </x-ui.select>
                </div>
                <div class="flex items-end space-x-2">
                    <div class="flex-grow">
                        <x-ui.label for="search" value="Cari" />
                        <x-ui.input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Cari hari libur..." />
                    </div>
                    <x-ui.button type="submit">Cari</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
            <x-ui.card variant="metric" title="Total Hari Libur" :value="$holidays->total()" color="destructive">
                <x-slot name="icon">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </x-slot>
            </x-ui.card>
            <x-ui.card variant="metric" title="Tahun Ini" :value="\App\Models\Holiday::whereYear('date', now()->year)->count()" color="info">
                 <x-slot name="icon">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </x-slot>
            </x-ui.card>
            <x-ui.card variant="metric" title="Akan Datang" :value="\App\Models\Holiday::active()->where('date', '>=', now())->count()" color="success">
                 <x-slot name="icon">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                </x-slot>
            </x-ui.card>
            <x-ui.card variant="metric" title="Berulang" :value="\App\Models\Holiday::recurring()->count()" color="warning">
                 <x-slot name="icon">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                </x-slot>
            </x-ui.card>
        </div>

        <!-- Holiday Table -->
        <x-ui.card title="Daftar Hari Libur">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Hari Libur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Tipe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Berulang</th>
                            @can('edit_holidays')<th class="relative px-6 py-3"><span class="sr-only">Aksi</span></th>@endcan
                        </tr>
                    </thead>
                    <tbody class="bg-background divide-y divide-border">
                        @forelse($holidays as $holiday)
                        <tr class="hover:bg-muted/30">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-3 w-3 rounded-full" style="background-color: {{ $holiday->color }}"></div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-foreground">{{ $holiday->name }}</div>
                                        @if($holiday->description)<div class="text-sm text-muted-foreground">{{ Str::limit($holiday->description, 50) }}</div>@endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                                <div>{{ $holiday->date->format('d M Y') }}</div>
                                @if($holiday->end_date && $holiday->end_date != $holiday->date)<div class="text-xs text-muted-foreground">sampai {{ $holiday->end_date->format('d M Y') }}</div>@endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-ui.badge variant="{{ 
                                    match($holiday->type) {
                                        'public_holiday' => 'destructive',
                                        'religious_holiday' => 'success',
                                        'school_holiday' => 'info',
                                        'substitute_holiday' => 'warning',
                                        default => 'secondary'
                                    }
                                }}">{{ $holiday->type_label }}</x-ui.badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-ui.badge variant="{{ $holiday->status === 'active' ? 'success' : 'secondary' }}">{{ $holiday->status_label }}</x-ui.badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                                @if($holiday->is_recurring)
                                    <x-ui.badge variant="success" class="bg-green-100 text-green-800">Ya</x-ui.badge>
                                @else
                                    <x-ui.badge variant="secondary">Tidak</x-ui.badge>
                                @endif
                            </td>
                            @can('edit_holidays')
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    <x-ui.button variant="ghost" size="sm" href="{{ route('holidays.show', $holiday) }}">Lihat</x-ui.button>
                                    @can('edit_holidays')<x-ui.button variant="ghost" size="sm" href="{{ route('holidays.edit', $holiday) }}">Edit</x-ui.button>@endcan
                                    @can('delete_holidays')<x-ui.button variant="ghost" size="sm" class="text-destructive" onclick="deleteHoliday('{{ $holiday->id }}', '{{ $holiday->name }}')">Hapus</x-ui.button>@endcan
                                </div>
                            </td>
                            @endcan
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6">
                                <x-ui.empty-state
                                    title="Tidak Ada Hari Libur"
                                    description="Mulai dengan membuat hari libur pertama Anda."
                                    icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    @can('create_holidays')
                                    <x-slot name="actions">
                                        <x-ui.button href="{{ route('holidays.create') }}">Tambah Hari Libur</x-ui.button>
                                    </x-slot>
                                    @endcan
                                </x-ui.empty-state>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($holidays->hasPages())
            <div class="p-4 border-t border-border">
                {{ $holidays->withQueryString()->links() }}
            </div>
            @endif
        </x-ui.card>
    </x-layouts.base-page>
</div>

<!-- Modals -->
@can('manage_holidays')
<x-ui.modal id="importModal" title="Impor Hari Libur">
    <form id="importForm" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <div>
            <x-ui.label for="importSource" value="Sumber Impor" />
            <x-ui.select name="source" id="importSource">
                <option value="government_api">API Pemerintah</option>
                <option value="file_upload">Unggah File</option>
            </x-ui.select>
        </div>
        <div>
            <x-ui.label for="year" value="Tahun" />
            <x-ui.input type="number" name="year" value="{{ date('Y') }}" min="2020" max="2030" />
        </div>
        <div id="fileUploadDiv" class="hidden">
            <x-ui.label for="file" value="File" />
            <x-ui.input type="file" name="file" accept=".csv,.xlsx,.json" />
            <p class="text-xs text-muted-foreground mt-1">Format yang didukung: CSV, Excel, JSON</p>
        </div>
        <div class="flex justify-end space-x-2 pt-4">
            <x-ui.button type="button" variant="outline" onclick="closeModal('importModal')">Batal</x-ui.button>
            <x-ui.button type="submit">Impor</x-ui.button>
        </div>
    </form>
</x-ui.modal>

<x-ui.modal id="exportModal" title="Ekspor Hari Libur">
    <form id="exportForm" class="space-y-4">
        @csrf
        <div>
            <x-ui.label for="format" value="Format" />
            <x-ui.select name="format">
                <option value="csv">CSV</option>
                <option value="xlsx">Excel</option>
                <option value="json">JSON</option>
                <option value="pdf">PDF</option>
            </x-ui.select>
        </div>
        <div>
            <x-ui.label for="export_year" value="Tahun (opsional)" />
            <x-ui.input type="number" name="year" placeholder="Semua tahun" min="2020" max="2030" />
        </div>
        <div>
            <x-ui.label for="export_type" value="Tipe (opsional)" />
            <x-ui.select name="type">
                <option value="">Semua Tipe</option>
                @foreach($types as $typeKey => $typeLabel)
                    <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                @endforeach
            </x-ui.select>
        </div>
        <div class="flex justify-end space-x-2 pt-4">
            <x-ui.button type="button" variant="outline" onclick="closeModal('exportModal')">Batal</x-ui.button>
            <x-ui.button type="submit">Ekspor</x-ui.button>
        </div>
    </form>
</x-ui.modal>
@endcan
@endsection

@push('scripts')
<script>
    // Modal functions
    function showImportModal() { openModal('importModal'); }
    function hideImportModal() { closeModal('importModal'); }
    function showExportModal() { openModal('exportModal'); }
    function hideExportModal() { closeModal('exportModal'); }
    
    document.addEventListener('DOMContentLoaded', function() {
        const importSource = document.getElementById('importSource');
        if(importSource) {
            importSource.addEventListener('change', function() {
                const fileDiv = document.getElementById('fileUploadDiv');
                if (this.value === 'file_upload') {
                    fileDiv.classList.remove('hidden');
                } else {
                    fileDiv.classList.add('hidden');
                }
            });
        }

        const importForm = document.getElementById('importForm');
        if(importForm) {
            importForm.addEventListener('submit', function(e) {
                e.preventDefault();
                // Handle import logic
            });
        }

        const exportForm = document.getElementById('exportForm');
        if(exportForm) {
            exportForm.addEventListener('submit', function(e) {
                e.preventDefault();
                // Handle export logic
            });
        }
    });

    function deleteHoliday(id, name) {
        if (confirm('Are you sure you want to delete "' + name + '"?')) {
            // Handle delete logic
        }
    }
</script>
@endpush
