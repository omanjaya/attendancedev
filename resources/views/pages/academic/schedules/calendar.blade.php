@extends('layouts.authenticated-unified')

@section('title', 'Tampilan Kalender - Jadwal Akademik')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Kalender Jadwal Akademik"
            subtitle="Kelola jadwal mingguan dengan antarmuka drag & drop"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Akademik'],
                ['label' => 'Jadwal', 'url' => route('academic.schedules.index')],
                ['label' => 'Kalender']
            ]">
            <x-slot name="actions">
                <x-ui.button variant="secondary" href="{{ route('academic.schedules.index') }}">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    Tampilan Daftar
                </x-ui.button>
            </x-slot>
        </x-layouts.base-page>

        <!-- Vue.js Calendar Component within a Glassmorphism Card -->
        <div class="mt-6 group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
            <div id="schedule-calendar-app">
                <jadwal-patra-calendar-view 
                    :academic-classes="{{ json_encode($academicClasses) }}"
                    :time-slots="{{ json_encode($timeSlots) }}"
                ></jadwal-patra-calendar-view>
            </div>
        </div>
    </div>
</div>
@endsection

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
