import { useIsMobile } from '@/lib/utils/device';
import { MobileEmployeeDashboard } from './mobile';
import { DesktopEmployeeDashboard } from './desktop';

export default function EmployeeDashboardPage() {
    const isMobile = useIsMobile();
    return isMobile ? <MobileEmployeeDashboard /> : <DesktopEmployeeDashboard />;
}
