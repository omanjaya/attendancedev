@extends('layouts.authenticated')

@section('title', 'Payroll Management')

@section('page-content')
    <!-- Page Header -->
    <div class="mb-8 mt-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Payroll Management</h1>
            <p class="mt-1 text-sm text-gray-600">Manage employee payroll calculations and payments</p>
        </div>
        
        <div class="flex items-center space-x-3">
            @can('view_payroll_reports')
                <x-ui.button
                    variant="outline"
                    href="{{ route('payroll.summary') }}"
                >
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Reports
                </x-ui.button>
            @endcan
            
            @can('create_payroll')
                <x-ui.button
                    variant="outline"
                    href="{{ route('payroll.bulk-calculate') }}"
                >
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    Bulk Calculate
                </x-ui.button>
                
                <x-ui.button
                    variant="primary"
                    href="{{ route('payroll.create') }}"
                >
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Calculate Payroll
                </x-ui.button>
            @endcan
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <x-ui.stats-card
            title="Total Gross Salary"
            value="$0.00"
            change="+$1,250"
            change-type="increase"
            icon-color="blue"
            id="totalGrossSalary"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </x-slot>
            <x-slot name="subtitle">this month</x-slot>
        </x-ui.stats-card>
        
        <x-ui.stats-card
            title="Total Net Salary"
            value="$0.00"
            change="+$980"
            change-type="increase"
            icon-color="green"
            id="totalNetSalary"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                </svg>
            </x-slot>
            <x-slot name="subtitle">after deductions</x-slot>
        </x-ui.stats-card>
        
        <x-ui.stats-card
            title="Total Deductions"
            value="$0.00"
            change="+$270"
            change-type="increase"
            icon-color="red"
            id="totalDeductions"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                </svg>
            </x-slot>
            <x-slot name="subtitle">from gross</x-slot>
        </x-ui.stats-card>
        
        <x-ui.stats-card
            title="Total Employees"
            value="0"
            change="+2"
            change-type="increase"
            icon-color="blue"
            id="totalEmployees"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </x-slot>
            <x-slot name="subtitle">with payroll</x-slot>
        </x-ui.stats-card>
    </div>
    
    <!-- Filters -->
    <x-ui.card class="mb-8">
        <x-slot name="title">Filter Payroll Records</x-slot>
        <x-slot name="subtitle">Filter records by employee, status, and period</x-slot>
        
        <form method="GET" id="filtersForm" class="space-y-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-5">
                <div class="space-y-2">
                    <x-ui.label for="employeeFilter" value="Employee" />
                    <x-ui.select id="employeeFilter" name="employee_id">
                        <option value="">All Employees</option>
                        <!-- Employee options would be populated from backend -->
                    </x-ui.select>
                </div>
                
                <div class="space-y-2">
                    <x-ui.label for="statusFilter" value="Status" />
                    <x-ui.select id="statusFilter" name="status">
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="processed">Processed</option>
                        <option value="paid">Paid</option>
                        <option value="cancelled">Cancelled</option>
                    </x-ui.select>
                </div>
                
                <div class="space-y-2">
                    <x-ui.label for="periodStartFilter" value="Period Start" />
                    <x-ui.input type="date" id="periodStartFilter" name="period_start" />
                </div>
                
                <div class="space-y-2">
                    <x-ui.label for="periodEndFilter" value="Period End" />
                    <x-ui.input type="date" id="periodEndFilter" name="period_end" />
                </div>
                
                <div class="flex items-end space-x-2">
                    <x-ui.button type="button" id="applyFilters">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Filter
                    </x-ui.button>
                    
                    <x-ui.button type="button" variant="outline" size="sm" id="setCurrentMonth">
                        Current Month
                    </x-ui.button>
                </div>
            </div>
        </form>
    </x-ui.card>
    
    <!-- Payroll Records Table -->
    <x-ui.card>
        <x-slot name="title">Payroll Records</x-slot>
        <x-slot name="subtitle">Employee payroll calculations and payments</x-slot>
        @php
            $records = [
                [
                    'id' => 1,
                    'employee_name' => 'John Doe',
                    'employee_id' => 'EMP001',
                    'period' => 'January 2024',
                    'gross_salary' => 5000.00,
                    'deductions' => 750.00,
                    'net_salary' => 4250.00,
                    'worked_hours' => 160,
                    'overtime_hours' => 8,
                    'status' => 'Approved',
                    'pay_date' => '2024-01-31'
                ],
                [
                    'id' => 2,
                    'employee_name' => 'Jane Smith',
                    'employee_id' => 'EMP002',
                    'period' => 'January 2024',
                    'gross_salary' => 4500.00,
                    'deductions' => 675.00,
                    'net_salary' => 3825.00,
                    'worked_hours' => 160,
                    'overtime_hours' => 4,
                    'status' => 'Processed',
                    'pay_date' => '2024-01-31'
                ],
                [
                    'id' => 3,
                    'employee_name' => 'Mike Johnson',
                    'employee_id' => 'EMP003',
                    'period' => 'January 2024',
                    'gross_salary' => 3500.00,
                    'deductions' => 525.00,
                    'net_salary' => 2975.00,
                    'worked_hours' => 140,
                    'overtime_hours' => 0,
                    'status' => 'Pending',
                    'pay_date' => null
                ],
            ];
            
            $columns = [
                [
                    'key' => 'employee_name',
                    'label' => 'Employee',
                    'sortable' => true,
                    'render' => function($value, $grid grid-cols-12 gap-4) {
                        return '
                            <div>
                                <div class="text-sm font-medium text-gray-900">' . $value . '</div>
                                <div class="text-sm text-gray-500">' . $grid grid-cols-12 gap-4['employee_id'] . '</div>
                            </div>
                        ';
                    }
                ],
                [
                    'key' => 'period',
                    'label' => 'Period',
                    'sortable' => true,
                ],
                [
                    'key' => 'gross_salary',
                    'label' => 'Gross Salary',
                    'render' => function($value, $grid grid-cols-12 gap-4) {
                        return '<span class="font-medium">$' . number_format($value, 2) . '</span>';
                    }
                ],
                [
                    'key' => 'deductions',
                    'label' => 'Deductions',
                    'render' => function($value, $grid grid-cols-12 gap-4) {
                        return '<span class="text-red-600">-$' . number_format($value, 2) . '</span>';
                    }
                ],
                [
                    'key' => 'net_salary',
                    'label' => 'Net Salary',
                    'render' => function($value, $grid grid-cols-12 gap-4) {
                        return '<span class="font-medium text-green-600">$' . number_format($value, 2) . '</span>';
                    }
                ],
                [
                    'key' => 'worked_hours',
                    'label' => 'Hours',
                    'render' => function($value, $grid grid-cols-12 gap-4) {
                        $overtime = $grid grid-cols-12 gap-4['overtime_hours'] > 0 ? ' (+' . $grid grid-cols-12 gap-4['overtime_hours'] . 'h OT)' : '';
                        return $value . 'h' . $overtime;
                    }
                ],
                [
                    'key' => 'status',
                    'label' => 'Status',
                    'render' => function($value, $grid grid-cols-12 gap-4) {
                        $statusClass = match($value) {
                            'Draft' => 'bg-gray-100 text-gray-800',
                            'Pending' => 'bg-yellow-100 text-yellow-800',
                            'Approved' => 'bg-blue-100 text-blue-800',
                            'Processed' => 'bg-green-100 text-green-800',
                            'Paid' => 'bg-emerald-100 text-emerald-800',
                            'Cancelled' => 'bg-red-100 text-red-800',
                            default => 'bg-gray-100 text-gray-800'
                        };
                        return '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ' . $statusClass . '">' . $value . '</span>';
                    }
                ],
                [
                    'key' => 'pay_date',
                    'label' => 'Pay Date',
                    'render' => function($value, $grid grid-cols-12 gap-4) {
                        return $value ? date('M j, Y', strtotime($value)) : '<span class="text-gray-400">Not set</span>';
                    }
                ],
                [
                    'key' => 'actions',
                    'label' => 'Actions',
                    'render' => function($value, $grid grid-cols-12 gap-4) {
                        $actions = '<div class="flex items-center space-x-2">';
                        $actions .= '<a href="/payroll/' . $grid grid-cols-12 gap-4['id'] . '" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">View</a>';
                        
                        if ($grid grid-cols-12 gap-4['status'] === 'Pending') {
                            $actions .= '<button onclick="approvePayroll(' . $grid grid-cols-12 gap-4['id'] . ')" class="text-green-600 hover:text-green-900 text-sm font-medium">Approve</button>';
                        }
                        
                        if ($grid grid-cols-12 gap-4['status'] === 'Approved') {
                            $actions .= '<button onclick="processPayroll(' . $grid grid-cols-12 gap-4['id'] . ')" class="text-blue-600 hover:text-blue-900 text-sm font-medium">Process</button>';
                        }
                        
                        if (in_array($grid grid-cols-12 gap-4['status'], ['Draft', 'Pending'])) {
                            $actions .= '<button onclick="editPayroll(' . $grid grid-cols-12 gap-4['id'] . ')" class="text-gray-600 hover:text-gray-900 text-sm font-medium">Edit</button>';
                        }
                        
                        $actions .= '</div>';
                        return $actions;
                    }
                ],
            ];
        @endphp
        
        <x-slot name="actions">
            <div class="flex items-center space-x-2">
                <x-ui.button variant="outline" size="sm" onclick="exportPayroll()">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export CSV
                </x-ui.button>
                <x-ui.button variant="outline" size="sm" id="clearFilters">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Clear Filters
                </x-ui.button>
            </div>
        </x-slot>
        
        <!-- Simplified Table Structure -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Period</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Gross Salary</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Deductions</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Net Salary</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-card divide-y divide-border">
                    @forelse($records ?? [] as $record)
                    <tr class="hover:bg-muted/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <x-ui.avatar :name="$record['employee_name']" size="sm" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-foreground">{{ $record['employee_name'] }}</div>
                                    <div class="text-sm text-muted-foreground">{{ $record['employee_id'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">{{ $record['period'] }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">${{ number_format($record['gross_salary'], 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-destructive">-${{ number_format($record['deductions'], 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-success">${{ number_format($record['net_salary'], 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-ui.badge variant="{{ $record['status'] === 'Paid' ? 'success' : ($record['status'] === 'Pending' ? 'warning' : 'secondary') }}">
                                {{ $record['status'] }}
                            </x-ui.badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-1">
                                <x-ui.button variant="ghost" size="icon" href="{{ route('payroll.show', $record['id']) }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </x-ui.button>
                                @if($record['status'] === 'Pending')
                                <x-ui.button variant="ghost" size="icon" onclick="approvePayroll({{ $record['id'] }})">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </x-ui.button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-muted-foreground">
                            <div class="flex flex-col items-center">
                                <svg class="h-12 w-12 mb-4 text-muted-foreground/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="text-lg font-medium">No payroll records found</p>
                                <p class="text-sm">Create your first payroll calculation to get started</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endsection

@push('scripts')
<script>
    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        // Set current month button
        document.getElementById('setCurrentMonth').addEventListener('click', function() {
            const now = new Date();
            const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
            const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            
            document.getElementById('periodStartFilter').value = firstDay.toISOString().split('T')[0];
            document.getElementById('periodEndFilter').value = lastDay.toISOString().split('T')[0];
        });

        // Apply filters button
        document.getElementById('applyFilters').addEventListener('click', function() {
            // In a real implementation, this would reload the data with filters
            updateSummaryCards();
            showAlert('success', 'Filters applied successfully');
        });

        // Clear filters button
        document.getElementById('clearFilters').addEventListener('click', function() {
            document.getElementById('filtersForm').reset();
            updateSummaryCards();
            showAlert('info', 'Filters cleared');
        });

        // Initial load
        updateSummaryCards();
    });

    // Approve payroll
    function approvePayroll(id) {
        if (confirm('Are you sure you want to approve this payroll?')) {
            // In a real implementation, this would make an API call
            showAlert('success', 'Payroll approved successfully');
            // Reload min-w-full divide-y divide-gray-200 data
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
    }

    // Process payroll
    function processPayroll(id) {
        if (confirm('Are you sure you want to process this payroll?')) {
            // In a real implementation, this would make an API call
            showAlert('success', 'Payroll processed successfully');
            // Reload min-w-full divide-y divide-gray-200 data
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
    }

    // Edit payroll
    function editPayroll(id) {
        window.location.href = '/payroll/' + id + '/edit';
    }

    // Export payroll
    function exportPayroll() {
        // In a real implementation, this would export the data
        showAlert('info', 'Payroll export started');
    }

    // Update summary cards
    function updateSummaryCards() {
        // In a real implementation, this would fetch data from an API
        document.getElementById('totalGrossSalary').textContent = '$13,000.00';
        document.getElementById('totalNetSalary').textContent = '$11,050.00';
        document.getElementById('totalDeductions').textContent = '$1,950.00';
        document.getElementById('totalEmployees').textContent = '3';
    }

    // Show alert function
    function showAlert(type, message) {
        const alertColors = {
            success: 'bg-green-50 text-green-800 border-green-200',
            error: 'bg-red-50 text-red-800 border-red-200',
            info: 'bg-blue-50 text-blue-800 border-blue-200',
            warning: 'bg-yellow-50 text-yellow-800 border-yellow-200'
        };

        const alert = document.createElement('div');
        alert.className = `fixed top-4 right-4 p-4 rounded-lg border ${alertColors[type]} shadow-lg z-50`;
        alert.innerHTML = `
            <div class="flex items-center">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-lg">&times;</button>
            </div>
        `;

        document.body.appendChild(alert);

        setTimeout(() => {
            if (alert.parentElement) {
                alert.remove();
            }
        }, 5000);
    }
</script>
@endpush