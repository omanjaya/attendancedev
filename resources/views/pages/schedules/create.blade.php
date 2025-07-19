@extends('layouts.authenticated-unified')

@section('title', 'Create New Schedule')

@section('page-content')
<x-layouts.page-base 
    title="Buat Jadwal Baru"
    subtitle="Atur jadwal pelajaran dan penugasan guru"
    :show-background="true"
    :show-welcome="false">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Buat Jadwal Baru</h1>
            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Atur jadwal pelajaran dan penugasan guru</p>
        </div>
        
        <button onclick="location.href='{{ route('schedules.index') }}'" 
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200 shadow-sm">
            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Jadwal
        </button>
    </div>

    <form action="{{ route('schedules.store') }}" method="POST" class="space-y-6">
        @csrf
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Basic Information -->
            <x-layouts.simple-card>
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Informasi Dasar</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Detail jadwal dan waktu pembelajaran</p>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            Nama Jadwal
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" 
                               placeholder="Contoh: Matematika - Kelas 10A" required
                               class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:text-gray-100 transition-all duration-200" />
                        @error('name')
                            <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            Mata Pelajaran
                        </label>
                        <input type="text" name="subject" id="subject" value="{{ old('subject') }}" 
                               placeholder="Contoh: Matematika, Bahasa Indonesia, IPA" required
                               class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:text-gray-100 transition-all duration-200" />
                        @error('subject')
                            <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="class_grade" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Kelas/Tingkat
                        </label>
                        <input type="text" name="class_grade" id="class_grade" value="{{ old('class_grade') }}" 
                               placeholder="Contoh: Kelas 10A, Kelas 2B" required
                               class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:text-gray-100 transition-all duration-200" />
                        @error('class_grade')
                            <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="room" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            Ruangan/Lokasi
                        </label>
                        <input type="text" name="room" id="room" value="{{ old('room') }}" 
                               placeholder="Contoh: Ruang 201, Lab IPA, Aula Utama"
                               class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:text-gray-100 transition-all duration-200" />
                        @error('room')
                            <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </x-layouts.simple-card>

            <!-- Time & Assignment -->
            <x-layouts.simple-card>
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Waktu & Penugasan</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Waktu jadwal dan penugasan guru</p>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label for="day_of_week" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Hari
                        </label>
                        <select name="day_of_week" id="day_of_week" required
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:text-gray-100 transition-all duration-200">
                            <option value="">Pilih Hari</option>
                            <option value="1" {{ old('day_of_week') == '1' ? 'selected' : '' }}>Senin</option>
                            <option value="2" {{ old('day_of_week') == '2' ? 'selected' : '' }}>Selasa</option>
                            <option value="3" {{ old('day_of_week') == '3' ? 'selected' : '' }}>Rabu</option>
                            <option value="4" {{ old('day_of_week') == '4' ? 'selected' : '' }}>Kamis</option>
                            <option value="5" {{ old('day_of_week') == '5' ? 'selected' : '' }}>Jumat</option>
                            <option value="6" {{ old('day_of_week') == '6' ? 'selected' : '' }}>Sabtu</option>
                            <option value="0" {{ old('day_of_week') == '0' ? 'selected' : '' }}>Minggu</option>
                        </select>
                        @error('day_of_week')
                            <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="start_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Waktu Mulai
                            </label>
                            <input type="time" name="start_time" id="start_time" value="{{ old('start_time') }}" required
                                   class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:text-gray-100 transition-all duration-200" />
                            @error('start_time')
                                <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="end_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Waktu Selesai
                            </label>
                            <input type="time" name="end_time" id="end_time" value="{{ old('end_time') }}" required
                                   class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:text-gray-100 transition-all duration-200" />
                            @error('end_time')
                                <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="period_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                            </svg>
                            Periode Ke-
                        </label>
                        <select name="period_number" id="period_number"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:text-gray-100 transition-all duration-200">
                            <option value="">Pilih Periode</option>
                            @for($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}" {{ old('period_number') == $i ? 'selected' : '' }}>
                                    Periode {{ $i }}
                                </option>
                            @endfor
                        </select>
                        @error('period_number')
                            <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="teacher_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Guru yang Ditugaskan
                        </label>
                        <select name="teacher_id" id="teacher_id"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:text-gray-100 transition-all duration-200">
                            <option value="">Pilih Guru</option>
                            @foreach($teachers ?? [] as $teacher)
                                <option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                    {{ $teacher->full_name }} ({{ $teacher->employee_id }})
                                </option>
                            @endforeach
                        </select>
                        @error('teacher_id')
                            <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kosongkan jika guru belum ditugaskan</p>
                    </div>
                </div>
            </x-layouts.simple-card>
        </div>

        <!-- Schedule Settings -->
        <x-layouts.simple-card>
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Pengaturan Jadwal</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Konfigurasi tambahan dan catatan</p>
            </div>
            
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-ui.label for="semester" value="Semester/Term" />
                        <x-ui.input type="text" name="semester" id="semester" value="{{ old('semester') }}" 
                                   placeholder="e.g., Fall 2024, Term 1" />
                        @error('semester')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="academic_year" value="Academic Year" />
                        <x-ui.input type="text" name="academic_year" id="academic_year" value="{{ old('academic_year', date('Y') . '/' . (date('Y') + 1)) }}" 
                                   placeholder="e.g., 2024/2025" />
                        @error('academic_year')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-ui.label for="start_date" value="Schedule Start Date" />
                        <x-ui.input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}" />
                        @error('start_date')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="end_date" value="Schedule End Date" />
                        <x-ui.input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}" />
                        @error('end_date')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div>
                    <x-ui.label for="description" value="Description/Notes" />
                    <textarea name="description" id="description" rows="3" 
                              class="mt-1 block w-full border-input rounded-md shadow-sm focus:border-primary focus:ring-primary" 
                              placeholder="Additional notes about this schedule...">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                    @enderror
                </div>

                <div class="flex items-center space-x-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} 
                               class="rounded border-input text-primary focus:border-primary focus:ring-primary" />
                        <span class="ml-2 text-sm text-foreground">Active Schedule</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="attendance_required" value="1" {{ old('attendance_required', true) ? 'checked' : '' }} 
                               class="rounded border-input text-primary focus:border-primary focus:ring-primary" />
                        <span class="ml-2 text-sm text-foreground">Attendance Required</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="is_recurring" value="1" {{ old('is_recurring', true) ? 'checked' : '' }} 
                               class="rounded border-input text-primary focus:border-primary focus:ring-primary" />
                        <span class="ml-2 text-sm text-foreground">Recurring Weekly</span>
                    </label>
                </div>
            </div>
        </x-layouts.simple-card>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-4 pt-6">
            <button type="button" onclick="history.back()" 
                    class="px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-xl transition-all duration-200">
                Batal
            </button>
            <button type="submit" 
                    class="px-6 py-3 text-sm font-medium text-white bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 rounded-xl transition-all duration-200 shadow-lg transform hover:scale-105">
                <svg class="h-4 w-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Buat Jadwal
            </button>
        </div>
    </form>

