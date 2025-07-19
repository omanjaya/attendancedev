/**
 * Vue Composable for Request Caching
 *
 * Provides reactive caching capabilities for Vue components with
 * automatic invalidation, loading states, and error handling.
 */

import { ref, computed, watch, onUnmounted, type Ref } from 'vue'
import {
  getRequestCacheService,
  type RequestOptions,
  type CacheStats,
} from '@/services/requestCache'
import { useErrorTracking } from '@/composables/useErrorTracking'

export interface UseCachedRequestOptions<T> extends RequestOptions {
  // Vue-specific options
  immediate?: boolean
  watchSource?: Ref<any> | (() => any)
  enabled?: Ref<boolean> | boolean
  onSuccess?: (data: T) => void
  onError?: (error: Error) => void
  retryOnError?: boolean
  retryDelay?: number
  maxRetries?: number
}

export interface UseCachedRequestReturn<T> {
  data: Ref<T | null>
  loading: Ref<boolean>
  error: Ref<Error | null>
  isStale: Ref<boolean>
  refresh: () => Promise<void>
  clear: () => void
  mutate: (newData: T | ((current: T | null) => T)) => void
}

export interface UseInfiniteQueryOptions<T> extends UseCachedRequestOptions<T[]> {
  pageSize?: number
  getNextPageParam?: (lastPage: T[], allPages: T[][]) => any
  initialPageParam?: any
}

export interface UseInfiniteQueryReturn<T> extends Omit<UseCachedRequestReturn<T[]>, 'refresh'> {
  hasNextPage: Ref<boolean>
  isFetchingNextPage: Ref<boolean>
  fetchNextPage: () => Promise<void>
  refresh: () => Promise<void>
}

/**
 * Main composable for cached requests
 */
export function useCachedRequest<T>(
  key: string | Ref<string> | (() => string),
  fetchFn: () => Promise<T>,
  options: UseCachedRequestOptions<T> = {}
): UseCachedRequestReturn<T> {
  const errorTracking = useErrorTracking()
  const cacheService = getRequestCacheService()

  const data = ref<T | null>(null) as Ref<T | null>
  const loading = ref(false)
  const error = ref<Error | null>(null)
  const isStale = ref(false)

  const retryCount = ref(0)
  const maxRetries = options.maxRetries || 3
  const retryDelay = options.retryDelay || 1000

  // Compute the cache key
  const cacheKey = computed(() => {
    if (typeof key === 'string') {return key}
    if (typeof key === 'function') {return key()}
    return key.value
  })

  // Check if request is enabled
  const isEnabled = computed(() => {
    if (typeof options.enabled === 'boolean') {return options.enabled}
    if (options.enabled) {return options.enabled.value}
    return true
  })

  const executeRequest = async (forceRefresh = false) => {
    if (!isEnabled.value || !cacheService) {return}

    const currentKey = cacheKey.value
    if (!currentKey) {return}

    loading.value = true
    error.value = null
    retryCount.value = 0

    const requestOptions: RequestOptions = {
      ttl: options.ttl,
      force: forceRefresh,
      background: options.background,
      etag: options.etag,
      lastModified: options.lastModified,
      metadata: options.metadata,
    }

    const attemptRequest = async (): Promise<T> => {
      try {
        errorTracking.addBreadcrumb('Executing cached request', 'api', {
          key: currentKey,
          force: forceRefresh,
          retryCount: retryCount.value,
        })

        const result = await cacheService.get(currentKey, fetchFn, requestOptions)

        errorTracking.addBreadcrumb('Cached request successful', 'api', {
          key: currentKey,
          hasData: !!result,
        })

        return result
      } catch (requestError) {
        const error = requestError instanceof Error ? requestError : new Error(String(requestError))

        errorTracking.captureError(error, {
          action: 'cached_request_failed',
          metadata: {
            key: currentKey,
            retryCount: retryCount.value,
            maxRetries,
            requestOptions,
          },
        })

        if (options.retryOnError && retryCount.value < maxRetries) {
          retryCount.value++

          errorTracking.addBreadcrumb(`Retrying request (attempt ${retryCount.value})`, 'api', {
            key: currentKey,
            delay: retryDelay,
          })

          await new Promise((resolve) => setTimeout(resolve, retryDelay))
          return attemptRequest()
        }

        throw error
      }
    }

    try {
      const result = await attemptRequest()
      data.value = result
      isStale.value = false

      if (options.onSuccess) {
        options.onSuccess(result)
      }
    } catch (requestError) {
      const finalError =
        requestError instanceof Error ? requestError : new Error(String(requestError))
      error.value = finalError

      if (options.onError) {
        options.onError(finalError)
      }
    } finally {
      loading.value = false
    }
  }

  const refresh = async () => {
    await executeRequest(true)
  }

  const clear = () => {
    if (cacheService) {
      cacheService.delete(cacheKey.value)
    }
    data.value = null
    error.value = null
    isStale.value = false
  }

  const mutate = (newData: T | ((current: T | null) => T)) => {
    const updatedData =
      typeof newData === 'function' ? (newData as (current: T | null) => T)(data.value) : newData

    data.value = updatedData

    // Update cache
    if (cacheService && cacheKey.value) {
      cacheService.set(cacheKey.value, updatedData, {
        ttl: options.ttl,
        metadata: options.metadata,
      })
    }
  }

  // Watch for key changes
  if (typeof key !== 'string') {
    watch(
      cacheKey,
      () => {
        if (isEnabled.value) {
          executeRequest()
        }
      },
      { immediate: false }
    )
  }

  // Watch for enabled changes
  watch(
    isEnabled,
    (enabled) => {
      if (enabled) {
        executeRequest()
      }
    },
    { immediate: false }
  )

  // Watch custom watch source
  if (options.watchSource) {
    watch(options.watchSource, () => {
      if (isEnabled.value) {
        executeRequest()
      }
    })
  }

  // Execute immediately if requested
  if (options.immediate !== false && isEnabled.value) {
    executeRequest()
  }

  return {
    data,
    loading,
    error,
    isStale,
    refresh,
    clear,
    mutate,
  }
}

