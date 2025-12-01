import { useIsMobile } from '@/lib/utils/device';
import { MobileLocationsPage } from './mobile';
import { DesktopLocationsPage } from './desktop';

export default function LocationsPage() {
  const isMobile = useIsMobile();

  if (isMobile) {
    return <MobileLocationsPage />;
  }

  return <DesktopLocationsPage />;
}
