import { useIsMobile } from '@/lib/utils/device';
import { MobileEmployeeAttendancePage } from './mobile';
import { DesktopEmployeeAttendancePage } from './desktop';

/**
 * Employee Attendance Page
 * Handles responsive rendering for attendance page
 */
export default function EmployeeAttendancePage() {
  const isMobile = useIsMobile();
  return isMobile ? <MobileEmployeeAttendancePage /> : <DesktopEmployeeAttendancePage />;
}

