import { useIsMobile } from '@/lib/utils/device';
import { MobileEmployeePayrollPage } from './mobile';
import { DesktopEmployeePayrollPage } from './desktop';

/**
 * Employee Payroll Page
 * View personal payslips (read-only)
 */
export default function EmployeePayrollPage() {
  const isMobile = useIsMobile();
  return isMobile ? <MobileEmployeePayrollPage /> : <DesktopEmployeePayrollPage />;
}
