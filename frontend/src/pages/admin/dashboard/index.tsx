import { useIsMobile } from '@/lib/utils/device';
import { MobileAdminDashboard } from './mobile';
import { DesktopAdminDashboard } from './desktop';

/**
 * Admin Dashboard
 * Shows company-wide statistics, pending approvals, and quick actions
 */
export default function AdminDashboard() {
    const isMobile = useIsMobile();
    return isMobile ? <MobileAdminDashboard /> : <DesktopAdminDashboard />;
}
