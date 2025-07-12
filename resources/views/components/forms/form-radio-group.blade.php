@props([
    'label' => null,
    'name' => '',
    'options' => [],
    'value' => '',
    'required' => false,
    'disabled' => false,
    'error' => null,
    'help' => null,
    'layout' => 'vertical', // vertical, horizontal, grid
    'columns' => 2, // for grid layout
    'size' => 'default', // sm, default, lg
])

@php
    $groupId = $name . '_group_' . Str::random(6);
    $hasError = $error || $errors->has($name);
    $errorMessage = $error ?: $errors->first($name);
    $selectedValue = old($name, $value);
    
    // Layout classes
    $layoutClasses = [
        'vertical' => 'space-y-3',
        'horizontal' => 'flex flex-wrap gap-4',
        'grid' => 'grid gap-3 grid-cols-1 md:grid-cols-' . $columns
    ];
    
    $containerClass = $layoutClasses[$layout] ?? $layoutClasses['vertical'];
    
    // Size classes
    $sizeClasses = [
        'sm' => 'h-3 w-3',
        'default' => 'h-4 w-4',
        'lg' => 'h-5 w-5'
    ];
    
    $radioSize = $sizeClasses[$size] ?? $sizeClasses['default'];
@endphp

<div class="space-y-3">
    <!-- Group Label -->
    @if($label)
    <fieldset>
        <legend class="text-sm font-medium text-foreground">
            {{ $label }}
            @if($required)
                <span class="text-destructive ml-1">*</span>
            @endif
        </legend>
    @endif
    
    <!-- Radio Options -->
    <div class="{{ $containerClass }}" @if($label) id="{{ $groupId }}" @endif>
        @foreach($options as $optionValue => $optionData)
            @php
                $optionId = $name . '_' . $optionValue . '_' . Str::random(4);
                $isChecked = (string) $selectedValue === (string) $optionValue;
                
                // Handle different option formats
                if (is_string($optionData)) {
                    $optionLabel = $optionData;
                    $optionDescription = null;
                    $optionDisabled = false;
                } else {
                    $optionLabel = $optionData['label'] ?? $optionValue;
                    $optionDescription = $optionData['description'] ?? null;
                    $optionDisabled = $optionData['disabled'] ?? false;
                }
            @endphp
            
            <div class="flex items-start space-x-3">
                <div class="flex items-center h-5">
                    <input
                        type="radio"
                        id="{{ $optionId }}"
                        name="{{ $name }}"
                        value="{{ $optionValue }}"
                        @if($isChecked) checked @endif
                        @if($disabled || $optionDisabled) disabled @endif
                        @if($required) required @endif
                        class="{{ $radioSize }} border-input text-primary focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 transition-colors"
                    />
                </div>
                
                <div class="flex-1 min-w-0">
                    <label for="{{ $optionId }}" class="text-sm font-medium text-foreground cursor-pointer select-none">
                        {{ $optionLabel }}
                    </label>
                    
                    @if($optionDescription)
                    <p class="text-sm text-muted-foreground mt-1">{{ $optionDescription }}</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    
    @if($label)
    </fieldset>
    @endif
    
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