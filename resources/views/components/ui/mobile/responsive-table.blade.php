@props([
    'headers' => [],
    'data' => [],
    'actions' => null,
    'searchable' => false,
    'sortable' => false,
    'pagination' => null,
    'emptyMessage' => 'No data available'
])

<div class="w-full overflow-hidden bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
    <!-- Search Bar (Mobile & Desktop) -->
    @if($searchable)
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" 
                       placeholder="Search..." 
                       class="block w-full pl-10 pr-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm"
                       x-data="{ search: '' }"
                       x-model="search"
                       @input="filterTable($event.target.value)">
            </div>
        </div>
    @endif

    <!-- Desktop Table View -->
    <div class="hidden md:block overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    @foreach($headers as $header)
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            @if($sortable && isset($header['sortable']) && $header['sortable'])
                                <button class="flex items-center space-x-1 hover:text-gray-700 dark:hover:text-gray-100">
                                    <span>{{ $header['label'] ?? $header }}</span>
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                                    </svg>
                                </button>
                            @else
                                {{ $header['label'] ?? $header }}
                            @endif
                        </th>
                    @endforeach
                    @if($actions)
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    @endif
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($data as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        @foreach($headers as $key => $header)
                            @php
                                $field = $header['field'] ?? $key;
                                $value = is_array($row) ? ($row[$field] ?? '') : ($row->{$field} ?? '');
                            @endphp
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                @if(isset($header['component']))
                                    @include($header['component'], ['value' => $value, 'row' => $row])
                                @else
                                    {{ $value }}
                                @endif
                            </td>
                        @endforeach
                        @if($actions)
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    @if(is_callable($actions))
                                        {!! $actions($row) !!}
                                    @else
                                        {!! $actions !!}
                                    @endif
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($headers) + ($actions ? 1 : 0) }}" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ $emptyMessage }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile Card View -->
    <div class="md:hidden">
        @forelse($data as $row)
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                <div class="space-y-3">
                    @foreach($headers as $key => $header)
                        @php
                            $field = $header['field'] ?? $key;
                            $value = is_array($row) ? ($row[$field] ?? '') : ($row->{$field} ?? '');
                            $label = $header['label'] ?? $header;
                        @endphp
                        @if($value)
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center">
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $label }}
                                </dt>
                                <dd class="mt-1 sm:mt-0 text-sm text-gray-900 dark:text-gray-100">
                                    @if(isset($header['component']))
                                        @include($header['component'], ['value' => $value, 'row' => $row])
                                    @else
                                        {{ $value }}
                                    @endif
                                </dd>
                            </div>
                        @endif
                    @endforeach
                    
                    @if($actions)
                        <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex flex-wrap gap-2">
                                @if(is_callable($actions))
                                    {!! $actions($row) !!}
                                @else
                                    {!! $actions !!}
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ $emptyMessage }}
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($pagination)
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
            {{ $pagination }}
        </div>
    @endif
</div>

@if($searchable)
<script>
function filterTable(query) {
    // Simple client-side filtering
    const table = event.target.closest('.overflow-hidden');
    const rows = table.querySelectorAll('tbody tr, .md\\:hidden > div');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const matches = text.includes(query.toLowerCase());
        row.style.display = matches ? '' : 'none';
    });
}
</script>
@endif