import { useRef, useState, useCallback } from 'react';

/**
 * Simple camera capture hook for DeepFace integration
 * No client-side ML processing - all processing happens server-side
 */
export function useCameraCapture() {
  const videoRef = useRef<HTMLVideoElement>(null);
  const streamRef = useRef<MediaStream | null>(null);

  const [cameraStatus, setCameraStatus] = useState<'idle' | 'starting' | 'active' | 'error'>('idle');
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  /**
   * Start camera with default constraints
   */
  const startCamera = useCallback(async () => {
    setCameraStatus('starting');
    setErrorMessage(null);

    try {
      const stream = await navigator.mediaDevices.getUserMedia({
        video: {
          width: { ideal: 1280, max: 1920 },
          height: { ideal: 720, max: 1080 },
          facingMode: 'user',
        },
        audio: false,
      });

      if (videoRef.current) {
        videoRef.current.srcObject = stream;
        streamRef.current = stream;

        await new Promise<void>((resolve) => {
          if (videoRef.current) {
            videoRef.current.onloadedmetadata = () => {
              videoRef.current?.play();
              resolve();
            };
          }
        });

        setCameraStatus('active');
      }
    } catch (error) {
      console.error('Failed to start camera:', error);
      setCameraStatus('error');
      setErrorMessage(
        error instanceof Error
          ? error.message
          : 'Gagal mengakses kamera. Pastikan izin kamera telah diberikan.'
      );
    }
  }, []);

  /**
   * Stop camera and release stream
   */
  const stopCamera = useCallback(() => {
    if (streamRef.current) {
      streamRef.current.getTracks().forEach((track) => track.stop());
      streamRef.current = null;
    }
    if (videoRef.current) {
      videoRef.current.srcObject = null;
    }
    setCameraStatus('idle');
  }, []);

  /**
   * Capture image from video as File object
   * Suitable for uploading to DeepFace API
   */
  const captureImage = useCallback(async (): Promise<File> => {
    if (!videoRef.current || cameraStatus !== 'active') {
      throw new Error('Camera tidak aktif');
    }

    const video = videoRef.current;
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    const ctx = canvas.getContext('2d');
    if (!ctx) {
      throw new Error('Gagal membuat canvas context');
    }

    // Draw current video frame to canvas
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    // Convert canvas to blob, then to File
    return new Promise((resolve, reject) => {
      canvas.toBlob((blob) => {
        if (blob) {
          const file = new File([blob], 'capture.jpg', { type: 'image/jpeg' });
          resolve(file);
        } else {
          reject(new Error('Gagal mengambil gambar'));
        }
      }, 'image/jpeg', 0.95);
    });
  }, [cameraStatus]);

  /**
   * Capture image as base64 data URL
   * Alternative format for some APIs
   */
  const captureImageAsBase64 = useCallback(async (): Promise<string> => {
    if (!videoRef.current || cameraStatus !== 'active') {
      throw new Error('Camera tidak aktif');
    }

    const video = videoRef.current;
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    const ctx = canvas.getContext('2d');
    if (!ctx) {
      throw new Error('Gagal membuat canvas context');
    }

    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    return canvas.toDataURL('image/jpeg', 0.95);
  }, [cameraStatus]);

  return {
    videoRef,
    cameraStatus,
    errorMessage,
    startCamera,
    stopCamera,
    captureImage,
    captureImageAsBase64,
  };
}
