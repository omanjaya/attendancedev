{{-- Error Tracking Configuration --}}
<script>
    window.__ERROR_TRACKING_CONFIG__ = {
        enabled: {{ config('error_tracking.enabled', true) ? 'true' : 'false' }},
        environment: '{{ app()->environment() }}',
        @if(config('error_tracking.sentry_dsn'))
        dsn: '{{ config('error_tracking.sentry_dsn') }}',
        @endif
        sampleRate: {{ config('error_tracking.sample_rate', 1.0) }},
        enablePerformanceMonitoring: {{ config('error_tracking.enable_performance_monitoring', true) ? 'true' : 'false' }},
        enableUserTracking: {{ config('error_tracking.enable_user_tracking', true) ? 'true' : 'false' }}
    };

    @auth
    // Set current user context for error tracking
    if (window.errorTracking && window.errorTracking.setUser) {
        window.errorTracking.setUser({
            id: {{ auth()->id() }},
            email: '{{ auth()->user()->email }}',
            name: '{{ auth()->user()->name }}',
            roles: @json(auth()->user()->roles->pluck('name'))
        });
    }
    @endauth
</script>