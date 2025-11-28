---
name: tailwind-components
description: Reusable Tailwind CSS component patterns for consistent UI. Use when creating buttons, cards, forms, tables, modals, navigation, or any UI components with Tailwind CSS.
allowed-tools: Read, Grep, Glob, Edit, Write
---

# Tailwind Components Skill

This skill provides ready-to-use Tailwind CSS component patterns for building consistent, professional UI.

## Button Components

### Primary Buttons
```html
<!-- Primary Button -->
<button type="button" class="inline-flex items-center justify-center px-4 py-2.5
    bg-blue-600 hover:bg-blue-700 active:bg-blue-800
    text-white text-sm font-medium
    rounded-lg shadow-sm hover:shadow
    transition-all duration-200
    focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
    disabled:opacity-50 disabled:cursor-not-allowed">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
    Add New
</button>

<!-- Secondary Button -->
<button type="button" class="inline-flex items-center justify-center px-4 py-2.5
    bg-white hover:bg-gray-50 active:bg-gray-100
    text-gray-700 text-sm font-medium
    border border-gray-300
    rounded-lg shadow-sm
    transition-colors duration-200
    focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2
    dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
    Cancel
</button>

<!-- Danger Button -->
<button type="button" class="inline-flex items-center justify-center px-4 py-2.5
    bg-red-600 hover:bg-red-700 active:bg-red-800
    text-white text-sm font-medium
    rounded-lg shadow-sm
    transition-colors duration-200
    focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
    Delete
</button>

<!-- Ghost Button -->
<button type="button" class="inline-flex items-center justify-center px-4 py-2.5
    hover:bg-gray-100 active:bg-gray-200
    text-gray-700 text-sm font-medium
    rounded-lg
    transition-colors duration-200
    dark:hover:bg-gray-700 dark:text-gray-200">
    View Details
</button>

<!-- Icon Button -->
<button type="button" class="inline-flex items-center justify-center p-2
    hover:bg-gray-100 active:bg-gray-200
    text-gray-500 hover:text-gray-700
    rounded-lg
    transition-colors duration-200
    dark:hover:bg-gray-700 dark:text-gray-400">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
    </svg>
</button>
```

### Button Group
```html
<div class="inline-flex rounded-lg shadow-sm" role="group">
    <button class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200
        rounded-l-lg hover:bg-gray-100 focus:z-10 focus:ring-2 focus:ring-blue-500
        dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:hover:bg-gray-700">
        Day
    </button>
    <button class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border-y border-blue-600
        focus:z-10 focus:ring-2 focus:ring-blue-500">
        Week
    </button>
    <button class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200
        rounded-r-lg hover:bg-gray-100 focus:z-10 focus:ring-2 focus:ring-blue-500
        dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:hover:bg-gray-700">
        Month
    </button>
</div>
```

## Card Components

### Basic Card
```html
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm
    border border-gray-200 dark:border-gray-700
    overflow-hidden">
    <div class="p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Card Title
        </h3>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Card description goes here with some supporting text.
        </p>
    </div>
</div>
```

### Stats Card
```html
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm
    border border-gray-200 dark:border-gray-700 p-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center justify-center w-12 h-12
            bg-blue-100 dark:bg-blue-900/30 rounded-xl">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
            bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
            </svg>
            12%
        </span>
    </div>
    <div class="mt-4">
        <h4 class="text-2xl font-bold text-gray-900 dark:text-white">2,543</h4>
        <p class="text-sm text-gray-500 dark:text-gray-400">Total Employees</p>
    </div>
</div>
```

### Card with Header & Actions
```html
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm
    border border-gray-200 dark:border-gray-700 overflow-hidden">
    <!-- Header -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Activity</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Last 7 days</p>
        </div>
        <button class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
            View All
        </button>
    </div>
    <!-- Body -->
    <div class="p-6">
        <!-- Content here -->
    </div>
</div>
```

## Form Components

### Text Input
```html
<div class="space-y-2">
    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
        Email Address
    </label>
    <input type="email" id="email" name="email"
        class="block w-full px-4 py-2.5
        bg-white dark:bg-gray-800
        border border-gray-300 dark:border-gray-600
        rounded-lg shadow-sm
        text-gray-900 dark:text-white
        placeholder:text-gray-400 dark:placeholder:text-gray-500
        focus:ring-2 focus:ring-blue-500 focus:border-blue-500
        transition-colors duration-200"
        placeholder="you@example.com">
    <p class="text-sm text-gray-500 dark:text-gray-400">We'll never share your email.</p>
</div>
```

