@props([
    'id' => 'dialog',
    'title' => '',
    'size' => 'default', // sm, default, lg, xl, full
    'closable' => true,
    'backdrop' => 'blur', // blur, dark, transparent
    'position' => 'center', // center, top
])

@php
    $sizeClasses = [
        'sm' => 'max-w-sm',
        'default' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-2xl',
        'full' => 'max-w-full mx-4'
    ];
    
    $backdropClasses = [
        'blur' => 'bg-background/80 backdrop-blur-sm',
        'dark' => 'bg-black/50',
        'transparent' => 'bg-transparent'
    ];
    
    $positionClasses = [
        'center' => 'items-center',
        'top' => 'items-start pt-16'
    ];
    
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['default'];
    $backdropClass = $backdropClasses[$backdrop] ?? $backdropClasses['blur'];
    $positionClass = $positionClasses[$position] ?? $positionClasses['center'];
@endphp

<!-- Dialog Overlay -->
<div id="{{ $id }}-overlay" 
     class="fixed inset-0 z-50 flex {{ $positionClass }} justify-center p-4 hidden {{ $backdropClass }}"
     data-dialog-overlay>
    
    <!-- Dialog Content -->
    <div class="relative w-full {{ $sizeClass }} bg-card border border-border rounded-lg shadow-lg transform transition-all duration-200 scale-95 opacity-0"
         data-dialog-content
         role="dialog"
         aria-modal="true"
         aria-labelledby="{{ $id }}-title">
        
        <!-- Header -->
        @if($title || $closable)
        <div class="flex items-center justify-between p-6 border-b border-border">
            @if($title)
                <h2 id="{{ $id }}-title" class="text-lg font-semibold text-foreground">{{ $title }}</h2>
            @endif
            
            @if($closable)
                <button type="button" 
                        class="text-muted-foreground hover:text-foreground transition-colors p-1 rounded-md hover:bg-muted/50"
                        data-dialog-close>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span class="sr-only">Close dialog</span>
                </button>
            @endif
        </div>
        @endif
        
        <!-- Body -->
        <div class="p-6 {{ $title || $closable ? '' : 'pt-6' }}">
            {{ $slot }}
        </div>
        
        <!-- Footer Slot -->
        @if(isset($footer))
        <div class="flex items-center justify-end gap-3 p-6 border-t border-border bg-muted/50 rounded-b-lg">
            {{ $footer }}
        </div>
        @endif
    </div>
</div>

<!-- JavaScript for Dialog Functionality -->
<script>
(function() {
    function initDialog(dialogId) {
        const overlay = document.getElementById(dialogId + '-overlay');
        const content = overlay?.querySelector('[data-dialog-content]');
        const closeBtn = overlay?.querySelector('[data-dialog-close]');
        
        if (!overlay || !content) return;
        
        function openDialog() {
            overlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            // Animate in
            requestAnimationFrame(() => {
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            });
            
            // Focus management
            const firstFocusable = content.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            firstFocusable?.focus();
        }
        
        function closeDialog() {
            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                overlay.classList.add('hidden');
                document.body.style.overflow = '';
            }, 200);
        }
        
        // Close button
        closeBtn?.addEventListener('click', closeDialog);
        
        // Backdrop click
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                closeDialog();
            }
        });
        
        // Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !overlay.classList.contains('hidden')) {
                closeDialog();
            }
        });
        
        // Expose functions globally
        window['open' + dialogId.charAt(0).toUpperCase() + dialogId.slice(1)] = openDialog;
        window['close' + dialogId.charAt(0).toUpperCase() + dialogId.slice(1)] = closeDialog;
    }
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initDialog('{{ $id }}');
        });
    } else {
        initDialog('{{ $id }}');
    }
})();
</script>