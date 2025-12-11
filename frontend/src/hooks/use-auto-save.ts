/**
 * useAutoSave Hook
 *
 * Custom hook untuk auto-save dengan debounce dan retry mechanism.
 * Digunakan di Grade Schedule Builder untuk menyimpan perubahan otomatis.
 */

import { useState, useEffect, useRef, useCallback } from 'react';

export type AutoSaveStatus = 'idle' | 'pending' | 'saving' | 'saved' | 'error';

export interface UseAutoSaveOptions {
  /** Debounce delay in milliseconds (default: 2000) */
  debounceMs?: number;
  /** Save callback function */
  onSave: () => Promise<void>;
  /** Error callback function */
  onError?: (error: Error) => void;
  /** Success callback function */
  onSuccess?: () => void;
  /** Enable/disable auto-save (default: true) */
  enabled?: boolean;
  /** Max retry attempts on error (default: 3) */
  maxRetries?: number;
  /** Retry delay in milliseconds (default: 1000) */
  retryDelayMs?: number;
}

export interface UseAutoSaveReturn {
  /** Current status of auto-save */
  status: AutoSaveStatus;
  /** Last successful save timestamp */
  lastSavedAt: Date | null;
  /** Force immediate save */
  saveNow: () => Promise<void>;
  /** Current error if any */
  error: Error | null;
  /** Retry count for current save attempt */
  retryCount: number;
  /** Reset error state */
  clearError: () => void;
}

export function useAutoSave(
  hasChanges: boolean,
  options: UseAutoSaveOptions
): UseAutoSaveReturn {
  const {
    debounceMs = 2000,
    onSave,
    onError,
    onSuccess,
    enabled = true,
    maxRetries = 3,
    retryDelayMs = 1000,
  } = options;

  const [status, setStatus] = useState<AutoSaveStatus>('idle');
  const [lastSavedAt, setLastSavedAt] = useState<Date | null>(null);
  const [error, setError] = useState<Error | null>(null);
  const [retryCount, setRetryCount] = useState(0);

  const debounceTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const retryTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const isSavingRef = useRef(false);
  const pendingChangesRef = useRef(false);

  // Clear all timers on unmount
  useEffect(() => {
    return () => {
      if (debounceTimerRef.current) {
        clearTimeout(debounceTimerRef.current);
      }
      if (retryTimerRef.current) {
        clearTimeout(retryTimerRef.current);
      }
    };
  }, []);

  // Internal save function with retry logic
  const performSave = useCallback(async (currentRetry = 0): Promise<void> => {
    if (isSavingRef.current) {
      pendingChangesRef.current = true;
      return;
    }

    isSavingRef.current = true;
    setStatus('saving');
    setRetryCount(currentRetry);

    try {
      await onSave();

      isSavingRef.current = false;
      setStatus('saved');
      setLastSavedAt(new Date());
      setError(null);
      setRetryCount(0);
      onSuccess?.();

      // If there were pending changes during save, trigger another save
      if (pendingChangesRef.current) {
        pendingChangesRef.current = false;
        debounceTimerRef.current = setTimeout(() => {
          performSave(0);
        }, debounceMs);
      }
    } catch (err) {
      isSavingRef.current = false;
      const saveError = err instanceof Error ? err : new Error(String(err));

      if (currentRetry < maxRetries - 1) {
        // Retry after delay
        setStatus('saving');
        retryTimerRef.current = setTimeout(() => {
          performSave(currentRetry + 1);
        }, retryDelayMs);
      } else {
        // Max retries reached
        setStatus('error');
        setError(saveError);
        onError?.(saveError);
      }
    }
  }, [onSave, onSuccess, onError, maxRetries, retryDelayMs, debounceMs]);

  // Force immediate save
  const saveNow = useCallback(async () => {
    // Clear pending debounce timer
    if (debounceTimerRef.current) {
      clearTimeout(debounceTimerRef.current);
      debounceTimerRef.current = null;
    }

    await performSave(0);
  }, [performSave]);

  // Clear error state
  const clearError = useCallback(() => {
    setError(null);
    setStatus('idle');
    setRetryCount(0);
  }, []);

  // Watch for changes and trigger debounced save
  useEffect(() => {
    if (!enabled) return;

    if (hasChanges) {
      setStatus('pending');

      // Clear existing timer
      if (debounceTimerRef.current) {
        clearTimeout(debounceTimerRef.current);
      }

      // Set new debounce timer
      debounceTimerRef.current = setTimeout(() => {
        performSave(0);
      }, debounceMs);
    }

    return () => {
      if (debounceTimerRef.current) {
        clearTimeout(debounceTimerRef.current);
      }
    };
  }, [hasChanges, enabled, debounceMs, performSave]);

  return {
    status,
    lastSavedAt,
    saveNow,
    error,
    retryCount,
    clearError,
  };
}
