@props([
    'title' => '', 
    'description' => '', 
    'subtitle' => '', 
    'actions' => ''
])

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 sm:mb-8">
    <div class="mb-4 sm:mb-0">
        <h1 class="responsive-text-xl font-bold text-foreground">{{ $title }}</h1>
        @if($description || $subtitle)
            <p class="responsive-text-sm text-muted-foreground mt-1">{{ $description ?: $subtitle }}</p>
        @endif
    </div>
    
    @if($actions)
        <div class="flex-shrink-0 w-full sm:w-auto">
            <div class="responsive-button-group">
                {{ $actions }}
            </div>
        </div>
    @endif
</div>