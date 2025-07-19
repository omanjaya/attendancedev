# Request Caching Guide

This guide explains how to use the request caching system implemented in the Vue.js application for
improved performance and reduced API calls.

## Overview

The request caching system provides:

- **Intelligent caching** with TTL (Time To Live) support
- **Background refresh** to serve stale data while updating
- **Memory management** with LRU eviction
- **Persistence** to localStorage for cross-session caching
- **Cache invalidation** by patterns and keys
- **Performance monitoring** and statistics
- **Vue integration** through composables

## Core Services

### 1. RequestCacheService (`/services/requestCache.ts`)

The core caching service that handles all cache operations:

```typescript
import { createRequestCacheService } from '@/services/requestCache'

const cacheService = createRequestCacheService({
  defaultTTL: 5 * 60 * 1000, // 5 minutes
  maxSize: 200, // max cache entries
  enableBackgroundRefresh: true,
  backgroundRefreshThreshold: 0.7, // refresh at 70% TTL
  enablePersistence: true,
  debugMode: true,
})
```

### 2. Vue Composables (`/composables/useRequestCache.ts`)

Vue-specific composables for reactive caching:

- `useCachedRequest()` - Basic cached request with reactive state
- `useInfiniteQuery()` - Infinite/paginated queries
- `useRequestCacheStats()` - Cache statistics and management
- `useCacheKey()` - Cache key generation utilities

### 3. Cached API Service (`/services/cachedApiService.ts`)

High-level API service with built-in caching for common endpoints.

## Basic Usage

### Simple Cached Request

```typescript
import { useCachedRequest } from '@/composables/useRequestCache'

const { data, loading, error, refresh } = useCachedRequest(
  'users:list', // cache key
  () => fetch('/api/users').then((r) => r.json()), // fetch function
  {
    ttl: 10 * 60 * 1000, // 10 minutes
    immediate: true, // fetch immediately
  }
)
```

### Reactive Cache Keys

```typescript
const userId = ref('123')

const { data, loading, error } = useCachedRequest(
  () => `users:${userId.value}`, // reactive key
  () => fetch(`/api/users/${userId.value}`).then((r) => r.json()),
  {
    ttl: 5 * 60 * 1000,
    watchSource: userId, // refetch when userId changes
  }
)
```

### Conditional Requests

```typescript
const shouldLoad = ref(false)

const { data, loading, error } = useCachedRequest(
  'expensive-data',
  () => fetch('/api/expensive-data').then((r) => r.json()),
  {
    enabled: shouldLoad, // only fetch when enabled
    ttl: 30 * 60 * 1000, // 30 minutes
  }
)
```

## Advanced Features

### Background Refresh

Serve stale data while refreshing in the background:

```typescript
const { data, loading, error, isStale } = useCachedRequest(
  'real-time-data',
  () => fetch('/api/real-time-data').then((r) => r.json()),
  {
    ttl: 1 * 60 * 1000, // 1 minute
    background: true, // allow stale data
    backgroundRefreshThreshold: 0.8, // refresh at 80% TTL
  }
)

// isStale.value will be true when serving cached data
```

### Infinite Queries

For paginated data:

```typescript
import { useInfiniteQuery } from '@/composables/useRequestCache'

const {
  data, // flattened array of all pages
  loading,
  error,
  hasNextPage,
  isFetchingNextPage,
  fetchNextPage,
} = useInfiniteQuery(
  'posts:infinite',
  ({ pageParam = 1 }) => fetch(`/api/posts?page=${pageParam}`).then((r) => r.json()),
  {
    getNextPageParam: (lastPage, allPages) => (lastPage.hasMore ? allPages.length + 1 : undefined),
    pageSize: 20,
  }
)
```

### Cache Key Generation

Use the cache key utilities for consistent key generation:

```typescript
import { useCacheKey } from '@/composables/useRequestCache'

const cacheKey = useCacheKey()

// Generate keys with parameters
const listKey = cacheKey.generateListKey(
  'users', // resource
  { department: 'IT', status: 'active' }, // filters
  { field: 'name', direction: 'asc' }, // sorting
  { page: 1, limit: 20 } // pagination
)
// Result: "users:list?department=IT&status=active&sort=name:asc&page=1&limit=20"

const detailKey = cacheKey.generateDetailKey('users', '123')
// Result: "users:123"
```

## Using the Cached API Service

The cached API service provides high-level methods with built-in caching:

```typescript
import { cachedApiService } from '@/services/cachedApiService'

// Get employees with caching
const employees = await cachedApiService.getEmployees({
  department: 'IT',
  status: 'active',
  ttl: 15 * 60 * 1000, // custom TTL
})

// Get specific employee
const employee = await cachedApiService.getEmployee('123')

// Get available teachers (cached for 2 minutes)
const teachers = await cachedApiService.getAvailableTeachers({
  subjectId: 'math-101',
  dayOfWeek: 'monday',
  timeSlotId: 'slot-1',
})
```

## Cache Invalidation

### Manual Invalidation

```typescript
import { invalidateCache } from '@/services/requestCache'

// Invalidate specific key
invalidateCache('users:123')

// Invalidate by pattern
invalidateCache(/^users:.*/) // all user-related cache
invalidateCache('users:list') // specific list cache
```

### Using API Service Invalidation

```typescript
import { cachedApiService } from '@/services/cachedApiService'

// Invalidate employee cache after update
await updateEmployee(employeeId, data)
cachedApiService.invalidateEmployeeCache(employeeId)

// Invalidate all schedule cache after changes
await createSchedule(scheduleData)
cachedApiService.invalidateScheduleCache()
```

### Automatic Invalidation in Components