### Input with Icon
```html
<div class="relative">
    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
    </div>
    <input type="text"
        class="block w-full pl-10 pr-4 py-2.5
        bg-white dark:bg-gray-800
        border border-gray-300 dark:border-gray-600
        rounded-lg shadow-sm
        text-gray-900 dark:text-white
        placeholder:text-gray-400
        focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        placeholder="Search...">
</div>
```

### Select Input
```html
<div class="space-y-2">
    <label for="department" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
        Department
    </label>
    <select id="department" name="department"
        class="block w-full px-4 py-2.5
        bg-white dark:bg-gray-800
        border border-gray-300 dark:border-gray-600
        rounded-lg shadow-sm
        text-gray-900 dark:text-white
        focus:ring-2 focus:ring-blue-500 focus:border-blue-500
        transition-colors duration-200">
        <option value="">Select Department</option>
        <option value="engineering">Engineering</option>
        <option value="hr">Human Resources</option>
        <option value="finance">Finance</option>
    </select>
</div>
```

### Checkbox & Radio
```html
<!-- Checkbox -->
<label class="inline-flex items-center cursor-pointer">
    <input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300
        rounded focus:ring-blue-500 dark:focus:ring-blue-600
        dark:ring-offset-gray-800 dark:bg-gray-700 dark:border-gray-600">
    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Remember me</span>
</label>

<!-- Radio -->
<label class="inline-flex items-center cursor-pointer">
    <input type="radio" name="type" value="full-time"
        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300
        focus:ring-blue-500 dark:focus:ring-blue-600
        dark:ring-offset-gray-800 dark:bg-gray-700 dark:border-gray-600">
    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Full Time</span>
</label>
```

### Toggle Switch
```html
<label class="relative inline-flex items-center cursor-pointer">
    <input type="checkbox" class="sr-only peer">
    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4
        peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer
        dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white
        after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white
        after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5
        after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
    <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">Enable notifications</span>
</label>
```

## Table Components

### Basic Table
```html
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Employee
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Status
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Department
                    </th>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <img class="h-10 w-10 rounded-full object-cover" src="avatar.jpg" alt="">
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">John Doe</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">john@example.com</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                            bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                            Active
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                        Engineering
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mr-3">Edit</button>
                        <button class="text-red-600 hover:text-red-800 dark:text-red-400">Delete</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
```

## Modal Component

```html
<!-- Backdrop -->
<div class="fixed inset-0 z-50 overflow-y-auto" x-show="open" x-cloak>
    <div class="flex min-h-screen items-center justify-center p-4">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-black/50 transition-opacity" @click="open = false"></div>

        <!-- Modal -->
        <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-xl shadow-2xl
            transform transition-all">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Modal Title</h3>
                <button @click="open = false" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="px-6 py-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Modal content goes here.
                </p>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                <button @click="open = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>
```

## Badge/Status Components

```html
<!-- Status Badges -->
<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
    <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-green-500"></span>
    Active
</span>

<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
    <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-yellow-500"></span>
    Pending
</span>

<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
    <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-red-500"></span>
    Inactive
</span>

<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
    Draft
</span>
```

## Alert Components

```html
<!-- Success Alert -->
<div class="flex items-center p-4 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800">
    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
    </svg>
    <p class="ml-3 text-sm font-medium text-green-700 dark:text-green-400">
        Successfully saved!
    </p>
</div>

<!-- Error Alert -->
<div class="flex items-center p-4 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800">
    <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
    </svg>
    <p class="ml-3 text-sm font-medium text-red-700 dark:text-red-400">
        Something went wrong!
    </p>
</div>

<!-- Warning Alert -->
<div class="flex items-center p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800">
    <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
    </svg>
    <p class="ml-3 text-sm font-medium text-yellow-700 dark:text-yellow-400">
        Please review before submitting.
    </p>
</div>
```

## Navigation Components

### Sidebar Navigation Item
```html
<a href="#" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg
    text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700
    transition-colors duration-200 group">
    <svg class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    Dashboard
</a>

<!-- Active state -->
<a href="#" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg
    bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
    <svg class="w-5 h-5 mr-3 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
    Employees
</a>
```

## Loading States

```html
<!-- Spinner -->
<svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
</svg>

<!-- Skeleton -->
<div class="animate-pulse space-y-4">
    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded"></div>
    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-5/6"></div>
</div>

<!-- Button with Loading -->
<button class="inline-flex items-center px-4 py-2.5 bg-blue-600 text-white rounded-lg" disabled>
    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
    </svg>
    Processing...
</button>
```

## Empty States

```html
<div class="flex flex-col items-center justify-center py-12 px-4">
    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
        </svg>
    </div>
    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">No data found</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-4">
        Get started by creating your first item.
    </p>
    <button class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Create New
    </button>
</div>
```
