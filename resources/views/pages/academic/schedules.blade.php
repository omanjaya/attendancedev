@extends('layouts.app')

@section('title', 'Manajemen Jadwal Akademik')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Page Header -->
    <div class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <nav class="flex" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-3">
                            <li class="inline-flex items-center">
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                                    <svg class="w-3 h-3 mr-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                                    </svg>
                                    Dashboard
                                </a>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                    </svg>
                                    <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">Akademik</span>
                                </div>
                            </li>
                            <li aria-current="page">
                                <div class="flex items-center">
                                    <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                    </svg>
                                    <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">Jadwal</span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                </div>

                <!-- Import/Export Actions -->
                <div class="flex items-center space-x-3">
                    <button
                        id="import-btn"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                        </svg>
                        Import Jadwal
                    </button>

                    <button
                        id="template-btn"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Buat Template
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Title -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Manajemen Jadwal Akademik</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Kelola jadwal mata pelajaran untuk semua kelas dengan sistem drag & drop yang mudah
                    </p>
                </div>
                
                <!-- Statistics Cards -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 text-center">
                        <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $statistics['total_schedules'] ?? 0 }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Jadwal</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 text-center">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $statistics['active_classes'] ?? 0 }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Kelas Aktif</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 text-center">
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $statistics['total_conflicts'] ?? 0 }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Konflik</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schedule Grid Component -->
        <div id="schedule-app">
            <schedule-grid
                :academic-classes="academicClasses"
                :subjects="subjects"
            ></schedule-grid>
        </div>
    </div>

    <!-- Import Modal -->
    <div id="import-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            
            <!-- Modal Content -->
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="import-form" enctype="multipart/form-data">
                    @csrf
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    Import Jadwal
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <!-- Class Selection -->
                                    <div>
                                        <label for="import_class_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Pilih Kelas
                                        </label>
                                        <select id="import_class_id" name="class_id" required
                                                class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white">
                                            <option value="">-- Pilih Kelas --</option>
                                            @foreach($academicClasses as $class)
                                                <option value="{{ $class->id }}">{{ $class->full_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- File Upload -->
                                    <div>
                                        <label for="import_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            File JSON
                                        </label>
                                        <input type="file" id="import_file" name="file" accept=".json" required
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white">
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Upload file JSON yang berisi data jadwal</p>
                                    </div>

                                    <!-- Replace Option -->
                                    <div class="flex items-center">
                                        <input id="replace_existing" name="replace_existing" type="checkbox" 
                                               class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded">
                                        <label for="replace_existing" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                                            Ganti jadwal yang sudah ada
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" 
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-emerald-600 text-base font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Import
                        </button>
                        <button type="button" id="cancel-import"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Pass data to Vue components
window.scheduleData = {
    academicClasses: @json($academicClasses),
    subjects: @json($subjects),
    csrfToken: '{{ csrf_token() }}'
};

// Import modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const importBtn = document.getElementById('import-btn');
    const importModal = document.getElementById('import-modal');
    const cancelImport = document.getElementById('cancel-import');
    const importForm = document.getElementById('import-form');

    // Show import modal
    importBtn.addEventListener('click', function() {
        importModal.classList.remove('hidden');
    });

    // Hide import modal
    function hideImportModal() {
        importModal.classList.add('hidden');
    }

    cancelImport.addEventListener('click', hideImportModal);

    // Hide modal when clicking outside
    importModal.addEventListener('click', function(e) {
        if (e.target === importModal) {
            hideImportModal();
        }
    });

    // Handle import form submission
    importForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(importForm);
        
        try {
            const response = await fetch('/api/academic-schedules/import', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await response.json();

            if (data.success) {
                alert(`Import berhasil! ${data.imported_count} jadwal diimpor, ${data.skipped_count} dilewati.`);
                hideImportModal();
                window.location.reload(); // Refresh page to show new data
            } else {
                alert(data.message || 'Import gagal');
            }
        } catch (error) {
            console.error('Import error:', error);
            alert('Terjadi kesalahan saat mengimpor jadwal');
        }
    });

    // Template button
    document.getElementById('template-btn').addEventListener('click', function() {
        // This could open a template creation modal
        alert('Fitur template akan segera tersedia!');
    });
});
</script>

@vite(['resources/js/components/schedule-app.js'])
@endpush

@push('styles')
<style>
/* Custom styles for schedule management */
.schedule-grid-container {
    transition: all 0.3s ease;
}

/* Loading animation */
@keyframes spin {
    to { transform: rotate(360deg); }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* Drag and drop visual feedback */
.drag-over {
    background-color: rgba(34, 197, 94, 0.1);
    border-color: rgba(34, 197, 94, 0.5);
}

.dragging {
    opacity: 0.5;
    transform: rotate(5deg);
}

/* Custom scrollbar for better UX */
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Dark mode scrollbar */
.dark .custom-scrollbar::-webkit-scrollbar-track {
    background: #374151;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #6b7280;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>
@endpush