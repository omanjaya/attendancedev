/**
 * Request Caching Service
 *
 * Provides intelligent caching for API requests with TTL, invalidation,
 * background refresh, and memory management capabilities.
 */

import { reactive, ref } from 'vue'

export interface CacheEntry<T = any> {
  data: T
  timestamp: number
  ttl: number
  key: string
  etag?: string
  lastModified?: string
  refreshPromise?: Promise<T>
  metadata?: Record<string, any>
}

export interface CacheConfig {
  defaultTTL: number // milliseconds
  maxSize: number // maximum number of entries
  enableBackgroundRefresh: boolean
  backgroundRefreshThreshold: number // refresh when TTL is X% expired
  enablePersistence: boolean
  persistenceKey: string
  debugMode: boolean
}

export interface RequestOptions {
  ttl?: number
  force?: boolean // bypass cache
  background?: boolean // allow stale data while refreshing
  etag?: string
  lastModified?: string
  metadata?: Record<string, any>
}

export interface CacheStats {
  hits: number
  misses: number
  evictions: number
  backgroundRefreshes: number
  totalRequests: number
  hitRate: number
  size: number
  memoryUsage: number
}

class RequestCacheService {
  private cache = new Map<string, CacheEntry>()
  private stats = reactive<CacheStats>({
    hits: 0,
    misses: 0,
    evictions: 0,
    backgroundRefreshes: 0,
    totalRequests: 0,
    hitRate: 0,
    size: 0,
    memoryUsage: 0,
  })

  private config: CacheConfig = {
    defaultTTL: 5 * 60 * 1000, // 5 minutes
    maxSize: 100,
    enableBackgroundRefresh: true,
    backgroundRefreshThreshold: 0.8, // refresh when 80% of TTL has passed
    enablePersistence: true,
    persistenceKey: 'app_request_cache',
    debugMode: false,
  }

  private cleanupInterval?: NodeJS.Timeout

  constructor(config: Partial<CacheConfig> = {}) {
    this.config = { ...this.config, ...config }
    this.startCleanupInterval()
    this.loadFromPersistence()
  }

  /**
   * Get cached data or fetch from provided function
   */
  async get<T>(key: string, fetchFn: () => Promise<T>, options: RequestOptions = {}): Promise<T> {
    this.stats.totalRequests++
    this.updateStats()

    const normalizedKey = this.normalizeKey(key)
    const cacheEntry = this.cache.get(normalizedKey)
    const now = Date.now()

    // Check if we have valid cached data
    if (cacheEntry && !this.isExpired(cacheEntry, now) && !options.force) {
      this.stats.hits++
      this.log(`Cache HIT for key: ${normalizedKey}`)

      // Check if we should background refresh
      if (this.shouldBackgroundRefresh(cacheEntry, now)) {
        this.backgroundRefresh(normalizedKey, fetchFn, options)
      }

      return cacheEntry.data
    }

    // Cache miss or expired data
    this.stats.misses++
    this.log(`Cache MISS for key: ${normalizedKey}`)

    // If we have stale data and background refresh is enabled, return stale data
    if (cacheEntry && options.background && this.config.enableBackgroundRefresh) {
      this.log(`Returning stale data and refreshing in background: ${normalizedKey}`)
      this.backgroundRefresh(normalizedKey, fetchFn, options)
      return cacheEntry.data
    }

    // Fetch fresh data
    return this.fetchAndCache(normalizedKey, fetchFn, options)
  }

  /**
   * Set data in cache manually
   */
  set<T>(key: string, data: T, options: RequestOptions = {}): void {
    const normalizedKey = this.normalizeKey(key)
    const ttl = options.ttl || this.config.defaultTTL

    const entry: CacheEntry<T> = {
      data,
      timestamp: Date.now(),
      ttl,
      key: normalizedKey,
      etag: options.etag,
      lastModified: options.lastModified,
      metadata: options.metadata,
    }

    this.cache.set(normalizedKey, entry)
    this.ensureMaxSize()
    this.updateStats()
    this.saveToPersistence()

    this.log(`Cached data for key: ${normalizedKey}`)
  }

  /**
   * Remove specific key from cache
   */
  delete(key: string): boolean {
    const normalizedKey = this.normalizeKey(key)
    const deleted = this.cache.delete(normalizedKey)

    if (deleted) {
      this.updateStats()
      this.saveToPersistence()
      this.log(`Deleted cache entry: ${normalizedKey}`)
    }

    return deleted
  }

  /**
   * Clear all cache entries
   */
  clear(): void {
    this.cache.clear()
    this.updateStats()
    this.saveToPersistence()
    this.log('Cache cleared')
  }

