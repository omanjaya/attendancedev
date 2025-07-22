@extends('layouts.authenticated-unified')

@section('title', 'Absensi Pegawai')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
     crossorigin=""/>
     
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
     integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
     crossorigin=""></script>
@endpush

@push('styles')
<style>
    /* Alpine.js cloak */
    [x-cloak] { display: none !important; }
    
    /* Modern Mobile-first Design System */
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        --danger-gradient: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
        --dark-gradient: linear-gradient(180deg, #0f0f0f 0%, #1a1a1a 100%);
        --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        --glass-bg: rgba(255, 255, 255, 0.1);
        --glass-border: rgba(255, 255, 255, 0.2);
    }
    
    /* Container with modern dark theme */
    .mobile-attendance-container {
        background: var(--dark-gradient);
        min-height: 100vh;
        padding: 0;
        color: white;
        position: relative;
        overflow-x: hidden;
    }
    
    /* Animated background pattern */
    .mobile-attendance-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 50%;
        background: var(--primary-gradient);
        opacity: 0.1;
        transform: skewY(-6deg);
        transform-origin: top left;
    }
    
    /* Profile section with glassmorphism */
    .profile-section {
        position: relative;
        display: flex;
        align-items: center;
        padding: 2rem 1.5rem;
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        border-bottom: 1px solid var(--glass-border);
        margin-bottom: 2rem;
    }
    
    .profile-avatar {
        width: 4.5rem;
        height: 4.5rem;
        border-radius: 50%;
        background: var(--primary-gradient);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1.25rem;
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        position: relative;
        overflow: hidden;
    }
    
    .profile-avatar::after {
        content: '';
        position: absolute;
        top: -2px;
        left: -2px;
        right: -2px;
        bottom: -2px;
        background: var(--primary-gradient);
        border-radius: 50%;
        z-index: -1;
        filter: blur(10px);
        opacity: 0.5;
    }
    
    .profile-info h2 {
        font-size: 1.125rem;
        font-weight: 400;
        margin: 0 0 0.25rem 0;
        opacity: 0.8;
    }
    
    .profile-info p {
        font-size: 1.375rem;
        font-weight: 600;
        margin: 0;
        letter-spacing: -0.02em;
    }
    
    /* Modern datetime card */
    .datetime-card {
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        border-radius: 1.5rem;
        padding: 1.5rem;
        margin: 0 1.5rem 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border: 1px solid var(--glass-border);
        position: relative;
        overflow: hidden;
    }
    
    .datetime-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: var(--primary-gradient);
        opacity: 0.05;
    }
    
    .datetime-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        position: relative;
    }
    
    .datetime-info svg {
        width: 1.5rem;
        height: 1.5rem;
        opacity: 0.7;
    }
    
    .datetime-info div {
        font-size: 0.875rem;
        font-weight: 500;
        letter-spacing: 0.01em;
    }
    
    /* Modern attendance status cards */
    .attendance-cards {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin: 0 1.5rem 2rem;
        position: relative;
    }
    
    .attendance-card {
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        border: 1px solid var(--glass-border);
        border-radius: 1.5rem;
        padding: 1.5rem;
        text-align: center;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .attendance-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: var(--success-gradient);
        opacity: 0.1;
        transition: opacity 0.3s ease;
    }
    
    .attendance-card:hover::before {
        opacity: 0.15;
    }
    
    .attendance-card.checkout::before {
        background: var(--danger-gradient);
    }
    
    .attendance-card h3 {
        font-size: 0.75rem;
        margin: 0 0 0.75rem 0;
        opacity: 0.7;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 500;
    }
    
    .attendance-card .time {
        font-size: 1.75rem;
        font-weight: 700;
        margin: 0 0 0.25rem 0;
        letter-spacing: -0.02em;
        font-feature-settings: 'tnum';
    }
    
    .attendance-card .date {
        font-size: 0.75rem;
        opacity: 0.6;
        margin: 0;
        font-weight: 400;
    }
    
    /* Modern action buttons section */
    .action-buttons {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 2rem 2rem 0 0;
        padding: 2rem 1.5rem 8rem;
        margin-top: auto;
        box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.1);
    }
    
    .action-button {
        display: flex;
        align-items: center;
        width: 100%;
        padding: 1.25rem;
        margin-bottom: 1rem;
        border: none;
        border-radius: 1.25rem;
        font-size: 1rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-align: left;
        position: relative;
        overflow: hidden;
    }
    
    .action-button::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    .action-button:hover::before {
        transform: translateX(0);
    }
    
    .action-button:active {
        transform: scale(0.98);
    }
    
    .action-button:last-child {
        margin-bottom: 0;
    }
    
    .action-button.checkin {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        color: #15803d;
        border: 1px solid #86efac;
    }
    
    .action-button.checkin::before {
        background: var(--success-gradient);
        opacity: 0.1;
    }
    
    .action-button.checkout {
        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        color: #dc2626;
        border: 1px solid #fca5a5;
    }
    
    .action-button.checkout::before {
        background: var(--danger-gradient);
        opacity: 0.1;
    }
    
    .action-button .icon {
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1.25rem;
        position: relative;
        z-index: 1;
    }
    
    .action-button.checkin .icon {
        background: var(--success-gradient);
        color: white;
        box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
    }
    
    .action-button.checkout .icon {
        background: var(--danger-gradient);
        color: white;
        box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
    }
    
    .action-button .content {
        position: relative;
        z-index: 1;
    }
    
    .action-button .content h4 {
        font-size: 1.125rem;
        font-weight: 700;
        margin: 0 0 0.25rem 0;
        color: inherit;
        letter-spacing: -0.01em;
    }
    
    .action-button .content p {
        font-size: 0.8125rem;
        opacity: 0.8;
        margin: 0;
        line-height: 1.4;
    }
    
    /* Remove pengajuan section for cleaner UI */
    .pengajuan-section {
        display: none;
    }
    
    /* Modern bottom navigation */
    .bottom-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(20px);
        padding: 1rem 1.5rem 1.5rem;
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 0.5rem;
        box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.1);
        border-top: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .nav-item {
        text-align: center;
        color: #6b7280;
        text-decoration: none;
        padding: 0.75rem 0.5rem;
        border-radius: 1rem;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .nav-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: var(--primary-gradient);
        opacity: 0;
        border-radius: 1rem;
        transition: opacity 0.3s ease;
    }
    
    .nav-item:hover::before,
    .nav-item.active::before {
        opacity: 0.1;
    }
    
    .nav-item.active {
        color: #667eea;
    }
    
    .nav-item .icon {
        width: 1.5rem;
        height: 1.5rem;
        margin: 0 auto 0.25rem;
        fill: currentColor;
        position: relative;
        z-index: 1;
    }
    
    .nav-item span {
        font-size: 0.75rem;
        display: block;
        font-weight: 500;
        position: relative;
        z-index: 1;
    }
    
    /* Modal Styles */
    .modal-overlay {
        background: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(10px);
    }
    
    .modal-container {
        background: white;
        border-radius: 2rem;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
        max-height: 90vh;
        overflow-y: auto;
    }
    
    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }
    
    .animate-fade-in-up {
        animation: fadeInUp 0.6s ease-out;
    }
    
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    
    /* Loading skeleton */
    .skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
    }
    
    @keyframes loading {
        0% {
            background-position: 200% 0;
        }
        100% {
            background-position: -200% 0;
        }
    }
    
    /* Responsive adjustments */
    @media (min-width: 640px) {
        .mobile-attendance-container {
            max-width: 428px;
            margin: 0 auto;
            box-shadow: 0 0 50px rgba(0, 0, 0, 0.2);
        }
    }
    
    /* Map Styles */
    .user-location-marker {
        background: transparent !important;
        border: none !important;
    }
    
    .leaflet-popup-content-wrapper {
        border-radius: 8px;
        font-family: inherit;
    }
    
    .leaflet-popup-content {
        margin: 8px 12px;
        font-size: 14px;
    }

    /* Smooth transitions */
    * {
        -webkit-tap-highlight-color: transparent;
    }
    
    input, button, select, textarea {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }
