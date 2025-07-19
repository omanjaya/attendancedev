@extends('layouts.authenticated-unified')

@section('title', 'Penyusunan Jadwal Mengajar Guru')

@section('page-content')
<x-layouts.page-base 
    title="Penyusunan Jadwal Mengajar Guru"
    subtitle="Sistem manajemen jadwal dengan interface interaktif"
    :show-background="true"
    :show-welcome="false">

    <!-- Breadcrumb -->
    <nav class="mb-8" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm">
            <li>
                <a href="{{ route('dashboard') }}" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                </a>
            </li>
            <li class="flex items-center">
                <svg class="h-5 w-5 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <a href="{{ route('schedules.index') }}" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">Schedules</a>
            </li>
            <li class="flex items-center">
                <svg class="h-5 w-5 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="text-gray-900 dark:text-gray-100 font-medium">Calendar View</span>
            </li>
        </ol>
    </nav>

    <div class="max-w-full space-y-8">
        <!-- Quick Actions Header -->
        <x-layouts.simple-card class="p-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Quick Actions
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Buat jadwal baru atau import data existing</p>
                </div>
                <div class="flex items-center gap-3">
                    <x-ui.button variant="default" size="default">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Jadwal Baru
                    </x-ui.button>
                    
                    <x-ui.button variant="outline" size="default">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Import Excel
                    </x-ui.button>
                </div>
            </div>
        </x-layouts.simple-card>

        <!-- Teacher Selection -->
        <x-layouts.simple-card class="p-6">
            <div class="mb-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Teacher Selection
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Pilih guru yang akan dijadwalkan</p>
            </div>
            
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <label class="text-sm font-medium text-gray-900 dark:text-white">Masukkan Kode Guru:</label>
                <div class="flex items-center gap-3">
                    <x-ui.input 
                        id="teacher-code-input"
                        placeholder="Contoh: JIN_28_3C"
                        class="w-full sm:w-64"
                    />
                    <x-ui.button variant="info" size="default" id="set-teacher-btn">
                        Set Kode
                    </x-ui.button>
                    
                    <x-ui.button variant="destructive" size="default" id="logout-btn">
                        Logout
                    </x-ui.button>
                </div>
            </div>
            <div class="mt-4 p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg border border-emerald-200 dark:border-emerald-700/30">
                <span class="text-sm text-emerald-700 dark:text-emerald-300">
                    Kode Guru Saat Ini: 
                    <strong id="current-teacher" class="text-emerald-800 dark:text-emerald-200">BELUM DISET</strong>
                </span>
            </div>
        </x-layouts.simple-card>

        <!-- Action Buttons -->
        <x-layouts.simple-card class="p-6">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Manage Schedules</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300">Save, load, dan export jadwal yang sudah dibuat</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button variant="success" size="default" id="save-json-btn">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    Simpan Jadwal (JSON)
                </x-ui.button>
                
                <x-ui.button variant="success" size="default" id="load-json-btn">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    Buat Jadwal (JSON)
                </x-ui.button>
                
                <x-ui.button variant="info" size="default" id="export-excel-btn">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export ke Excel
                </x-ui.button>
                
                <x-ui.button variant="secondary" size="default" id="undo-btn">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                    </svg>
                    Undo (Ctrl+Z)
                </x-ui.button>
                
                <x-ui.button variant="destructive" size="default" id="reset-btn">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Reset Data
                </x-ui.button>
            </div>
        </x-layouts.simple-card>

        <!-- Schedule Grid -->
        <x-layouts.simple-card padding="none" class="overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse" style="min-width: 1000px;">
                <!-- Header -->
                <thead>
                    <tr class="bg-gradient-to-r from-emerald-500 to-teal-500">
                        <th rowspan="2" class="px-4 py-4 text-white font-bold text-center border-r border-white/30 w-24">
                            HARI
                        </th>
                        <th colspan="10" class="px-4 py-3 text-white font-bold text-center border-r border-white/30">
                            Kelas X
                        </th>
                        <th colspan="10" class="px-4 py-3 text-white font-bold text-center border-r border-white/30">
                            Kelas XI
                        </th>
                        <th colspan="10" class="px-4 py-3 text-white font-bold text-center">
                            Kelas XII
                        </th>
                    </tr>
                    <tr class="bg-gradient-to-r from-emerald-400 to-teal-400">
                        <!-- Kelas X -->
                        @for($i = 1; $i <= 10; $i++)
                        <th class="px-2 py-2 text-white font-medium text-center border-r border-white/30 min-w-[60px]">
                            {{ $i }}
                        </th>
                        @endfor
                        <!-- Kelas XI -->
                        @for($i = 1; $i <= 10; $i++)
                        <th class="px-2 py-2 text-white font-medium text-center border-r border-white/30 min-w-[60px]">
                            {{ $i }}
                        </th>
                        @endfor
                        <!-- Kelas XII -->
                        @for($i = 1; $i <= 10; $i++)
                        <th class="px-2 py-2 text-white font-medium text-center border-r border-white/30 min-w-[60px]">
                            {{ $i }}
                        </th>
                        @endfor
                    </tr>
                </thead>
                
                <!-- Body -->
                <tbody>
                    @foreach(['SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT'] as $day)
                    <tr class="hover:bg-white/20 dark:hover:bg-gray-700/30 transition-colors">
                        <!-- Day Column -->
                        <td class="px-4 py-4 font-bold text-center bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 text-gray-800 dark:text-gray-200 border-r border-gray-300 dark:border-gray-600">
                            {{ $day }}
                        </td>
                        
                        <!-- Schedule Cells -->
                        @for($i = 1; $i <= 30; $i++)
                        <td class="p-1 border-r border-b border-gray-200 dark:border-gray-600 relative">
                            <div class="h-12 flex items-center justify-center cursor-pointer transition-all duration-200 rounded hover:bg-emerald-100 dark:hover:bg-emerald-900/30 border border-transparent hover:border-emerald-300 dark:hover:border-emerald-600"
                                 onclick="handleCellClick(this)"
                            >
                                <span class="text-xs text-gray-400 dark:text-gray-500">+</span>
                            </div>
                        </td>
                        @endfor
                    </tr>
                    @endforeach
                </tbody>

                <!-- Footer with totals -->
                <tfoot>
                    <tr class="bg-gradient-to-r from-gray-600 to-gray-700">
                        <td class="px-4 py-3 font-bold text-white text-center border-r border-gray-500">
                            TOTAL JAM GURU
                        </td>
                        @for($i = 1; $i <= 30; $i++)
                        <td class="px-2 py-3 text-white text-center text-xs border-r border-gray-500">
                            0
                        </td>
                        @endfor
                    </tr>
                    <tr class="bg-gradient-to-r from-gray-700 to-gray-800">
                        <td class="px-4 py-3 font-bold text-white text-center border-r border-gray-600">
                            TOTAL JAM KELAS
                        </td>
                        @for($i = 1; $i <= 30; $i++)
                        <td class="px-2 py-3 text-white text-center text-xs border-r border-gray-600">
                            0
                        </td>
                        @endfor
                    </tr>
                </tfoot>
            </table>
        </div>
        </x-layouts.simple-card>

        <!-- Instructions -->
        <x-layouts.simple-card class="p-6">
            <div class="mb-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Cara Penggunaan
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Panduan lengkap menggunakan sistem jadwal</p>
            </div>
            <ol class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                <li class="flex items-start">
                    <span class="bg-emerald-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-3 mt-0.5 flex-shrink-0">1</span>
                    <div>
                        <span class="font-medium text-gray-900 dark:text-gray-100">Set Teacher Code:</span><br>
                        Masukkan kode guru di kolom "Masukkan Kode Guru" dan klik "Set Kode" atau tekan Enter.
                    </div>
                </li>
                <li class="flex items-start">
                    <span class="bg-emerald-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-3 mt-0.5 flex-shrink-0">2</span>
                    <div>
                        <span class="font-medium text-gray-900 dark:text-gray-100">Assign Schedule:</span><br>
                        Klik pada kotak kosong di jadwal untuk menjadwalkan kode guru saat ini.
                    </div>
                </li>
                <li class="flex items-start">
                    <span class="bg-emerald-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-3 mt-0.5 flex-shrink-0">3</span>
                    <div>
                        <span class="font-medium text-gray-900 dark:text-gray-100">Remove Schedule:</span><br>
                        Klik pada kotak berisi untuk menghapus kode guru dari jadwal.
                    </div>
                </li>
                <li class="flex items-start">
                    <span class="bg-emerald-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-3 mt-0.5 flex-shrink-0">4</span>
                    <div>
                        <span class="font-medium text-gray-900 dark:text-gray-100">Undo Changes:</span><br>
                        Gunakan "Ctrl+Z" atau tombol Undo untuk membatalkan perubahan terakhir.
                    </div>
                </li>
                <li class="flex items-start">
                    <span class="bg-emerald-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-3 mt-0.5 flex-shrink-0">5</span>
                    <div>
                        <span class="font-medium text-gray-900 dark:text-gray-100">Auto Save:</span><br>
                        Jadwal otomatis tersimpan di browser. Gunakan fitur export/import untuk backup.
                    </div>
                </li>
            </ol>
        </x-layouts.simple-card>
    </div>
