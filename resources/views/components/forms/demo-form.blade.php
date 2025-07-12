@props([
    'action' => '#',
    'method' => 'POST',
    'title' => 'Demo Form with Notifications'
])

<div class="max-w-md mx-auto">
    <form x-data="demoForm()" @submit.prevent="submitForm" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ $title }}</h3>
        
        <!-- Name Field -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Name
            </label>
            <input 
                type="text" 
                id="name"
                x-model="form.name"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white"
                placeholder="Enter your name">
        </div>
        
        <!-- Email Field -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Email
            </label>
            <input 
                type="email" 
                id="email"
                x-model="form.email"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white"
                placeholder="Enter your email">
        </div>
        
        <!-- Message Field -->
        <div>
            <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Message
            </label>
            <textarea 
                id="message"
                x-model="form.message"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white"
                placeholder="Enter your message"></textarea>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex space-x-3 pt-4">
            <button 
                type="submit"
                :disabled="loading"
                class="flex-1 btn btn-primary h-10"
                :class="{ 'opacity-50 cursor-not-allowed': loading }">
                <span x-show="!loading">Submit Form</span>
                <span x-show="loading" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Processing...
                </span>
            </button>
            
            <button 
                type="button"
                @click="showDemoNotifications"
                class="flex-1 btn btn-outline h-10">
                Demo Notifications
            </button>
        </div>
        
        <!-- Notification Trigger Buttons -->
        <div class="grid grid-cols-2 gap-2 pt-4 border-t border-gray-200 dark:border-gray-600">
            <button 
                type="button"
                @click="toast.success('This is a success message!')"
                class="btn btn-primary text-xs h-8">
                Success Toast
            </button>
            
            <button 
                type="button"
                @click="toast.error('This is an error message!')"
                class="btn btn-destructive text-xs h-8">
                Error Toast
            </button>
            
            <button 
                type="button"
                @click="toast.warning('This is a warning message!')"
                class="btn bg-amber-500 text-white hover:bg-amber-600 text-xs h-8">
                Warning Toast
            </button>
            
            <button 
                type="button"
                @click="toast.info('This is an info message!')"
                class="btn bg-blue-500 text-white hover:bg-blue-600 text-xs h-8">
                Info Toast
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('demoForm', () => ({
        loading: false,
        form: {
            name: '',
            email: '',
            message: ''
        },
        
        async submitForm() {
            if (!this.validateForm()) return;
            
            this.loading = true;
            
            // Show loading notification
            const loadingToast = showToast({
                type: 'info',
                title: 'Processing',
                message: 'Submitting your form data...',
                duration: 0,
                progress: false,
                dismissible: false
            });
            
            try {
                // Simulate API call
                await new Promise(resolve => setTimeout(resolve, 2000));
                
                // Dismiss loading toast
                if (loadingToast && typeof loadingToast.dismiss === 'function') {
                    loadingToast.dismiss();
                }
                
                // Show success notification
                toast.success('Form submitted successfully!', {
                    title: 'Success',
                    duration: 5000,
                    progress: true
                });
                
                // Reset form
                this.form = { name: '', email: '', message: '' };
                
            } catch (error) {
                // Dismiss loading toast
                if (loadingToast && typeof loadingToast.dismiss === 'function') {
                    loadingToast.dismiss();
                }
                
                // Show error notification
                toast.error('Failed to submit form. Please try again.', {
                    title: 'Submission Failed',
                    duration: 7000
                });
            } finally {
                this.loading = false;
            }
        },
        
        validateForm() {
            if (!this.form.name.trim()) {
                toast.warning('Please enter your name', {
                    title: 'Validation Error'
                });
                return false;
            }
            
            if (!this.form.email.trim()) {
                toast.warning('Please enter your email', {
                    title: 'Validation Error'
                });
                return false;
            }
            
            if (!this.isValidEmail(this.form.email)) {
                toast.error('Please enter a valid email address', {
                    title: 'Invalid Email'
                });
                return false;
            }
            
            return true;
        },
        
        isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },
        
        showDemoNotifications() {
            // Show multiple notification types with different configurations
            setTimeout(() => {
                toast.success('File uploaded successfully!', {
                    title: 'Upload Complete',
                    progress: true,
                    duration: 4000
                });
            }, 500);
            
            setTimeout(() => {
                showToast({
                    type: 'warning',
                    title: 'Session Expiry Warning',
                    message: 'Your session will expire in 5 minutes. Click to extend.',
                    duration: 10000,
                    progress: true,
                    actions: [
                        {
                            label: 'Extend Session',
                            style: 'primary',
                            callback: () => {
                                toast.success('Session extended successfully!');
                            }
                        },
                        {
                            label: 'Logout',
                            style: 'secondary',
                            callback: () => {
                                toast.info('Logged out successfully');
                            }
                        }
                    ]
                });
            }, 1000);
            
            setTimeout(() => {
                showToast({
                    type: 'error',
                    title: 'Network Error',
                    message: 'Connection lost. Retrying automatically...',
                    duration: 6000,
                    progress: true,
                    actions: [
                        {
                            label: 'Retry Now',
                            style: 'primary',
                            callback: () => {
                                toast.info('Reconnecting...', { duration: 2000 });
                            }
                        }
                    ]
                });
            }, 1500);
        }
    }));
});
</script>