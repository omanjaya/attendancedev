import { useIsMobile } from '@/lib/utils/device';
import { MobileProfilePage } from './mobile';
import { DesktopProfilePage } from './desktop';

export default function ProfilePage() {
  const isMobile = useIsMobile();
  return isMobile ? <MobileProfilePage /> : <DesktopProfilePage />;
}
