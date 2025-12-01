import { useIsMobile } from '@/lib/utils/device';
import { MobileDashboard, DesktopDashboard } from '@/components/dashboard';

export default function DashboardPage() {
  const isMobile = useIsMobile();

  return isMobile ? <MobileDashboard /> : <DesktopDashboard />;
}
