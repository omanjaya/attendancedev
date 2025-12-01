import { useIsMobile } from '@/lib/utils/device';
import { MobileHolidaysPage } from './mobile';
import { DesktopHolidaysPage } from './desktop';

export default function HolidaysPage() {
  const isMobile = useIsMobile();

  if (isMobile) {
    return <MobileHolidaysPage />;
  }

  return <DesktopHolidaysPage />;
}
