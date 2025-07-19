<div class="backdrop-blur-xl bg-white/25 border border-white/20 rounded-3xl p-6 shadow-2xl">
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Leave Balance Overview</h3>
        <p class="text-gray-600 text-sm mt-1">Your current leave entitlements and usage</p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @if(isset($leaveBalances) && $leaveBalances->count() > 0)
            @foreach($leaveBalances as $balance)
            <div class="backdrop-blur-xl bg-white/30 border border-white/20 rounded-2xl p-6 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h4 class="font-semibold text-gray-900">{{ $balance->leaveType->name }}</h4>
                        <p class="text-sm text-gray-600">{{ $balance->leaveType->description ?? 'Standard leave type' }}</p>
                    </div>
                    <div class="text-right">
                        <span class="text-2xl font-bold text-emerald-600">{{ $balance->remaining_days }}</span>
                        <p class="text-xs text-gray-500">days left</p>
                    </div>
                </div>
                
                <div class="mb-4">
                    @php
                        $percentage = $balance->allocated_days > 0 ? ($balance->remaining_days / $balance->allocated_days) * 100 : 0;
                        $colorClass = $percentage > 50 ? 'bg-green-500' : ($percentage > 20 ? 'bg-yellow-500' : 'bg-red-500');
                    @endphp
                    <div class="w-full bg-gray-200/50 rounded-full h-2">
                        <div class="{{ $colorClass }} h-2 rounded-full transition-all" style="width: {{ $percentage }}%"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                        <span>{{ $balance->used_days }} used</span>
                        <span>{{ $balance->allocated_days }} allocated</span>
                    </div>
                </div>
                
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Carried Forward:</span>
                        <span class="font-medium text-gray-900">{{ $balance->carried_forward ?? 0 }} days</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Used This Year:</span>
                        <span class="font-medium text-gray-900">{{ $balance->used_days }} days</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Expires:</span>
                        <span class="font-medium text-gray-900">{{ $balance->expires_at ? $balance->expires_at->format('M Y') : 'No expiry' }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        @else
            <div class="col-span-full text-center py-12">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Leave Balances Found</h3>
                <p class="text-gray-600 mb-4">Your leave entitlements haven't been set up yet.</p>
                <button class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
                    Contact HR
                </button>
            </div>
        @endif
    </div>
</div>

<!-- Leave History Summary -->
<div class="backdrop-blur-xl bg-white/25 border border-white/20 rounded-3xl p-6 shadow-2xl mt-6">
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Leave Usage This Year</h3>
        <p class="text-gray-600 text-sm mt-1">Monthly breakdown of your leave usage</p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Monthly Usage Chart -->
        <div class="backdrop-blur-xl bg-white/20 border border-white/15 rounded-2xl p-4">
            <h4 class="font-medium text-gray-900 mb-4">Monthly Usage</h4>
            <div class="space-y-3">
                @for($month = 1; $month <= 12; $month++)
                    @php
                        $monthName = date('M', mktime(0, 0, 0, $month, 1));
                        $usage = rand(0, 3); // Simulate data
                        $maxUsage = 5;
                        $percentage = $maxUsage > 0 ? ($usage / $maxUsage) * 100 : 0;
                    @endphp
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 w-8">{{ $monthName }}</span>
                        <div class="flex-1 mx-3">
                            <div class="w-full bg-gray-200/50 rounded-full h-2">
                                <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                        <span class="text-sm font-medium text-gray-900 w-8 text-right">{{ $usage }}</span>
                    </div>
                @endfor
            </div>
        </div>
        
        <!-- Leave Types Usage -->
        <div class="backdrop-blur-xl bg-white/20 border border-white/15 rounded-2xl p-4">
            <h4 class="font-medium text-gray-900 mb-4">Usage by Type</h4>
            <div class="space-y-3">
                @php
                    $leaveTypes = [
                        ['name' => 'Annual Leave', 'used' => 8, 'total' => 20, 'color' => 'bg-blue-500'],
                        ['name' => 'Sick Leave', 'used' => 3, 'total' => 10, 'color' => 'bg-red-500'],
                        ['name' => 'Personal Leave', 'used' => 2, 'total' => 5, 'color' => 'bg-purple-500'],
                        ['name' => 'Maternity Leave', 'used' => 0, 'total' => 90, 'color' => 'bg-pink-500'],
                    ];
                @endphp
                
                @foreach($leaveTypes as $type)
                    @php
                        $percentage = $type['total'] > 0 ? ($type['used'] / $type['total']) * 100 : 0;
                    @endphp
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 {{ $type['color'] }} rounded-full mr-3"></div>
                            <span class="text-sm text-gray-600">{{ $type['name'] }}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-20 bg-gray-200/50 rounded-full h-2">
                                <div class="{{ $type['color'] }} h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900">{{ $type['used'] }}/{{ $type['total'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="backdrop-blur-xl bg-white/25 border border-white/20 rounded-3xl p-6 shadow-2xl mt-6">
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
        <p class="text-gray-600 text-sm mt-1">Manage your leave entitlements</p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <button onclick="document.querySelector('[x-data]').activeTab = 'request'" 
                class="p-4 bg-emerald-50 hover:bg-emerald-100 rounded-xl transition-colors text-center">
            <svg class="w-8 h-8 mx-auto mb-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            <h4 class="font-medium text-emerald-900">Request Leave</h4>
            <p class="text-sm text-emerald-700">Submit a new leave request</p>
        </button>
        
        <button onclick="document.querySelector('[x-data]').activeTab = 'history'" 
                class="p-4 bg-blue-50 hover:bg-blue-100 rounded-xl transition-colors text-center">
            <svg class="w-8 h-8 mx-auto mb-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            <h4 class="font-medium text-blue-900">View History</h4>
            <p class="text-sm text-blue-700">Check your leave applications</p>
        </button>
        
        <button onclick="document.querySelector('[x-data]').activeTab = 'calendar'" 
                class="p-4 bg-purple-50 hover:bg-purple-100 rounded-xl transition-colors text-center">
            <svg class="w-8 h-8 mx-auto mb-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <h4 class="font-medium text-purple-900">Calendar View</h4>
            <p class="text-sm text-purple-700">See leave schedule</p>
        </button>
    </div>
</div>