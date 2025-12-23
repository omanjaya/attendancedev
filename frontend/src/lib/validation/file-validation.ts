/**
 * File Validation Utilities
 *
 * Provides client-side validation for file uploads including:
 * - File size validation
 * - File type/MIME type validation
 * - Image dimension validation
 *
 * Usage:
 * ```typescript
 * import { validateFile, validateImage, FileValidationRules } from '@/lib/validation/file-validation';
 *
 * const rules: FileValidationRules = {
 *   maxSize: 2 * 1024 * 1024, // 2MB
 *   allowedTypes: ['image/jpeg', 'image/png', 'image/webp'],
 *   maxWidth: 1920,
 *   maxHeight: 1080,
 * };
 *
 * const result = await validateImage(file, rules);
 * if (!result.valid) {
 *   console.error(result.error);
 * }
 * ```
 */

export interface FileValidationRules {
  /** Maximum file size in bytes */
  maxSize?: number;
  /** Minimum file size in bytes */
  minSize?: number;
  /** Allowed MIME types */
  allowedTypes?: string[];
  /** Allowed file extensions (e.g., ['.jpg', '.png']) */
  allowedExtensions?: string[];
  /** Maximum image width in pixels (for images only) */
  maxWidth?: number;
  /** Maximum image height in pixels (for images only) */
  maxHeight?: number;
  /** Minimum image width in pixels (for images only) */
  minWidth?: number;
  /** Minimum image height in pixels (for images only) */
  minHeight?: number;
}

export interface FileValidationResult {
  valid: boolean;
  error?: string;
  metadata?: {
    size: number;
    type: string;
    name: string;
    width?: number;
    height?: number;
  };
}

/**
 * Validate a file against the provided rules
 */
export function validateFile(
  file: File,
  rules: FileValidationRules = {}
): FileValidationResult {
  const {
    maxSize,
    minSize,
    allowedTypes,
    allowedExtensions,
  } = rules;

  // Check file size
  if (maxSize !== undefined && file.size > maxSize) {
    return {
      valid: false,
      error: `Ukuran file terlalu besar. Maksimal ${formatFileSize(maxSize)}`,
    };
  }

  if (minSize !== undefined && file.size < minSize) {
    return {
      valid: false,
      error: `Ukuran file terlalu kecil. Minimal ${formatFileSize(minSize)}`,
    };
  }

  // Check MIME type
  if (allowedTypes && !allowedTypes.includes(file.type)) {
    return {
      valid: false,
      error: `Tipe file tidak didukung. Gunakan: ${allowedTypes.join(', ')}`,
    };
  }

  // Check file extension
  if (allowedExtensions) {
    const extension = '.' + file.name.split('.').pop()?.toLowerCase();
    if (!allowedExtensions.includes(extension)) {
      return {
        valid: false,
        error: `Ekstensi file tidak didukung. Gunakan: ${allowedExtensions.join(', ')}`,
      };
    }
  }

  return {
    valid: true,
    metadata: {
      size: file.size,
      type: file.type,
      name: file.name,
    },
  };
}

/**
 * Validate an image file (includes dimension checks)
 */
export async function validateImage(
  file: File,
  rules: FileValidationRules = {}
): Promise<FileValidationResult> {
  // First validate basic file rules
  const basicValidation = validateFile(file, rules);
  if (!basicValidation.valid) {
    return basicValidation;
  }

  // Check if it's an image
  if (!file.type.startsWith('image/')) {
    return {
      valid: false,
      error: 'File bukan gambar yang valid',
    };
  }

  // Check dimensions if rules provided
  if (
    rules.maxWidth !== undefined ||
    rules.maxHeight !== undefined ||
    rules.minWidth !== undefined ||
    rules.minHeight !== undefined
  ) {
    try {
      const dimensions = await getImageDimensions(file);

      if (rules.maxWidth !== undefined && dimensions.width > rules.maxWidth) {
        return {
          valid: false,
          error: `Lebar gambar terlalu besar. Maksimal ${rules.maxWidth}px`,
        };
      }

      if (rules.maxHeight !== undefined && dimensions.height > rules.maxHeight) {
        return {
          valid: false,
          error: `Tinggi gambar terlalu besar. Maksimal ${rules.maxHeight}px`,
        };
      }

      if (rules.minWidth !== undefined && dimensions.width < rules.minWidth) {
        return {
          valid: false,
          error: `Lebar gambar terlalu kecil. Minimal ${rules.minWidth}px`,
        };
      }

      if (rules.minHeight !== undefined && dimensions.height < rules.minHeight) {
        return {
          valid: false,
          error: `Tinggi gambar terlalu kecil. Minimal ${rules.minHeight}px`,
        };
      }

      return {
        valid: true,
        metadata: {
          size: file.size,
          type: file.type,
          name: file.name,
          width: dimensions.width,
          height: dimensions.height,
        },
      };
    } catch (_error) {
      return {
        valid: false,
        error: 'Gagal membaca dimensi gambar',
      };
    }
  }

  return {
    valid: true,
    metadata: {
      size: file.size,
      type: file.type,
      name: file.name,
    },
  };
}

/**
 * Get image dimensions from a File object
 */
function getImageDimensions(file: File): Promise<{ width: number; height: number }> {
  return new Promise((resolve, reject) => {
    const img = new Image();
    const url = URL.createObjectURL(file);

    img.onload = () => {
      URL.revokeObjectURL(url);
      resolve({
        width: img.width,
        height: img.height,
      });
    };

    img.onerror = () => {
      URL.revokeObjectURL(url);
      reject(new Error('Failed to load image'));
    };

    img.src = url;
  });
}

/**
 * Format file size in human-readable format
 */
export function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 Bytes';

  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));

  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
}

/**
 * Common file validation presets
 */
export const FileValidationPresets = {
  /** Avatar/profile image validation */
  AVATAR: {
    maxSize: 2 * 1024 * 1024, // 2MB
    allowedTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'],
    allowedExtensions: ['.jpg', '.jpeg', '.png', '.webp'],
    maxWidth: 2000,
    maxHeight: 2000,
    minWidth: 100,
    minHeight: 100,
  } as FileValidationRules,

  /** Document upload validation */
  DOCUMENT: {
    maxSize: 5 * 1024 * 1024, // 5MB
    allowedTypes: ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'],
    allowedExtensions: ['.pdf', '.jpg', '.jpeg', '.png'],
  } as FileValidationRules,

  /** Excel/spreadsheet validation */
  EXCEL: {
    maxSize: 10 * 1024 * 1024, // 10MB
    allowedTypes: [
      'application/vnd.ms-excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ],
    allowedExtensions: ['.xls', '.xlsx'],
  } as FileValidationRules,

  /** General image validation */
  IMAGE: {
    maxSize: 5 * 1024 * 1024, // 5MB
    allowedTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'],
    allowedExtensions: ['.jpg', '.jpeg', '.png', '.webp', '.gif'],
  } as FileValidationRules,
};
