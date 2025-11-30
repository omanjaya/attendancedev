@props([
    'title',
    'subtitle' => null,
    'breadcrumbs' => [],
    'actions' => null,
    'tabs' => null,
    'icon' => null,
])

<div class="mb-8 mt-6">
    <!-- Breadcrumbs -->
    @if(count($breadcrumbs) > 0)
    <nav class="flex mb-4" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            @foreach($breadcrumbs as $index => $breadcrumb)
                <li class="inline-flex items-center">
                    @if($index > 0)
                        <svg class="w-3 h-3 text-muted-foreground mx-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    @endif
                    
                    @if(isset($breadcrumb['href']) && $index < count($breadcrumbs) - 1)
                        <a href="{{ $breadcrumb['href'] }}" class="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors">
                            {{ $breadcrumb['label'] }}
                        </a>
                    @else
                        <span class="text-sm font-medium text-foreground">{{ $breadcrumb['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
    @endif
    
    <!-- Header Content -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-start gap-3">
            @if($icon)
                <div class="w-12 h-12 rounded-lg bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                    {!! $icon !!}
                </div>
            @endif
            
            <div class="min-w-0 flex-1">
                <h1 class="text-2xl font-bold text-foreground truncate">{{ $title }}</h1>
                @if($subtitle)
                    <p class="text-sm text-muted-foreground mt-1">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        
        @if($actions)
            <div class="flex items-center gap-3 flex-shrink-0">
                {{ $actions }}
            </div>
        @endif
    </div>
    
    <!-- Tabs -->
    @if($tabs)
    <div class="mt-6 border-b border-border">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            @foreach($tabs as $tab)
                @if($tab['active'])
                    <span class="border-primary text-primary whitespace-nowrap border-b-2 py-2 px-1 text-sm font-medium">
                        {{ $tab['label'] }}
                    </span>
                @else
                    <a href="{{ $tab['href'] }}" 
                       class="border-transparent text-muted-foreground hover:text-foreground hover:border-muted-foreground whitespace-nowrap border-b-2 py-2 px-1 text-sm font-medium transition-colors">
                        {{ $tab['label'] }}
                    </a>
                @endif
            @endforeach
        </nav>
    </div>
    @endif
</div>