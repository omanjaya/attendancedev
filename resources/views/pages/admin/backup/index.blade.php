@extends('layouts.authenticated')

@section('title', 'System Backup & Restore')

@section('page-content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <x-layout.page-header
            title="System Backup & Restore"
            subtitle="System Administration - Backup and restore operations">
            <x-slot name="actions">
                <div class="flex flex-col sm:flex-row gap-2">
                    <x-ui.button onclick="openCreateBackupModal()">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                        </svg>
                        Create Backup
                    </x-ui.button>
                    
                    <x-ui.button variant="outline" onclick="openScheduleModal()">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Schedule
                    </x-ui.button>
                    
                    <x-ui.button variant="outline" onclick="cleanupOldBackups()">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Cleanup
                    </x-ui.button>
                </div>
            </x-slot>
        </x-layout.page-header>

        <!-- Storage Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="mr-3">
                        <span class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <rect x="4" y="4" width="6" height="6" rx="1"/>
                                <rect x="14" y="4" width="6" height="6" rx="1"/>
                                <rect x="4" y="14" width="6" height="6" rx="1"/>
                                <rect x="14" y="14" width="6" height="6" rx="1"/>
                            </svg>
                        </span>
                    </div>
                    <div>
                        <div class="font-medium">Total Backups</div>
                        <div class="text-gray-600">Available backups</div>
                    </div>
                </div>
                <div class="text-2xl font-bold mt-4" id="totalBackups">{{ $storageInfo['total_backups'] }}</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="mr-3">
                        <span class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M14 3v4a1 1 0 0 0 1 1h4"/>
                                <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                            </svg>
                        </span>
                    </div>
                    <div>
                        <div class="font-medium">Storage Used</div>
                        <div class="text-gray-600">Disk space used</div>
                    </div>
                </div>
                <div class="text-2xl font-bold mt-4" id="storageUsed">{{ $storageInfo['total_size_human'] }}</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="mr-3">
                        <span class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z"/>
                                <path d="M12 15l0 .01"/>
                                <path d="M12 12c0 -1.5 .8 -3 2 -3s2 1.5 2 3c0 .75 -.4 1.5 -1 2l-1 1h-2"/>
                            </svg>
                        </span>
                    </div>
                    <div>
                        <div class="font-medium">Available Space</div>
                        <div class="text-gray-600">Free disk space</div>
                    </div>
                </div>
                <div class="text-2xl font-bold mt-4" id="availableSpace">{{ $storageInfo['available_space_human'] }}</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="mr-3">
                        <span class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="12" cy="12" r="9"/>
                                <polyline points="12,7 12,12 15,15"/>
                            </svg>
                        </span>
                    </div>
                    <div>
                        <div class="font-medium">Last Backup</div>
                        <div class="text-gray-600">Most recent backup</div>
                    </div>
                </div>
                <div class="text-2xl font-bold mt-4" id="lastBackup">
                    @if(count($backups) > 0)
                        {{ \Carbon\Carbon::parse($backups[0]['created_at'])->diffForHumans() }}
                    @else
                        Never
                    @endif
                </div>
            </div>
        </div>

        <!-- Backup Schedule Status -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Backup Schedule</h3>
                <button class="inline-flex items-center px-3 py-2 border border-blue-300 text-sm font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" onclick="openScheduleModal()">
                    <svg class="w-4 h-4 mr-2" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M9 7h-3a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-3"/>
                        <path d="M9 15h3l8.5 -8.5a1.5 1.5 0 0 0 -3 -3l-8.5 8.5v3"/>
                        <line x1="16" y1="5" x2="19" y2="8"/>
                    </svg>
                    Edit Schedule
                </button>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mr-3 {{ $backupSchedule['database']['enabled'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $backupSchedule['database']['enabled'] ? 'Enabled' : 'Disabled' }}
                        </span>
                        <div>
                            <div class="font-medium">Database Backup</div>
                            <div class="text-gray-600 text-sm">
                                {{ ucfirst($backupSchedule['database']['frequency']) }} at {{ $backupSchedule['database']['time'] }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mr-3 {{ $backupSchedule['files']['enabled'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $backupSchedule['files']['enabled'] ? 'Enabled' : 'Disabled' }}
                        </span>
                        <div>
                            <div class="font-medium">Files Backup</div>
                            <div class="text-gray-600 text-sm">
                                {{ ucfirst($backupSchedule['files']['frequency']) }} on {{ ucfirst($backupSchedule['files']['day']) }} at {{ $backupSchedule['files']['time'] }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mr-3 {{ $backupSchedule['full']['enabled'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $backupSchedule['full']['enabled'] ? 'Enabled' : 'Disabled' }}
                        </span>
                        <div>
                            <div class="font-medium">Full Backup</div>
                            <div class="text-gray-600 text-sm">
                                {{ ucfirst($backupSchedule['full']['frequency']) }} on day {{ $backupSchedule['full']['day'] }} at {{ $backupSchedule['full']['time'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Backup Progress (hidden by default) -->
        <div id="backupProgress" class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6" style="display: none;">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="mr-3">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                    </div>
                    <div class="flex-1">
                        <div class="font-medium">Creating Backup...</div>
                        <div class="text-gray-600" id="backupProgressText">Initializing backup process...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Backups -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Available Backups</h3>
                <button class="inline-flex items-center px-3 py-2 border border-blue-300 text-sm font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" onclick="refreshBackupList()">
                    <svg class="w-4 h-4 mr-2" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                        <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                    </svg>
                    Refresh
                </button>
            </div>
            <div class="overflow-hidden">
                @if(count($backups) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="backupsTable">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($backups as $backup)
                                <tr data-backup-id="{{ $backup['backup_id'] }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $backup['type'] === 'full' ? 'bg-blue-100 text-blue-800' : ($backup['type'] === 'database' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800') }}">
                                            {{ ucfirst($backup['type']) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900">{{ $backup['description'] ?: 'No description' }}</div>
                                        <div class="text-gray-500 text-sm">ID: {{ substr($backup['backup_id'], 0, 8) }}...</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-gray-900">{{ \Carbon\Carbon::parse($backup['created_at'])->format('M j, Y g:i A') }}</div>
                                        <div class="text-gray-500 text-sm">{{ \Carbon\Carbon::parse($backup['created_at'])->diffForHumans() }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="font-medium text-gray-900">{{ $backup['size_human'] }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($backup['items'] as $item)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ $item }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('backup.download', $backup['backup_id']) }}" class="inline-flex items-center px-3 py-1.5 border border-blue-300 text-xs font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                <svg class="w-4 h-4 mr-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/>
                                                    <polyline points="7,11 12,16 17,11"/>
                                                    <line x1="12" y1="4" x2="12" y2="16"/>
                                                </svg>
                                                Download
                                            </a>
                                            <button class="inline-flex items-center px-3 py-1.5 border border-green-300 text-xs font-medium rounded-md text-green-700 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500" onclick="restoreBackup('{{ $backup['backup_id'] }}')">
                                                <svg class="w-4 h-4 mr-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                                                    <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                                                </svg>
                                                Restore
                                            </button>
                                            <button class="inline-flex items-center px-3 py-1.5 border border-red-300 text-xs font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" onclick="deleteBackup('{{ $backup['backup_id'] }}')">
                                                <svg class="w-4 h-4 mr-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M4 7h16"/>
                                                    <path d="M10 11v6"/>
                                                    <path d="M14 11v6"/>
                                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
                                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>
                                                </svg>
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="mb-4">
                            <svg class="mx-auto h-12 w-12 text-gray-400" width="48" height="48" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M14 3v4a1 1 0 0 0 1 1h4"/>
                                <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                <path d="M12 11v6"/>
                                <path d="M9 14l3 -3l3 3"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No backups available</h3>
                        <p class="text-gray-500 mb-4">Create your first backup to get started with data protection.</p>
                        <button class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" onclick="openCreateBackupModal()">
                            Create First Backup
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Create Backup Modal -->
<div id="createBackupModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="createBackupForm">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-0 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">Create New Backup</h3>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Backup Type <span class="text-red-500">*</span></label>
                                <select class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="type" required>
                                    <option value="">Select backup type...</option>
                                    <option value="database">Database Only</option>
                                    <option value="files">Files Only</option>
                                    <option value="full">Full Backup (Database + Files)</option>
                                </select>
                                <div class="mt-2 text-sm text-gray-600">
                                    <strong>Database:</strong> All database tables and data<br>
                                    <strong>Files:</strong> Application files, uploads, and configurations<br>
                                    <strong>Full:</strong> Complete system backup including database and files
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <input type="text" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="description" placeholder="Optional backup description">
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Additional Options</label>
                                <div class="space-y-2">
                                    <div class="flex items-center">
                                        <input id="include_uploads" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" type="checkbox" name="include_uploads" value="1" checked>
                                        <label for="include_uploads" class="ml-2 block text-sm text-gray-900">Include user uploads and files</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="include_logs" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" type="checkbox" name="include_logs" value="1">
                                        <label for="include_logs" class="ml-2 block text-sm text-gray-900">Include system logs</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        <svg class="w-4 h-4 mr-2" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M14 3v4a1 1 0 0 0 1 1h4"/>
                            <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                            <path d="M12 11v6"/>
                            <path d="M9 14l3 -3l3 3"/>
                        </svg>
                        Create Backup
                    </button>
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeCreateBackupModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Schedule Modal -->
<div id="scheduleModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <form id="scheduleForm">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-0 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6" id="modal-title">Backup Schedule Configuration</h3>
                            
                            <!-- Database Backup Schedule -->
                            <div class="mb-6">
                                <h4 class="text-md font-medium text-gray-900 mb-3">Database Backup</h4>
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div>
                                        <div class="flex items-center">
                                            <input id="database_enabled" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" type="checkbox" name="database[enabled]" value="1" {{ $backupSchedule['database']['enabled'] ? 'checked' : '' }}>
                                            <label for="database_enabled" class="ml-2 block text-sm text-gray-900">Enabled</label>
                                        </div>
                                    </div>
                                    <div>
                                        <select class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="database[frequency]">
                                            <option value="daily" {{ $backupSchedule['database']['frequency'] === 'daily' ? 'selected' : '' }}>Daily</option>
                                            <option value="weekly" {{ $backupSchedule['database']['frequency'] === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                        </select>
                                    </div>
                                    <div>
                                        <input type="time" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="database[time]" value="{{ $backupSchedule['database']['time'] }}">
                                    </div>
                                    <div>
                                        <input type="number" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="database[retention_days]" value="{{ $backupSchedule['database']['retention_days'] }}" placeholder="Retention days">
                                    </div>
                                </div>
                            </div>

                            <!-- Files Backup Schedule -->
                            <div class="mb-6">
                                <h4 class="text-md font-medium text-gray-900 mb-3">Files Backup</h4>
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div>
                                        <div class="flex items-center">
                                            <input id="files_enabled" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" type="checkbox" name="files[enabled]" value="1" {{ $backupSchedule['files']['enabled'] ? 'checked' : '' }}>
                                            <label for="files_enabled" class="ml-2 block text-sm text-gray-900">Enabled</label>
                                        </div>
                                    </div>
                                    <div>
                                        <select class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="files[frequency]">
                                            <option value="weekly" {{ $backupSchedule['files']['frequency'] === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                            <option value="monthly" {{ $backupSchedule['files']['frequency'] === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                        </select>
                                    </div>
                                    <div>
                                        <input type="time" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="files[time]" value="{{ $backupSchedule['files']['time'] }}">
                                    </div>
                                    <div>
                                        <input type="number" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="files[retention_days]" value="{{ $backupSchedule['files']['retention_days'] }}" placeholder="Retention days">
                                    </div>
                                </div>
                            </div>

                            <!-- Full Backup Schedule -->
                            <div class="mb-6">
                                <h4 class="text-md font-medium text-gray-900 mb-3">Full Backup</h4>
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div>
                                        <div class="flex items-center">
                                            <input id="full_enabled" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" type="checkbox" name="full[enabled]" value="1" {{ $backupSchedule['full']['enabled'] ? 'checked' : '' }}>
                                            <label for="full_enabled" class="ml-2 block text-sm text-gray-900">Enabled</label>
                                        </div>
                                    </div>
                                    <div>
                                        <select class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="full[frequency]">
                                            <option value="monthly" {{ $backupSchedule['full']['frequency'] === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                            <option value="quarterly" {{ $backupSchedule['full']['frequency'] === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                        </select>
                                    </div>
                                    <div>
                                        <input type="time" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="full[time]" value="{{ $backupSchedule['full']['time'] }}">
                                    </div>
                                    <div>
                                        <input type="number" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="full[retention_days]" value="{{ $backupSchedule['full']['retention_days'] }}" placeholder="Retention days">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">Save Schedule</button>
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeScheduleModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Restore Confirmation Modal -->
<div id="restoreModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-0 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">Confirm System Restore</h3>
                        
                        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">Warning</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>This will restore your system to a previous state. This action cannot be undone and may overwrite current data.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <input id="confirmRestore" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" type="checkbox" required>
                                <label for="confirmRestore" class="ml-2 block text-sm text-gray-900">
                                    I understand this will overwrite current system data
                                </label>
                            </div>
                            
                            <div class="flex items-center">
                                <input id="backupCurrent" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" type="checkbox" checked>
                                <label for="backupCurrent" class="ml-2 block text-sm text-gray-900">
                                    Create backup of current state before restoring
                                </label>
                            </div>
                        </div>
                        
                        <input type="hidden" id="restoreBackupId">
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm" id="confirmRestoreBtn">
                    <svg class="w-4 h-4 mr-2" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                        <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                    </svg>
                    Restore System
                </button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeRestoreModal()">Cancel</button>
            </div>
        </div>
    </div>
</div>
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
            toastr.error('Please confirm you understand the restore process');
            return;
        }
        
        performRestore(backupId, backupCurrent);
    });
});

// Modal functions
function openCreateBackupModal() {
    document.getElementById('createBackupModal').classList.remove('hidden');
}

function closeCreateBackupModal() {
    document.getElementById('createBackupModal').classList.add('hidden');
}

function openScheduleModal() {
    document.getElementById('scheduleModal').classList.remove('hidden');
}

function closeScheduleModal() {
    document.getElementById('scheduleModal').classList.add('hidden');
}

function openRestoreModal() {
    document.getElementById('restoreModal').classList.remove('hidden');
}

function closeRestoreModal() {
    document.getElementById('restoreModal').classList.add('hidden');
}

function createBackup() {
    const formData = new FormData($('#createBackupForm')[0]);
    
    closeCreateBackupModal();
    $('#backupProgress').show();
    
    $.ajax({
        url: '{{ route("backup.create") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
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
            toastr.error(xhr.responseJSON?.message || 'Backup creation failed');
        }
    });
}

function restoreBackup(backupId) {
    $('#restoreBackupId').val(backupId);
    openRestoreModal();
}

function performRestore(backupId, backupCurrent) {
    closeRestoreModal();
    
    $.ajax({
        url: `/admin/backup/${backupId}/restore`,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
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
            toastr.error(xhr.responseJSON?.message || 'Restore failed');
        }
    });
}

function deleteBackup(backupId) {
    if (!confirm('Are you sure you want to delete this backup? This action cannot be undone.')) {
        return;
    }
    
    $.ajax({
        url: `/admin/backup/${backupId}`,
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
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
            toastr.error(xhr.responseJSON?.message || 'Delete failed');
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
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                closeScheduleModal();
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Schedule update failed');
        }
    });
}

function cleanupOldBackups() {
    const days = prompt('Delete backups older than how many days?', '30');
    if (!days || isNaN(days)) {
        return;
    }
    
    $.ajax({
        url: '{{ route("backup.cleanup") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            older_than_days: parseInt(days),
            keep_minimum: 5
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                refreshBackupList();
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Cleanup failed');
        }
    });
}

function refreshBackupList() {
    window.location.reload();
}

function updateStorageInfo() {
    // Update storage information after backup operations
    // This would typically fetch updated storage info via AJAX
}
</script>
@endpush