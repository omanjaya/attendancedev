@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'error' => null,
    'help' => null,
    'icon' => null,
    'addon' => null,
    'options' => [], // for select fields
    'rows' => 4, // for textarea
    'multiple' => false, // for file/select
    'accept' => null, // for file input
])

@php
    $inputId = $name ? "field-{$name}" : "field-" . uniqid();
    $hasError = !empty($error) || $errors->has($name ?? '');
    $errorMessage = $error ?? $errors->first($name ?? '');
    
    $baseInputClasses = 'block w-full rounded-lg border bg-white dark:bg-gray-700 px-3 py-2.5 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-colors duration-200 text-base';
    
    $borderClasses = $hasError 
        ? 'border-red-300 dark:border-red-600' 
        : 'border-gray-300 dark:border-gray-600';
        
    $inputClasses = $baseInputClasses . ' ' . $borderClasses;
    
    if ($disabled) {
        $inputClasses .= ' bg-gray-50 dark:bg-gray-800 cursor-not-allowed opacity-50';
    }
    
    if ($icon) {
        $inputClasses .= ' pl-10';
    }
    
    if ($addon) {
        $inputClasses .= ' pr-12';
    }
@endphp

<div class="w-full">
    <!-- Label -->
    @if($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            {{ $label }}
            @if($required)
                <span class="text-red-500 ml-1">*</span>
            @endif
        </label>
    @endif

    <!-- Input Container -->
    <div class="relative">
        <!-- Icon -->
        @if($icon)
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="{{ $icon }} text-gray-400 text-sm"></i>
            </div>
        @endif

        <!-- Input Field -->
        @if($type === 'textarea')
            <textarea 
                id="{{ $inputId }}"
                name="{{ $name }}"
                rows="{{ $rows }}"
                placeholder="{{ $placeholder }}"
                {{ $required ? 'required' : '' }}
                {{ $disabled ? 'disabled' : '' }}
                {{ $readonly ? 'readonly' : '' }}
                class="{{ $inputClasses }} resize-none">{{ old($name, $value) }}</textarea>
                
        @elseif($type === 'select')
            <select 
                id="{{ $inputId }}"
                name="{{ $name }}{{ $multiple ? '[]' : '' }}"
                {{ $required ? 'required' : '' }}
                {{ $disabled ? 'disabled' : '' }}
                {{ $multiple ? 'multiple' : '' }}
                class="{{ $inputClasses }}">
                @if(!$multiple && !$required)
                    <option value="">{{ $placeholder ?: 'Select an option' }}</option>
                @endif
                @foreach($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" 
                            {{ old($name, $value) == $optionValue ? 'selected' : '' }}>
                        {{ $optionLabel }}
                    </option>
                @endforeach
            </select>
            
        @elseif($type === 'file')
            <input 
                type="file"
                id="{{ $inputId }}"
                name="{{ $name }}{{ $multiple ? '[]' : '' }}"
                {{ $multiple ? 'multiple' : '' }}
                {{ $accept ? "accept=\"{$accept}\"" : '' }}
                {{ $required ? 'required' : '' }}
                {{ $disabled ? 'disabled' : '' }}
                class="{{ $inputClasses }} file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-900/20 dark:file:text-emerald-400">
                
        @elseif($type === 'checkbox')
            <div class="flex items-center">
                <input 
                    type="checkbox"
                    id="{{ $inputId }}"
                    name="{{ $name }}"
                    value="1"
                    {{ old($name, $value) ? 'checked' : '' }}
                    {{ $required ? 'required' : '' }}
                    {{ $disabled ? 'disabled' : '' }}
                    class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                @if($label)
                    <label for="{{ $inputId }}" class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                        {{ $placeholder }}
                    </label>
                @endif
            </div>
            
        @else
            <input 
                type="{{ $type }}"
                id="{{ $inputId }}"
                name="{{ $name }}"
                value="{{ old($name, $value) }}"
                placeholder="{{ $placeholder }}"
                {{ $required ? 'required' : '' }}
                {{ $disabled ? 'disabled' : '' }}
                {{ $readonly ? 'readonly' : '' }}
                class="{{ $inputClasses }}">
        @endif

        <!-- Addon -->
        @if($addon)
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                {{ $addon }}
            </div>
        @endif
    </div>

    <!-- Help Text -->
    @if($help)
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            {{ $help }}
        </p>
    @endif

    <!-- Error Message -->
    @if($hasError)
        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center">
            <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            {{ $errorMessage }}
        </p>
    @endif
</div>

<style>
    /* Enhanced mobile input styling */
    @media (max-width: 640px) {
        /* Prevent zoom on iOS when focusing inputs */
        input[type="text"], 
        input[type="email"], 
        input[type="password"], 
        input[type="number"], 
        input[type="tel"], 
        input[type="url"],
        input[type="date"],
        input[type="time"],
        input[type="datetime-local"],
        textarea, 
        select {
            font-size: 16px !important;
        }
        
        /* Better touch targets */
        input, select, textarea {
            min-height: 44px;
        }
        
        /* Enhanced focus states for touch */
        input:focus, 
        select:focus, 
        textarea:focus {
            transform: none; /* Prevent layout shift */
            box-shadow: 0 0 0 2px #10b981, 0 0 0 4px rgba(16, 185, 129, 0.1);
        }
    }
    
    /* Custom file input styling */
    input[type="file"]::-webkit-file-upload-button {
        transition: all 0.2s ease-in-out;
    }
    
    /* Custom select arrow */
    select {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 0.5rem center;
        background-repeat: no-repeat;
        background-size: 1.5em 1.5em;
        padding-right: 2.5rem;
        appearance: none;
    }
    
    .dark select {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%259ca3af' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    }
</style>