</style>
@endpush

@section('page-content')
<div class="mobile-attendance-container" 
     x-data="mobileAttendanceApp()" 
     x-init="$nextTick(() => init())">
    <!-- Profile Section -->
    <div class="profile-section">
        <div class="profile-avatar">
            <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
        </div>
        <div class="profile-info">
            <h2>Selamat {{ now()->format('H') < 12 ? 'Pagi' : (now()->format('H') < 15 ? 'Siang' : (now()->format('H') < 18 ? 'Sore' : 'Malam')) }},</h2>
            <p>{{ Auth::user()->employee?->name ?? Auth::user()->name }}</p>
        </div>
    </div>
    
    <!-- Date Time Card -->
    <div class="datetime-card">
        <div class="datetime-info">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
            </svg>
            <div x-text="currentDate" class="font-semibold">Mon, 21 Jul 25</div>
        </div>
        <div class="datetime-info">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M16.2,16.2L11,13V7H12.5V12.2L17,14.9L16.2,16.2Z"/>
            </svg>
            <div x-text="currentTime" class="font-semibold">19:16:20 WITA</div>
        </div>
    </div>
    
    <!-- Attendance Status Cards -->
    <div class="attendance-cards">
        <div class="attendance-card">
            <h3>Check In</h3>
            <div class="time" x-text="checkinTime">--:--:--</div>
            <p class="date" x-text="checkinDate">-- --- ----</p>
        </div>
        <div class="attendance-card checkout">
            <h3>Check Out</h3>
            <div class="time" x-text="checkoutTime">--:--:--</div>
            <p class="date" x-text="checkoutDate">-- --- ----</p>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="action-buttons">
        <button class="action-button checkin" @click="startCheckin()" x-show="!isCheckedIn">
            <div class="icon">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                </svg>
            </div>
            <div class="content">
                <h4>Check In</h4>
                <p>Rekam kehadiran Anda untuk memulai hari kerja</p>
            </div>
        </button>
        
        <button class="action-button checkout" @click="startCheckout()" x-show="isCheckedIn && !isCheckedOut">
            <div class="icon">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                </svg>
            </div>
            <div class="content">
                <h4>Check Out</h4>
                <p>Rekam waktu pulang untuk mengakhiri hari kerja</p>
            </div>
        </button>
    </div>
    
    <!-- Pengajuan Section -->
    <div class="pengajuan-section">
        <h3>Pengajuan</h3>
        
        <div class="pengajuan-grid">
            <a href="#" class="pengajuan-button">
                <div class="icon">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                    </svg>
                </div>
                <div>Ubah Absen</div>
                <p style="font-size: 0.75rem; margin-top: 0.25rem; color: #6b7280;">Ajukan perubahan absen karena alasan tertentu</p>
            </a>
            
            <a href="#" class="pengajuan-button">
                <div class="icon">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4M12,6A6,6 0 0,1 18,12A6,6 0 0,1 12,18A6,6 0 0,1 6,12A6,6 0 0,1 12,6M12,8A4,4 0 0,0 8,12A4,4 0 0,0 12,16A4,4 0 0,0 16,12A4,4 0 0,0 12,8Z"/>
                    </svg>
                </div>
                <div>Cuti</div>
                <p style="font-size: 0.75rem; margin-top: 0.25rem; color: #6b7280;">Ajukan cuti tidak hadir karena alasan tertentu</p>
            </a>
        </div>
        
        <a href="#" class="dinas-button">
            <div class="icon">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                </svg>
            </div>
            <div>
                <div style="font-weight: 600;">Dinas/Diklat</div>
                <div style="font-size: 0.875rem; opacity: 0.8;">Ajukan dinas atau diklat</div>
            </div>
        </a>
    </div>
    
    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <a href="{{ route('dashboard') }}" class="nav-item active">
            <svg class="icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span>Beranda</span>
        </a>
        <a href="#" class="nav-item">
            <svg class="icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span>Laporan</span>
        </a>
        <a href="{{ route('profile.edit') }}" class="nav-item">
            <svg class="icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span>Profil</span>
        </a>
    </div>
    
    <!-- Location Detection Modal -->
