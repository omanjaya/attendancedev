import { useCallback } from 'react';
import { useNavigate } from '@tanstack/react-router';
import { toast } from 'sonner';
import {
  getDetailedError,
  getErrorMessage,
  logError,
  shouldLogout,
  isRecoverableError,
} from '@/lib/utils/error';

/**
 * Configuration untuk error handler
 */
interface ErrorHandlerOptions {
  /**
   * Show toast notification untuk error
   * Default: true
   */
  showToast?: boolean;

  /**
   * Context untuk logging (membantu debugging)
   * Example: 'Login Form', 'Check-in Process'
   */
  context?: string;

  /**
   * Custom callback setelah error handled
   */
  onError?: (error: unknown) => void;

  /**
   * Auto-logout pada authentication error (401)
   * Default: true
   */
  autoLogout?: boolean;

  /**
   * Custom error message override
   */
  customMessage?: string;

  /**
   * Toast duration in milliseconds
   * Default: 5000 (5 seconds)
   */
  toastDuration?: number;
}

/**
 * Global Error Handler Hook
 *
 * Provides centralized error handling dengan:
 * - User-friendly error messages
 * - Auto-logout pada authentication errors
 * - Error logging ke Sentry (production)
 * - Toast notifications
 * - Error recovery suggestions
 *
 * @example
 * ```tsx
 * const { handleError } = useErrorHandler();
 *
 * try {
 *   await checkIn();
 * } catch (error) {
 *   handleError(error, {
 *     context: 'Check-in Process',
 *     showToast: true
 *   });
 * }
 * ```
 */
export function useErrorHandler() {
  const navigate = useNavigate();

  /**
   * Handle error dengan berbagai options
   */
  const handleError = useCallback(
    (error: unknown, options: ErrorHandlerOptions = {}) => {
      const {
        showToast = true,
        context,
        onError,
        autoLogout = true,
        customMessage,
        toastDuration = 5000,
      } = options;

      // Extract detailed error info
      const errorDetails = getDetailedError(error);

      // Log error untuk debugging
      logError(error, context);

      // Get user-friendly message
      const userMessage = customMessage || errorDetails.userMessage;

      // Show toast notification
      if (showToast) {
        const isRecoverable = isRecoverableError(error);

        toast.error(userMessage, {
          description: errorDetails.requestId
            ? `ID: ${errorDetails.requestId}`
            : isRecoverable
              ? 'Silakan coba lagi'
              : undefined,
          duration: toastDuration,
          action: isRecoverable
            ? {
                label: 'Coba Lagi',
                onClick: () => {
                  // Caller should handle retry logic
                  console.log('[Error Handler] Retry clicked');
                },
              }
            : undefined,
        });
      }

      // Auto-logout on authentication error
      if (autoLogout && shouldLogout(error)) {
        toast.info('Mengalihkan ke halaman login...', {
          duration: 2000,
        });

        // Clear auth state
        localStorage.removeItem('auth_token');
        sessionStorage.clear();

        // Navigate to login after short delay
        setTimeout(() => {
          navigate({ to: '/login' });
        }, 2000);
      }

      // Call custom error callback
      if (onError) {
        onError(error);
      }

      return errorDetails;
    },
    [navigate]
  );

  /**
   * Handle error dengan silent mode (no toast)
   * Useful untuk background operations
   */
  const handleSilentError = useCallback(
    (error: unknown, context?: string) => {
      return handleError(error, {
        showToast: false,
        context,
        autoLogout: false,
      });
    },
    [handleError]
  );

  /**
   * Handle error dengan custom message
   */
  const handleErrorWithMessage = useCallback(
    (error: unknown, message: string, context?: string) => {
      return handleError(error, {
        customMessage: message,
        context,
      });
    },
    [handleError]
  );

  /**
   * Get just the user-friendly message (tanpa handling)
   */
  const getErrorMessageOnly = useCallback((error: unknown): string => {
    return getErrorMessage(error);
  }, []);

  /**
   * Check if error is recoverable (user can retry)
   */
  const checkRecoverable = useCallback((error: unknown): boolean => {
    return isRecoverableError(error);
  }, []);

  return {
    handleError,
    handleSilentError,
    handleErrorWithMessage,
    getErrorMessage: getErrorMessageOnly,
    isRecoverableError: checkRecoverable,
  };
}

/**
 * Type export untuk external usage
 */
export type { ErrorHandlerOptions };
