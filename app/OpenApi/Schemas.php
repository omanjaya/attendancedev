<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * OpenAPI Schema Definitions
 *
 * This file contains all the schema definitions for the API documentation.
 */
class Schemas
{
    /**
 * @OA\Schema(
 *     schema="Employee",
 *     type="object",
 *     title="Employee",
 *     description="Employee model",
 *     required={"id", "full_name", "email", "employee_code", "department", "position", "employment_type"},
 *
 *     @OA\Property(property="id", type="string", format="uuid", description="Employee unique identifier"),
 *     @OA\Property(property="full_name", type="string", maxLength=255, description="Full name of the employee"),
 *     @OA\Property(property="email", type="string", format="email", description="Employee email address"),
 *     @OA\Property(property="phone", type="string", maxLength=20, nullable=true, description="Phone number"),
 *     @OA\Property(property="employee_code", type="string", description="Unique employee code"),
 *     @OA\Property(property="department", type="string", maxLength=100, description="Department name"),
 *     @OA\Property(property="position", type="string", maxLength=100, description="Job position"),
 *     @OA\Property(property="employment_type", type="string", enum={"permanent", "contract", "part_time", "honorary"}, description="Type of employment"),
 *     @OA\Property(property="salary_type", type="string", enum={"monthly", "hourly"}, description="Salary calculation type"),
 *     @OA\Property(property="base_salary", type="number", format="float", description="Base salary amount"),
 *     @OA\Property(property="hire_date", type="string", format="date", description="Date of hire"),
 *     @OA\Property(property="is_active", type="boolean", description="Whether employee is active"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp"),
 *     @OA\Property(property="user", ref="#/components/schemas/User", nullable=true, description="Associated user account"),
 *     @OA\Property(property="location", ref="#/components/schemas/Location", nullable=true, description="Work location")
 * )
 */
    /**
     * @OA\Schema(
     *     schema="EmployeeDetailed",
     *     allOf={
     *         @OA\Schema(ref="#/components/schemas/Employee"),
     *         @OA\Schema(
     *
     *             @OA\Property(
     *                 property="attendances",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/Attendance"),
     *                 description="Recent attendance records"
     *             ),
     *
     *             @OA\Property(
     *                 property="leave_balances",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/LeaveBalance"),
     *                 description="Leave balance information"
     *             )
     *         )
     *     }
     * )
     */
    /**
     * @OA\Schema(
     *     schema="EmployeeBasic",
     *     type="object",
     *
     *     @OA\Property(property="id", type="string", format="uuid"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="employee_code", type="string"),
     *     @OA\Property(property="department", type="string"),
     *     @OA\Property(property="position", type="string")
     * )
     */
    /**
     * @OA\Schema(
     *     schema="EmployeeRequest",
     *     type="object",
     *     required={"full_name", "email", "department", "position", "employment_type", "salary_type", "base_salary", "hire_date"},
     *
     *     @OA\Property(property="full_name", type="string", maxLength=255, example="John Doe"),
     *     @OA\Property(property="email", type="string", format="email", example="john.doe@school.com"),
     *     @OA\Property(property="phone", type="string", maxLength=20, nullable=true, example="+1234567890"),
     *     @OA\Property(property="employee_code", type="string", nullable=true, example="EMP2024001"),
     *     @OA\Property(property="department", type="string", maxLength=100, example="Mathematics"),
     *     @OA\Property(property="position", type="string", maxLength=100, example="Senior Teacher"),
     *     @OA\Property(property="employment_type", type="string", enum={"permanent", "contract", "part_time", "honorary"}, example="permanent"),
     *     @OA\Property(property="salary_type", type="string", enum={"monthly", "hourly"}, example="monthly"),
     *     @OA\Property(property="base_salary", type="number", format="float", example=5000.00),
     *     @OA\Property(property="hire_date", type="string", format="date", example="2024-01-15"),
     *     @OA\Property(property="is_active", type="boolean", example=true)
     * )
     */
    /**
     * @OA\Schema(
     *     schema="EmployeeStatistics",
     *     type="object",
     *
     *     @OA\Property(property="total", type="integer", description="Total employees"),
     *     @OA\Property(property="active", type="integer", description="Active employees"),
     *     @OA\Property(property="inactive", type="integer", description="Inactive employees"),
     *     @OA\Property(property="present_today", type="integer", description="Employees present today"),
     *     @OA\Property(property="absent_today", type="integer", description="Employees absent today"),
     *     @OA\Property(property="attendance_rate", type="number", format="float", description="Today's attendance rate percentage")
     * )
     */
    /**
     * @OA\Schema(
     *     schema="User",
     *     type="object",
     *
     *     @OA\Property(property="id", type="integer", description="User ID"),
     *     @OA\Property(property="name", type="string", description="User name"),
     *     @OA\Property(property="email", type="string", format="email", description="Email address"),
     *     @OA\Property(property="is_active", type="boolean", description="Whether user is active"),
     *     @OA\Property(property="last_login_at", type="string", format="date-time", nullable=true, description="Last login timestamp"),
     *     @OA\Property(property="roles", type="array", @OA\Items(ref="#/components/schemas/Role"), description="User roles")
     * )
     */
    /**
     * @OA\Schema(
     *     schema="Role",
     *     type="object",
     *
     *     @OA\Property(property="id", type="integer", description="Role ID"),
     *     @OA\Property(property="name", type="string", description="Role name"),
     *     @OA\Property(property="guard_name", type="string", description="Guard name")
     * )
     */
    /**
     * @OA\Schema(
     *     schema="Location",
     *     type="object",
     *
     *     @OA\Property(property="id", type="string", format="uuid", description="Location ID"),
     *     @OA\Property(property="name", type="string", description="Location name"),
     *     @OA\Property(property="address", type="string", description="Full address"),
     *     @OA\Property(property="latitude", type="number", format="float", description="Latitude coordinate"),
     *     @OA\Property(property="longitude", type="number", format="float", description="Longitude coordinate"),
     *     @OA\Property(property="radius", type="integer", description="Allowed radius in meters")
     * )
     */
    /**
     * @OA\Schema(
     *     schema="Attendance",
     *     type="object",
     *
     *     @OA\Property(property="id", type="string", format="uuid", description="Attendance ID"),
     *     @OA\Property(property="employee_id", type="string", format="uuid", description="Employee ID"),
     *     @OA\Property(property="check_in_time", type="string", format="date-time", description="Check-in timestamp"),
     *     @OA\Property(property="check_out_time", type="string", format="date-time", nullable=true, description="Check-out timestamp"),
     *     @OA\Property(property="status", type="string", enum={"present", "late", "absent", "on_leave"}, description="Attendance status"),
     *     @OA\Property(property="working_hours", type="number", format="float", nullable=true, description="Total working hours"),
     *     @OA\Property(property="overtime_hours", type="number", format="float", default=0, description="Overtime hours"),
     *     @OA\Property(property="location_verified", type="boolean", description="Whether location was verified"),
     *     @OA\Property(property="face_verified", type="boolean", description="Whether face recognition was verified")
     * )
     */
    /**
     * @OA\Schema(
     *     schema="LeaveBalance",
     *     type="object",
     *
     *     @OA\Property(property="leave_type", type="string", description="Type of leave"),
     *     @OA\Property(property="total_days", type="number", format="float", description="Total allocated days"),
     *     @OA\Property(property="used_days", type="number", format="float", description="Used days"),
     *     @OA\Property(property="remaining_days", type="number", format="float", description="Remaining days")
     * )
     */
    /**
     * @OA\Schema(
     *     schema="Pagination",
     *     type="object",
     *
     *     @OA\Property(property="current_page", type="integer", description="Current page number"),
     *     @OA\Property(property="last_page", type="integer", description="Last page number"),
     *     @OA\Property(property="per_page", type="integer", description="Items per page"),
     *     @OA\Property(property="total", type="integer", description="Total items"),
     *     @OA\Property(property="from", type="integer", description="First item number on current page"),
     *     @OA\Property(property="to", type="integer", description="Last item number on current page"),
     *     @OA\Property(property="has_more_pages", type="boolean", description="Whether there are more pages")
     * )
     */
    /**
     * @OA\Schema(
     *     schema="ErrorResponse",
     *     type="object",
     *
     *     @OA\Property(property="success", type="boolean", example=false),
     *     @OA\Property(property="message", type="string", description="Error message"),
     *     @OA\Property(property="timestamp", type="string", format="date-time")
     * )
     */
    /**
     * @OA\Schema(
     *     schema="ValidationErrorResponse",
     *     type="object",
     *
     *     @OA\Property(property="success", type="boolean", example=false),
     *     @OA\Property(property="message", type="string", example="Validation failed"),
     *     @OA\Property(
     *         property="errors",
     *         type="object",
     *         additionalProperties={
     *             "type": "array",
     *
     *             @OA\Items(type="string")
     *         },
     *         description="Validation errors by field"
     *     ),
     *
     *     @OA\Property(property="timestamp", type="string", format="date-time")
     * )
     */
}
