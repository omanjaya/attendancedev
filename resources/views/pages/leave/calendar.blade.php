@extends('layouts.authenticated-unified')

@section('title', 'Kalender Cuti')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Kalender Cuti"
            subtitle="Ikhtisar visual permintaan cuti Anda"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Cuti', 'url' => route('leave.index')],
                ['label' => 'Kalender']
            ]">
            <x-slot name="actions">
                <x-ui.button variant="secondary" href="{{ route('leave.index') }}">
                    <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Kelola Permintaan
                </x-ui.button>
                <x-ui.button variant="primary" href="{{ route('leave.create') }}">
                    <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Ajukan Cuti
                </x-ui.button>
                @can('approve_leave')
                <x-ui.button variant="success" href="{{ route('leave.approvals.index') }}">
                    <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l5 5 5-5"/></svg>
                    Tampilan Manajer
                </x-ui.button>
                @endcan
            </x-slot>
        </x-layouts.base-page>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Total Cuti</p>
                        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1" id="total-leaves">-</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                </div>
            </div>
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Disetujui</p>
                        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1" id="approved-leaves">-</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Tertunda</p>
                        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1" id="pending-leaves">-</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Aktif Sekarang</p>
                        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1" id="active-leaves">-</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                <div>
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white">Kalender Cuti Saya</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Tampilan kalender interaktif permintaan cuti Anda</p>
                </div>
                
                <div class="flex flex-col sm:flex-row items-end gap-4">
                    <div class="space-y-2">
                        <x-ui.label for="leave-type-filter" value="Tipe Cuti" class="text-slate-700 dark:text-slate-300" />
                        <x-ui.select id="leave-type-filter" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                            <option value="">Semua Tipe</option>
                            @foreach($leaveTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                    <div class="space-y-2">
                        <x-ui.label for="status-filter" value="Status" class="text-slate-700 dark:text-slate-300" />
                        <x-ui.select id="status-filter" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                            <option value="">Semua Status</option>
                            <option value="pending">Tertunda</option>
                            <option value="approved">Disetujui</option>
                            <option value="rejected">Ditolak</option>
                            <option value="cancelled">Dibatalkan</option>
                        </x-ui.select>
                    </div>
                </div>
            </div>
            
            <div class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-4 bg-white/10 rounded-lg">
                    <div class="flex flex-wrap gap-4 text-slate-600 dark:text-slate-400">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                            <span class="text-sm">Tertunda</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-green-500"></div>
                            <span class="text-sm">Disetujui</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-500"></div>
                            <span class="text-sm">Ditolak</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-slate-500"></div>
                            <span class="text-sm">Dibatalkan</span>
                        </div>
                    </div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Klik pada cuti untuk melihat detail</p>
                </div>
                
                <div id="leave-calendar" class="[&_.fc]:text-slate-800 dark:[&_.fc]:text-white [&_.fc-theme-standard_td]:border-white/20 [&_.fc-theme-standard_th]:border-white/20">
                    <!-- FullCalendar will render here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leave Details Modal -->
<x-ui.modal id="leaveDetailsModal" title="Detail Cuti" size="2xl">
    <div id="leave-details-content" class="mb-6 text-slate-800 dark:text-white">
        <!-- Content will be populated dynamically -->
    </div>
    
    <x-slot name="footer">
        <x-ui.button type="button" variant="secondary" onclick="closeModal('leaveDetailsModal')">
            Tutup
        </x-ui.button>
        <div id="leave-actions" class="flex gap-2">
            <!-- Action buttons will be populated dynamically -->
        </div>
    </x-slot>
</x-ui.modal>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.8/main.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.8/main.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@6.1.8/main.min.css" rel="stylesheet">
<style>
    /* FullCalendar theming */
    .fc-toolbar {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 0.75rem;
        padding: 1rem;
        margin-bottom: 1rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .fc-button-primary {
        background-color: #3b82f6 !important; /* blue-500 */
        border-color: #3b82f6 !important;
        color: white !important;
        transition: all 0.2s ease-in-out;
    }
    .fc-button-primary:hover {
        background-color: #2563eb !important; /* blue-600 */
        border-color: #2563eb !important;
    }
    .fc-button-primary:not(:disabled):active {
        background-color: #1d4ed8 !important; /* blue-700 */
        border-color: #1d4ed8 !important;
    }
    .fc-event {
        cursor: pointer;
        border-radius: 0.375rem;
        border: 1px solid rgba(255, 255, 255, 0.3);
        background-color: rgba(255, 255, 255, 0.1) !important;
        color: #1e293b !important; /* slate-800 */
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        transition: all 0.2s ease;
    }
    .fc-event:hover {
        opacity: 0.9;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    .fc-daygrid-event {
        font-size: 0.75rem;
        padding: 2px 4px;
    }
    .fc-col-header {
        background-color: rgba(255, 255, 255, 0.1) !important;
    }
    .fc-day-today {
        background-color: rgba(59, 130, 246, 0.1) !important; /* blue-500 with opacity */
    }
    .fc-daygrid-day:hover {
        background-color: rgba(255, 255, 255, 0.15) !important;
    }
    .fc-event-success {
        background-color: rgba(16, 185, 129, 0.8) !important; /* emerald-500 */
        border-color: rgba(16, 185, 129, 0.9) !important;
    }
    .fc-event-warning {
        background-color: rgba(251, 191, 36, 0.8) !important; /* amber-500 */
        border-color: rgba(251, 191, 36, 0.9) !important;
    }
    .fc-event-destructive {
        background-color: rgba(239, 68, 68, 0.8) !important; /* red-500 */
        border-color: rgba(239, 68, 68, 0.9) !important;
    }
    .fc-event-info {
        background-color: rgba(59, 130, 246, 0.8) !important; /* blue-500 */
        border-color: rgba(59, 130, 246, 0.9) !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.8/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.8/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@6.1.8/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/interaction@6.1.8/main.min.js"></script>

<script>
// Modal helper functions
function showModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize calendar
    const calendarEl = document.getElementById('leave-calendar');
    let calendar;
    
    // Load stats
    loadLeaveStats();
    
    // Initialize calendar
    initializeCalendar();
    
    // Filter event handlers
    $('#leave-type-filter, #status-filter').on('change', function() {
        calendar.refetchEvents();
    });
    
    function initializeCalendar() {
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listWeek'
            },
            height: 'auto',
            weekends: true,
            businessHours: {
                daysOfWeek: [1, 2, 3, 4, 5], // Monday - Friday
                startTime: '08:00',
                endTime: '17:00',
            },
            events: function(fetchInfo, successCallback, failureCallback) {
                const filters = {
                    start: fetchInfo.startStr,
                    end: fetchInfo.endStr,
                    leave_type_id: $('#leave-type-filter').val(),
                    status: $('#status-filter').val()
                };
                
                $.ajax({
                    url: '{{ route("leave.calendar.data") }}',
                    type: 'GET',
                    data: filters,
                    success: function(data) {
                        successCallback(data);
                    },
                    error: function() {
                        failureCallback();
                        toastr.error('Gagal memuat data cuti');
                    }
                });
            },
            eventClick: function(info) {
                showLeaveDetails(info.event.extendedProps.leave_id);
            },
            eventDidMount: function(info) {
                // Add tooltip
                const event = info.event;
                const props = event.extendedProps;
                info.el.setAttribute('title', 
                    `${props.leave_type_name}\n` +
                    `${props.date_range}\n` +
                    `Status: ${props.status.charAt(0).toUpperCase() + props.status.slice(1)}\n` +
                    `Durasi: ${props.duration}`
                );
            },
            dayHeaderFormat: { weekday: 'short' },
            displayEventTime: false,
            eventDisplay: 'block'
        });
        
        calendar.render();
    }
    
    function loadLeaveStats() {
        $.ajax({
            url: '{{ route("leave.calendar.stats") }}',
            type: 'GET',
            success: function(data) {
                $('#total-leaves').text(data.total_leaves);
                $('#approved-leaves').text(data.approved_leaves);
                $('#pending-leaves').text(data.pending_leaves);
                $('#active-leaves').text(data.active_leaves);
            },
            error: function() {
                console.error('Gagal memuat statistik cuti');
            }
        });
    }
    
    function showLeaveDetails(leaveId) {
        $.ajax({
            url: '{{ route("leave.calendar.details", ":id") }}'.replace(':id', leaveId),
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const leave = response.leave;
                    const content = `
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-slate-800 dark:text-white">
                            <div>
                                <h6 class="text-lg font-semibold mb-3">Informasi Cuti</h6>
                                <dl class="space-y-2">
                                    <div class="flex justify-between"><dt class="font-medium text-slate-600 dark:text-slate-400">Tipe Cuti:</dt><dd><span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white bg-gradient-to-r from-blue-500 to-purple-600 shadow-lg">${leave.leave_type}</span></dd></div>
                                    <div class="flex justify-between"><dt class="font-medium text-slate-600 dark:text-slate-400">Status:</dt><dd><span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white bg-gradient-to-r ${getLeaveStatusColorClass(leave.status)} shadow-lg">${leave.status.charAt(0).toUpperCase() + leave.status.slice(1)}</span></dd></div>
                                    <div class="flex justify-between"><dt class="font-medium text-slate-600 dark:text-slate-400">Durasi:</dt><dd>${leave.duration}</dd></div>
                                    <div class="flex justify-between"><dt class="font-medium text-slate-600 dark:text-slate-400">Rentang Tanggal:</dt><dd>${leave.date_range}</dd></div>
                                    <div class="flex justify-between"><dt class="font-medium text-slate-600 dark:text-slate-400">Hari Diminta:</dt><dd>${leave.days_requested}</dd></div>
                                    ${leave.is_emergency ? `<div class="flex justify-between"><dt class="font-medium text-slate-600 dark:text-slate-400">Darurat:</dt><dd><span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white bg-gradient-to-r from-red-500 to-rose-600 shadow-lg">Ya</span></dd></div>` : ''}
                                    ${leave.approved_by ? `<div class="flex justify-between"><dt class="font-medium text-slate-600 dark:text-slate-400">Disetujui Oleh:</dt><dd>${leave.approved_by}</dd></div>` : ''}
                                    ${leave.approved_at ? `<div class="flex justify-between"><dt class="font-medium text-slate-600 dark:text-slate-400">Disetujui Pada:</dt><dd>${leave.approved_at}</dd></div>` : ''}
                                    <div class="flex justify-between"><dt class="font-medium text-slate-600 dark:text-slate-400">Diminta:</dt><dd>${leave.created_at}</dd></div>
                                </dl>
                            </div>
                            <div>
                                <h6 class="text-lg font-semibold mb-3">Detail Tambahan</h6>
                                ${leave.reason ? `<div class="mb-3"><p class="font-medium text-slate-600 dark:text-slate-400">Alasan:</p><p>${leave.reason}</p></div>` : ''}
                                ${leave.approval_notes ? `<div class="mb-3"><p class="font-medium text-slate-600 dark:text-slate-400">Catatan Persetujuan:</p><p>${leave.approval_notes}</p></div>` : ''}
                                ${leave.rejection_reason ? `<div class="mb-3"><p class="font-medium text-slate-600 dark:text-slate-400">Alasan Penolakan:</p><p>${leave.rejection_reason}</p></div>` : ''}
                            </div>
                        </div>
                    `;
                    
                    $('#leave-details-content').html(content);
                    
                    let actions = '';
                    if (leave.can_be_cancelled) {
                        actions += `<x-ui.button variant="destructive" onclick="cancelLeave('${leave.id}')">Batalkan Cuti</x-ui.button>`;
                    }
                    actions += `<x-ui.button variant="secondary" href="{{ route("leave.show", ":id") }}".replace(':id', leave.id)>Lihat Detail Lengkap</x-ui.button>`;
                    
                    $('#leave-actions').html(actions);
                    
                    showModal('leaveDetailsModal');
                } else {
                    toastr.error('Gagal memuat detail cuti');
                }
            },
            error: function() {
                toastr.error('Gagal memuat detail cuti');
            }
        });
    }
    
    function getLeaveStatusColorClass(status) {
        switch(status) {
            case 'approved': return 'from-green-500 to-emerald-600';
            case 'pending': return 'from-amber-500 to-orange-600';
            case 'rejected': return 'from-red-500 to-rose-600';
            case 'cancelled': return 'from-gray-500 to-slate-600';
            default: return 'from-gray-500 to-slate-600';
        }
    }

    // Auto-refresh calendar and stats every 5 minutes
    setInterval(function() {
        calendar.refetchEvents();
        loadLeaveStats();
    }, 300000);
});

function cancelLeave(leaveId) {
    if (confirm('Apakah Anda yakin ingin membatalkan permintaan cuti ini?')) {
        $.ajax({
            url: '{{ route("leave.cancel", ":id") }}'.replace(':id', leaveId),
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Permintaan cuti berhasil dibatalkan');
                    closeModal('leaveDetailsModal');
                    calendar.refetchEvents();
                    loadLeaveStats();
                } else {
                    toastr.error(response.message || 'Gagal membatalkan permintaan cuti');
                }
            },
            error: function() {
                toastr.error('Gagal membatalkan permintaan cuti');
            }
        });
    }
}
</script>
@endpush
