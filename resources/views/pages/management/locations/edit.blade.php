@extends('layouts.authenticated')

@section('title', 'Edit Location')

@section('page-header')
@section('page-pretitle', 'Locations')
@section('page-title', 'Edit Location: {{ $location->name }}')
@endsection

@section('page-content')
<form action="{{ route('locations.update', $location) }}" method="POST">
    @csrf
    @method('PUT')
    
    <div class="grid grid-cols-12 gap-4">
        <div class="lg:col-span-8">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200-header">
                    <h3 class="bg-white rounded-lg shadow-sm border border-gray-200-title">Location Information</h3>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                    <div class="grid grid-cols-12 gap-4 mb-3">
                        <div class="md:col-span-6">
                            <div class="block text-sm font-medium text-gray-700 required">Location Name</div>
                            <input type="text" name="name" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('name') is-invalid @enderror" 
                                   value="{{ old('name', $location->name) }}" placeholder="Main Campus" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="md:col-span-6">
                            <div class="block text-sm font-medium text-gray-700 required">Allowed Radius</div>
                            <div class="flex rounded-md shadow-sm">
                                <input type="number" name="radius_meters" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('radius_meters') is-invalid @enderror" 
                                       value="{{ old('radius_meters', $location->radius_meters) }}" min="10" max="1000" required>
                                <span class="flex rounded-md shadow-sm-text">meters</span>
                            </div>
                            @error('radius_meters')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-hint">How close employees need to be for location verification</small>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-12 gap-4 mb-3">
                        <div class="col-span-12">
                            <div class="block text-sm font-medium text-gray-700">Address</div>
                            <textarea name="address" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('address') is-invalid @enderror" 
                                      rows="2" placeholder="123 Main Street, City, State, ZIP">{{ old('address', $location->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-12 gap-4 mb-3">
                        <div class="md:col-span-6">
                            <div class="block text-sm font-medium text-gray-700">Latitude</div>
                            <input type="number" name="latitude" id="latitude" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('latitude') is-invalid @enderror" 
                                   value="{{ old('latitude', $location->latitude) }}" step="0.000001" placeholder="40.712776">
                            @error('latitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-hint">For GPS-based attendance verification</small>
                        </div>
                        
                        <div class="md:col-span-6">
                            <div class="block text-sm font-medium text-gray-700">Longitude</div>
                            <input type="number" name="longitude" id="longitude" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('longitude') is-invalid @enderror" 
                                   value="{{ old('longitude', $location->longitude) }}" step="0.000001" placeholder="-74.005974">
                            @error('longitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-hint">For GPS-based attendance verification</small>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-12 gap-4 mb-3">
                        <div class="col-span-12">
                            <button type="button" id="getCurrentLocation" class="inline-flex items-center px-4 py-2 border border-blue-300 text-sm font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-4.5 -7l-7 -4.5a.55 .55 0 0 1 0 -1l18 -6.5"/>
                                </svg>
                                Update to Current Location
                            </button>
                            <span id="locationStatus" class="text-gray-600 ml-2"></span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-12 gap-4 mb-3">
                        <div class="md:col-span-6">
                            <div class="block text-sm font-medium text-gray-700">WiFi Network Name (SSID)</div>
                            <input type="text" name="wifi_ssid" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('wifi_ssid') is-invalid @enderror" 
                                   value="{{ old('wifi_ssid', $location->wifi_ssid) }}" placeholder="School-WiFi">
                            @error('wifi_ssid')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-hint">Optional: For WiFi-based location verification</small>
                        </div>
                        
                        <div class="md:col-span-6">
                            <div class="block text-sm font-medium text-gray-700">Status</div>
                            <div class="flex items-center form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="flex items-center-input" type="checkbox" name="is_active" value="1" 
                                       {{ old('is_active', $location->is_active) ? 'checked' : '' }}>
                                <label class="flex items-center-label">Active Location</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="lg:col-span-4">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200-header">
                    <h3 class="bg-white rounded-lg shadow-sm border border-gray-200-title">Location Preview</h3>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                    <div id="mapContainer" style="height: 300px; background-color: #f8f9fa; border-radius: 8px; display: flex; align-items: center; justify-content: center; border: 1px dashed #dee2e6;">
                        <div class="text-center text-gray-600">
                            <svg class="icon mb-2" width="48" height="48" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-4.5 -7l-7 -4.5a.55 .55 0 0 1 0 -1l18 -6.5"/>
                            </svg>
                            <div>Enter coordinates to preview location</div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-12">
                                <strong>Verification Methods:</strong>
                                <div id="verificationMethods" class="mt-1">
                                    <span class="text-gray-600">None configured</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-4">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200-body">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 w-full">
                        <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/>
                            <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"/>
                            <path d="M16 5l3 3"/>
                        </svg>
                        Update Location
                    </button>
                    <a href="{{ route('locations.index') }}" class="btn btn-link w-full mt-2">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Update verification methods preview
    function updateVerificationMethods() {
        const latitude = $('#latitude').val();
        const longitude = $('#longitude').val();
        const wifiSsid = $('[name="wifi_ssid"]').val();
        
        let methods = [];
        
        if (latitude && longitude) {
            methods.push('<span class="badge bg-blue-lt">GPS Location</span>');
        }
        
        if (wifiSsid) {
            methods.push('<span class="badge bg-green-lt">WiFi Network</span>');
        }
        
        const max-w-7xl mx-auto px-4 = $('#verificationMethods');
        if (methods.length > 0) {
            max-w-7xl mx-auto px-4.html(methods.join(' '));
        } else {
            max-w-7xl mx-auto px-4.html('<span class="text-gray-600">None configured</span>');
        }
    }
    
    // Update map preview (placeholder)
    function updateMapPreview() {
        const latitude = $('#latitude').val();
        const longitude = $('#longitude').val();
        
        const mapContainer = $('#mapContainer');
        
        if (latitude && longitude) {
            mapContainer.html(`
                <div class="text-center text-success">
                    <svg class="icon mb-2" width="48" height="48" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-4.5 -7l-7 -4.5a.55 .55 0 0 1 0 -1l18 -6.5"/>
                    </svg>
                    <div><strong>Location Set</strong></div>
                    <small>${latitude}, ${longitude}</small>
                </div>
            `);
        } else {
            mapContainer.html(`
                <div class="text-center text-gray-600">
                    <svg class="icon mb-2" width="48" height="48" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-4.5 -7l-7 -4.5a.55 .55 0 0 1 0 -1l18 -6.5"/>
                    </svg>
                    <div>Enter coordinates to preview location</div>
                </div>
            `);
        }
    }
    
    // Event listeners
    $('#latitude, #longitude, [name="wifi_ssid"]').on('input', function() {
        updateVerificationMethods();
        updateMapPreview();
    });
    
    // Get current location
    $('#getCurrentLocation').on('click', function() {
        const button = $(this);
        const status = $('#locationStatus');
        
        if (!navigator.geolocation) {
            status.text('Geolocation is not supported by this browser.').removeClass('text-gray-600').addClass('text-danger');
            return;
        }
        
        button.prop('disabled', true).html(`
            <span class="spinner-border spinner-border-sm mr-2" role="status"></span>
            Getting location...
        `);
        status.text('Requesting location permission...').removeClass('text-danger').addClass('text-gray-600');
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                $('#latitude').val(position.coords.latitude.toFixed(6));
                $('#longitude').val(position.coords.longitude.toFixed(6));
                
                status.text('Location retrieved successfully!').removeClass('text-gray-600').addClass('text-success');
                updateVerificationMethods();
                updateMapPreview();
                
                button.prop('disabled', false).html(`
                    <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-4.5 -7l-7 -4.5a.55 .55 0 0 1 0 -1l18 -6.5"/>
                    </svg>
                    Update to Current Location
                `);
            },
            function(error) {
                let message = 'Unable to retrieve location.';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        message = 'Location access denied by user.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        message = 'Location information unavailable.';
                        break;
                    case error.TIMEOUT:
                        message = 'Location request timed out.';
                        break;
                }
                
                status.text(message).removeClass('text-gray-600').addClass('text-danger');
                button.prop('disabled', false).html(`
                    <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-4.5 -7l-7 -4.5a.55 .55 0 0 1 0 -1l18 -6.5"/>
                    </svg>
                    Update to Current Location
                `);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000
            }
        );
    });
    
    // Initialize
    updateVerificationMethods();
    updateMapPreview();
});
</script>
@endpush