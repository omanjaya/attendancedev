<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\MonthlySchedule;
use App\Models\TeachingSchedule;
use App\Models\EmployeeMonthlySchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk menangani logika jadwal absensi berdasarkan jenis pegawai
 * 
 * Mode Jadwal:
 * - 'fixed': Jam masuk/pulang sesuai jadwal bulanan (untuk Pegawai Tetap, Guru Tetap)
 * - 'flexible': Jam masuk dari jadwal mengajar pertama, jam pulang dari jadwal mengajar terakhir (untuk Honor)
 */
class AttendanceScheduleService
{
    /**
     * Mendapatkan jam kerja efektif untuk seorang pegawai pada tanggal tertentu
     * 
     * @param Employee $employee
     * @param Carbon|null $date
     * @return array{
     *   schedule_mode: string,
     *   has_schedule: bool,
     *   expected_start_time: string|null,
     *   expected_end_time: string|null,
     *   source: string,
     *   teaching_schedules: array,
     *   monthly_schedule: MonthlySchedule|null
     * }
     */
    public function getEffectiveWorkingHours(Employee $employee, ?Carbon $date = null): array
    {
        $date = $date ?? now('Asia/Makassar');
        $employeeType = $employee->employeeTypeRelation;
        
        // Default schedule mode dari employee type
        $scheduleMode = $employeeType?->schedule_mode ?? 'fixed';
        
        // Get monthly schedule
        $monthlySchedule = $this->getMonthlyScheduleForDate($employee, $date);
        
        // Jika mode flexible, cek jadwal mengajar
        if ($scheduleMode === 'flexible') {
            return $this->getFlexibleWorkingHours($employee, $date, $monthlySchedule);
        }
        
        // Mode fixed - gunakan jadwal bulanan
        return $this->getFixedWorkingHours($employee, $date, $monthlySchedule);
    }
    
    /**
     * Mendapatkan jam kerja untuk mode FIXED (Pegawai Tetap)
     * Jam masuk/pulang sesuai jadwal bulanan yang di-assign
     */
    protected function getFixedWorkingHours(Employee $employee, Carbon $date, ?MonthlySchedule $schedule): array
    {
        if (!$schedule) {
            return [
                'schedule_mode' => 'fixed',
                'has_schedule' => false,
                'expected_start_time' => null,
                'expected_end_time' => null,
                'source' => 'no_schedule',
                'teaching_schedules' => [],
                'monthly_schedule' => null,
                'message' => 'Tidak ada jadwal bulanan yang di-assign'
            ];
        }
        
        // Cek apakah hari ini adalah hari kerja
        $workingDays = $schedule->working_days ?? [];
        $dateStr = $date->toDateString();
        
        if (!in_array($dateStr, $workingDays)) {
            return [
                'schedule_mode' => 'fixed',
                'has_schedule' => false,
                'expected_start_time' => null,
                'expected_end_time' => null,
                'source' => 'not_working_day',
                'teaching_schedules' => [],
                'monthly_schedule' => $schedule,
                'message' => 'Hari ini bukan hari kerja'
            ];
        }
        
        return [
            'schedule_mode' => 'fixed',
            'has_schedule' => true,
            'expected_start_time' => $schedule->default_start_time,
            'expected_end_time' => $schedule->default_end_time,
            'source' => 'monthly_schedule',
            'teaching_schedules' => [],
            'monthly_schedule' => $schedule,
            'message' => null
        ];
    }
    
