import { useIsMobile } from '@/lib/utils/device';
import MobileCorrectionsPage from './mobile';
import DesktopCorrectionsPage from './desktop';

export default function EmployeeCorrectionsPage() {
    const isMobile = useIsMobile();
    return isMobile ? <MobileCorrectionsPage /> : <DesktopCorrectionsPage />;
}
