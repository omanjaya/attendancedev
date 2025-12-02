import { useIsMobile } from '@/lib/utils/device';
import { MobileAdminAttendancePage } from './mobile';
import { DesktopAdminAttendancePage } from './desktop';

/**
 * Admin Attendance Page
 * Manage all employees' attendance with approval capabilities
 */
export default function AdminAttendancePage() {
  const isMobile = useIsMobile();
  return isMobile ? <MobileAdminAttendancePage /> : <DesktopAdminAttendancePage />;
}

