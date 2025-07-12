@props([
    'enableThemes' => true,
    'enableLayouts' => true,
    'enableLocalization' => true,
    'enableNotifications' => true,
    'enableDashboard' => true,
    'enableAccessibility' => true,
    'autoSave' => true,
    'syncAcrossDevices' => false
])

<!-- User Preferences Modal -->
<div class="user-preferences-modal" 
     x-data="userPreferences({
        enableThemes: {{ $enableThemes ? 'true' : 'false' }},
        enableLayouts: {{ $enableLayouts ? 'true' : 'false' }},
        enableLocalization: {{ $enableLocalization ? 'true' : 'false' }},
        enableNotifications: {{ $enableNotifications ? 'true' : 'false' }},
        enableDashboard: {{ $enableDashboard ? 'true' : 'false' }},
        enableAccessibility: {{ $enableAccessibility ? 'true' : 'false' }},
        autoSave: {{ $autoSave ? 'true' : 'false' }},
        syncAcrossDevices: {{ $syncAcrossDevices ? 'true' : 'false' }}
     })"
     x-init="init()"
     x-show="isOpen"
     x-cloak>
     
    <!-- Modal Backdrop -->
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[9999]"
         x-show="isOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="close()">
    </div>
    
    <!-- Modal Content -->
    <div class="fixed inset-0 flex items-center justify-center p-4 z-[9999]"
         x-show="isOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        
        <div class="bg-background border border-border rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden"
             @click.stop>
            
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-border">
                <h2 class="text-xl font-semibold">User Preferences</h2>
                <button class="w-6 h-6 flex items-center justify-center text-muted-foreground hover:text-foreground"
                        @click="close()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Content -->
            <div class="flex h-[calc(90vh-120px)]">
                <!-- Sidebar Navigation -->
                <div class="w-64 border-r border-border p-4 space-y-2 overflow-y-auto">
                    <button class="w-full text-left px-3 py-2 rounded-md text-sm transition-colors"
                            :class="activeTab === 'appearance' ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'"
                            @click="activeTab = 'appearance'">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM7 21h10a2 2 0 002-2v-4a2 2 0 00-2-2H7M7 21V9a2 2 0 012-2h8a2 2 0 012 2v4"/>
                            </svg>
                            <span>Appearance</span>
                        </div>
                    </button>
                    
                    @if($enableLayouts)
                    <button class="w-full text-left px-3 py-2 rounded-md text-sm transition-colors"
                            :class="activeTab === 'layout' ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'"
                            @click="activeTab = 'layout'">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                            </svg>
                            <span>Layout</span>
                        </div>
                    </button>
                    @endif
                    
                    @if($enableNotifications)
                    <button class="w-full text-left px-3 py-2 rounded-md text-sm transition-colors"
                            :class="activeTab === 'notifications' ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'"
                            @click="activeTab = 'notifications'">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5-5V9a6 6 0 00-12 0v3l-5 5h5m7 0v1a3 3 0 01-6 0v-1m6 0H9"/>
                            </svg>
                            <span>Notifications</span>
                        </div>
                    </button>
                    @endif
                    
                    @if($enableDashboard)
                    <button class="w-full text-left px-3 py-2 rounded-md text-sm transition-colors"
                            :class="activeTab === 'dashboard' ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'"
                            @click="activeTab = 'dashboard'">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <span>Dashboard</span>
                        </div>
                    </button>
                    @endif
                    
                    @if($enableLocalization)
                    <button class="w-full text-left px-3 py-2 rounded-md text-sm transition-colors"
                            :class="activeTab === 'localization' ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'"
                            @click="activeTab = 'localization'">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                            </svg>
                            <span>Language & Region</span>
                        </div>
                    </button>
                    @endif
                    
                    @if($enableAccessibility)
                    <button class="w-full text-left px-3 py-2 rounded-md text-sm transition-colors"
                            :class="activeTab === 'accessibility' ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'"
                            @click="activeTab = 'accessibility'">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                            </svg>
                            <span>Accessibility</span>
                        </div>
                    </button>
                    @endif
                </div>
                
                <!-- Main Content -->
                <div class="flex-1 p-6 overflow-y-auto">
                    <!-- Appearance Tab -->
                    <div x-show="activeTab === 'appearance'" class="space-y-6">
                        <div>
                            <h3 class="text-lg font-medium mb-4">Appearance & Theme</h3>
                            
                            <!-- Theme Selection -->
                            @if($enableThemes)
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium mb-3 block">Color Theme</label>
                                    <div class="grid grid-cols-3 gap-3">
                                        <template x-for="theme in availableThemes" :key="theme.id">
                                            <button class="p-3 border-2 rounded-lg transition-all hover:scale-105"
                                                    :class="preferences.theme === theme.id ? 'border-primary ring-2 ring-primary/20' : 'border-border'"
                                                    @click="updatePreference('theme', theme.id)">
                                                <div class="w-full h-12 rounded mb-2 flex overflow-hidden">
                                                    <div class="flex-1" :style="`background-color: ${theme.preview.primary}`"></div>
                                                    <div class="flex-1" :style="`background-color: ${theme.preview.secondary}`"></div>
                                                    <div class="flex-1" :style="`background-color: ${theme.preview.accent}`"></div>
                                                </div>
                                                <span class="text-xs font-medium" x-text="theme.name"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                
                                <!-- Dark Mode Toggle -->
                                <div class="flex items-center justify-between p-4 border border-border rounded-lg">
                                    <div>
                                        <label class="text-sm font-medium">Dark Mode</label>
                                        <p class="text-xs text-muted-foreground">Enable dark theme across the application</p>
                                    </div>
                                    <button class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors
                                                   focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                            :class="preferences.darkMode ? 'bg-primary' : 'bg-muted'"
                                            @click="updatePreference('darkMode', !preferences.darkMode)">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-background transition-transform"
                                              :class="preferences.darkMode ? 'translate-x-6' : 'translate-x-1'">
                                        </span>
                                    </button>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Layout Tab -->
                    @if($enableLayouts)
                    <div x-show="activeTab === 'layout'" class="space-y-6">
                        <div>
                            <h3 class="text-lg font-medium mb-4">Layout & Navigation</h3>
                            
                            <div class="space-y-4">
                                <!-- Sidebar Position -->
                                <div>
                                    <label class="text-sm font-medium mb-3 block">Sidebar Position</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <button class="p-3 border-2 rounded-lg text-center transition-all"
                                                :class="preferences.sidebarPosition === 'left' ? 'border-primary bg-primary/5' : 'border-border'"
                                                @click="updatePreference('sidebarPosition', 'left')">
                                            Left
                                        </button>
                                        <button class="p-3 border-2 rounded-lg text-center transition-all"
                                                :class="preferences.sidebarPosition === 'right' ? 'border-primary bg-primary/5' : 'border-border'"
                                                @click="updatePreference('sidebarPosition', 'right')">
                                            Right
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Sidebar Collapsed -->
                                <div class="flex items-center justify-between p-4 border border-border rounded-lg">
                                    <div>
                                        <label class="text-sm font-medium">Collapsed Sidebar</label>
                                        <p class="text-xs text-muted-foreground">Start with sidebar collapsed</p>
                                    </div>
                                    <button class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                            :class="preferences.sidebarCollapsed ? 'bg-primary' : 'bg-muted'"
                                            @click="updatePreference('sidebarCollapsed', !preferences.sidebarCollapsed)">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-background transition-transform"
                                              :class="preferences.sidebarCollapsed ? 'translate-x-6' : 'translate-x-1'">
                                        </span>
                                    </button>
                                </div>
                                
                                <!-- Density -->
                                <div>
                                    <label class="text-sm font-medium mb-3 block">Interface Density</label>
                                    <div class="grid grid-cols-3 gap-2">
                                        <button class="p-2 border-2 rounded text-xs transition-all"
                                                :class="preferences.density === 'compact' ? 'border-primary bg-primary/5' : 'border-border'"
                                                @click="updatePreference('density', 'compact')">
                                            Compact
                                        </button>
                                        <button class="p-2 border-2 rounded text-xs transition-all"
                                                :class="preferences.density === 'normal' ? 'border-primary bg-primary/5' : 'border-border'"
                                                @click="updatePreference('density', 'normal')">
                                            Normal
                                        </button>
                                        <button class="p-2 border-2 rounded text-xs transition-all"
                                                :class="preferences.density === 'comfortable' ? 'border-primary bg-primary/5' : 'border-border'"
                                                @click="updatePreference('density', 'comfortable')">
                                            Comfortable
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <!-- Notifications Tab -->
                    @if($enableNotifications)
                    <div x-show="activeTab === 'notifications'" class="space-y-6">
                        <div>
                            <h3 class="text-lg font-medium mb-4">Notifications</h3>
                            
                            <div class="space-y-4">
                                <template x-for="setting in notificationSettings" :key="setting.id">
                                    <div class="flex items-center justify-between p-4 border border-border rounded-lg">
                                        <div>
                                            <label class="text-sm font-medium" x-text="setting.label"></label>
                                            <p class="text-xs text-muted-foreground" x-text="setting.description"></p>
                                        </div>
                                        <button class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                                :class="preferences.notifications[setting.id] ? 'bg-primary' : 'bg-muted'"
                                                @click="updateNotificationPreference(setting.id, !preferences.notifications[setting.id])">
                                            <span class="inline-block h-4 w-4 transform rounded-full bg-background transition-transform"
                                                  :class="preferences.notifications[setting.id] ? 'translate-x-6' : 'translate-x-1'">
                                            </span>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <!-- Dashboard Tab -->
                    @if($enableDashboard)
                    <div x-show="activeTab === 'dashboard'" class="space-y-6">
                        <div>
                            <h3 class="text-lg font-medium mb-4">Dashboard Customization</h3>
                            
                            <div class="space-y-4">
                                <!-- Default Dashboard -->
                                <div>
                                    <label class="text-sm font-medium mb-3 block">Default Dashboard</label>
                                    <select class="w-full p-2 border border-border rounded-md bg-background"
                                            x-model="preferences.defaultDashboard"
                                            @change="updatePreference('defaultDashboard', $event.target.value)">
                                        <option value="overview">Overview Dashboard</option>
                                        <option value="attendance">Attendance Dashboard</option>
                                        <option value="performance">Performance Dashboard</option>
                                        <option value="analytics">Analytics Dashboard</option>
                                    </select>
                                </div>
                                
                                <!-- Widget Preferences -->
                                <div>
                                    <label class="text-sm font-medium mb-3 block">Visible Widgets</label>
                                    <div class="space-y-2">
                                        <template x-for="widget in dashboardWidgets" :key="widget.id">
                                            <div class="flex items-center space-x-3">
                                                <input type="checkbox" 
                                                       :id="`widget-${widget.id}`"
                                                       :checked="preferences.widgets[widget.id]"
                                                       @change="updateWidgetPreference(widget.id, $event.target.checked)"
                                                       class="rounded border-border">
                                                <label :for="`widget-${widget.id}`" class="text-sm" x-text="widget.name"></label>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Footer -->
            <div class="flex items-center justify-between p-6 border-t border-border">
                <div class="text-sm text-muted-foreground">
                    <span x-show="hasUnsavedChanges">Unsaved changes</span>
                    <span x-show="!hasUnsavedChanges">All changes saved</span>
                </div>
                
                <div class="flex space-x-3">
                    <button class="px-4 py-2 text-sm border border-border rounded-md hover:bg-muted"
                            @click="resetToDefaults()">
                        Reset to Defaults
                    </button>
                    <button class="px-4 py-2 text-sm bg-primary text-primary-foreground rounded-md hover:bg-primary/90"
                            @click="savePreferences()">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function userPreferences(config) {
    return {
        enableThemes: config.enableThemes,
        enableLayouts: config.enableLayouts,
        enableLocalization: config.enableLocalization,
        enableNotifications: config.enableNotifications,
        enableDashboard: config.enableDashboard,
        enableAccessibility: config.enableAccessibility,
        autoSave: config.autoSave,
        syncAcrossDevices: config.syncAcrossDevices,
        
        isOpen: false,
        activeTab: 'appearance',
        hasUnsavedChanges: false,
        
        preferences: {
            theme: 'default',
            darkMode: false,
            sidebarPosition: 'left',
            sidebarCollapsed: false,
            density: 'normal',
            defaultDashboard: 'overview',
            language: 'en',
            timezone: 'UTC',
            notifications: {
                attendance: true,
                leaves: true,
                schedules: true,
                reports: false,
                system: true
            },
            widgets: {
                attendance_summary: true,
                recent_activity: true,
                quick_stats: true,
                schedule_overview: true,
                leave_calendar: false
            }
        },
        
        availableThemes: [
            {
                id: 'default',
                name: 'Default',
                preview: { primary: '#3b82f6', secondary: '#f1f5f9', accent: '#10b981' }
            },
            {
                id: 'purple',
                name: 'Purple',
                preview: { primary: '#8b5cf6', secondary: '#f3f4f6', accent: '#06b6d4' }
            },
            {
                id: 'green',
                name: 'Green',
                preview: { primary: '#10b981', secondary: '#f0fdf4', accent: '#f59e0b' }
            }
        ],
        
        notificationSettings: [
            {
                id: 'attendance',
                label: 'Attendance Notifications',
                description: 'Check-in/out reminders and attendance alerts'
            },
            {
                id: 'leaves',
                label: 'Leave Notifications',
                description: 'Leave approvals and balance updates'
            },
            {
                id: 'schedules',
                label: 'Schedule Changes',
                description: 'Updates to your work schedule'
            },
            {
                id: 'reports',
                label: 'Report Notifications',
                description: 'Monthly reports and analytics updates'
            },
            {
                id: 'system',
                label: 'System Notifications',
                description: 'Important system updates and maintenance'
            }
        ],
        
        dashboardWidgets: [
            { id: 'attendance_summary', name: 'Attendance Summary' },
            { id: 'recent_activity', name: 'Recent Activity' },
            { id: 'quick_stats', name: 'Quick Statistics' },
            { id: 'schedule_overview', name: 'Schedule Overview' },
            { id: 'leave_calendar', name: 'Leave Calendar' }
        ],
        
        init() {
            this.loadPreferences();
            this.setupEventListeners();
            
            if (this.autoSave) {
                this.setupAutoSave();
            }
        },
        
        setupEventListeners() {
            // Listen for preference open events
            window.addEventListener('open-preferences', () => {
                this.open();
            });
            
            // Close on escape
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && this.isOpen) {
                    this.close();
                }
            });
        },
        
        setupAutoSave() {
            this.$watch('preferences', () => {
                this.hasUnsavedChanges = true;
                
                // Debounced auto-save
                clearTimeout(this.autoSaveTimer);
                this.autoSaveTimer = setTimeout(() => {
                    this.savePreferences();
                }, 2000);
            }, { deep: true });
        },
        
        open() {
            this.isOpen = true;
            this.loadPreferences();
        },
        
        close() {
            if (this.hasUnsavedChanges && !this.autoSave) {
                if (confirm('You have unsaved changes. Are you sure you want to close?')) {
                    this.isOpen = false;
                }
            } else {
                this.isOpen = false;
            }
        },
        
        loadPreferences() {
            try {
                const saved = localStorage.getItem('user-preferences');
                if (saved) {
                    const parsed = JSON.parse(saved);
                    this.preferences = { ...this.preferences, ...parsed };
                }
                this.applyPreferences();
            } catch (error) {
                console.warn('Failed to load user preferences:', error);
            }
        },
        
        savePreferences() {
            try {
                localStorage.setItem('user-preferences', JSON.stringify(this.preferences));
                this.hasUnsavedChanges = false;
                this.applyPreferences();
                
                // Sync across devices if enabled
                if (this.syncAcrossDevices) {
                    this.syncPreferences();
                }
                
                // Show success notification
                window.notify?.success('Preferences saved successfully');
            } catch (error) {
                console.error('Failed to save preferences:', error);
                window.notify?.error('Failed to save preferences');
            }
        },
        
        applyPreferences() {
            // Apply theme
            this.applyTheme();
            
            // Apply layout preferences
            this.applyLayout();
            
            // Apply density
            this.applyDensity();
            
            // Dispatch preferences changed event
            window.dispatchEvent(new CustomEvent('preferences-changed', {
                detail: this.preferences
            }));
        },
        
        applyTheme() {
            const root = document.documentElement;
            
            // Apply dark mode
            if (this.preferences.darkMode) {
                root.classList.add('dark');
            } else {
                root.classList.remove('dark');
            }
            
            // Apply color theme
            root.setAttribute('data-theme', this.preferences.theme);
        },
        
        applyLayout() {
            const root = document.documentElement;
            
            // Apply sidebar position
            root.setAttribute('data-sidebar-position', this.preferences.sidebarPosition);
            
            // Apply sidebar collapsed state
            if (this.preferences.sidebarCollapsed) {
                root.classList.add('sidebar-collapsed');
            } else {
                root.classList.remove('sidebar-collapsed');
            }
        },
        
        applyDensity() {
            const root = document.documentElement;
            
            // Remove existing density classes
            root.classList.remove('density-compact', 'density-normal', 'density-comfortable');
            
            // Apply new density
            root.classList.add(`density-${this.preferences.density}`);
        },
        
        updatePreference(key, value) {
            this.preferences[key] = value;
            this.hasUnsavedChanges = true;
        },
        
        updateNotificationPreference(key, value) {
            this.preferences.notifications[key] = value;
            this.hasUnsavedChanges = true;
        },
        
        updateWidgetPreference(key, value) {
            this.preferences.widgets[key] = value;
            this.hasUnsavedChanges = true;
        },
        
        resetToDefaults() {
            if (confirm('Are you sure you want to reset all preferences to defaults?')) {
                this.preferences = {
                    theme: 'default',
                    darkMode: false,
                    sidebarPosition: 'left',
                    sidebarCollapsed: false,
                    density: 'normal',
                    defaultDashboard: 'overview',
                    language: 'en',
                    timezone: 'UTC',
                    notifications: {
                        attendance: true,
                        leaves: true,
                        schedules: true,
                        reports: false,
                        system: true
                    },
                    widgets: {
                        attendance_summary: true,
                        recent_activity: true,
                        quick_stats: true,
                        schedule_overview: true,
                        leave_calendar: false
                    }
                };
                
                this.hasUnsavedChanges = true;
                this.savePreferences();
            }
        },
        
        async syncPreferences() {
            try {
                await fetch('/api/user/preferences', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(this.preferences)
                });
            } catch (error) {
                console.warn('Failed to sync preferences:', error);
            }
        }
    };
}

