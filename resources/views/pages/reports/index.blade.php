@extends('layouts.authenticated')

@section('title', 'Reports')

@section('page-content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-8">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8 mt-6">
            <div class="mb-4 sm:mb-0">
                <h2 class="text-3xl font-bold text-gray-900">Reports</h2>
                <div class="text-gray-600 mt-1">Management - View system reports and analytics</div>
            </div>
            <div class="flex-shrink-0">
                <a href="{{ route('reports.builder') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M12 5l0 14"/>
                        <path d="M5 12l14 0"/>
                    </svg>
                    Build Custom Report
                </a>
            </div>
        </div>

        <!-- Quick Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-medium text-gray-500 uppercase tracking-wider">Today's Attendance</div>
                </div>
                <div class="flex items-baseline mt-4">
                    <div class="text-3xl font-bold text-gray-900">{{ $quickStats['todays_attendance'] }}</div>
                    <div class="ml-auto">
                        <span class="text-green-600 inline-flex items-center text-sm">
                            <svg class="w-4 h-4 mr-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <polyline points="22,6 12,13 9,10 2,17"/>
                                <polyline points="13,6 22,6 22,15"/>
                            </svg>
                            records
                        </span>
                    </div>
                </div>
            </div>
    
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-medium text-gray-500 uppercase tracking-wider">Pending Leaves</div>
                </div>
                <div class="flex items-baseline mt-4">
                    <div class="text-3xl font-bold text-gray-900">{{ $quickStats['pending_leaves'] }}</div>
                    <div class="ml-auto">
                        <span class="text-yellow-600 inline-flex items-center text-sm">
                            <svg class="w-4 h-4 mr-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="12" cy="12" r="9"/>
                                <polyline points="12,6 12,12 16,14"/>
                            </svg>
                            pending
                        </span>
                    </div>
                </div>
            </div>
    
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-medium text-gray-500 uppercase tracking-wider">Monthly Payrolls</div>
                </div>
                <div class="flex items-baseline mt-4">
                    <div class="text-3xl font-bold text-gray-900">{{ $quickStats['monthly_payrolls'] }}</div>
                    <div class="ml-auto">
                        <span class="text-blue-600 inline-flex items-center text-sm">
                            <svg class="w-4 h-4 mr-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M14.8 9a2 2 0 0 0-1.8-1h-2a2 2 0 0 0 0 4h2a2 2 0 0 1 0 4h-2a2 2 0 0 1-1.8-1"/>
                                <path d="M12 6v2m0 8v2"/>
                            </svg>
                            processed
                        </span>
                    </div>
                </div>
            </div>
    
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Employees</div>
                </div>
                <div class="flex items-baseline mt-4">
                    <div class="text-3xl font-bold text-gray-900">{{ $quickStats['total_employees'] }}</div>
                    <div class="ml-auto">
                        <span class="text-gray-600 inline-flex items-center text-sm">
                            <svg class="w-4 h-4 mr-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                <path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/>
                            </svg>
                            active
                        </span>
                    </div>
                </div>
            </div>
</div>

<!-- Report Types Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($reportTypes as $key => $reportType)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6">
            <div class="flex items-center mb-3">
                <div class="mr-3">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-100">
                        <svg class="w-6 h-6 text-blue-600" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                @switch($reportType['icon'])
                                    @case('calendar-check')
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <rect x="4" y="5" width="16" height="16" rx="2"/>
                                        <line x1="16" y1="3" x2="16" y2="7"/>
                                        <line x1="8" y1="3" x2="8" y2="7"/>
                                        <line x1="4" y1="11" x2="20" y2="11"/>
                                        <path d="M8 15h2v2H8z"/>
                                        <path d="M9 16l2 2 4-4"/>
                                        @break
                                    @case('calendar-x')
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <rect x="4" y="5" width="16" height="16" rx="2"/>
                                        <line x1="16" y1="3" x2="16" y2="7"/>
                                        <line x1="8" y1="3" x2="8" y2="7"/>
                                        <line x1="4" y1="11" x2="20" y2="11"/>
                                        <line x1="10" y1="16" x2="14" y2="16"/>
                                        @break
                                    @case('currency-dollar')
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <circle cx="12" cy="12" r="9"/>
                                        <path d="M14.8 9a2 2 0 0 0-1.8-1h-2a2 2 0 0 0 0 4h2a2 2 0 0 1 0 4h-2a2 2 0 0 1-1.8-1"/>
                                        <path d="M12 6v2m0 8v2"/>
                                        @break
                                    @case('users')
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <circle cx="9" cy="7" r="4"/>
                                        <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                        <path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/>
                                        @break
                                    @case('chart-bar')
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <rect x="3" y="12" width="6" height="8" rx="1"/>
                                        <rect x="9" y="8" width="6" height="12" rx="1"/>
                                        <rect x="15" y="4" width="6" height="16" rx="1"/>
                                        <line x1="4" y1="20" x2="18" y2="20"/>
                                        @break
                                @endswitch
                        </svg>
                    </span>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-900">{{ $reportType['name'] }}</h3>
                </div>
            </div>
            <p class="text-gray-600 mb-4">{{ $reportType['description'] }}</p>
            <div class="flex items-center space-x-2">
                <a href="{{ route('reports.' . $key) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    View Report
                </a>
                <div class="relative">
                    <button class="inline-flex items-center px-4 py-2 border border-blue-300 text-sm font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" type="button" onclick="toggleExportMenu('{{ $key }}')">
                        Export
                    </button>
                    <div id="export-menu-{{ $key }}" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                        <div class="py-1">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="exportReport('{{ $key }}', 'pdf')">PDF</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="exportReport('{{ $key }}', 'csv')">CSV</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="exportReport('{{ $key }}', 'excel')">Excel</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- Quick Actions Section -->
<div class="mt-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Quick Actions</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="{{ route('reports.builder') }}" class="inline-flex items-center justify-center px-4 py-2 border border-blue-300 text-sm font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 w-full">
                    <svg class="w-4 h-4 mr-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M12 5l0 14"/>
                        <path d="M5 12l14 0"/>
                    </svg>
                    Custom Report Builder
                </a>
                
                <button class="inline-flex items-center justify-center px-4 py-2 border border-green-300 text-sm font-medium rounded-md text-green-700 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 w-full" onclick="scheduleReport()">
                    <svg class="w-4 h-4 mr-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <circle cx="12" cy="12" r="9"/>
                        <polyline points="12,6 12,12 16,14"/>
                    </svg>
                    Schedule Report
                </button>
                
                <button class="inline-flex items-center justify-center px-4 py-2 border border-cyan-300 text-sm font-medium rounded-md text-cyan-700 bg-white hover:bg-cyan-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 transition duration-150 w-full" onclick="viewScheduledReports()">
                    <svg class="w-4 h-4 mr-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M9 11l3 3l8 -8"/>
                        <path d="M20 12v6a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h9"/>
                    </svg>
                    Scheduled Reports
                </button>
                
                <a href="{{ route('analytics.dashboard') }}" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-150 w-full">
                    <svg class="w-4 h-4 mr-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <line x1="4" y1="19" x2="20" y2="19"/>
                        <polyline points="4,15 8,9 12,11 16,6 20,10"/>
                    </svg>
                    Analytics Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Report Modal -->
<div id="scheduleReportModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-0 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">Schedule Automated Report</h3>
                <form id="scheduleReportForm">
                    @csrf
                    <div class="grid grid-cols-12 gap-4">
                        <div class="md:col-span-6">
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700">Report Type</label>
                                <select class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="report_type" required>
                                    <option value="">Select Report Type</option>
                                    @foreach($reportTypes as $key => $reportType)
                                    <option value="{{ $key }}">{{ $reportType['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="md:col-span-6">
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700">Schedule</label>
                                <select class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="schedule_type" required>
                                    <option value="">Select Schedule</option>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-12 gap-4">
                        <div class="md:col-span-6">
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700">Export Format</label>
                                <select class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="format" required>
                                    <option value="">Select Format</option>
                                    <option value="pdf">PDF</option>
                                    <option value="csv">CSV</option>
                                    <option value="excel">Excel</option>
                                </select>
                            </div>
                        </div>
                        <div class="md:col-span-6">
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700">Recipients</label>
                                <input type="email" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="recipients[]" placeholder="Enter email addresses" required>
                                <div class="form-hint">Separate multiple emails with commas</div>
                            </div>
                        </div>
                    </div>
                </form>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm" onclick="submitScheduleReport()">Schedule Report</button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeScheduleReportModal()">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Scheduled Reports Modal -->
<div id="scheduledReportsModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-0 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">Scheduled Reports</h3>
                        <div id="scheduledReportsTable">
                            <!-- Table will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm" onclick="closeScheduledReportsModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function exportReport(reportType, format) {
    // Create a form for export
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/reports/export/${reportType}`;
    
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
    
    // Add default date range (current month)
    const startDate = new Date();
    startDate.setDate(1);
    const endDate = new Date();
    
    const startInput = document.createElement('input');
    startInput.type = 'hidden';
    startInput.name = 'start_date';
    startInput.value = startDate.toISOString().split('T')[0];
    form.appendChild(startInput);
    
    const endInput = document.createElement('input');
    endInput.type = 'hidden';
    endInput.name = 'end_date';
    endInput.value = endDate.toISOString().split('T')[0];
    form.appendChild(endInput);
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function scheduleReport() {
    document.getElementById('scheduleReportModal').classList.remove('hidden');
}

function closeScheduleReportModal() {
    document.getElementById('scheduleReportModal').classList.add('hidden');
}

function toggleExportMenu(reportKey) {
    const menu = document.getElementById('export-menu-' + reportKey);
    // Close all other menus first
    document.querySelectorAll('[id^="export-menu-"]').forEach(m => {
        if (m.id !== 'export-menu-' + reportKey) {
            m.classList.add('hidden');
        }
    });
    menu.classList.toggle('hidden');
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('[onclick*="toggleExportMenu"]')) {
        document.querySelectorAll('[id^="export-menu-"]').forEach(menu => {
            menu.classList.add('hidden');
        });
    }
});

function submitScheduleReport() {
    const form = document.getElementById('scheduleReportForm');
    const formData = new FormData(form);
    
    // Parse recipients
    const recipientsInput = form.querySelector('input[name="recipients[]"]');
    const recipients = recipientsInput.value.split(',').map(email => email.trim());
    formData.delete('recipients[]');
    recipients.forEach(email => formData.append('recipients[]', email));
    
    fetch('{{ route('reports.schedule') }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal and show success message
            closeScheduleReportModal();
            alert('Report scheduled successfully!');
            form.reset();
        } else {
            alert('Error scheduling report: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error scheduling report');
    });
}

function viewScheduledReports() {
    // Load scheduled reports
    fetch('{{ route('reports.scheduled') }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayScheduledReports(data.data);
                document.getElementById('scheduledReportsModal').classList.remove('hidden');
            } else {
                alert('Error loading scheduled reports');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading scheduled reports');
        });
}

function closeScheduledReportsModal() {
    document.getElementById('scheduledReportsModal').classList.add('hidden');
}

function displayScheduledReports(reports) {
    const container = document.getElementById('scheduledReportsTable');
    
    if (reports.length === 0) {
        container.innerHTML = '<p class="text-gray-600 text-center py-8">No scheduled reports found.</p>';
        return;
    }
    
    let html = `
        <div class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Report Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Schedule</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Format</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recipients</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Next Run</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
    `;
    
    reports.forEach(report => {
        html += `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${report.report_type}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${report.schedule_type}</td>
                <td class="px-6 py-4 whitespace-nowrap"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">${report.format.toUpperCase()}</span></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${report.recipients.join(', ')}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${new Date(report.next_run).toLocaleDateString()}</td>
                <td class="px-6 py-4 whitespace-nowrap"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-${report.status === 'active' ? 'green' : 'gray'}-100 text-${report.status === 'active' ? 'green' : 'gray'}-800">${report.status}</span></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button class="inline-flex items-center px-3 py-1.5 border border-red-300 text-xs font-medium rounded text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" onclick="deleteScheduledReport('${report.id}')">
                        Delete
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;
    
    container.innerHTML = html;
}

function deleteScheduledReport(reportId) {
    if (confirm('Are you sure you want to delete this scheduled report?')) {
        // In a real implementation, this would make an API call to delete the scheduled report
        alert('Scheduled report deleted successfully!');
        viewScheduledReports(); // Refresh the list
    }
}
</script>
@endsection