  /**
   * Invalidate cache entries by pattern
   */
  invalidatePattern(pattern: string | RegExp): number {
    const regex = typeof pattern === 'string' ? new RegExp(pattern) : pattern
    let deletedCount = 0

    for (const key of this.cache.keys()) {
      if (regex.test(key)) {
        this.cache.delete(key)
        deletedCount++
      }
    }

    if (deletedCount > 0) {
      this.updateStats()
      this.saveToPersistence()
      this.log(`Invalidated ${deletedCount} cache entries matching pattern: ${pattern}`)
    }

    return deletedCount
  }

  /**
   * Check if key exists in cache and is not expired
   */
  has(key: string): boolean {
    const normalizedKey = this.normalizeKey(key)
    const entry = this.cache.get(normalizedKey)
    return entry ? !this.isExpired(entry) : false
  }

  /**
   * Get cache statistics
   */
  getStats(): CacheStats {
    return { ...this.stats }
  }

  /**
   * Get all cache keys
   */
  getKeys(): string[] {
    return Array.from(this.cache.keys())
  }

  /**
   * Get cache entry for debugging
   */
  getEntry(key: string): CacheEntry | undefined {
    const normalizedKey = this.normalizeKey(key)
    return this.cache.get(normalizedKey)
  }

  /**
   * Prefetch data for given keys
   */
  async prefetch<T>(
    requests: Array<{ key: string; fetchFn: () => Promise<T>; options?: RequestOptions }>
  ): Promise<void> {
    const promises = requests.map(({ key, fetchFn, options }) =>
      this.get(key, fetchFn, { ...options, background: true }).catch((error) => {
        console.warn(`Prefetch failed for key ${key}:`, error)
      })
    )

    await Promise.allSettled(promises)
    this.log(`Prefetched ${requests.length} cache entries`)
  }

  /**
   * Warm up cache with provided data
   */
  warmUp<T>(entries: Array<{ key: string; data: T; options?: RequestOptions }>): void {
    entries.forEach(({ key, data, options }) => {
      this.set(key, data, options)
    })

    this.log(`Warmed up cache with ${entries.length} entries`)
  }

  /**
   * Private methods
   */

  private async fetchAndCache<T>(
    key: string,
    fetchFn: () => Promise<T>,
    options: RequestOptions
  ): Promise<T> {
    try {
      this.log(`Fetching fresh data for key: ${key}`)
      const data = await fetchFn()
      this.set(key, data, options)
      return data
    } catch (error) {
      this.log(`Fetch failed for key: ${key}`, error)
      throw error
    }
  }

  private async backgroundRefresh<T>(
    key: string,
    fetchFn: () => Promise<T>,
    options: RequestOptions
  ): Promise<void> {
    const entry = this.cache.get(key)

    // Prevent multiple background refreshes for the same key
    if (entry?.refreshPromise) {
      return entry.refreshPromise
    }

    this.stats.backgroundRefreshes++
    this.log(`Starting background refresh for key: ${key}`)

    const refreshPromise = this.fetchAndCache(key, fetchFn, options)
      .catch((error) => {
        this.log(`Background refresh failed for key: ${key}`, error)
        // Don't throw error in background refresh
      })
      .finally(() => {
        // Clear the refresh promise
        const currentEntry = this.cache.get(key)
        if (currentEntry) {
          delete currentEntry.refreshPromise
        }
      })

    // Store the refresh promise to prevent multiple refreshes
    if (entry) {
      entry.refreshPromise = refreshPromise
    }

    await refreshPromise
  }

  private isExpired(entry: CacheEntry, now: number = Date.now()): boolean {
    return now - entry.timestamp > entry.ttl
  }

  private shouldBackgroundRefresh(entry: CacheEntry, now: number = Date.now()): boolean {
    if (!this.config.enableBackgroundRefresh) {return false}

    const elapsed = now - entry.timestamp
    const threshold = entry.ttl * this.config.backgroundRefreshThreshold

    return elapsed > threshold && !entry.refreshPromise
  }

  private normalizeKey(key: string): string {
    // Remove query parameters that don't affect the response
    // You can customize this based on your API structure
    return key.replace(/[?&](timestamp|_t|nocache)=[^&]*/g, '').toLowerCase()
  }

