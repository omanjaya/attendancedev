/**
 * Client-Side Face Detection Service
 *
 * Uses @vladmandic/face-api for lightweight, fast face detection in browser.
 * No server calls needed for detection - only for final verification.
 *
 * Easy to swap with other libraries (pico.js, etc) by implementing the same interface.
 *
 * @module client-face-detection
 */

import * as faceapi from '@vladmandic/face-api';

export interface FaceDetectionBox {
  x: number;
  y: number;
  width: number;
  height: number;
}

export interface FaceExpressions {
  neutral: number;
  happy: number;
  sad: number;
  angry: number;
  fearful: number;
  disgusted: number;
  surprised: number;
}

export interface ClientFaceDetection {
  box: FaceDetectionBox;
  score: number;
  expressions?: FaceExpressions;
}

export interface ClientFaceDetectionResult {
  detected: boolean;
  faces: ClientFaceDetection[];
  confidence: number;
  isSmiling?: boolean;
  smileScore?: number;
}

// Detection provider interface - easy to swap implementations
export interface IFaceDetectionProvider {
  initialize(): Promise<void>;
  detect(video: HTMLVideoElement): Promise<ClientFaceDetectionResult>;
  isReady(): boolean;
  destroy(): void;
}

/**
 * Face-API.js Provider (vladmandic fork)
 * Uses TinyFaceDetector for fast, lightweight detection
 * Includes expression detection for smile/liveness check
 */
class FaceApiProvider implements IFaceDetectionProvider {
  private initialized = false;
  private modelPath = '/models/face-api';
  private expressionsLoaded = false;

  async initialize(): Promise<void> {
    if (this.initialized) return;

    try {
      // Load TinyFaceDetector model (smallest, fastest)
      await faceapi.nets.tinyFaceDetector.loadFromUri(this.modelPath);
      console.log('FaceAPI TinyFaceDetector loaded');

      // Load expression model for smile detection
      try {
        await faceapi.nets.faceExpressionNet.loadFromUri(this.modelPath);
        this.expressionsLoaded = true;
        console.log('FaceAPI Expression model loaded');
      } catch (err) {
        console.warn('Expression model not loaded, smile detection disabled:', err);
      }

      this.initialized = true;
    } catch (error) {
      console.error('Failed to load face-api models:', error);
      throw new Error('Gagal memuat model deteksi wajah');
    }
  }

  async detect(video: HTMLVideoElement): Promise<ClientFaceDetectionResult> {
    if (!this.initialized) {
      return { detected: false, faces: [], confidence: 0 };
    }

    try {
      // Use TinyFaceDetector with optimized settings
      const options = new faceapi.TinyFaceDetectorOptions({
        inputSize: 224,      // Smaller = faster (128, 160, 224, 320, 416, 512, 608)
        scoreThreshold: 0.5  // Minimum confidence
      });

      let detections: faceapi.FaceDetection[];
      let expressionResults: faceapi.WithFaceExpressions<faceapi.WithFaceDetection<{}>>[] | null = null;

      if (this.expressionsLoaded) {
        // Detect faces with expressions
        expressionResults = await faceapi
          .detectAllFaces(video, options)
          .withFaceExpressions();
        detections = expressionResults.map(r => r.detection);
      } else {
        // Detect faces only
        detections = await faceapi.detectAllFaces(video, options);
      }

      if (detections.length === 0) {
        return { detected: false, faces: [], confidence: 0 };
      }

      // Get highest confidence detection
      const bestIdx = detections.reduce((bestI: number, current: faceapi.FaceDetection, i: number, arr: faceapi.FaceDetection[]) =>
        current.score > arr[bestI].score ? i : bestI, 0
      );
      const bestDetection = detections[bestIdx];

      // Map faces with expressions
      const faces: ClientFaceDetection[] = detections.map((d: faceapi.FaceDetection, i: number) => {
        const face: ClientFaceDetection = {
          box: {
            x: d.box.x,
            y: d.box.y,
            width: d.box.width,
            height: d.box.height
          },
          score: d.score
        };

        // Add expressions if available
        if (expressionResults !== null && expressionResults[i]?.expressions) {
          const exp = expressionResults[i].expressions;
          face.expressions = {
            neutral: exp.neutral,
            happy: exp.happy,
            sad: exp.sad,
            angry: exp.angry,
            fearful: exp.fearful,
            disgusted: exp.disgusted,
            surprised: exp.surprised
          };
        }

        return face;
      });

      // Check if best face is smiling
      let isSmiling = false;
      let smileScore = 0;

      if (expressionResults && expressionResults[bestIdx]?.expressions) {
        const exp = expressionResults[bestIdx].expressions;
        smileScore = exp.happy;
        // Log all expressions for debugging
        console.log('Expressions:', {
          happy: (exp.happy * 100).toFixed(1) + '%',
          neutral: (exp.neutral * 100).toFixed(1) + '%',
          sad: (exp.sad * 100).toFixed(1) + '%',
          surprised: (exp.surprised * 100).toFixed(1) + '%',
        });
        // Smile detection threshold - 50% happy expression for proper liveness check
        isSmiling = smileScore > 0.50;
      }

      return {
        detected: true,
        faces,
        confidence: bestDetection.score,
        isSmiling,
        smileScore
      };
    } catch (error) {
      console.error('Face detection error:', error);
      return { detected: false, faces: [], confidence: 0 };
    }
  }

