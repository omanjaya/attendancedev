import { useIsMobile } from '@/lib/utils/device';
import { MobileEmployeesPage } from './mobile';
import { DesktopEmployeesPage } from './desktop';

export default function EmployeesPage() {
    const isMobile = useIsMobile();
    return isMobile ? <MobileEmployeesPage /> : <DesktopEmployeesPage />;
}
