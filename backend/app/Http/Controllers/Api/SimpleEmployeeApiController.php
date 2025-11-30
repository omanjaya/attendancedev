<?php

namespace App\Http\Controllers\Api;

use App\Repositories\Interfaces\EmployeeRepositoryInterface;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * Simple Employee API Controller for Documentation Demo
 */
class SimpleEmployeeApiController extends BaseApiController
{
    public function __construct(protected EmployeeRepositoryInterface $employeeRepository) {}

    /**
     * @OA\Get(
     *     path="/api/v1/employees",
     *     operationId="getEmployeesListDemo",
     *     tags={"Employees"},
     *     summary="Get list of employees",
     *     description="Retrieve a paginated list of employees with optional filtering",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *
     *         @OA\Schema(type="integer", minimum=1, default=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by name, employee code, or email",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Employees retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(
     *                     type="object",
     *
     *                     @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                     @OA\Property(property="full_name", type="string", example="John Doe"),
     *                     @OA\Property(property="employee_code", type="string", example="EMP2024001"),
     *                     @OA\Property(property="department", type="string", example="Mathematics"),
     *                     @OA\Property(property="position", type="string", example="Senior Teacher"),
     *                     @OA\Property(property="email", type="string", example="john.doe@school.com"),
     *                     @OA\Property(property="is_active", type="boolean", example=true)
     *                 )
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Insufficient permissions",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="This action is unauthorized"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $this->authorize('view_employees');

        $filters = $request->only(['search', 'department', 'position', 'employment_type', 'is_active']);

        $employees = $this->employeeRepository->getAll(
            array_filter($filters),
            $request->get('per_page', 15),
        );

        return $this->paginatedResponse($employees, 'Employees retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/employees",
     *     operationId="createEmployeeDemo",
     *     tags={"Employees"},
     *     summary="Create a new employee",
     *     description="Create a new employee record",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Employee data",
     *
     *         @OA\JsonContent(
     *             required={"full_name", "email", "department", "position", "employment_type", "salary_type", "base_salary", "hire_date"},
     *
     *             @OA\Property(property="full_name", type="string", maxLength=255, example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@school.com"),
     *             @OA\Property(property="phone", type="string", maxLength=20, nullable=true, example="+1234567890"),
     *             @OA\Property(property="employee_code", type="string", nullable=true, example="EMP2024001"),
     *             @OA\Property(property="department", type="string", maxLength=100, example="Mathematics"),
     *             @OA\Property(property="position", type="string", maxLength=100, example="Senior Teacher"),
     *             @OA\Property(property="employment_type", type="string", enum={"permanent", "contract", "part_time", "honorary"}, example="permanent"),
     *             @OA\Property(property="salary_type", type="string", enum={"monthly", "hourly"}, example="monthly"),
     *             @OA\Property(property="base_salary", type="number", format="float", example=5000.00),
     *             @OA\Property(property="hire_date", type="string", format="date", example="2024-01-15"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Employee created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Employee created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="full_name", type="string", example="John Doe"),
     *                 @OA\Property(property="employee_code", type="string", example="EMP2024001"),
     *                 @OA\Property(property="department", type="string", example="Mathematics"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="email",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The email field is required.")
     *                 )
     *             ),
     *
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $this->authorize('create_employees');

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'phone' => 'nullable|string|max:20',
            'employee_code' => 'nullable|string|unique:employees,employee_code',
            'department' => 'required|string|max:100',
            'position' => 'required|string|max:100',
            'employment_type' => 'required|in:permanent,contract,part_time,honorary',
            'salary_type' => 'required|in:monthly,hourly',
            'base_salary' => 'required|numeric|min:0',
            'hire_date' => 'required|date',
            'is_active' => 'boolean',
        ]);

        try {
            $employee = $this->employeeRepository->create($validated);

            return $this->apiResponse(
                $employee->load(['user', 'location']),
                'Employee created successfully',
                201,
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create employee: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/employees/statistics",
     *     operationId="getEmployeeStatisticsDemo",
     *     tags={"Employees"},
     *     summary="Get employee statistics",
     *     description="Get statistical overview of employees including totals, attendance rates, etc.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Statistics retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Statistics retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=150, description="Total employees"),
     *                 @OA\Property(property="active", type="integer", example=145, description="Active employees"),
     *                 @OA\Property(property="inactive", type="integer", example=5, description="Inactive employees"),
     *                 @OA\Property(property="present_today", type="integer", example=132, description="Employees present today"),
     *                 @OA\Property(property="absent_today", type="integer", example=13, description="Employees absent today"),
     *                 @OA\Property(property="attendance_rate", type="number", format="float", example=91.03, description="Today's attendance rate percentage")
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function statistics()
    {
        $this->authorize('view_employees');

        $statistics = $this->employeeRepository->getStatistics();

        return $this->apiResponse($statistics, 'Statistics retrieved successfully');
    }
}
