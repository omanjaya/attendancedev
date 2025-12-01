import { useIsMobile } from '@/lib/utils/device';
import { MobileUsersPage } from './mobile';
import { DesktopUsersPage } from './desktop';

export default function UsersPage() {
  const isMobile = useIsMobile();

  if (isMobile) {
    return <MobileUsersPage />;
  }

  return <DesktopUsersPage />;
}
