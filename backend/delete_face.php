$employee = App\Models\Employee::where('full_name', 'like', '%oman%')->first();
if ($employee) {
    echo "Found employee: " . $employee->full_name . "\n";
    $metadata = $employee->metadata;
    if (isset($metadata['face_recognition'])) {
        echo "Face data found. Deleting...\n";
        unset($metadata['face_recognition']);
        $employee->metadata = $metadata;
        $employee->save();
        echo "Face data deleted.\n";
    } else {
        echo "No face data found.\n";
    }
} else {
    echo "Employee not found.\n";
}
