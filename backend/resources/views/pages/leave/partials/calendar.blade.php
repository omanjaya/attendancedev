<div class="backdrop-blur-xl bg-white/25 border border-white/20 rounded-3xl p-6 shadow-2xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Leave Calendar</h3>
            <p class="text-gray-600 text-sm mt-1">View your leave schedule and team availability</p>
        </div>
        
        <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 bg-emerald-500 rounded-full"></div>
                <span class="text-sm text-gray-600">My Leave</span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                <span class="text-sm text-gray-600">Team Leave</span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                <span class="text-sm text-gray-600">Holidays</span>
            </div>
        </div>
    </div>
    
    <!-- Calendar Controls -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4">
            <button id="prevMonth" class="p-2 hover:bg-white/30 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <h4 id="currentMonth" class="text-xl font-semibold text-gray-900"></h4>
            <button id="nextMonth" class="p-2 hover:bg-white/30 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
        
        <div class="flex items-center space-x-2">
            <button id="todayBtn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white/50 border border-gray-300/50 hover:bg-white/70 rounded-lg transition-colors">
                Today
            </button>
            <button onclick="document.querySelector('[x-data]').activeTab = 'request'" 
                    class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors">
                Request Leave
            </button>
        </div>
    </div>
    
    <!-- Calendar Grid -->
    <div class="backdrop-blur-xl bg-white/20 border border-white/15 rounded-2xl p-4 overflow-hidden">
        <div class="grid grid-cols-7 gap-1 mb-4">
            <div class="text-center py-2 text-sm font-medium text-gray-600">Sun</div>
            <div class="text-center py-2 text-sm font-medium text-gray-600">Mon</div>
            <div class="text-center py-2 text-sm font-medium text-gray-600">Tue</div>
            <div class="text-center py-2 text-sm font-medium text-gray-600">Wed</div>
            <div class="text-center py-2 text-sm font-medium text-gray-600">Thu</div>
            <div class="text-center py-2 text-sm font-medium text-gray-600">Fri</div>
            <div class="text-center py-2 text-sm font-medium text-gray-600">Sat</div>
        </div>
        
        <div id="calendar-grid" class="grid grid-cols-7 gap-1">
            <!-- Calendar days will be populated by JavaScript -->
        </div>
    </div>
</div>

<!-- Upcoming Leave Events -->
<div class="backdrop-blur-xl bg-white/25 border border-white/20 rounded-3xl p-6 shadow-2xl mt-6">
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Upcoming Leave Events</h3>
        <p class="text-gray-600 text-sm mt-1">Your scheduled leave and team events</p>
    </div>
    
    <div class="space-y-4">
        @php
            $upcomingLeaves = [
                [
                    'title' => 'Annual Leave',
                    'date' => '2024-03-15',
                    'duration' => '5 days',
                    'status' => 'approved',
                    'type' => 'own'
                ],
                [
                    'title' => 'Team Meeting - John out',
                    'date' => '2024-03-20',
                    'duration' => '1 day',
                    'status' => 'approved',
                    'type' => 'team'
                ],
                [
                    'title' => 'Sick Leave',
                    'date' => '2024-03-25',
                    'duration' => '2 days',
                    'status' => 'pending',
                    'type' => 'own'
                ]
            ];
        @endphp
        
        @foreach($upcomingLeaves as $leave)
            <div class="flex items-center justify-between p-4 bg-white/30 border border-white/20 rounded-xl">
                <div class="flex items-center space-x-4">
                    <div class="w-2 h-2 rounded-full {{ $leave['type'] === 'own' ? 'bg-emerald-500' : 'bg-blue-500' }}"></div>
                    <div>
                        <h4 class="font-medium text-gray-900">{{ $leave['title'] }}</h4>
                        <p class="text-sm text-gray-600">{{ \Carbon\Carbon::parse($leave['date'])->format('M j, Y') }} • {{ $leave['duration'] }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="px-3 py-1 text-xs font-medium rounded-full
                        {{ $leave['status'] === 'approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                        {{ ucfirst($leave['status']) }}
                    </span>
                    @if($leave['type'] === 'own' && $leave['status'] === 'pending')
                        <button class="text-red-600 hover:text-red-800 text-sm">Cancel</button>
                    @endif
                </div>
            </div>
        @endforeach
        
        @if(empty($upcomingLeaves))
            <div class="text-center py-8 text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="font-medium">No upcoming leave events</p>
                <p class="text-sm">Your schedule is clear for the next 30 days</p>
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentDate = new Date();
    let currentMonth = currentDate.getMonth();
    let currentYear = currentDate.getFullYear();
    
    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    
    // Sample leave data
    const leaveData = {
        '2024-03-15': { type: 'own', title: 'Annual Leave', status: 'approved' },
        '2024-03-16': { type: 'own', title: 'Annual Leave', status: 'approved' },
        '2024-03-17': { type: 'own', title: 'Annual Leave', status: 'approved' },
        '2024-03-20': { type: 'team', title: 'John - Sick Leave', status: 'approved' },
        '2024-03-25': { type: 'own', title: 'Personal Leave', status: 'pending' },
        '2024-03-26': { type: 'own', title: 'Personal Leave', status: 'pending' }
    };
    
    function generateCalendar(month, year) {
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const calendar = document.getElementById('calendar-grid');
        
        calendar.innerHTML = '';
        
        // Add empty cells for days before the first day of the month
        for (let i = 0; i < firstDay; i++) {
            calendar.appendChild(createEmptyCell());
        }
        
        // Add cells for each day of the month
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            calendar.appendChild(createDayCell(day, dateStr));
        }
        
        // Update month display
        document.getElementById('currentMonth').textContent = `${monthNames[month]} ${year}`;
    }
    
    function createEmptyCell() {
        const cell = document.createElement('div');
        cell.className = 'h-12';
        return cell;
    }
    
    function createDayCell(day, dateStr) {
        const cell = document.createElement('div');
        cell.className = 'h-12 flex flex-col items-center justify-center relative cursor-pointer hover:bg-white/30 rounded-lg transition-colors';
        
        const dayNumber = document.createElement('span');
        dayNumber.textContent = day;
        dayNumber.className = 'text-sm font-medium text-gray-900';
        
        cell.appendChild(dayNumber);
        
        // Check if there's leave data for this date
        if (leaveData[dateStr]) {
            const leave = leaveData[dateStr];
            const indicator = document.createElement('div');
            indicator.className = `absolute bottom-1 w-2 h-2 rounded-full ${
                leave.type === 'own' ? 'bg-emerald-500' : 'bg-blue-500'
            }`;
            cell.appendChild(indicator);
            
            // Add tooltip
            cell.title = `${leave.title} (${leave.status})`;
        }
        
        // Highlight today
        const today = new Date();
        if (day === today.getDate() && currentMonth === today.getMonth() && currentYear === today.getFullYear()) {
            cell.classList.add('bg-emerald-100', 'border-2', 'border-emerald-500');
        }
        
        return cell;
    }
    
    // Event listeners
    document.getElementById('prevMonth').addEventListener('click', function() {
        currentMonth--;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        generateCalendar(currentMonth, currentYear);
    });
    
    document.getElementById('nextMonth').addEventListener('click', function() {
        currentMonth++;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        generateCalendar(currentMonth, currentYear);
    });
    
    document.getElementById('todayBtn').addEventListener('click', function() {
        const today = new Date();
        currentMonth = today.getMonth();
        currentYear = today.getFullYear();
        generateCalendar(currentMonth, currentYear);
    });
    
    // Initialize calendar
    generateCalendar(currentMonth, currentYear);
});
</script>