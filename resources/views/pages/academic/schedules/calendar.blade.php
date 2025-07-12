@extends('layouts.app')

@section('title', 'Calendar View - Academic Schedules')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="h3 font-weight-bold text-gray-800">Academic Schedule Calendar</h2>
                    <p class="text-muted mb-0">Manage weekly schedules with drag & drop interface</p>
                </div>
                <div>
                    <a href="{{ route('academic.schedules.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-list me-1"></i> List View
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Vue.js Calendar Component -->
    <div id="schedule-calendar-app">
        <jadwal-patra-calendar-view 
            :academic-classes="{{ json_encode($academicClasses) }}"
            :time-slots="{{ json_encode($timeSlots) }}"
        ></jadwal-patra-calendar-view>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Custom styles for schedule calendar */
.schedule-calendar-container {
    min-height: calc(100vh - 200px);
}

/* Override any Bootstrap conflicting styles */
.schedule-calendar-container * {
    box-sizing: border-box;
}

/* Ensure proper spacing */
.schedule-calendar-container .bg-white {
    background-color: #ffffff !important;
}

.schedule-calendar-container .rounded-xl {
    border-radius: 12px !important;
}

/* Custom scrollbar for better UX */
.schedule-calendar-container ::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.schedule-calendar-container ::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

.schedule-calendar-container ::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 4px;
}

.schedule-calendar-container ::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}
</style>
@endpush

@push('scripts')
<script type="module">
import { createApp } from 'vue'
import JadwalPatraCalendarView from '../../../js/components/JadwalPatraCalendarView.vue'

const app = createApp({
    components: {
        JadwalPatraCalendarView
    }
})

app.mount('#schedule-calendar-app')
</script>
@endpush