/**
 * Composable for infinite/paginated queries
 */
export function useInfiniteQuery<T>(
  baseKey: string | Ref<string> | (() => string),
  fetchFn: (pageParam: any) => Promise<T[]>,
  options: UseInfiniteQueryOptions<T> = {}
): UseInfiniteQueryReturn<T> {
  const errorTracking = useErrorTracking()
  const cacheService = getRequestCacheService()

  const pages = ref<T[][]>([])
  const loading = ref(false)
  const error = ref<Error | null>(null)
  const isStale = ref(false)
  const isFetchingNextPage = ref(false)
  const currentPageParam = ref(options.initialPageParam)

  const data = computed(() => pages.value.flat())
  const hasNextPage = computed(() => {
    if (!options.getNextPageParam) {return false}
    const lastPage = pages.value[pages.value.length - 1]
    return lastPage ? !!options.getNextPageParam(lastPage, pages.value) : true
  })

  const baseKeyComputed = computed(() => {
    if (typeof baseKey === 'string') {return baseKey}
    if (typeof baseKey === 'function') {return baseKey()}
    return baseKey.value
  })

  const isEnabled = computed(() => {
    if (typeof options.enabled === 'boolean') {return options.enabled}
    if (options.enabled) {return options.enabled.value}
    return true
  })

  const fetchPage = async (pageParam: any = options.initialPageParam) => {
    if (!cacheService || !isEnabled.value) {return}

    const pageKey = `${baseKeyComputed.value}_page_${JSON.stringify(pageParam)}`

    errorTracking.addBreadcrumb('Fetching infinite query page', 'api', {
      baseKey: baseKeyComputed.value,
      pageParam,
      pageKey,
    })

    const pageData = await cacheService.get(pageKey, () => fetchFn(pageParam), {
      ttl: options.ttl,
      background: options.background,
      metadata: { ...options.metadata, pageParam },
    })

    return pageData
  }

  const fetchNextPage = async () => {
    if (!hasNextPage.value || isFetchingNextPage.value) {return}

    isFetchingNextPage.value = true
    error.value = null

    try {
      const nextPageParam =
        options.getNextPageParam?.(pages.value[pages.value.length - 1] || [], pages.value) ||
        currentPageParam.value

      const pageData = await fetchPage(nextPageParam)
      pages.value.push(pageData)
      currentPageParam.value = nextPageParam

      if (options.onSuccess) {
        options.onSuccess(data.value)
      }
    } catch (fetchError) {
      const finalError = fetchError instanceof Error ? fetchError : new Error(String(fetchError))
      error.value = finalError

      errorTracking.captureError(finalError, {
        action: 'infinite_query_page_failed',
        metadata: {
          baseKey: baseKeyComputed.value,
          pageParam: currentPageParam.value,
          pagesLoaded: pages.value.length,
        },
      })

      if (options.onError) {
        options.onError(finalError)
      }
    } finally {
      isFetchingNextPage.value = false
    }
  }

  const refresh = async () => {
    if (!isEnabled.value) {return}

    loading.value = true
    error.value = null
    pages.value = []
    currentPageParam.value = options.initialPageParam

    try {
      const firstPage = await fetchPage(options.initialPageParam)
      pages.value = [firstPage]
      isStale.value = false

      if (options.onSuccess) {
        options.onSuccess(data.value)
      }
    } catch (refreshError) {
      const finalError =
        refreshError instanceof Error ? refreshError : new Error(String(refreshError))
      error.value = finalError

      if (options.onError) {
        options.onError(finalError)
      }
    } finally {
      loading.value = false
    }
  }

  const clear = () => {
    if (cacheService) {
      // Clear all pages for this query
      const pattern = new RegExp(`^${baseKeyComputed.value}_page_`)
      cacheService.invalidatePattern(pattern)
    }
    pages.value = []
    error.value = null
    isStale.value = false
    currentPageParam.value = options.initialPageParam
  }

  const mutate = (newData: T[] | ((current: T[]) => T[])) => {
    const updatedData =
      typeof newData === 'function' ? (newData as (current: T[]) => T[])(data.value) : newData

    // For simplicity, replace all pages with single page containing all data
    pages.value = [updatedData]
  }

  // Initial load
  if (options.immediate !== false && isEnabled.value) {
    refresh()
  }

  return {
    data,
    loading,
    error,
    isStale,
    hasNextPage,
    isFetchingNextPage,
    fetchNextPage,
    refresh,
    clear,
    mutate,
  }
}

