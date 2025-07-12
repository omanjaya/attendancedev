@extends('layouts.authenticated')

@section('title', 'Report Builder')

@section('page-header')
    @section('page-pretitle', 'Reports')
    @section('page-title', 'Interactive Report Builder')
    @section('page-actions')
        <div class="flex items-center space-x-2">
            <button class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" id="generateReport">
                <svg class="icon mr-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M3 19a9 9 0 0 1 9 0a9 9 0 0 1 9 0"/>
                    <path d="M3 6a9 9 0 0 1 9 0a9 9 0 0 1 9 0"/>
                    <line x1="3" y1="6" x2="3" y2="19"/>
                    <line x1="12" y1="6" x2="12" y2="19"/>
                    <line x1="21" y1="6" x2="21" y2="19"/>
                </svg>
                Generate Report
            </button>
        </div>
    @endsection
@endsection

@section('page-content')
<div class="max-w-7xl mx-auto px-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Report Configuration -->
        <div class="lg:col-span-4">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-3">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Report Configuration</h3>
                </div>
                <div class="px-6 py-4">
                    <form id="reportConfigForm">
                        <!-- Report Type -->
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 required">Report Type</label>
                            <select class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="reportType" name="report_type" required>
                                <option value="">Select a report type</option>
                                <option value="attendance">Attendance Report</option>
                                <option value="leave">Leave Report</option>
                                <option value="payroll">Payroll Report</option>
                                <option value="employee">Employee Report</option>
                                <option value="summary">Summary Report</option>
                            </select>
                        </div>

                        <!-- Date Range -->
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 required">Date Range</label>
                            <div class="flex rounded-md shadow-sm mb-2">
                                <input type="date" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="startDate" name="start_date" required>
                                <span class="flex items-center px-3 text-sm text-gray-500">to</span>
                                <input type="date" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="endDate" name="end_date" required>
                            </div>
                            <div class="inline-flex rounded-md shadow-sm w-full" role="group">
                                <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-150" onclick="setDateRange('today')">Today</button>
                                <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-150" onclick="setDateRange('this_week')">This Week</button>
                                <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-150" onclick="setDateRange('this_month')">This Month</button>
                                <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-150" onclick="setDateRange('last_month')">Last Month</button>
                            </div>
                        </div>

                        <!-- Grouping -->
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700">Group By</label>
                            <select class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="groupBy" name="group_by">
                                <option value="">No grouping</option>
                                <option value="employee">Employee</option>
                                <option value="department">Department</option>
                                <option value="location">Location</option>
                                <option value="date">Date</option>
                                <option value="week">Week</option>
                                <option value="month">Month</option>
                            </select>
                        </div>

                        <!-- Export Format -->
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700">Export Format</label>
                            <div class="flex flex-wrap gap-4">
                                <label class="flex items-center">
                                    <input type="radio" name="export_format" value="preview" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" checked>
                                    <span class="ml-2 text-sm text-gray-700">Preview</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="export_format" value="pdf" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                    <span class="ml-2 text-sm text-gray-700">PDF</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="export_format" value="csv" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                    <span class="ml-2 text-sm text-gray-700">CSV</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="export_format" value="excel" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                    <span class="ml-2 text-sm text-gray-700">Excel</span>
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-3">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Filters</h3>
                </div>
                <div class="px-6 py-4">
                    <div id="filterContainer">
                        <!-- Dynamic filters will be loaded here based on report type -->
                    </div>
                </div>
            </div>

            <!-- Columns Selection -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200" id="columnsCard" style="display: none;">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">Select Columns</h3>
                    <div class="flex space-x-2">
                        <button type="button" class="text-sm text-blue-600 hover:text-blue-500" onclick="selectAllColumns()">Select All</button>
                        <button type="button" class="text-sm text-blue-600 hover:text-blue-500" onclick="deselectAllColumns()">Deselect All</button>
                    </div>
                </div>
                <div class="px-6 py-4">
                    <div id="columnsContainer">
                        <!-- Dynamic columns will be loaded here based on report type -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Preview -->
        <div class="lg:col-span-8">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">Report Preview</h3>
                    <div class="hidden" id="previewActions">
                        <div class="relative">
                            <button class="inline-flex items-center px-3 py-1.5 text-xs font-medium border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" type="button" onclick="toggleExportDropdown()">
                                <svg class="icon mr-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M14 3v4a1 1 0 0 0 1 1h4"/>
                                    <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                </svg>
                                Export
                                <svg class="ml-2 -mr-0.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="export-dropdown" class="hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5">
                                <div class="py-1" role="menu">
                                    <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" href="#" onclick="exportReport('pdf')">Export as PDF</a>
                                    <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" href="#" onclick="exportReport('csv')">Export as CSV</a>
                                    <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" href="#" onclick="exportReport('excel')">Export as Excel</a>
                                </div>
                            </div>
                        </div>
                        <button class="inline-flex items-center px-3 py-1.5 text-xs font-medium border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 ml-2" onclick="scheduleReport()">
                            <svg class="icon mr-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <rect x="4" y="5" width="16" height="16" rx="2"/>
                                <line x1="16" y1="3" x2="16" y2="7"/>
                                <line x1="8" y1="3" x2="8" y2="7"/>
                                <line x1="4" y1="11" x2="20" y2="11"/>
                                <line x1="11" y1="15" x2="12" y2="15"/>
                                <line x1="12" y1="15" x2="12" y2="18"/>
                            </svg>
                            Schedule
                        </button>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                    <div id="reportPreview">
                        <div class="text-center text-gray-600 py-5">
                            <svg class="icon icon-lg mb-3" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2"/>
                                <rect x="9" y="3" width="6" height="4" rx="2"/>
                                <line x1="9" y1="12" x2="9.01" y2="12"/>
                                <line x1="13" y1="12" x2="15" y2="12"/>
                                <line x1="9" y1="16" x2="9.01" y2="16"/>
                                <line x1="13" y1="16" x2="15" y2="16"/>
                            </svg>
                            <p>Configure your report settings and click "Generate Report" to preview</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Statistics -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-4" id="reportStats" style="display: none;">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200-header">
                    <h3 class="bg-white rounded-lg shadow-sm border border-gray-200-title">Report Statistics</h3>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                    <div class="grid grid-cols-12 gap-4" id="statsContainer">
                        <!-- Dynamic statistics will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Report Modal -->
