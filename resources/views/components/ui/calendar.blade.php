@props([
    'events' => [],
    'currentDate' => null,
    'viewMode' => 'month', // month, week, day
    'showNavigation' => true,
    'showToday' => true,
    'selectable' => false,
    'eventColors' => [
        'default' => 'bg-primary text-primary-foreground',
        'success' => 'bg-success text-success-foreground',
        'warning' => 'bg-warning text-warning-foreground',
        'destructive' => 'bg-destructive text-destructive-foreground',
        'info' => 'bg-info text-info-foreground'
    ]
])

@php
    $currentDate = $currentDate ? Carbon\Carbon::parse($currentDate) : Carbon\Carbon::now();
    $calendarId = 'calendar-' . Str::random(6);
@endphp

<div class="bg-card rounded-lg border shadow-sm" 
     x-data="calendar({
        currentDate: '{{ $currentDate->toDateString() }}',
        viewMode: '{{ $viewMode }}',
        events: {{ json_encode($events) }},
        selectable: {{ $selectable ? 'true' : 'false' }}
     })"
     x-init="init()">
     
    @if($showNavigation)
    <!-- Calendar Header -->
    <div class="flex items-center justify-between p-4 border-b border-border">
        <div class="flex items-center space-x-4">
            <h2 class="text-lg font-semibold text-foreground" x-text="currentMonthYear"></h2>
            @if($showToday)
            <button @click="goToToday()" 
                    class="px-3 py-1 text-sm bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors">
                Today
            </button>
            @endif
        </div>
        
        <div class="flex items-center space-x-2">
            <!-- View Mode Selector -->
            <div class="hidden sm:flex items-center bg-muted rounded-lg p-1">
                <button @click="setViewMode('month')" 
                        :class="viewMode === 'month' ? 'bg-background text-foreground' : 'text-muted-foreground'"
                        class="px-3 py-1 text-xs font-medium rounded-md transition-colors">
                    Month
                </button>
                <button @click="setViewMode('week')" 
                        :class="viewMode === 'week' ? 'bg-background text-foreground' : 'text-muted-foreground'"
                        class="px-3 py-1 text-xs font-medium rounded-md transition-colors">
                    Week
                </button>
            </div>
            
            <!-- Navigation -->
            <div class="flex items-center space-x-1">
                <button @click="previousPeriod()" 
                        class="p-2 hover:bg-muted rounded-md transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <button @click="nextPeriod()" 
                        class="p-2 hover:bg-muted rounded-md transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Calendar Body -->
    <div class="p-4">
        <!-- Month View -->
        <div x-show="viewMode === 'month'">
            <!-- Day Headers -->
            <div class="grid grid-cols-7 gap-1 mb-2">
                <template x-for="day in dayHeaders">
                    <div class="p-2 text-center text-xs font-medium text-muted-foreground" x-text="day"></div>
                </template>
            </div>
            
            <!-- Calendar Grid -->
            <div class="grid grid-cols-7 gap-1">
                <template x-for="(date, index) in calendarDates" :key="index">
                    <div class="min-h-[80px] border border-border rounded-lg p-1 transition-colors"
                         :class="{
                            'bg-muted/30': date.isOtherMonth,
                            'bg-primary/10 border-primary': date.isToday,
                            'hover:bg-muted/50 cursor-pointer': selectable,
                            'bg-accent': date.isSelected
                         }"
                         @click="selectDate(date.date)">
                        
                        <!-- Date Number -->
                        <div class="text-sm font-medium mb-1"
                             :class="{
                                'text-muted-foreground': date.isOtherMonth,
                                'text-primary font-bold': date.isToday,
                                'text-foreground': !date.isOtherMonth && !date.isToday
                             }"
                             x-text="date.day">
                        </div>
                        
                        <!-- Events -->
                        <div class="space-y-1">
                            <template x-for="event in date.events.slice(0, 2)">
                                <div class="text-xs px-1 py-0.5 rounded truncate"
                                     :class="getEventClass(event.type || 'default')"
                                     :title="event.title"
                                     x-text="event.title">
                                </div>
                            </template>
                            <div x-show="date.events.length > 2" 
                                 class="text-xs text-muted-foreground px-1"
                                 x-text="`+${date.events.length - 2} more`">
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
        
        <!-- Week View -->
        <div x-show="viewMode === 'week'">
            <div class="grid grid-cols-8 gap-1">
                <!-- Time Column -->
                <div class="border-r border-border pr-2">
                    <div class="h-12"></div> <!-- Header spacer -->
                    <template x-for="hour in timeSlots">
                        <div class="h-12 flex items-center justify-end text-xs text-muted-foreground pr-2" 
                             x-text="hour"></div>
                    </template>
                </div>
                
                <!-- Day Columns -->
                <template x-for="day in weekDays" :key="day.date">
                    <div class="border-r border-border last:border-r-0">
                        <!-- Day Header -->
                        <div class="h-12 p-2 border-b border-border text-center">
                            <div class="text-xs text-muted-foreground" x-text="day.dayName"></div>
                            <div class="text-sm font-medium"
                                 :class="day.isToday ? 'text-primary font-bold' : 'text-foreground'"
                                 x-text="day.day">
                            </div>
                        </div>
                        
                        <!-- Time Slots -->
                        <template x-for="hour in timeSlots">
                            <div class="h-12 border-b border-border/50 p-1">
                                <!-- Events for this time slot -->
                                <template x-for="event in getEventsForTimeSlot(day.date, hour)">
                                    <div class="text-xs px-1 py-0.5 rounded mb-1"
                                         :class="getEventClass(event.type || 'default')"
                                         :title="event.title"
                                         x-text="event.title">
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function calendar(config) {
    return {
        currentDate: null,
        viewMode: config.viewMode,
        events: config.events,
        selectable: config.selectable,
        selectedDate: null,
        
        dayHeaders: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        timeSlots: ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'],
        
        get currentMonthYear() {
            return this.currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        },
        
        get calendarDates() {
            const dates = [];
            const startOfMonth = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), 1);
            const endOfMonth = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 0);
            
            // Start from the first day of the week containing the first day of the month
            const startDate = new Date(startOfMonth);
            startDate.setDate(startDate.getDate() - startOfMonth.getDay());
            
            // Generate 42 days (6 weeks)
            for (let i = 0; i < 42; i++) {
                const date = new Date(startDate);
                date.setDate(startDate.getDate() + i);
                
                const isCurrentMonth = date.getMonth() === this.currentDate.getMonth();
                const isToday = this.isToday(date);
                const dateEvents = this.getEventsForDate(date);
                
                dates.push({
                    date: date,
                    day: date.getDate(),
                    isCurrentMonth: isCurrentMonth,
                    isOtherMonth: !isCurrentMonth,
                    isToday: isToday,
                    isSelected: this.selectedDate && this.isSameDate(date, this.selectedDate),
                    events: dateEvents
                });
            }
            
            return dates;
        },
        
        get weekDays() {
            const days = [];
            const startOfWeek = new Date(this.currentDate);
            startOfWeek.setDate(this.currentDate.getDate() - this.currentDate.getDay());
            
            for (let i = 0; i < 7; i++) {
                const date = new Date(startOfWeek);
                date.setDate(startOfWeek.getDate() + i);
                
                days.push({
                    date: date,
                    day: date.getDate(),
                    dayName: date.toLocaleDateString('en-US', { weekday: 'short' }),
                    isToday: this.isToday(date)
                });
            }
            
            return days;
        },
        
        init() {
            this.currentDate = new Date(config.currentDate);
        },
        
        setViewMode(mode) {
            this.viewMode = mode;
        },
        
        previousPeriod() {
            if (this.viewMode === 'month') {
                this.currentDate.setMonth(this.currentDate.getMonth() - 1);
            } else if (this.viewMode === 'week') {
                this.currentDate.setDate(this.currentDate.getDate() - 7);
            }
            this.currentDate = new Date(this.currentDate);
        },
        
        nextPeriod() {
            if (this.viewMode === 'month') {
                this.currentDate.setMonth(this.currentDate.getMonth() + 1);
            } else if (this.viewMode === 'week') {
                this.currentDate.setDate(this.currentDate.getDate() + 7);
            }
            this.currentDate = new Date(this.currentDate);
        },
        
        goToToday() {
            this.currentDate = new Date();
        },
        
        selectDate(date) {
            if (this.selectable) {
                this.selectedDate = date;
                this.$dispatch('date-selected', { date: date });
            }
        },
        
        isToday(date) {
            const today = new Date();
            return this.isSameDate(date, today);
        },
        
        isSameDate(date1, date2) {
            return date1.toDateString() === date2.toDateString();
        },
        
        getEventsForDate(date) {
            return this.events.filter(event => {
                const eventDate = new Date(event.date);
                return this.isSameDate(eventDate, date);
            });
        },
        
        getEventsForTimeSlot(date, timeSlot) {
            return this.events.filter(event => {
                const eventDate = new Date(event.date);
                const eventTime = event.time || '00:00';
                return this.isSameDate(eventDate, date) && eventTime.startsWith(timeSlot.substring(0, 2));
            });
        },
        
        getEventClass(type) {
            const classes = {
                'default': 'bg-primary/10 text-primary',
                'success': 'bg-success/10 text-success',
                'warning': 'bg-warning/10 text-warning',
                'destructive': 'bg-destructive/10 text-destructive',
                'info': 'bg-info/10 text-info'
            };
            return classes[type] || classes['default'];
        }
    };
}
</script>