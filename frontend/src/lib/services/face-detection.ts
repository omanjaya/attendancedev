import * as faceapi from 'face-api.js';
import type {
    FaceDetectionResult,
    FaceData,
    RecognitionResult,
    KnownFaceDescriptor,
    FaceBox,
    FaceLandmark,
    FaceExpression,
} from '@/types/face-recognition';

class FaceDetectionService {
    private isInitialized = false;
    private stream: MediaStream | null = null;
    private modelsLoaded = false;
    private options: faceapi.TinyFaceDetectorOptions;

    constructor() {
        this.options = new faceapi.TinyFaceDetectorOptions({
            inputSize: 512,
            scoreThreshold: 0.5,
        });
    }

    async initialize(): Promise<void> {
        if (this.isInitialized) return;

        try {
            const MODEL_URL = '/models';

            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
                faceapi.nets.faceExpressionNet.loadFromUri(MODEL_URL),
            ]);

            this.modelsLoaded = true;
            this.isInitialized = true;
            console.log('Face detection models loaded');
        } catch (error) {
            console.error('Failed to load face detection models:', error);
            throw new Error('Gagal memuat model deteksi wajah');
        }
    }

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

    stopCamera(): void {
        if (this.stream) {
            this.stream.getTracks().forEach((track) => track.stop());
            this.stream = null;
        }
    }

    async detectFaces(videoElement: HTMLVideoElement): Promise<FaceDetectionResult[]> {
        if (!this.isInitialized) throw new Error('Service not initialized');

        const detections = await faceapi
            .detectAllFaces(videoElement, this.options)
            .withFaceLandmarks()
            .withFaceExpressions();

        return detections.map((d) => ({
            detection: {
                box: d.detection.box,
                score: d.detection.score,
            },
            landmarks: {
                positions: d.landmarks.positions,
            },
            expressions: {
                asSortedArray: () => d.expressions.asSortedArray(),
            },
            descriptor: undefined, // detectAllFaces doesn't return descriptor by default here unless chained
        }));
    }

    async detectFacesFast(videoElement: HTMLVideoElement): Promise<FaceDetectionResult[]> {
        if (!this.isInitialized) return [];

        const detections = await faceapi.detectAllFaces(videoElement, this.options);

        return detections.map((d) => ({
            detection: {
                box: d.box,
                score: d.score,
            },
            // Minimal mock data
            landmarks: undefined,
            expressions: undefined,
        }));
    }

    async captureFaceDescriptor(
        videoElement: HTMLVideoElement,
        employeeId: string | number,
        options: { requireLiveness?: boolean } = {}
    ): Promise<FaceData> {
        if (!this.isInitialized) throw new Error('Service not initialized');

        const detection = await faceapi
            .detectSingleFace(videoElement, this.options)
            .withFaceLandmarks()
            .withFaceExpressions()
            .withFaceDescriptor();

        if (!detection) {
            throw new Error('Wajah tidak terdeteksi');
        }

        // Map expressions
        const expressions: FaceExpression[] = detection.expressions.asSortedArray().map(e => ({
            expression: e.expression,
            probability: e.probability
        }));

        return {
            descriptor: Array.from(detection.descriptor),
            confidence: detection.detection.score,
            face_bounds: detection.detection.box,
            timestamp: Date.now(),
            expressions: expressions,
            landmarks: detection.landmarks.positions,
            // Optional fields
            pose: null,
            liveness: undefined,
        };
    }

    async recognizeFace(
        videoElement: HTMLVideoElement,
        knownDescriptors: KnownFaceDescriptor[]
    ): Promise<RecognitionResult> {
        if (!this.isInitialized) throw new Error('Service not initialized');
        if (knownDescriptors.length === 0) {
            return { success: false, message: 'No known faces', confidence: 0 };
        }

        const detection = await faceapi
            .detectSingleFace(videoElement, this.options)
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (!detection) {
            return { success: false, message: 'No face detected', confidence: 0 };
        }

        const labeledDescriptors = knownDescriptors
            .filter(kd => kd.descriptor && kd.descriptor.length > 0)
            .map((kd) => {
                const descriptor = kd.descriptor instanceof Float32Array
                    ? kd.descriptor
                    : new Float32Array(kd.descriptor);
                return new faceapi.LabeledFaceDescriptors(String(kd.employeeId), [descriptor]);
            });

        if (labeledDescriptors.length === 0) {
            return { success: false, message: 'Invalid known descriptors', confidence: 0 };
        }

        const faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.6);
        const match = faceMatcher.findBestMatch(detection.descriptor);

        if (match.label !== 'unknown') {
            const matchedFace = knownDescriptors.find((kd) => String(kd.employeeId) === match.label);
            return {
                success: true,
                employeeId: match.label,
                // name: matchedFace?.name, // RecognitionResult doesn't have name field in interface
                confidence: 1 - match.distance,
                message: 'Face recognized',
            };
        }

        return {
            success: false,
            message: 'Face not recognized',
            confidence: 1 - match.distance,
        };
    }

    getDisplaySize(videoElement: HTMLVideoElement): { width: number; height: number } {
        return {
            width: videoElement.videoWidth,
            height: videoElement.videoHeight,
        };
    }

    drawDetections(
        canvas: HTMLCanvasElement,
        detections: FaceDetectionResult[],
        dims: { width: number; height: number }
    ): void {
        faceapi.matchDimensions(canvas, dims);

        // Map back to face-api types for drawing
        const faceapiDetections = detections.map(d => {
            const box = new faceapi.Box(d.detection.box);
            return new faceapi.FaceDetection(d.detection.score, box, { width: dims.width, height: dims.height });
        });

        faceapi.draw.drawDetections(canvas, faceapiDetections);
    }

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

    destroy(): void {
        this.stopCamera();
        this.isInitialized = false;
    }
}

export const faceDetectionService = new FaceDetectionService();
