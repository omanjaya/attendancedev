import { useState } from 'react';
import { Bug, Camera, Send, X, AlertCircle, Lightbulb, HelpCircle, AlertTriangle } from 'lucide-react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { toast } from 'sonner';
import {
  submitBugReport,
  getBrowserInfo,
  captureScreenshot,
  type BugReportInput,
} from '@/lib/api/bug-reports';

interface BugReportDialogProps {
  trigger?: React.ReactNode;
  defaultType?: BugReportInput['type'];
  defaultErrorMessage?: string;
  defaultErrorStack?: string;
  onSuccess?: () => void;
}

const typeIcons = {
  bug: Bug,
  error: AlertCircle,
  suggestion: Lightbulb,
  question: HelpCircle,
};

const typeLabels = {
  bug: 'Bug',
  error: 'Error',
  suggestion: 'Saran',
  question: 'Pertanyaan',
};

const severityLabels = {
  low: 'Rendah',
  medium: 'Sedang',
  high: 'Tinggi',
  critical: 'Kritis',
};

export function BugReportDialog({
  trigger,
  defaultType = 'bug',
  defaultErrorMessage,
  defaultErrorStack,
  onSuccess,
}: BugReportDialogProps) {
  const [open, setOpen] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isCapturing, setIsCapturing] = useState(false);
  const [screenshots, setScreenshots] = useState<string[]>([]);

  const [formData, setFormData] = useState<BugReportInput>({
    title: '',
    description: '',
    type: defaultType,
    severity: 'medium',
    error_message: defaultErrorMessage || '',
    error_stack: defaultErrorStack || '',
  });

  const handleCaptureScreenshot = async () => {
    if (screenshots.length >= 3) {
      toast.error('Maksimal 3 screenshot per laporan');
      return;
    }

    setIsCapturing(true);
    try {
      const screenshot = await captureScreenshot();
      if (screenshot) {
        setScreenshots((prev) => [...prev, screenshot]);
        toast.success('Screenshot berhasil ditambahkan');
      }
    } catch {
      toast.error('Gagal mengambil screenshot');
    } finally {
      setIsCapturing(false);
    }
  };

  const handleRemoveScreenshot = (index: number) => {
    setScreenshots((prev) => prev.filter((_, i) => i !== index));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!formData.title.trim()) {
      toast.error('Silakan masukkan judul laporan');
      return;
    }

    if (!formData.description.trim()) {
      toast.error('Silakan masukkan deskripsi masalah');
      return;
    }

    setIsSubmitting(true);
    try {
      await submitBugReport({
        ...formData,
        browser_info: getBrowserInfo(),
        page_url: window.location.href,
        screenshots: screenshots.length > 0 ? screenshots : undefined,
      });

      toast.success('Terima kasih! Laporan Anda telah dikirim ke developer.');

      // Reset form
      setFormData({
        title: '',
        description: '',
        type: defaultType,
        severity: 'medium',
        error_message: '',
        error_stack: '',
      });
      setScreenshots([]);
      setOpen(false);
      onSuccess?.();
    } catch {
      toast.error('Terjadi kesalahan saat mengirim laporan. Silakan coba lagi.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const TypeIcon = typeIcons[formData.type];

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        {trigger || (
          <Button variant="outline" size="sm" className="gap-2">
            <Bug className="h-4 w-4" />
            Laporkan Bug
          </Button>
        )}
      </DialogTrigger>
      <DialogContent className="sm:max-w-[500px] max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <TypeIcon className="h-5 w-5" />
            Laporkan Masalah
          </DialogTitle>
          <DialogDescription>
            Bantu kami meningkatkan aplikasi dengan melaporkan bug atau masalah yang Anda temui.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-4">
          {/* Type Selection */}
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Tipe Laporan</Label>
              <Select
                value={formData.type}
                onValueChange={(value) =>
                  setFormData((prev) => ({ ...prev, type: value as BugReportInput['type'] }))
                }
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {Object.entries(typeLabels).map(([key, label]) => {
                    const Icon = typeIcons[key as keyof typeof typeIcons];
                    return (
                      <SelectItem key={key} value={key}>
                        <span className="flex items-center gap-2">
                          <Icon className="h-4 w-4" />
                          {label}
                        </span>
                      </SelectItem>
                    );
                  })}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label>Tingkat Keparahan</Label>
              <Select
                value={formData.severity}
                onValueChange={(value) =>
                  setFormData((prev) => ({ ...prev, severity: value as BugReportInput['severity'] }))
                }
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {Object.entries(severityLabels).map(([key, label]) => (
                    <SelectItem key={key} value={key}>
                      <span className="flex items-center gap-2">
                        <AlertTriangle
                          className={`h-4 w-4 ${
                            key === 'critical'
                              ? 'text-red-500'
                              : key === 'high'
                                ? 'text-orange-500'
                                : key === 'medium'
                                  ? 'text-yellow-500'
                                  : 'text-green-500'
                          }`}
                        />
                        {label}
                      </span>
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>

          {/* Title */}
          <div className="space-y-2">
            <Label htmlFor="title">Judul *</Label>
            <Input
              id="title"
              placeholder="Ringkasan singkat masalah..."
              value={formData.title}
              onChange={(e) => setFormData((prev) => ({ ...prev, title: e.target.value }))}
              maxLength={255}
            />
          </div>

          {/* Description */}
          <div className="space-y-2">
            <Label htmlFor="description">Deskripsi *</Label>
            <Textarea
              id="description"
              placeholder="Jelaskan masalah secara detail. Apa yang Anda lakukan saat masalah terjadi? Apa yang seharusnya terjadi?"
              value={formData.description}
              onChange={(e) => setFormData((prev) => ({ ...prev, description: e.target.value }))}
              rows={4}
              maxLength={5000}
            />
          </div>

          {/* Error Message (for error type) */}
          {(formData.type === 'error' || formData.type === 'bug') && (
            <div className="space-y-2">
              <Label htmlFor="error_message">Pesan Error (opsional)</Label>
              <Textarea
                id="error_message"
                placeholder="Salin pesan error jika ada..."
                value={formData.error_message}
                onChange={(e) =>
                  setFormData((prev) => ({ ...prev, error_message: e.target.value }))
                }
                rows={2}
                maxLength={2000}
                className="font-mono text-sm"
              />
            </div>
          )}

          {/* Screenshots */}
          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <Label>Screenshot (opsional)</Label>
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={handleCaptureScreenshot}
                disabled={isCapturing || screenshots.length >= 3}
                className="gap-1"
              >
                <Camera className="h-4 w-4" />
                {isCapturing ? 'Mengambil...' : 'Ambil Screenshot'}
              </Button>
            </div>
            {screenshots.length > 0 && (
              <div className="flex gap-2 flex-wrap">
                {screenshots.map((screenshot, index) => (
                  <div key={index} className="relative group">
                    <img
                      src={screenshot}
                      alt={`Screenshot ${index + 1}`}
                      className="w-20 h-20 object-cover rounded border"
                    />
                    <button
                      type="button"
                      onClick={() => handleRemoveScreenshot(index)}
                      className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                    >
                      <X className="h-3 w-3" />
                    </button>
                  </div>
                ))}
              </div>
            )}
            <p className="text-xs text-muted-foreground">
              Maksimal 3 screenshot. Screenshot akan membantu kami memahami masalah.
            </p>
          </div>

          {/* Submit Button */}
          <div className="flex justify-end gap-2 pt-4">
            <Button type="button" variant="outline" onClick={() => setOpen(false)}>
              Batal
            </Button>
            <Button type="submit" disabled={isSubmitting} className="gap-2">
              {isSubmitting ? (
                <>
                  <span className="animate-spin">⏳</span>
                  Mengirim...
                </>
              ) : (
                <>
                  <Send className="h-4 w-4" />
                  Kirim Laporan
                </>
              )}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
}

// Floating Bug Report Button Component
export function FloatingBugReportButton() {
  return (
    <div className="fixed bottom-4 right-4 z-50">
      <BugReportDialog
        trigger={
          <Button
            size="icon"
            className="rounded-full h-12 w-12 shadow-lg bg-orange-500 hover:bg-orange-600"
            title="Laporkan Bug"
          >
            <Bug className="h-5 w-5" />
          </Button>
        }
      />
    </div>
  );
}

// Error Boundary Bug Report - Auto-populate with error info
export function ErrorBugReport({
  error,
  errorInfo,
}: {
  error: Error;
  errorInfo?: { componentStack?: string };
}) {
  return (
    <div className="p-4 bg-red-50 border border-red-200 rounded-lg">
      <div className="flex items-start gap-3">
        <AlertCircle className="h-5 w-5 text-red-500 mt-0.5" />
        <div className="flex-1">
          <h3 className="font-medium text-red-800">Terjadi Kesalahan</h3>
          <p className="text-sm text-red-600 mt-1">{error.message}</p>
          <div className="mt-3">
            <BugReportDialog
              defaultType="error"
              defaultErrorMessage={error.message}
              defaultErrorStack={errorInfo?.componentStack || error.stack}
              trigger={
                <Button variant="outline" size="sm" className="gap-2 text-red-600 border-red-300">
                  <Bug className="h-4 w-4" />
                  Laporkan Error Ini
                </Button>
              }
            />
          </div>
        </div>
      </div>
    </div>
  );
}