</x-layouts.page-base>

<script>
// Simple JavaScript implementation without Vue.js
let currentTeacher = '';
let scheduleData = {};
let actionHistory = [];

// Load from localStorage
function loadSchedule() {
    const saved = localStorage.getItem('schedule_data');
    if (saved) {
        const data = JSON.parse(saved);
        scheduleData = data.schedule || {};
        currentTeacher = data.currentTeacher || '';
        actionHistory = data.history || [];
        document.getElementById('current-teacher').textContent = currentTeacher || 'BELUM DISET';
        renderSchedule();
    }
}

// Save to localStorage
function saveSchedule() {
    const data = {
        schedule: scheduleData,
        currentTeacher: currentTeacher,
        history: actionHistory,
        savedAt: new Date().toISOString()
    };
    localStorage.setItem('schedule_data', JSON.stringify(data));
}

// Set teacher code
document.getElementById('set-teacher-btn').addEventListener('click', function() {
    const input = document.getElementById('teacher-code-input');
    if (input.value.trim()) {
        currentTeacher = input.value.trim();
        document.getElementById('current-teacher').textContent = currentTeacher;
        input.value = '';
        saveSchedule();
    }
});

// Enter key support
document.getElementById('teacher-code-input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        document.getElementById('set-teacher-btn').click();
    }
});

