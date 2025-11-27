---
name: frontend-design
description: Enhance frontend UI/UX design with better typography, color systems, animations, and visual hierarchy. Use when creating or improving UI components, layouts, dashboards, forms, or any visual elements.
allowed-tools: Read, Grep, Glob, Edit, Write
---

# Frontend Design Skill

This skill provides guidance for creating polished, professional frontend designs that avoid the generic "AI aesthetic."

## Typography System

### Font Selection
- **AVOID**: Inter, Roboto, Open Sans (overused, generic)
- **PREFER**:
  - Headers: Figtree, Plus Jakarta Sans, Outfit, Manrope
  - Body: Source Sans 3, IBM Plex Sans, Nunito Sans
  - Monospace: JetBrains Mono, Fira Code, IBM Plex Mono

### Font Sizing Scale
```css
/* Use consistent scale */
--text-xs: 0.75rem;    /* 12px - captions, badges */
--text-sm: 0.875rem;   /* 14px - secondary text */
--text-base: 1rem;     /* 16px - body text */
--text-lg: 1.125rem;   /* 18px - lead text */
--text-xl: 1.25rem;    /* 20px - card titles */
--text-2xl: 1.5rem;    /* 24px - section headers */
--text-3xl: 1.875rem;  /* 30px - page titles */
--text-4xl: 2.25rem;   /* 36px - hero text */
```

### Font Weight Usage
- **300 Light**: Large display text only
- **400 Regular**: Body text, descriptions
- **500 Medium**: Labels, buttons, navigation
- **600 Semibold**: Card titles, emphasis
- **700 Bold**: Page titles, key metrics

## Color System

### Avoid Generic Colors
- ❌ Purple gradients (#8B5CF6 → #6366F1)
- ❌ Default blue (#3B82F6)
- ❌ Plain gray backgrounds

### Create Cohesive Palettes
```css
/* Warm Professional Theme */
--primary: #0F766E;      /* Teal - professional, calm */
--primary-light: #14B8A6;
--accent: #F59E0B;       /* Amber - energy, attention */
--success: #059669;      /* Emerald */
--warning: #D97706;      /* Amber dark */
--error: #DC2626;        /* Red */
--surface: #FAFAF9;      /* Stone 50 */
--surface-alt: #F5F5F4;  /* Stone 100 */

/* Cool Modern Theme */
--primary: #0EA5E9;      /* Sky - fresh, modern */
--primary-light: #38BDF8;
--accent: #8B5CF6;       /* Violet */
--surface: #F8FAFC;      /* Slate 50 */
```

### Dark Mode Considerations
- Don't just invert colors
- Use slate/zinc grays, not pure black
- Reduce contrast slightly for comfort
- Keep accent colors slightly desaturated

## Visual Hierarchy

### Spacing System (8px base)
```css
--space-1: 0.25rem;  /* 4px */
--space-2: 0.5rem;   /* 8px */
--space-3: 0.75rem;  /* 12px */
--space-4: 1rem;     /* 16px */
--space-5: 1.25rem;  /* 20px */
--space-6: 1.5rem;   /* 24px */
--space-8: 2rem;     /* 32px */
--space-10: 2.5rem;  /* 40px */
--space-12: 3rem;    /* 48px */
```

### Card Design
```html
<!-- Good card structure -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm
            border border-gray-100 dark:border-gray-700
            hover:shadow-md transition-shadow duration-200">
  <div class="p-6">
    <!-- Content with consistent padding -->
  </div>
</div>
```

### Z-Index Scale
```css
--z-dropdown: 10;
--z-sticky: 20;
--z-fixed: 30;
--z-modal-backdrop: 40;
--z-modal: 50;
--z-popover: 60;
--z-tooltip: 70;
```

## Animations & Micro-interactions

### Timing Functions
```css
/* Smooth, natural motion */
--ease-out: cubic-bezier(0.16, 1, 0.3, 1);
--ease-in-out: cubic-bezier(0.65, 0, 0.35, 1);
--ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
```

### Duration Guidelines
- **Instant feedback**: 100-150ms (hovers, toggles)
- **Quick transitions**: 200-300ms (dropdowns, tabs)
- **Smooth animations**: 300-500ms (modals, page transitions)
- **Elaborate effects**: 500-800ms (onboarding, celebrations)

### Meaningful Animations
```css
/* Button hover - subtle lift */
.btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Card hover - gentle rise */
.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

/* Loading state - pulse */
.loading {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
```

## Component Patterns

### Buttons
```html
<!-- Primary action -->
<button class="px-4 py-2.5 bg-primary-600 hover:bg-primary-700
               text-white font-medium rounded-lg
               shadow-sm hover:shadow-md
               transition-all duration-200
               focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
  Save Changes
</button>

<!-- Secondary action -->
<button class="px-4 py-2.5 bg-white hover:bg-gray-50
               text-gray-700 font-medium rounded-lg
               border border-gray-300
               transition-colors duration-200">
  Cancel
</button>
```

### Form Inputs
```html
<div class="space-y-1.5">
  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
    Email Address
  </label>
  <input type="email"
         class="w-full px-4 py-2.5 rounded-lg border border-gray-300
                bg-white dark:bg-gray-800 dark:border-gray-600
                focus:ring-2 focus:ring-primary-500 focus:border-primary-500
                placeholder:text-gray-400
                transition-colors duration-200"
         placeholder="you@example.com">
  <p class="text-sm text-gray-500">We'll never share your email.</p>
</div>
```

### Status Badges
```html
<!-- Success -->
<span class="inline-flex items-center px-2.5 py-1 rounded-full
             text-xs font-medium
             bg-emerald-50 text-emerald-700
             dark:bg-emerald-900/30 dark:text-emerald-400">
  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
  Active
</span>
```

## Atmospheric Backgrounds

### Gradient Meshes
```css
.bg-mesh {
  background-color: #fafafa;
  background-image:
    radial-gradient(at 40% 20%, rgba(20, 184, 166, 0.1) 0px, transparent 50%),
    radial-gradient(at 80% 0%, rgba(59, 130, 246, 0.08) 0px, transparent 50%),
    radial-gradient(at 0% 50%, rgba(245, 158, 11, 0.06) 0px, transparent 50%);
}
```

### Subtle Patterns
```css
.bg-dots {
  background-image: radial-gradient(#e5e7eb 1px, transparent 1px);
  background-size: 20px 20px;
}

.bg-grid {
  background-image:
    linear-gradient(to right, #f1f5f9 1px, transparent 1px),
    linear-gradient(to bottom, #f1f5f9 1px, transparent 1px);
  background-size: 24px 24px;
}
```

## Dashboard Specific

### Metric Cards
- Use icons with colored backgrounds (not bare icons)
- Show trend indicators (↑ +12% in green)
- Include sparkline charts for context
- Group related metrics visually

### Data Tables
- Sticky headers for long tables
- Zebra striping OR hover highlight (not both)
- Action buttons visible on hover
- Pagination with page size options

### Charts
- Use brand colors consistently
- Include legends when >2 data series
- Add tooltips with formatted values
- Animate on first load only

## Accessibility Checklist

- [ ] Color contrast ratio ≥ 4.5:1 for text
- [ ] Focus states visible and consistent
- [ ] Touch targets ≥ 44x44px on mobile
- [ ] Animations respect prefers-reduced-motion
- [ ] Form labels properly associated
- [ ] Error messages descriptive and visible
