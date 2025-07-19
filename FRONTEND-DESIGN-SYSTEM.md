# AttendanceHub Frontend Design System

## Overview

The AttendanceHub Design System provides a comprehensive set of components, patterns, and guidelines for building consistent, accessible, and maintainable user interfaces. This system ensures uniformity across all views and enhances the user experience through predictable interactions and visual consistency.

## 🎨 Design Principles

### 1. Consistency
- **Visual Consistency**: Unified color palette, typography, and spacing
- **Behavioral Consistency**: Predictable interactions and feedback patterns
- **Code Consistency**: Standardized component APIs and naming conventions

### 2. Accessibility
- **WCAG 2.1 AA Compliance**: All components meet accessibility standards
- **Keyboard Navigation**: Full keyboard support for all interactive elements
- **Screen Reader Support**: Proper ARIA labels and semantic HTML

### 3. Responsive Design
- **Mobile-First**: Designed for mobile devices, enhanced for desktop
- **Touch-Friendly**: 44px minimum touch targets on mobile devices
- **Adaptive**: Components adapt to different screen sizes and orientations

### 4. Performance
- **Optimized Loading**: Minimal CSS and JavaScript footprint
- **Progressive Enhancement**: Works without JavaScript, enhanced with it
- **Lazy Loading**: Components load only when needed

## 🏗️ Architecture

### Component Organization

```
resources/views/components/
├── layouts/           # Page layout templates
├── ui/               # Basic UI components
├── forms/            # Form-specific components
├── navigation/       # Navigation patterns
└── patterns/         # Complex UI patterns
```

### CSS Architecture

```css
/* Layer Structure */
@layer base;          /* Reset and base styles */
@layer components;    /* Component styles */
@layer utilities;     /* Utility classes */

/* Design System Import */
@import './design-system.css';
```

## 🎯 Core Components

### Layout Components

#### Base Page Layout
```blade
<x-layouts.base-page 
    title="Page Title"
    subtitle="Optional subtitle"
    :breadcrumbs="[
        ['label' => 'Home', 'url' => '/'],
        ['label' => 'Current Page']
    ]">
    <x-slot name="actions">
        <x-ui.button>Action</x-ui.button>
    </x-slot>
    
    <!-- Page content -->
</x-layouts.base-page>
```

#### Authenticated Layout
```blade
@extends('layouts.authenticated-clean')

@section('main-content')
    <!-- Your page content -->
@endsection
```

### UI Components

#### Button
```blade
<x-ui.button 
    variant="primary"    <!-- primary, secondary, destructive, outline, ghost, link -->
    size="md"           <!-- sm, md, lg, icon -->
    :loading="false"
    :disabled="false">
    Button Text
</x-ui.button>
```

#### Card
```blade
<x-ui.card 
    variant="default"   <!-- default, metric, simple, compact, featured, interactive -->
    title="Card Title"
    subtitle="Card subtitle">
    Card content goes here
</x-ui.card>
```

#### Form Field
```blade
<x-ui.form-field 
    name="email"
    label="Email Address"
    type="email"
    :required="true"
    :errors="$errors"
    help-text="We'll never share your email">
    <!-- Custom input can go in slot -->
</x-ui.form-field>
```

#### Data Table
```blade
<x-ui.data-table 
    :columns="[
        ['key' => 'name', 'label' => 'Name', 'sortable' => true],
        ['key' => 'email', 'label' => 'Email'],
        ['key' => 'created_at', 'label' => 'Created', 'format' => fn($date) => $date->format('M j, Y')]
    ]"
    :data="$users"
    :searchable="true"
    :paginated="true">
</x-ui.data-table>
```

### Form Components

#### Form Layout
```blade
<x-forms.form-layout 
    title="User Registration"
    method="POST"
    action="{{ route('users.store') }}"
    :autosave="true"
    :sections="[
        ['title' => 'Personal Info', 'content' => $personalInfoFields],
        ['title' => 'Account Details', 'content' => $accountFields]
    ]">
    
    <!-- Form fields -->
    
</x-forms.form-layout>
```

