import { http, HttpResponse } from 'msw';

const API_URL = 'http://localhost:8000/api';

// Mock user data
const mockUser = {
  id: 1,
  name: 'Test User',
  email: 'test@example.com',
  role: 'admin',
  employee_id: 'EMP001',
  avatar: null,
};

// Mock employees data
const mockEmployees = [
  {
    id: 1,
    employee_id: 'EMP001',
    name: 'Ahmad Rizki',
    email: 'ahmad.rizki@company.com',
    phone: '081234567890',
    position: 'Software Engineer',
    department: 'IT',
    status: 'active',
    join_date: '2023-01-15',
    face_registered: true,
    created_at: '2023-01-15T00:00:00',
    updated_at: '2023-01-15T00:00:00',
  },
  {
    id: 2,
    employee_id: 'EMP002',
    name: 'Siti Nurhaliza',
    email: 'siti.nurhaliza@company.com',
    phone: '081234567891',
    position: 'HR Manager',
    department: 'HR',
    status: 'active',
    join_date: '2022-06-01',
    face_registered: true,
    created_at: '2022-06-01T00:00:00',
    updated_at: '2022-06-01T00:00:00',
  },
];

// Mock attendance data
const mockAttendance = [
  {
    id: 1,
    employee_id: 1,
    employee_name: 'Ahmad Rizki',
    date: '2024-01-15',
    check_in: '08:00:00',
    check_out: '17:00:00',
    status: 'present',
    work_hours: 9,
  },
];

// Mock locations data
const mockLocations = [
  {
    id: 1,
    name: 'Kantor Pusat',
    address: 'Jl. Sudirman No. 1',
    latitude: -6.2088,
    longitude: 106.8456,
    radius: 100,
    is_active: true,
  },
  {
    id: 2,
    name: 'Kantor Cabang',
    address: 'Jl. Thamrin No. 10',
    latitude: -6.1944,
    longitude: 106.8229,
    radius: 150,
    is_active: true,
  },
];

// Mock schedules data
const mockSchedules = [
  {
    id: 1,
    name: 'Jadwal Reguler Pagi',
    type: 'regular',
    shift: 'pagi',
    start_time: '07:00',
    end_time: '15:00',
    employee_count: 25,
    is_active: true,
  },
  {
    id: 2,
    name: 'Jadwal Reguler Siang',
    type: 'regular',
    shift: 'siang',
    start_time: '13:00',
    end_time: '21:00',
    employee_count: 18,
    is_active: true,
  },
];

// Mock monthly schedules
const mockMonthlySchedules = [
  {
    id: 1,
    name: 'Jadwal Januari 2025',
    month: 1,
    year: 2025,
    status: 'active',
    employee_count: 45,
    created_at: '2024-12-20',
    published_at: '2024-12-25',
  },
];

// Mock leave requests
const mockLeaveRequests = [
  {
    id: 1,
    employee_id: 1,
    employee_name: 'Ahmad Rizki',
    type: 'annual',
    start_date: '2025-01-20',
    end_date: '2025-01-22',
    reason: 'Liburan keluarga',
    status: 'pending',
    created_at: '2025-01-15',
  },
];

// Mock dashboard data
const mockDashboard = {
  total_employees: 125,
  present_today: 118,
  on_leave: 5,
  late_today: 3,
  attendance_rate: 94.5,
  weekly_data: [
    { day: 'Sen', present: 118, absent: 7 },
    { day: 'Sel', present: 120, absent: 5 },
    { day: 'Rab', present: 115, absent: 10 },
    { day: 'Kam', present: 122, absent: 3 },
    { day: 'Jum', present: 110, absent: 15 },
  ],
  recent_activities: [
    {
      id: 1,
      type: 'check_in',
      message: 'Ahmad Rizki melakukan check-in',
      time: '08:00',
      employee: 'Ahmad Rizki',
    },
  ],
};

