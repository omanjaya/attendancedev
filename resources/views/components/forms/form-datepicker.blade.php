@props([
    'label' => null,
    'name' => '',
    'value' => '',
    'placeholder' => 'Select date...',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'error' => null,
    'help' => null,
    'format' => 'Y-m-d',
    'displayFormat' => 'M j, Y',
    'minDate' => null,
    'maxDate' => null,
    'disabledDates' => [],
    'enableTime' => false,
    'timeFormat' => 'H:i',
    'size' => 'default',
])

@php
    $datepickerId = $name . '_' . Str::random(6);
    $hasError = $error || $errors->has($name);
    $errorMessage = $error ?: $errors->first($name);
    
    // Size classes
    $sizeClasses = [
        'sm' => 'h-8 px-3 text-sm',
        'default' => 'h-10 px-3 text-sm',
        'lg' => 'h-12 px-4 text-base'
    ];
    
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['default'];
    
    // Input classes
    $inputClasses = 'flex w-full rounded-md border bg-background text-foreground ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 pr-10';
    
    if ($hasError) {
        $inputClasses .= ' border-destructive focus-visible:ring-destructive';
    } else {
        $inputClasses .= ' border-input';
    }
    
    $inputClasses .= ' ' . $sizeClass;
    
    // Format the current value for display
    $displayValue = '';
    if ($value) {
        try {
            $date = \Carbon\Carbon::parse(old($name, $value));
            $displayValue = $date->format($displayFormat);
        } catch (Exception $e) {
            $displayValue = old($name, $value);
        }
    } else {
        $displayValue = old($name, $value);
    }
@endphp

<div class="space-y-2">
    <!-- Label -->
    @if($label)
    <label for="{{ $datepickerId }}" class="block text-sm font-medium text-foreground">
        {{ $label }}
        @if($required)
            <span class="text-destructive ml-1">*</span>
        @endif
    </label>
    @endif
    
    <!-- Date Input Container -->
    <div class="relative" 
         x-data="datePicker({
            value: '{{ old($name, $value) }}',
            format: '{{ $format }}',
            displayFormat: '{{ $displayFormat }}',
            minDate: {{ $minDate ? "'$minDate'" : 'null' }},
            maxDate: {{ $maxDate ? "'$maxDate'" : 'null' }},
            disabledDates: {{ json_encode($disabledDates) }},
            enableTime: {{ $enableTime ? 'true' : 'false' }},
            timeFormat: '{{ $timeFormat }}'
         })">
        
        <!-- Display Input -->
        <input
            type="text"
            id="{{ $datepickerId }}"
            x-model="displayValue"
            @click="togglePicker()"
            @keydown.escape="closePicker()"
            placeholder="{{ $placeholder }}"
            @if($readonly || true) readonly @endif
            @if($disabled) disabled @endif
            class="{{ $inputClasses }} cursor-pointer"
        />
        
        <!-- Hidden Input for Form Submission -->
        <input type="hidden" name="{{ $name }}" x-model="hiddenValue" />
        
        <!-- Calendar Icon -->
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
            <svg class="h-4 w-4 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        </div>
        
        <!-- Date Picker Dropdown -->
        <div x-show="isOpen" 
             @click.away="closePicker()"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="absolute z-50 mt-1 bg-background border border-input rounded-lg shadow-lg p-4 w-80">
            
            <!-- Month/Year Navigation -->
            <div class="flex items-center justify-between mb-4">
                <button type="button" @click="previousMonth()" class="p-1 hover:bg-muted rounded">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                
                <div class="flex items-center space-x-2">
                    <select x-model="currentMonth" @change="updateCalendar()" class="text-sm border border-input rounded px-2 py-1">
                        <template x-for="(month, index) in months" :key="index">
                            <option :value="index" x-text="month"></option>
                        </template>
                    </select>
                    
                    <select x-model="currentYear" @change="updateCalendar()" class="text-sm border border-input rounded px-2 py-1">
                        <template x-for="year in years" :key="year">
                            <option :value="year" x-text="year"></option>
                        </template>
                    </select>
                </div>
                
                <button type="button" @click="nextMonth()" class="p-1 hover:bg-muted rounded">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
            
            <!-- Calendar Grid -->
            <div class="grid grid-cols-7 gap-1 mb-4">
                <!-- Day Headers -->
                <template x-for="day in dayHeaders">
                    <div class="text-center text-xs font-medium text-muted-foreground p-2" x-text="day"></div>
                </template>
                
                <!-- Calendar Days -->
                <template x-for="day in calendarDays" :key="day.date">
                    <button type="button"
                            @click="selectDate(day.date)"
                            :disabled="day.disabled"
                            class="p-2 text-sm rounded hover:bg-muted transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="{
                                'bg-primary text-primary-foreground': day.isSelected,
                                'text-muted-foreground': day.isOtherMonth,
                                'font-bold border border-primary': day.isToday && !day.isSelected
                            }"
                            x-text="day.day">
                    </button>
                </template>
            </div>
            
            <!-- Time Picker (if enabled) -->
            <div x-show="enableTime" class="border-t border-border pt-4">
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium">Time:</label>
                    <input type="time" 
                           x-model="selectedTime" 
                           @change="updateDateTime()"
                           class="text-sm border border-input rounded px-2 py-1" />
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-4 border-t border-border">
                <button type="button" @click="clearDate()" class="text-sm text-muted-foreground hover:text-foreground">
                    Clear
                </button>
                
                <div class="space-x-2">
                    <button type="button" @click="selectToday()" class="text-sm px-3 py-1 bg-muted hover:bg-muted/80 rounded">
                        Today
                    </button>
                    <button type="button" @click="closePicker()" class="text-sm px-3 py-1 bg-primary text-primary-foreground hover:bg-primary/90 rounded">
                        Done
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Error Message -->
    @if($hasError)
    <div class="flex items-center gap-2 text-sm text-destructive">
        <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ $errorMessage }}</span>
    </div>
    @endif
    
    <!-- Help Text -->
    @if($help && !$hasError)
    <p class="text-sm text-muted-foreground">{{ $help }}</p>
    @endif