  isReady(): boolean {
    return this.initialized;
  }

  destroy(): void {
    this.initialized = false;
    this.expressionsLoaded = false;
  }
}

/**
 * Simple Canvas-based Provider (Fallback)
 * Just checks if there's enough contrast/movement in center area
 * No actual face detection - use as last resort
 */
class SimpleCanvasProvider implements IFaceDetectionProvider {
  private initialized = false;

  async initialize(): Promise<void> {
    this.initialized = true;
    console.log('SimpleCanvasProvider initialized (fallback mode)');
  }

  async detect(video: HTMLVideoElement): Promise<ClientFaceDetectionResult> {
    if (!video.videoWidth || !video.videoHeight) {
      return { detected: false, faces: [], confidence: 0 };
    }

    // Create small canvas for analysis
    const canvas = document.createElement('canvas');
    const size = 100;
    canvas.width = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d');

    if (!ctx) {
      return { detected: false, faces: [], confidence: 0 };
    }

    // Draw center portion of video
    const sx = (video.videoWidth - video.videoHeight) / 2;
    ctx.drawImage(video, sx, 0, video.videoHeight, video.videoHeight, 0, 0, size, size);

    // Analyze center region
    const imageData = ctx.getImageData(25, 25, 50, 50);
    const data = imageData.data;

    // Calculate variance (rough skin tone detection)
    let sumR = 0, sumG = 0, sumB = 0;
    let count = 0;

    for (let i = 0; i < data.length; i += 4) {
      sumR += data[i];
      sumG += data[i + 1];
      sumB += data[i + 2];
      count++;
    }

    const avgR = sumR / count;
    const avgG = sumG / count;
    const avgB = sumB / count;
    const brightness = (avgR + avgG + avgB) / 3;

    // Simple heuristic: skin tones have R > G > B and reasonable brightness
    const isSkinTone = avgR > avgG && avgG > avgB * 0.8 && brightness > 60 && brightness < 200;
    const confidence = isSkinTone ? 0.6 : 0.3;

    return {
      detected: isSkinTone,
      faces: isSkinTone ? [{
        box: { x: video.videoWidth * 0.25, y: video.videoHeight * 0.15, width: video.videoWidth * 0.5, height: video.videoHeight * 0.7 },
        score: confidence
      }] : [],
      confidence
    };
  }

  isReady(): boolean {
    return this.initialized;
  }

  destroy(): void {
    this.initialized = false;
  }
}

/**
 * Client Face Detection Service
 * Manages the detection provider and provides unified API
 */
class ClientFaceDetectionService {
  private provider: IFaceDetectionProvider;
  private stream: MediaStream | null = null;

  constructor() {
    // Default to FaceAPI provider
    this.provider = new FaceApiProvider();
  }

  /**
   * Switch detection provider
   */
  setProvider(type: 'faceapi' | 'simple'): void {
    if (type === 'faceapi') {
      this.provider = new FaceApiProvider();
    } else {
      this.provider = new SimpleCanvasProvider();
    }
  }

  /**
   * Initialize the detection provider
   */
  async initialize(): Promise<void> {
    try {
      await this.provider.initialize();
    } catch (error) {
      console.warn('FaceAPI failed to load, falling back to simple provider:', error);
      this.provider = new SimpleCanvasProvider();
      await this.provider.initialize();
    }
  }

