import { useIsMobile } from '@/lib/utils/device';
import { MobileEmployeeSchedulePage } from './mobile';
import { DesktopEmployeeSchedulePage } from './desktop';

/**
 * Employee Schedule Page
 * Read-only view for employees to see their assigned schedules
 */
export default function EmployeeSchedulePage() {
  const isMobile = useIsMobile();
  return isMobile ? <MobileEmployeeSchedulePage /> : <DesktopEmployeeSchedulePage />;
}
