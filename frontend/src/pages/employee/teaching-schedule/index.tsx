import { useIsMobile } from '@/lib/utils/device';
import { MobileTeachingSchedulePage } from './mobile';
import { DesktopTeachingSchedulePage } from './desktop';

/**
 * Teaching Schedule Page
 * For teachers/guru to view their teaching schedules
 */
export default function TeachingSchedulePage() {
  const isMobile = useIsMobile();
  return isMobile ? <MobileTeachingSchedulePage /> : <DesktopTeachingSchedulePage />;
}
