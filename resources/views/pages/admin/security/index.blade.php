@extends('layouts.authenticated')

@section('title', 'Security Dashboard')

@section('page-content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-8">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div class="mb-4 sm:mb-0">
                <h2 class="text-3xl font-bold text-gray-900">Security Dashboard</h2>
                <div class="text-gray-600 mt-1">Monitor security metrics and manage user access</div>
            </div>
            <div class="flex-shrink-0">
                <div class="flex flex-col sm:flex-row sm:space-x-2 space-y-2 sm:space-y-0">
                    <a href="{{ route('admin.security.report') }}" class="inline-flex items-center px-4 py-2 border border-blue-300 text-sm font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2"/>
                            <rect x="9" y="3" width="6" height="4" rx="2"/>
                            <path d="M14 11h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5"/>
                            <path d="M12 17v1m0 -8v1"/>
                        </svg>
                        Security Report
                    </a>
                    <a href="{{ route('admin.security.users') }}" class="inline-flex items-center px-4 py-2 border border-blue-300 text-sm font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="m3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                            <path d="m16 3.13a4 4 0 0 1 0 7.75"/>
                            <path d="m21 21v-2a4 4 0 0 0 -3 -3.85"/>
                        </svg>
                        Manage Users
                    </a>
                </div>
            </div>
        </div>

        <!-- Threat Level Alert -->
        @if($threatLevels['level'] >= 6)
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md flex items-center" role="alert">
            <div class="mr-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-600" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <circle cx="12" cy="12" r="9"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <div>
                <strong>{{ $threatLevels['description'] }} Threat Level Detected!</strong>
                <p class="mb-0">Current threat level: {{ $threatLevels['level'] }}/10. Please review security incidents immediately.</p>
            </div>
        </div>
        @endif

        <!-- Security Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="mr-3">
                        <span class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-600" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="12" cy="12" r="9"/>
                                <line x1="9" y1="9" x2="15" y2="15"/>
                                <line x1="15" y1="9" x2="9" y2="15"/>
                            </svg>
                        </span>
                    </div>
                    <div>
                        <div class="font-medium">Failed Logins</div>
                        <div class="text-gray-600">Last 30 days</div>
                    </div>
                </div>
                <div class="text-2xl font-bold mt-4">{{ number_format($metrics['failed_logins']) }}</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="mr-3">
                        <span class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-yellow-600" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="12" cy="12" r="9"/>
                                <line x1="12" y1="8" x2="12" y2="12"/>
                                <line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                        </span>
                    </div>
                    <div>
                        <div class="font-medium">Suspicious Activities</div>
                        <div class="text-gray-600">Last 30 days</div>
                    </div>
                </div>
                <div class="text-2xl font-bold mt-4">{{ number_format($metrics['suspicious_activities']) }}</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="mr-3">
                        <span class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-green-600" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3"/>
                                <circle cx="12" cy="11" r="1"/>
                                <path d="m12 12l0 2.5"/>
                            </svg>
                        </span>
                    </div>
                    <div>
                        <div class="font-medium">2FA Users</div>
                        <div class="text-gray-600">{{ $twoFactorStats['compliance_rate'] }}% compliance</div>
                    </div>
                </div>
                <div class="text-2xl font-bold mt-4">{{ number_format($metrics['2fa_enabled_users']) }}</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="mr-3">
                        <span class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-blue-600" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="12" cy="12" r="9"/>
                                <line x1="9" y1="10" x2="9.01" y2="10"/>
                                <line x1="15" y1="10" x2="15.01" y2="10"/>
                                <path d="M9.5 15a3.5 3.5 0 0 0 5 0"/>
                            </svg>
                        </span>
                    </div>
                    <div>
                        <div class="font-medium">Active Sessions</div>
                        <div class="text-gray-600">Current users</div>
                    </div>
                </div>
                <div class="text-2xl font-bold mt-4">{{ number_format($metrics['active_sessions']) }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Current Threat Level -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Current Threat Level</h3>
                    </div>
                    <div class="p-6 text-center">
                        <div class="mb-3">
                            <div class="inline-block">
                                <svg width="120" height="120" viewBox="0 0 120 120">
                                    <circle cx="60" cy="60" r="50" fill="none" stroke="#e9ecef" stroke-width="8"/>
                                    <circle cx="60" cy="60" r="50" fill="none" 
                                            stroke="{{ $threatLevels['level'] >= 8 ? '#dc3545' : ($threatLevels['level'] >= 6 ? '#fd7e14' : ($threatLevels['level'] >= 4 ? '#ffc107' : '#198754')) }}" 
                                            stroke-width="8" 
                                            stroke-dasharray="{{ $threatLevels['level'] * 31.4 }} 314" 
                                            stroke-dashoffset="78.5" 
                                            transform="rotate(-90 60 60)"/>
                                    <text x="60" y="65" text-anchor="middle" font-size="20" font-weight="bold" 
                                          fill="{{ $threatLevels['level'] >= 8 ? '#dc3545' : ($threatLevels['level'] >= 6 ? '#fd7e14' : ($threatLevels['level'] >= 4 ? '#ffc107' : '#198754')) }}">
                                        {{ $threatLevels['level'] }}
                                    </text>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-xl font-semibold mb-1">{{ $threatLevels['description'] }}</h3>
                        <p class="text-gray-600">Threat Level</p>
                        <div class="grid grid-cols-3 gap-4 mt-6">
                            <div class="text-center">
                                <div class="text-lg font-semibold text-red-600">{{ $threatLevels['failed_logins'] }}</div>
                                <div class="text-gray-600 text-xs">Failed Logins Today</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-semibold text-yellow-600">{{ $threatLevels['suspicious_activity'] }}</div>
                                <div class="text-gray-600 text-xs">Suspicious Events</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-semibold text-orange-600">{{ $threatLevels['locked_users'] }}</div>
                                <div class="text-gray-600 text-xs">Locked Accounts</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Recommendations -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Security Recommendations</h3>
                    </div>
                    <div class="p-6">
                        @if(empty($recommendations))
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-green-600" width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3"/>
                                    <path d="M9 12l2 2l4 -4"/>
                                </svg>
                            </div>
                            <h3 class="text-green-600 text-lg font-semibold">All Good!</h3>
                            <p class="text-gray-600">No security recommendations at this time. Your system appears to be secure.</p>
                        </div>
                        @else
                        <div class="space-y-4">
                            @foreach($recommendations as $recommendation)
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-start">
                                    <div class="mr-3 mt-1">
                                        @if($recommendation['type'] === 'alert')
                                        <span class="inline-block w-3 h-3 bg-red-500 rounded-full"></span>
                                        @elseif($recommendation['type'] === 'warning')
                                        <span class="inline-block w-3 h-3 bg-yellow-500 rounded-full"></span>
                                        @else
                                        <span class="inline-block w-3 h-3 bg-blue-500 rounded-full"></span>
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <h4 class="font-semibold text-gray-900 mb-1">{{ $recommendation['title'] }}</h4>
                                                <p class="text-gray-600 mb-2">{{ $recommendation['description'] }}</p>
                                                <div class="text-blue-600 text-sm">
                                                    <strong>Recommended Action:</strong> {{ $recommendation['action'] }}
                                                </div>
                                            </div>
                                            <div class="ml-3 flex-shrink-0">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $recommendation['priority'] === 'high' ? 'border-red-200 text-red-800 bg-red-50' : ($recommendation['priority'] === 'medium' ? 'border-yellow-200 text-yellow-800 bg-yellow-50' : 'border-blue-200 text-blue-800 bg-blue-50') }}">
                                                    {{ ucfirst($recommendation['priority']) }} Priority
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Security Events -->
        <div class="mt-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Security Events</h3>
                    <div class="relative">
                        <select class="block px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" onchange="window.location.href = this.value">
                            <option value="{{ route('admin.security.index') }}?period=1">Last 24 hours</option>
                            <option value="{{ route('admin.security.index') }}?period=7" selected>Last 7 days</option>
                            <option value="{{ route('admin.security.index') }}?period=30">Last 30 days</option>
                        </select>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Risk Level</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-8"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($recentEvents as $event)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-600">{{ $event['timestamp'] }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ ucfirst(str_replace('_', ' ', $event['action'])) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center text-xs font-medium text-gray-700 mr-3">
                                            {{ substr($event['user'], 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $event['user'] }}</div>
                                            <div class="text-sm text-gray-500">{{ $event['email'] }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-mono text-gray-900">{{ $event['ip_address'] ?? 'Unknown' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $event['risk_level'] === 'high' ? 'bg-red-100 text-red-800' : ($event['risk_level'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') }}">
                                        {{ ucfirst($event['risk_level']) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button onclick="viewEventDetails({{ json_encode($event) }})" class="text-blue-600 hover:text-blue-900">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <circle cx="12" cy="12" r="1"/>
                                            <circle cx="12" cy="19" r="1"/>
                                            <circle cx="12" cy="5" r="1"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="text-gray-500">No recent security events</div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Event Details Modal -->
<div id="eventDetailsModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900" id="modal-title">Security Event Details</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-600" onclick="closeModal()">
                        <span class="sr-only">Close</span>
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            <div class="bg-white px-6 py-4" id="eventDetailsContent">
                <!-- Event details will be populated here -->
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end">
                <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewEventDetails(event) {
    const modal = document.getElementById('eventDetailsModal');
    const content = document.getElementById('eventDetailsContent');
    
    let detailsHtml = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h5 class="text-sm font-semibold text-gray-900 mb-3">Event Information</h5>
                <dl class="space-y-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Event ID:</dt>
                        <dd class="text-sm text-gray-900">${event.id}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Action:</dt>
                        <dd class="text-sm text-gray-900">${event.action.replace(/_/g, ' ')}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Timestamp:</dt>
                        <dd class="text-sm text-gray-900">${event.timestamp}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Risk Level:</dt>
                        <dd class="text-sm text-gray-900">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${event.risk_level === 'high' ? 'bg-red-100 text-red-800' : (event.risk_level === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800')}">
                                ${event.risk_level}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>
            <div>
                <h5 class="text-sm font-semibold text-gray-900 mb-3">User Information</h5>
                <dl class="space-y-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Name:</dt>
                        <dd class="text-sm text-gray-900">${event.user}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Email:</dt>
                        <dd class="text-sm text-gray-900">${event.email}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">IP Address:</dt>
                        <dd class="text-sm text-gray-900 font-mono">${event.ip_address || 'Unknown'}</dd>
                    </div>
                </dl>
            </div>
        </div>
    `;
    
    if (event.details && Object.keys(event.details).length > 0) {
        detailsHtml += `
            <div class="mt-6 pt-6 border-t border-gray-200">
                <h5 class="text-sm font-semibold text-gray-900 mb-3">Additional Details</h5>
                <pre class="bg-gray-100 p-3 rounded text-sm overflow-x-auto"><code>${JSON.stringify(event.details, null, 2)}</code></pre>
            </div>
        `;
    }
    
    content.innerHTML = detailsHtml;
    modal.classList.remove('hidden');
}

function closeModal() {
    document.getElementById('eventDetailsModal').classList.add('hidden');
}

// Auto-refresh dashboard every 30 seconds
setInterval(function() {
    window.location.reload();
}, 30000);

// Close modal when clicking outside
document.getElementById('eventDetailsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
@endsection