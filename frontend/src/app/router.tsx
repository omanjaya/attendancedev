import { createRouter, createRootRoute, createRoute, redirect, Outlet } from '@tanstack/react-router';
import { useAuthStore } from '@/stores';
import { AppShell } from '@/components/layout';
import { requireAdmin, requireEmployee, requireAuth as requireAuthGuard } from '@/lib/auth/guards';
import { getDefaultRedirect } from '@/lib/auth';

// Lazy load pages
import { lazy } from 'react';

const LoginPage = lazy(() => import('@/pages/login'));

// Admin pages
const AdminDashboard = lazy(() => import('@/pages/admin/dashboard/index'));
const AdminAttendancePage = lazy(() => import('@/pages/admin/attendance'));

// Employee pages
const EmployeeDashboard = lazy(() => import('@/pages/employee/dashboard'));
const EmployeeAttendancePage = lazy(() => import('@/pages/employee/attendance'));
const EmployeeSchedulePage = lazy(() => import('@/pages/employee/schedule'));
const EmployeeLeavePage = lazy(() => import('@/pages/employee/leave'));
const EmployeePayrollPage = lazy(() => import('@/pages/employee/payroll'));
const EmployeeReportsPage = lazy(() => import('@/pages/employee/reports'));

// Shared pages
const VerifyLocationPage = lazy(() => import('@/pages/shared/verify-location'));
const VerifyFacePage = lazy(() => import('@/pages/shared/verify-face'));

// Error pages
const UnauthorizedPage = lazy(() => import('@/pages/unauthorized'));
const NotFoundPage = lazy(() => import('@/pages/not-found'));

// Admin - Employee Management (moved from /employees to /admin/employees)
const EmployeesPage = lazy(() => import('@/pages/admin/employees'));
const EmployeeCreatePage = lazy(() => import('@/pages/admin/employees/create'));
const EmployeeShowPage = lazy(() => import('@/pages/admin/employees/show'));
const EmployeeEditPage = lazy(() => import('@/pages/admin/employees/edit'));
const EmployeeCredentialsPage = lazy(() => import('@/pages/admin/employees/credentials'));

// Admin - Schedule Management
const SchedulesPage = lazy(() => import('@/pages/admin/schedules'));
const ScheduleCreatePage = lazy(() => import('@/pages/admin/schedules/create'));
const ScheduleShowPage = lazy(() => import('@/pages/admin/schedules/show'));
const ScheduleEditPage = lazy(() => import('@/pages/admin/schedules/edit'));
const ScheduleBuilderPage = lazy(() => import('@/pages/admin/schedules/builder'));
const ScheduleAssignPage = lazy(() => import('@/pages/admin/schedules/assign'));
const ScheduleCalendarPage = lazy(() => import('@/pages/admin/schedules/calendar'));
const MonthlyScheduleIndexPage = lazy(() => import('@/pages/admin/schedules/monthly'));
const MonthlyScheduleCreatePage = lazy(() => import('@/pages/admin/schedules/monthly/create'));
const MonthlyScheduleEditPage = lazy(() => import('@/pages/admin/schedules/monthly/edit'));

// Admin - Leave Management
const LeavePage = lazy(() => import('@/pages/admin/leave'));
const LeaveShowPage = lazy(() => import('@/pages/admin/leave/show'));
const LeaveApprovalsPage = lazy(() => import('@/pages/admin/leave/approvals'));
const LeaveCalendarPage = lazy(() => import('@/pages/admin/leave/calendar'));
const LeaveCreatePage = lazy(() => import('@/pages/admin/leave/create'));

// Admin - Payroll Management
const PayrollPage = lazy(() => import('@/pages/admin/payroll'));
const PayrollShowPage = lazy(() => import('@/pages/admin/payroll/show'));
const PayrollEditPage = lazy(() => import('@/pages/admin/payroll/edit'));

// Admin - Reports
const ReportsPage = lazy(() => import('@/pages/admin/reports'));
const ReportBuilderPage = lazy(() => import('@/pages/admin/reports/builder'));

