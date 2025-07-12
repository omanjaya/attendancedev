@props([
    'label' => null,
    'name' => '',
    'type' => 'text',
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'error' => null,
    'help' => null,
    'prefix' => null,
    'suffix' => null,
    'icon' => null,
    'floating' => false,
    'size' => 'default', // sm, default, lg
    'maxlength' => null,
    'minlength' => null,
    'pattern' => null,
    'autocomplete' => null,
])

@php
    $inputId = $name . '_' . Str::random(6);
    $hasError = $error || $errors->has($name);
    $errorMessage = $error ?: $errors->first($name);
    
    // Size classes
    $sizeClasses = [
        'sm' => 'h-8 px-3 text-sm',
        'default' => 'h-10 px-3 text-sm',
        'lg' => 'h-12 px-4 text-base'
    ];
    
    $labelSizeClasses = [
        'sm' => 'text-xs',
        'default' => 'text-sm',
        'lg' => 'text-base'
    ];
    
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['default'];
    $labelSizeClass = $labelSizeClasses[$size] ?? $labelSizeClasses['default'];
    
    // Input classes
    $inputClasses = 'flex w-full rounded-md border bg-background text-foreground transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50';
    
    if ($hasError) {
        $inputClasses .= ' border-destructive focus-visible:ring-destructive';
    } else {
        $inputClasses .= ' border-input';
    }
    
    $inputClasses .= ' ' . $sizeClass;
    
    // Adjust padding for prefix/suffix
    if ($prefix) {
        $inputClasses = str_replace('px-3', 'pl-10 pr-3', $inputClasses);
        $inputClasses = str_replace('px-4', 'pl-12 pr-4', $inputClasses);
    }
    if ($suffix) {
        $inputClasses = str_replace('px-3', 'pl-3 pr-10', $inputClasses);
        $inputClasses = str_replace('px-4', 'pl-4 pr-12', $inputClasses);
    }
    if ($prefix && $suffix) {
        $inputClasses = str_replace('pl-10 pr-10', 'px-10', $inputClasses);
        $inputClasses = str_replace('pl-12 pr-12', 'px-12', $inputClasses);
    }
@endphp

<div class="space-y-2">
    <!-- Label -->
    @if($label && !$floating)
    <label for="{{ $inputId }}" class="block font-medium text-foreground {{ $labelSizeClass }}">
        {{ $label }}
        @if($required)
            <span class="text-destructive ml-1">*</span>
        @endif
    </label>
    @endif
    
    <!-- Input Container -->
    <div class="relative">
        @if($floating && $label)
        <!-- Floating Label -->
        <input
            type="{{ $type }}"
            id="{{ $inputId }}"
            name="{{ $name }}"
            value="{{ old($name, $value) }}"
            placeholder=" "
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            @if($maxlength) maxlength="{{ $maxlength }}" @endif
            @if($minlength) minlength="{{ $minlength }}" @endif
            @if($pattern) pattern="{{ $pattern }}" @endif
            @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            {{ $attributes->merge(['class' => $inputClasses . ' peer']) }}
        />
        <label for="{{ $inputId }}" 
               class="absolute {{ $labelSizeClass }} text-muted-foreground duration-300 transform -translate-y-4 scale-75 top-2 z-10 origin-[0] bg-background px-2 peer-focus:px-2 peer-focus:text-primary peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-4 left-1">
            {{ $label }}
            @if($required)
                <span class="text-destructive ml-1">*</span>
            @endif
        </label>
        @else
        <!-- Regular Input -->
        <input
            type="{{ $type }}"
            id="{{ $inputId }}"
            name="{{ $name }}"
            value="{{ old($name, $value) }}"
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            @if($maxlength) maxlength="{{ $maxlength }}" @endif
            @if($minlength) minlength="{{ $minlength }}" @endif
            @if($pattern) pattern="{{ $pattern }}" @endif
            @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            {{ $attributes->merge(['class' => $inputClasses]) }}
        />
        @endif
        
        <!-- Prefix Icon/Text -->
        @if($prefix)
        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
            @if(str_contains($prefix, '<svg'))
                <div class="h-4 w-4 text-muted-foreground">{!! $prefix !!}</div>
            @else
                <span class="text-muted-foreground {{ $labelSizeClass }}">{{ $prefix }}</span>
            @endif
        </div>
        @endif
        
        <!-- Suffix Icon/Text -->
        @if($suffix)
        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            @if(str_contains($suffix, '<svg'))
                <div class="h-4 w-4 text-muted-foreground">{!! $suffix !!}</div>
            @else
                <span class="text-muted-foreground {{ $labelSizeClass }}">{{ $suffix }}</span>
            @endif
        </div>
        @endif
        
        <!-- Character Counter -->
        @if($maxlength)
        <div class="absolute bottom-0 right-0 transform translate-y-full mt-1">
            <span class="text-xs text-muted-foreground" data-counter-for="{{ $inputId }}">
                <span class="current">{{ strlen(old($name, $value)) }}</span>/<span class="max">{{ $maxlength }}</span>
            </span>
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

@if($maxlength)
<script>
(function() {
    const input = document.getElementById('{{ $inputId }}');
    const counter = document.querySelector('[data-counter-for="{{ $inputId }}"] .current');
    
    if (input && counter) {
        input.addEventListener('input', function() {
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
})();
</script>
@endif