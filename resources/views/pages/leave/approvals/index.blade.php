@extends('layouts.authenticated-unified')

@section('title', 'Persetujuan Cuti')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Persetujuan Cuti"
            subtitle="Manajemen Cuti - Tinjau dan setujui permintaan cuti"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Cuti', 'url' => route('leave.index')],
                ['label' => 'Persetujuan']
            ]">
            <x-slot name="actions">
                <x-ui.button variant="secondary" href="{{ route('leave.calendar.manager') }}">
                    <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Tampilan Kalender
                </x-ui.button>
                <x-ui.button variant="primary" id="bulk-approve-btn" disabled>
                    <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12l5 5l10 -10"/></svg>
                    Setujui Massal (<span id="bulkCount">0</span>)
                </x-ui.button>
                <x-ui.button variant="secondary" href="{{ route('leave.analytics') }}">
                    <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/><path d="M9 12l2 2l4 -4"/></svg>
                    Analitik
                </x-ui.button>
            </x-slot>
        </x-layouts.base-page>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Permintaan Tertunda</p>
                        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">{{ $stats['pending_requests'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <p class="text-xs text-slate-600 dark:text-slate-400 mt-2">Menunggu persetujuan Anda</p>
            </div>
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Darurat</p>
                        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">{{ $stats['emergency_requests'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-rose-600 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v.01"/><path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/></svg>
                    </div>
                </div>
                <p class="text-xs text-slate-600 dark:text-slate-400 mt-2">Permintaan prioritas tinggi</p>
            </div>
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Disetujui</p>
                        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">{{ $stats['approved_this_month'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12l5 5l10 -10"/></svg>
                    </div>
                </div>
                <p class="text-xs text-slate-600 dark:text-slate-400 mt-2">Bulan ini</p>
            </div>
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Total Permintaan</p>
                        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">{{ $stats['total_this_month'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"/><path d="M16 3l0 4"/><path d="M8 3l0 4"/><path d="M4 11l16 0"/></svg>
                    </div>
                </div>
                <p class="text-xs text-slate-600 dark:text-slate-400 mt-2">Bulan ini</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="col-span-12 group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white">Permintaan Cuti untuk Persetujuan</h3>
                    <div class="inline-flex rounded-md shadow-sm" role="group">
                        <input type="radio" class="hidden" name="status-filter" id="all" value="" checked>
                        <label for="all" class="px-3 py-1.5 text-xs font-medium rounded-l-md cursor-pointer transition-colors text-slate-700 dark:text-slate-300 bg-white/50 hover:bg-white/70">Semua</label>
                        
                        <input type="radio" class="hidden" name="status-filter" id="pending" value="pending">
                        <label for="pending" class="px-3 py-1.5 text-xs font-medium cursor-pointer transition-colors text-slate-700 dark:text-slate-300 bg-white/50 hover:bg-white/70">Tertunda</label>
                        
                        <input type="radio" class="hidden" name="status-filter" id="approved" value="approved">
                        <label for="approved" class="px-3 py-1.5 text-xs font-medium cursor-pointer transition-colors text-slate-700 dark:text-slate-300 bg-white/50 hover:bg-white/70">Disetujui</label>
                        
                        <input type="radio" class="hidden" name="status-filter" id="rejected" value="rejected">
                        <label for="rejected" class="px-3 py-1.5 text-xs font-medium rounded-r-md cursor-pointer transition-colors text-slate-700 dark:text-slate-300 bg-white/50 hover:bg-white/70">Ditolak</label>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table id="approvalsTable" class="min-w-full divide-y divide-white/20">
                        <thead class="bg-white/10 backdrop-blur-sm">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                                    <input type="checkbox" id="select-all" class="form-checkbox h-4 w-4 text-blue-600 rounded"/>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Karyawan</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Detail Cuti</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Prioritas</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Dikirim</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider w-8">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Approval Modal -->
<x-ui.modal id="quickApprovalModal" title="Persetujuan Cepat">
    <form id="quickApprovalForm">
        <input type="hidden" id="approvalLeaveId">
        <div class="mb-3">
            <x-ui.label for="approvalNotes" class="text-slate-700 dark:text-slate-300">Catatan Persetujuan (Opsional)</x-ui.label>
            <textarea id="approvalNotes" rows="3" placeholder="Tambahkan catatan tentang persetujuan ini..." class="w-full px-3 py-2 border border-white/40 rounded-md shadow-sm focus:outline-none focus:ring-blue-500/50 focus:border-blue-500/50 sm:text-sm bg-white/30 backdrop-blur-sm text-slate-800 dark:text-white"></textarea>
        </div>
    </form>
    <x-slot name="footer">
        <x-ui.button type="button" variant="secondary" onclick="closeModal('quickApprovalModal')">Batal</x-ui.button>
        <x-ui.button type="button" variant="success" id="confirmApproval">
            <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12l5 5l10 -10"/></svg>
            Setujui Permintaan
        </x-ui.button>
    </x-slot>
