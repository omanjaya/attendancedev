@extends('layouts.app')

@section('title', 'Standardized Components Demo')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                Standardized Components Demo
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                All components now use pure Tailwind CSS with consistent emerald green theming
            </p>
        </div>
        
        <!-- Buttons Section -->
        <x-ui.card title="Button Variants" class="mb-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="space-y-2">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Default</p>
                    <x-ui.button>Default Button</x-ui.button>
                    <x-ui.button size="sm">Small Button</x-ui.button>
                    <x-ui.button size="lg">Large Button</x-ui.button>
                </div>
                
                <div class="space-y-2">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Secondary</p>
                    <x-ui.button variant="secondary">Secondary</x-ui.button>
                    <x-ui.button variant="outline">Outline</x-ui.button>
                    <x-ui.button variant="ghost">Ghost</x-ui.button>
                </div>
                
                <div class="space-y-2">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Status</p>
                    <x-ui.button variant="success">Success</x-ui.button>
                    <x-ui.button variant="warning">Warning</x-ui.button>
                    <x-ui.button variant="destructive">Destructive</x-ui.button>
                </div>
                
                <div class="space-y-2">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">States</p>
                    <x-ui.button :loading="true">Loading</x-ui.button>
                    <x-ui.button disabled>Disabled</x-ui.button>
                    <x-ui.button variant="link">Link Button</x-ui.button>
                </div>
            </div>
        </x-ui.card>

        <!-- Badges Section -->
        <x-ui.card title="Badge Variants" class="mb-8">
            <div class="flex flex-wrap gap-3">
                <x-ui.badge>Default Badge</x-ui.badge>
                <x-ui.badge variant="secondary">Secondary</x-ui.badge>
                <x-ui.badge variant="success">Success</x-ui.badge>
                <x-ui.badge variant="warning">Warning</x-ui.badge>
                <x-ui.badge variant="destructive">Error</x-ui.badge>
                <x-ui.badge variant="info">Info</x-ui.badge>
                <x-ui.badge variant="outline">Outline</x-ui.badge>
                <x-ui.badge size="sm">Small</x-ui.badge>
                <x-ui.badge size="lg">Large</x-ui.badge>
            </div>
        </x-ui.card>

        <!-- Form Components Section -->
        <x-ui.card title="Form Components" class="mb-8">
            <form class="space-y-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <x-forms.input-label for="demo_name" value="Name" />
                        <x-ui.input id="demo_name" name="demo_name" type="text" placeholder="Enter your name" />
                    </div>
                    
                    <div>
                        <x-forms.input-label for="demo_email" value="Email" />
                        <x-ui.input id="demo_email" name="demo_email" type="email" placeholder="Enter your email" />
                    </div>
                </div>
                
                <div>
                    <x-forms.input-label for="demo_message" value="Message" />
                    <textarea 
                        id="demo_message" 
                        name="demo_message" 
                        rows="4"
                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        placeholder="Enter your message"></textarea>
                </div>
                
                <div class="flex gap-4">
                    <x-forms.primary-button>Submit Form</x-forms.primary-button>
                    <x-ui.button variant="outline" type="button">Cancel</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <!-- Cards Section -->
        <div class="grid lg:grid-cols-3 gap-6 mb-8">
            <!-- Metric Card -->
            <x-ui.card variant="metric" title="Total Users" value="1,234" icon="fas fa-users" color="primary">
                <x-slot:subtitle>
                    <span class="text-emerald-600">+12%</span> from last month
                </x-slot:subtitle>
            </x-ui.card>
            
            <!-- Featured Card -->
            <x-ui.card variant="featured" title="Important Notice" class="border-l-emerald-500">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    This is a featured card with emerald accent border and enhanced styling.
                </p>
                <x-slot:actions>
                    <x-ui.button size="sm" variant="outline">Action</x-ui.button>
                </x-slot:actions>
            </x-ui.card>
            
            <!-- Compact Card -->
            <x-ui.card variant="compact" title="Quick Stats">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Active</span>
                        <span class="font-medium">89%</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Pending</span>
                        <span class="font-medium">11%</span>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <!-- Table Section -->
        <x-ui.card title="Data Table" class="mb-8">
            <x-ui.table :hover="true" :striped="true">
                <thead>
                    <tr class="border-b">
                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Name</th>
                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Role</th>
                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Status</th>
                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b transition-colors hover:bg-muted/50">
                        <td class="p-4 align-middle">John Doe</td>
                        <td class="p-4 align-middle">Administrator</td>
                        <td class="p-4 align-middle">
                            <x-ui.badge variant="success">Active</x-ui.badge>
                        </td>
                        <td class="p-4 align-middle">
                            <div class="flex gap-2">
                                <x-ui.button size="sm" variant="outline">Edit</x-ui.button>
                                <x-ui.button size="sm" variant="destructive">Delete</x-ui.button>
                            </div>
                        </td>
                    </tr>
                    <tr class="border-b transition-colors hover:bg-muted/50">
                        <td class="p-4 align-middle">Jane Smith</td>
                        <td class="p-4 align-middle">Manager</td>
                        <td class="p-4 align-middle">
                            <x-ui.badge variant="warning">Pending</x-ui.badge>
                        </td>
                        <td class="p-4 align-middle">
                            <div class="flex gap-2">
                                <x-ui.button size="sm" variant="outline">Edit</x-ui.button>
                                <x-ui.button size="sm" variant="destructive">Delete</x-ui.button>
                            </div>
                        </td>
                    </tr>
                    <tr class="border-b transition-colors hover:bg-muted/50">
                        <td class="p-4 align-middle">Mike Johnson</td>
                        <td class="p-4 align-middle">Employee</td>
                        <td class="p-4 align-middle">
                            <x-ui.badge variant="destructive">Inactive</x-ui.badge>
                        </td>
                        <td class="p-4 align-middle">
                            <div class="flex gap-2">
                                <x-ui.button size="sm" variant="outline">Edit</x-ui.button>
                                <x-ui.button size="sm" variant="destructive">Delete</x-ui.button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </x-ui.table>
        </x-ui.card>

        <!-- Alert Examples -->
        <x-ui.card title="Alert Messages" class="mb-8">
            <div class="space-y-4">
                <x-ui.alerts.success message="This is a success alert using emerald colors" />
                
                <div class="rounded-md bg-red-50 p-4 dark:bg-red-900/20">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800 dark:text-red-200">This is an error alert with consistent styling</p>
                        </div>
                    </div>
                </div>
                
                <div class="rounded-md bg-amber-50 p-4 dark:bg-amber-900/20">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-amber-800 dark:text-amber-200">This is a warning alert with pure Tailwind styling</p>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <!-- Theme Toggle -->
        <x-ui.card title="Theme Controls" class="mb-8">
            <div class="flex items-center gap-4">
                <span class="text-sm font-medium">Theme:</span>
                <x-ui.theme-toggle :show-label="true" />
                <span class="text-xs text-muted-foreground">Toggle between light and dark mode</span>
            </div>
        </x-ui.card>

        <!-- Implementation Guide -->
        <x-ui.card title="Standardization Complete" variant="featured" class="border-l-emerald-500">
            <div class="prose prose-sm max-w-none dark:prose-invert">
                <p>All components have been standardized to use:</p>
                <ul>
                    <li><strong>Pure Tailwind CSS classes</strong> - No custom CSS dependencies</li>
                    <li><strong>Emerald green color scheme</strong> - Consistent with system branding</li>
                    <li><strong>Dark mode support</strong> - Automatic theme detection and switching</li>
                    <li><strong>Accessibility features</strong> - Focus states, ARIA labels, keyboard navigation</li>
                    <li><strong>Mobile-first design</strong> - Responsive and touch-friendly</li>
                </ul>
                <p class="text-emerald-600 dark:text-emerald-400 font-medium">
                    Phase 4 Complete: All components now use standardized Tailwind classes!
                </p>
            </div>
        </x-ui.card>

        <!-- Back to Dashboard -->
        <div class="mt-8 text-center">
            <a href="{{ route('dashboard') }}" class="btn btn-outline">
                ← Back to Dashboard
            </a>
        </div>
    </div>
</div>

<script>
// Test notification integration
document.addEventListener('DOMContentLoaded', function() {
    // Show a welcome notification
    setTimeout(() => {
        toast.success('Component standardization completed successfully!', {
            title: 'Phase 4 Complete',
            duration: 5000,
            progress: true
        });
    }, 1000);
});
</script>
@endsection