### Navigation Components

#### Sidebar Navigation
```blade
<x-navigation.nav-patterns 
    variant="sidebar"
    :items="[
        [
            'label' => 'Dashboard', 
            'path' => '/dashboard', 
            'icon' => '<svg>...</svg>'
        ],
        [
            'label' => 'Users',
            'children' => [
                ['label' => 'All Users', 'path' => '/users'],
                ['label' => 'Add User', 'path' => '/users/create']
            ]
        ]
    ]">
</x-navigation.nav-patterns>
```

#### Tab Navigation
```blade
<x-navigation.nav-patterns 
    variant="tabs"
    :items="[
        ['label' => 'Overview', 'path' => '/profile'],
        ['label' => 'Settings', 'path' => '/profile/settings'],
        ['label' => 'Security', 'path' => '/profile/security']
    ]">
</x-navigation.nav-patterns>
```

## 🎨 Design Tokens

### Color System

```css
:root {
  /* Primary Colors */
  --primary: 142 86 89%;          /* Emerald-500 */
  --primary-foreground: 98 98 98%; /* White */
  
  /* Semantic Colors */
  --success: 142 86 89%;           /* Green-500 */
  --warning: 48 100 59%;          /* Amber-500 */
  --destructive: 0 84% 55%;       /* Red-500 */
  --info: 217 100% 59%;           /* Blue-500 */
  
  /* Neutral Colors */
  --background: 0 0% 100%;        /* White */
  --foreground: 240 10% 4%;       /* Gray-950 */
  --muted: 240 5% 96%;           /* Gray-50 */
  --muted-foreground: 240 4% 46%; /* Gray-500 */
  
  /* Interactive States */
  --accent: 240 5% 96%;          /* Gray-50 */
  --accent-foreground: 240 10% 4%; /* Gray-950 */
  --border: 240 6% 90%;          /* Gray-200 */
  --input: 240 6% 90%;           /* Gray-200 */
  --ring: 142 86% 89%;           /* Emerald-500 */
}
```

### Typography Scale

```css
:root {
  --text-xs: 0.75rem;    /* 12px */
  --text-sm: 0.875rem;   /* 14px */
  --text-base: 1rem;     /* 16px */
  --text-lg: 1.125rem;   /* 18px */
  --text-xl: 1.25rem;    /* 20px */
  --text-2xl: 1.5rem;    /* 24px */
  --text-3xl: 1.875rem;  /* 30px */
  --text-4xl: 2.25rem;   /* 36px */
}
```

### Spacing Scale

```css
:root {
  --space-xs: 0.25rem;   /* 4px */
  --space-sm: 0.5rem;    /* 8px */
  --space-md: 1rem;      /* 16px */
  --space-lg: 1.5rem;    /* 24px */
  --space-xl: 2rem;      /* 32px */
  --space-2xl: 3rem;     /* 48px */
  --space-3xl: 4rem;     /* 64px */
}
```

## 📱 Responsive Breakpoints

```css
/* Mobile First Breakpoints */
@media (min-width: 640px) { /* sm */ }
@media (min-width: 768px) { /* md */ }
@media (min-width: 1024px) { /* lg */ }
@media (min-width: 1280px) { /* xl */ }
@media (min-width: 1536px) { /* 2xl */ }
```

### Responsive Utilities

```blade
<!-- Responsive text sizing -->
<h1 class="text-responsive-xl">Responsive Heading</h1>

<!-- Responsive spacing -->
<div class="space-responsive-md">
    <!-- Content with responsive spacing -->
</div>

<!-- Touch targets -->
<button class="touch-target">Mobile-friendly button</button>
```

## ♿ Accessibility Guidelines

### Keyboard Navigation
- **Tab Order**: Logical tab sequence through interactive elements
- **Focus Indicators**: Visible focus rings on all interactive elements
- **Keyboard Shortcuts**: Support for common shortcuts (Ctrl+K for search)