// Admin - Face Recognition
const FaceRecognitionPage = lazy(() => import('@/pages/admin/face-recognition'));
const FaceRecognitionSettingsPage = lazy(() => import('@/pages/admin/face-recognition/settings'));

// Admin - Settings & Security
const SettingsPage = lazy(() => import('@/pages/admin/settings'));
const SecurityPage = lazy(() => import('@/pages/admin/security'));
const SecurityEventsPage = lazy(() => import('@/pages/admin/security/events'));
const SecurityDevicesPage = lazy(() => import('@/pages/admin/security/devices'));
const TwoFactorPage = lazy(() => import('@/pages/admin/security/two-factor'));

// Admin - System Management
const UsersPage = lazy(() => import('@/pages/admin/users'));
const UserCreatePage = lazy(() => import('@/pages/admin/users/create'));
const UserShowPage = lazy(() => import('@/pages/admin/users/show'));
const UserEditPage = lazy(() => import('@/pages/admin/users/edit'));

const LocationsPage = lazy(() => import('@/pages/admin/locations'));
const LocationCreatePage = lazy(() => import('@/pages/admin/locations/create'));
const LocationShowPage = lazy(() => import('@/pages/admin/locations/show'));
const LocationEditPage = lazy(() => import('@/pages/admin/locations/edit'));

const HolidaysPage = lazy(() => import('@/pages/admin/holidays'));
const HolidayShowPage = lazy(() => import('@/pages/admin/holidays/show'));
const HolidayEditPage = lazy(() => import('@/pages/admin/holidays/edit'));
const HolidayCalendarPage = lazy(() => import('@/pages/admin/holidays/calendar'));
const HolidayCreatePage = lazy(() => import('@/pages/admin/holidays/create'));

const MasterDataPage = lazy(() => import('@/pages/admin/master-data'));
const EmployeeTypesPage = lazy(() => import('@/pages/admin/settings/employee-types'));

// Employee - Profile
const ProfilePage = lazy(() => import('@/pages/employee/profile'));
const ProfileEditPage = lazy(() => import('@/pages/employee/profile/edit'));

// Auth pages
const VerifyEmailPage = lazy(() => import('@/pages/auth/verify-email'));
const ConfirmPasswordPage = lazy(() => import('@/pages/auth/confirm-password'));
const ChangePasswordPage = lazy(() => import('@/pages/auth/change-password'));
const ForgotPasswordPage = lazy(() => import('@/pages/auth/forgot-password'));
const ResetPasswordPage = lazy(() => import('@/pages/auth/reset-password'));

// Root layout component
function RootLayout() {
  return <Outlet />;
}

// Authenticated layout with sidebar
function AuthenticatedLayout() {
  return <AppShell />;
}

// Auth guard - redirect to login if not authenticated
const requireAuth = () => {
  const { isAuthenticated, user } = useAuthStore.getState();
  if (!isAuthenticated) {
    throw redirect({ to: '/login' });
  }
  return {
    auth: {
      user,
      isAuthenticated,
    },
  };
};

// Guest guard - redirect to appropriate dashboard if already authenticated
const requireGuest = () => {
  const { isAuthenticated, user } = useAuthStore.getState();
  if (isAuthenticated && user) {
    const defaultPath = getDefaultRedirect(user);
    throw redirect({ to: defaultPath });
  }
};

// Root route
const rootRoute = createRootRoute({
  component: RootLayout,
});

// Public routes
const loginRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/login',
  beforeLoad: requireGuest,
  component: LoginPage,
});

const forgotPasswordRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/auth/forgot-password',
  beforeLoad: requireGuest,
  component: ForgotPasswordPage,
});

const resetPasswordRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/auth/reset-password',
  beforeLoad: requireGuest,
  component: ResetPasswordPage,
});

// Index route - redirect to appropriate dashboard based on role
const indexRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/',
  beforeLoad: () => {
    const { isAuthenticated, user } = useAuthStore.getState();
    if (!isAuthenticated) {
      throw redirect({ to: '/login' });
    }
    const defaultPath = getDefaultRedirect(user);
    throw redirect({ to: defaultPath });
  },
});

