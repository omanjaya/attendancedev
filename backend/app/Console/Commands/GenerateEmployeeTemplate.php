<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateEmployeeTemplate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'employee:generate-template';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate employee CSV template file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $templatePath = storage_path('templates');

        // Create templates directory if it doesn't exist
        if (! is_dir($templatePath)) {
            mkdir($templatePath, 0755, true);
            $this->info('Created templates directory.');
        }

        // Generate CSV template
        $csvContent = "employee_id,first_name,last_name,email,phone,employee_type,role,location_id,date_of_birth,gender,hire_date,address,is_active\n";
        $csvContent .= "EMP001,John,Doe,john.doe@example.com,+1234567890,permanent,employee,1,1990-01-15,male,2024-01-01,\"123 Main St, City, State 12345\",1\n";
        $csvContent .= "EMP002,Jane,Smith,jane.smith@example.com,+1234567891,honorary,teacher,1,1985-05-20,female,2024-01-15,\"456 Oak Ave, City, State 12345\",1\n";
        $csvContent .= "EMP003,Bob,Johnson,bob.johnson@example.com,+1234567892,staff,employee,2,1992-03-10,male,2024-02-01,\"789 Pine Rd, City, State 12345\",1\n";

        file_put_contents($templatePath.'/employees.csv', $csvContent);

        // Generate guide
        $guideContent = "EMPLOYEE IMPORT TEMPLATE GUIDE\n";
        $guideContent .= "================================\n\n";
        $guideContent .= "FIELD DESCRIPTIONS:\n";
        $guideContent .= "- employee_id: Unique identifier for employee (e.g., EMP001)\n";
        $guideContent .= "- first_name: Employee's first name\n";
        $guideContent .= "- last_name: Employee's last name\n";
        $guideContent .= "- email: Valid email address (must be unique)\n";
        $guideContent .= "- phone: Phone number with country code (e.g., +1234567890)\n";
        $guideContent .= "- employee_type: permanent, honorary, or staff\n";
        $guideContent .= "- role: employee, teacher, manager, or admin\n";
        $guideContent .= "- location_id: Location ID number (check with admin for valid IDs)\n";
        $guideContent .= "- date_of_birth: Date format: YYYY-MM-DD (e.g., 1990-01-15)\n";
        $guideContent .= "- gender: male, female, or other\n";
        $guideContent .= "- hire_date: Date format: YYYY-MM-DD (e.g., 2024-01-01)\n";
        $guideContent .= "- address: Full address in quotes if contains commas\n";
        $guideContent .= "- is_active: 1 for active, 0 for inactive\n\n";
        $guideContent .= "VALIDATION RULES:\n";
        $guideContent .= "- employee_id: Required, must be unique\n";
        $guideContent .= "- first_name: Required, max 50 characters\n";
        $guideContent .= "- last_name: Required, max 50 characters\n";
        $guideContent .= "- email: Required, valid email format, must be unique\n";
        $guideContent .= "- employee_type: Required, must be one of: permanent, honorary, staff\n";
        $guideContent .= "- role: Required, must be valid role in system\n";
        $guideContent .= "- location_id: Required, must exist in system\n";
        $guideContent .= "- is_active: Required, must be 1 or 0\n\n";
        $guideContent .= "NOTES:\n";
        $guideContent .= "- Remove example rows before importing\n";
        $guideContent .= "- Keep header row intact\n";
        $guideContent .= "- Save as CSV format\n";
        $guideContent .= "- Maximum 100 rows per import\n";
        $guideContent .= "- Duplicate emails will be rejected\n";

        file_put_contents($templatePath.'/employees_template_guide.txt', $guideContent);

        $this->info('Employee template files generated successfully!');
        $this->info('Files created:');
        $this->info('- '.$templatePath.'/employees.csv');
        $this->info('- '.$templatePath.'/employees_template_guide.txt');

        return Command::SUCCESS;
    }
}