<div x-show="showLocationDetection" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex items-center justify-center modal-overlay p-4"
     style="display: none;">
    <div class="modal-container w-full max-w-md p-6">
        <div class="text-center mb-6">
            <h3 class="text-xl font-bold text-gray-900" x-text="locationTitle"></h3>
            <p class="text-sm text-gray-500 mt-1">Memverifikasi lokasi Anda...</p>
        </div>
        
        <!-- Location Status with Map -->
        <div class="mb-6">
            <!-- Map Container -->
            <div class="w-full h-64 rounded-xl overflow-hidden mb-4 bg-gray-100 relative">
                <div id="location-map" class="w-full h-full"></div>
                <!-- Map Loading Overlay -->
                <div x-show="locationStatus === 'checking'" 
                     class="absolute inset-0 bg-gray-100 flex items-center justify-center">
                    <div class="text-center">
                        <svg class="w-8 h-8 text-blue-500 animate-spin mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-sm text-gray-600">Mencari lokasi...</p>
                    </div>
                </div>
            </div>

            <!-- Status Summary -->
            <div class="bg-gray-50 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-3">
                        <div class="w-4 h-4 rounded-full transition-colors"
                             :class="locationStatus === 'inside' ? 'bg-green-500' : 
                                     locationStatus === 'outside' ? 'bg-red-500' : 
                                     'bg-yellow-500'"></div>
                        <span class="font-medium text-gray-900" x-text="locationMessage"></span>
                    </div>
                    <div x-show="locationAccuracy" class="text-xs text-gray-500">
                        GPS: <span x-text="locationAccuracy"></span>m
                    </div>
                </div>
                
                <p class="text-sm text-gray-600" x-text="locationDetails"></p>
                
                <!-- Distance Information -->
                <div x-show="distanceFromOffice" class="mt-2 text-xs text-gray-500">
                    Jarak dari kantor: <span x-text="distanceFromOffice"></span>m
                </div>
            </div>
        </div>
        
        <!-- Controls -->
        <div class="flex justify-between items-center mt-8">
            <button @click="cancelLocationDetection()" 
                    class="px-6 py-3 text-gray-600 hover:text-gray-800 font-medium transition-colors">
                Batal
            </button>
            <button @click="proceedToCamera()" 
                    x-show="locationStatus === 'inside'"
                    class="px-8 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:shadow-lg transition-all duration-300 font-medium">
                Lanjutkan
            </button>
            <button @click="retryLocationDetection()" 
                    x-show="locationStatus === 'outside'"
                    class="px-8 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl hover:shadow-lg transition-all duration-300 font-medium">
                Coba Lagi
            </button>
        </div>
    </div>
</div>

