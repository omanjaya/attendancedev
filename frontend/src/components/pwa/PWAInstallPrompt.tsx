import { Download, X, RefreshCw } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { usePWA } from '@/hooks/use-pwa';

export function PWAInstallPrompt() {
    const { isInstallable, isInstalled, isUpdateAvailable, installApp, dismissInstall, updateApp } = usePWA();

    // Don't show if already installed and no update
    if ((!isInstallable && !isUpdateAvailable) || isInstalled) {
        return null;
    }

    // Show update banner
    if (isUpdateAvailable) {
        return (
            <div className="fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:w-96 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl shadow-2xl p-4 z-50 animate-in slide-in-from-bottom-4">
                <div className="flex items-start gap-3">
                    <div className="flex-shrink-0 p-2 bg-white/20 rounded-lg">
                        <RefreshCw className="h-5 w-5" />
                    </div>
                    <div className="flex-1 min-w-0">
                        <h3 className="font-semibold text-sm">Update Tersedia</h3>
                        <p className="text-xs text-blue-100 mt-0.5">
                            Versi baru aplikasi sudah siap digunakan
                        </p>
                    </div>
                    <Button
                        size="sm"
                        variant="secondary"
                        className="flex-shrink-0 bg-white text-blue-700 hover:bg-blue-50"
                        onClick={updateApp}
                    >
                        Update
                    </Button>
                </div>
            </div>
        );
    }

    // Show install prompt
    if (isInstallable) {
        return (
            <div className="fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:w-96 bg-gradient-to-r from-slate-800 to-slate-900 text-white rounded-xl shadow-2xl p-4 z-50 animate-in slide-in-from-bottom-4">
                <button
                    onClick={dismissInstall}
                    className="absolute top-2 right-2 p-1 hover:bg-white/10 rounded-full transition-colors"
                    aria-label="Tutup"
                >
                    <X className="h-4 w-4" />
                </button>
                <div className="flex items-start gap-3">
                    <div className="flex-shrink-0 p-2 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg">
                        <Download className="h-5 w-5" />
                    </div>
                    <div className="flex-1 min-w-0 pr-4">
                        <h3 className="font-semibold text-sm">Install Aplikasi</h3>
                        <p className="text-xs text-slate-300 mt-0.5">
                            Akses lebih cepat langsung dari home screen
                        </p>
                    </div>
                </div>
                <div className="flex gap-2 mt-3">
                    <Button
                        size="sm"
                        variant="ghost"
                        className="flex-1 text-slate-300 hover:text-white hover:bg-white/10"
                        onClick={dismissInstall}
                    >
                        Nanti
                    </Button>
                    <Button
                        size="sm"
                        className="flex-1 bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600"
                        onClick={installApp}
                    >
                        <Download className="h-4 w-4 mr-1" />
                        Install
                    </Button>
                </div>
            </div>
        );
    }

    return null;
}