</div>

<script>
function datePicker(config) {
    return {
        isOpen: false,
        selectedDate: config.value ? new Date(config.value) : null,
        currentMonth: new Date().getMonth(),
        currentYear: new Date().getFullYear(),
        selectedTime: config.enableTime ? '09:00' : '',
        
        months: [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ],
        
        dayHeaders: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
        
        get years() {
            const current = new Date().getFullYear();
            const years = [];
            for (let i = current - 50; i <= current + 10; i++) {
                years.push(i);
            }
            return years;
        },
        
        get displayValue() {
            if (!this.selectedDate) return '';
            return this.formatDate(this.selectedDate, config.displayFormat);
        },
        
        get hiddenValue() {
            if (!this.selectedDate) return '';
            let formatted = this.formatDate(this.selectedDate, config.format);
            if (config.enableTime && this.selectedTime) {
                const [hours, minutes] = this.selectedTime.split(':');
                const date = new Date(this.selectedDate);
                date.setHours(parseInt(hours), parseInt(minutes));
                formatted = this.formatDate(date, config.format + (config.format.includes('H') ? '' : ' H:i'));
            }
            return formatted;
        },
        
        get calendarDays() {
            const firstDay = new Date(this.currentYear, this.currentMonth, 1);
            const lastDay = new Date(this.currentYear, this.currentMonth + 1, 0);
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay());
            
            const days = [];
            const today = new Date();
            
            for (let i = 0; i < 42; i++) {
                const date = new Date(startDate);
                date.setDate(startDate.getDate() + i);
                
                const isOtherMonth = date.getMonth() !== this.currentMonth;
                const isToday = date.toDateString() === today.toDateString();
                const isSelected = this.selectedDate && date.toDateString() === this.selectedDate.toDateString();
                const isDisabled = this.isDateDisabled(date);
                
                days.push({
                    date: new Date(date),
                    day: date.getDate(),
                    isOtherMonth,
                    isToday,
                    isSelected,
                    disabled: isDisabled
                });
            }
            
            return days;
        },
        
        init() {
            if (config.value) {
                this.selectedDate = new Date(config.value);
                this.currentMonth = this.selectedDate.getMonth();
                this.currentYear = this.selectedDate.getFullYear();
            }
        },
        
        togglePicker() {
            this.isOpen = !this.isOpen;
        },
        
        closePicker() {
            this.isOpen = false;
        },
        
        selectDate(date) {
            if (this.isDateDisabled(date)) return;
            
            this.selectedDate = new Date(date);
            if (!config.enableTime) {
                this.closePicker();
            }
        },
        
        selectToday() {
            this.selectedDate = new Date();
            this.currentMonth = this.selectedDate.getMonth();
            this.currentYear = this.selectedDate.getFullYear();
            if (!config.enableTime) {
                this.closePicker();
            }
        },
        
        clearDate() {
            this.selectedDate = null;
            this.closePicker();
        },
        
        previousMonth() {
            if (this.currentMonth === 0) {
                this.currentMonth = 11;
                this.currentYear--;
            } else {
                this.currentMonth--;
            }
        },
        
        nextMonth() {
            if (this.currentMonth === 11) {
                this.currentMonth = 0;
                this.currentYear++;
            } else {
                this.currentMonth++;
            }
        },
        
        updateCalendar() {
            // Calendar will automatically update due to reactive properties
        },
        
        updateDateTime() {
            // Update the hidden value when time changes
            this.$nextTick(() => {
                this.hiddenValue; // Trigger reactivity
            });
        },
        
        isDateDisabled(date) {
            const dateStr = this.formatDate(date, 'Y-m-d');
            
            if (config.minDate && dateStr < config.minDate) return true;
            if (config.maxDate && dateStr > config.maxDate) return true;
            if (config.disabledDates.includes(dateStr)) return true;
            
            return false;
        },
        
        formatDate(date, format) {
            const year = date.getFullYear();
            const month = date.getMonth();
            const day = date.getDate();
            const hours = date.getHours();
            const minutes = date.getMinutes();
            
            const replacements = {
                'Y': year,
                'y': year.toString().slice(-2),
                'm': (month + 1).toString().padStart(2, '0'),
                'n': month + 1,
                'M': this.months[month].slice(0, 3),
                'F': this.months[month],
                'd': day.toString().padStart(2, '0'),
                'j': day,
                'H': hours.toString().padStart(2, '0'),
                'i': minutes.toString().padStart(2, '0')
            };
            
            return format.replace(/[YymnMFdjHi]/g, match => replacements[match] || match);
        }
    };
}
</script>