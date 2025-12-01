import { ReactNode } from 'react';
import { MobileCardList } from './mobile-card-list';
import type { MobileCardListProps } from './mobile-card-list';

interface ResponsiveDataViewProps<T> extends MobileCardListProps<T> {
  desktopView: ReactNode;
  mobileView?: ReactNode;
}

/**
 * Responsive component that shows DataTable on desktop and MobileCardList on mobile
 * Usage:
 * <ResponsiveDataView
 *   items={employees}
 *   desktopView={<DataTable columns={columns} data={employees} />}
 *   renderCard={(employee) => <EmployeeCard employee={employee} />}
 *   onItemClick={(employee) => navigate(`/employees/${employee.id}`)}
 * />
 */
export function ResponsiveDataView<T>({
  items,
  renderCard,
  onItemClick,
  loading,
  emptyMessage,
  showChevron,
  desktopView,
  mobileView,
}: ResponsiveDataViewProps<T>) {
  return (
    <>
      {/* Desktop View - Hidden on mobile */}
      <div className="hidden md:block">{desktopView}</div>

      {/* Mobile View - Hidden on desktop */}
      <div className="block md:hidden">
        {mobileView || (
          <MobileCardList
            items={items}
            renderCard={renderCard}
            onItemClick={onItemClick}
            loading={loading}
            emptyMessage={emptyMessage}
            showChevron={showChevron}
          />
        )}
      </div>
    </>
  );
}
