import { useIsMobile } from '@/lib/utils/device';
import { MobileEmployeeLeavePage } from './mobile';
import { DesktopEmployeeLeavePage } from './desktop';

/**
 * Employee Leave Page
 * Request and view personal leave requests
 */
export default function EmployeeLeavePage() {
  const isMobile = useIsMobile();
  return isMobile ? <MobileEmployeeLeavePage /> : <DesktopEmployeeLeavePage />;
}
