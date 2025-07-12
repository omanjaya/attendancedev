@props([
    'label' => null,
    'name' => '',
    'accept' => null,
    'multiple' => false,
    'required' => false,
    'disabled' => false,
    'error' => null,
    'help' => null,
    'maxSize' => null, // in MB
    'maxFiles' => null,
    'preview' => true,
    'dragDrop' => true,
    'existingFiles' => [],
])

@php
    $uploadId = $name . '_' . Str::random(6);
    $hasError = $error || $errors->has($name);
    $errorMessage = $error ?: $errors->first($name);
    
    // Accept types for display
    $acceptTypes = $accept ? explode(',', $accept) : ['*'];
    $acceptDisplay = $accept ? implode(', ', array_map('trim', $acceptTypes)) : 'All files';
@endphp

<div class="space-y-3">
    <!-- Label -->
    @if($label)
    <label for="{{ $uploadId }}" class="block text-sm font-medium text-foreground">
        {{ $label }}
        @if($required)
            <span class="text-destructive ml-1">*</span>
        @endif
    </label>
    @endif
    
    @if($dragDrop)
    <!-- Drag & Drop Upload Area -->
    <div class="file-upload-container" data-upload-id="{{ $uploadId }}">
        <div class="upload-area relative border-2 border-dashed rounded-lg p-6 text-center transition-colors duration-200"
             :class="{
                'border-primary bg-primary/5': isDragging,
                'border-destructive bg-destructive/5': hasError,
                'border-input hover:border-primary/50': !isDragging && !hasError
             }"
             x-data="{
                isDragging: false,
                files: [],
                hasError: {{ $hasError ? 'true' : 'false' }}
             }"
             @dragover.prevent="isDragging = true"
             @dragleave.prevent="isDragging = false"
             @drop.prevent="
                isDragging = false;
                handleFiles($event.dataTransfer.files);
             ">
            
            <!-- Upload Icon & Text -->
            <div class="upload-prompt" x-show="files.length === 0">
                <svg class="mx-auto h-12 w-12 text-muted-foreground mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                
                <div class="space-y-2">
                    <p class="text-sm font-medium text-foreground">
                        Drop files here or 
                        <button type="button" class="text-primary hover:text-primary/80 underline" onclick="document.getElementById('{{ $uploadId }}').click()">
                            browse
                        </button>
                    </p>
                    
                    <p class="text-xs text-muted-foreground">
                        Accepted: {{ $acceptDisplay }}
                        @if($maxSize)
                            • Max size: {{ $maxSize }}MB
                        @endif
                        @if($multiple && $maxFiles)
                            • Max files: {{ $maxFiles }}
                        @endif
                    </p>
                </div>
            </div>
            
            <!-- File Preview -->
            <div x-show="files.length > 0" class="file-preview space-y-3">
                <template x-for="(file, index) in files" :key="index">
                    <div class="flex items-center justify-between p-3 bg-muted rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <!-- File type icon -->
                                <svg class="h-8 w-8 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-foreground truncate" x-text="file.name"></p>
                                <p class="text-xs text-muted-foreground" x-text="formatFileSize(file.size)"></p>
                            </div>
                        </div>
                        
                        <button type="button" 
                                @click="removeFile(index)"
                                class="text-muted-foreground hover:text-destructive transition-colors">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </template>
                
                @if($multiple)
                <button type="button" 
                        @click="document.getElementById('{{ $uploadId }}').click()"
                        class="w-full p-3 border border-dashed border-primary text-primary hover:bg-primary/5 rounded-lg transition-colors">
                    <svg class="h-5 w-5 mx-auto mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <span class="text-sm">Add more files</span>
                </button>
                @endif
            </div>
        </div>
        
        <!-- Hidden File Input -->
        <input
            type="file"
            id="{{ $uploadId }}"
            name="{{ $name }}{{ $multiple ? '[]' : '' }}"
            @if($accept) accept="{{ $accept }}" @endif
            @if($multiple) multiple @endif
            @if($required) required @endif
            @if($disabled) disabled @endif
            @change="handleFiles($event.target.files)"
            class="hidden"
        />
    </div>
    @else
    <!-- Simple File Input -->
    <input
        type="file"
        id="{{ $uploadId }}"
        name="{{ $name }}{{ $multiple ? '[]' : '' }}"
        @if($accept) accept="{{ $accept }}" @endif
        @if($multiple) multiple @endif
        @if($required) required @endif
        @if($disabled) disabled @endif
        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
    />
    @endif
    
    <!-- Existing Files -->
    @if(count($existingFiles) > 0)
    <div class="existing-files space-y-2">
        <p class="text-sm font-medium text-foreground">Current files:</p>
        <div class="space-y-2">
            @foreach($existingFiles as $file)
            <div class="flex items-center justify-between p-2 bg-muted/50 rounded">
                <div class="flex items-center space-x-2">
                    <svg class="h-4 w-4 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="text-sm text-foreground">{{ is_array($file) ? ($file['name'] ?? $file['filename'] ?? 'File') : $file }}</span>
                </div>
                
                @if(is_array($file) && isset($file['url']))
                <a href="{{ $file['url'] }}" 
                   target="_blank" 
                   class="text-primary hover:text-primary/80 text-sm underline">
                    View
                </a>
                @endif
            </div>
            @endforeach
        </div>
    </div>
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

@if($dragDrop)
<script>
function initFileUpload(uploadId) {
    return {
        isDragging: false,
        files: [],
        hasError: {{ $hasError ? 'true' : 'false' }},
        
        handleFiles(fileList) {
            const newFiles = Array.from(fileList);
            
            // Validate files
            const validFiles = newFiles.filter(file => this.validateFile(file));
            
            @if($multiple)
            // Add to existing files
            @if($maxFiles)
            const remainingSlots = {{ $maxFiles }} - this.files.length;
            this.files = [...this.files, ...validFiles.slice(0, remainingSlots)];
            @else
            this.files = [...this.files, ...validFiles];
            @endif
            @else
            // Replace existing file
            this.files = validFiles.slice(0, 1);
            @endif
            
            // Update hidden input
            this.updateFileInput();
        },
        
        validateFile(file) {
            @if($maxSize)
            const maxSizeBytes = {{ $maxSize }} * 1024 * 1024;
            if (file.size > maxSizeBytes) {
                alert(`File "${file.name}" is too large. Maximum size is {{ $maxSize }}MB.`);
                return false;
            }
            @endif
            
            @if($accept)
            const acceptedTypes = '{{ $accept }}'.split(',').map(type => type.trim());
            const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
            const mimeType = file.type;
            
            const isAccepted = acceptedTypes.some(type => {
                if (type === '*' || type === '*/*') return true;
                if (type.startsWith('.')) return fileExtension === type;
                if (type.includes('/')) return mimeType.match(new RegExp(type.replace('*', '.*')));
                return false;
            });
            
            if (!isAccepted) {
                alert(`File "${file.name}" is not an accepted file type.`);
                return false;
            }
            @endif
            
            return true;
        },
        
        removeFile(index) {
            this.files.splice(index, 1);
            this.updateFileInput();
        },
        
        updateFileInput() {
            const input = document.getElementById('{{ $uploadId }}');
            const dt = new DataTransfer();
            
            this.files.forEach(file => dt.items.add(file));
            input.files = dt.files;
        },
        
        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    };
}

// Initialize when Alpine is ready
document.addEventListener('alpine:init', () => {
    Alpine.data('fileUpload_{{ $uploadId }}', () => initFileUpload('{{ $uploadId }}'));
});
</script>
@endif