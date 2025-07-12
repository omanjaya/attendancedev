@extends('layouts.authenticated')

@section('title', 'Location Management')

@section('page-content')
    <!-- Page Header -->
    <div class="mb-8 mt-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-foreground">Location Management</h1>
                <p class="text-muted-foreground mt-1">Manage attendance verification locations and GPS settings</p>
            </div>
            
            <div class="flex items-center space-x-3">
                <x-ui.button variant="outline" size="sm">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filter
                </x-ui.button>
                @can('manage_locations')
                    <x-ui.button href="{{ route('locations.create') }}">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Add Location
                    </x-ui.button>
                @endcan
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <!-- Total Locations -->
        <div class="bg-white/70 backdrop-blur-sm rounded-2xl p-6 shadow-lg border border-white/20 hover:shadow-xl transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Locations</p>
                    <p class="text-3xl font-bold text-gray-900" id="stat-total">5</p>
                    <p class="text-sm text-gray-500">All sites</p>
                </div>
                <div class="bg-blue-100 rounded-xl p-3 group-hover:scale-110 transition-transform">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active Locations -->
        <div class="bg-white/70 backdrop-blur-sm rounded-2xl p-6 shadow-lg border border-white/20 hover:shadow-xl transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Active Locations</p>
                    <p class="text-3xl font-bold text-gray-900" id="stat-active">4</p>
                    <p class="text-sm text-gray-500">Currently enabled</p>
                </div>
                <div class="bg-green-100 rounded-xl p-3 group-hover:scale-110 transition-transform">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Employees Assigned -->
        <div class="bg-white/70 backdrop-blur-sm rounded-2xl p-6 shadow-lg border border-white/20 hover:shadow-xl transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Employees Assigned</p>
                    <p class="text-3xl font-bold text-gray-900" id="stat-employees">18</p>
                    <p class="text-sm text-gray-500">Across all locations</p>
                </div>
                <div class="bg-purple-100 rounded-xl p-3 group-hover:scale-110 transition-transform">
                    <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Average Radius -->
        <div class="bg-white/70 backdrop-blur-sm rounded-2xl p-6 shadow-lg border border-white/20 hover:shadow-xl transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Average Radius</p>
                    <p class="text-3xl font-bold text-gray-900" id="stat-radius">50m</p>
                    <p class="text-sm text-gray-500">GPS verification</p>
                </div>
                <div class="bg-amber-100 rounded-xl p-3 group-hover:scale-110 transition-transform">
                    <svg class="h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Locations Table -->
    <div class="bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg border border-white/20">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">All Locations</h3>
            <p class="text-sm text-gray-600">Manage attendance verification locations</p>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <min-w-full divide-y divide-gray-200 class="w-full min-w-full divide-y divide-gray-200-auto" id="locationsTable">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Location</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Address</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Coordinates</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Radius</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Verification</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Employees</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Populated by JavaScript -->
                    </tbody>
                </min-w-full divide-y divide-gray-200>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize min-w-full divide-y divide-gray-200 with sample data
    initializeLocationsTable();
    
    // Event delegation for dynamic buttons
    document.addEventListener('click', function(e) {
        if (e.target.matches('.delete-location') || e.target.closest('.delete-location')) {
            const button = e.target.matches('.delete-location') ? e.target : e.target.closest('.delete-location');
            const locationId = button.dataset.id;
            deleteLocation(locationId);
        }
        
        if (e.target.matches('.toggle-status') || e.target.closest('.toggle-status')) {
            const button = e.target.matches('.toggle-status') ? e.target : e.target.closest('.toggle-status');
            const locationId = button.dataset.id;
            toggleLocationStatus(locationId);
        }
    });
});

function initializeLocationsTable() {
    const tableBody = document.querySelector('#locationsTable tbody');
    const sampleLocations = [
        {
            id: 1,
            name: 'Main Campus',
            address: '123 Education St, City Center',
            coordinates: '-6.2088, 106.8456',
            radius: '100m',
            verification: ['GPS', 'Face Recognition'],
            employees: 15,
            status: 'Active'
        },
        {
            id: 2,
            name: 'Branch Office',
            address: '456 Learning Ave, District 2',
            coordinates: '-6.1751, 106.8650',
            radius: '50m',
            verification: ['GPS'],
            employees: 8,
            status: 'Active'
        },
        {
            id: 3,
            name: 'Remote Site',
            address: '789 Education Blvd, Suburb',
            coordinates: '-6.2297, 106.6890',
            radius: '75m',
            verification: ['GPS', 'Face Recognition'],
            employees: 5,
            status: 'Inactive'
        }
    ];
    
    tableBody.innerHTML = sampleLocations.map(location => `
        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="py-3 px-4">
                <div class="flex items-center">
                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                        <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="font-medium text-gray-900">${location.name}</span>
                        <div class="text-sm text-gray-500">ID: LOC${location.id.toString().padStart(3, '0')}</div>
                    </div>
                </div>
            </td>
            <td class="py-3 px-4 text-gray-700">${location.address}</td>
            <td class="py-3 px-4">
                <span class="font-mono text-sm text-gray-600">${location.coordinates}</span>
            </td>
            <td class="py-3 px-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    ${location.radius}
                </span>
            </td>
            <td class="py-3 px-4">
                <div class="flex flex-wrap gap-1">
                    ${location.verification.map(method => `
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                            method === 'GPS' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800'
                        }">
                            ${method}
                        </span>
                    `).join('')}
                </div>
            </td>
            <td class="py-3 px-4">
                <span class="font-medium text-gray-900">${location.employees}</span>
                <span class="text-sm text-gray-500">assigned</span>
            </td>
            <td class="py-3 px-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                    location.status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                }">
                    ${location.status}
                </span>
            </td>
            <td class="py-3 px-4">
                <div class="flex items-center space-x-2">
                    <a href="/locations/${location.id}" class="p-1 text-gray-400 hover:text-blue-600 transition-colors" title="View Details">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </a>
                    <a href="/locations/${location.id}/edit" class="p-1 text-gray-400 hover:text-blue-600 transition-colors" title="Edit Location">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </a>
                    <button class="toggle-status p-1 text-gray-400 hover:text-amber-600 transition-colors" data-id="${location.id}" title="Toggle Status">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                        </svg>
                    </button>
                    <button class="delete-location p-1 text-gray-400 hover:text-red-600 transition-colors" data-id="${location.id}" title="Delete Location">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function deleteLocation(locationId) {
    if (confirm('Are you sure you want to delete this location? This action cannot be undone.')) {
        // In a real implementation, this would send delete request to server
        console.log('Deleting location:', locationId);
        showNotification('Location deleted successfully', 'success');
        
        // Remove grid grid-cols-12 gap-4 from min-w-full divide-y divide-gray-200 (demo)
        const grid grid-cols-12 gap-4 = document.querySelector(`[data-id="${locationId}"]`).closest('tr');
        if (grid grid-cols-12 gap-4) grid grid-cols-12 gap-4.remove();
    }
}

function toggleLocationStatus(locationId) {
    if (confirm('Are you sure you want to change this location\'s status?')) {
        // In a real implementation, this would send request to server
        console.log('Toggling status for location:', locationId);
        showNotification('Location status updated successfully', 'success');
    }
}

function showNotification(message, type = 'info') {
    // Simple notification system
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white transition-all duration-300 ${
        type === 'success' ? 'bg-green-500' :
        type === 'error' ? 'bg-red-500' :
        type === 'warning' ? 'bg-amber-500' : 'bg-blue-500'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
</script>
@endsection