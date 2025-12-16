import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import * as Sentry from '@sentry/react';
import './index.css';
import App from './app/App';

// Initialize Sentry for error tracking
Sentry.init({
  dsn: 'https://bfe05310c962f29ce3411adce7526eb1@o4509519502376960.ingest.de.sentry.io/4510521490407504',

  // Integrations
  integrations: [
    Sentry.browserTracingIntegration(),
    Sentry.replayIntegration({
      maskAllText: false,
      blockAllMedia: false,
    }),
  ],

  // Performance Monitoring
  tracesSampleRate: 0.1, // 10% of transactions for performance monitoring

  // Session Replay
  replaysSessionSampleRate: 0.1, // 10% of sessions recorded
  replaysOnErrorSampleRate: 1.0, // 100% of sessions with errors recorded

  // Environment
  environment: import.meta.env.MODE,

  // Send default PII (IP, user info)
  sendDefaultPii: true,

  // Only enable in production
  enabled: import.meta.env.PROD,
});

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <App />
  </StrictMode>
);
