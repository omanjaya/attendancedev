@props([
    'title' => null,
    'subtitle' => null,
    'breadcrumbs' => [],
    'actions' => null,
    'tabs' => null,
    'headerless' => false,
    'fluid' => false,
    'loading' => false,
])

{{-- Base page layout with consistent structure --}}
<div class="min-h-screen bg-background">
    {{-- Page Loading Overlay --}}
    @if($loading)
    <div class="fixed inset-0 bg-background/80 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="flex flex-col items-center space-y-4">
            <div class="animate-spin rounded-full h-12 w-12 border-4 border-primary border-t-transparent"></div>
            <p class="text-sm text-muted-foreground">Loading...</p>
        </div>
    </div>
    @endif

    {{-- Page Header --}}
    @unless($headerless)
    <div class="bg-card border-b">
        <div class="@if($fluid) w-full px-4 sm:px-6 lg:px-8 @else max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 @endif">
            {{-- Breadcrumbs --}}
            @if(count($breadcrumbs) > 0)
            <nav class="flex py-4" aria-label="Breadcrumb">
                <ol role="list" class="flex items-center space-x-2 text-sm">
                    @foreach($breadcrumbs as $index => $crumb)
                        <li class="flex items-center">
                            @if($index > 0)
                                <svg class="h-4 w-4 text-muted-foreground mx-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                            @if(isset($crumb['url']) && $index < count($breadcrumbs) - 1)
                                <a href="{{ $crumb['url'] }}" class="text-muted-foreground hover:text-foreground transition-colors">
                                    {{ $crumb['label'] }}
                                </a>
                            @else
                                <span class="text-foreground font-medium">{{ $crumb['label'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
            @endif

            {{-- Header Content --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between @if(count($breadcrumbs) > 0) py-4 @else py-6 @endif">
                <div class="flex-1 min-w-0">
                    @if($title)
                    <h1 class="text-2xl font-bold text-foreground truncate">{{ $title }}</h1>
                    @endif
                    @if($subtitle)
                    <p class="mt-1 text-sm text-muted-foreground">{{ $subtitle }}</p>
                    @endif
                </div>
                
                @if($actions)
                <div class="mt-4 sm:mt-0 sm:ml-4 flex-shrink-0">
                    <div class="flex space-x-3">
                        {{ $actions }}
                    </div>
                </div>
                @endif
            </div>

            {{-- Tabs Navigation --}}
            @if($tabs)
            <div class="border-t border-border">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    {{ $tabs }}
                </nav>
            </div>
            @endif
        </div>
    </div>
    @endunless

    {{-- Page Content --}}
    <main class="@if($fluid) w-full px-4 sm:px-6 lg:px-8 @else max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 @endif py-6">
        {{ $slot }}
    </main>
</div>