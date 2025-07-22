@extends('layouts.authenticated-unified')

@section('title', 'Backup & Restore Sistem')

@section('page-content')
<div class="backup-page-bg">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Backup & Restore Sistem"
            subtitle="Administrasi Sistem - Operasi backup dan restore"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Admin'],
                ['label' => 'Backup & Restore']
            ]">
            <x-slot name="actions">
                <div class="flex flex-col sm:flex-row gap-2">
                    <x-ui.button variant="primary" onclick="openModal('createBackupModal')">
                        <x-icons.cloud-download class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" />
                        Buat Backup
                    </x-ui.button>
                    
                    <x-ui.button variant="secondary" onclick="openModal('scheduleModal')">
                        <x-icons.clock class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" />
                        Jadwal
                    </x-ui.button>
                    
                    <x-ui.button variant="destructive" onclick="openModal('cleanupModal')">
                        <x-icons.trash class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" />
                        Bersihkan
                    </x-ui.button>
                </div>
            </x-slot>
        </x-layouts.base-page>

        <!-- Storage Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="glassmorphism-card">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Total Backup</p>
                        <p class="metric-value" id="totalBackups">{{ $storageInfo['total_backups'] }}</p>
                    </div>
                    <div class="metric-icon metric-icon-blue">
                        <x-icons.refresh class="w-6 h-6 text-white" />
                    </div>
                </div>
            </div>
            <div class="glassmorphism-card">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Penyimpanan Terpakai</p>
                        <p class="metric-value" id="storageUsed">{{ $storageInfo['total_size_human'] }}</p>
                    </div>
                    <div class="metric-icon metric-icon-green">
                        <x-icons.document class="w-6 h-6 text-white" />
                    </div>
                </div>
            </div>
            <div class="glassmorphism-card">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Ruang Tersedia</p>
                        <p class="metric-value" id="availableSpace">{{ $storageInfo['available_space_human'] }}</p>
                    </div>
                    <div class="metric-icon metric-icon-amber">
                        <x-icons.question-mark-circle class="w-6 h-6 text-white" />
                    </div>
                </div>
            </div>
            <div class="glassmorphism-card">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Backup Terakhir</p>
                        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1" id="lastBackup">
                            @if(count($backups) > 0)
                                {{ \Carbon\Carbon::parse($backups[0]['created_at'])->diffForHumans() }}
                            @else
                                Tidak Pernah
                            @endif
                        </p>
                    </div>
                    <div class="metric-icon metric-icon-cyan">
                        <x-icons.arrow-down-circle class="w-6 h-6 text-white" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Backup Schedule Status -->
        <div class="glassmorphism-card mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-semibold text-slate-800 dark:text-white">Jadwal Backup</h3>
                <x-ui.button variant="secondary" onclick="openModal('scheduleModal')">
                    <x-icons.edit class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" />
                    Edit Jadwal
                </x-ui.button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="flex items-center p-4 bg-white/10 rounded-lg">
                    <span class="schedule-badge {{ $backupSchedule['database']['enabled'] ? 'schedule-enabled' : 'schedule-disabled' }}">
                        {{ $backupSchedule['database']['enabled'] ? 'Aktif' : 'Nonaktif' }}
                    </span>
                    <div>
                        <div class="font-medium text-slate-800 dark:text-white">Backup Database</div>
                        <div class="text-slate-600 dark:text-slate-400 text-sm">
                            {{ ucfirst($backupSchedule['database']['frequency']) }} pada {{ $backupSchedule['database']['time'] }}
                        </div>
                    </div>
                </div>
                <div class="flex items-center p-4 bg-white/10 rounded-lg">
                    <span class="schedule-badge {{ $backupSchedule['files']['enabled'] ? 'schedule-enabled' : 'schedule-disabled' }}">
                        {{ $backupSchedule['files']['enabled'] ? 'Aktif' : 'Nonaktif' }}
                    </span>
                    <div>
                        <div class="font-medium text-slate-800 dark:text-white">Backup File</div>
                        <div class="text-slate-600 dark:text-slate-400 text-sm">
                            {{ ucfirst($backupSchedule['files']['frequency']) }} pada {{ ucfirst($backupSchedule['files']['day']) }} pukul {{ $backupSchedule['files']['time'] }}
                        </div>
                    </div>
                </div>
                <div class="flex items-center p-4 bg-white/10 rounded-lg">
                    <span class="schedule-badge {{ $backupSchedule['full']['enabled'] ? 'schedule-enabled' : 'schedule-disabled' }}">
                        {{ $backupSchedule['full']['enabled'] ? 'Aktif' : 'Nonaktif' }}
                    </span>
                    <div>
                        <div class="font-medium text-slate-800 dark:text-white">Backup Penuh</div>
                        <div class="text-slate-600 dark:text-slate-400 text-sm">
                            {{ ucfirst($backupSchedule['full']['frequency']) }} pada hari {{ $backupSchedule['full']['day'] }} pukul {{ $backupSchedule['full']['time'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Backup Progress (hidden by default) -->
        <div id="backupProgress" class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mb-6" style="display: none;">
            <div class="flex items-center">
                <div class="mr-3">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                </div>
                <div class="flex-1">
                    <div class="font-medium text-slate-800 dark:text-white">Membuat Backup...</div>
                    <div class="text-slate-600 dark:text-slate-400" id="backupProgressText">Menginisialisasi proses backup...</div>
                </div>
            </div>
        </div>

        <!-- Available Backups -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-semibold text-slate-800 dark:text-white">Backup Tersedia</h3>
                <x-ui.button variant="secondary" onclick="refreshBackupList()">
                    <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                    </svg>
                    Segarkan
                </x-ui.button>
            </div>
            <div class="overflow-hidden">
                @if(count($backups) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-white/20" id="backupsTable">
                            <thead class="bg-white/10 backdrop-blur-sm">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Tipe</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Deskripsi</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Dibuat</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Ukuran</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Item</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white/5 backdrop-blur-sm divide-y divide-white/10">
                                @foreach($backups as $backup)
                                <tr data-backup-id="{{ $backup['backup_id'] }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white bg-gradient-to-r {{ $backup['type'] === 'full' ? 'from-blue-500 to-purple-600' : ($backup['type'] === 'database' ? 'from-green-500 to-emerald-600' : 'from-amber-500 to-orange-600') }} shadow-lg">
                                            {{ ucfirst($backup['type']) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-slate-800 dark:text-white">{{ $backup['description'] ?: 'Tidak ada deskripsi' }}</div>
                                        <div class="text-slate-500 dark:text-slate-400 text-sm">ID: {{ substr($backup['backup_id'], 0, 8) }}...</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-slate-800 dark:text-white">{{ \Carbon\Carbon::parse($backup['created_at'])->format('M j, Y g:i A') }}</div>
                                        <div class="text-slate-500 dark:text-slate-400 text-sm">{{ \Carbon\Carbon::parse($backup['created_at'])->diffForHumans() }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="font-medium text-slate-800 dark:text-white">{{ $backup['size_human'] }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($backup['items'] as $item)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium text-white bg-gradient-to-r from-gray-500 to-slate-600 shadow-lg">{{ $item }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('backup.download', $backup['backup_id']) }}" class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm border border-white/30 text-slate-600 dark:text-slate-300 hover:bg-blue-500 hover:text-white transition-all duration-300 hover:scale-110 shadow-lg">
                                                <svg class="w-4 h-4 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 17v2a2 2 0 002 2h12a2 2 0 002 -2v-2"/><polyline points="7,11 12,16 17,11"/><line x1="12" y1="4" x2="12" y2="16"/></svg>
                                            </a>
                                            <button class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm border border-white/30 text-slate-600 dark:text-slate-300 hover:bg-green-500 hover:text-white transition-all duration-300 hover:scale-110 shadow-lg" onclick="restoreBackup('{{ $backup['backup_id'] }}')">
                                                <svg class="w-4 h-4 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/></svg>
                                            </button>
                                            <button class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm border border-white/30 text-slate-600 dark:text-slate-300 hover:bg-red-500 hover:text-white transition-all duration-300 hover:scale-110 shadow-lg" onclick="deleteBackup('{{ $backup['backup_id'] }}')">
                                                <svg class="w-4 h-4 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11v6"/><path d="M14 11v6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-ui.empty-state
                        title="Tidak ada backup tersedia"
                        description="Buat backup pertama Anda untuk memulai perlindungan data."
                        icon="M14 3v4a1 1 0 0 0 1 1h4M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2zM12 11v6M9 14l3 -3l3 3">
                        <x-slot name="actions">
                            <x-ui.button onclick="openModal('createBackupModal')">Buat Backup Pertama</x-ui.button>
                        </x-slot>
                    </x-ui.empty-state>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Create Backup Modal -->
<x-ui.modal id="createBackupModal" title="Buat Backup Baru">
    <form id="createBackupForm" class="space-y-4">
        @csrf
        <div>
            <x-ui.label for="backupType" class="text-slate-700 dark:text-slate-300">Tipe Backup <span class="text-red-500">*</span></x-ui.label>
            <x-ui.select name="type" id="backupType" required class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                <option value="">Pilih tipe backup...</option>
                <option value="database">Hanya Database</option>
                <option value="files">Hanya File</option>
                <option value="full">Backup Penuh (Database + File)</option>
            </x-ui.select>
            <div class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                <strong>Database:</strong> Semua tabel dan data database<br>
                <strong>File:</strong> File aplikasi, unggahan, dan konfigurasi<br>
                <strong>Penuh:</strong> Backup sistem lengkap termasuk database dan file
            </div>
        </div>
        <div>
            <x-ui.label for="backupDescription" class="text-slate-700 dark:text-slate-300">Deskripsi</x-ui.label>
            <x-ui.input type="text" name="description" id="backupDescription" placeholder="Deskripsi backup opsional" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
        </div>
        <div>
            <x-ui.label class="text-slate-700 dark:text-slate-300">Opsi Tambahan</x-ui.label>
            <div class="space-y-2">
                <div class="flex items-center">
                    <x-ui.checkbox id="include_uploads" name="include_uploads" value="1" checked />
                    <label for="include_uploads" class="ml-2 block text-sm text-slate-700 dark:text-slate-300">Sertakan unggahan dan file pengguna</label>
                </div>
                <div class="flex items-center">
                    <x-ui.checkbox id="include_logs" name="include_logs" value="1" />
                    <label for="include_logs" class="ml-2 block text-sm text-slate-700 dark:text-slate-300">Sertakan log sistem</label>
                </div>
            </div>
        </div>
        <div class="flex justify-end space-x-3 pt-4">
            <x-ui.button type="button" variant="secondary" onclick="closeModal('createBackupModal')">Batal</x-ui.button>
            <x-ui.button type="submit" variant="primary">
                <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3v4a1 1 0 001 1h4"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21h-10a2 2 0 01-2-2v-14a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11v6"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l3 -3l3 3"/>
                </svg>
                Buat Backup
            </x-ui.button>
        </div>
    </form>
</x-ui.modal>

<!-- Schedule Modal -->
<x-ui.modal id="scheduleModal" title="Konfigurasi Jadwal Backup" size="4xl">
    <form id="scheduleForm" class="space-y-4">
        @csrf
        <!-- Database Backup Schedule -->
        <div class="mb-6">
            <h4 class="text-lg font-semibold text-slate-800 dark:text-white mb-3">Backup Database</h4>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="flex items-center">
                    <x-ui.checkbox id="database_enabled" name="database[enabled]" value="1" {{ $backupSchedule['database']['enabled'] ? 'checked' : '' }} />
                    <label for="database_enabled" class="ml-2 block text-sm text-slate-700 dark:text-slate-300">Aktifkan</label>
                </div>
                <div>
                    <x-ui.select name="database[frequency]" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                        <option value="daily" {{ $backupSchedule['database']['frequency'] === 'daily' ? 'selected' : '' }}>Harian</option>
                        <option value="weekly" {{ $backupSchedule['database']['frequency'] === 'weekly' ? 'selected' : '' }}>Mingguan</option>
                    </x-ui.select>
                </div>
                <div>
                    <x-ui.input type="time" name="database[time]" value="{{ $backupSchedule['database']['time'] }}" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                </div>
                <div>
                    <x-ui.input type="number" name="database[retention_days]" value="{{ $backupSchedule['database']['retention_days'] }}" placeholder="Hari retensi" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                </div>
            </div>
        </div>

        <!-- Files Backup Schedule -->
        <div class="mb-6">
            <h4 class="text-lg font-semibold text-slate-800 dark:text-white mb-3">Backup File</h4>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="flex items-center">
                    <x-ui.checkbox id="files_enabled" name="files[enabled]" value="1" {{ $backupSchedule['files']['enabled'] ? 'checked' : '' }} />
                    <label for="files_enabled" class="ml-2 block text-sm text-slate-700 dark:text-slate-300">Aktifkan</label>
                </div>
                <div>
                    <x-ui.select name="files[frequency]" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                        <option value="weekly" {{ $backupSchedule['files']['frequency'] === 'weekly' ? 'selected' : '' }}>Mingguan</option>
                        <option value="monthly" {{ $backupSchedule['files']['frequency'] === 'monthly' ? 'selected' : '' }}>Bulanan</option>
                    </x-ui.select>
                </div>
                <div>
                    <x-ui.input type="time" name="files[time]" value="{{ $backupSchedule['files']['time'] }}" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                </div>
                <div>
                    <x-ui.input type="number" name="files[retention_days]" value="{{ $backupSchedule['files']['retention_days'] }}" placeholder="Hari retensi" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                </div>
            </div>
        </div>

        <!-- Full Backup Schedule -->
        <div class="mb-6">
            <h4 class="text-lg font-semibold text-slate-800 dark:text-white mb-3">Backup Penuh</h4>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="flex items-center">
                    <x-ui.checkbox id="full_enabled" name="full[enabled]" value="1" {{ $backupSchedule['full']['enabled'] ? 'checked' : '' }} />
                    <label for="full_enabled" class="ml-2 block text-sm text-slate-700 dark:text-slate-300">Aktifkan</label>
                </div>
                <div>
                    <x-ui.select name="full[frequency]" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                        <option value="monthly" {{ $backupSchedule['full']['frequency'] === 'monthly' ? 'selected' : '' }}>Bulanan</option>
                        <option value="quarterly" {{ $backupSchedule['full']['frequency'] === 'quarterly' ? 'selected' : '' }}>Triwulanan</option>
                    </x-ui.select>
                </div>
                <div>
                    <x-ui.input type="time" name="full[time]" value="{{ $backupSchedule['full']['time'] }}" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                </div>
                <div>
                    <x-ui.input type="number" name="full[retention_days]" value="{{ $backupSchedule['full']['retention_days'] }}" placeholder="Hari retensi" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                </div>
            </div>
        </div>
        <div class="flex justify-end space-x-3 pt-4">
            <x-ui.button type="button" variant="secondary" onclick="closeModal('scheduleModal')">Batal</x-ui.button>
            <x-ui.button type="submit" variant="primary">Simpan Jadwal</x-ui.button>
        </div>
    </form>
</x-ui.modal>

<!-- Restore Confirmation Modal -->
<x-ui.modal id="restoreModal" title="Konfirmasi Restore Sistem">
    <div class="bg-amber-50/20 border border-amber-500/30 text-amber-800 dark:text-amber-200 px-4 py-3 rounded-lg mb-4">
        <h4 class="font-medium text-base">⚠️ Peringatan</h4>
        <p class="text-sm">Ini akan mengembalikan sistem Anda ke keadaan sebelumnya. Tindakan ini tidak dapat dibatalkan dan dapat menimpa data saat ini.</p>
    </div>
    
    <div class="space-y-4">
        <div class="flex items-center">
            <x-ui.checkbox id="confirmRestore" required />
            <label for="confirmRestore" class="ml-2 block text-sm text-slate-700 dark:text-slate-300">
                Saya mengerti ini akan menimpa data sistem saat ini
            </label>
        </div>
        
        <div class="flex items-center">
            <x-ui.checkbox id="backupCurrent" checked />
            <label for="backupCurrent" class="ml-2 block text-sm text-slate-700 dark:text-slate-300">
                Buat backup keadaan saat ini sebelum restore
            </label>
        </div>
    </div>
    
    <input type="hidden" id="restoreBackupId">
    
    <x-slot name="footer">
        <x-ui.button type="button" variant="secondary" onclick="closeModal('restoreModal')">Batal</x-ui.button>
        <x-ui.button type="button" variant="destructive" id="confirmRestoreBtn">
            <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
            </svg>
            Restore Sistem
        </x-ui.button>
    </x-slot>
</x-ui.modal>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Create backup form submission
    $('#createBackupForm').on('submit', function(e) {
        e.preventDefault();
        createBackup();
    });
    
    // Schedule form submission
    $('#scheduleForm').on('submit', function(e) {
        e.preventDefault();
        updateSchedule();
    });
    
    // Restore confirmation
    $('#confirmRestoreBtn').on('click', function() {
        const backupId = $('#restoreBackupId').val();
        const confirmRestore = $('#confirmRestore').is(':checked');
        const backupCurrent = $('#backupCurrent').is(':checked');
        
        if (!confirmRestore) {
            toastr.error('Harap konfirmasi Anda memahami proses restore');
            return;
        }
        
        performRestore(backupId, backupCurrent);
    });
});

// Modal functions
// openModal and closeModal are assumed to be global functions from x-ui.modal

function createBackup() {
    const formData = new FormData($('#createBackupForm')[0]);
    
    closeModal('createBackupModal');
    $('#backupProgress').show();
    
    $.ajax({
        url: '{{ route("backup.create") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            $('#backupProgress').hide();
            if (response.success) {
                toastr.success(response.message);
                refreshBackupList();
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            $('#backupProgress').hide();
            toastr.error(xhr.responseJSON?.message || 'Pembuatan backup gagal');
        }
    });
}

function restoreBackup(backupId) {
    $('#restoreBackupId').val(backupId);
    openModal('restoreModal');
}

function performRestore(backupId, backupCurrent) {
    closeModal('restoreModal');
    
    $.ajax({
        url: `/admin/backup/${backupId}/restore`,
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            confirm_restore: 1,
            backup_current: backupCurrent ? 1 : 0
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                // Refresh page after successful restore
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Restore gagal');
        }
    });
}

function deleteBackup(backupId) {
    if (!confirm('Apakah Anda yakin ingin menghapus backup ini? Tindakan ini tidak dapat dibatalkan.')) {
        return;
    }
    
    $.ajax({
        url: `/admin/backup/${backupId}`,
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                $(`tr[data-backup-id="${backupId}"]`).remove();
                updateStorageInfo();
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Penghapusan gagal');
        }
    });
}

function updateSchedule() {
    const formData = new FormData($('#scheduleForm')[0]);
    
    $.ajax({
        url: '{{ route("backup.schedule.update") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                closeModal('scheduleModal');
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Pembaruan jadwal gagal');
        }
    });
}

function cleanupOldBackups() {
    openModal('cleanupModal');
}

function refreshBackupList() {
    window.location.reload();
}

function updateStorageInfo() {
    // Update storage information after backup operations
    // This would typically fetch updated storage info via AJAX
    $.ajax({
        url: '{{ route("backup.storage.info") }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const info = response.data;
                $('#totalBackups').text(info.total_backups);
                $('#storageUsed').text(info.total_size_human);
                $('#availableSpace').text(info.available_space_human);
                if (info.last_backup_human) {
                    $('#lastBackup').text(info.last_backup_human);
                } else {
                    $('#lastBackup').text('Tidak Pernah');
                }
            }
        },
        error: function() {
            toastr.error('Gagal memperbarui info penyimpanan');
        }
    });
}
</script>
@endpush
