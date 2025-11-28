/**
 * Setup Flow Tests
 *
 * Tests the initial setup process for the attendance system:
 * 1. Add Location
 * 2. Add Employees
 * 3. Create User Accounts
 * 4. Create Schedule
 * 5. Assign Employees to Schedule
 */

import { describe, it, expect } from 'vitest';
import { http, HttpResponse } from 'msw';
import { server } from '../mocks/server';

const API_URL = 'http://localhost:8000/api';

describe('Setup Flow', () => {
  describe('Step 1: Add Location', () => {
    it('should create a new location with GPS coordinates', async () => {
      const newLocation = {
        name: 'Kantor Baru',
        address: 'Jl. Gatot Subroto No. 5',
        latitude: -6.2350,
        longitude: 106.8200,
        radius: 100,
      };

      server.use(
        http.post(`${API_URL}/locations`, () => {
          return HttpResponse.json({
            id: 3,
            ...newLocation,
            is_active: true,
          }, { status: 201 });
        })
      );

      const response = await fetch(`${API_URL}/locations`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(newLocation),
      });

      const data = await response.json();

      expect(response.status).toBe(201);
      expect(data).toMatchObject({
        id: 3,
        name: 'Kantor Baru',
        latitude: -6.2350,
        longitude: 106.8200,
        radius: 100,
      });
    });

    it('should list all locations', async () => {
      const response = await fetch(`${API_URL}/locations`);
      const data = await response.json();

      expect(response.ok).toBe(true);
      expect(data.data).toBeInstanceOf(Array);
      expect(data.data.length).toBeGreaterThan(0);
      expect(data.data[0]).toHaveProperty('name');
      expect(data.data[0]).toHaveProperty('latitude');
      expect(data.data[0]).toHaveProperty('longitude');
    });
  });

  describe('Step 2: Add Employees', () => {
    it('should create a new employee', async () => {
      const newEmployee = {
        employee_id: 'EMP003',
        name: 'Budi Santoso',
        email: 'budi.santoso@company.com',
        phone: '081234567892',
        position: 'Accountant',
        department: 'Finance',
      };

      const response = await fetch(`${API_URL}/employees`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(newEmployee),
      });

      const data = await response.json();

      expect(response.status).toBe(201);
      expect(data).toMatchObject({
        name: 'Budi Santoso',
        department: 'Finance',
      });
    });

    it('should import multiple employees via CSV', async () => {
      server.use(
        http.post(`${API_URL}/employees/import`, () => {
          return HttpResponse.json({
            message: '10 karyawan berhasil diimport',
            imported: 10,
            failed: 0,
          }, { status: 201 });
        })
      );

      const formData = new FormData();
      formData.append('file', new Blob(['csv data'], { type: 'text/csv' }));

      const response = await fetch(`${API_URL}/employees/import`, {
        method: 'POST',
        body: formData,
      });

      const data = await response.json();

      expect(response.status).toBe(201);
      expect(data.imported).toBe(10);
    });

    it('should list all employees', async () => {
      const response = await fetch(`${API_URL}/employees`);
      const data = await response.json();

      expect(response.ok).toBe(true);
      expect(data.data).toBeInstanceOf(Array);
      expect(data.meta).toHaveProperty('total');
    });
  });

  describe('Step 3: Create User Accounts', () => {
    it('should create user accounts for employees', async () => {
      const response = await fetch(`${API_URL}/employees/create-users`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          employee_ids: [1, 2, 3],
        }),
      });

      const data = await response.json();

      expect(response.ok).toBe(true);
      expect(data.message).toContain('3 user berhasil dibuat');
      expect(data.created).toBe(3);
    });

    it('should reset password for an employee', async () => {
      const response = await fetch(`${API_URL}/employees/1/reset-password`, {
        method: 'POST',
      });

      const data = await response.json();

      expect(response.ok).toBe(true);
      expect(data).toHaveProperty('temporary_password');
      expect(data.message).toContain('Password berhasil direset');
    });
  });

  describe('Step 4: Create Schedule', () => {
    it('should create a new schedule', async () => {
      const newSchedule = {
        name: 'Jadwal Shift Malam',
        type: 'shift',
        shift: 'malam',
        start_time: '21:00',
        end_time: '07:00',
      };

      const response = await fetch(`${API_URL}/schedules`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(newSchedule),
      });

      const data = await response.json();

      expect(response.status).toBe(201);
      expect(data).toMatchObject({
        name: 'Jadwal Shift Malam',
        shift: 'malam',
      });
    });

    it('should list all schedules', async () => {
      const response = await fetch(`${API_URL}/schedules`);
      const data = await response.json();

      expect(response.ok).toBe(true);
      expect(data.data).toBeInstanceOf(Array);
      expect(data.data[0]).toHaveProperty('name');
      expect(data.data[0]).toHaveProperty('start_time');
      expect(data.data[0]).toHaveProperty('end_time');
    });
  });

  describe('Step 5: Assign Employees to Schedule', () => {
    it('should assign employees to a schedule', async () => {
      const response = await fetch(`${API_URL}/schedules/assign`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          schedule_id: 1,
          employee_ids: [1, 2, 3],
        }),
      });

      const data = await response.json();

      expect(response.ok).toBe(true);
      expect(data.message).toContain('3 karyawan berhasil ditugaskan');
      expect(data.schedule_id).toBe(1);
      expect(data.employee_ids).toEqual([1, 2, 3]);
    });
  });

  describe('Complete Setup Flow Integration', () => {
    it('should complete the entire setup flow', async () => {
      // Step 1: Create location
      const locationResponse = await fetch(`${API_URL}/locations`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name: 'Kantor Test',
          address: 'Test Address',
          latitude: -6.2000,
          longitude: 106.8000,
          radius: 100,
        }),
      });
      expect(locationResponse.status).toBe(201);

      // Step 2: Create employee
      const employeeResponse = await fetch(`${API_URL}/employees`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          employee_id: 'EMP_TEST',
          name: 'Test Employee',
          email: 'test@company.com',
          department: 'IT',
          position: 'Tester',
        }),
      });
      expect(employeeResponse.status).toBe(201);
      const employee = await employeeResponse.json();

      // Step 3: Create user account
      const userResponse = await fetch(`${API_URL}/employees/create-users`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          employee_ids: [employee.id],
        }),
      });
      expect(userResponse.ok).toBe(true);

      // Step 4: Create schedule
      const scheduleResponse = await fetch(`${API_URL}/schedules`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name: 'Test Schedule',
          type: 'regular',
          shift: 'pagi',
          start_time: '08:00',
          end_time: '17:00',
        }),
      });
      expect(scheduleResponse.status).toBe(201);
      const schedule = await scheduleResponse.json();

      // Step 5: Assign employee to schedule
      const assignResponse = await fetch(`${API_URL}/schedules/assign`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          schedule_id: schedule.id,
          employee_ids: [employee.id],
        }),
      });
      expect(assignResponse.ok).toBe(true);

      // Verify setup complete
      const employeesResponse = await fetch(`${API_URL}/employees`);
      const employees = await employeesResponse.json();
      expect(employees.data.length).toBeGreaterThan(0);
    });
  });
});