// Action buttons
document.getElementById('save-json-btn').addEventListener('click', exportJSON);
document.getElementById('load-json-btn').addEventListener('click', importJSON);
document.getElementById('export-excel-btn').addEventListener('click', exportExcel);
document.getElementById('undo-btn').addEventListener('click', undoAction);
document.getElementById('reset-btn').addEventListener('click', resetSchedule);
document.getElementById('logout-btn').addEventListener('click', logout);

// Handle cell click
function handleCellClick(cell) {
    if (!currentTeacher) {
        alert('Silakan set kode guru terlebih dahulu!');
        return;
    }
    
    const cellKey = generateCellKey(cell);
    
    if (scheduleData[cellKey]) {
        // Remove existing
        actionHistory.push({
            type: 'remove',
            key: cellKey,
            value: scheduleData[cellKey]
        });
        delete scheduleData[cellKey];
        cell.innerHTML = '<span class="text-xs text-gray-400 dark:text-gray-500">+</span>';
        cell.className = cell.className.replace(/bg-emerald-\d+/g, '').replace(/text-white/g, '');
    } else {
        // Add new
        actionHistory.push({
            type: 'add',
            key: cellKey,
            value: currentTeacher
        });
        scheduleData[cellKey] = currentTeacher;
        cell.innerHTML = `<span class="text-xs font-bold text-white">${currentTeacher}</span>`;
        cell.className += ' bg-emerald-500 text-white';
    }
    
    saveSchedule();
    updateTotals();
}

// Generate unique key for each cell
function generateCellKey(cell) {
    const row = cell.closest('tr');
    const tbody = row.closest('tbody');
    const dayIndex = Array.from(tbody.children).indexOf(row);
    const cellIndex = Array.from(row.children).indexOf(cell) - 1; // -1 for day column
    return `day-${dayIndex}-cell-${cellIndex}`;
}

// Update totals
function updateTotals() {
    // This would calculate totals - simplified for now
    console.log('Updating totals...');
}

// Render existing schedule
function renderSchedule() {
    // This would render existing data - simplified for now
    console.log('Rendering schedule...');
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'z') {
        e.preventDefault();
        undoAction();
    }
});

function undoAction() {
    if (actionHistory.length === 0) return;
    
    const lastAction = actionHistory.pop();
    // Implement undo logic
    console.log('Undoing action:', lastAction);
    saveSchedule();
}

function exportJSON() {
    const exportData = {
        schedule: scheduleData,
        currentTeacher: currentTeacher,
        exported_at: new Date().toISOString()
    };
    
    const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `jadwal_${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function importJSON() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.json';
    
    input.onchange = (e) => {
        const file = e.target.files[0];
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const data = JSON.parse(e.target.result);
                
                if (confirm('Import jadwal baru? Data sekarang akan diganti.')) {
                    scheduleData = data.schedule || {};
                    currentTeacher = data.currentTeacher || '';
                    document.getElementById('current-teacher').textContent = currentTeacher || 'BELUM DISET';
                    renderSchedule();
                    saveSchedule();
                }
            } catch (error) {
                alert('File JSON tidak valid!');
            }
        };
        reader.readAsText(file);
    };
    
    input.click();
}

function exportExcel() {
    alert('Export Excel akan segera tersedia dengan library SheetJS');
}

function resetSchedule() {
    if (confirm('Apakah Anda yakin ingin menghapus semua jadwal?')) {
        scheduleData = {};
        actionHistory = [];
        currentTeacher = '';
        document.getElementById('current-teacher').textContent = 'BELUM DISET';
        
        // Clear all cells
        document.querySelectorAll('.schedule-cell div').forEach(cell => {
            cell.innerHTML = '<span class="text-xs text-gray-400 dark:text-gray-500">+</span>';
            cell.className = cell.className.replace(/bg-emerald-\d+/g, '').replace(/text-white/g, '');
        });
        
        saveSchedule();
    }
}

function logout() {
    if (confirm('Logout dari sistem?')) {
        // Create form to submit logout
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("logout") }}';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadSchedule();
});
</script>
@endsection