@props([
    'id' => null,
    'data' => '[]',
    'labels' => '[]',
    'title' => null,
    'height' => '300',
    'colors' => ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'],
    'options' => '{}',
    'loading' => false,
    'empty' => false,
    'emptyMessage' => 'No data available',
    'showLegend' => true,
    'legendPosition' => 'right',
    'centerText' => null,
    'centerValue' => null,
    'cutout' => '60%'
])

@php
    $chartId = $id ?: 'donut-chart-' . Str::random(6);
    $chartData = is_string($data) ? $data : json_encode($data);
    $chartLabels = is_string($labels) ? $labels : json_encode($labels);
    $chartOptions = is_string($options) ? $options : json_encode($options);
    $chartColors = is_string($colors) ? $colors : json_encode($colors);
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                </svg>
                <h3 class="text-sm font-medium text-foreground mb-1">{{ $title ?: 'Chart' }}</h3>
                <p class="text-sm text-muted-foreground">{{ $emptyMessage }}</p>
            </div>
        </div>
    @else
        <!-- Chart Container -->
        <div class="relative w-full h-full">
            <canvas id="{{ $chartId }}" width="400" height="{{ $height }}"></canvas>
            
            @if($centerText || $centerValue)
            <!-- Center Text Overlay -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div class="text-center">
                    @if($centerValue)
                        <div class="text-2xl font-bold text-foreground">{{ $centerValue }}</div>
                    @endif
                    @if($centerText)
                        <div class="text-sm text-muted-foreground">{{ $centerText }}</div>
                    @endif
                </div>
            </div>
            @endif
        </div>
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
    const showLegend = {{ $showLegend ? 'true' : 'false' }};
    const legendPosition = '{{ $legendPosition }}';
    const cutout = '{{ $cutout }}';
    
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: showLegend,
                position: legendPosition,
                labels: {
                    usePointStyle: true,
                    padding: 20,
                    font: {
                        size: 12,
                        family: 'Inter, system-ui, sans-serif'
                    },
                    color: 'hsl(var(--muted-foreground))',
                    generateLabels: function(chart) {
                        const data = chart.data;
                        if (data.labels.length && data.datasets.length) {
                            return data.labels.map((label, i) => {
                                const dataset = data.datasets[0];
                                const value = dataset.data[i];
                                const total = dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                
                                return {
                                    text: `${label} (${percentage}%)`,
                                    fillStyle: dataset.backgroundColor[i],
                                    strokeStyle: dataset.backgroundColor[i],
                                    lineWidth: 0,
                                    pointStyle: 'circle',
                                    hidden: false,
                                    index: i
                                };
                            });
                        }
                        return [];
                    }
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
                },
                callbacks: {
                    label: function(context) {
                        const dataset = context.dataset;
                        const total = dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = Math.round((context.parsed / total) * 100);
                        return `${context.label}: ${context.parsed} (${percentage}%)`;
                    }
                }
            }
        },
        cutout: cutout,
        elements: {
            arc: {
                borderWidth: 2,
                borderColor: 'hsl(var(--background))',
                hoverBorderWidth: 3
            }
        },
        animation: {
            animateRotate: true,
            animateScale: false
        }
    };
    
    // Merge custom options with defaults
    const finalOptions = { ...defaultOptions, ...customOptions };
    
    // Prepare dataset
    const dataset = {
        label: 'Data',
        data: Array.isArray(data) ? data : [data],
        backgroundColor: colors.slice(0, Array.isArray(data) ? data.length : 1),
        borderColor: colors.slice(0, Array.isArray(data) ? data.length : 1),
        borderWidth: 0,
        hoverOffset: 4
    };
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [dataset]
        },
        options: finalOptions
    });
});
</script>
@endif