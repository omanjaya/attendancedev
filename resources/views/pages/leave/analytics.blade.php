@extends('layouts.authenticated')

@section('title', 'Leave Analytics')

@section('page-content')
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-foreground">Leave Analytics</h1>
                <p class="text-muted-foreground mt-1">Analyze leave patterns and trends</p>
            </div>
            
            <div class="flex items-center space-x-3">
                <x-ui.button variant="outline" href="{{ route('leave.calendar') }}">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Calendar View
                </x-ui.button>
                <x-ui.button variant="outline" href="{{ route('leave.index') }}">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Leave Requests
                </x-ui.button>
            </div>
        </div>
    </div>

    <!-- Analytics Dashboard -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Leave Statistics -->
        <div class="lg:col-span-2">
            <x-ui.card title="Leave Statistics Overview">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Total Requests -->
                    <div class="text-center p-4 rounded-lg bg-muted/50">
                        <div class="text-2xl font-bold text-foreground">156</div>
                        <div class="text-sm text-muted-foreground">Total Requests</div>
                        <div class="text-xs text-success mt-1">+12% vs last month</div>
                    </div>
                    
                    <!-- Approved -->
                    <div class="text-center p-4 rounded-lg bg-success/10">
                        <div class="text-2xl font-bold text-success">142</div>
                        <div class="text-sm text-muted-foreground">Approved</div>
                        <div class="text-xs text-muted-foreground mt-1">91.0% approval rate</div>
                    </div>
                    
                    <!-- Pending -->
                    <div class="text-center p-4 rounded-lg bg-warning/10">
                        <div class="text-2xl font-bold text-warning">8</div>
                        <div class="text-sm text-muted-foreground">Pending</div>
                        <div class="text-xs text-muted-foreground mt-1">Avg 2.5 days</div>
                    </div>
                    
                    <!-- Rejected -->
                    <div class="text-center p-4 rounded-lg bg-destructive/10">
                        <div class="text-2xl font-bold text-destructive">6</div>
                        <div class="text-sm text-muted-foreground">Rejected</div>
                        <div class="text-xs text-muted-foreground mt-1">3.8% rejection rate</div>
                    </div>
                </div>
                
                <!-- Monthly Trend Chart Placeholder -->
                <div class="mt-6 p-6 bg-muted/30 rounded-lg">
                    <h3 class="text-lg font-semibold mb-4">Monthly Leave Trend</h3>
                    <div class="h-64 flex items-center justify-center text-muted-foreground">
                        <div class="text-center">
                            <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <p>Chart visualization will be implemented</p>
                            <p class="text-sm mt-1">Integration with Chart.js or similar library</p>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
        
        <!-- Department Breakdown -->
        <div>
            <x-ui.card title="Department Breakdown">
                <div class="space-y-4">
                    <!-- Mathematics -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 bg-primary rounded-full"></div>
                            <span class="text-sm font-medium">Mathematics</span>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold">24</div>
                            <div class="text-xs text-muted-foreground">15.4%</div>
                        </div>
                    </div>
                    
                    <!-- Science -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 bg-success rounded-full"></div>
                            <span class="text-sm font-medium">Science</span>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold">18</div>
                            <div class="text-xs text-muted-foreground">11.5%</div>
                        </div>
                    </div>
                    
                    <!-- English -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 bg-warning rounded-full"></div>
                            <span class="text-sm font-medium">English</span>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold">15</div>
                            <div class="text-xs text-muted-foreground">9.6%</div>
                        </div>
                    </div>
                    
                    <!-- Administration -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 bg-info rounded-full"></div>
                            <span class="text-sm font-medium">Administration</span>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold">12</div>
                            <div class="text-xs text-muted-foreground">7.7%</div>
                        </div>
                    </div>
                    
                    <!-- Others -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 bg-muted rounded-full"></div>
                            <span class="text-sm font-medium">Others</span>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold">87</div>
                            <div class="text-xs text-muted-foreground">55.8%</div>
                        </div>
                    </div>
                </div>
            </x-ui.card>
            
            <!-- Leave Types -->
            <x-ui.card title="Leave Types" class="mt-6">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm">Annual Leave</span>
                        <div class="flex items-center gap-2">
                            <div class="w-16 bg-muted rounded-full h-2">
                                <div class="w-12 bg-primary rounded-full h-2"></div>
                            </div>
                            <span class="text-sm font-medium">45%</span>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-sm">Sick Leave</span>
                        <div class="flex items-center gap-2">
                            <div class="w-16 bg-muted rounded-full h-2">
                                <div class="w-8 bg-warning rounded-full h-2"></div>
                            </div>
                            <span class="text-sm font-medium">32%</span>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-sm">Emergency</span>
                        <div class="flex items-center gap-2">
                            <div class="w-16 bg-muted rounded-full h-2">
                                <div class="w-4 bg-destructive rounded-full h-2"></div>
                            </div>
                            <span class="text-sm font-medium">15%</span>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-sm">Personal</span>
                        <div class="flex items-center gap-2">
                            <div class="w-16 bg-muted rounded-full h-2">
                                <div class="w-2 bg-info rounded-full h-2"></div>
                            </div>
                            <span class="text-sm font-medium">8%</span>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>

    <!-- Recent Activity -->
    <x-ui.card title="Recent Leave Activity">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Leave Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Submitted</th>
                    </tr>
                </thead>
                <tbody class="bg-card divide-y divide-border">
                    <!-- Sample Data -->
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <x-ui.avatar name="John Doe" size="sm" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-foreground">John Doe</div>
                                    <div class="text-sm text-muted-foreground">Mathematics</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">Annual Leave</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">3 days</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-ui.badge variant="success">Approved</x-ui.badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">2 days ago</td>
                    </tr>
                    
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <x-ui.avatar name="Jane Smith" size="sm" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-foreground">Jane Smith</div>
                                    <div class="text-sm text-muted-foreground">Science</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">Sick Leave</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">1 day</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-ui.badge variant="warning">Pending</x-ui.badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">1 day ago</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endsection