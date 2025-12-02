import { useIsMobile } from '@/lib/utils/device';
import { MobileAdminLeavePage } from './mobile';
import { DesktopAdminLeavePage } from './desktop';

/**
 * Admin Leave Page
 * Manage employee leave requests
 */
export default function AdminLeavePage() {
  const isMobile = useIsMobile();
  return isMobile ? <MobileAdminLeavePage /> : <DesktopAdminLeavePage />;
}