<div class="modal modal-blur fade" id="scheduleModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="scheduleForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Schedule Type</label>
                        <select class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="schedule_type" required>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Recipients (Email addresses)</label>
                        <textarea class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="recipients" rows="3" placeholder="email1@example.com&#10;email2@example.com" required></textarea>
                        <small class="form-hint">Enter one email address per line</small>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Report Format</label>
                        <select class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="format" required>
                            <option value="pdf">PDF</option>
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Schedule Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
#reportPreview {
    min-height: 400px;
}

.filter-group {
    margin-bottom: 1rem;
}

.columns-list {
    max-height: 300px;
    overflow-y: auto;
}

.stat-bg-white rounded-lg shadow-sm border border-gray-200 {
    text-align: center;
    padding: 1rem;
    background: var(--tblr-bg-surface-secondary);
    border-radius: var(--tblr-border-radius);
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--tblr-primary);
}

.stat-label {
    font-size: 0.875rem;
    color: var(--tblr-muted);
}
</style>

<script type="module">
const reportConfig = {
    attendance: {
        filters: ['employees', 'status', 'locations', 'departments'],
        columns: ['date', 'employee_name', 'employee_id', 'check_in', 'check_out', 'total_hours', 'status', 'late_minutes', 'notes'],
        defaultColumns: ['date', 'employee_name', 'check_in', 'check_out', 'total_hours', 'status']
    },
    leave: {
        filters: ['employees', 'leave_types', 'status', 'approved_by'],
        columns: ['employee_name', 'employee_id', 'leave_type', 'start_date', 'end_date', 'days_requested', 'status', 'reason', 'approved_by', 'approval_date'],
        defaultColumns: ['employee_name', 'leave_type', 'start_date', 'end_date', 'days_requested', 'status']
    },
    payroll: {
        filters: ['employees', 'status', 'salary_range'],
        columns: ['employee_name', 'employee_id', 'period', 'gross_salary', 'deductions', 'bonuses', 'net_salary', 'worked_hours', 'overtime_hours', 'status'],
        defaultColumns: ['employee_name', 'period', 'gross_salary', 'net_salary', 'status']
    },
    employee: {
        filters: ['employee_type', 'is_active', 'locations', 'hire_date'],
        columns: ['employee_id', 'full_name', 'email', 'phone', 'employee_type', 'hire_date', 'salary_type', 'salary_amount', 'location', 'status'],
        defaultColumns: ['employee_id', 'full_name', 'email', 'employee_type', 'location', 'status']
    },
    summary: {
        filters: ['grouping', 'metrics'],
        columns: [],
        defaultColumns: []
    }
};

