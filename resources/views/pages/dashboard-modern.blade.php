@extends('layouts.authenticated-unified')

@section('title', 'Modern Dashboard')

@section('page-content')
<div id="modern-dashboard" class="w-full"></div>
@endsection

@push('styles')
<style>
/* Dashboard specific styles */
.modern-dashboard-container {
    min-height: calc(100vh - 80px);
}

/* Skeleton loading animations */
@keyframes shimmer {
    0% {
        background-position: -468px 0;
    }
    100% {
        background-position: 468px 0;
    }
}

.shimmer {
    animation: shimmer 1.5s ease-in-out infinite;
    background: linear-gradient(
        to right,
        #f6f7f8 0%,
        #edeef1 20%,
        #f6f7f8 40%,
        #f6f7f8 100%
    );
    background-size: 800px 104px;
}

.dark .shimmer {
    background: linear-gradient(
        to right,
        #374151 0%,
        #4b5563 20%,
        #374151 40%,
        #374151 100%
    );
}

/* Chart container styles */
.chart-container {
    position: relative;
    height: 320px;
}

/* Custom scrollbar for activity feed */
.activity-scroll::-webkit-scrollbar {
    width: 4px;
}

.activity-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.activity-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
}

.dark .activity-scroll::-webkit-scrollbar-thumb {
    background: #475569;
}

/* Notification animations */
.notification-enter-active {
    transition: all 0.3s ease-out;
}

.notification-leave-active {
    transition: all 0.3s cubic-bezier(1.0, 0.5, 0.8, 1.0);
}

.notification-enter-from {
    transform: translateX(100%);
    opacity: 0;
}

.notification-leave-to {
    transform: translateX(100%);
    opacity: 0;
}

/* Responsive grid adjustments */
@media (max-width: 640px) {
    .modern-dashboard-container {
        padding: 1rem;
    }
}

/* Focus states for accessibility */
.focus-ring:focus {
    outline: none;
    ring: 2px;
    ring-color: #059669;
    ring-offset: 2px;
}

.dark .focus-ring:focus {
    ring-offset-color: #1f2937;
}
</style>
@endpush

@push('scripts')
<script type="module">
import { createApp } from 'vue'
import ModernDashboard from '@/components/Dashboard/ModernDashboard.vue'

// Mount the Vue app
const app = createApp(ModernDashboard, {
    user: @json([
        'name' => auth()->user()->name,
        'role' => auth()->user()->roles->first()->name ?? 'User',
        'avatar' => null // Add avatar URL if available
    ]),
    initialData: @json($dashboardData ?? [])
})

app.mount('#modern-dashboard')

// Add global error handler
app.config.errorHandler = (err, instance, info) => {
    console.error('Dashboard Vue Error:', err, info)
    
    // Show user-friendly error message
    if (window.showNotification) {
        window.showNotification('error', 'Dashboard Error', 'Something went wrong. Please refresh the page.')
    }
}

// Add performance monitoring
if (window.performance && window.performance.mark) {
    window.performance.mark('dashboard-vue-start')
    
    app.config.globalProperties.$nextTick(() => {
        window.performance.mark('dashboard-vue-end')
        window.performance.measure('dashboard-vue-load', 'dashboard-vue-start', 'dashboard-vue-end')
        
        const measure = window.performance.getEntriesByName('dashboard-vue-load')[0]
        console.log(`Dashboard loaded in ${Math.round(measure.duration)}ms`)
    })
}
</script>
@endpush

@section('meta_description', 'Modern attendance system dashboard with real-time updates and analytics')