# User Accounts for Attendance System

## Admin Account

- **Email:** admin@attendance.com
- **Password:** password123
- **Role:** Super Admin
- **Name:** System Administrator
- **Employee ID:** ADM001

## Management Users

### Kepala Sekolah (Principal)

- **Email:** budi.santoso@attendance.com
- **Password:** password123
- **Role:** Admin
- **Name:** Budi Santoso
- **Employee ID:** MGR001
- **Position:** Kepala Sekolah

### Wakil Kepala Sekolah (Vice Principal)

- **Email:** siti.nurhaliza@attendance.com
- **Password:** password123
- **Role:** Manager
- **Name:** Siti Nurhaliza
- **Employee ID:** MGR002
- **Position:** Wakil Kepala Sekolah

## Teacher Accounts (Permanent Staff)

### Exact Sciences Teachers

- **Email:** ahmad.wijaya@attendance.com
  - **Password:** password123
  - **Name:** Ahmad Wijaya
  - **Employee ID:** TCH001
  - **Subject:** Guru Matematika

- **Email:** sri.mulyani@attendance.com
  - **Password:** password123
  - **Name:** Sri Mulyani
  - **Employee ID:** TCH004
  - **Subject:** Guru Fisika

- **Email:** bambang.setiawan@attendance.com
  - **Password:** password123
  - **Name:** Bambang Setiawan
  - **Employee ID:** TCH005
  - **Subject:** Guru Kimia

- **Email:** maya.sari@attendance.com
  - **Password:** password123
  - **Name:** Maya Sari
  - **Employee ID:** TCH006
  - **Subject:** Guru Biologi

### Language Teachers

- **Email:** dewi.lestari@attendance.com
  - **Password:** password123
  - **Name:** Dewi Lestari
  - **Employee ID:** TCH002
  - **Subject:** Guru Bahasa Indonesia

- **Email:** andi.pratama@attendance.com
  - **Password:** password123
  - **Name:** Andi Pratama
  - **Employee ID:** TCH003
  - **Subject:** Guru Bahasa Inggris

### Social Sciences Teachers

- **Email:** dedi.kurniawan@attendance.com
  - **Password:** password123
  - **Name:** Dedi Kurniawan
  - **Employee ID:** TCH007
  - **Subject:** Guru Sejarah

- **Email:** ratna.dewi@attendance.com
  - **Password:** password123
  - **Name:** Ratna Dewi
  - **Employee ID:** TCH008
  - **Subject:** Guru Geografi

## Honorary Teachers

- **Email:** joko.widodo@attendance.com
  - **Password:** password123
  - **Name:** Joko Widodo
  - **Employee ID:** HON001
  - **Subject:** Guru Ekonomi
  - **Type:** Honorary (Hourly Rate: Rp 50,000/hour)

- **Email:** indira.kenzo@attendance.com
  - **Password:** password123
  - **Name:** Indira Kenzo
  - **Employee ID:** HON002
  - **Subject:** Guru Seni Budaya
  - **Type:** Honorary (Hourly Rate: Rp 50,000/hour)

## Administrative Staff

- **Email:** eko.purnomo@attendance.com
  - **Password:** password123
  - **Name:** Eko Purnomo
  - **Employee ID:** STF001
  - **Position:** Staff Tata Usaha

- **Email:** lina.marlina@attendance.com
  - **Password:** password123
  - **Name:** Lina Marlina
  - **Employee ID:** STF002
  - **Position:** Staff Perpustakaan

- **Email:** hendra.gunawan@attendance.com
  - **Password:** password123
  - **Name:** Hendra Gunawan
  - **Employee ID:** STF003
  - **Position:** Staff IT

## Permission Levels

### Super Admin (admin@attendance.com)

- Full system access
- Can manage users, employees, schedules, attendance, payroll
- System administration capabilities

### Admin (budi.santoso@attendance.com)

- Employee management
- Attendance management and reporting
- Schedule management
- Payroll access
- Leave approval

### Manager (siti.nurhaliza@attendance.com)

- View employees and attendance
- Attendance reporting
- View schedules
- Leave approval
- Basic reporting

### Employee (All Teachers & Staff)

- View own attendance
- Check-in/Check-out
- View schedules
- Submit leave requests

## Sample Data Created

### Academic Classes

- 12 classes created (X-IPA-1, X-IPA-2, X-IPS-1, X-IPS-2, XI-IPA-1, XI-IPA-2, XI-IPS-1, XI-IPS-2,
  XII-IPA-1, XII-IPA-2, XII-IPS-1, XII-IPS-2)

### Subjects

- 15 subjects created including Matematika, Bahasa Indonesia, Bahasa Inggris, Fisika, Kimia,
  Biologi, Sejarah, Geografi, Ekonomi, etc.

### Time Slots

- 10 time slots created (8 lesson periods + 2 break times)
- Schedule: 07:00 - 13:45

### Teacher-Subject Assignments

- 32 assignments created linking teachers to their subjects
- Teachers can teach multiple subjects
- Primary teacher designation for each subject

## How to Login

1. Go to `/login` in your application
2. Use any of the email/password combinations above
3. All passwords are: **password123**

## Next Steps

1. Teachers can be assigned to specific classes using the schedule management system
2. Attendance tracking can begin once face recognition is set up
3. Leave management system is ready for use
4. Payroll can be calculated based on attendance data

## Schedule Management

- Access schedule management at `/academic/schedules`
- Drag and drop functionality for easy schedule creation
- Conflict detection for teacher and class overlaps
- Export/import capabilities for schedule backup
- Comprehensive audit trail for all changes