</x-ui.modal>

<!-- Quick Rejection Modal -->
<x-ui.modal id="quickRejectionModal" title="Tolak Permintaan Cuti">
    <form id="quickRejectionForm">
        <input type="hidden" id="rejectionLeaveId">
        <div class="mb-3">
            <x-ui.label for="rejectionReason" class="text-slate-700 dark:text-slate-300">Alasan Penolakan</x-ui.label>
            <textarea id="rejectionReason" rows="3" required placeholder="Mohon berikan alasan untuk menolak permintaan ini..." class="w-full px-3 py-2 border border-white/40 rounded-md shadow-sm focus:outline-none focus:ring-blue-500/50 focus:border-blue-500/50 sm:text-sm bg-white/30 backdrop-blur-sm text-slate-800 dark:text-white"></textarea>
        </div>
    </form>
    <x-slot name="footer">
        <x-ui.button type="button" variant="secondary" onclick="closeModal('quickRejectionModal')">Batal</x-ui.button>
        <x-ui.button type="button" variant="destructive" id="confirmRejection">
            <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            Tolak Permintaan
        </x-ui.button>
    </x-slot>
</x-ui.modal>

<!-- Bulk Approval Modal -->
<x-ui.modal id="bulkApprovalModal" title="Setujui Permintaan Massal">
    <p class="text-slate-600 dark:text-slate-400 mb-4">Anda akan menyetujui <span id="bulkCount">0</span> permintaan cuti.</p>
    <form id="bulkApprovalForm">
        <div class="mb-3">
            <x-ui.label for="bulkApprovalNotes" class="text-slate-700 dark:text-slate-300">Catatan Persetujuan (Opsional)</x-ui.label>
            <textarea id="bulkApprovalNotes" rows="3" placeholder="Tambahkan catatan yang akan berlaku untuk semua permintaan yang disetujui..." class="w-full px-3 py-2 border border-white/40 rounded-md shadow-sm focus:outline-none focus:ring-blue-500/50 focus:border-blue-500/50 sm:text-sm bg-white/30 backdrop-blur-sm text-slate-800 dark:text-white"></textarea>
        </div>
    </form>
    <x-slot name="footer">
        <x-ui.button type="button" variant="secondary" onclick="closeModal('bulkApprovalModal')">Batal</x-ui.button>
        <x-ui.button type="button" variant="success" id="confirmBulkApproval">
            <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12l5 5l10 -10"/></svg>
            Setujui Semua yang Dipilih
        </x-ui.button>
    </x-slot>
</x-ui.modal>
@endsection

@push('scripts')
<script>
// Modal helper functions
function showModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

