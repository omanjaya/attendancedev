import { useIsMobile } from '@/lib/utils/device';
import MobileCorrectionsPage from './mobile';
import DesktopCorrectionsPage from './desktop';

export default function AdminCorrectionsPage() {
    const isMobile = useIsMobile();
    return isMobile ? <MobileCorrectionsPage /> : <DesktopCorrectionsPage />;
}
