import { AxiosError } from 'axios';
import * as Sentry from '@sentry/react';

interface ApiErrorResponse {
  message?: string;
  errors?: Record<string, string[]>;
  error?: string;
  trace?: string; // Stack trace dari backend (dev mode)
  request_id?: string; // Request ID untuk tracking
  file?: string; // File yang error (dev mode)
  line?: number; // Line number (dev mode)
}

/**
 * Error Translation Map - Translasi error backend ke bahasa user-friendly
 */
const ERROR_TRANSLATION_MAP: Record<string, string> = {
  // Database errors
  'SQLSTATE[23505]': 'Data sudah ada, silakan gunakan data lain',
  'SQLSTATE[23000]': 'Data sudah ada atau terjadi duplikasi',
  'SQLSTATE[42S02]': 'Tabel database tidak ditemukan',
  'SQLSTATE[HY000]': 'Terjadi kesalahan database, silakan coba lagi',
  'Duplicate entry': 'Data sudah ada dalam sistem',
  'foreign key constraint': 'Data tidak dapat dihapus karena masih terkait dengan data lain',
  'unique constraint': 'Data sudah ada, silakan gunakan data yang berbeda',

  // Authentication errors
  'Unauthenticated': 'Sesi Anda telah berakhir, silakan login kembali',
  'Unauthorized': 'Sesi Anda telah berakhir, silakan login kembali',
  'Token expired': 'Sesi Anda telah berakhir, silakan login kembali',
  'Invalid token': 'Sesi tidak valid, silakan login kembali',
  'Token has expired': 'Sesi Anda telah berakhir, silakan login kembali',

  // Authorization errors
  'Forbidden': 'Anda tidak memiliki akses untuk melakukan tindakan ini',
  'Access denied': 'Akses ditolak, Anda tidak memiliki izin',
  'Insufficient permissions': 'Anda tidak memiliki izin yang cukup',

  // Network errors
  'Network Error': 'Tidak dapat terhubung ke server, periksa koneksi internet Anda',
  'ERR_NETWORK': 'Tidak dapat terhubung ke server, periksa koneksi internet Anda',
  'ERR_CONNECTION_REFUSED': 'Server tidak dapat dihubungi, silakan coba lagi nanti',
  'ERR_INTERNET_DISCONNECTED': 'Tidak ada koneksi internet, silakan periksa koneksi Anda',
  'timeout': 'Permintaan memakan waktu terlalu lama, silakan coba lagi',
  'ECONNABORTED': 'Koneksi terputus, silakan coba lagi',
  'ETIMEDOUT': 'Waktu permintaan habis, silakan coba lagi',

  // Validation errors
  'The given data was invalid': 'Data yang Anda masukkan tidak valid',
  'Validation failed': 'Validasi gagal, periksa kembali data Anda',
  'required': 'Field ini wajib diisi',
  'email': 'Format email tidak valid',
  'min': 'Nilai terlalu kecil',
  'max': 'Nilai terlalu besar',

  // Business logic errors
  'Not found': 'Data tidak ditemukan',
  'Resource not found': 'Data yang Anda cari tidak ditemukan',
  'already exists': 'Data sudah ada dalam sistem',
  'Invalid credentials': 'Email atau password salah',
  'Wrong password': 'Password salah',
  'User not found': 'Pengguna tidak ditemukan',

  // Face recognition errors
  'Face not detected': 'Wajah tidak terdeteksi, pastikan wajah Anda terlihat jelas',
  'Multiple faces detected': 'Terdeteksi lebih dari satu wajah, pastikan hanya ada satu wajah',
  'Face not recognized': 'Wajah tidak dikenali dalam sistem',
  'Low confidence': 'Kualitas deteksi wajah kurang baik, silakan coba lagi',
  'Poor image quality': 'Kualitas gambar kurang baik, pastikan pencahayaan cukup',
  'Face too far': 'Wajah terlalu jauh dari kamera',
  'Face too close': 'Wajah terlalu dekat dengan kamera',

  // Location errors
  'Location not verified': 'Lokasi tidak dapat diverifikasi',
  'Outside attendance area': 'Anda berada di luar area absensi',
  'GPS permission denied': 'Izin lokasi ditolak, mohon aktifkan izin lokasi',
  'GPS not available': 'GPS tidak tersedia di browser ini',
  'Location unavailable': 'Lokasi tidak tersedia',

  // Attendance errors
  'Already checked in': 'Anda sudah melakukan check-in hari ini',
  'Already checked out': 'Anda sudah melakukan check-out hari ini',
  'Must check in first': 'Anda harus check-in terlebih dahulu',
  'Outside work hours': 'Di luar jam kerja',

  // Server errors
  'Internal Server Error': 'Terjadi kesalahan di server, silakan coba lagi',
  'Service Unavailable': 'Layanan sedang tidak tersedia, silakan coba lagi nanti',
  'Gateway Timeout': 'Server tidak merespons, silakan coba lagi',
  'Bad Gateway': 'Terjadi kesalahan pada server, silakan coba lagi',

  // Rate limiting
  'Too Many Requests': 'Terlalu banyak permintaan, silakan tunggu sebentar',
  'Rate limit exceeded': 'Terlalu banyak permintaan, silakan coba lagi nanti',
};