export const handlers = [
  // Auth handlers
  http.post(`${API_URL}/login`, async ({ request }) => {
    const body = await request.json() as { email: string; password: string };

    if (body.email === 'test@example.com' && body.password === 'password') {
      return HttpResponse.json({
        user: mockUser,
        token: 'mock-token-123',
      });
    }

    return HttpResponse.json(
      { message: 'Invalid credentials' },
      { status: 401 }
    );
  }),

  http.post(`${API_URL}/logout`, () => {
    return HttpResponse.json({ message: 'Logged out successfully' });
  }),

  http.get(`${API_URL}/user`, () => {
    return HttpResponse.json(mockUser);
  }),

  // Employees handlers
  http.get(`${API_URL}/employees`, () => {
    return HttpResponse.json({
      data: mockEmployees,
      meta: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: mockEmployees.length,
      },
    });
  }),

  http.get(`${API_URL}/employees/:id`, ({ params }) => {
    const employee = mockEmployees.find((e) => e.id === Number(params.id));
    if (employee) {
      return HttpResponse.json(employee);
    }
    return HttpResponse.json({ message: 'Not found' }, { status: 404 });
  }),

  http.post(`${API_URL}/employees`, async ({ request }) => {
    const body = await request.json() as Record<string, unknown>;
    const newEmployee = {
      id: mockEmployees.length + 1,
      ...body,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
    };
    return HttpResponse.json(newEmployee, { status: 201 });
  }),

  http.put(`${API_URL}/employees/:id`, async ({ params, request }) => {
    const body = await request.json() as Record<string, unknown>;
    const employee = mockEmployees.find((e) => e.id === Number(params.id));
    if (employee) {
      return HttpResponse.json({ ...employee, ...body });
    }
    return HttpResponse.json({ message: 'Not found' }, { status: 404 });
  }),

  http.delete(`${API_URL}/employees/:id`, ({ params }) => {
    const index = mockEmployees.findIndex((e) => e.id === Number(params.id));
    if (index !== -1) {
      return HttpResponse.json({ message: 'Deleted successfully' });
    }
    return HttpResponse.json({ message: 'Not found' }, { status: 404 });
  }),

  // Attendance handlers
  http.get(`${API_URL}/attendance`, () => {
    return HttpResponse.json({
      data: mockAttendance,
      meta: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: mockAttendance.length,
      },
    });
  }),

  http.post(`${API_URL}/attendance/check-in`, () => {
    return HttpResponse.json({
      id: 1,
      check_in: new Date().toISOString(),
      status: 'checked_in',
    });
  }),

  http.post(`${API_URL}/attendance/check-out`, () => {
    return HttpResponse.json({
      id: 1,
      check_out: new Date().toISOString(),
      status: 'checked_out',
    });
  }),

  // Dashboard handler
  http.get(`${API_URL}/dashboard`, () => {
    return HttpResponse.json(mockDashboard);
  }),

  // Locations handlers
  http.get(`${API_URL}/locations`, () => {
    return HttpResponse.json({
      data: mockLocations,
      meta: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: mockLocations.length,
      },
    });
  }),

  http.post(`${API_URL}/locations`, async ({ request }) => {
    const body = await request.json() as Record<string, unknown>;
    const newLocation = {
      id: mockLocations.length + 1,
      ...body,
    };
    return HttpResponse.json(newLocation, { status: 201 });
  }),

  // Schedules handlers
  http.get(`${API_URL}/schedules`, () => {
    return HttpResponse.json({
      data: mockSchedules,
      meta: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: mockSchedules.length,
      },
    });
  }),

  http.post(`${API_URL}/schedules`, async ({ request }) => {
    const body = await request.json() as Record<string, unknown>;
    const newSchedule = {
      id: mockSchedules.length + 1,
      ...body,
    };
    return HttpResponse.json(newSchedule, { status: 201 });
  }),

  http.post(`${API_URL}/schedules/assign`, async ({ request }) => {
    const body = await request.json() as { schedule_id: number; employee_ids: number[] };
    return HttpResponse.json({
      message: `${body.employee_ids.length} karyawan berhasil ditugaskan`,
      schedule_id: body.schedule_id,
      employee_ids: body.employee_ids,
    });
  }),

  // Monthly schedules handlers
  http.get(`${API_URL}/schedules/monthly`, () => {
    return HttpResponse.json({
      data: mockMonthlySchedules,
      meta: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: mockMonthlySchedules.length,
      },
    });
  }),

  http.post(`${API_URL}/schedules/monthly`, async ({ request }) => {
    const body = await request.json() as Record<string, unknown>;
    const newMonthlySchedule = {
      id: mockMonthlySchedules.length + 1,
      ...body,
      status: 'draft',
      created_at: new Date().toISOString(),
    };
    return HttpResponse.json(newMonthlySchedule, { status: 201 });
  }),

  http.post(`${API_URL}/schedules/monthly/:id/publish`, ({ params }) => {
    return HttpResponse.json({
      id: Number(params.id),
      status: 'active',
      published_at: new Date().toISOString(),
    });
  }),

  // Leave handlers
  http.get(`${API_URL}/leave`, () => {
    return HttpResponse.json({
      data: mockLeaveRequests,
      meta: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: mockLeaveRequests.length,
      },
    });
  }),

  http.get(`${API_URL}/leave/approvals`, () => {
    return HttpResponse.json({
      data: mockLeaveRequests.filter((l) => l.status === 'pending'),
      meta: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: mockLeaveRequests.filter((l) => l.status === 'pending').length,
      },
    });
  }),

  http.post(`${API_URL}/leave/:id/approve`, ({ params }) => {
    return HttpResponse.json({
      id: Number(params.id),
      status: 'approved',
      approved_at: new Date().toISOString(),
    });
  }),

  http.post(`${API_URL}/leave/:id/reject`, ({ params }) => {
    return HttpResponse.json({
      id: Number(params.id),
      status: 'rejected',
      rejected_at: new Date().toISOString(),
    });
  }),

  // User credentials handlers
  http.post(`${API_URL}/employees/create-users`, async ({ request }) => {
    const body = await request.json() as { employee_ids: number[] };
    return HttpResponse.json({
      message: `${body.employee_ids.length} user berhasil dibuat`,
      created: body.employee_ids.length,
    });
  }),

  http.post(`${API_URL}/employees/:id/reset-password`, ({ params }) => {
    return HttpResponse.json({
      employee_id: Number(params.id),
      message: 'Password berhasil direset',
      temporary_password: 'temp123456',
    });
  }),

  // Face recognition handlers
  http.post(`${API_URL}/face-recognition/enroll`, async ({ request }) => {
    const body = await request.json() as { employee_id: number; face_data: string };
    return HttpResponse.json({
      employee_id: body.employee_id,
      message: 'Wajah berhasil didaftarkan',
      face_registered: true,
    });
  }),

  http.post(`${API_URL}/face-recognition/verify`, async ({ request }) => {
    await request.json(); // consume the body
    return HttpResponse.json({
      verified: true,
      employee_id: 1,
      employee_name: 'Ahmad Rizki',
      confidence: 0.95,
    });
  }),
];