</x-layouts.page-base>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    // Validate end time is after start time
    function validateTimes() {
        if (startTimeInput.value && endTimeInput.value) {
            if (endTimeInput.value <= startTimeInput.value) {
                alert('End time must be after start time');
                endTimeInput.value = '';
            }
        }
    }

    // Validate end date is after start date
    function validateDates() {
        if (startDateInput.value && endDateInput.value) {
            if (endDateInput.value < startDateInput.value) {
                alert('End date must be after start date');
                endDateInput.value = '';
            }
        }
    }

    // Event listeners
    startTimeInput.addEventListener('change', validateTimes);
    endTimeInput.addEventListener('change', validateTimes);
    startDateInput.addEventListener('change', validateDates);
    endDateInput.addEventListener('change', validateDates);

    // Auto-suggest schedule name based on subject and class
    const subjectInput = document.getElementById('subject');
    const classInput = document.getElementById('class_grade');
    const nameInput = document.getElementById('name');

    function updateScheduleName() {
        if (subjectInput.value && classInput.value && !nameInput.value) {
            nameInput.value = `${subjectInput.value} - ${classInput.value}`;
        }
    }

    subjectInput.addEventListener('blur', updateScheduleName);
    classInput.addEventListener('blur', updateScheduleName);

    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    startDateInput.min = today;
    endDateInput.min = today;

    // Update end date minimum when start date changes
    startDateInput.addEventListener('change', function() {
        endDateInput.min = this.value;
    });
});
</script>
@endpush