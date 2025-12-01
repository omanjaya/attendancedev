import { useIsMobile } from '@/lib/utils/device';
import { MobileSettingsPage } from './mobile';
import { DesktopSettingsPage } from './desktop';

export default function SettingsPage() {
  const isMobile = useIsMobile();

  if (isMobile) {
    return <MobileSettingsPage />;
  }

  return <DesktopSettingsPage />;
}