interface DetailedError {
  message: string;
  userMessage: string; // User-friendly message
  technicalMessage: string; // Technical details
  statusCode?: number;
  requestId?: string;
  endpoint?: string;
  timestamp: string;
  stack?: string;
}

/**
 * Translate error message ke bahasa user-friendly
 * Checks ERROR_TRANSLATION_MAP untuk match (case-insensitive, partial match)
 */
function translateErrorMessage(message: string): string {
  if (!message) return 'Terjadi kesalahan yang tidak diketahui';

  // Exact match (case-insensitive)
  const exactMatch = Object.entries(ERROR_TRANSLATION_MAP).find(
    ([key]) => message.toLowerCase() === key.toLowerCase()
  );
  if (exactMatch) return exactMatch[1];

  // Partial match (contains)
  const partialMatch = Object.entries(ERROR_TRANSLATION_MAP).find(
    ([key]) => message.toLowerCase().includes(key.toLowerCase())
  );
  if (partialMatch) return partialMatch[1];

  // No match found, return original message
  return message;
}

/**
 * Extract detailed error information dari API error
 * Returns both user-friendly message dan technical details
 */
export function getDetailedError(error: unknown): DetailedError {
  const timestamp = new Date().toISOString();

  // Axios error
  if (error instanceof AxiosError) {
    const data = error.response?.data as ApiErrorResponse;
    const statusCode = error.response?.status;
    const endpoint = error.config?.url;

    // Validation errors (422)
    if (statusCode === 422 && data?.errors) {
      const errors = data.errors;
      const firstField = Object.keys(errors)[0];
      const firstError = errors[firstField]?.[0];
      const allErrors = Object.entries(errors)
        .map(([field, messages]) => `${field}: ${messages.join(', ')}`)
        .join('\n');

      const translatedError = firstError ? translateErrorMessage(firstError) : 'Data yang Anda masukkan tidak valid';

      return {
        message: firstError || 'Validation failed',
        userMessage: translatedError,
        technicalMessage: `Validation failed:\n${allErrors}`,
        statusCode,
        requestId: data?.request_id,
        endpoint,
        timestamp,
        stack: data?.trace,
      };
    }

    // Server error dengan detail (500)
    if (statusCode === 500 && data) {
      return {
        message: data.message || 'Server error',
        userMessage: 'Terjadi kesalahan di server. Tim kami telah diberitahu.',
        technicalMessage: [
          data.message || 'Internal Server Error',
          data.file && `File: ${data.file}:${data.line}`,
          data.trace && `Stack: ${data.trace.substring(0, 500)}...`,
        ].filter(Boolean).join('\n'),
        statusCode,
        requestId: data?.request_id,
        endpoint,
        timestamp,
        stack: data?.trace,
      };
    }

    // Business logic error (400, 403, etc.)
    if (data?.message) {
      const translatedMessage = translateErrorMessage(data.message);
      return {
        message: data.message,
        userMessage: translatedMessage,
        technicalMessage: data.message,
        statusCode,
        requestId: data?.request_id,
        endpoint,
        timestamp,
      };
    }

    // HTTP status messages
    const statusMessages: Record<number, { user: string; tech: string }> = {
      401: {
        user: 'Sesi Anda telah berakhir. Silakan login kembali.',
        tech: 'Unauthorized - Token expired or invalid',
      },
      403: {
        user: 'Anda tidak memiliki akses untuk melakukan tindakan ini.',
        tech: 'Forbidden - Insufficient permissions',
      },
      404: {
        user: 'Data yang Anda cari tidak ditemukan.',
        tech: `Not Found - ${endpoint}`,
      },
      429: {
        user: 'Terlalu banyak permintaan. Silakan coba lagi nanti.',
        tech: 'Rate limit exceeded',
      },
      500: {
        user: 'Terjadi kesalahan di server. Silakan coba lagi.',
        tech: 'Internal Server Error',
      },
      503: {
        user: 'Layanan sedang tidak tersedia. Silakan coba lagi nanti.',
        tech: 'Service Unavailable',
      },
    };

    const statusMsg = statusMessages[statusCode || 0] || {
      user: 'Terjadi kesalahan jaringan',
      tech: `HTTP ${statusCode} - ${error.message}`,
    };

    return {
      message: statusMsg.user,
      userMessage: statusMsg.user,
      technicalMessage: statusMsg.tech,
      statusCode,
      endpoint,
      timestamp,
      stack: error.stack,
    };
  }

  // Generic Error object
  if (error instanceof Error) {
    const translatedMessage = translateErrorMessage(error.message);
    return {
      message: error.message,
      userMessage: translatedMessage,
      technicalMessage: error.message,
      timestamp,
      stack: error.stack,
    };
  }

  // Unknown error
  return {
    message: 'An unknown error occurred',
    userMessage: 'Terjadi kesalahan yang tidak diketahui',
    technicalMessage: String(error),
    timestamp,
  };
}