// Authenticated layout route
const authenticatedRoute = createRoute({
  getParentRoute: () => rootRoute,
  id: 'authenticated',
  beforeLoad: requireAuth,
  component: AuthenticatedLayout,
});

// Unauthorized page
const unauthorizedRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/unauthorized',
  component: UnauthorizedPage,
});

// ====================================
// ADMIN ROUTES
// ====================================

// Admin Dashboard
const adminDashboardRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/dashboard',
  beforeLoad: requireAdmin,
  component: AdminDashboard,
});

// Admin Attendance
const adminAttendanceRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/attendance',
  beforeLoad: requireAdmin,
  component: AdminAttendancePage,
});

// Admin Employees
const adminEmployeesRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/employees',
  beforeLoad: requireAdmin,
  component: EmployeesPage,
});

const adminEmployeeCreateRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/employees/create',
  beforeLoad: requireAdmin,
  component: EmployeeCreatePage,
});

const adminEmployeeCredentialsRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/employees/credentials',
  beforeLoad: requireAdmin,
  component: EmployeeCredentialsPage,
});

const adminEmployeeShowRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/employees/$id',
  beforeLoad: requireAdmin,
  component: EmployeeShowPage,
});

const adminEmployeeEditRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/employees/$id/edit',
  beforeLoad: requireAdmin,
  component: EmployeeEditPage,
});

// Admin Schedules
const adminSchedulesRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/schedules',
  beforeLoad: requireAdmin,
  component: SchedulesPage,
});

const adminScheduleCreateRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/schedules/create',
  beforeLoad: requireAdmin,
  component: ScheduleCreatePage,
});

const adminScheduleBuilderRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/schedules/builder',
  beforeLoad: requireAdmin,
  component: ScheduleBuilderPage,
});

const adminScheduleAssignRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/schedules/assign',
  beforeLoad: requireAdmin,
  component: ScheduleAssignPage,
});

const adminScheduleCalendarRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/schedules/calendar',
  beforeLoad: requireAdmin,
  component: ScheduleCalendarPage,
});

const adminMonthlyScheduleIndexRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/schedules/monthly',
  beforeLoad: requireAdmin,
  component: MonthlyScheduleIndexPage,
});

const adminMonthlyScheduleCreateRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/schedules/monthly/create',
  beforeLoad: requireAdmin,
  component: MonthlyScheduleCreatePage,
});

const adminMonthlyScheduleEditRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/schedules/monthly/$id/edit',
  beforeLoad: requireAdmin,
  component: MonthlyScheduleEditPage,
});

const adminScheduleShowRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/schedules/$id',
  beforeLoad: requireAdmin,
  component: ScheduleShowPage,
});

const adminScheduleEditRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/schedules/$id/edit',
  beforeLoad: requireAdmin,
  component: ScheduleEditPage,
});

// Admin Leave
const adminLeaveRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/leave',
  beforeLoad: requireAdmin,
  component: LeavePage,
});

const adminLeaveCreateRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/leave/create',
  beforeLoad: requireAdmin,
  component: LeaveCreatePage,
});

const adminLeaveApprovalsRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/leave/approvals',
  beforeLoad: requireAdmin,
  component: LeaveApprovalsPage,
});

const adminLeaveCalendarRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/leave/calendar',
  beforeLoad: requireAdmin,
  component: LeaveCalendarPage,
});

const adminLeaveShowRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/leave/$id',
  beforeLoad: requireAdmin,
  component: LeaveShowPage,
});

// Admin Payroll
const adminPayrollRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/payroll',
  beforeLoad: requireAdmin,
  component: PayrollPage,
});

const adminPayrollShowRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/payroll/$periodId/employee/$employeeId',
  beforeLoad: requireAdmin,
  component: PayrollShowPage,
});

const adminPayrollEditRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/payroll/$periodId/employee/$employeeId/edit',
  beforeLoad: requireAdmin,
  component: PayrollEditPage,
});

// Admin Reports
const adminReportsRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/reports',
  beforeLoad: requireAdmin,
  component: ReportsPage,
});

const adminReportBuilderRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/reports/builder',
  beforeLoad: requireAdmin,
  component: ReportBuilderPage,
});

// Admin Face Recognition
const adminFaceRecognitionRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/face-recognition',
  beforeLoad: requireAdmin,
  component: FaceRecognitionPage,
});

const adminFaceRecognitionSettingsRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/face-recognition/settings',
  beforeLoad: requireAdmin,
  component: FaceRecognitionSettingsPage,
});

// Admin Settings
const adminSettingsRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/settings',
  beforeLoad: requireAdmin,
  component: SettingsPage,
});

const adminSettingsEmployeeTypesRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/settings/employee-types',
  beforeLoad: requireAdmin,
  component: EmployeeTypesPage,
});

// Admin Security
const adminSecurityRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/security',
  beforeLoad: requireAdmin,
  component: SecurityPage,
});

const adminSecurityEventsRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/security/events',
  beforeLoad: requireAdmin,
  component: SecurityEventsPage,
});

const adminSecurityDevicesRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/security/devices',
  beforeLoad: requireAdmin,
  component: SecurityDevicesPage,
});

const adminTwoFactorRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/security/two-factor',
  beforeLoad: requireAdmin,
  component: TwoFactorPage,
});

// Admin Users
const adminUsersRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/users',
  beforeLoad: requireAdmin,
  component: UsersPage,
});

const adminUserCreateRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/users/create',
  beforeLoad: requireAdmin,
  component: UserCreatePage,
});

const adminUserShowRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/users/$id',
  beforeLoad: requireAdmin,
  component: UserShowPage,
});

const adminUserEditRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/users/$id/edit',
  beforeLoad: requireAdmin,
  component: UserEditPage,
});

// Admin Locations
const adminLocationsRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/locations',
  beforeLoad: requireAdmin,
  component: LocationsPage,
});

const adminLocationCreateRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/locations/create',
  beforeLoad: requireAdmin,
  component: LocationCreatePage,
});

const adminLocationShowRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/locations/$id',
  beforeLoad: requireAdmin,
  component: LocationShowPage,
});

const adminLocationEditRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/locations/$id/edit',
  beforeLoad: requireAdmin,
  component: LocationEditPage,
});

// Admin Holidays
const adminHolidaysRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/holidays',
  beforeLoad: requireAdmin,
  component: HolidaysPage,
});

const adminHolidayCreateRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/holidays/create',
  beforeLoad: requireAdmin,
  component: HolidayCreatePage,
});

const adminHolidayCalendarRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/holidays/calendar',
  beforeLoad: requireAdmin,
  component: HolidayCalendarPage,
});

const adminHolidayShowRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/holidays/$id',
  beforeLoad: requireAdmin,
  component: HolidayShowPage,
});

const adminHolidayEditRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/holidays/$id/edit',
  beforeLoad: requireAdmin,
  component: HolidayEditPage,
});

// Admin Master Data
const adminMasterDataRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/master-data',
  beforeLoad: requireAdmin,
  component: MasterDataPage,
});

// ====================================
// EMPLOYEE ROUTES
// ====================================

// Employee Dashboard
const employeeDashboardRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/employee/dashboard',
  beforeLoad: requireEmployee,
  component: EmployeeDashboard,
});

// Employee Attendance
const employeeAttendanceRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/employee/attendance',
  beforeLoad: requireEmployee,
  component: EmployeeAttendancePage,
});

// Employee Schedule
const employeeScheduleRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/employee/schedule',
  beforeLoad: requireEmployee,
  component: EmployeeSchedulePage,
});

// Employee Leave
const employeeLeaveRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/employee/leave',
  beforeLoad: requireEmployee,
  component: EmployeeLeavePage,
});

const employeeLeaveCreateRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/employee/leave/create',
  beforeLoad: requireEmployee,
  component: EmployeeLeavePage, // Reuse same page, form is built-in
});

// Employee Payroll
const employeePayrollRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/employee/payroll',
  beforeLoad: requireEmployee,
  component: EmployeePayrollPage,
});

