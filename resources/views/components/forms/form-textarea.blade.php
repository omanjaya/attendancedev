@props([
    'label' => null,
    'name' => '',
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'error' => null,
    'help' => null,
    'rows' => 3,
    'cols' => null,
    'maxlength' => null,
    'minlength' => null,
    'resize' => 'vertical', // none, both, horizontal, vertical
    'autoResize' => false,
])

@php
    $textareaId = $name . '_' . Str::random(6);
    $hasError = $error || $errors->has($name);
    $errorMessage = $error ?: $errors->first($name);
    
    // Textarea classes
    $textareaClasses = 'flex min-h-[80px] w-full rounded-md border bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50';
    
    if ($hasError) {
        $textareaClasses .= ' border-destructive focus-visible:ring-destructive';
    } else {
        $textareaClasses .= ' border-input';
    }
    
    // Resize classes
    $resizeClasses = [
        'none' => 'resize-none',
        'both' => 'resize',
        'horizontal' => 'resize-x',
        'vertical' => 'resize-y'
    ];
    
    $textareaClasses .= ' ' . ($resizeClasses[$resize] ?? $resizeClasses['vertical']);
@endphp

<div class="space-y-2">
    <!-- Label -->
    @if($label)
    <label for="{{ $textareaId }}" class="block text-sm font-medium text-foreground">
        {{ $label }}
        @if($required)
            <span class="text-destructive ml-1">*</span>
        @endif
    </label>
    @endif
    
    <!-- Textarea Container -->
    <div class="relative">
        <textarea
            id="{{ $textareaId }}"
            name="{{ $name }}"
            rows="{{ $rows }}"
            @if($cols) cols="{{ $cols }}" @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            @if($maxlength) maxlength="{{ $maxlength }}" @endif
            @if($minlength) minlength="{{ $minlength }}" @endif
            @if($autoResize) data-auto-resize @endif
            {{ $attributes->merge(['class' => $textareaClasses]) }}>{{ old($name, $value) }}</textarea>
        
        <!-- Character Counter -->
        @if($maxlength)
        <div class="absolute bottom-2 right-2 text-xs text-muted-foreground bg-background/80 backdrop-blur-sm px-2 py-1 rounded" data-counter-for="{{ $textareaId }}">
            <span class="current">{{ strlen(old($name, $value)) }}</span>/<span class="max">{{ $maxlength }}</span>
        </div>
        @endif
    </div>
    
    <!-- Error Message -->
    @if($hasError)
    <div class="flex items-center gap-2 text-sm text-destructive">
        <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ $errorMessage }}</span>
    </div>
    @endif
    
    <!-- Help Text -->
    @if($help && !$hasError)
    <p class="text-sm text-muted-foreground">{{ $help }}</p>
    @endif
</div>

<script>
(function() {
    const textarea = document.getElementById('{{ $textareaId }}');
    
    @if($maxlength)
    // Character counter
    const counter = document.querySelector('[data-counter-for="{{ $textareaId }}"] .current');
    
    if (textarea && counter) {
        textarea.addEventListener('input', function() {
            counter.textContent = this.value.length;
            
            // Color coding
            const percent = (this.value.length / {{ $maxlength }}) * 100;
            const counterContainer = counter.closest('[data-counter-for]');
            
            counterContainer.classList.remove('text-muted-foreground', 'text-warning', 'text-destructive');
            
            if (percent >= 90) {
                counterContainer.classList.add('text-destructive');
            } else if (percent >= 75) {
                counterContainer.classList.add('text-warning');
            } else {
                counterContainer.classList.add('text-muted-foreground');
            }
        });
    }
    @endif
    
    @if($autoResize)
    // Auto resize functionality
    if (textarea) {
        function autoResize() {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        }
        
        textarea.addEventListener('input', autoResize);
        
        // Initial resize
        autoResize();
    }
    @endif
})();
</script>