import { useIsMobile } from '@/lib/utils/device';
import { MobilePayrollPage } from './mobile';
import { DesktopPayrollPage } from './desktop';

export default function PayrollPage() {
    const isMobile = useIsMobile();
    return isMobile ? <MobilePayrollPage /> : <DesktopPayrollPage />;
}
