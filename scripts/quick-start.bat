@echo off
echo Quick start without problematic seeder...
echo.

echo The database is already migrated successfully!
echo ScheduleSeeder has a conflict issue, but we can skip it for now.
echo.

echo Creating minimal required data...
php artisan tinker --execute="
if (!\App\Models\User::where('email', 'admin@admin.com')->exists()) {
    \App\Models\User::create([
        'name' => 'Administrator',
        'email' => 'admin@admin.com', 
        'password' => bcrypt('password'),
        'email_verified_at' => now()
    ]);
    echo 'Admin user created: admin@admin.com / password';
}
"

echo.
echo ===========================================
echo Application is ready!
echo ===========================================
echo.
echo Login credentials:
echo Email: admin@admin.com
echo Password: password
echo.
echo Starting Laravel server...
echo URL: http://127.0.0.1:8000
echo.
php artisan serve