  private ensureMaxSize(): void {
    if (this.cache.size <= this.config.maxSize) {return}

    // Remove oldest entries (LRU-style)
    const entries = Array.from(this.cache.entries())
    entries.sort((a, b) => a[1].timestamp - b[1].timestamp)

    const toRemove = entries.slice(0, this.cache.size - this.config.maxSize)
    toRemove.forEach(([key]) => {
      this.cache.delete(key)
      this.stats.evictions++
    })

    this.log(`Evicted ${toRemove.length} cache entries due to size limit`)
  }

  private updateStats(): void {
    this.stats.size = this.cache.size
    this.stats.hitRate =
      this.stats.totalRequests > 0 ? (this.stats.hits / this.stats.totalRequests) * 100 : 0
    this.stats.memoryUsage = this.estimateMemoryUsage()
  }

  private estimateMemoryUsage(): number {
    // Rough estimate of memory usage in bytes
    let totalSize = 0

    for (const entry of this.cache.values()) {
      totalSize += JSON.stringify(entry.data).length * 2 // rough estimate
      totalSize += entry.key.length * 2
      totalSize += 100 // overhead for timestamps, etc.
    }

    return totalSize
  }

  private startCleanupInterval(): void {
    // Clean up expired entries every 5 minutes
    this.cleanupInterval = setInterval(
      () => {
        this.cleanupExpired()
      },
      5 * 60 * 1000
    )
  }

  private cleanupExpired(): void {
    const now = Date.now()
    let cleanedCount = 0

    for (const [key, entry] of this.cache.entries()) {
      if (this.isExpired(entry, now)) {
        this.cache.delete(key)
        cleanedCount++
      }
    }

    if (cleanedCount > 0) {
      this.updateStats()
      this.saveToPersistence()
      this.log(`Cleaned up ${cleanedCount} expired cache entries`)
    }
  }

  private saveToPersistence(): void {
    if (!this.config.enablePersistence) {return}

    try {
      const serializable = Array.from(this.cache.entries()).map(([key, entry]) => ({
        key,
        data: entry.data,
        timestamp: entry.timestamp,
        ttl: entry.ttl,
        etag: entry.etag,
        lastModified: entry.lastModified,
        metadata: entry.metadata,
      }))

      localStorage.setItem(this.config.persistenceKey, JSON.stringify(serializable))
    } catch (error) {
      console.warn('Failed to save cache to persistence:', error)
    }
  }

  private loadFromPersistence(): void {
    if (!this.config.enablePersistence) {return}

    try {
      const data = localStorage.getItem(this.config.persistenceKey)
      if (!data) {return}

      const entries = JSON.parse(data)
      const now = Date.now()

      entries.forEach((entry: any) => {
        // Only load non-expired entries
        if (!this.isExpired(entry, now)) {
          this.cache.set(entry.key, {
            data: entry.data,
            timestamp: entry.timestamp,
            ttl: entry.ttl,
            key: entry.key,
            etag: entry.etag,
            lastModified: entry.lastModified,
            metadata: entry.metadata,
          })
        }
      })

      this.updateStats()
      this.log(`Loaded ${this.cache.size} cache entries from persistence`)
    } catch (error) {
      console.warn('Failed to load cache from persistence:', error)
    }
  }

  private log(message: string, data?: any): void {
    if (this.config.debugMode) {
      console.log(`[RequestCache] ${message}`, data || '')
    }
  }

  /**
   * Cleanup when service is destroyed
   */
  destroy(): void {
    if (this.cleanupInterval) {
      clearInterval(this.cleanupInterval)
    }
    this.saveToPersistence()
  }
}

// Create singleton instance
let requestCacheService: RequestCacheService | null = null

export function createRequestCacheService(config?: Partial<CacheConfig>): RequestCacheService {
  if (requestCacheService) {
    console.warn('[RequestCache] Service already created')
    return requestCacheService
  }

  requestCacheService = new RequestCacheService(config)
  return requestCacheService
}

export function getRequestCacheService(): RequestCacheService | null {
  return requestCacheService
}

// Convenience functions
export function cachedRequest<T>(
  key: string,
  fetchFn: () => Promise<T>,
  options?: RequestOptions
): Promise<T> {
  if (!requestCacheService) {
    console.warn('[RequestCache] Service not initialized, falling back to direct fetch')
    return fetchFn()
  }

  return requestCacheService.get(key, fetchFn, options)
}

export function invalidateCache(pattern: string | RegExp): number {
  if (!requestCacheService) {return 0}
  return requestCacheService.invalidatePattern(pattern)
}

export function clearCache(): void {
  requestCacheService?.clear()
}

export function getCacheStats(): CacheStats | null {
  return requestCacheService?.getStats() || null
}

export type { RequestCacheService, CacheEntry, CacheConfig, RequestOptions, CacheStats }