  /**
   * Check if provider is ready
   */
  isReady(): boolean {
    return this.provider.isReady();
  }

  /**
   * Start camera stream
   */
  async startCamera(videoElement: HTMLVideoElement): Promise<void> {
    if (this.stream) {
      this.stopCamera();
    }

    try {
      if (!navigator.mediaDevices?.getUserMedia) {
        throw new Error('Browser tidak mendukung akses kamera');
      }

      const stream = await navigator.mediaDevices.getUserMedia({
        video: {
          width: { ideal: 640 },  // Lower resolution for faster processing
          height: { ideal: 480 },
          facingMode: 'user',
        },
        audio: false,
      });

      this.stream = stream;
      videoElement.srcObject = stream;

      return new Promise((resolve) => {
        videoElement.onloadedmetadata = () => {
          videoElement.play();
          resolve();
        };
      });
    } catch (error) {
      console.error('Failed to access camera:', error);

      if (error instanceof Error) {
        if (error.name === 'NotAllowedError') {
          throw new Error('Izin kamera ditolak. Silakan aktifkan di pengaturan browser.');
        } else if (error.name === 'NotFoundError') {
          throw new Error('Kamera tidak ditemukan.');
        } else if (error.name === 'NotReadableError') {
          throw new Error('Kamera sedang digunakan aplikasi lain.');
        }
      }

      throw new Error('Gagal mengakses kamera.');
    }
  }

  /**
   * Stop camera stream
   */
  stopCamera(): void {
    if (this.stream) {
      this.stream.getTracks().forEach(track => track.stop());
      this.stream = null;
    }
  }

  /**
   * Detect faces in video frame (client-side, no API call)
   */
  async detectFaces(video: HTMLVideoElement): Promise<ClientFaceDetectionResult> {
    return this.provider.detect(video);
  }

  /**
   * Get display dimensions
   */
  getDisplaySize(video: HTMLVideoElement): { width: number; height: number } {
    return {
      width: video.videoWidth,
      height: video.videoHeight,
    };
  }

  /**
   * Draw face guide overlay
   */
  drawFaceGuide(
    canvas: HTMLCanvasElement,
    dims: { width: number; height: number },
    detected: boolean = false,
    faces: ClientFaceDetection[] = []
  ): void {
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    canvas.width = dims.width;
    canvas.height = dims.height;

    // Clear
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Draw oval guide
    const centerX = dims.width / 2;
    const centerY = dims.height / 2;
    const radiusX = dims.width * 0.25;
    const radiusY = dims.height * 0.35;

    ctx.beginPath();
    ctx.ellipse(centerX, centerY, radiusX, radiusY, 0, 0, 2 * Math.PI);
    ctx.strokeStyle = detected ? '#22c55e' : '#64748b';
    ctx.lineWidth = 3;
    ctx.setLineDash([10, 5]);
    ctx.stroke();
    ctx.setLineDash([]);

    // Draw face boxes if detected
    if (detected && faces.length > 0) {
      faces.forEach(face => {
        ctx.strokeStyle = '#22c55e';
        ctx.lineWidth = 2;
        ctx.strokeRect(face.box.x, face.box.y, face.box.width, face.box.height);
      });
    }
  }

  /**
   * Capture image from video
   */
  async captureImage(video: HTMLVideoElement): Promise<File> {
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    if (!ctx) throw new Error('Could not get canvas context');

    ctx.drawImage(video, 0, 0);

    return new Promise((resolve, reject) => {
      canvas.toBlob(blob => {
        if (blob) {
          resolve(new File([blob], `capture-${Date.now()}.jpg`, { type: 'image/jpeg' }));
        } else {
          reject(new Error('Failed to capture image'));
        }
      }, 'image/jpeg', 0.92);
    });
  }

  /**
   * Capture as base64
   */
  captureBase64(video: HTMLVideoElement): string {
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    if (!ctx) throw new Error('Could not get canvas context');

    ctx.drawImage(video, 0, 0);
    return canvas.toDataURL('image/jpeg', 0.92);
  }

  /**
   * Cleanup
   */
  destroy(): void {
    this.stopCamera();
    this.provider.destroy();
  }
}

// Export singleton instance
export const clientFaceDetection = new ClientFaceDetectionService();
export default clientFaceDetection;