### Screen Reader Support
```blade
<!-- Proper labeling -->
<x-ui.form-field 
    label="Email Address"
    aria-describedby="email-help"
    required>
    <input type="email" name="email" />
</x-ui.form-field>
<div id="email-help">We'll never share your email</div>

<!-- Live regions for dynamic content -->
<div aria-live="polite" aria-atomic="true">
    Status updates appear here
</div>
```

### Color Contrast
- **Text**: Minimum 4.5:1 contrast ratio
- **Interactive Elements**: Minimum 3:1 contrast ratio
- **Focus Indicators**: Minimum 3:1 contrast with adjacent colors

## 🎭 Animation & Transitions

### Standard Durations
```css
:root {
  --duration-fast: 150ms;    /* Quick feedback */
  --duration-normal: 300ms;  /* Standard transitions */
  --duration-slow: 500ms;    /* Complex animations */
}
```

### Common Patterns
```blade
<!-- Hover effects -->
<div class="hover-lift">Content that lifts on hover</div>
<div class="hover-scale">Content that scales on hover</div>

<!-- Loading states -->
<div class="loading">Content with pulse animation</div>
<div class="loading-skeleton">Skeleton placeholder</div>

<!-- Smooth transitions -->
<div class="transition-smooth">Smooth transition on all properties</div>
```

## 🔧 Error Handling

### Error Boundary
```blade
<x-ui.error-boundary 
    :error="$exception"
    title="Something went wrong"
    :reportable="true">
    <!-- Content that might error -->
    @include('risky-component')
</x-ui.error-boundary>
```

### Validation Errors
```blade
<!-- Automatic error display -->
<x-ui.validation-errors :errors="$errors" />

<!-- Form field with error state -->
<x-ui.form-field 
    name="email"
    label="Email"
    :errors="$errors">
</x-ui.form-field>
```

### User Feedback
```blade
<!-- Success notification -->
<x-ui.notification 
    type="success"
    title="Success!"
    message="Your changes have been saved."
    :dismissible="true">
</x-ui.notification>

<!-- Error alert -->
<x-ui.alert variant="destructive">
    <x-slot name="title">Error</x-slot>
    Something went wrong. Please try again.
</x-ui.alert>
```

## 🧪 Testing Components

### Visual Testing
```bash
# Run visual regression tests
npm run test:visual

# Update visual baselines
npm run test:visual:update
```

### Accessibility Testing
```bash
# Run accessibility audits
npm run test:a11y

# Generate accessibility report
npm run report:a11y
```

### Component Testing
```javascript
// Example component test
import { render, screen } from '@testing-library/vue'
import Button from '@/components/ui/Button.vue'

test('button renders with correct variant', () => {
    render(Button, {
        props: { variant: 'primary' },
        slots: { default: 'Click me' }
    })
    
    expect(screen.getByRole('button')).toHaveClass('btn--primary')
})
```

## 📚 Usage Examples

### Dashboard Page
```blade
@extends('layouts.authenticated-clean')

@section('main-content')
<x-layouts.base-page 
    title="Dashboard"
    subtitle="Overview of your attendance system">
    
    <!-- Stats Grid -->
    <div class="grid grid--cols-4 mb-8">
        <x-ui.card variant="metric" 
                  title="Total Employees" 
                  value="{{ $stats['employees'] }}"
                  icon="users">
        </x-ui.card>
        
        <x-ui.card variant="metric" 
                  title="Present Today" 
                  value="{{ $stats['present'] }}"
                  icon="check-circle">
        </x-ui.card>
        
        <x-ui.card variant="metric" 
                  title="Late Arrivals" 
                  value="{{ $stats['late'] }}"
                  icon="clock">
        </x-ui.card>
        
        <x-ui.card variant="metric" 
                  title="Absent" 
                  value="{{ $stats['absent'] }}"
                  icon="x-circle">
        </x-ui.card>
    </div>
    
    <!-- Recent Activity -->
    <x-ui.card title="Recent Activity">
        <x-ui.data-table 
            :columns="$activityColumns"
            :data="$recentActivity"
            :searchable="false"
            :paginated="false">
        </x-ui.data-table>
    </x-ui.card>
</x-layouts.base-page>
@endsection
```

