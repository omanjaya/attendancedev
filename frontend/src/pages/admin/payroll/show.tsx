import { useParams } from '@tanstack/react-router';
import { usePayrollEmployee } from '@/hooks/use-payroll';
import { useIsMobile } from '@/lib/utils/device';
import { MobilePayrollShowPage } from './mobile-show';
import { DesktopPayrollShowPage } from './desktop-show';
import type { PayrollEmployeeDetail } from '@/types/payroll';

export default function PayrollShowPage() {
  const isMobile = useIsMobile();
  const params = useParams({
    strict: false,
  }) as {
    periodId: string;
    employeeId: string;
  };

  const { data: payroll, isLoading, error } = usePayrollEmployee(
    params.periodId,
    params.employeeId
  );

  // Cast to PayrollEmployeeDetail since that's what the API returns
  const payrollData = payroll as PayrollEmployeeDetail | undefined;

  if (isMobile) {
    return (
      <MobilePayrollShowPage
        payroll={payrollData || null}
        isLoading={isLoading}
        error={error}
      />
    );
  }

  return (
    <DesktopPayrollShowPage
      payroll={payrollData || null}
      isLoading={isLoading}
      error={error}
    />
  );
}
