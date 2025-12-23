import { useState, useRef, useCallback, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import {
  Camera,
  Upload,
  X,
  Trash2,
  Loader2,
  CheckCircle,
  ImagePlus,
  ZoomIn,
  ZoomOut,
  RotateCw,
} from 'lucide-react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Slider } from '@/components/ui/slider';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { cn } from '@/lib/utils';

interface AvatarUploadDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  currentAvatar: string | null;
  userName: string;
  onUpload: (file: File) => Promise<void>;
  onDelete: () => Promise<void>;
  isUploading: boolean;
  isDeleting: boolean;
}

export function AvatarUploadDialog({
  open,
  onOpenChange,
  currentAvatar,
  userName,
  onUpload,
  onDelete,
  isUploading,
  isDeleting,
}: AvatarUploadDialogProps) {
  const [dragActive, setDragActive] = useState(false);
  const [previewUrl, setPreviewUrl] = useState<string | null>(null);
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [zoom, setZoom] = useState(1);
  const [rotation, setRotation] = useState(0);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [uploadSuccess, setUploadSuccess] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const getInitials = (name: string) => {
    return name
      .split(' ')
      .map((n) => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  // Reset state when dialog closes
  useEffect(() => {
    if (!open) {
      setTimeout(() => {
        setPreviewUrl(null);
        setSelectedFile(null);
        setZoom(1);
        setRotation(0);
        setUploadProgress(0);
        setUploadSuccess(false);
      }, 200);
    }
  }, [open]);

  const handleDrag = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (e.type === 'dragenter' || e.type === 'dragover') {
      setDragActive(true);
    } else if (e.type === 'dragleave') {
      setDragActive(false);
    }
  }, []);

  const handleDrop = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);

    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      handleFile(e.dataTransfer.files[0]);
    }
  }, []);

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      handleFile(e.target.files[0]);
    }
  };

  const handleFile = (file: File) => {
    // Validate file type
    if (!file.type.startsWith('image/')) {
      return;
    }

    // Validate file size (max 2MB)
    if (file.size > 2 * 1024 * 1024) {
      return;
    }

    setSelectedFile(file);
    const reader = new FileReader();
    reader.onloadend = () => {
      setPreviewUrl(reader.result as string);
    };
    reader.readAsDataURL(file);
  };

  const handleUpload = async () => {
    if (!selectedFile) return;

    try {
      // Simulate progress
      const progressInterval = setInterval(() => {
        setUploadProgress((prev) => {
          if (prev >= 90) {
            clearInterval(progressInterval);
            return prev;
          }
          return prev + 10;
        });
      }, 100);

      await onUpload(selectedFile);

      clearInterval(progressInterval);
      setUploadProgress(100);
      setUploadSuccess(true);

      // Close dialog after success
      setTimeout(() => {
        onOpenChange(false);
      }, 1500);
    } catch {
      setUploadProgress(0);
    }
  };

  const handleDelete = async () => {
    await onDelete();
    onOpenChange(false);
  };

  const handleRotate = () => {
    setRotation((prev) => (prev + 90) % 360);
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md overflow-hidden">
        <DialogHeader>
          <DialogTitle className="text-xl font-semibold">Foto Profil</DialogTitle>
          <DialogDescription>
            Upload foto untuk digunakan sebagai avatar di seluruh sistem
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-6 py-4">
          {/* Current Avatar Preview */}
          {!previewUrl && (
            <motion.div
              initial={{ opacity: 0, scale: 0.95 }}
              animate={{ opacity: 1, scale: 1 }}
              className="flex flex-col items-center gap-4"
            >
              <div className="relative group">
                <Avatar className="h-32 w-32 ring-4 ring-primary/10 transition-all duration-300 group-hover:ring-primary/20">
                  <AvatarImage src={currentAvatar || undefined} alt={userName} />
                  <AvatarFallback className="text-3xl bg-gradient-to-br from-primary/20 to-primary/10 text-primary font-semibold">
                    {getInitials(userName)}
                  </AvatarFallback>
                </Avatar>
                {currentAvatar && (
                  <motion.div
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    className="absolute inset-0 bg-black/40 rounded-full opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"
                  >
                    <Camera className="h-8 w-8 text-white" />
                  </motion.div>
                )}
              </div>

              {currentAvatar && (
                <Button
                  variant="ghost"
                  size="sm"
                  className="text-destructive hover:text-destructive hover:bg-destructive/10"
                  onClick={handleDelete}
                  disabled={isDeleting}
                >
                  {isDeleting ? (
                    <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                  ) : (
                    <Trash2 className="h-4 w-4 mr-2" />
                  )}
                  Hapus Foto
                </Button>
              )}
            </motion.div>
          )}

          {/* Preview Selected Image */}
          <AnimatePresence mode="wait">
            {previewUrl && (
              <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -20 }}
                className="space-y-4"
              >
                {/* Image Preview with Controls */}
                <div className="relative aspect-square max-w-[240px] mx-auto rounded-2xl overflow-hidden bg-muted ring-2 ring-primary/20">
                  <motion.img
                    src={previewUrl}
                    alt="Preview"
                    className="w-full h-full object-cover"
                    style={{
                      transform: `scale(${zoom}) rotate(${rotation}deg)`,
                      transition: 'transform 0.3s ease-out',
                    }}
                  />

                  {/* Upload Progress Overlay */}
                  {isUploading && (
                    <div className="absolute inset-0 bg-black/60 flex flex-col items-center justify-center">
                      <div className="relative">
                        <svg className="w-16 h-16 transform -rotate-90">
                          <circle
                            cx="32"
                            cy="32"
                            r="28"
                            stroke="currentColor"
                            strokeWidth="4"
                            fill="none"
                            className="text-white/20"
                          />
                          <motion.circle
                            cx="32"
                            cy="32"
                            r="28"
                            stroke="currentColor"
                            strokeWidth="4"
                            fill="none"
                            strokeLinecap="round"
                            className="text-white"
                            initial={{ strokeDasharray: '0 175.93' }}
                            animate={{
                              strokeDasharray: `${(uploadProgress / 100) * 175.93} 175.93`,
                            }}
                            transition={{ duration: 0.3 }}
                          />
                        </svg>
                        <span className="absolute inset-0 flex items-center justify-center text-white font-semibold">
                          {uploadProgress}%
                        </span>
                      </div>
                      <p className="text-white text-sm mt-3">Mengupload...</p>
                    </div>
                  )}

                  {/* Success Overlay */}
                  {uploadSuccess && (
                    <motion.div
                      initial={{ opacity: 0 }}
                      animate={{ opacity: 1 }}
                      className="absolute inset-0 bg-emerald-500/90 flex flex-col items-center justify-center"
                    >
                      <motion.div
                        initial={{ scale: 0 }}
                        animate={{ scale: 1 }}
                        transition={{ type: 'spring', duration: 0.5 }}
                      >
                        <CheckCircle className="h-16 w-16 text-white" />
                      </motion.div>
                      <p className="text-white font-semibold mt-3">Berhasil!</p>
                    </motion.div>
                  )}
                </div>

                {/* Image Controls */}
                {!isUploading && !uploadSuccess && (
                  <div className="flex items-center justify-center gap-6">
                    <div className="flex items-center gap-2">
                      <ZoomOut className="h-4 w-4 text-muted-foreground" />
                      <Slider
                        value={[zoom]}
                        min={1}
                        max={2}
                        step={0.1}
                        className="w-24"
                        onValueChange={(value) => setZoom(value[0])}
                      />
                      <ZoomIn className="h-4 w-4 text-muted-foreground" />
                    </div>
                    <Button
                      variant="ghost"
                      size="icon"
                      className="h-8 w-8"
                      onClick={handleRotate}
                    >
                      <RotateCw className="h-4 w-4" />
                    </Button>
                  </div>
                )}

                {/* Action Buttons */}
                {!isUploading && !uploadSuccess && (
                  <div className="flex gap-3 justify-center">
                    <Button
                      variant="outline"
                      onClick={() => {
                        setPreviewUrl(null);
                        setSelectedFile(null);
                        setZoom(1);
                        setRotation(0);
                      }}
                    >
                      <X className="h-4 w-4 mr-2" />
                      Batal
                    </Button>
                    <Button
                      onClick={handleUpload}
                      className="bg-gradient-to-r from-primary to-emerald-600 hover:from-primary/90 hover:to-emerald-600/90"
                    >
                      <Upload className="h-4 w-4 mr-2" />
                      Upload Foto
                    </Button>
                  </div>
                )}
              </motion.div>
            )}
          </AnimatePresence>

          {/* Drop Zone */}
          {!previewUrl && (
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              className={cn(
                'relative border-2 border-dashed rounded-2xl p-8 transition-all duration-300 cursor-pointer group',
                dragActive
                  ? 'border-primary bg-primary/5 scale-[1.02]'
                  : 'border-muted-foreground/25 hover:border-primary/50 hover:bg-muted/50'
              )}
              onDragEnter={handleDrag}
              onDragLeave={handleDrag}
              onDragOver={handleDrag}
              onDrop={handleDrop}
              onClick={() => fileInputRef.current?.click()}
            >
              <input
                ref={fileInputRef}
                type="file"
                className="hidden"
                accept="image/jpeg,image/png,image/jpg,image/gif"
                onChange={handleFileChange}
              />

              <div className="flex flex-col items-center gap-4 text-center">
                <motion.div
                  animate={{
                    y: dragActive ? -5 : 0,
                    scale: dragActive ? 1.1 : 1,
                  }}
                  className={cn(
                    'p-4 rounded-2xl transition-colors duration-300',
                    dragActive
                      ? 'bg-primary/20 text-primary'
                      : 'bg-muted text-muted-foreground group-hover:bg-primary/10 group-hover:text-primary'
                  )}
                >
                  <ImagePlus className="h-8 w-8" />
                </motion.div>

                <div className="space-y-1">
                  <p className="font-medium text-foreground">
                    {dragActive ? 'Lepas untuk upload' : 'Drag & drop foto di sini'}
                  </p>
                  <p className="text-sm text-muted-foreground">
                    atau klik untuk memilih file
                  </p>
                </div>

                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                  <span className="px-2 py-1 rounded-full bg-muted">JPG</span>
                  <span className="px-2 py-1 rounded-full bg-muted">PNG</span>
                  <span className="px-2 py-1 rounded-full bg-muted">GIF</span>
                  <span className="text-muted-foreground/60">Max 2MB</span>
                </div>
              </div>

              {/* Animated border on drag */}
              {dragActive && (
                <motion.div
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  className="absolute inset-0 rounded-2xl pointer-events-none"
                  style={{
                    background:
                      'linear-gradient(90deg, transparent, rgba(var(--primary), 0.3), transparent)',
                    backgroundSize: '200% 100%',
                    animation: 'shimmer 1.5s infinite',
                  }}
                />
              )}
            </motion.div>
          )}

          {/* Tips */}
          {!previewUrl && (
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              transition={{ delay: 0.2 }}
              className="bg-muted/50 rounded-xl p-4 space-y-2"
            >
              <p className="text-sm font-medium text-foreground">Tips foto profil yang baik:</p>
              <ul className="text-sm text-muted-foreground space-y-1">
                <li className="flex items-center gap-2">
                  <CheckCircle className="h-3.5 w-3.5 text-emerald-500" />
                  Gunakan foto wajah yang jelas
                </li>
                <li className="flex items-center gap-2">
                  <CheckCircle className="h-3.5 w-3.5 text-emerald-500" />
                  Pencahayaan yang baik
                </li>
                <li className="flex items-center gap-2">
                  <CheckCircle className="h-3.5 w-3.5 text-emerald-500" />
                  Background polos atau sederhana
                </li>
              </ul>
            </motion.div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
}