/**
 * Composable for cache statistics and management
 */
export function useRequestCacheStats() {
  const cacheService = getRequestCacheService()
  const stats = ref<CacheStats | null>(null)

  const updateStats = () => {
    if (cacheService) {
      stats.value = cacheService.getStats()
    }
  }

  const clearCache = () => {
    cacheService?.clear()
    updateStats()
  }

  const invalidatePattern = (pattern: string | RegExp): number => {
    const count = cacheService?.invalidatePattern(pattern) || 0
    updateStats()
    return count
  }

  const getCacheKeys = (): string[] => {
    return cacheService?.getKeys() || []
  }

  // Update stats initially and set up interval
  updateStats()
  const interval = setInterval(updateStats, 5000) // Update every 5 seconds

  onUnmounted(() => {
    clearInterval(interval)
  })

  return {
    stats: computed(() => stats.value),
    clearCache,
    invalidatePattern,
    getCacheKeys,
    updateStats,
  }
}

/**
 * Utility composable for cache key generation
 */
export function useCacheKey() {
  const generateKey = (
    baseKey: string,
    params?: Record<string, any>,
    userContext?: { id?: string | number; role?: string }
  ): string => {
    let key = baseKey

    if (params && Object.keys(params).length > 0) {
      const sortedParams = Object.keys(params)
        .sort()
        .map((k) => `${k}=${encodeURIComponent(String(params[k]))}`)
        .join('&')
      key += `?${sortedParams}`
    }

    if (userContext) {
      const userKey = `user:${userContext.id || 'anonymous'}`
      key = `${userKey}:${key}`
    }

    return key
  }

  const generateListKey = (
    resource: string,
    filters?: Record<string, any>,
    sorting?: { field: string; direction: 'asc' | 'desc' },
    pagination?: { page: number; limit: number }
  ): string => {
    const params: Record<string, any> = {}

    if (filters) {
      Object.assign(params, filters)
    }

    if (sorting) {
      params.sort = `${sorting.field}:${sorting.direction}`
    }

    if (pagination) {
      params.page = pagination.page
      params.limit = pagination.limit
    }

    return generateKey(`${resource}:list`, params)
  }

  const generateDetailKey = (resource: string, id: string | number): string => {
    return `${resource}:${id}`
  }

  return {
    generateKey,
    generateListKey,
    generateDetailKey,
  }
}
