/**
 * Face Detection Service - Native Browser Implementation
 * 
 * Uses browser native APIs for camera access and basic face detection visuals.
 * Actual face recognition is handled server-side by DeepFace service.
 * 
 * @module face-detection
 */

import type {
    FaceDetectionResult,
} from '@/types/face-recognition';

class FaceDetectionService {
    private stream: MediaStream | null = null;
    private isInitialized = false;

    /**
     * Initialize the service
     * No model loading needed - we use DeepFace server-side
     */
    async initialize(): Promise<void> {
        if (this.isInitialized) return;
        this.isInitialized = true;
        console.log('Face detection service initialized (DeepFace backend)');
    }

    /**
     * Start camera stream
     */
    async startCamera(videoElement: HTMLVideoElement): Promise<void> {
        if (this.stream) {
            this.stopCamera();
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
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
            throw new Error('Gagal mengakses kamera');
        }
    }

    /**
     * Stop camera stream
     */
    stopCamera(): void {
        if (this.stream) {
            this.stream.getTracks().forEach((track) => track.stop());
            this.stream = null;
        }
    }

    /**
     * Get display size from video element
     */
    getDisplaySize(videoElement: HTMLVideoElement): { width: number; height: number } {
        return {
            width: videoElement.videoWidth,
            height: videoElement.videoHeight,
        };
    }

    /**
     * Draw detection results on canvas
     * Simple box drawing - actual detection done by DeepFace
     */
    drawDetections(
        canvas: HTMLCanvasElement,
        detections: FaceDetectionResult[],
        dims: { width: number; height: number }
    ): void {
        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        // Match canvas size to video
        canvas.width = dims.width;
        canvas.height = dims.height;

        // Clear previous drawings
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Draw each detection
        detections.forEach((d) => {
            const box = d.detection.box;

            // Draw box
            ctx.strokeStyle = '#22c55e'; // Green
            ctx.lineWidth = 3;
            ctx.strokeRect(box.x, box.y, box.width, box.height);

            // Draw confidence label
            const confidence = Math.round(d.detection.score * 100);
            ctx.fillStyle = '#22c55e';
            ctx.font = 'bold 14px sans-serif';
            ctx.fillText(`${confidence}%`, box.x, box.y - 5);
        });
    }

    /**
     * Draw face guide overlay (oval guide for user positioning)
     */
    drawFaceGuide(
        canvas: HTMLCanvasElement,
        dims: { width: number; height: number },
        detected: boolean = false
    ): void {
        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        canvas.width = dims.width;
        canvas.height = dims.height;

        const centerX = dims.width / 2;
        const centerY = dims.height / 2;
        const radiusX = dims.width * 0.25;
        const radiusY = dims.height * 0.35;

        // Draw oval guide
        ctx.beginPath();
        ctx.ellipse(centerX, centerY, radiusX, radiusY, 0, 0, 2 * Math.PI);
        ctx.strokeStyle = detected ? '#22c55e' : '#64748b';
        ctx.lineWidth = 3;
        ctx.setLineDash([10, 5]);
        ctx.stroke();
        ctx.setLineDash([]);
    }

    /**
     * Capture image from video element
     */
    async captureImage(videoElement: HTMLVideoElement): Promise<File> {
        const canvas = document.createElement('canvas');
        canvas.width = videoElement.videoWidth;
        canvas.height = videoElement.videoHeight;
        const ctx = canvas.getContext('2d');
        if (!ctx) throw new Error('Could not get canvas context');

        ctx.drawImage(videoElement, 0, 0);

        return new Promise((resolve, reject) => {
            canvas.toBlob((blob) => {
                if (blob) {
                    const file = new File([blob], `capture-${Date.now()}.jpg`, { type: 'image/jpeg' });
                    resolve(file);
                } else {
                    reject(new Error('Failed to capture image'));
                }
            }, 'image/jpeg', 0.95);
        });
    }

    /**
     * Capture image as base64 string
     */
    async captureBase64(videoElement: HTMLVideoElement): Promise<string> {
        const canvas = document.createElement('canvas');
        canvas.width = videoElement.videoWidth;
        canvas.height = videoElement.videoHeight;
        const ctx = canvas.getContext('2d');
        if (!ctx) throw new Error('Could not get canvas context');

        ctx.drawImage(videoElement, 0, 0);
        return canvas.toDataURL('image/jpeg', 0.95);
    }

    /**
     * Cleanup service
     */
    destroy(): void {
        this.stopCamera();
        this.isInitialized = false;
    }

    /**
     * Stub: Detect faces (legacy compatibility)
     * Actual detection is done server-side by DeepFace
     */
    async detectFaces(_videoOrImage: HTMLVideoElement | HTMLImageElement): Promise<FaceDetectionResult[]> {
        console.warn('detectFaces is deprecated - use server-side DeepFace API');
        return [];
    }

    /**
     * Stub: Fast face detection (legacy compatibility)
     */
    async detectFacesFast(_videoElement: HTMLVideoElement): Promise<FaceDetectionResult[]> {
        console.warn('detectFacesFast is deprecated - use server-side DeepFace API');
        return [];
    }

    /**
     * Stub: Capture face descriptor (legacy compatibility)
     * Descriptors are now computed server-side by DeepFace
     */
    async captureFaceDescriptor(_videoElement: HTMLVideoElement): Promise<{
        descriptor: number[];
        detection: { box: { x: number; y: number; width: number; height: number }; score: number };
        confidence: number;
        imageData?: string;
    }> {
        console.warn('captureFaceDescriptor is deprecated - use server-side DeepFace API');
        return {
            descriptor: [],
            detection: { box: { x: 0, y: 0, width: 0, height: 0 }, score: 0 },
            confidence: 0,
        };
    }

    /**
     * Stub: Recognize face (legacy compatibility)
     */
    async recognizeFace(_videoElement: HTMLVideoElement, _knownDescriptors: unknown[]): Promise<{
        success: boolean;
        matched?: boolean;
        matchedId?: string | number;
        confidence: number;
        message?: string;
    }> {
        console.warn('recognizeFace is deprecated - use server-side DeepFace API');
        return {
            success: false,
            confidence: 0,
            message: 'Use server-side face recognition via DeepFace API',
        };
    }
}

export const faceDetectionService = new FaceDetectionService();
