import { useState, useRef, useCallback, useEffect } from 'react';
import { Upload, Camera, Check, X, RefreshCw, AlertCircle, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Progress } from '@/components/ui/progress';
import { faceDetectionService } from '@/lib/services/face-detection';
import { useRegisterFace, useUpdateFace } from '@/hooks/use-face-recognition-api';
import { useFaceStore } from '@/stores';
import { cn } from '@/lib/utils';

interface FaceEnrollmentFromPhotoProps {
  employeeId: string;
  employeeName: string;
  existingPhotoUrl?: string;
  onSuccess?: () => void;
  onCancel?: () => void;
  isUpdate?: boolean;
}

type EnrollmentState = 'idle' | 'loading' | 'detecting' | 'detected' | 'error' | 'saving';

interface DetectionResult {
  descriptor: number[];
  confidence: number;
  box: { x: number; y: number; width: number; height: number };
}

export function FaceEnrollmentFromPhoto({
  employeeId,
  employeeName,
  existingPhotoUrl,
  onSuccess,
  onCancel,
  isUpdate = false,
}: FaceEnrollmentFromPhotoProps) {
  const [state, setState] = useState<EnrollmentState>('idle');
  const [error, setError] = useState<string | null>(null);
  const [selectedImage, setSelectedImage] = useState<string | null>(existingPhotoUrl || null);
  const [detectionResult, setDetectionResult] = useState<DetectionResult | null>(null);
  const [qualityScore, setQualityScore] = useState<number>(0);

  const canvasRef = useRef<HTMLCanvasElement>(null);
  const imageRef = useRef<HTMLImageElement>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const registerFace = useRegisterFace();
  const updateFace = useUpdateFace();
  const { addRegisteredFace } = useFaceStore();

  // Initialize face detection service
  useEffect(() => {
    const init = async () => {
      try {
        await faceDetectionService.initialize();
      } catch (_err) {
        setError('Gagal memuat model deteksi wajah');
        setState('error');
      }
    };
    init();
  }, []);

  // Handle file selection
  const handleFileSelect = useCallback((event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
      setError('File harus berupa gambar');
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      setError('Ukuran file maksimal 5MB');
      return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
      setSelectedImage(e.target?.result as string);
      setDetectionResult(null);
      setError(null);
      setState('idle');
    };
    reader.readAsDataURL(file);
  }, []);

  // Detect face from image
  const detectFace = useCallback(async () => {
    if (!selectedImage || !imageRef.current) return;

    setState('detecting');
    setError(null);

    try {
      // Wait for image to load
      await new Promise<void>((resolve) => {
        if (imageRef.current?.complete) {
          resolve();
        } else {
          imageRef.current!.onload = () => resolve();
        }
      });

      const detections = await faceDetectionService.detectFaces(imageRef.current!);

      if (detections.length === 0) {
        setError('Tidak ada wajah terdeteksi. Pastikan foto menunjukkan wajah dengan jelas.');
        setState('error');
        return;
      }

      if (detections.length > 1) {
        setError('Terdeteksi lebih dari satu wajah. Gunakan foto dengan satu wajah saja.');
        setState('error');
        return;
      }

      const detection = detections[0];
      const descriptor = Array.from(detection.descriptor!);
      const confidence = detection.detection.score;
      const box = detection.detection.box;

      // Calculate quality score
      const quality = calculateQualityScore(confidence, box, imageRef.current!);
      setQualityScore(quality);

      if (confidence < 0.7) {
        setError('Kualitas deteksi wajah rendah. Coba gunakan foto yang lebih jelas.');
        setState('error');
        return;
      }

      setDetectionResult({
        descriptor,
        confidence,
        box: {
          x: box.x,
          y: box.y,
          width: box.width,
          height: box.height,
        },
      });

      // Draw detection on canvas
      drawDetectionOverlay(box, confidence);
      setState('detected');
    } catch (err) {
      console.error('Face detection error:', err);
      setError('Gagal mendeteksi wajah. Silakan coba lagi.');
      setState('error');
    }
  }, [selectedImage]);

  // Calculate quality score based on multiple factors
  const calculateQualityScore = (
    confidence: number,
    box: { x: number; y: number; width: number; height: number },
    image: HTMLImageElement
  ): number => {
    let score = 0;

    // Confidence weight: 40%
    score += confidence * 0.4;

    // Face size weight: 30% (face should be at least 10% of image)
    const faceArea = box.width * box.height;
    const imageArea = image.width * image.height;
    const faceRatio = faceArea / imageArea;
    const sizeScore = Math.min(faceRatio / 0.1, 1);
    score += sizeScore * 0.3;

    // Face position weight: 30% (face should be centered)
    const faceCenterX = box.x + box.width / 2;
    const faceCenterY = box.y + box.height / 2;
    const imageCenterX = image.width / 2;
    const imageCenterY = image.height / 2;
    const distanceFromCenter = Math.sqrt(
      Math.pow(faceCenterX - imageCenterX, 2) + Math.pow(faceCenterY - imageCenterY, 2)
    );
    const maxDistance = Math.sqrt(Math.pow(image.width / 2, 2) + Math.pow(image.height / 2, 2));
    const positionScore = 1 - distanceFromCenter / maxDistance;
    score += positionScore * 0.3;

    return Math.round(score * 100);
  };

  // Draw detection overlay on canvas
  const drawDetectionOverlay = (
    box: { x: number; y: number; width: number; height: number },
    confidence: number
  ) => {
    if (!canvasRef.current || !imageRef.current) return;

    const canvas = canvasRef.current;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    // Set canvas size to match image display size
    const displayWidth = imageRef.current.offsetWidth;
    const displayHeight = imageRef.current.offsetHeight;
    const naturalWidth = imageRef.current.naturalWidth;
    const naturalHeight = imageRef.current.naturalHeight;

    canvas.width = displayWidth;
    canvas.height = displayHeight;

    // Scale box coordinates
    const scaleX = displayWidth / naturalWidth;
    const scaleY = displayHeight / naturalHeight;

    const scaledBox = {
      x: box.x * scaleX,
      y: box.y * scaleY,
      width: box.width * scaleX,
      height: box.height * scaleY,
    };

    // Clear canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Draw face box
    ctx.strokeStyle = '#22c55e';
    ctx.lineWidth = 3;
    ctx.shadowColor = '#22c55e';
    ctx.shadowBlur = 10;
    ctx.strokeRect(scaledBox.x, scaledBox.y, scaledBox.width, scaledBox.height);
    ctx.shadowBlur = 0;

    // Draw corner accents
    const cornerLength = 20;
    ctx.strokeStyle = '#3b82f6';
    ctx.lineWidth = 4;

    // Top-left
    ctx.beginPath();
    ctx.moveTo(scaledBox.x, scaledBox.y + cornerLength);
    ctx.lineTo(scaledBox.x, scaledBox.y);
    ctx.lineTo(scaledBox.x + cornerLength, scaledBox.y);
    ctx.stroke();

    // Top-right
    ctx.beginPath();
    ctx.moveTo(scaledBox.x + scaledBox.width - cornerLength, scaledBox.y);
    ctx.lineTo(scaledBox.x + scaledBox.width, scaledBox.y);
    ctx.lineTo(scaledBox.x + scaledBox.width, scaledBox.y + cornerLength);
    ctx.stroke();

    // Bottom-left
    ctx.beginPath();
    ctx.moveTo(scaledBox.x, scaledBox.y + scaledBox.height - cornerLength);
    ctx.lineTo(scaledBox.x, scaledBox.y + scaledBox.height);
    ctx.lineTo(scaledBox.x + cornerLength, scaledBox.y + scaledBox.height);
    ctx.stroke();

    // Bottom-right
    ctx.beginPath();
    ctx.moveTo(scaledBox.x + scaledBox.width - cornerLength, scaledBox.y + scaledBox.height);
    ctx.lineTo(scaledBox.x + scaledBox.width, scaledBox.y + scaledBox.height);
    ctx.lineTo(scaledBox.x + scaledBox.width, scaledBox.y + scaledBox.height - cornerLength);
    ctx.stroke();

    // Draw confidence badge
    const confidenceText = `${Math.round(confidence * 100)}%`;
    ctx.fillStyle = 'rgba(34, 197, 94, 0.9)';
    ctx.beginPath();
    ctx.roundRect(scaledBox.x, scaledBox.y - 30, 60, 24, 4);
    ctx.fill();

    ctx.fillStyle = '#fff';
    ctx.font = 'bold 14px Inter, system-ui, sans-serif';
    ctx.fillText(confidenceText, scaledBox.x + 10, scaledBox.y - 12);
  };

  // Save face data to backend
  const handleSave = async () => {
    if (!detectionResult) return;

    setState('saving');
    setError(null);

    try {
      const mutation = isUpdate ? updateFace : registerFace;
      await mutation.mutateAsync({
        employee_id: employeeId,
        descriptor: detectionResult.descriptor,
        confidence: detectionResult.confidence,
        image: selectedImage || undefined,
      });

      // Update local cache
      addRegisteredFace({
        employeeId,
        name: employeeName,
        descriptor: detectionResult.descriptor,
      });

      onSuccess?.();
    } catch (err) {
      console.error('Save face error:', err);
      setError('Gagal menyimpan data wajah. Silakan coba lagi.');
      setState('error');
    }
  };

  // Reset to initial state
  const handleReset = () => {
    setSelectedImage(null);
    setDetectionResult(null);
    setError(null);
    setQualityScore(0);
    setState('idle');
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const getQualityLabel = (score: number): { label: string; color: string } => {
    if (score >= 80) return { label: 'Sangat Baik', color: 'text-success' };
    if (score >= 60) return { label: 'Baik', color: 'text-primary' };
    if (score >= 40) return { label: 'Cukup', color: 'text-warning' };
    return { label: 'Kurang', color: 'text-destructive' };
  };

  const qualityInfo = getQualityLabel(qualityScore);

  return (
    <Card className="w-full max-w-md mx-auto">
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Camera className="h-5 w-5" />
          {isUpdate ? 'Update Wajah' : 'Daftarkan Wajah'}
        </CardTitle>
        <CardDescription>
          {isUpdate
            ? `Update data wajah untuk ${employeeName}`
            : `Daftarkan wajah ${employeeName} dari foto`}
        </CardDescription>
      </CardHeader>

      <CardContent className="space-y-4">
        {/* Error Alert */}
        {error && (
          <Alert variant="destructive">
            <AlertCircle className="h-4 w-4" />
            <AlertDescription>{error}</AlertDescription>
          </Alert>
        )}

        {/* Image Preview Area */}
        <div className="relative aspect-[4/3] bg-muted rounded-lg overflow-hidden">
          {selectedImage ? (
            <>
              <img
                ref={imageRef}
                src={selectedImage}
                alt="Preview"
                className="w-full h-full object-contain"
                crossOrigin="anonymous"
              />
              <canvas
                ref={canvasRef}
                className="absolute inset-0 pointer-events-none"
              />
            </>
          ) : (
            <div className="absolute inset-0 flex flex-col items-center justify-center text-muted-foreground">
              <Upload className="h-12 w-12 mb-2" />
              <p className="text-sm">Upload foto atau pilih dari profil</p>
            </div>
          )}

          {/* Loading Overlay */}
          {(state === 'loading' || state === 'detecting' || state === 'saving') && (
            <div className="absolute inset-0 bg-black/50 flex flex-col items-center justify-center">
              <Loader2 className="h-8 w-8 text-white animate-spin mb-2" />
              <p className="text-white text-sm">
                {state === 'detecting' ? 'Mendeteksi wajah...' : 'Menyimpan...'}
              </p>
            </div>
          )}
        </div>

        {/* Quality Score */}
        {detectionResult && qualityScore > 0 && (
          <div className="space-y-2">
            <div className="flex items-center justify-between text-sm">
              <span>Kualitas Foto</span>
              <span className={cn('font-medium', qualityInfo.color)}>
                {qualityScore}% - {qualityInfo.label}
              </span>
            </div>
            <Progress value={qualityScore} className="h-2" />
          </div>
        )}

        {/* Detection Info */}
        {detectionResult && (
          <div className="bg-success/5 dark:bg-success/10 border border-success/20 rounded-lg p-3">
            <div className="flex items-center gap-2 text-success">
              <Check className="h-5 w-5" />
              <span className="font-medium">Wajah Terdeteksi</span>
            </div>
            <p className="text-sm text-success/80 mt-1">
              Confidence: {Math.round(detectionResult.confidence * 100)}%
            </p>
          </div>
        )}

        {/* Action Buttons */}
        <div className="flex flex-col gap-2">
          {/* File Input */}
          <input
            ref={fileInputRef}
            type="file"
            accept="image/*"
            onChange={handleFileSelect}
            className="hidden"
          />

          {state === 'idle' && !selectedImage && (
            <Button
              onClick={() => fileInputRef.current?.click()}
              className="w-full"
            >
              <Upload className="h-4 w-4 mr-2" />
              Pilih Foto
            </Button>
          )}

          {state === 'idle' && selectedImage && !detectionResult && (
            <div className="flex gap-2">
              <Button onClick={detectFace} className="flex-1">
                <Camera className="h-4 w-4 mr-2" />
                Deteksi Wajah
              </Button>
              <Button variant="outline" onClick={handleReset}>
                <RefreshCw className="h-4 w-4" />
              </Button>
            </div>
          )}

          {state === 'detected' && detectionResult && (
            <div className="flex gap-2">
              <Button
                onClick={handleSave}
                className="flex-1"
                disabled={qualityScore < 40}
              >
                <Check className="h-4 w-4 mr-2" />
                Simpan
              </Button>
              <Button variant="outline" onClick={handleReset}>
                <RefreshCw className="h-4 w-4 mr-2" />
                Ulang
              </Button>
            </div>
          )}

          {state === 'error' && (
            <div className="flex gap-2">
              <Button onClick={() => fileInputRef.current?.click()} className="flex-1">
                <Upload className="h-4 w-4 mr-2" />
                Pilih Foto Lain
              </Button>
              {selectedImage && (
                <Button variant="outline" onClick={detectFace}>
                  <RefreshCw className="h-4 w-4 mr-2" />
                  Coba Lagi
                </Button>
              )}
            </div>
          )}

          {/* Cancel Button */}
          {onCancel && (
            <Button variant="ghost" onClick={onCancel} className="w-full">
              <X className="h-4 w-4 mr-2" />
              Batal
            </Button>
          )}
        </div>
      </CardContent>
    </Card>
  );
}

export default FaceEnrollmentFromPhoto;