    /**
     * Mendapatkan jam kerja untuk mode FLEXIBLE (Pegawai Honor)
     * Jam masuk dari jadwal mengajar pertama, jam pulang dari jadwal mengajar terakhir
     * Jika tidak ada jadwal mengajar = tidak perlu absen
     */
    protected function getFlexibleWorkingHours(Employee $employee, Carbon $date, ?MonthlySchedule $monthlySchedule): array
    {
        // Ambil semua jadwal mengajar untuk hari ini
        $teachingSchedules = $this->getTeachingSchedulesForDate($employee, $date);
        
        // Jika tidak ada jadwal mengajar, tidak perlu absen
        if ($teachingSchedules->isEmpty()) {
            return [
                'schedule_mode' => 'flexible',
                'has_schedule' => false,
                'expected_start_time' => null,
                'expected_end_time' => null,
                'source' => 'no_teaching_schedule',
                'teaching_schedules' => [],
                'monthly_schedule' => $monthlySchedule,
                'message' => 'Tidak ada jadwal mengajar hari ini'
            ];
        }
        
        // Sort by teaching_start_time
        $sortedSchedules = $teachingSchedules->sortBy(function($schedule) {
            return $schedule->teaching_start_time->format('H:i');
        });
        
        // Jam masuk = jadwal mengajar PERTAMA
        $firstSchedule = $sortedSchedules->first();
        $expectedStartTime = $firstSchedule->teaching_start_time->format('H:i');
        
        // Jam pulang = jadwal mengajar TERAKHIR
        $lastSchedule = $sortedSchedules->last();
        $expectedEndTime = $lastSchedule->teaching_end_time->format('H:i');
        
        // Siapkan data jadwal untuk response
        $scheduleData = $teachingSchedules->map(function($schedule) {
            return [
                'id' => $schedule->id,
                'subject' => $schedule->subject?->name ?? 'Unknown',
                'class_name' => $schedule->class_name,
                'start_time' => $schedule->teaching_start_time->format('H:i'),
                'end_time' => $schedule->teaching_end_time->format('H:i'),
                'room' => $schedule->room,
            ];
        })->values()->toArray();
        
        return [
            'schedule_mode' => 'flexible',
            'has_schedule' => true,
            'expected_start_time' => $expectedStartTime,
            'expected_end_time' => $expectedEndTime,
            'source' => 'teaching_schedule',
            'teaching_schedules' => $scheduleData,
            'monthly_schedule' => $monthlySchedule,
            'teaching_count' => $teachingSchedules->count(),
            'message' => null
        ];
    }
    
    /**
     * Mendapatkan jadwal bulanan untuk tanggal tertentu
     */
    public function getMonthlyScheduleForDate(Employee $employee, Carbon $date): ?MonthlySchedule
    {
        $month = $date->month;
        $year = $date->year;
        
        $employeeSchedule = EmployeeMonthlySchedule::where('employee_id', $employee->id)
            ->whereHas('monthlySchedule', function ($query) use ($month, $year) {
                $query->where('month', $month)
                      ->where('year', $year)
                      ->where('is_active', true);
            })
            ->with('monthlySchedule')
            ->first();
        
        return $employeeSchedule?->monthlySchedule;
    }
    
