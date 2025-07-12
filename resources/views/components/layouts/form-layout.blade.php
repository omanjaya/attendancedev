@props([
    'title' => null,
    'subtitle' => null,
    'action' => '',
    'method' => 'POST',
    'enctype' => null,
    'submitText' => 'Submit',
    'cancelUrl' => null,
    'cancelText' => 'Cancel',
    'sections' => [],
    'sidebar' => null,
    'loading' => false,
    'breadcrumbs' => [],
    'cards' => true,
    'spacing' => 'default', // compact, default, relaxed
])

@php
    $spacingClasses = [
        'compact' => 'space-y-4',
        'default' => 'space-y-6',
        'relaxed' => 'space-y-8'
    ];
    
    $containerSpacing = $spacingClasses[$spacing] ?? $spacingClasses['default'];
@endphp

<div>
    <!-- Page Header -->
    @if($title)
    <x-layouts.page-header 
        :title="$title"
        :subtitle="$subtitle"
        :breadcrumbs="$breadcrumbs" />
    @endif
    
    <div class="grid grid-cols-1 {{ $sidebar ? 'lg:grid-cols-3' : '' }} gap-8">
        <!-- Main Form Content -->
        <div class="{{ $sidebar ? 'lg:col-span-2' : '' }}">
            @if($loading)
                <!-- Loading State -->
                <div class="{{ $containerSpacing }}">
                    @for($i = 0; $i < 3; $i++)
                        <x-ui.card>
                            <div class="space-y-4">
                                <x-ui.skeleton height="h-6" width="w-1/3" />
                                <x-ui.skeleton height="h-4" width="w-full" />
                                <x-ui.skeleton height="h-10" width="w-full" />
                                <x-ui.skeleton height="h-4" width="w-2/3" />
                                <x-ui.skeleton height="h-10" width="w-full" />
                            </div>
                        </x-ui.card>
                    @endfor
                </div>
            @else
                <form action="{{ $action }}" 
                      method="{{ $method }}" 
                      @if($enctype) enctype="{{ $enctype }}" @endif
                      class="{{ $containerSpacing }}"
                      x-data="formHandler()"
                      @submit="handleSubmit">
                    
                    @if($method !== 'GET')
                        @csrf
                    @endif
                    
                    @if(in_array(strtoupper($method), ['PUT', 'PATCH', 'DELETE']))
                        @method($method)
                    @endif
                    
                    <!-- Form Sections -->
                    @if(count($sections) > 0)
                        @foreach($sections as $section)
                            @if($cards)
                                <x-ui.card 
                                    :title="$section['title'] ?? null"
                                    :subtitle="$section['subtitle'] ?? null"
                                    variant="{{ $section['variant'] ?? 'default' }}">
                                    {!! $section['content'] ?? '' !!}
                                </x-ui.card>
                            @else
                                <div class="space-y-4">
                                    @if(isset($section['title']))
                                        <div>
                                            <h3 class="text-lg font-semibold text-foreground">{{ $section['title'] }}</h3>
                                            @if(isset($section['subtitle']))
                                                <p class="text-sm text-muted-foreground mt-1">{{ $section['subtitle'] }}</p>
                                            @endif
                                        </div>
                                    @endif
                                    {!! $section['content'] ?? '' !!}
                                </div>
                            @endif
                        @endforeach
                    @else
                        <!-- Default Content Slot -->
                        @if($cards)
                            <x-ui.card>
                                {{ $slot }}
                            </x-ui.card>
                        @else
                            {{ $slot }}
                        @endif
                    @endif
                    
                    <!-- Form Actions -->
                    <div class="flex items-center justify-end gap-4 pt-6 border-t border-border">
                        @if($cancelUrl)
                            <x-ui.button 
                                type="button" 
                                variant="outline" 
                                href="{{ $cancelUrl }}">
                                {{ $cancelText }}
                            </x-ui.button>
                        @endif
                        
                        <x-ui.button 
                            type="submit" 
                            :loading="submitting"
                            :disabled="submitting">
                            <span x-show="!submitting">{{ $submitText }}</span>
                            <span x-show="submitting">Processing...</span>
                        </x-ui.button>
                    </div>
                </form>
            @endif
        </div>
        
        <!-- Sidebar -->
        @if($sidebar)
        <div class="lg:col-span-1">
            <div class="sticky top-8 space-y-6">
                {{ $sidebar }}
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Form Handler Script -->
<script>
function formHandler() {
    return {
        submitting: false,
        
        handleSubmit(event) {
            // Prevent double submission
            if (this.submitting) {
                event.preventDefault();
                return;
            }
            
            this.submitting = true;
            
            // Allow form to submit normally
            // The submitting state will be reset on page reload/redirect
            
            // Optional: Add client-side validation here
            const form = event.target;
            const formData = new FormData(form);
            
            // Example: Check required fields
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.focus();
                    this.submitting = false;
                    return;
                }
            });
            
            if (!isValid) {
                event.preventDefault();
                this.submitting = false;
            }
        },
        
        resetForm() {
            this.submitting = false;
        }
    };
}

// Reset form state on page show (for back button)
window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        // Page was loaded from cache, reset form states
        document.querySelectorAll('[x-data*="formHandler"]').forEach(form => {
            const alpineData = Alpine.$data(form);
            if (alpineData && alpineData.resetForm) {
                alpineData.resetForm();
            }
        });
    }
});
</script>