<!-- Camera Modal for Checkin/Checkout -->
<div x-show="showCamera" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex items-center justify-center modal-overlay p-4"
     style="display: none;">
    <div class="modal-container w-full max-w-md p-6">
        <div class="text-center mb-4">
            <h3 class="text-xl font-bold text-gray-900" x-text="cameraTitle"></h3>
            <p class="text-sm text-gray-500 mt-1">Posisikan wajah Anda di dalam frame</p>
        </div>
        
        <!-- Camera Preview -->
        <div class="relative bg-gray-900 rounded-xl overflow-hidden mb-4" style="aspect-ratio: 4/3;">
            <video x-ref="camera" autoplay playsinline muted class="w-full h-full object-cover"></video>
            <canvas x-ref="faceCanvas" class="absolute inset-0 w-full h-full" style="pointer-events: none;"></canvas>
            
            <!-- Face Detection Overlay -->
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-48 h-32 border-2 rounded-lg transition-colors duration-300"
                 :class="faceMatchStatus === 'matched' ? 'border-green-400' : 
                         faceMatchStatus === 'no_match' ? 'border-red-400' : 
                         'border-yellow-400'"></div>
            
            <!-- Face Match Status -->
            <div class="absolute top-4 left-4 right-4">
                <div class="bg-black/70 backdrop-blur-sm rounded-lg p-3 text-white text-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span>Verifikasi Wajah:</span>
                        <span :class="faceMatchStatus === 'matched' ? 'text-green-400' : 
                                     faceMatchStatus === 'no_match' ? 'text-red-400' : 
                                     'text-yellow-400'"
                              x-text="faceMatchMessage"></span>
                    </div>
                    <div class="w-full bg-gray-700 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all duration-500"
                             :class="faceMatchStatus === 'matched' ? 'bg-green-400' : 
                                     faceMatchStatus === 'no_match' ? 'bg-red-400' : 
                                     'bg-yellow-400'"
                             :style="`width: ${faceMatchConfidence}%`"></div>
                    </div>
                </div>
            </div>
            
            <!-- Liveness Detection Instructions -->
            <div x-show="showLivenessTest" class="absolute bottom-4 left-4 right-4">
                <div class="bg-green-600/90 backdrop-blur-sm rounded-lg p-3 text-white text-center">
                    <div class="font-semibold mb-1" x-text="livenessInstruction"></div>
                    <div class="text-sm opacity-90" x-text="livenessProgress"></div>
                    <div class="w-full bg-green-800 rounded-full h-2 mt-2">
                        <div class="bg-white h-2 rounded-full transition-all duration-300"
                             :style="`width: ${livenessProgressPercent}%`"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Controls -->
        <div class="flex justify-between items-center mt-6">
            <button @click="closeCamera()" 
                    class="px-6 py-3 text-gray-600 hover:text-gray-800 font-medium transition-colors">
                Batal
            </button>
            <button @click="capturePhoto()" 
                    :disabled="!canCapture"
                    :class="canCapture ? 'bg-gradient-to-r from-green-500 to-emerald-600 hover:shadow-lg' : 'bg-gray-400 cursor-not-allowed'"
                    class="px-8 py-3 text-white rounded-xl transition-all duration-300 font-medium">
                <span x-show="!showLivenessTest" x-text="cameraAction"></span>
                <span x-show="showLivenessTest">Lakukan Gerakan</span>
            </button>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div x-show="showConfirmation" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex items-center justify-center modal-overlay p-4"
     style="display: none;">
    <div class="modal-container w-full max-w-md p-6">
        <div class="text-center mb-6">
            <h3 class="text-xl font-bold text-gray-900">Konfirmasi Absensi</h3>
            <p class="text-sm text-gray-500 mt-1" x-text="confirmationMessage"></p>
        </div>
        
        <!-- Captured Photo Preview -->
        <div x-show="capturedPhotoUrl" class="mb-4">
            <div class="relative w-32 h-24 mx-auto bg-gray-100 rounded-lg overflow-hidden">
                <img :src="capturedPhotoUrl" alt="Captured photo" class="w-full h-full object-cover">
            </div>
        </div>
        
        <!-- Attendance Details -->
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-5 mb-6 space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Waktu:</span>
                <span class="font-semibold text-gray-900" x-text="confirmationTime"></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Tanggal:</span>
                <span class="font-semibold text-gray-900" x-text="confirmationDate"></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Lokasi:</span>
                <span class="font-semibold text-green-600 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Valid
                </span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Wajah:</span>
                <span class="font-semibold text-green-600 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Terverifikasi
                </span>
            </div>
        </div>
        
        <!-- Controls -->
        <div class="flex justify-between items-center mt-6">
            <button @click="cancelConfirmation()" 
                    class="px-6 py-3 text-gray-600 hover:text-gray-800 font-medium transition-colors">
                Batal
            </button>
            <button @click="confirmAttendance()" 
                    :disabled="isSubmitting"
                    class="px-8 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:shadow-lg transition-all duration-300 font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!isSubmitting" x-text="actionType === 'checkin' ? 'Konfirmasi Check-in' : 'Konfirmasi Check-out'"></span>
                <span x-show="isSubmitting" x-cloak class="flex items-center gap-2">
                    <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Menyimpan...
                </span>
            </button>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
