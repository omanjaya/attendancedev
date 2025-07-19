@extends('layouts.authenticated-unified')

@section('title', 'Leave Balance Management')

@section('page-header')
@section('page-pretitle', 'Leave Management')
@section('page-title', 'Leave Balance Management')
@endsection

@section('page-content')
<div class="grid grid-cols-12 gap-4">
    <div class="col-span-12">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Leave Balance Management</h3>
                <div class="flex space-x-2">
                    <button class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" data-bs-toggle="modal" data-bs-target="#addBalanceModal">
                        <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M12 5l0 14"/>
                            <path d="M5 12l14 0"/>
                        </svg>
                        Add Balance
                    </button>
                    <button class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ml-2" data-bs-toggle="modal" data-bs-target="#bulkCreateModal">
                        <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M7 12l5 5l10 -10"/>
                        </svg>
                        Bulk Create
                    </button>
                </div>
            </div>
            <div class="px-6 py-4">
                <!-- Filters -->
                <div class="grid grid-cols-12 gap-4 mb-3">
                    <div class="col-md-3">
                        <label class="block text-sm font-medium text-gray-700">Year</label>
                        <select id="year-filter" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            @for($year = $currentYear + 1; $year >= $currentYear - 5; $year--)
                                <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>{{ $year }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="block text-sm font-medium text-gray-700">Employee</label>
                        <select id="employee-filter" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">All Employees</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->full_name }} ({{ $employee->employee_id }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="block text-sm font-medium text-gray-700">Leave Type</label>
                        <select id="leave-type-filter" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">All Leave Types</option>
                            @foreach($leaveTypes as $leaveType)
                                <option value="{{ $leaveType->id }}">{{ $leaveType->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="block text-sm font-medium text-gray-700">&nbsp;</label>
                        <div class="flex">
                            <button id="filter-btn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Filter</button>
                            <button id="reset-btn" class="btn btn-link ml-2">Reset</button>
                        </div>
                    </div>
                </div>
                
                <!-- DataTable -->
                <div class="overflow-x-auto">
                    <table id="balances-table" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th>Employee</th>
                                <th>Employee ID</th>
                                <th>Leave Type</th>
                                <th>Year</th>
                                <th>Allocated</th>
                                <th>Used</th>
                                <th>Remaining</th>
                                <th>Carried Forward</th>
                                <th>Utilization</th>
                                <th>Progress</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded via DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Balance Modal -->
<div class="modal fade" id="addBalanceModal" tabindex="-1" aria-labelledby="addBalanceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addBalanceModalLabel">Add Leave Balance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="add-balance-form">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 required">Employee</label>
                        <select name="employee_id" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                            <option value="">Select Employee</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->full_name }} ({{ $employee->employee_id }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 required">Leave Type</label>
                        <select name="leave_type_id" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                            <option value="">Select Leave Type</option>
                            @foreach($leaveTypes as $leaveType)
                                <option value="{{ $leaveType->id }}">{{ $leaveType->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 required">Year</label>
                        <select name="year" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                            @for($year = $currentYear + 1; $year >= $currentYear - 2; $year--)
                                <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>{{ $year }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 required">Allocated Days</label>
                        <input type="number" name="allocated_days" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" min="0" max="365" step="0.5" required>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Used Days</label>
                        <input type="number" name="used_days" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" min="0" step="0.5" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Carried Forward</label>
                        <input type="number" name="carried_forward" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" min="0" max="365" step="0.5" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Reason</label>
                        <textarea name="reason" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" rows="3" placeholder="Optional: Reason for creating this balance"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Create Balance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Balance Modal -->
<div class="modal fade" id="editBalanceModal" tabindex="-1" aria-labelledby="editBalanceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editBalanceModalLabel">Edit Leave Balance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="edit-balance-form">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Employee</label>
                        <input type="text" id="edit-employee-name" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Leave Type</label>
                        <input type="text" id="edit-leave-type" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Year</label>
                        <input type="text" id="edit-year" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 required">Allocated Days</label>
                        <input type="number" name="allocated_days" id="edit-allocated-days" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" min="0" max="365" step="0.5" required>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 required">Used Days</label>
                        <input type="number" name="used_days" id="edit-used-days" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" min="0" step="0.5" required>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Carried Forward</label>
                        <input type="number" name="carried_forward" id="edit-carried-forward" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" min="0" max="365" step="0.5">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Reason for Change</label>
                        <textarea name="reason" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" rows="3" placeholder="Required: Reason for updating this balance" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Update Balance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Create Modal -->
<div class="modal fade" id="bulkCreateModal" tabindex="-1" aria-labelledby="bulkCreateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkCreateModalLabel">Bulk Create Leave Balances</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="bulk-create-form">
                <div class="modal-body">
                    <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-md">
                        <h4>Bulk Create Leave Balances</h4>
                        <p>This will create leave balances for all active employees for the selected leave type and year.</p>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 required">Leave Type</label>
                        <select name="leave_type_id" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                            <option value="">Select Leave Type</option>
                            @foreach($leaveTypes as $leaveType)
                                <option value="{{ $leaveType->id }}" data-default-days="{{ $leaveType->default_days_per_year }}">
                                    {{ $leaveType->name }} (Default: {{ $leaveType->default_days_per_year ?? 'N/A' }} days)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 required">Year</label>
                        <select name="year" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                            @for($year = $currentYear + 1; $year >= $currentYear - 2; $year--)
                                <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>{{ $year }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 required">Allocated Days</label>
                        <input type="number" name="allocated_days" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" min="0" max="365" step="0.5" required>
                        <small class="form-hint">This will be applied to all employees</small>
                    </div>
                    <div class="mb-3">
                        <div class="flex items-center">
                            <input class="flex items-center-input" type="checkbox" name="overwrite_existing" value="1">
                            <label class="flex items-center-label">
                                Overwrite existing balances
                            </label>
                            <small class="form-hint block">If checked, existing balances will be updated with the new allocated days</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150">Create Balances</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="historyModalLabel">Leave Balance History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="history-content">
                    <!-- Content will be loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#balances-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('leave.balance.data') }}",
            data: function(d) {
                d.year = $('#year-filter').val();
                d.employee_id = $('#employee-filter').val();
                d.leave_type_id = $('#leave-type-filter').val();
            }
        },
        columns: [
            {data: 'employee_name', name: 'employee.first_name'},
            {data: 'employee_id_display', name: 'employee.employee_id'},
            {data: 'leave_type_name', name: 'leaveType.name'},
            {data: 'year', name: 'year'},
            {data: 'allocated_days', name: 'allocated_days'},
            {data: 'used_days', name: 'used_days'},
            {data: 'remaining_days', name: 'remaining_days'},
            {data: 'carried_forward', name: 'carried_forward'},
            {data: 'utilization_percentage', name: 'utilization_percentage', orderable: false},
            {data: 'progress_bar', name: 'progress_bar', orderable: false},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ],
        order: [[0, 'asc']]
    });

    // Filter functionality
    $('#filter-btn').on('click', function() {
        table.ajax.reload();
    });

    $('#reset-btn').on('click', function() {
        $('#year-filter').val('{{ $currentYear }}');
        $('#employee-filter').val('');
        $('#leave-type-filter').val('');
        table.ajax.reload();
    });

    // Auto-fill allocated days based on leave type selection
    $('#bulkCreateModal select[name="leave_type_id"]').on('change', function() {
        var defaultDays = $(this).find('option:selected').data('default-days');
        if (defaultDays) {
            $('#bulkCreateModal input[name="allocated_days"]').val(defaultDays);
        }
    });

    // Add balance form submission
    $('#add-balance-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.ajax({
            url: "{{ route('leave.balance.store') }}",
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#addBalanceModal').modal('hide');
                    $('#add-balance-form')[0].reset();
                    table.ajax.reload();
                    
                    // Show success message
                    $('body').append('<div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md alert-dismissible fade show" role="alert">' +
                        response.message +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
                    
                    setTimeout(function() {
                        $('.alert').alert('close');
                    }, 5000);
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                var errorMessage = 'Failed to create leave balance.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                alert(errorMessage);
            }
        });
    });

    // Edit balance functionality
    var currentBalanceId = null;
    
    $(document).on('click', '.edit-balance', function() {
        var balanceId = $(this).data('id');
        currentBalanceId = balanceId;
        
        // Get balance data from the table row
        var rowData = table.row($(this).closest('tr')).data();
        
        $('#edit-employee-name').val(rowData.employee_name);
        $('#edit-leave-type').val(rowData.leave_type_name);
        $('#edit-year').val(rowData.year);
        $('#edit-allocated-days').val(rowData.allocated_days);
        $('#edit-used-days').val(rowData.used_days);
        $('#edit-carried-forward').val(rowData.carried_forward);
        
        $('#editBalanceModal').modal('show');
    });

    // Edit balance form submission
    $('#edit-balance-form').on('submit', function(e) {
        e.preventDefault();
        
        if (!currentBalanceId) {
            alert('No balance selected for editing.');
            return;
        }
        
        var formData = $(this).serialize();
        
        $.ajax({
            url: "{{ route('leave.balance.update', '') }}/" + currentBalanceId,
            method: 'PUT',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#editBalanceModal').modal('hide');
                    $('#edit-balance-form')[0].reset();
                    table.ajax.reload();
                    
                    // Show success message
                    $('body').append('<div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md alert-dismissible fade show" role="alert">' +
                        response.message +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
                    
                    setTimeout(function() {
                        $('.alert').alert('close');
                    }, 5000);
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                var errorMessage = 'Failed to update leave balance.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                alert(errorMessage);
            }
        });
    });

    // Bulk create form submission
    $('#bulk-create-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        if (!confirm('Are you sure you want to create leave balances for all active employees?')) {
            return;
        }
        
        $.ajax({
            url: "{{ route('leave.balance.bulk-create') }}",
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#bulkCreateModal').modal('hide');
                    $('#bulk-create-form')[0].reset();
                    table.ajax.reload();
                    
                    // Show success message
                    $('body').append('<div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md alert-dismissible fade show" role="alert">' +
                        response.message +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
                    
                    setTimeout(function() {
                        $('.alert').alert('close');
                    }, 5000);
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                var errorMessage = 'Failed to create leave balances.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                alert(errorMessage);
            }
        });
    });

    // View history functionality
    $(document).on('click', '.view-history', function() {
        var employeeId = $(this).data('employee-id');
        var leaveTypeId = $(this).data('leave-type-id');
        
        $('#history-content').html('<div class="text-center py-4"><div class="spinner-border" role="status"></div></div>');
        $('#historyModal').modal('show');
        
        $.ajax({
            url: "{{ route('leave.balance.history') }}",
            method: 'GET',
            data: {
                employee_id: employeeId,
                leave_type_id: leaveTypeId
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    var historyHtml = '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Allocated</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Used</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remaining</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Carried Forward</th></tr></thead><tbody class="bg-white divide-y divide-gray-200">';
                    
                    response.history.forEach(function(balance) {
                        historyHtml += '<tr class="hover:bg-gray-50">';
                        historyHtml += '<td class="px-6 py-4 whitespace-nowrap"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">' + balance.year + '</span></td>';
                        historyHtml += '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' + balance.allocated_days + ' days</td>';
                        historyHtml += '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' + balance.used_days + ' days</td>';
                        historyHtml += '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' + balance.remaining_days + ' days</td>';
                        historyHtml += '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' + (balance.carried_forward > 0 ? balance.carried_forward + ' days' : '-') + '</td>';
                        historyHtml += '</tr>';
                    });
                    
                    historyHtml += '</tbody></table></div>';
                    
                    if (response.history.length === 0) {
                        historyHtml = '<div class="text-center py-4 text-gray-600">No history found</div>';
                    }
                    
                    $('#history-content').html(historyHtml);
                } else {
                    $('#history-content').html('<div class="text-center py-4 text-danger">Error loading history</div>');
                }
            },
            error: function() {
                $('#history-content').html('<div class="text-center py-4 text-danger">Error loading history</div>');
            }
        });
    });
});
</script>
@endpush