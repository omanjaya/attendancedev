import { useIsMobile } from '@/lib/utils/device';
import { MobileSchedulesPage } from './mobile';
import { DesktopSchedulesPage } from './desktop';

/**
 * Admin Schedules Page
 * Manage employee schedules
 */
export default function SchedulesPage() {
  const isMobile = useIsMobile();
  return isMobile ? <MobileSchedulesPage /> : <DesktopSchedulesPage />;
}
