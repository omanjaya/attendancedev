import { Suspense } from 'react';
import { RouterProvider } from '@tanstack/react-router';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import { Toaster, toast } from '@/components/ui/sonner';
import { PWAInstallPrompt } from '@/components/pwa/PWAInstallPrompt';
import { ErrorBoundary } from '@/components/error';
import { getErrorMessage, logError } from '@/lib/utils/error';

import { router } from './router';
import { Loader2 } from 'lucide-react';

// Create Query Client with global error handler
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5, // 5 minutes
      retry: 1,
      refetchOnWindowFocus: false,
    },
    mutations: {
      onError: (error) => {
        const message = getErrorMessage(error);
        toast.error(message);
        logError(error, 'Mutation');
      },
    },
  },
});

// Loading fallback component
function LoadingFallback() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-background">
      <div className="flex flex-col items-center gap-4">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
        <p className="text-sm text-muted-foreground">Loading...</p>
      </div>
    </div>
  );
}

export default function App() {
  return (
    <ErrorBoundary>
      <QueryClientProvider client={queryClient}>
        <Suspense fallback={<LoadingFallback />}>
          <RouterProvider router={router} />
        </Suspense>
        <Toaster position="top-right" richColors closeButton />
        <PWAInstallPrompt />
        <ReactQueryDevtools initialIsOpen={false} />
      </QueryClientProvider>
    </ErrorBoundary>
  );
}