// Global preferences API
window.userPreferences = {
    open: () => window.dispatchEvent(new CustomEvent('open-preferences')),
    
    get: (key) => {
        try {
            const saved = localStorage.getItem('user-preferences');
            if (saved) {
                const preferences = JSON.parse(saved);
                return key ? preferences[key] : preferences;
            }
        } catch (error) {
            console.warn('Failed to get preference:', error);
        }
        return null;
    },
    
    set: (key, value) => {
        try {
            const saved = localStorage.getItem('user-preferences');
            const preferences = saved ? JSON.parse(saved) : {};
            preferences[key] = value;
            localStorage.setItem('user-preferences', JSON.stringify(preferences));
            
            // Trigger preferences changed event
            window.dispatchEvent(new CustomEvent('preferences-changed', {
                detail: preferences
            }));
            
            return true;
        } catch (error) {
            console.error('Failed to set preference:', error);
            return false;
        }
    }
};
</script>

<style>
/* Theme-specific styles */
[data-theme="purple"] {
    --primary: 139 92 246;
    --primary-foreground: 255 255 255;
}

[data-theme="green"] {
    --primary: 16 185 129;
    --primary-foreground: 255 255 255;
}

/* Density styles */
.density-compact {
    --spacing-unit: 0.75rem;
    --line-height: 1.4;
}

.density-comfortable {
    --spacing-unit: 1.25rem;
    --line-height: 1.7;
}

.density-normal {
    --spacing-unit: 1rem;
    --line-height: 1.5;
}

/* Sidebar position styles */
[data-sidebar-position="right"] .sidebar {
    order: 2;
}

[data-sidebar-position="right"] .main-content {
    order: 1;
}

.sidebar-collapsed .sidebar {
    width: 4rem;
}

/* Modal styles */
[x-cloak] {
    display: none !important;
}

/* Smooth transitions for preference changes */
* {
    transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
}

/* High contrast support for themes */
@media (prefers-contrast: high) {
    .border-border {
        border-width: 2px;
    }
}

/* Print styles */
@media print {
    .user-preferences-modal {
        display: none !important;
    }
}
</style>