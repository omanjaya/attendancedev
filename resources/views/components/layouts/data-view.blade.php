@props([
    'title' => null,
    'description' => null,
    'searchPlaceholder' => 'Search...',
    'showSearch' => true,
    'showFilters' => false,
    'filters' => [],
    'actions' => null,
    'data' => [],
    'columns' => [],
    'loading' => false,
    'emptyState' => null,
    'pagination' => null,
    'exportable' => false,
])

<div class="space-y-6">
    <!-- Header -->
    @if($title || $description || $actions)
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            @if($title)
                <h2 class="text-lg font-semibold text-foreground">{{ $title }}</h2>
            @endif
            @if($description)
                <p class="text-sm text-muted-foreground mt-1">{{ $description }}</p>
            @endif
        </div>
        
        @if($actions)
            <div class="flex items-center gap-3">
                {{ $actions }}
            </div>
        @endif
    </div>
    @endif
    
    <!-- Filters and Search Bar -->
    @if($showSearch || $showFilters || $exportable)
    <div class="flex flex-col sm:flex-row gap-4 items-center justify-between bg-muted/50 rounded-lg p-4">
        <div class="flex flex-1 gap-4 w-full sm:w-auto">
            <!-- Search -->
            @if($showSearch)
            <div class="relative flex-1 max-w-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" 
                       placeholder="{{ $searchPlaceholder }}" 
                       class="block w-full pl-10 pr-3 py-2 border border-input rounded-md bg-background text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent text-sm"
                       name="search"
                       value="{{ request('search') }}" />
            </div>
            @endif
            
            <!-- Filters -->
            @if($showFilters && count($filters) > 0)
            <div class="flex gap-2">
                @foreach($filters as $filter)
                    <select name="{{ $filter['name'] }}" 
                            class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm">
                        <option value="">{{ $filter['placeholder'] ?? 'All' }}</option>
                        @foreach($filter['options'] as $value => $label)
                            <option value="{{ $value }}" {{ request($filter['name']) == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                @endforeach
            </div>
            @endif
        </div>
        
        <!-- Export and Actions -->
        <div class="flex items-center gap-2">
            @if($exportable)
                <x-ui.button variant="outline" size="sm">
                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export
                </x-ui.button>
            @endif
            
            <x-ui.button variant="outline" size="sm" onclick="window.location.reload()">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
            </x-ui.button>
        </div>
    </div>
    @endif
    
    <!-- Data Table -->
    <x-ui.card variant="simple" class="p-0">
        @if($loading)
            <!-- Loading State -->
            <div class="p-6">
                @for($i = 0; $i < 5; $i++)
                    <div class="flex items-center gap-4 py-4 {{ $i > 0 ? 'border-t border-border' : '' }}">
                        <x-ui.skeleton shape="circle" height="h-8" width="w-8" />
                        <div class="flex-1 space-y-2">
                            <x-ui.skeleton height="h-4" width="w-1/4" />
                            <x-ui.skeleton height="h-3" width="w-1/3" />
                        </div>
                        <x-ui.skeleton height="h-4" width="w-20" />
                        <x-ui.skeleton height="h-8" width="w-16" />
                    </div>
                @endfor
            </div>
        @elseif(count($data) > 0)
            <!-- Data Content -->
            <x-ui.table hover="true" responsive="true">
                @if(count($columns) > 0)
                <x-ui.table.table-header>
                    <x-ui.table.table-row>
                        @foreach($columns as $column)
                            <x-ui.table.table-cell 
                                header="true" 
                                sortable="{{ $column['sortable'] ?? false }}"
                                sorted="{{ $column['sorted'] ?? null }}"
                                width="{{ $column['width'] ?? null }}"
                                align="{{ $column['align'] ?? 'left' }}">
                                {{ $column['label'] }}
                            </x-ui.table.table-cell>
                        @endforeach
                    </x-ui.table.table-row>
                </x-ui.table.table-header>
                @endif
                
                <x-ui.table.table-body>
                    {{ $slot }}
                </x-ui.table.table-body>
            </x-ui.table>
        @else
            <!-- Empty State -->
            @if($emptyState)
                {{ $emptyState }}
            @else
                <x-ui.empty-state 
                    title="No data available"
                    description="There are no records to display at this time."
                    size="default" />
            @endif
        @endif
    </x-ui.card>
    
    <!-- Pagination -->
    @if($pagination)
        <div class="flex justify-center">
            {{ $pagination }}
        </div>
    @endif
</div>