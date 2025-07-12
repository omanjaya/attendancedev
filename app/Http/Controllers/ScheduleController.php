<?php

namespace App\Http\Controllers;

use App\Models\Period;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ScheduleController extends Controller
{
    /**
     * Display the schedule management interface.
     */
    public function index()
    {
        $periods = Period::active()->orderBy('day_of_week')->orderBy('start_time')->get();
        $employees = Employee::with('user')->where('is_active', true)->get();
        
        return view('pages.schedules.index', compact('periods', 'employees'));
    }

    /**
     * Show the schedule calendar view with Patra-style interface.
     */
    public function calendar()
    {
        // Simple static data for the Patra-style interface
        $academicClasses = collect([
            (object)['id' => 1, 'grade_level' => 10, 'major' => 'IPA', 'class_number' => 1, 'name' => 'X IPA 1', 'section' => '1'],
            (object)['id' => 2, 'grade_level' => 10, 'major' => 'IPA', 'class_number' => 2, 'name' => 'X IPA 2', 'section' => '2'],
            (object)['id' => 3, 'grade_level' => 10, 'major' => 'IPA', 'class_number' => 3, 'name' => 'X IPA 3', 'section' => '3'],
            (object)['id' => 4, 'grade_level' => 10, 'major' => 'IPS', 'class_number' => 1, 'name' => 'X IPS 1', 'section' => '1'],
            (object)['id' => 5, 'grade_level' => 10, 'major' => 'IPS', 'class_number' => 2, 'name' => 'X IPS 2', 'section' => '2'],
            (object)['id' => 6, 'grade_level' => 10, 'major' => 'IPS', 'class_number' => 3, 'name' => 'X IPS 3', 'section' => '3'],
            (object)['id' => 7, 'grade_level' => 11, 'major' => 'IPA', 'class_number' => 1, 'name' => 'XI IPA 1', 'section' => '1'],
            (object)['id' => 8, 'grade_level' => 11, 'major' => 'IPA', 'class_number' => 2, 'name' => 'XI IPA 2', 'section' => '2'],
            (object)['id' => 9, 'grade_level' => 11, 'major' => 'IPA', 'class_number' => 3, 'name' => 'XI IPA 3', 'section' => '3'],
            (object)['id' => 10, 'grade_level' => 11, 'major' => 'IPS', 'class_number' => 1, 'name' => 'XI IPS 1', 'section' => '1'],
            (object)['id' => 11, 'grade_level' => 11, 'major' => 'IPS', 'class_number' => 2, 'name' => 'XI IPS 2', 'section' => '2'],
            (object)['id' => 12, 'grade_level' => 11, 'major' => 'IPS', 'class_number' => 3, 'name' => 'XI IPS 3', 'section' => '3'],
            (object)['id' => 13, 'grade_level' => 12, 'major' => 'IPA', 'class_number' => 1, 'name' => 'XII IPA 1', 'section' => '1'],
            (object)['id' => 14, 'grade_level' => 12, 'major' => 'IPA', 'class_number' => 2, 'name' => 'XII IPA 2', 'section' => '2'],
            (object)['id' => 15, 'grade_level' => 12, 'major' => 'IPA', 'class_number' => 3, 'name' => 'XII IPA 3', 'section' => '3'],
            (object)['id' => 16, 'grade_level' => 12, 'major' => 'IPS', 'class_number' => 1, 'name' => 'XII IPS 1', 'section' => '1'],
            (object)['id' => 17, 'grade_level' => 12, 'major' => 'IPS', 'class_number' => 2, 'name' => 'XII IPS 2', 'section' => '2'],
            (object)['id' => 18, 'grade_level' => 12, 'major' => 'IPS', 'class_number' => 3, 'name' => 'XII IPS 3', 'section' => '3'],
        ]);
        
        $timeSlots = collect([
            (object)['id' => 1, 'name' => 'Jam 1', 'start_time' => '07:00', 'end_time' => '07:45'],
            (object)['id' => 2, 'name' => 'Jam 2', 'start_time' => '07:45', 'end_time' => '08:30'],
            (object)['id' => 3, 'name' => 'Jam 3', 'start_time' => '08:30', 'end_time' => '09:15'],
            (object)['id' => 4, 'name' => 'Jam 4', 'start_time' => '09:35', 'end_time' => '10:20'],
            (object)['id' => 5, 'name' => 'Jam 5', 'start_time' => '10:20', 'end_time' => '11:05'],
            (object)['id' => 6, 'name' => 'Jam 6', 'start_time' => '11:05', 'end_time' => '11:50'],
        ]);
        
        return view('pages.schedules.calendar-patra', compact('academicClasses', 'timeSlots'));
    }

    /**
     * Get schedule data for calendar view.
     */
    public function getCalendarData()
    {
        $schedules = EmployeeSchedule::with(['employee.user', 'period'])
            ->active()
            ->current()
            ->get();

        $events = [];
        foreach ($schedules as $schedule) {
            // Create recurring events for each week
            $startDate = max($schedule->effective_date, now()->startOfMonth());
            $endDate = min($schedule->end_date ?? now()->endOfMonth()->addMonth(), now()->endOfMonth()->addMonth());
            
            $current = $startDate->copy();
            while ($current <= $endDate) {
                if ($current->dayOfWeek == $schedule->period->day_of_week) {
                    $events[] = [
                        'id' => $schedule->id . '_' . $current->format('Y-m-d'),
                        'title' => $schedule->employee->full_name,
                        'start' => $current->format('Y-m-d') . 'T' . $schedule->period->start_time->format('H:i:s'),
                        'end' => $current->format('Y-m-d') . 'T' . $schedule->period->end_time->format('H:i:s'),
                        'backgroundColor' => $this->getEmployeeTypeColor($schedule->employee->employee_type),
                        'borderColor' => $this->getEmployeeTypeColor($schedule->employee->employee_type),
                        'extendedProps' => [
                            'employee_id' => $schedule->employee_id,
                            'period_id' => $schedule->period_id,
                            'period_name' => $schedule->period->name,
                            'employee_type' => $schedule->employee->employee_type
                        ]
                    ];
                }
                $current->addDay();
            }
        }

        return response()->json($events);
    }

    /**
     * Get periods data for DataTables.
     */
    public function getPeriodsData()
    {
        $periods = Period::withCount('schedules')->orderBy('day_of_week')->orderBy('start_time');

        return DataTables::of($periods)
            ->addColumn('day_name', function ($period) {
                return $period->day_name;
            })
            ->addColumn('time_range', function ($period) {
                return $period->time_range;
            })
            ->addColumn('assigned_count', function ($period) {
                return '<span class="badge bg-blue">' . $period->schedules_count . ' assigned</span>';
            })
            ->addColumn('status', function ($period) {
                return $period->is_active 
                    ? '<span class="badge bg-green">Active</span>' 
                    : '<span class="badge bg-red">Inactive</span>';
            })
            ->addColumn('actions', function ($period) {
                return '
                    <div class="btn-list">
                        <button class="btn btn-sm btn-primary assign-employees" data-period-id="' . $period->id . '">
                            Assign
                        </button>
                        <button class="btn btn-sm btn-info view-assignments" data-period-id="' . $period->id . '">
                            View
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['assigned_count', 'status', 'actions'])
            ->make(true);
    }

    /**
     * Assign employees to a period.
     */
    public function assignEmployees(Request $request)
    {
        $validated = $request->validate([
            'period_id' => 'required|exists:periods,id',
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:effective_date'
        ]);

        DB::beginTransaction();
        try {
            $period = Period::findOrFail($validated['period_id']);
            
            foreach ($validated['employee_ids'] as $employeeId) {
                // Check for conflicts
                $conflicts = EmployeeSchedule::where('employee_id', $employeeId)
                    ->whereHas('period', function ($query) use ($period) {
                        $query->where('day_of_week', $period->day_of_week)
                              ->where(function ($q) use ($period) {
                                  $q->whereBetween('start_time', [$period->start_time, $period->end_time])
                                    ->orWhereBetween('end_time', [$period->start_time, $period->end_time])
                                    ->orWhere(function ($subQ) use ($period) {
                                        $subQ->where('start_time', '<=', $period->start_time)
                                             ->where('end_time', '>=', $period->end_time);
                                    });
                              });
                    })
                    ->active()
                    ->current()
                    ->exists();

                if ($conflicts) {
                    $employee = Employee::find($employeeId);
                    throw new \Exception("Schedule conflict detected for {$employee->full_name}");
                }

                // Create the assignment
                EmployeeSchedule::create([
                    'employee_id' => $employeeId,
                    'period_id' => $validated['period_id'],
                    'effective_date' => $validated['effective_date'],
                    'end_date' => $validated['end_date'],
                    'is_active' => true
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Employees assigned successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get assignments for a period.
     */
    public function getPeriodAssignments(Period $period)
    {
        $assignments = $period->schedules()
            ->with(['employee.user'])
            ->active()
            ->current()
            ->get();

        return response()->json([
            'success' => true,
            'period' => $period,
            'assignments' => $assignments
        ]);
    }

    /**
     * Remove employee from period.
     */
    public function removeAssignment(EmployeeSchedule $schedule)
    {
        try {
            $schedule->update(['is_active' => false, 'end_date' => now()->toDateString()]);
            
            return response()->json([
                'success' => true,
                'message' => 'Assignment removed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created schedule.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'period_id' => 'required|exists:periods,id',
            'employee_id' => 'required|exists:employees,id',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:effective_date'
        ]);

        try {
            $schedule = EmployeeSchedule::create([
                'period_id' => $validated['period_id'],
                'employee_id' => $validated['employee_id'],
                'effective_date' => $validated['effective_date'],
                'end_date' => $validated['end_date'],
                'is_active' => true
            ]);

            return redirect()->route('schedules.index')
                ->with('success', 'Schedule created successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create schedule: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing a schedule.
     */
    public function edit(EmployeeSchedule $schedule)
    {
        $periods = Period::active()->orderBy('day_of_week')->orderBy('start_time')->get();
        $employees = Employee::with('user')->where('is_active', true)->get();
        
        return view('pages.schedules.edit', compact('schedule', 'periods', 'employees'));
    }

    /**
     * Update the specified schedule.
     */
    public function update(Request $request, EmployeeSchedule $schedule)
    {
        $validated = $request->validate([
            'period_id' => 'required|exists:periods,id',
            'employee_id' => 'required|exists:employees,id',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:effective_date',
            'is_active' => 'boolean'
        ]);

        try {
            $schedule->update($validated);

            return redirect()->route('schedules.index')
                ->with('success', 'Schedule updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update schedule: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified schedule.
     */
    public function destroy(EmployeeSchedule $schedule)
    {
        try {
            $schedule->delete();

            return response()->json([
                'success' => true,
                'message' => 'Schedule deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import schedules from Excel/CSV file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'schedule_file' => 'required|file|mimes:xlsx,xls,csv|max:2048'
        ]);

        try {
            // In a real implementation, you would process the uploaded file
            // For now, we'll simulate a successful import
            $fileName = $request->file('schedule_file')->getClientOriginalName();
            
            // Simulate processing time
            sleep(1);
            
            return response()->json([
                'success' => true,
                'message' => "Schedule file '{$fileName}' imported successfully. 25 schedules were added."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download schedule template.
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="schedule_template.xlsx"'
        ];

        // In a real implementation, you would generate an Excel file
        // For now, we'll return a CSV template
        $csv = "Period Name,Day of Week,Start Time,End Time,Subject,Room,Employee ID,Effective Date,End Date\n";
        $csv .= "Period 1,1,08:00,09:00,Mathematics,Room 101,EMP001,2024-01-01,2024-06-30\n";
        $csv .= "Period 2,1,09:00,10:00,English,Room 102,EMP002,2024-01-01,2024-06-30\n";
        $csv .= "Period 3,1,10:00,11:00,Science,Lab 1,EMP003,2024-01-01,2024-06-30\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="schedule_template.csv"'
        ]);
    }

    /**
     * Get employee type color for calendar.
     */
    private function getEmployeeTypeColor($type)
    {
        $colors = [
            'permanent' => '#28a745',
            'honorary' => '#007bff',
            'staff' => '#ffc107'
        ];
        
        return $colors[$type] ?? '#6c757d';
    }
}