### Form Page
```blade
@extends('layouts.authenticated-clean')

@section('main-content')
<x-layouts.base-page 
    title="Add Employee"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Employees', 'url' => route('employees.index')],
        ['label' => 'Add Employee']
    ]">
    
    <x-forms.form-layout 
        method="POST"
        action="{{ route('employees.store') }}"
        enctype="multipart/form-data"
        :autosave="true">
        
        <div class="grid grid--cols-2">
            <x-ui.form-field 
                name="first_name"
                label="First Name"
                :required="true"
                :errors="$errors">
            </x-ui.form-field>
            
            <x-ui.form-field 
                name="last_name"
                label="Last Name"
                :required="true"
                :errors="$errors">
            </x-ui.form-field>
        </div>
        
        <x-ui.form-field 
            name="email"
            label="Email Address"
            type="email"
            :required="true"
            :errors="$errors"
            help-text="This will be used for login">
        </x-ui.form-field>
        
        <x-ui.form-field 
            name="photo"
            label="Profile Photo"
            type="file"
            :errors="$errors">
            <x-ui.input type="file" 
                       name="photo" 
                       accept="image/*"
                       class="file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-primary-foreground hover:file:bg-primary/80">
        </x-ui.form-field>
        
    </x-forms.form-layout>
</x-layouts.base-page>
@endsection
```

## 🚀 Performance Optimizations

### Bundle Optimization
- **Code Splitting**: Components loaded only when needed
- **Tree Shaking**: Unused CSS and JavaScript removed
- **Critical CSS**: Above-the-fold styles inlined

### Lazy Loading
```blade
<!-- Lazy load heavy components -->
<div x-intersect="$el.innerHTML = '<x-heavy-component />'" class="min-h-[200px]">
    <!-- Placeholder content -->
</div>
```

### Caching Strategies
- **Component Caching**: Frequently used components cached
- **Asset Versioning**: Automatic cache busting for updates
- **Service Worker**: Offline-first approach for PWA

## 🔧 Development Workflow

### Component Development
1. **Create Component**: Start with basic functionality
2. **Add Props**: Define comprehensive prop interface
3. **Style**: Apply design system classes
4. **Test**: Write unit and accessibility tests
5. **Document**: Add usage examples

### Testing Checklist
- [ ] Visual appearance across breakpoints
- [ ] Keyboard navigation
- [ ] Screen reader compatibility
- [ ] Color contrast compliance
- [ ] Touch target sizes
- [ ] Loading states
- [ ] Error states
- [ ] Performance impact

### Code Review Guidelines
- [ ] Follows design system patterns
- [ ] Proper accessibility attributes
- [ ] Responsive design implemented
- [ ] Performance considerations
- [ ] Browser compatibility
- [ ] Documentation updated

## 📖 Migration Guide

### From Old Components
1. **Identify**: Find components using old patterns
2. **Replace**: Use new design system components
3. **Test**: Verify functionality and appearance
4. **Update**: Modify any custom styles

### Breaking Changes
- Old button classes replaced with `x-ui.button`
- Form components now use `x-ui.form-field`
- Navigation uses `x-navigation.nav-patterns`
- Layout uses new `x-layouts.base-page`

## 🤝 Contributing

### Adding New Components
1. Create component in appropriate directory
2. Follow existing naming conventions
3. Include comprehensive props documentation
4. Add usage examples
5. Write tests
6. Update this documentation

### Reporting Issues
- Use GitHub issues for bugs and feature requests
- Include reproduction steps and screenshots
- Tag with appropriate labels (bug, enhancement, accessibility)

---

**Version**: 1.0.0  
**Last Updated**: {{ date('Y-m-d') }}  
**Maintainer**: AttendanceHub Development Team