```vue
<script setup>
import { useCachedRequest } from '@/composables/useRequestCache'

const { data, refresh, clear } = useCachedRequest('users:list', fetchUsers)

const handleUserUpdate = async (userData) => {
  await updateUser(userData)

  // Refresh the cache with new data
  await refresh()

  // Or clear to force fresh fetch
  clear()
}
</script>
```

## Performance Optimization

### Prefetching

Prefetch data that will likely be needed:

```typescript
import { cachedApiService } from '@/services/cachedApiService'

// Prefetch employee details for a list
const employeeIds = ['1', '2', '3', '4', '5']
await cachedApiService.prefetchEmployeeData(employeeIds)
```

### Cache Warming

Warm up frequently accessed data on app startup:

```typescript
// In app initialization
import { cachedApiService } from '@/services/cachedApiService'

// Warm up common data
await cachedApiService.warmUpCommonData()
```

### Memory Management

The cache automatically manages memory with:

- **LRU eviction** when max size is reached
- **TTL-based cleanup** for expired entries
- **Background cleanup** every 5 minutes

Monitor cache performance:

```typescript
import { useRequestCacheStats } from '@/composables/useRequestCache'

const { stats, clearCache, invalidatePattern } = useRequestCacheStats()

// stats.value contains:
// - hits, misses, evictions
// - hitRate, size, memoryUsage
// - backgroundRefreshes, totalRequests
```

## Component Integration Examples

### Loading States with Cache

```vue
<script setup>
import { useCachedRequest } from '@/composables/useRequestCache'

const {
  data: users,
  loading,
  error,
  isStale,
} = useCachedRequest('users:list', () => fetch('/api/users').then((r) => r.json()), {
  ttl: 5 * 60 * 1000,
  background: true,
})
</script>

<template>
  <div>
    <!-- Show loading spinner only for initial load -->
    <LoadingSpinner v-if="loading && !data" />

    <!-- Show stale data indicator -->
    <StaleDataBanner v-if="isStale" @refresh="refresh" />

    <!-- Show users list -->
    <UserList v-if="data" :users="data" />

    <!-- Show error state -->
    <ErrorMessage v-if="error" :error="error" />
  </div>
</template>
```

### Form with Cached Dependencies

```vue
<script setup>
import { useCachedRequest } from '@/composables/useRequestCache'

const selectedDepartment = ref('')

// Cache subjects based on department
const { data: subjects } = useCachedRequest(
  () => `subjects:department:${selectedDepartment.value}`,
  () => fetch(`/api/subjects?department=${selectedDepartment.value}`).then((r) => r.json()),
  {
    enabled: computed(() => !!selectedDepartment.value),
    ttl: 10 * 60 * 1000,
  }
)

// Cache teachers for selected subject and time
const selectedSubject = ref('')
const timeSlot = ref('')

const { data: availableTeachers } = useCachedRequest(
  () => `teachers:${selectedSubject.value}:${timeSlot.value}`,
  () => fetchAvailableTeachers(selectedSubject.value, timeSlot.value),
  {
    enabled: computed(() => !!selectedSubject.value && !!timeSlot.value),
    ttl: 2 * 60 * 1000, // shorter TTL for dynamic data
    watchSource: () => [selectedSubject.value, timeSlot.value],
  }
)
</script>
```

## Configuration

### Environment-based Configuration

```typescript
// In app initialization
const cacheConfig = {
  defaultTTL: import.meta.env.PROD ? 10 * 60 * 1000 : 30 * 1000,
  maxSize: import.meta.env.PROD ? 500 : 100,
  enableBackgroundRefresh: import.meta.env.PROD,
  enablePersistence: import.meta.env.PROD,
  debugMode: import.meta.env.DEV,
}

createRequestCacheService(cacheConfig)
```

### Cache TTL Guidelines

- **Static data** (subjects, departments): 30+ minutes
- **User data** (profiles, permissions): 15 minutes
- **Schedule data**: 5 minutes
- **Attendance data**: 1-2 minutes
- **Real-time data**: 30 seconds with background refresh

## Best Practices

### Do's ✅

1. **Use appropriate TTL** based on data freshness requirements
2. **Enable background refresh** for frequently accessed data
3. **Invalidate cache** after mutations
4. **Use reactive cache keys** for dynamic data
5. **Monitor cache statistics** in development
6. **Prefetch data** when possible

### Don'ts ❌

1. **Don't cache sensitive data** (passwords, tokens)
2. **Don't use very short TTLs** without background refresh
3. **Don't cache large objects** without memory monitoring
4. **Don't forget to invalidate** after updates
5. **Don't cache error responses**

## Testing

Test cached requests in your components:

```typescript
// In tests
import { vi } from 'vitest'
import { createRequestCacheService } from '@/services/requestCache'

beforeEach(() => {
  // Create fresh cache for each test
  createRequestCacheService({
    enablePersistence: false,
    debugMode: true,
  })
})

it('should cache API responses', async () => {
  const mockFetch = vi.fn().mockResolvedValue({
    ok: true,
    json: () => Promise.resolve({ data: [{ id: 1, name: 'Test' }] }),
  })

  global.fetch = mockFetch

  // Mount component that uses cached request
  const wrapper = mount(MyComponent)

  // First call should hit the API
  await nextTick()
  expect(mockFetch).toHaveBeenCalledTimes(1)

  // Second call should use cache
  wrapper.vm.refresh()
  await nextTick()
  expect(mockFetch).toHaveBeenCalledTimes(1) // still 1
})
```

This caching system significantly improves application performance by reducing redundant API calls
while maintaining data freshness through intelligent background refresh and proper invalidation
strategies.