    /**
     * Mendapatkan jadwal mengajar untuk tanggal tertentu
     */
    public function getTeachingSchedulesForDate(Employee $employee, Carbon $date)
    {
        $dayOfWeek = strtolower($date->format('l')); // monday, tuesday, etc.
        
        return TeachingSchedule::where('teacher_id', $employee->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where('status', 'scheduled')
            ->where('effective_from', '<=', $date)
            ->where(function($query) use ($date) {
                $query->whereNull('effective_until')
                      ->orWhere('effective_until', '>=', $date);
            })
            ->with('subject')
            ->orderBy('teaching_start_time')
            ->get();
    }
    
    /**
     * Menghitung keterlambatan check-in
     * 
     * @return array{
     *   is_late: bool,
     *   late_minutes: int,
     *   expected_time: string|null,
     *   actual_time: string,
     *   message: string|null
     * }
     */
    public function calculateCheckInLateness(Employee $employee, Carbon $checkInTime): array
    {
        $workingHours = $this->getEffectiveWorkingHours($employee, $checkInTime);
        
        // Jika tidak ada jadwal, tidak bisa dihitung keterlambatan
        if (!$workingHours['has_schedule'] || !$workingHours['expected_start_time']) {
            return [
                'is_late' => false,
                'late_minutes' => 0,
                'expected_time' => null,
                'actual_time' => $checkInTime->format('H:i'),
                'message' => $workingHours['message'],
                'schedule_mode' => $workingHours['schedule_mode']
            ];
        }
        
        $expectedStart = Carbon::createFromFormat('H:i', $workingHours['expected_start_time']);
        $actualStart = Carbon::createFromFormat('H:i:s', $checkInTime->format('H:i:s'));
        
        // Hitung selisih dalam menit
        $diffMinutes = $actualStart->diffInMinutes($expectedStart, false);
        
        // Positif = terlambat, Negatif = datang lebih awal
        $isLate = $diffMinutes > 0;
        $lateMinutes = max(0, $diffMinutes);
        
        $message = null;
        if ($isLate) {
            $message = "Terlambat {$lateMinutes} menit dari jadwal {$workingHours['expected_start_time']}";
        }
        
        return [
            'is_late' => $isLate,
            'late_minutes' => $lateMinutes,
            'expected_time' => $workingHours['expected_start_time'],
            'actual_time' => $checkInTime->format('H:i'),
            'message' => $message,
            'schedule_mode' => $workingHours['schedule_mode'],
            'source' => $workingHours['source']
        ];
    }
    
    /**
     * Menghitung pulang cepat pada check-out
     * 
     * @return array{
     *   is_early: bool,
     *   early_minutes: int,
     *   expected_time: string|null,
     *   actual_time: string,
     *   message: string|null
     * }
     */
    public function calculateCheckOutEarliness(Employee $employee, Carbon $checkOutTime): array
    {
        $workingHours = $this->getEffectiveWorkingHours($employee, $checkOutTime);
        
        // Jika tidak ada jadwal, tidak bisa dihitung
        if (!$workingHours['has_schedule'] || !$workingHours['expected_end_time']) {
            return [
                'is_early' => false,
                'early_minutes' => 0,
                'expected_time' => null,
                'actual_time' => $checkOutTime->format('H:i'),
                'message' => $workingHours['message'],
                'schedule_mode' => $workingHours['schedule_mode']
            ];
        }
        
        $expectedEnd = Carbon::createFromFormat('H:i', $workingHours['expected_end_time']);
        $actualEnd = Carbon::createFromFormat('H:i:s', $checkOutTime->format('H:i:s'));
        
        // Hitung selisih dalam menit
        $diffMinutes = $expectedEnd->diffInMinutes($actualEnd, false);
        
        // Positif = pulang lebih awal, Negatif = pulang terlambat/overtime
        $isEarly = $diffMinutes > 0;
        $earlyMinutes = max(0, $diffMinutes);
        
        $message = null;
        if ($isEarly) {
            $message = "Pulang {$earlyMinutes} menit lebih awal dari jadwal {$workingHours['expected_end_time']}";
        }
        
        return [
            'is_early' => $isEarly,
            'early_minutes' => $earlyMinutes,
            'expected_time' => $workingHours['expected_end_time'],
            'actual_time' => $checkOutTime->format('H:i'),
            'message' => $message,
            'schedule_mode' => $workingHours['schedule_mode'],
            'source' => $workingHours['source']
        ];
    }
    
    /**
     * Validasi apakah pegawai bisa absen hari ini
     * Untuk mode flexible tanpa jadwal mengajar = tidak boleh absen
     */
    public function canAttendToday(Employee $employee, ?Carbon $date = null): array
    {
        $workingHours = $this->getEffectiveWorkingHours($employee, $date ?? now('Asia/Makassar'));
        
        // Mode fixed - bisa absen jika ada jadwal bulanan dan hari kerja
        if ($workingHours['schedule_mode'] === 'fixed') {
            if (!$workingHours['has_schedule']) {
                return [
                    'can_attend' => false,
                    'reason' => $workingHours['message'] ?? 'Tidak ada jadwal kerja',
                    'schedule_mode' => 'fixed'
                ];
            }
            return [
                'can_attend' => true,
                'reason' => null,
                'schedule_mode' => 'fixed',
                'working_hours' => $workingHours
            ];
        }
        
        // Mode flexible - bisa absen hanya jika ada jadwal mengajar
        if (!$workingHours['has_schedule']) {
            return [
                'can_attend' => false,
                'reason' => 'Tidak ada jadwal mengajar hari ini',
                'schedule_mode' => 'flexible'
            ];
        }
        
        return [
            'can_attend' => true,
            'reason' => null,
            'schedule_mode' => 'flexible',
            'working_hours' => $workingHours
        ];
    }
}
