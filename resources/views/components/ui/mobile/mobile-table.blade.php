@props([
    'headers' => [],
    'rows' => [],
    'stackable' => true,
    'swipeActions' => false,
    'searchable' => false,
    'sortable' => false,
    'loading' => false,
    'emptyMessage' => 'No data available',
    'primaryKey' => 'id',
    'showRowNumbers' => false
])

@php
    $tableId = 'mobile-table-' . Str::random(6);
@endphp

<div class="mobile-table-container" 
     x-data="mobileTable({
        rows: {{ json_encode($rows) }},
        headers: {{ json_encode($headers) }},
        stackable: {{ $stackable ? 'true' : 'false' }},
        swipeActions: {{ $swipeActions ? 'true' : 'false' }},
        searchable: {{ $searchable ? 'true' : 'false' }},
        sortable: {{ $sortable ? 'true' : 'false' }}
     })"
     x-init="init()"
     id="{{ $tableId }}">
     
    @if($searchable)
    <!-- Mobile Search -->
    <div class="mb-4">
        <div class="relative">
            <input type="text"
                   x-model="searchQuery"
                   @input="handleSearch()"
                   placeholder="Search..."
                   class="w-full pl-10 pr-4 py-3 text-base 
                          bg-background border border-input rounded-lg
                          focus:outline-none focus:ring-2 focus:ring-ring 
                          placeholder:text-muted-foreground touch-manipulation">
            
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <svg class="w-5 h-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>
    </div>
    @endif
    
    @if($loading)
    <!-- Loading State -->
    <div class="space-y-4">
        @for($i = 0; $i < 3; $i++)
        <div class="bg-card rounded-lg border p-4 space-y-3">
            <x-ui.skeleton height="h-4" width="w-1/2" />
            <x-ui.skeleton height="h-3" width="w-3/4" />
            <x-ui.skeleton height="h-3" width="w-1/3" />
        </div>
        @endfor
    </div>
    @elseif(empty($rows))
    <!-- Empty State -->
    <div class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-muted-foreground mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="text-muted-foreground">{{ $emptyMessage }}</p>
    </div>
    @else
    <!-- Table Content -->
    <div class="space-y-3">
        <!-- Desktop Table (hidden on mobile) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-border">
                <thead class="bg-muted/50">
                    <tr>
                        @if($showRowNumbers)
                        <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">#</th>
                        @endif
                        @foreach($headers as $header)
                        <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase"
                            @if($sortable) 
                            @click="sortBy('{{ $header['key'] ?? '' }}')"
                            class="cursor-pointer hover:text-foreground"
                            @endif>
                            <div class="flex items-center space-x-1">
                                <span>{{ $header['label'] ?? $header }}</span>
                                @if($sortable && isset($header['key']))
                                <svg x-show="sortColumn === '{{ $header['key'] }}'" 
                                     class="w-3 h-3 transition-transform"
                                     :class="{ 'rotate-180': sortDirection === 'desc' }"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                </svg>
                                @endif
                            </div>
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="bg-background divide-y divide-border">
                    <template x-for="(row, index) in filteredRows" :key="row.{{ $primaryKey }}">
                        <tr class="hover:bg-muted/50">
                            @if($showRowNumbers)
                            <td class="px-4 py-3 text-sm text-muted-foreground" x-text="index + 1"></td>
                            @endif
                            @foreach($headers as $header)
                            <td class="px-4 py-3 text-sm text-foreground">
                                <span x-html="getCellContent(row, '{{ $header['key'] ?? '' }}')"></span>
                            </td>
                            @endforeach
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        <!-- Mobile Cards (visible on mobile) -->
        <div class="md:hidden space-y-3">
            <template x-for="(row, index) in filteredRows" :key="row.{{ $primaryKey }}">
                @if($swipeActions)
                <x-ui.mobile.swipe-actions
                    :leftActions="[
                        ['action' => 'edit', 'label' => 'Edit', 'icon' => 'ti ti-edit', 'variant' => 'primary'],
                    ]"
                    :rightActions="[
                        ['action' => 'delete', 'label' => 'Delete', 'icon' => 'ti ti-trash', 'variant' => 'destructive'],
                    ]"
                    @swipe-action="handleSwipeAction($event.detail)">
                @endif
                
                <div class="bg-card rounded-lg border p-4 space-y-3 touch-manipulation
                           hover:shadow-md transition-shadow duration-200"
                     @click="handleRowClick(row)">
                    
                    @if($showRowNumbers)
                    <div class="text-xs text-muted-foreground font-medium">
                        #<span x-text="index + 1"></span>
                    </div>
                    @endif
                    
                    @foreach($headers as $header)
                    @if($loop->first)
                    <!-- Primary field (larger, more prominent) -->
                    <div class="space-y-1">
                        <div class="text-base font-medium text-foreground" 
                             x-html="getCellContent(row, '{{ $header['key'] ?? '' }}')">
                        </div>
                        @if(isset($header['subtitle_key']))
                        <div class="text-sm text-muted-foreground"
                             x-html="getCellContent(row, '{{ $header['subtitle_key'] }}')">
                        </div>
                        @endif
                    </div>
                    @else
                    <!-- Secondary fields -->
                    <div class="flex items-center justify-between py-1">
                        <span class="text-sm text-muted-foreground">{{ $header['label'] ?? $header }}</span>
                        <span class="text-sm text-foreground font-medium" 
                              x-html="getCellContent(row, '{{ $header['key'] ?? '' }}')">
                        </span>
                    </div>
                    @endif
                    @endforeach
                </div>
                
                @if($swipeActions)
                </x-ui.mobile.swipe-actions>
                @endif
            </template>
        </div>
    @endif
    
    <!-- Load More Button for Pagination -->
    <div x-show="hasMoreRows" class="text-center mt-6">
        <x-ui.mobile.touch-button
            @click="loadMore()"
            variant="outline"
            :loading="loadingMore">
            Load More
        </x-ui.mobile.touch-button>
    </div>