/**
 * Extract clean user-friendly error message
 * Hanya tampilkan pesan untuk user, hide technical details
 */
export function getErrorMessage(error: unknown): string {
  return getDetailedError(error).userMessage;
}

/**
 * Log error dengan detail lengkap untuk debugging
 * Development: Console log dengan colors
 * Production: Send to error tracking service (Sentry/LogRocket)
 */
export function logError(error: unknown, context?: string): void {
  const details = getDetailedError(error);

  if (import.meta.env.DEV) {
    // Development - detailed console logging
    console.group(
      `%c❌ Error${context ? ` - ${context}` : ''}`,
      'color: #ef4444; font-weight: bold; font-size: 14px;'
    );
    console.log('%c📝 User Message:', 'color: #f59e0b; font-weight: bold;', details.userMessage);
    console.log('%c🔧 Technical:', 'color: #3b82f6; font-weight: bold;', details.technicalMessage);
    if (details.statusCode) {
      console.log('%c📊 Status Code:', 'color: #8b5cf6;', details.statusCode);
    }
    if (details.endpoint) {
      console.log('%c🌐 Endpoint:', 'color: #10b981;', details.endpoint);
    }
    if (details.requestId) {
      console.log('%c🔑 Request ID:', 'color: #ec4899;', details.requestId);
    }
    console.log('%c⏰ Timestamp:', 'color: #6b7280;', details.timestamp);
    if (details.stack && import.meta.env.DEV) {
      console.log('%c📚 Stack Trace:', 'color: #6b7280;');
      console.log(details.stack);
    }
    console.groupEnd();
  }

  // Production - send to error tracking service
  if (import.meta.env.PROD) {
    // Report to Sentry
    Sentry.captureException(error, {
      contexts: {
        error_details: {
          userMessage: details.userMessage,
          technicalMessage: details.technicalMessage,
          endpoint: details.endpoint,
          requestId: details.requestId,
          timestamp: details.timestamp,
        },
      },
      tags: {
        context: context || 'unknown',
        status_code: details.statusCode?.toString() || 'unknown',
        endpoint: details.endpoint || 'unknown',
      },
      fingerprint: [details.requestId || details.message],
    });

    // Also log minimal info to console
    console.error('[Error]', {
      context,
      message: details.userMessage,
      requestId: details.requestId,
      timestamp: details.timestamp,
    });
  }
}

/**
 * Format error untuk display di UI (optional)
 * Returns formatted HTML-safe string
 */
export function formatErrorForDisplay(error: unknown): {
  title: string;
  message: string;
  details?: string;
} {
  const details = getDetailedError(error);

  return {
    title: 'Terjadi Kesalahan',
    message: details.userMessage,
    details: import.meta.env.DEV
      ? `${details.technicalMessage}\n${details.endpoint ? `Endpoint: ${details.endpoint}` : ''}\n${details.requestId ? `Request ID: ${details.requestId}` : ''}`
      : undefined,
  };
}

/**
 * Check if error is recoverable (user can retry)
 */
export function isRecoverableError(error: unknown): boolean {
  if (error instanceof AxiosError) {
    const status = error.response?.status;
    // Network errors, timeouts, 5xx errors are recoverable
    return !status || status >= 500 || error.code === 'ECONNABORTED' || error.code === 'ETIMEDOUT';
  }
  return true; // Most errors are recoverable
}

/**
 * Check if error should trigger auto-logout
 */
export function shouldLogout(error: unknown): boolean {
  if (error instanceof AxiosError) {
    return error.response?.status === 401;
  }
  return false;
}
