@props([
    'id' => null,
    'data' => '[]',
    'labels' => '[]',
    'title' => null,
    'height' => '300',
    'colors' => ['#3b82f6'],
    'options' => '{}',
    'loading' => false,
    'empty' => false,
    'emptyMessage' => 'No data available',
    'horizontal' => false
])

@php
    $chartId = $id ?: 'bar-chart-' . Str::random(6);
    $chartData = is_string($data) ? $data : json_encode($data);
    $chartLabels = is_string($labels) ? $labels : json_encode($labels);
    $chartOptions = is_string($options) ? $options : json_encode($options);
    $chartColors = is_string($colors) ? $colors : json_encode($colors);
    $chartType = $horizontal ? 'horizontalBar' : 'bar';
@endphp

<div class="relative" style="height: {{ $height }}px">
    @if($loading)
        <!-- Loading State -->
        <div class="absolute inset-0 flex items-center justify-center bg-background/80 backdrop-blur-sm rounded-lg">
            <div class="text-center">
                <div class="animate-spin rounded-full h-8 w-8 border-2 border-primary border-t-transparent mx-auto mb-2"></div>
                <p class="text-sm text-muted-foreground">Loading chart...</p>
            </div>
        </div>
    @elseif($empty)
        <!-- Empty State -->
        <div class="absolute inset-0 flex items-center justify-center">
            <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-muted-foreground mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                </svg>
                <h3 class="text-sm font-medium text-foreground mb-1">{{ $title ?: 'Chart' }}</h3>
                <p class="text-sm text-muted-foreground">{{ $emptyMessage }}</p>
            </div>
        </div>
    @else
        <!-- Chart Canvas -->
        <canvas id="{{ $chartId }}" width="400" height="{{ $height }}"></canvas>
    @endif
</div>

@if(!$loading && !$empty)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('{{ $chartId }}').getContext('2d');
    
    const data = {!! $chartData !!};
    const labels = {!! $chartLabels !!};
    const colors = {!! $chartColors !!};
    const customOptions = {!! $chartOptions !!};
    const isHorizontal = {{ $horizontal ? 'true' : 'false' }};
    
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: isHorizontal ? 'y' : 'x',
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    usePointStyle: true,
                    padding: 20,
                    font: {
                        size: 12,
                        family: 'Inter, system-ui, sans-serif'
                    },
                    color: 'hsl(var(--muted-foreground))'
                }
            },
            tooltip: {
                backgroundColor: 'hsl(var(--popover))',
                titleColor: 'hsl(var(--popover-foreground))',
                bodyColor: 'hsl(var(--popover-foreground))',
                borderColor: 'hsl(var(--border))',
                borderWidth: 1,
                cornerRadius: 8,
                padding: 12,
                titleFont: {
                    size: 13,
                    weight: '500'
                },
                bodyFont: {
                    size: 12
                }
            }
        },
        scales: {
            x: {
                grid: {
                    display: !isHorizontal,
                    color: 'hsl(var(--border))',
                    borderDash: [2, 2]
                },
                ticks: {
                    color: 'hsl(var(--muted-foreground))',
                    font: {
                        size: 11
                    }
                }
            },
            y: {
                grid: {
                    display: isHorizontal,
                    color: 'hsl(var(--border))',
                    borderDash: [2, 2]
                },
                ticks: {
                    color: 'hsl(var(--muted-foreground))',
                    font: {
                        size: 11
                    }
                }
            }
        },
        elements: {
            bar: {
                borderRadius: 4,
                borderSkipped: false,
                borderWidth: 0
            }
        }
    };
    
    // Merge custom options with defaults
    const finalOptions = { ...defaultOptions, ...customOptions };
    
    // Prepare datasets
    const datasets = Array.isArray(data) ? data.map((dataset, index) => ({
        label: dataset.label || `Dataset ${index + 1}`,
        data: dataset.data || [],
        backgroundColor: dataset.backgroundColor || colors[index % colors.length] + '80',
        borderColor: dataset.borderColor || colors[index % colors.length],
        borderWidth: dataset.borderWidth || 0,
        ...dataset
    })) : [{
        label: 'Data',
        data: data,
        backgroundColor: colors[0] + '80',
        borderColor: colors[0],
        borderWidth: 0
    }];
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: finalOptions
    });
});
</script>
@endif