</div>

<script>
function mobileTable(config) {
    return {
        rows: config.rows,
        headers: config.headers,
        filteredRows: [],
        searchQuery: '',
        sortColumn: null,
        sortDirection: 'asc',
        loadingMore: false,
        hasMoreRows: false,
        
        init() {
            this.filteredRows = [...this.rows];
        },
        
        handleSearch() {
            if (!this.searchQuery.trim()) {
                this.filteredRows = [...this.rows];
                return;
            }
            
            const query = this.searchQuery.toLowerCase();
            this.filteredRows = this.rows.filter(row => {
                return Object.values(row).some(value => 
                    String(value).toLowerCase().includes(query)
                );
            });
        },
        
        sortBy(column) {
            if (!column) return;
            
            if (this.sortColumn === column) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortColumn = column;
                this.sortDirection = 'asc';
            }
            
            this.filteredRows.sort((a, b) => {
                let valueA = a[column];
                let valueB = b[column];
                
                // Handle different data types
                if (typeof valueA === 'string') {
                    valueA = valueA.toLowerCase();
                    valueB = valueB.toLowerCase();
                }
                
                if (this.sortDirection === 'asc') {
                    return valueA > valueB ? 1 : -1;
                } else {
                    return valueA < valueB ? 1 : -1;
                }
            });
        },
        
        getCellContent(row, key) {
            if (!key) return '';
            
            let value = row[key];
            
            // Handle HTML content safely
            if (typeof value === 'string' && value.includes('<')) {
                return value; // Assume it's safe HTML
            }
            
            return value || '';
        },
        
        handleRowClick(row) {
            this.$dispatch('mobile-table-row-click', {
                row: row,
                element: this.$el
            });
        },
        
        handleSwipeAction(detail) {
            this.$dispatch('mobile-table-swipe-action', {
                action: detail.action,
                direction: detail.direction,
                element: detail.element
            });
        },
        
        loadMore() {
            this.loadingMore = true;
            
            // Dispatch load more event
            this.$dispatch('mobile-table-load-more', {
                currentCount: this.filteredRows.length,
                element: this.$el
            });
            
            // Simulate loading delay
            setTimeout(() => {
                this.loadingMore = false;
            }, 1000);
        }
    };
}
</script>

<style>
/* Mobile table optimizations */
.mobile-table-container {
    -webkit-overflow-scrolling: touch;
}

/* Touch-friendly row spacing */
.mobile-table-row {
    min-height: 60px;
    padding: 12px 16px;
}

/* Improve touch targets */
.mobile-table-row:active {
    background-color: hsl(var(--muted) / 0.5);
}

/* Smooth animations for mobile */
@media (prefers-reduced-motion: no-preference) {
    .mobile-table-row {
        transition: background-color 0.2s ease-out;
    }
}
</style>