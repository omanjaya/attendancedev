import { useParams } from '@tanstack/react-router';
import { usePayrollEmployee } from '@/hooks/use-payroll';
import { useIsMobile } from '@/lib/utils/device';
import { MobilePayrollShowPage } from './mobile-show';
import { DesktopPayrollShowPage } from './desktop-show';

export default function PayrollShowPage() {
  const isMobile = useIsMobile();
  const params = useParams({
    from: '/authenticated/payroll/$periodId/employee/$employeeId',
  }) as {
    periodId: string;
    employeeId: string;
  };

  const { data: payroll, isLoading, error } = usePayrollEmployee(
    params.periodId,
    params.employeeId
  );

  if (isMobile) {
    return (
      <MobilePayrollShowPage
        payroll={payroll}
        isLoading={isLoading}
        error={error}
      />
    );
  }

  return (
    <DesktopPayrollShowPage
      payroll={payroll}
      isLoading={isLoading}
      error={error}
    />
  );
}