// Employee Reports
const employeeReportsRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/employee/reports',
  beforeLoad: requireEmployee,
  component: EmployeeReportsPage,
});

// Employee Profile
const employeeProfileRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/employee/profile',
  beforeLoad: requireEmployee,
  component: ProfilePage,
});

const employeeProfileEditRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/employee/profile/edit',
  beforeLoad: requireEmployee,
  component: ProfileEditPage,
});

// ====================================
// SHARED ROUTES (Both admin and employee)
// ====================================

const sharedVerifyLocationRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/shared/verify-location',
  beforeLoad: requireAuthGuard,
  component: VerifyLocationPage,
});

const sharedVerifyFaceRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/shared/verify-face',
  beforeLoad: requireAuthGuard,
  component: VerifyFacePage,
});

// ====================================
// AUTH & OTHER ROUTES
// ====================================

const verifyEmailRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/verify-email',
  component: VerifyEmailPage,
});

const changePasswordRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/auth/change-password',
  component: ChangePasswordPage,
});

const confirmPasswordRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/confirm-password',
  component: ConfirmPasswordPage,
});

// Catch-all 404 route
const notFoundRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '*',
  component: NotFoundPage,
});

// Route tree
const routeTree = rootRoute.addChildren([
  indexRoute,
  loginRoute,
  forgotPasswordRoute,
  resetPasswordRoute,
  verifyEmailRoute,
  unauthorizedRoute,
  authenticatedRoute.addChildren([
    // Admin routes
    adminDashboardRoute,
    adminAttendanceRoute,
    adminEmployeesRoute,
    adminEmployeeCreateRoute,
    adminEmployeeCredentialsRoute,
    adminEmployeeShowRoute,
    adminEmployeeEditRoute,
    adminSchedulesRoute,
    adminScheduleCreateRoute,
    adminScheduleBuilderRoute,
    adminScheduleAssignRoute,
    adminScheduleCalendarRoute,
    adminMonthlyScheduleIndexRoute,
    adminMonthlyScheduleCreateRoute,
    adminMonthlyScheduleEditRoute,
    adminScheduleShowRoute,
    adminScheduleEditRoute,
    adminLeaveRoute,
    adminLeaveCreateRoute,
    adminLeaveApprovalsRoute,
    adminLeaveCalendarRoute,
    adminLeaveShowRoute,
    adminPayrollRoute,
    adminPayrollShowRoute,
    adminPayrollEditRoute,
    adminReportsRoute,
    adminReportBuilderRoute,
    adminFaceRecognitionRoute,
    adminFaceRecognitionSettingsRoute,
    adminSettingsRoute,
    adminSecurityRoute,
    adminSecurityEventsRoute,
    adminSecurityDevicesRoute,
    adminTwoFactorRoute,
    adminUsersRoute,
    adminUserCreateRoute,
    adminUserShowRoute,
    adminUserEditRoute,
    adminLocationsRoute,
    adminLocationCreateRoute,
    adminLocationShowRoute,
    adminLocationEditRoute,
    adminHolidaysRoute,
    adminHolidayCreateRoute,
    adminHolidayCalendarRoute,
    adminHolidayShowRoute,
    adminHolidayEditRoute,
    adminMasterDataRoute,
    adminSettingsEmployeeTypesRoute,
    // Employee routes
    employeeDashboardRoute,
    employeeAttendanceRoute,
    employeeScheduleRoute,
    employeeLeaveRoute,
    employeeLeaveCreateRoute,
    employeePayrollRoute,
    employeeReportsRoute,
    employeeProfileRoute,
    employeeProfileEditRoute,
    // Shared routes
    sharedVerifyLocationRoute,
    sharedVerifyFaceRoute,
    // Auth routes
    changePasswordRoute,
    confirmPasswordRoute,
  ]),
  notFoundRoute,
]);

// Create router instance
export const router = createRouter({
  routeTree,
  defaultPreload: 'intent',
  defaultPreloadStaleTime: 0,
});

// Type declaration for router
declare module '@tanstack/react-router' {
  interface Register {
    router: typeof router;
  }
}
