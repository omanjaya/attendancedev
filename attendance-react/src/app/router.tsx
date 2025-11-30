import { createRouter, createRootRoute, createRoute, redirect, Outlet } from '@tanstack/react-router';
import { useAuthStore } from '@/stores';
import { AppShell } from '@/components/layout';

// Lazy load pages
import { lazy } from 'react';

const LoginPage = lazy(() => import('@/pages/login'));
const DashboardPage = lazy(() => import('@/pages/dashboard'));

// Employee pages
const EmployeesPage = lazy(() => import('@/pages/employees'));
const EmployeeCreatePage = lazy(() => import('@/pages/employees/create'));
const EmployeeShowPage = lazy(() => import('@/pages/employees/show'));
const EmployeeEditPage = lazy(() => import('@/pages/employees/edit'));
const EmployeeCredentialsPage = lazy(() => import('@/pages/employees/credentials'));

// Attendance pages
const AttendancePage = lazy(() => import('@/pages/attendance'));
const FaceRecognitionPage = lazy(() => import('@/pages/face-recognition'));
const FaceRecognitionSettingsPage = lazy(() => import('@/pages/face-recognition/settings'));

// Schedule pages
const SchedulesPage = lazy(() => import('@/pages/schedules'));
const ScheduleCreatePage = lazy(() => import('@/pages/schedules/create'));
const ScheduleShowPage = lazy(() => import('@/pages/schedules/show'));
const ScheduleEditPage = lazy(() => import('@/pages/schedules/edit'));
const ScheduleBuilderPage = lazy(() => import('@/pages/schedules/builder'));
const ScheduleAssignPage = lazy(() => import('@/pages/schedules/assign'));
const MonthlyScheduleIndexPage = lazy(() => import('@/pages/schedules/monthly'));
const MonthlyScheduleCreatePage = lazy(() => import('@/pages/schedules/monthly/create'));

// Leave pages
const LeavePage = lazy(() => import('@/pages/leave'));
const LeaveShowPage = lazy(() => import('@/pages/leave/show'));
const LeaveApprovalsPage = lazy(() => import('@/pages/leave/approvals'));

// Payroll pages
const PayrollPage = lazy(() => import('@/pages/payroll'));
const PayrollShowPage = lazy(() => import('@/pages/payroll/show'));
const PayrollEditPage = lazy(() => import('@/pages/payroll/edit'));

// Reports pages
const ReportsPage = lazy(() => import('@/pages/reports'));
const ReportBuilderPage = lazy(() => import('@/pages/reports/builder'));

// Admin pages
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

// Security pages
const SecurityPage = lazy(() => import('@/pages/security'));
const SecurityEventsPage = lazy(() => import('@/pages/security/events'));
const TwoFactorPage = lazy(() => import('@/pages/security/two-factor'));

// Profile pages
const ProfilePage = lazy(() => import('@/pages/profile'));
const ProfileEditPage = lazy(() => import('@/pages/profile/edit'));

// Settings
const SettingsPage = lazy(() => import('@/pages/settings'));

// Auth pages
const VerifyEmailPage = lazy(() => import('@/pages/auth/verify-email'));
const ConfirmPasswordPage = lazy(() => import('@/pages/auth/confirm-password'));

// Error pages
const NotFoundPage = lazy(() => import('@/pages/not-found'));

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
  const isAuthenticated = useAuthStore.getState().isAuthenticated;
  if (!isAuthenticated) {
    throw redirect({ to: '/login' });
  }
};

// Guest guard - redirect to dashboard if already authenticated
const requireGuest = () => {
  const isAuthenticated = useAuthStore.getState().isAuthenticated;
  if (isAuthenticated) {
    throw redirect({ to: '/dashboard' });
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

// Index route - redirect to dashboard or login
const indexRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/',
  beforeLoad: () => {
    const isAuthenticated = useAuthStore.getState().isAuthenticated;
    throw redirect({ to: isAuthenticated ? '/dashboard' : '/login' });
  },
});

// Authenticated layout route
const authenticatedRoute = createRoute({
  getParentRoute: () => rootRoute,
  id: 'authenticated',
  beforeLoad: requireAuth,
  component: AuthenticatedLayout,
});

// Dashboard route (under authenticated layout)
const dashboardRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/dashboard',
  component: DashboardPage,
});

// Placeholder routes for navigation
const attendanceRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/attendance',
  component: AttendancePage,
});

const faceRecognitionRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/face-recognition',
  component: FaceRecognitionPage,
});

const faceRecognitionSettingsRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/face-recognition/settings',
  component: FaceRecognitionSettingsPage,
});

// Employee routes
const employeesRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/employees',
  component: EmployeesPage,
});

const employeeCreateRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/employees/create',
  component: EmployeeCreatePage,
});

const employeeCredentialsRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/employees/credentials',
  component: EmployeeCredentialsPage,
});

const employeeShowRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/employees/$id',
  component: EmployeeShowPage,
});

const employeeEditRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/employees/$id/edit',
  component: EmployeeEditPage,
});

// Schedule routes
const schedulesRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/schedules',
  component: SchedulesPage,
});

const scheduleCreateRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/schedules/create',
  component: ScheduleCreatePage,
});

const scheduleShowRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/schedules/$id',
  component: ScheduleShowPage,
});

const scheduleEditRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/schedules/$id/edit',
  component: ScheduleEditPage,
});

const scheduleBuilderRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/schedules/builder',
  component: ScheduleBuilderPage,
});

const scheduleAssignRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/schedules/assign',
  component: ScheduleAssignPage,
});

const monthlyScheduleIndexRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/schedules/monthly',
  component: MonthlyScheduleIndexPage,
});

const monthlyScheduleCreateRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/schedules/monthly/create',
  component: MonthlyScheduleCreatePage,
});

// Leave routes
const leaveRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/leave',
  component: LeavePage,
});

const leaveShowRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/leave/$id',
  component: LeaveShowPage,
});

const leaveApprovalsRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/leave/approvals',
  component: LeaveApprovalsPage,
});

// Payroll routes
const payrollRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/payroll',
  component: PayrollPage,
});

const payrollShowRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/payroll/$periodId/employee/$employeeId',
  component: PayrollShowPage,
});

const payrollEditRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/payroll/$periodId/employee/$employeeId/edit',
  component: PayrollEditPage,
});

// Reports routes
const reportsRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/reports',
  component: ReportsPage,
});

const reportBuilderRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/reports/builder',
  component: ReportBuilderPage,
});

// Security routes
const securityRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/security',
  component: SecurityPage,
});

const securityEventsRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/security/events',
  component: SecurityEventsPage,
});

const twoFactorRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/security/two-factor',
  component: TwoFactorPage,
});

// Settings route
const settingsRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/settings',
  component: SettingsPage,
});

// Profile routes
const profileRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/profile',
  component: ProfilePage,
});

const profileEditRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/profile/edit',
  component: ProfileEditPage,
});

// Admin - Users routes
const usersRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/users',
  component: UsersPage,
});

const userCreateRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/users/create',
  component: UserCreatePage,
});

const userShowRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/users/$id',
  component: UserShowPage,
});

const userEditRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/users/$id/edit',
  component: UserEditPage,
});

// Admin - Locations routes
const locationsRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/locations',
  component: LocationsPage,
});

const locationCreateRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/locations/create',
  component: LocationCreatePage,
});

const locationShowRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/locations/$id',
  component: LocationShowPage,
});

const locationEditRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/locations/$id/edit',
  component: LocationEditPage,
});

// Admin - Holidays routes
const holidaysRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/holidays',
  component: HolidaysPage,
});

const holidayShowRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/holidays/$id',
  component: HolidayShowPage,
});

const holidayEditRoute = createRoute({
  getParentRoute: () => authenticatedRoute,
  path: '/admin/holidays/$id/edit',
  component: HolidayEditPage,
});

// Auth routes (semi-public)
const verifyEmailRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/verify-email',
  component: VerifyEmailPage,
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
  verifyEmailRoute,
  authenticatedRoute.addChildren([
    dashboardRoute,
    attendanceRoute,
    faceRecognitionRoute,
    faceRecognitionSettingsRoute,
    // Employees
    employeesRoute,
    employeeCreateRoute,
    employeeCredentialsRoute,
    employeeShowRoute,
    employeeEditRoute,
    // Schedules
    schedulesRoute,
    scheduleCreateRoute,
    scheduleBuilderRoute,
    scheduleAssignRoute,
    monthlyScheduleIndexRoute,
    monthlyScheduleCreateRoute,
    scheduleShowRoute,
    scheduleEditRoute,
    // Leave
    leaveRoute,
    leaveApprovalsRoute,
    leaveShowRoute,
    // Payroll
    payrollRoute,
    payrollShowRoute,
    payrollEditRoute,
    // Reports
    reportsRoute,
    reportBuilderRoute,
    // Security
    securityRoute,
    securityEventsRoute,
    twoFactorRoute,
    // Profile
    profileRoute,
    profileEditRoute,
    // Settings
    settingsRoute,
    confirmPasswordRoute,
    // Admin - Users
    usersRoute,
    userCreateRoute,
    userShowRoute,
    userEditRoute,
    // Admin - Locations
    locationsRoute,
    locationCreateRoute,
    locationShowRoute,
    locationEditRoute,
    // Admin - Holidays
    holidaysRoute,
    holidayShowRoute,
    holidayEditRoute,
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
