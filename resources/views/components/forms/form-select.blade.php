@props([
    'label' => null,
    'name' => '',
    'value' => '',
    'options' => [],
    'placeholder' => 'Select an option...',
    'required' => false,
    'disabled' => false,
    'error' => null,
    'help' => null,
    'multiple' => false,
    'searchable' => false,
    'size' => 'default', // sm, default, lg
    'emptyOption' => true,
])

@php
    $selectId = $name . '_' . Str::random(6);
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
    
    // Select classes
    $selectClasses = 'flex w-full items-center justify-between rounded-md border bg-background text-foreground ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50';
    
    if ($hasError) {
        $selectClasses .= ' border-destructive focus:ring-destructive';
    } else {
        $selectClasses .= ' border-input';
    }
    
    $selectClasses .= ' ' . $sizeClass;
    
    $selectedValue = old($name, $value);
@endphp

<div class="space-y-2">
    <!-- Label -->
    @if($label)
    <label for="{{ $selectId }}" class="block font-medium text-foreground {{ $labelSizeClass }}">
        {{ $label }}
        @if($required)
            <span class="text-destructive ml-1">*</span>
        @endif
    </label>
    @endif
    
    @if($searchable)
    <!-- Searchable Select (Custom Dropdown) -->
    <div class="relative" x-data="searchableSelect({
        options: {{ json_encode($options) }},
        selected: '{{ $selectedValue }}',
        multiple: {{ $multiple ? 'true' : 'false' }},
        placeholder: '{{ $placeholder }}'
    })">
        <!-- Selected Value Display -->
        <button type="button" 
                @click="toggle()" 
                @click.away="close()"
                class="{{ $selectClasses }} cursor-pointer"
                :class="{ 'ring-2 ring-ring': isOpen }">
            <span x-text="displayText" class="block truncate" :class="{ 'text-muted-foreground': !hasSelection }"></span>
            <svg class="h-4 w-4 opacity-50 transition-transform" :class="{ 'rotate-180': isOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
        
        <!-- Hidden Input -->
        <input type="hidden" name="{{ $name }}" :value="selectedValue" />
        
        <!-- Dropdown -->
        <div x-show="isOpen" 
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="absolute z-50 w-full mt-1 bg-background border border-input rounded-md shadow-lg max-h-60 overflow-auto">
            
            <!-- Search Input -->
            <div class="p-2 border-b border-border">
                <input type="text" 
                       x-model="search" 
                       placeholder="Search options..."
                       class="w-full px-2 py-1 text-sm border border-input rounded focus:outline-none focus:ring-1 focus:ring-ring" />
            </div>
            
            <!-- Options -->
            <div class="py-1">
                <template x-for="option in filteredOptions" :key="option.value">
                    <button type="button"
                            @click="select(option.value)"
                            class="w-full px-3 py-2 text-left text-sm hover:bg-muted transition-colors flex items-center justify-between"
                            :class="{ 'bg-muted text-foreground': isSelected(option.value) }">
                        <span x-text="option.label"></span>
                        <svg x-show="isSelected(option.value)" class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                </template>
                
                <div x-show="filteredOptions.length === 0" class="px-3 py-2 text-sm text-muted-foreground">
                    No options found
                </div>
            </div>
        </div>
    </div>
    @else
    <!-- Regular Select -->
    <select
        id="{{ $selectId }}"
        name="{{ $name }}{{ $multiple ? '[]' : '' }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($multiple) multiple @endif
        {{ $attributes->merge(['class' => $selectClasses]) }}>
        
        @if($emptyOption && !$multiple)
            <option value="">{{ $placeholder }}</option>
        @endif
        
        @foreach($options as $optionValue => $optionLabel)
            @if(is_array($optionLabel))
                <!-- Option Group -->
                <optgroup label="{{ $optionValue }}">
                    @foreach($optionLabel as $groupValue => $groupLabel)
                        <option value="{{ $groupValue }}" 
                                {{ (is_array($selectedValue) ? in_array($groupValue, $selectedValue) : $selectedValue == $groupValue) ? 'selected' : '' }}>
                            {{ $groupLabel }}
                        </option>
                    @endforeach
                </optgroup>
            @else
                <!-- Regular Option -->
                <option value="{{ $optionValue }}" 
                        {{ (is_array($selectedValue) ? in_array($optionValue, $selectedValue) : $selectedValue == $optionValue) ? 'selected' : '' }}>
                    {{ $optionLabel }}
                </option>
            @endif
        @endforeach
    </select>
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

@if($searchable)
<script>
function searchableSelect(config) {
    return {
        isOpen: false,
        search: '',
        options: config.options,
        selectedValue: config.selected,
        multiple: config.multiple,
        placeholder: config.placeholder,
        
        get filteredOptions() {
            if (!this.search) return this.options;
            return this.options.filter(option => 
                option.label.toLowerCase().includes(this.search.toLowerCase())
            );
        },
        
        get hasSelection() {
            return this.multiple ? this.selectedValue.length > 0 : this.selectedValue !== '';
        },
        
        get displayText() {
            if (!this.hasSelection) return this.placeholder;
            
            if (this.multiple) {
                const selectedOptions = this.options.filter(opt => this.selectedValue.includes(opt.value));
                return selectedOptions.length === 1 
                    ? selectedOptions[0].label 
                    : `${selectedOptions.length} items selected`;
            } else {
                const option = this.options.find(opt => opt.value === this.selectedValue);
                return option ? option.label : this.placeholder;
            }
        },
        
        toggle() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.$nextTick(() => {
                    this.$el.querySelector('input[type="text"]')?.focus();
                });
            }
        },
        
        close() {
            this.isOpen = false;
            this.search = '';
        },
        
        select(value) {
            if (this.multiple) {
                if (this.selectedValue.includes(value)) {
                    this.selectedValue = this.selectedValue.filter(v => v !== value);
                } else {
                    this.selectedValue.push(value);
                }
            } else {
                this.selectedValue = value;
                this.close();
            }
        },
        
        isSelected(value) {
            return this.multiple ? this.selectedValue.includes(value) : this.selectedValue === value;
        }
    };
}
</script>
@endif