$(document).ready(function() {
    // Initialize DataTable
    const table = $('#approvalsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("leave.approvals.data") }}',
            data: function(d) {
                d.status = $('input[name="status-filter"]:checked').val();
            }
        },
        columns: [
            { 
                data: null, 
                orderable: false, 
                searchable: false,
                width: '30px',
                render: function(data, type, row) {
                    if (row.status === 'pending') {
                        return '<input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 rounded" value="' + row.id + '">';
                    }
                    return '';
                }
            },
            { 
                data: 'employee_info', 
                name: 'employee.first_name',
                render: function(data, type, row) {
                    return `
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-sm font-bold">
                                ${row.employee_name.charAt(0).toUpperCase()}${row.employee_name.charAt(1).toUpperCase()}
                            </div>
                            <div>
                                <div class="font-medium text-slate-800 dark:text-white">${row.employee_name}</div>
                                <div class="text-sm text-slate-600 dark:text-slate-400">${row.employee_id}</div>
                            </div>
                        </div>
                    `;
                }
            },
            { 
                data: 'leave_details', 
                name: 'leave_type_id',
                render: function(data, type, row) {
                    return `
                        <div class="text-sm text-slate-800 dark:text-white">${row.leave_type_name}</div>
                        <div class="text-xs text-slate-600 dark:text-slate-400">${row.date_range} (${row.duration})</div>
                    `;
                }
            },
            { 
                data: 'priority', 
                name: 'priority',
                render: function(data) {
                    const colorClass = data === 'High' ? 'from-red-500 to-rose-600' : 'from-green-500 to-emerald-600';
                    return `<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white bg-gradient-to-r ${colorClass} shadow-lg">${data}</span>`;
                }
            },
            { 
                data: 'status_badge', 
                name: 'status',
                render: function(data, type, row) {
                    const colorClass = getLeaveStatusColorClass(row.status);
                    return `<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white bg-gradient-to-r ${colorClass} shadow-lg">${row.status.charAt(0).toUpperCase() + row.status.slice(1)}</span>`;
                }
            },
            { 
                data: 'submitted_date', 
                name: 'created_at',
                render: function(data, type, row) {
                    return `
                        <div class="text-sm text-slate-800 dark:text-white">${data}</div>
                        <div class="text-xs text-slate-600 dark:text-slate-400">${row.created_at_diff_for_humans}</div>
                    `;
                }
            },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    let buttons = '';
                    if (row.status === 'pending') {
                        buttons += `<x-ui.button variant="success" size="sm" class="approve-leave" data-id="${row.id}">Setujui</x-ui.button>`;
                        buttons += `<x-ui.button variant="destructive" size="sm" class="reject-leave" data-id="${row.id}">Tolak</x-ui.button>`;
                    }
                    buttons += `<x-ui.button variant="secondary" size="sm" href="/leave/approvals/${row.id}">Lihat</x-ui.button>`;
                    return `<div class="flex space-x-1">${buttons}</div>`;
                }
            }
        ],
        order: [[5, 'desc']] // Order by submitted date desc
    });
    
    // Status filter
    $('input[name="status-filter"]').on('change', function() {
        table.ajax.reload();
    });
    
    // Select all checkbox
    $('#select-all').on('change', function() {
        $('.form-checkbox').prop('checked', this.checked);
        updateBulkApproveButton();
    });
    
    // Individual checkbox change
    $(document).on('change', '.form-checkbox', function() {
        updateBulkApproveButton();
        
        // Update select all checkbox
        const allChecked = $('.form-checkbox').length === $('.form-checkbox:checked').length;
        $('#select-all').prop('checked', allChecked);
    });
    
    // Update bulk approve button state
    function updateBulkApproveButton() {
        const checkedCount = $('.form-checkbox:checked').length;
        $('#bulk-approve-btn').prop('disabled', checkedCount === 0);
        $('#bulkCount').text(checkedCount);
    }
    
    // Quick approve
    $(document).on('click', '.approve-leave', function() {
        const leaveId = $(this).data('id');
        $('#approvalLeaveId').val(leaveId);
        $('#approvalNotes').val('');
        showModal('quickApprovalModal');
    });
    
    // Quick reject
    $(document).on('click', '.reject-leave', function() {
        const leaveId = $(this).data('id');
        $('#rejectionLeaveId').val(leaveId);
        $('#rejectionReason').val('');
        showModal('quickRejectionModal');
    });
    
    // Bulk approve
    $('#bulk-approve-btn').on('click', function() {
        if ($('.form-checkbox:checked').length > 0) {
            showModal('bulkApprovalModal');
        }
    });
    
    // Confirm approval
    $('#confirmApproval').on('click', function() {
        const leaveId = $('#approvalLeaveId').val();
        const notes = $('#approvalNotes').val();
        
        approveLeave(leaveId, notes);
    });
    
    // Confirm rejection
    $('#confirmRejection').on('click', function() {
        const leaveId = $('#rejectionLeaveId').val();
        const reason = $('#rejectionReason').val();
        
        if (!reason.trim()) {
            toastr.error('Mohon berikan alasan penolakan');
            return;
        }
        
        rejectLeave(leaveId, reason);
    });
    
    // Confirm bulk approval
    $('#confirmBulkApproval').on('click', function() {
        const leaveIds = $('.form-checkbox:checked').map(function() {
            return this.value;
        }).get();
        const notes = $('#bulkApprovalNotes').val();
        
        bulkApproveLeaves(leaveIds, notes);
    });
    
    // Approve leave function
    function approveLeave(leaveId, notes) {
        $.ajax({
            url: `/leave/approvals/${leaveId}/approve`,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                approval_notes: notes
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    closeModal('quickApprovalModal');
                    table.ajax.reload();
                    updateBulkApproveButton();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan');
            }
        });
    }
    
    // Reject leave function
    function rejectLeave(leaveId, reason) {
        $.ajax({
            url: `/leave/approvals/${leaveId}/reject`,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                rejection_reason: reason
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    closeModal('quickRejectionModal');
                    table.ajax.reload();
                    updateBulkApproveButton();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan');
            }
        });
    }
    
    // Bulk approve function
    function bulkApproveLeaves(leaveIds, notes) {
        $.ajax({
            url: '/leave/approvals/bulk-approve',
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                leave_ids: leaveIds,
                approval_notes: notes
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    closeModal('bulkApprovalModal');
                    table.ajax.reload();
                    $('.form-checkbox').prop('checked', false);
                    $('#select-all').prop('checked', false);
                    updateBulkApproveButton();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan');
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
});
</script>
@endpush
