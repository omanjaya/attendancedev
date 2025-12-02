import { useIsMobile } from '@/lib/utils/device';
import { MobileEmployeeReportsPage } from './mobile';
import { DesktopEmployeeReportsPage } from './desktop';

export default function EmployeeReportsPage() {
  const isMobile = useIsMobile();
  return isMobile ? <MobileEmployeeReportsPage /> : <DesktopEmployeeReportsPage />;
}
