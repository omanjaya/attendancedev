/**
 * Image Compression Utilities
 *
 * Utilities for compressing images before upload to reduce bandwidth.
 */

export interface CompressImageOptions {
  /**
   * Maximum width in pixels
   * @default 640
   */
  maxWidth?: number;

  /**
   * Maximum height in pixels
   * @default 480
   */
  maxHeight?: number;

  /**
   * JPEG quality (0-1)
   * @default 0.8
   */
  quality?: number;

  /**
   * Output format
   * @default 'image/jpeg'
   */
  mimeType?: string;
}

/**
 * Compress image from canvas or video element
 *
 * @param source - Canvas or Video element containing the image
 * @param options - Compression options
 * @returns Base64 encoded compressed image (data URL)
 */
export async function compressImage(
  source: HTMLCanvasElement | HTMLVideoElement,
  options: CompressImageOptions = {}
): Promise<string> {
  const {
    maxWidth = 640,
    maxHeight = 480,
    quality = 0.8,
    mimeType = 'image/jpeg',
  } = options;

  // Create canvas if source is video
  let canvas: HTMLCanvasElement;
  if (source instanceof HTMLVideoElement) {
    canvas = document.createElement('canvas');
    const { videoWidth, videoHeight } = source;

    // Calculate scaled dimensions
    const scale = Math.min(maxWidth / videoWidth, maxHeight / videoHeight, 1);
    canvas.width = videoWidth * scale;
    canvas.height = videoHeight * scale;

    const ctx = canvas.getContext('2d');
    if (!ctx) {
      throw new Error('Failed to get canvas context');
    }

    ctx.drawImage(source, 0, 0, canvas.width, canvas.height);
  } else {
    canvas = source;

    // Resize if needed
    if (canvas.width > maxWidth || canvas.height > maxHeight) {
      const scale = Math.min(maxWidth / canvas.width, maxHeight / canvas.height);
      const resizedCanvas = document.createElement('canvas');
      resizedCanvas.width = canvas.width * scale;
      resizedCanvas.height = canvas.height * scale;

      const ctx = resizedCanvas.getContext('2d');
      if (!ctx) {
        throw new Error('Failed to get canvas context');
      }

      ctx.drawImage(canvas, 0, 0, resizedCanvas.width, resizedCanvas.height);
      canvas = resizedCanvas;
    }
  }

  // Compress and convert to data URL
  return canvas.toDataURL(mimeType, quality);
}

/**
 * Get estimated size of compressed image in bytes
 *
 * @param dataUrl - Base64 data URL
 * @returns Size in bytes
 */
export function getImageSize(dataUrl: string): number {
  // Remove data URL prefix (e.g., "data:image/jpeg;base64,")
  const base64 = dataUrl.split(',')[1] || dataUrl;

  // Calculate size: base64 is ~4/3 of actual size
  // Padding characters (=) don't count
  const padding = (base64.match(/=/g) || []).length;
  return Math.floor((base64.length * 3 / 4) - padding);
}

/**
 * Format size in human-readable format
 *
 * @param bytes - Size in bytes
 * @returns Formatted string (e.g., "150 KB")
 */
export function formatSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
}

/**
 * Capture and compress image from video stream
 *
 * @param video - Video element with active stream
 * @param options - Compression options
 * @returns Compressed image data URL and metadata
 */
export async function captureAndCompress(
  video: HTMLVideoElement,
  options: CompressImageOptions = {}
): Promise<{
  dataUrl: string;
  size: number;
  formattedSize: string;
  width: number;
  height: number;
}> {
  const dataUrl = await compressImage(video, options);
  const size = getImageSize(dataUrl);

  // Calculate final dimensions
  const {
    maxWidth = 640,
    maxHeight = 480,
  } = options;

  const scale = Math.min(
    maxWidth / video.videoWidth,
    maxHeight / video.videoHeight,
    1
  );

  return {
    dataUrl,
    size,
    formattedSize: formatSize(size),
    width: Math.floor(video.videoWidth * scale),
    height: Math.floor(video.videoHeight * scale),
  };
}