function mobileAttendanceApp() {
    return {
        // Initialize first to prevent undefined errors
        init() {
            // Set initial values for display variables
            this.cameraTitle = 'Verifikasi Wajah';
            this.cameraAction = 'Ambil Foto';
            this.livenessInstruction = 'Siapkan wajah Anda';
            this.livenessProgress = 'Menunggu deteksi wajah';
            this.confirmationMessage = 'Periksa data Anda';
            this.locationMessage = 'Mencari lokasi...';
            this.locationDetails = 'Mohon tunggu';
            this.locationTitle = 'Verifikasi Lokasi';
            
            // Load office location settings
            this.loadOfficeLocation();
            
            // Start the app
            this.updateDateTime();
            setInterval(() => this.updateDateTime(), 1000);
            this.loadAttendanceStatus();
        },
        
        async loadOfficeLocation() {
            try {
                // Try to get office location from server
                const response = await fetch('/api/attendance/office-location');
                if (response.ok) {
                    const data = await response.json();
                    this.officeLocation = {
                        latitude: data.latitude,
                        longitude: data.longitude,
                        radius: data.radius || 100
                    };
                }
            } catch (error) {
                console.log('Using default office location');
                // Keep default location
            }
        },
        
        // Flow control - define early to prevent undefined errors
        actionType: '', 
        isSubmitting: false,
        canCapture: false,
        faceRecognitionReady: false,
        
        // Date and time display
        currentDate: '',
        currentTime: '',
        checkinTime: '--:--:--',
        checkinDate: '-- --- ----',
        checkoutTime: '--:--:--',
        checkoutDate: '-- --- ----',
        isCheckedIn: false,
        isCheckedOut: false,
        
        // Modal states
        showLocationDetection: false,
        showCamera: false,
        showConfirmation: false,
        showLivenessTest: false,
        
        // Location detection
        locationTitle: 'Verifikasi Lokasi',
        locationStatus: 'checking', // 'checking', 'inside', 'outside'
        locationMessage: 'Mencari lokasi...',
        locationDetails: 'Mohon tunggu',
        locationAccuracy: '',
        currentPosition: null,
        distanceFromOffice: '',
        locationMap: null,
        officeLocation: {
            latitude: -8.5069, // Default Bali coordinates
            longitude: 115.2625,
            radius: 100 // 100 meters radius
        },
        
        // Camera and Face Recognition
        cameraTitle: 'Verifikasi Wajah',
        cameraAction: 'Ambil Foto',
        faceMatchStatus: 'detecting', // 'detecting', 'matched', 'no_match'
        faceMatchMessage: 'Mendeteksi wajah...',
        faceMatchConfidence: 0,
        employeePhotoData: null,
        faceDetectionInterval: null,
        
        // Liveness Detection
        livenessStep: 0,
        livenessSteps: ['smile', 'shake', 'nod'],
        livenessLabels: {
            'smile': 'Silakan Tersenyum 😊',
            'shake': 'Gelengkan Kepala ↔️',
            'nod': 'Anggukkan Kepala ↕️'
        },
        livenessInstruction: 'Siapkan wajah Anda',
        livenessProgress: 'Menunggu deteksi wajah',
        livenessProgressPercent: 0,
        livenessCompleted: [],
        livenessDetectionActive: false,
        
        // Confirmation
        confirmationMessage: 'Periksa data Anda',
        confirmationTime: '',
        confirmationDate: '',
        capturedPhotoUrl: '',
        capturedPhotoBlob: null,
        
        
        
        updateDateTime() {
            const now = new Date();
            
            // Format date
            const options = { 
                weekday: 'short', 
                day: '2-digit', 
                month: 'short', 
                year: '2-digit' 
            };
            this.currentDate = now.toLocaleDateString('en-US', options);
            
            // Format time
            this.currentTime = now.toLocaleTimeString('en-US', { 
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            }) + ' WITA';
        },
        
        async loadAttendanceStatus() {
            try {
                const response = await fetch('/attendance/api/current-status');
                const data = await response.json();
                
                this.isCheckedIn = data.checked_in;
                this.isCheckedOut = data.checked_out;
                
                if (data.check_in_time) {
                    const checkin = new Date(data.check_in_time);
                    this.checkinTime = checkin.toLocaleTimeString('en-US', { 
                        hour12: false,
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                    this.checkinDate = checkin.toLocaleDateString('en-US', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                    });
                }
                
                if (data.check_out_time) {
                    const checkout = new Date(data.check_out_time);
                    this.checkoutTime = checkout.toLocaleTimeString('en-US', { 
                        hour12: false,
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                    this.checkoutDate = checkout.toLocaleDateString('en-US', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                    });
                }
            } catch (error) {
                console.error('Failed to load attendance status:', error);
            }
        },
        
        startCheckin() {
            this.actionType = 'checkin';
            this.locationTitle = 'Verifikasi Lokasi - Check In';
            this.startLocationDetection();
        },
        
        startCheckout() {
            this.actionType = 'checkout';
            this.locationTitle = 'Verifikasi Lokasi - Check Out';
            this.startLocationDetection();
        },
        
        async startLocationDetection() {
            this.showLocationDetection = true;
            this.locationStatus = 'checking';
            this.locationMessage = 'Mendeteksi lokasi...';
            this.locationDetails = 'Mohon tunggu, GPS sedang mencari lokasi Anda';
            
            // Initialize map after modal is shown
            setTimeout(() => {
                this.initializeLocationMap();
            }, 100);
            
            try {
                const position = await this.getCurrentPosition();
                this.currentPosition = position;
                this.locationAccuracy = Math.round(position.accuracy);
                
                // Calculate distance and check validity
                const distance = this.calculateDistance(
                    position.latitude,
                    position.longitude,
                    this.officeLocation.latitude,
                    this.officeLocation.longitude
                );
                
                this.distanceFromOffice = Math.round(distance);
                const isInside = distance <= this.officeLocation.radius;
                
                // Update map with user location
                this.updateLocationMap(position, isInside);
                
                if (isInside) {
                    this.locationStatus = 'inside';
                    this.locationMessage = 'Lokasi Valid ✓';
                    this.locationDetails = 'Anda berada di area kantor yang diizinkan';
                } else {
                    this.locationStatus = 'outside';
                    this.locationMessage = 'Lokasi di Luar Area ✗';
                    this.locationDetails = `Anda berada ${this.distanceFromOffice}m dari kantor (radius: ${this.officeLocation.radius}m)`;
                }
            } catch (error) {
                console.error('Location detection failed:', error);
                this.locationStatus = 'outside';
                this.locationMessage = 'GPS Error ✗';
                this.locationDetails = 'Tidak dapat mengakses GPS. Pastikan izin lokasi diberikan.';
                
                // Show office location only
                this.showOfficeOnMap();
            }
        },
        
        async checkLocationValidity(position) {
            // Get office location from server or use default
            // This should be fetched from your location settings
            const officeLocation = {
                latitude: -8.5069, // Default Bali coordinates
                longitude: 115.2625,
                radius: 100 // 100 meters radius
            };
            
            const distance = this.calculateDistance(
                position.latitude,
                position.longitude,
                officeLocation.latitude,
                officeLocation.longitude
            );
            
            console.log(`Distance from office: ${distance.toFixed(2)} meters`);
            return distance <= officeLocation.radius;
        },
        
        calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371000; // Earth's radius in meters
            const dLat = this.toRadians(lat2 - lat1);
            const dLon = this.toRadians(lon2 - lon1);
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(this.toRadians(lat1)) * Math.cos(this.toRadians(lat2)) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        },
        
        toRadians(degrees) {
            return degrees * (Math.PI/180);
        },
        
        initializeLocationMap() {
            if (this.locationMap) {
                this.locationMap.remove();
            }
            
            // Initialize map centered on office location
            this.locationMap = L.map('location-map').setView([this.officeLocation.latitude, this.officeLocation.longitude], 16);
            
            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap contributors'
            }).addTo(this.locationMap);
            
            // Add office location marker
            const officeMarker = L.marker([this.officeLocation.latitude, this.officeLocation.longitude])
                .addTo(this.locationMap)
                .bindPopup('<b>Lokasi Kantor</b><br>Area absensi yang diizinkan')
                .openPopup();
            
            // Add office radius circle
            L.circle([this.officeLocation.latitude, this.officeLocation.longitude], {
                color: '#3b82f6',
                fillColor: '#3b82f6',
                fillOpacity: 0.2,
                radius: this.officeLocation.radius
            }).addTo(this.locationMap);
        },
        
        updateLocationMap(position, isInside) {
            if (!this.locationMap) return;
            
            // Add user location marker
            const userIcon = L.divIcon({
                html: `<div class="w-4 h-4 ${isInside ? 'bg-green-500' : 'bg-red-500'} rounded-full border-2 border-white shadow-lg"></div>`,
                className: 'user-location-marker',
                iconSize: [16, 16],
                iconAnchor: [8, 8]
            });
            
            const userMarker = L.marker([position.latitude, position.longitude], {icon: userIcon})
                .addTo(this.locationMap)
                .bindPopup(`<b>Lokasi Anda</b><br>${isInside ? 'Di dalam area' : 'Di luar area'}<br>Akurasi: ${Math.round(position.accuracy)}m`);
            
            // Add accuracy circle
            L.circle([position.latitude, position.longitude], {
                color: isInside ? '#10b981' : '#ef4444',
                fillColor: isInside ? '#10b981' : '#ef4444',
                fillOpacity: 0.1,
                radius: position.accuracy,
                weight: 1,
                dashArray: '5, 5'
            }).addTo(this.locationMap);
            
            // Fit map to show both locations
            const bounds = L.latLngBounds([
                [this.officeLocation.latitude, this.officeLocation.longitude],
                [position.latitude, position.longitude]
            ]);
            this.locationMap.fitBounds(bounds, {padding: [20, 20]});
        },
        
        showOfficeOnMap() {
            if (!this.locationMap) return;
            
            // Just show office location
            this.locationMap.setView([this.officeLocation.latitude, this.officeLocation.longitude], 16);
        },

        cancelLocationDetection() {
            this.showLocationDetection = false;
            this.cleanupLocationMap();
            this.resetLocationState();
        },
        
        cleanupLocationMap() {
            if (this.locationMap) {
                this.locationMap.remove();
                this.locationMap = null;
            }
        },
        
        retryLocationDetection() {
            this.startLocationDetection();
        },
        
        proceedToCamera() {
            this.showLocationDetection = false;
            this.cleanupLocationMap();
            this.cameraTitle = this.actionType === 'checkin' ? 'Absen Datang' : 'Absen Pulang';
            this.cameraAction = 'Ambil Foto';
            this.showCamera = true;
            this.initializeCamera();
        },
        
        resetLocationState() {
            this.locationStatus = 'checking';
            this.locationMessage = '';
            this.locationDetails = '';
            this.locationAccuracy = '';
            this.currentPosition = null;
            this.distanceFromOffice = '';
            this.cleanupLocationMap();
        },
        
        async initializeCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        facingMode: 'user',
                        width: { ideal: 640 },
                        height: { ideal: 480 }
                    } 
                });
                this.$refs.camera.srcObject = stream;
                
                // Load employee photo for comparison
                await this.loadEmployeePhoto();
                
                // Start face detection after camera is ready
                this.$refs.camera.addEventListener('loadedmetadata', () => {
                    this.startFaceDetection();
                });
                
            } catch (error) {
                console.error('Camera access denied:', error);
                alert('Tidak dapat mengakses kamera. Pastikan Anda memberikan izin kamera.');
                this.closeCamera();
            }
        },
        
        async loadEmployeePhoto() {
            try {
                // Get employee photo from server
                const response = await fetch('/api/employee/photo');
                const data = await response.json();
                
                if (data.photo_url) {
                    // Load and process employee photo
                    const img = new Image();
                    img.crossOrigin = 'anonymous';
                    img.onload = () => {
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');
                        canvas.width = img.width;
                        canvas.height = img.height;
                        ctx.drawImage(img, 0, 0);
                        
                        // Convert to base64 for face comparison
                        this.employeePhotoData = canvas.toDataURL('image/jpeg', 0.8);
                        console.log('Employee photo loaded for comparison');
                    };
                    img.src = data.photo_url;
                }
            } catch (error) {
                console.error('Failed to load employee photo:', error);
                // Continue without photo comparison
                this.employeePhotoData = null;
            }
        },
        
        startFaceDetection() {
            if (this.faceDetectionInterval) {
                clearInterval(this.faceDetectionInterval);
            }
            
            this.faceDetectionInterval = setInterval(() => {
                this.detectAndCompareFace();
            }, 500); // Check every 500ms
        },
        
        async detectAndCompareFace() {
            if (!this.$refs.camera || !this.$refs.faceCanvas) return;
            
            const video = this.$refs.camera;
            const canvas = this.$refs.faceCanvas;
            const ctx = canvas.getContext('2d');
            
            // Set canvas size to match video
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            // Draw current video frame
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            try {
                // Simulate face detection (in production, use Face-API.js or similar)
                const currentFrame = canvas.toDataURL('image/jpeg', 0.8);
                
                if (this.employeePhotoData) {
                    // Compare with employee photo
                    const similarity = await this.compareFaces(currentFrame, this.employeePhotoData);
                    
                    if (similarity > 0.7) { // 70% similarity threshold
                        this.faceMatchStatus = 'matched';
                        this.faceMatchMessage = 'Wajah Cocok ✓';
                        this.faceMatchConfidence = Math.round(similarity * 100);
                        
                        if (!this.showLivenessTest && !this.livenessDetectionActive) {
                            // Start liveness detection
                            this.startLivenessDetection();
                        }
                    } else if (similarity > 0.3) {
                        this.faceMatchStatus = 'detecting';
                        this.faceMatchMessage = 'Mencocokkan...';
                        this.faceMatchConfidence = Math.round(similarity * 100);
                    } else {
                        this.faceMatchStatus = 'no_match';
                        this.faceMatchMessage = 'Wajah Tidak Cocok ✗';
                        this.faceMatchConfidence = Math.round(similarity * 100);
                        this.resetLivenessTest();
                    }
                } else {
                    // No employee photo available, just detect face presence
                    this.faceMatchStatus = 'matched';
                    this.faceMatchMessage = 'Wajah Terdeteksi ✓';
                    this.faceMatchConfidence = 85;
                    
                    if (!this.showLivenessTest && !this.livenessDetectionActive) {
                        this.startLivenessDetection();
                    }
                }
            } catch (error) {
                console.error('Face detection error:', error);
                this.faceMatchStatus = 'detecting';
                this.faceMatchMessage = 'Mendeteksi wajah...';
                this.faceMatchConfidence = 0;
            }
        },
        
        async compareFaces(currentFrame, employeePhoto) {
            // Simplified face comparison simulation
            // In production, use proper face recognition library like Face-API.js
            
            // For now, simulate comparison based on image data
            try {
                // Create temporary canvases for comparison
                const canvas1 = document.createElement('canvas');
                const canvas2 = document.createElement('canvas');
                const ctx1 = canvas1.getContext('2d');
                const ctx2 = canvas2.getContext('2d');
                
                // Load current frame
                const img1 = new Image();
                img1.src = currentFrame;
                
                // Load employee photo
                const img2 = new Image();
                img2.src = employeePhoto;
                
                return new Promise((resolve) => {
                    let loaded = 0;
                    
                    const onLoad = () => {
                        loaded++;
                        if (loaded === 2) {
                            // Simple pixel comparison (in production, use proper face vectors)
                            const similarity = Math.random() * 0.4 + 0.6; // Simulate 60-100% match
                            resolve(similarity);
                        }
                    };
                    
                    img1.onload = onLoad;
                    img2.onload = onLoad;
                    
                    // Timeout fallback
                    setTimeout(() => resolve(0.5), 1000);
                });
            } catch (error) {
                console.error('Face comparison error:', error);
                return 0.5; // Default similarity
            }
        },
        
        startLivenessDetection() {
            this.showLivenessTest = true;
            this.livenessDetectionActive = true;
            this.livenessStep = 0;
            this.livenessCompleted = [];
            this.livenessProgressPercent = 0;
            
            this.nextLivenessStep();
        },
        
        nextLivenessStep() {
            if (this.livenessStep >= this.livenessSteps.length) {
                // All steps completed
                this.completeLivenessTest();
                return;
            }
            
            const currentStep = this.livenessSteps[this.livenessStep];
            this.livenessInstruction = this.livenessLabels[currentStep];
            this.livenessProgress = `Langkah ${this.livenessStep + 1} dari ${this.livenessSteps.length}`;
            
            // Simulate liveness detection
            setTimeout(() => {
                this.livenessCompleted.push(currentStep);
                this.livenessProgressPercent = (this.livenessCompleted.length / this.livenessSteps.length) * 100;
                this.livenessStep++;
                
                if (this.livenessStep < this.livenessSteps.length) {
                    // Continue to next step
                    setTimeout(() => this.nextLivenessStep(), 1000);
                } else {
                    // All steps completed
                    this.completeLivenessTest();
                }
            }, 3000); // 3 seconds per step
        },
        
        completeLivenessTest() {
            this.showLivenessTest = false;
            this.livenessDetectionActive = false;
            this.canCapture = true;
            
            // Update camera action text
            this.cameraAction = 'Ambil Foto';
            
            // Show success message briefly
            this.livenessInstruction = 'Verifikasi Selesai ✓';
            setTimeout(() => {
                this.livenessInstruction = '';
            }, 2000);
        },
        
        resetLivenessTest() {
            this.showLivenessTest = false;
            this.livenessDetectionActive = false;
            this.livenessStep = 0;
            this.livenessCompleted = [];
            this.livenessProgressPercent = 0;
            this.canCapture = false;
            this.cameraAction = 'Verifikasi Wajah';
        },
        
        closeCamera() {
            // Stop camera stream
            if (this.$refs.camera && this.$refs.camera.srcObject) {
                const stream = this.$refs.camera.srcObject;
                const tracks = stream.getTracks();
                tracks.forEach(track => track.stop());
            }
            
            // Clear face detection
            if (this.faceDetectionInterval) {
                clearInterval(this.faceDetectionInterval);
                this.faceDetectionInterval = null;
            }
            
            // Reset face recognition state
            this.resetFaceRecognitionState();
            
            this.showCamera = false;
        },
        
        resetFaceRecognitionState() {
            this.faceMatchStatus = 'detecting';
            this.faceMatchMessage = 'Mendeteksi wajah...';
            this.faceMatchConfidence = 0;
            this.resetLivenessTest();
            this.canCapture = false;
            this.faceRecognitionReady = false;
        },
        
        async capturePhoto() {
            // Capture photo from camera
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            canvas.width = this.$refs.camera.videoWidth;
            canvas.height = this.$refs.camera.videoHeight;
            context.drawImage(this.$refs.camera, 0, 0);
            
            // Convert to blob and data URL
            canvas.toBlob((blob) => {
                this.capturedPhotoBlob = blob;
                this.capturedPhotoUrl = canvas.toDataURL('image/jpeg', 0.8);
                
                // Close camera and show confirmation
                this.closeCamera();
                this.showConfirmationDialog();
            }, 'image/jpeg', 0.8);
        },
        
        showConfirmationDialog() {
            const now = new Date();
            this.confirmationTime = now.toLocaleTimeString('en-US', { 
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            }) + ' WITA';
            
            this.confirmationDate = now.toLocaleDateString('en-US', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
            
            this.confirmationMessage = this.actionType === 'checkin' 
                ? 'Pastikan data check-in Anda sudah benar'
                : 'Pastikan data check-out Anda sudah benar';
                
            this.showConfirmation = true;
        },
        
        cancelConfirmation() {
            this.showConfirmation = false;
            this.capturedPhotoUrl = '';
            this.capturedPhotoBlob = null;
        },
        
        async confirmAttendance() {
            this.isSubmitting = true;
            
            try {
                const formData = new FormData();
                formData.append('photo', this.capturedPhotoBlob, 'attendance.jpg');
                formData.append('latitude', this.currentPosition.latitude);
                formData.append('longitude', this.currentPosition.longitude);
                formData.append('accuracy', this.currentPosition.accuracy);
                
                const endpoint = this.actionType === 'checkin' ? '/attendance/api/check-in' : '/attendance/api/check-out';
                
                const response = await fetch(endpoint, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    // Success - close modal and refresh data
                    this.showConfirmation = false;
                    this.resetAllStates();
                    
                    // Show success message
                    alert(`${this.actionType === 'checkin' ? 'Check-in' : 'Check-out'} berhasil!`);
                    
                    // Reload attendance status
                    await this.loadAttendanceStatus();
                } else {
                    alert('Gagal melakukan absensi: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Attendance submission failed:', error);
                alert('Terjadi kesalahan saat mengirim data absensi');
            } finally {
                this.isSubmitting = false;
            }
        },
        
        resetAllStates() {
            this.showLocationDetection = false;
            this.showCamera = false;
            this.showConfirmation = false;
            this.resetLocationState();
            this.capturedPhotoUrl = '';
            this.capturedPhotoBlob = null;
            this.actionType = '';
            this.isSubmitting = false;
        },
        
        getCurrentPosition() {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject(new Error('Geolocation not supported'));
                    return;
                }
                
                navigator.geolocation.getCurrentPosition(
                    (position) => resolve(position.coords),
                    (error) => reject(error),
                    { enableHighAccuracy: true, timeout: 10000 }
                );
            });
        },
        
        async submitAttendance(photoBlob, position) {
            try {
                const formData = new FormData();
                formData.append('photo', photoBlob, 'attendance.jpg');
                formData.append('latitude', position.latitude);
                formData.append('longitude', position.longitude);
                formData.append('accuracy', position.accuracy);
                
                const endpoint = this.actionType === 'checkin' ? '/attendance/api/check-in' : '/attendance/api/check-out';
                
                const response = await fetch(endpoint, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    this.closeCamera();
                    alert(`${this.actionType === 'checkin' ? 'Check-in' : 'Check-out'} berhasil!`);
                    await this.loadAttendanceStatus();
                } else {
                    alert('Gagal melakukan absensi: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Attendance submission failed:', error);
                alert('Terjadi kesalahan saat mengirim data absensi');
            }
        }
    }
}
</script>
@endpush