let currentReportType = null;
let reportData = null;

function toggleExportDropdown() {
    const dropdown = document.getElementById('export-dropdown');
    dropdown.classList.toggle('hidden');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('export-dropdown');
    const button = event.target.closest('button');
    if (!button || !button.onclick || button.onclick.toString().indexOf('toggleExportDropdown') === -1) {
        dropdown.classList.add('hidden');
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Initialize date inputs with current month
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    
    document.getElementById('startDate').value = firstDay.toISOString().split('T')[0];
    document.getElementById('endDate').value = lastDay.toISOString().split('T')[0];
    
    // Report type change handler
    document.getElementById('reportType').addEventListener('change', function() {
        currentReportType = this.value;
        if (currentReportType) {
            loadFilters(currentReportType);
            loadColumns(currentReportType);
            document.getElementById('columnsCard').style.display = 'block';
        } else {
            document.getElementById('filterContainer').innerHTML = '';
            document.getElementById('columnsCard').style.display = 'none';
        }
    });
    
    // Generate report button handler
    document.getElementById('generateReport').addEventListener('click', generateReport);
    
    // Schedule form handler
    document.getElementById('scheduleForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitSchedule();
    });
});

function loadFilters(reportType) {
    const max-w-7xl mx-auto px-4 = document.getElementById('filterContainer');
    max-w-7xl mx-auto px-4.innerHTML = '';
    
    const filters = reportConfig[reportType].filters;
    const filterOptions = @json($filterOptions);
    
    filters.forEach(filter => {
        let filterHtml = '';
        
        switch(filter) {
            case 'employees':
                filterHtml = createMultiSelectFilter('Employees', 'employee_ids[]', 
                    filterOptions.employees.map(e => ({value: e.id, label: `${e.first_name} ${e.last_name} (${e.employee_id})`})));
                break;
            case 'status':
                if (reportType === 'attendance') {
                    filterHtml = createMultiSelectFilter('Status', 'status[]', 
                        filterOptions.attendance_statuses.map(s => ({value: s, label: s.charAt(0).toUpperCase() + s.slice(1)})));
                } else if (reportType === 'leave') {
                    filterHtml = createMultiSelectFilter('Status', 'status[]', 
                        filterOptions.leave_statuses.map(s => ({value: s, label: s.charAt(0).toUpperCase() + s.slice(1)})));
                } else if (reportType === 'payroll') {
                    filterHtml = createMultiSelectFilter('Status', 'status[]', 
                        filterOptions.payroll_statuses.map(s => ({value: s, label: s.charAt(0).toUpperCase() + s.slice(1)})));
                }
                break;
            case 'locations':
                filterHtml = createMultiSelectFilter('Locations', 'location_ids[]', 
                    filterOptions.locations.map(l => ({value: l.id, label: l.name})));
                break;
            case 'leave_types':
                filterHtml = createMultiSelectFilter('Leave Types', 'leave_type_ids[]', 
                    filterOptions.leave_types.map(lt => ({value: lt.id, label: lt.name})));
                break;
            case 'employee_type':
                filterHtml = createMultiSelectFilter('Employee Types', 'employee_types[]', 
                    filterOptions.employee_types.map(et => ({value: et, label: et.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')})));
                break;
            case 'is_active':
                filterHtml = `
                    <div class="filter-group">
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="is_active">
                            <option value="">All</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                `;
                break;
            case 'salary_range':
                filterHtml = `
                    <div class="filter-group">
                        <label class="block text-sm font-medium text-gray-700">Salary Range</label>
                        <div class="grid grid-cols-12 gap-4 g-2">
                            <div class="col">
                                <input type="number" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="min_salary" placeholder="Min">
                            </div>
                            <div class="col">
                                <input type="number" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="max_salary" placeholder="Max">
                            </div>
                        </div>
                    </div>
                `;
                break;
            case 'hire_date':
                filterHtml = `
                    <div class="filter-group">
                        <label class="block text-sm font-medium text-gray-700">Hire Date Range</label>
                        <div class="grid grid-cols-12 gap-4 g-2">
                            <div class="col">
                                <input type="date" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="hire_date_start">
                            </div>
                            <div class="col">
                                <input type="date" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="hire_date_end">
                            </div>
                        </div>
                    </div>
                `;
                break;
            case 'grouping':
                filterHtml = `
                    <div class="filter-group">
                        <label class="block text-sm font-medium text-gray-700">Grouping Period</label>
                        <select class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="grouping">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly" selected>Monthly</option>
                        </select>
                    </div>
                `;
                break;
            case 'metrics':
                filterHtml = `
                    <div class="filter-group">
                        <label class="block text-sm font-medium text-gray-700">Include Metrics</label>
                        <div class="form-selectgroup form-selectgroup-boxes flex flex-column">
                            <label class="form-selectgroup-item flex-1">
                                <input type="checkbox" name="metrics[]" value="attendance" class="form-selectgroup-input" checked>
                                <div class="form-selectgroup-label flex items-center p-3">
                                    <div class="mr-3">
                                        <span class="form-selectgroup-check"></span>
                                    </div>
                                    <div>Attendance</div>
                                </div>
                            </label>
                            <label class="form-selectgroup-item flex-1">
                                <input type="checkbox" name="metrics[]" value="leave" class="form-selectgroup-input" checked>
                                <div class="form-selectgroup-label flex items-center p-3">
                                    <div class="mr-3">
                                        <span class="form-selectgroup-check"></span>
                                    </div>
                                    <div>Leave</div>
                                </div>
                            </label>
                            <label class="form-selectgroup-item flex-1">
                                <input type="checkbox" name="metrics[]" value="payroll" class="form-selectgroup-input" checked>
                                <div class="form-selectgroup-label flex items-center p-3">
                                    <div class="mr-3">
                                        <span class="form-selectgroup-check"></span>
                                    </div>
                                    <div>Payroll</div>
                                </div>
                            </label>
                        </div>
                    </div>
                `;
                break;
        }
        
        max-w-7xl mx-auto px-4.innerHTML += filterHtml;
    });
    
    // Initialize select2 for multi-select filters
    setTimeout(() => {
        max-w-7xl mx-auto px-4.querySelectorAll('.multi-select').forEach(select => {
            $(select).select2({
                theme: 'default',
                width: '100%',
                placeholder: 'Select options...'
            });
        });
    }, 100);
}

function createMultiSelectFilter(label, name, options) {
    const optionsHtml = options.map(opt => `<option value="${opt.value}">${opt.label}</option>`).join('');
    return `
        <div class="filter-group">
            <label class="block text-sm font-medium text-gray-700">${label}</label>
            <select class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm multi-select" name="${name}" multiple>
                ${optionsHtml}
            </select>
        </div>
    `;
}

function loadColumns(reportType) {
    const max-w-7xl mx-auto px-4 = document.getElementById('columnsContainer');
    max-w-7xl mx-auto px-4.innerHTML = '<div class="columns-list">';
    
    const columns = reportConfig[reportType].columns;
    const defaultColumns = reportConfig[reportType].defaultColumns;
    
    columns.forEach(column => {
        const isChecked = defaultColumns.includes(column);
        const label = column.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
        
        max-w-7xl mx-auto px-4.innerHTML += `
            <label class="flex items-center">
                <input type="checkbox" class="flex items-center-input" name="columns[]" value="${column}" ${isChecked ? 'checked' : ''}>
                <span class="flex items-center-label">${label}</span>
            </label>
        `;
    });
    
    max-w-7xl mx-auto px-4.innerHTML += '</div>';
}

function selectAllColumns() {
    document.querySelectorAll('input[name="columns[]"]').forEach(checkbox => {
        checkbox.checked = true;
    });
}

function deselectAllColumns() {
    document.querySelectorAll('input[name="columns[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
}

function setDateRange(range) {
    const today = new Date();
    let startDate, endDate;
    
    switch(range) {
        case 'today':
            startDate = endDate = today;
            break;
        case 'this_week':
            startDate = new Date(today.setDate(today.getDate() - today.getDay()));
            endDate = new Date(today.setDate(today.getDate() - today.getDay() + 6));
            break;
        case 'this_month':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            break;
        case 'last_month':
            startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            endDate = new Date(today.getFullYear(), today.getMonth(), 0);
            break;
    }
    
    document.getElementById('startDate').value = startDate.toISOString().split('T')[0];
    document.getElementById('endDate').value = endDate.toISOString().split('T')[0];
}

function generateReport() {
    const reportType = document.getElementById('reportType').value;
    if (!reportType) {
        alert('Please select a report type');
        return;
    }
    
    // Collect form data
    const formData = new FormData(document.getElementById('reportConfigForm'));
    
    // Add date range
    formData.append('date_range[start]', document.getElementById('startDate').value);
    formData.append('date_range[end]', document.getElementById('endDate').value);
    
    // Add filters
    const filters = {};
    document.querySelectorAll('#filterContainer input, #filterContainer select').forEach(input => {
        if (input.type === 'checkbox' && input.checked) {
            const name = input.name.replace('[]', '');
            if (!filters[name]) filters[name] = [];
            filters[name].push(input.value);
        } else if (input.type !== 'checkbox' && input.value) {
            if (input.name.includes('[]')) {
                const name = input.name.replace('[]', '');
                filters[name] = $(input).val(); // For select2 multi-select
            } else {
                filters[input.name] = input.value;
            }
        }
    });
    
    Object.keys(filters).forEach(key => {
        if (Array.isArray(filters[key])) {
            filters[key].forEach(value => {
                formData.append(`filters[${key}][]`, value);
            });
        } else {
            formData.append(`filters[${key}]`, filters[key]);
        }
    });
    
    // Add columns
    const columns = [];
    document.querySelectorAll('input[name="columns[]"]:checked').forEach(checkbox => {
        columns.push(checkbox.value);
    });
    columns.forEach(col => formData.append('columns[]', col));
    
    // Add grouping
    formData.append('grouping', document.getElementById('groupBy').value);
    
    // Show loading state
    const previewContainer = document.getElementById('reportPreview');
    previewContainer.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-4">Generating report...</p>
        </div>
    `;
    
    // Send request
    fetch('{{ route("reports.generate-custom") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            reportData = data;
            displayReport(data);
            displayStats(data);
            document.getElementById('previewActions').style.display = 'block';
            document.getElementById('reportStats').style.display = 'block';
        } else {
            throw new Error(data.error || 'Failed to generate report');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        previewContainer.innerHTML = `
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">
                <h4 class="font-medium text-base">Error generating report</h4>
                <div class="text-gray-600">${error.message}</div>
            </div>
        `;
    });
}

function displayReport(data) {
    const max-w-7xl mx-auto px-4 = document.getElementById('reportPreview');
    const reportType = data.report_config.type;
    
    if (!data.data.records || data.data.records.length === 0) {
        max-w-7xl mx-auto px-4.innerHTML = `
            <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-md">
                <h4 class="font-medium text-base">No data found</h4>
                <div class="text-gray-600">No records match your selected criteria.</div>
            </div>
        `;
        return;
    }
    
    // Build min-w-full divide-y divide-gray-200 HTML
    let tableHtml = `
        <div class="min-w-full divide-y divide-gray-200-responsive">
            <min-w-full divide-y divide-gray-200 class="min-w-full divide-y divide-gray-200 min-w-full divide-y divide-gray-200-vcenter min-w-full divide-y divide-gray-200-striped">
                <thead>
                    <tr>
    `;
    
    // Add min-w-full divide-y divide-gray-200 headers
    const columns = data.report_config.columns || Object.keys(data.data.records[0]);
    columns.forEach(col => {
        const label = col.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
        tableHtml += `<th>${label}</th>`;
    });
    
    tableHtml += `
                    </tr>
                </thead>
                <tbody>
    `;
    
    // Add min-w-full divide-y divide-gray-200 rows
    data.data.records.forEach(record => {
        tableHtml += '<tr>';
        columns.forEach(col => {
            let value = getNestedValue(record, col);
            
            // Format specific columns
            if (col.includes('date') && value) {
                value = new Date(value).toLocaleDateString();
            } else if ((col.includes('salary') || col.includes('amount')) && value) {
                value = '$' + parseFloat(value).toFixed(2);
            } else if (col === 'status') {
                const statusClass = {
                    'present': 'success',
                    'approved': 'success',
                    'paid': 'success',
                    'active': 'success',
                    'late': 'warning',
                    'pending': 'warning',
                    'absent': 'danger',
                    'rejected': 'danger',
                    'cancelled': 'danger',
                    'inactive': 'danger'
                }[value] || 'secondary';
                value = `<span class="badge bg-${statusClass}">${value}</span>`;
            }
            
            tableHtml += `<td>${value || '-'}</td>`;
        });
        tableHtml += '</tr>';
    });
    
    tableHtml += `
                </tbody>
            </min-w-full divide-y divide-gray-200>
        </div>
    `;
    
    max-w-7xl mx-auto px-4.innerHTML = tableHtml;
}

function getNestedValue(obj, path) {
    const keys = path.split('.');
    let value = obj;
    
    for (const key of keys) {
        if (value && typeof value === 'object' && key in value) {
            value = value[key];
        } else {
            // Try alternative naming conventions
            if (key === 'employee_name' && value.employee) {
                value = value.employee.full_name || `${value.employee.first_name} ${value.employee.last_name}`;
            } else if (key === 'employee_id' && value.employee) {
                value = value.employee.employee_id;
            } else {
                return null;
            }
        }
    }
    
    return value;
}

function displayStats(data) {
    const max-w-7xl mx-auto px-4 = document.getElementById('statsContainer');
    max-w-7xl mx-auto px-4.innerHTML = '';
    
    if (data.data.stats) {
        Object.entries(data.data.stats).forEach(([key, value]) => {
            const label = key.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
            let displayValue = value;
            
            if (key.includes('amount') || key.includes('salary')) {
                displayValue = '$' + parseFloat(value).toFixed(2);
            } else if (key.includes('rate') || key.includes('percentage')) {
                displayValue = parseFloat(value).toFixed(1) + '%';
            } else if (key.includes('hours')) {
                displayValue = parseFloat(value).toFixed(1) + ' hrs';
            }
            
            max-w-7xl mx-auto px-4.innerHTML += `
                <div class="col-md-3 mb-3">
                    <div class="stat-bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="stat-value">${displayValue}</div>
                        <div class="stat-label">${label}</div>
                    </div>
                </div>
            `;
        });
    }
}

function exportReport(format) {
    if (!reportData) {
        alert('Please generate a report first');
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `{{ url('/reports/export') }}/${reportData.report_config.type}`;
    
    // Add CSRF token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);
    
    // Add format
    const formatInput = document.createElement('input');
    formatInput.type = 'hidden';
    formatInput.name = 'format';
    formatInput.value = format;
    form.appendChild(formatInput);
    
    // Add filters and date range
    const config = reportData.report_config;
    
    // Date range
    const startDateInput = document.createElement('input');
    startDateInput.type = 'hidden';
    startDateInput.name = 'start_date';
    startDateInput.value = config.date_range.start;
    form.appendChild(startDateInput);
    
    const endDateInput = document.createElement('input');
    endDateInput.type = 'hidden';
    endDateInput.name = 'end_date';
    endDateInput.value = config.date_range.end;
    form.appendChild(endDateInput);
    
    // Filters
    if (config.filters) {
        Object.entries(config.filters).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value.forEach(v => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `${key}[]`;
                    input.value = v;
                    form.appendChild(input);
                });
            } else {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }
        });
    }
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function scheduleReport() {
    if (!reportData) {
        alert('Please generate a report first');
        return;
    }
    
    document.getElementById('scheduleModal').classList.remove('hidden');
}

function submitSchedule() {
    const formData = new FormData(document.getElementById('scheduleForm'));
    formData.append('report_type', reportData.report_config.type);
    
    // Add report configuration
    formData.append('filters', JSON.stringify(reportData.report_config.filters));
    
    // Parse recipients
    const recipientsText = formData.get('recipients');
    const recipients = recipientsText.split('\n').map(email => email.trim()).filter(email => email);
    formData.delete('recipients');
    recipients.forEach(email => formData.append('recipients[]', email));
    
    fetch('{{ route("reports.schedule") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('scheduleModal').classList.add('hidden');
            alert('Report scheduled successfully!');
        } else {
            throw new Error(data.message || 'Failed to schedule report');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error scheduling report: ' + error.message);
    });
}
</script>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<!-- Select2 theme removed - using default theme with Tailwind styling -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection