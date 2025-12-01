import { useIsMobile } from '@/lib/utils/device';
import { MobileReportsPage } from './mobile';
import { DesktopReportsPage } from './desktop';

export default function ReportsPage() {
    const isMobile = useIsMobile();
    return isMobile ? <MobileReportsPage /> : <DesktopReportsPage />;
}
