import { Component, type ReactNode, type ErrorInfo } from 'react';
import { AlertTriangle, RefreshCcw, Home } from 'lucide-react';
import * as Sentry from '@sentry/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { logError } from '@/lib/utils/error';

interface Props {
  children: ReactNode;
  fallback?: ReactNode;
}

interface State {
  hasError: boolean;
  error: Error | null;
  errorInfo: ErrorInfo | null;
}

/**
 * ErrorBoundary - Catch React component errors
 *
 * Usage:
 * <ErrorBoundary>
 *   <YourApp />
 * </ErrorBoundary>
 */
export class ErrorBoundary extends Component<Props, State> {
  constructor(props: Props) {
    super(props);
    this.state = {
      hasError: false,
      error: null,
      errorInfo: null,
    };
  }

  static getDerivedStateFromError(error: Error): Partial<State> {
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    // Log error dengan detail component stack
    logError(error, 'React Error Boundary');

    console.error('Component Stack:', errorInfo.componentStack);

    this.setState({
      error,
      errorInfo,
    });

    // Send to Sentry
    Sentry.captureException(error, {
      contexts: {
        react: {
          componentStack: errorInfo.componentStack,
        },
      },
    });
  }

  handleReset = () => {
    this.setState({
      hasError: false,
      error: null,
      errorInfo: null,
    });
  };

  handleGoHome = () => {
    window.location.href = '/';
  };

  render() {
    if (this.state.hasError) {
      // Custom fallback
      if (this.props.fallback) {
        return this.props.fallback;
      }

      // Default error UI
      return (
        <div className="min-h-screen flex items-center justify-center p-4 bg-muted/30">
          <Card className="w-full max-w-2xl">
            <CardHeader>
              <div className="flex items-center gap-3">
                <div className="p-3 rounded-full bg-destructive/10">
                  <AlertTriangle className="h-6 w-6 text-destructive" />
                </div>
                <div>
                  <CardTitle className="text-2xl">Oops! Terjadi Kesalahan</CardTitle>
                  <CardDescription>
                    Aplikasi mengalami kesalahan yang tidak terduga
                  </CardDescription>
                </div>
              </div>
            </CardHeader>

            <CardContent className="space-y-4">
              <div className="rounded-lg bg-muted p-4">
                <p className="text-sm font-medium mb-2">Pesan Error:</p>
                <p className="text-sm text-muted-foreground font-mono">
                  {this.state.error?.message || 'Unknown error'}
                </p>
              </div>

              {import.meta.env.DEV && this.state.errorInfo && (
                <details className="rounded-lg bg-muted p-4">
                  <summary className="text-sm font-medium cursor-pointer mb-2">
                    🔧 Developer Info (Development Only)
                  </summary>
                  <div className="mt-3 space-y-3">
                    <div>
                      <p className="text-xs font-semibold text-muted-foreground mb-1">Stack Trace:</p>
                      <pre className="text-xs text-muted-foreground overflow-x-auto bg-background p-3 rounded border">
                        {this.state.error?.stack}
                      </pre>
                    </div>
                    <div>
                      <p className="text-xs font-semibold text-muted-foreground mb-1">Component Stack:</p>
                      <pre className="text-xs text-muted-foreground overflow-x-auto bg-background p-3 rounded border">
                        {this.state.errorInfo.componentStack}
                      </pre>
                    </div>
                  </div>
                </details>
              )}

              <div className="rounded-lg bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900 p-4">
                <p className="text-sm text-blue-800 dark:text-blue-200">
                  💡 <strong>Apa yang bisa Anda lakukan?</strong>
                </p>
                <ul className="mt-2 text-sm text-blue-700 dark:text-blue-300 space-y-1 ml-4 list-disc">
                  <li>Coba refresh halaman ini</li>
                  <li>Kembali ke halaman utama</li>
                  <li>Clear cache browser Anda</li>
                  <li>Jika masalah berlanjut, hubungi tim support</li>
                </ul>
              </div>
            </CardContent>

            <CardFooter className="flex gap-3">
              <Button onClick={this.handleReset} variant="outline" className="flex-1">
                <RefreshCcw className="mr-2 h-4 w-4" />
                Coba Lagi
              </Button>
              <Button onClick={this.handleGoHome} className="flex-1">
                <Home className="mr-2 h-4 w-4" />
                Kembali ke Beranda
              </Button>
            </CardFooter>
          </Card>
        </div>
      );
    }

    return this.props.children;
  }
}
