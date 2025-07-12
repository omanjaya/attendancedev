@extends('layouts.app')

@section('title', 'Notification System Demo')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                Notification System Demo
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                Test the comprehensive toast notification system with various types and configurations
            </p>
        </div>
        
        <!-- Grid Layout -->
        <div class="grid lg:grid-cols-2 gap-8">
            <!-- Interactive Demo Form -->
            <div class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        Interactive Form Demo
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        This form demonstrates real-world notification usage with loading states, validation, and success feedback.
                    </p>
                    
                    <x-forms.demo-form />
                </div>
            </div>
            
            <!-- Server-side Flash Messages -->
            <div class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        Laravel Flash Messages
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Test server-side flash messages that automatically convert to toast notifications.
                    </p>
                    
                    <form action="{{ route('demo.notifications.test') }}" method="POST" class="space-y-4">
                        @csrf
                        
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Message Type
                            </label>
                            <select name="type" id="type" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white">
                                <option value="success">Success</option>
                                <option value="error">Error</option>
                                <option value="warning">Warning</option>
                                <option value="info">Info</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Message Content
                            </label>
                            <input 
                                type="text" 
                                name="message" 
                                id="message"
                                value="This is a test notification from Laravel session flash!"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-full h-10">
                            Send Flash Message
                        </button>
                    </form>
                </div>
                
                <!-- Direct JavaScript API -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        JavaScript API Demo
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Use the global JavaScript API for instant notifications in your Vue components or vanilla JS.
                    </p>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <button 
                            onclick="toast.success('Operation completed successfully!', { title: 'Great job!', progress: true })"
                            class="btn btn-primary text-sm h-10">
                            Success
                        </button>
                        
                        <button 
                            onclick="toast.error('Something went wrong!', { title: 'Oops!', duration: 7000 })"
                            class="btn btn-destructive text-sm h-10">
                            Error
                        </button>
                        
                        <button 
                            onclick="toast.warning('Please check your input!', { title: 'Warning', progress: true })"
                            class="btn bg-amber-500 text-white hover:bg-amber-600 text-sm h-10">
                            Warning
                        </button>
                        
                        <button 
                            onclick="toast.info('Here is some useful information', { title: 'FYI', duration: 6000 })"
                            class="btn bg-blue-500 text-white hover:bg-blue-600 text-sm h-10">
                            Info
                        </button>
                    </div>
                    
                    <div class="mt-4">
                        <button 
                            onclick="showAdvancedNotification()"
                            class="btn btn-outline w-full h-10">
                            Advanced Notification with Actions
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Code Examples -->
        <div class="mt-12 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                Implementation Examples
            </h2>
            
            <div class="grid lg:grid-cols-2 gap-6">
                <!-- JavaScript Usage -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">JavaScript Usage</h3>
                    <pre class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 text-sm overflow-x-auto"><code>// Simple notifications
toast.success('Success message');
toast.error('Error message');
toast.warning('Warning message');
toast.info('Info message');

// Advanced configuration
showToast({
    type: 'success',
    title: 'Upload Complete',
    message: 'File uploaded successfully',
    duration: 5000,
    progress: true,
    actions: [
        {
            label: 'View File',
            callback: () => console.log('View clicked')
        }
    ]
});</code></pre>
                </div>
                
                <!-- Laravel Usage -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Laravel Usage</h3>
                    <pre class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 text-sm overflow-x-auto"><code>// In controllers
return redirect()
    ->route('dashboard')
    ->with('success', 'Data saved successfully');

return redirect()
    ->back()
    ->with('error', 'Validation failed');

// Direct component usage
&lt;x-ui.notification 
    type="success"
    title="Welcome!"
    message="Thanks for joining us"
    :progress="true" /&gt;</code></pre>
                </div>
            </div>
        </div>
        
        <!-- Back to Dashboard -->
        <div class="mt-8 text-center">
            <a href="{{ route('dashboard') }}" class="btn btn-outline">
                ← Back to Dashboard
            </a>
        </div>
    </div>
</div>

<script>
function showAdvancedNotification() {
    showToast({
        type: 'warning',
        title: 'Confirm Action',
        message: 'Are you sure you want to delete this item? This action cannot be undone.',
        duration: 15000,
        progress: true,
        actions: [
            {
                label: 'Delete',
                style: 'primary',
                callback: () => {
                    toast.success('Item deleted successfully');
                }
            },
            {
                label: 'Cancel',
                style: 'secondary',
                callback: () => {
                    toast.info('Delete cancelled');
                }
            }
        ]
    });
}
</script>
@endsection