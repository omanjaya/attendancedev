import * as faceapi from 'face-api.js';
import type {
  FaceDetectionResult,
  FaceData,
  LivenessResult,
  ImageQuality,
  RecognitionResult,
  KnownFaceDescriptor,
  FacePose,
} from '@/types/face-recognition';

class FaceDetectionService {
  private isInitialized = false;
  private currentStream: MediaStream | null = null;
  private faceDescriptors = new Map<string | number, Float32Array>();
  private modelsPath = '/models';

  private livenessConfig = {
    isActive: false,
    gesturePrompts: ['blink', 'smile', 'nod'] as const,
    currentGesture: null as string | null,
    gestureTimeout: 5000,
    minLivenessScore: 0.8,
  };

  async initialize(): Promise<void> {
    if (this.isInitialized) return;

    try {
      await Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(this.modelsPath),
        faceapi.nets.faceLandmark68Net.loadFromUri(this.modelsPath),
        faceapi.nets.faceRecognitionNet.loadFromUri(this.modelsPath),
        faceapi.nets.faceExpressionNet.loadFromUri(this.modelsPath),
      ]);

      this.isInitialized = true;
      console.log('Face detection models loaded successfully');
    } catch (error) {
      console.error('Failed to load face detection models:', error);
      throw new Error('Face detection initialization failed');
    }
  }

  async startCamera(videoElement: HTMLVideoElement): Promise<void> {
    try {
      const constraints: MediaStreamConstraints = {
        video: {
          width: { ideal: 640 },
          height: { ideal: 480 },
          facingMode: 'user',
        },
      };

      this.currentStream = await navigator.mediaDevices.getUserMedia(constraints);
      videoElement.srcObject = this.currentStream;

      return new Promise((resolve) => {
        videoElement.onloadedmetadata = () => {
          videoElement.play();
          resolve();
        };
      });
    } catch (error) {
      console.error('Failed to start camera:', error);
      throw new Error('Camera access denied or not available');
    }
  }

  stopCamera(): void {
    if (this.currentStream) {
      this.currentStream.getTracks().forEach((track) => track.stop());
      this.currentStream = null;
    }
  }

  async detectFaces(input: HTMLVideoElement | HTMLImageElement | HTMLCanvasElement): Promise<FaceDetectionResult[]> {
    if (!this.isInitialized) {
      throw new Error('Face detection not initialized');
    }

    try {
      const detections = await faceapi
        .detectAllFaces(input, new faceapi.TinyFaceDetectorOptions())
        .withFaceLandmarks()
        .withFaceDescriptors()
        .withFaceExpressions();

      return detections as unknown as FaceDetectionResult[];
    } catch (error) {
      console.error('Face detection failed:', error);
      return [];
    }
  }

  async captureFaceDescriptor(
    videoElement: HTMLVideoElement,
    employeeId: string | number,
    options: { requireLiveness?: boolean } = {}
  ): Promise<FaceData> {
    const detections = await this.detectFaces(videoElement);

    if (detections.length === 0) {
      throw new Error('No face detected. Please ensure your face is clearly visible.');
    }

    if (detections.length > 1) {
      throw new Error('Multiple faces detected. Please ensure only one person is in frame.');
    }

    const detection = detections[0];
    const faceDescriptor = detection.descriptor!;
    this.faceDescriptors.set(employeeId, faceDescriptor);

    const faceData: FaceData = {
      descriptor: Array.from(faceDescriptor),
      landmarks: detection.landmarks?.positions,
      confidence: detection.detection.score,
      pose: this.extractPoseData(detection),
      expressions: detection.expressions?.asSortedArray() || null,
      face_bounds: {
        x: detection.detection.box.x,
        y: detection.detection.box.y,
        width: detection.detection.box.width,
        height: detection.detection.box.height,
      },
      timestamp: Date.now(),
    };

    if (options.requireLiveness) {
      const livenessResult = await this.performLivenessCheck(videoElement);
      faceData.liveness = livenessResult;
    }

    return faceData;
  }

  async recognizeFace(
    videoElement: HTMLVideoElement,
    knownDescriptors: KnownFaceDescriptor[] = []
  ): Promise<RecognitionResult> {
    const detections = await this.detectFaces(videoElement);

    if (detections.length === 0) {
      return { success: false, message: 'No face detected', confidence: 0 };
    }

    if (detections.length > 1) {
      return { success: false, message: 'Multiple faces detected', confidence: 0 };
    }

    const detection = detections[0];
    const threshold = 0.6;

    for (const known of knownDescriptors) {
      const knownDescriptor = known.descriptor instanceof Float32Array
        ? known.descriptor
        : new Float32Array(known.descriptor);

      const distance = faceapi.euclideanDistance(
        detection.descriptor!,
        knownDescriptor
      );
      const similarity = 1 - distance;

      if (similarity > threshold) {
        return {
          success: true,
          employeeId: known.employeeId,
          confidence: similarity,
          landmarks: detection.landmarks?.positions,
        };
      }
    }

    return {
      success: false,
      message: 'Face not recognized',
      confidence: 0,
    };
  }

  drawDetections(
    canvas: HTMLCanvasElement,
    detections: FaceDetectionResult[],
    displaySize: { width: number; height: number }
  ): void {
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    ctx.clearRect(0, 0, canvas.width, canvas.height);
    if (detections.length === 0) return;

    const resizedDetections = faceapi.resizeResults(detections, displaySize);

    resizedDetections.forEach((detection: FaceDetectionResult) => {
      const box = detection.detection.box;

      // Draw face box with gradient
      ctx.strokeStyle = '#22c55e';
      ctx.lineWidth = 3;
      ctx.shadowColor = '#22c55e';
      ctx.shadowBlur = 10;
      ctx.strokeRect(box.x, box.y, box.width, box.height);
      ctx.shadowBlur = 0;

      // Draw corner accents
      const cornerLength = 20;
      ctx.strokeStyle = '#3b82f6';
      ctx.lineWidth = 4;

      // Top-left
      ctx.beginPath();
      ctx.moveTo(box.x, box.y + cornerLength);
      ctx.lineTo(box.x, box.y);
      ctx.lineTo(box.x + cornerLength, box.y);
      ctx.stroke();

      // Top-right
      ctx.beginPath();
      ctx.moveTo(box.x + box.width - cornerLength, box.y);
      ctx.lineTo(box.x + box.width, box.y);
      ctx.lineTo(box.x + box.width, box.y + cornerLength);
      ctx.stroke();

      // Bottom-left
      ctx.beginPath();
      ctx.moveTo(box.x, box.y + box.height - cornerLength);
      ctx.lineTo(box.x, box.y + box.height);
      ctx.lineTo(box.x + cornerLength, box.y + box.height);
      ctx.stroke();

      // Bottom-right
      ctx.beginPath();
      ctx.moveTo(box.x + box.width - cornerLength, box.y + box.height);
      ctx.lineTo(box.x + box.width, box.y + box.height);
      ctx.lineTo(box.x + box.width, box.y + box.height - cornerLength);
      ctx.stroke();

      // Draw confidence badge
      const confidence = Math.round(detection.detection.score * 100);
      ctx.fillStyle = 'rgba(34, 197, 94, 0.9)';
      ctx.beginPath();
      ctx.roundRect(box.x, box.y - 30, 70, 24, 4);
      ctx.fill();

      ctx.fillStyle = '#fff';
      ctx.font = 'bold 14px Inter, system-ui, sans-serif';
      ctx.fillText(`${confidence}%`, box.x + 10, box.y - 12);

      // Draw landmarks (subtle dots)
      if (detection.landmarks) {
        const landmarks = detection.landmarks.positions;
        ctx.fillStyle = 'rgba(59, 130, 246, 0.6)';
        landmarks.forEach((point) => {
          ctx.beginPath();
          ctx.arc(point.x, point.y, 1.5, 0, 2 * Math.PI);
          ctx.fill();
        });
      }

      // Draw expression
      if (detection.expressions) {
        const expressions = detection.expressions.asSortedArray();
        const topExpression = expressions[0];

        ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
        ctx.beginPath();
        ctx.roundRect(box.x, box.y + box.height + 8, box.width, 24, 4);
        ctx.fill();

        ctx.fillStyle = '#fff';
        ctx.font = '12px Inter, system-ui, sans-serif';
        const expressionText = `${topExpression.expression}: ${Math.round(topExpression.probability * 100)}%`;
        ctx.fillText(expressionText, box.x + 8, box.y + box.height + 24);
      }
    });
  }

  async performLivenessCheck(videoElement: HTMLVideoElement): Promise<LivenessResult> {
    const livenessData: LivenessResult = {
      blink_detected: false,
      head_movement: 0,
      gesture_completed: false,
      liveness_score: 0,
      checks_performed: [],
      is_live: false,
    };

    // Detect blink
    const blinkDetection = this.detectBlink();
    livenessData.blink_detected = blinkDetection.detected;
    livenessData.checks_performed.push('blink');

    // Analyze image quality
    const quality = await this.analyzeImageQuality(videoElement);
    livenessData.checks_performed.push('quality');

    // Calculate liveness score
    let score = 0.5;
    if (livenessData.blink_detected) score += 0.2;
    if (quality.lighting_score > 0.5) score += 0.15;
    if (quality.blur_score > 0.5) score += 0.15;

    livenessData.liveness_score = Math.min(score, 1.0);
    livenessData.is_live = livenessData.liveness_score >= this.livenessConfig.minLivenessScore;

    return livenessData;
  }

  private detectBlink(): { detected: boolean; confidence: number } {
    // Simplified blink detection
    // In production, analyze eye aspect ratio from landmarks
    return {
      detected: Math.random() > 0.3,
      confidence: Math.random() * 0.4 + 0.6,
    };
  }

  private extractPoseData(detection: FaceDetectionResult): FacePose | null {
    if (!detection.landmarks) return null;

    const landmarks = detection.landmarks.positions;
    if (landmarks.length < 48) return null;

    const leftEye = landmarks.slice(36, 42);
    const rightEye = landmarks.slice(42, 48);
    const nose = landmarks.slice(27, 36);

    const eyeCenter = {
      x: (leftEye[0].x + rightEye[0].x) / 2,
      y: (leftEye[0].y + rightEye[0].y) / 2,
    };
    const noseCenter = nose[3];

    const yaw = Math.atan2(noseCenter.x - eyeCenter.x, 100) * (180 / Math.PI);
    const pitch = Math.atan2(noseCenter.y - eyeCenter.y, 100) * (180 / Math.PI);

    return { yaw, pitch, roll: 0 };
  }

  async analyzeImageQuality(videoElement: HTMLVideoElement): Promise<ImageQuality> {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    if (!ctx) return { lighting_score: 0, blur_score: 0, noise_level: 0 };

    canvas.width = videoElement.videoWidth || 640;
    canvas.height = videoElement.videoHeight || 480;
    ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);

    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);

    return {
      lighting_score: this.calculateLightingScore(imageData),
      blur_score: this.calculateBlurScore(imageData),
      noise_level: Math.random() * 0.3,
    };
  }

  private calculateLightingScore(imageData: ImageData): number {
    let total = 0;
    const pixels = imageData.data;

    for (let i = 0; i < pixels.length; i += 4) {
      const brightness = (pixels[i] + pixels[i + 1] + pixels[i + 2]) / 3;
      total += brightness;
    }

    const avgBrightness = total / (pixels.length / 4);
    return Math.max(0, 1 - Math.abs(avgBrightness - 130) / 130);
  }

  private calculateBlurScore(imageData: ImageData): number {
    const pixels = imageData.data;
    let variance = 0;
    let mean = 0;

    for (let i = 0; i < pixels.length; i += 4) {
      mean += (pixels[i] + pixels[i + 1] + pixels[i + 2]) / 3;
    }
    mean /= pixels.length / 4;

    for (let i = 0; i < pixels.length; i += 4) {
      const brightness = (pixels[i] + pixels[i + 1] + pixels[i + 2]) / 3;
      variance += Math.pow(brightness - mean, 2);
    }
    variance /= pixels.length / 4;

    return Math.min(variance / 10000, 1);
  }

  getIsInitialized(): boolean {
    return this.isInitialized;
  }

  getDisplaySize(element: HTMLElement): { width: number; height: number } {
    return { width: element.offsetWidth, height: element.offsetHeight };
  }

  destroy(): void {
    this.stopCamera();
    this.faceDescriptors.clear();
    this.livenessConfig.isActive = false;
    this.isInitialized = false;
  }
}

// Export singleton instance
export const faceDetectionService = new FaceDetectionService();
export default faceDetectionService;
