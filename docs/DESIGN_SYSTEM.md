# Design System Documentation

## Overview

This attendance management system now uses a **Glassmorphism Design System** based on the dashboard
design, ensuring consistency across all views and components.

## Core Components

### 1. Base Layout Component

**Location:** `resources/views/components/layouts/page-base.blade.php`

This is the foundation component that all pages should use. It provides:

- Glassmorphism background with animated blobs
- Optional welcome section
- Consistent spacing and structure
- Dark mode support

**Usage:**

```blade
<x-layouts.page-base
    title="Page Title"
    subtitle="Page description"
    :show-background="true"
    :show-welcome="true"
    welcome-title="Welcome Title"
    welcome-subtitle="Welcome description">

    <!-- Your page content here -->

</x-layouts.page-base>
```

### 2. Glass Card Component

**Location:** `resources/views/components/layouts/glass-card.blade.php`

Provides glassmorphism cards with:

- Backdrop blur effects
- Transparent backgrounds with borders
- Hover effects
- Multiple variants (default, primary, success, warning, danger, info)
- Responsive sizing and padding

**Usage:**

```blade
<x-layouts.glass-card variant="primary" size="lg" :hover="true">
    <!-- Card content -->
</x-layouts.glass-card>
```

### 3. Glass Table Component

**Location:** `resources/views/components/layouts/glass-table.blade.php`

Glassmorphism-styled tables with:

- Transparent backgrounds
- Enhanced readability in both light and dark modes
- Hover effects
- Proper contrast ratios

**Usage:**

```blade
<x-layouts.glass-table class="glass-table">
    <thead>
        <tr>
            <th>Column 1</th>
            <th>Column 2</th>
        </tr>
    </thead>
    <tbody>
        <!-- Table rows -->
    </tbody>
</x-layouts.glass-table>
```

## Design Principles

### 1. Glassmorphism Effects

- **Backdrop Blur:** `backdrop-blur-xl` (16px blur with 180% saturation)
- **Transparency:** Background colors with 20-40% opacity
- **Borders:** Semi-transparent borders (`border-white/20 dark:border-gray-600/30`)
- **Shadows:** Subtle shadows with color-matched blur

### 2. Color Palette

#### Light Mode

- **Background:** `from-emerald-50 via-green-50 to-teal-50`
- **Cards:** `bg-white/30` with `border-white/20`
- **Text:** `text-gray-900` (primary), `text-gray-600` (secondary)

#### Dark Mode

- **Background:** `dark:from-gray-900 dark:via-gray-800 dark:to-gray-900`
- **Cards:** `dark:bg-gray-800/40` with `dark:border-gray-600/30`
- **Text:** `dark:text-gray-100` (primary), `dark:text-gray-300` (secondary)

### 3. Typography

- **Headings:** `text-lg font-semibold` for card titles
- **Subtext:** `text-sm text-gray-600 dark:text-gray-300`
- **Body:** Consistent contrast ratios for accessibility

### 4. Interactive Elements

- **Hover Effects:** Increased opacity and enhanced shadows
- **Transitions:** `transition-all duration-300` for smooth animations
- **Focus States:** Emerald-colored focus rings

## Implementation Examples

### 1. Employee Management Page

**Location:** `resources/views/pages/management/employees/index.blade.php`

Features implemented:

- ✅ Page base layout with welcome section
- ✅ Stats grid wrapped in glass card
- ✅ Employee directory with glassmorphism styling
- ✅ Search and filter inputs with glass effects
- ✅ Responsive design

### 2. Attendance Management Page

**Location:** `resources/views/pages/attendance/index.blade.php`

Features implemented:

- ✅ Page base layout with glassmorphism background
- ✅ Stats grid with real-time data
- ✅ Glass table for attendance records
- ✅ Sidebar with quick actions
- ✅ Face detection status card

## Sidebar Design

### Organization

The sidebar is organized into logical sections with separators:

1. **Dashboard** - Main overview
2. **Master Data** - Employees, Schedules
3. **Operations** - Attendance, Leave, Payroll
4. **Reports** - Analytics & Reports
5. **Quick Actions** - Face Check-in

### Styling Features

- **Glassmorphism background** with animated elements
- **Section headers** with gradient separators
- **Active states** with enhanced contrast
- **Hover effects** with smooth transitions
- **Dark mode optimization** for better readability

## Theme Toggle

### Simplified Implementation

- **Light/Dark Only:** Removed system preference option
- **Single Click Toggle:** Alternates between light → dark → light
- **Persistent Storage:** Saves preference in localStorage
- **Smooth Transitions:** Enhanced theme switching animations

## CSS Animations

### Blob Animations

```css
@keyframes blob {
  0% {
    transform: translate(0px, 0px) scale(1);
  }
  33% {
    transform: translate(30px, -50px) scale(1.1);
  }
  66% {
    transform: translate(-20px, 20px) scale(0.9);
  }
  100% {
    transform: translate(0px, 0px) scale(1);
  }
}
```

### Glass Effects

- **Enhanced Backdrop Filter:** `backdrop-filter: blur(16px) saturate(180%)`
- **Hover Enhancements:** Increased opacity and shadow effects
- **Border Animations:** Color transitions on interaction

## Best Practices

### 1. Component Usage

- Always use `<x-layouts.page-base>` for consistent layout
- Wrap content sections in `<x-layouts.glass-card>`
- Use glass tables for data display
- Maintain consistent spacing with the established grid

### 2. Dark Mode Considerations

- Test all components in both light and dark modes
- Ensure proper contrast ratios (WCAG AA compliance)
- Use semantic color variables when possible
- Provide fallbacks for transparency effects

### 3. Performance

- CSS animations use transform properties for better performance
- Backdrop filters are applied efficiently
- Component lazy loading where applicable

### 4. Responsive Design

- Mobile-first approach with progressive enhancement
- Flexible grid systems
- Touch-friendly interactive elements
- Appropriate spacing adjustments for different screen sizes

## Future Enhancements

### Planned Components

1. **Glass Modal Component** - For dialogs and overlays
2. **Glass Form Components** - Enhanced form elements
3. **Glass Navigation** - Breadcrumb and pagination components
4. **Glass Charts** - Data visualization with glassmorphism

### Accessibility Improvements

1. **High Contrast Mode** - Optional high contrast theme
2. **Motion Preferences** - Respect prefers-reduced-motion
3. **Focus Management** - Enhanced keyboard navigation
4. **Screen Reader Optimization** - ARIA labels and descriptions

## Migration Guide

### Converting Existing Views

1. Replace `@extends('layouts.authenticated')` structure with `<x-layouts.page-base>`
2. Wrap content sections in `<x-layouts.glass-card>`
3. Replace tables with `<x-layouts.glass-table>`
4. Update form elements to use glassmorphism styling
5. Test in both light and dark modes

### Example Migration

```blade
<!-- Before -->
@extends('layouts.authenticated')
@section('page-content')
    <x-ui.card>
        <!-- Content -->
    </x-ui.card>
@endsection

<!-- After -->
@extends('layouts.authenticated')
@section('page-content')
<x-layouts.page-base title="Page Title" :show-background="true">
    <x-layouts.glass-card>
        <!-- Content -->
    </x-layouts.glass-card>
</x-layouts.page-base>
@endsection
```

This design system ensures consistency, maintainability, and a premium user experience across the
entire attendance management system.
