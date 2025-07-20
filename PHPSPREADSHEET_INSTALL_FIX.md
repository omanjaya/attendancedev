# PhpSpreadsheet Installation Fix

## Issue
The `PhpOffice\PhpSpreadsheet\IOFactory` class is not found, causing Excel import functionality to fail with a 500 error.

## Root Cause
The PhpSpreadsheet library is listed in `composer.json` but appears to not be properly installed in the vendor directory.

## Solution

### 1. Install Dependencies
Run composer install to ensure all dependencies are properly installed:
```bash
composer install
```

### 2. Alternative: Force Reinstall
If the above doesn't work, try clearing and reinstalling:
```bash
composer clear-cache
rm -rf vendor/
rm composer.lock
composer install
```

### 3. Verify Installation
Check if PhpSpreadsheet is installed:
```bash
ls vendor/phpoffice/
```

You should see a `phpspreadsheet` directory.

### 4. Test in PHP
```php
<?php
require_once 'vendor/autoload.php';

if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
    echo "PhpSpreadsheet is installed correctly\n";
} else {
    echo "PhpSpreadsheet is NOT installed\n";
}
?>
```

## Temporary Workaround (Current Implementation)

Until PhpSpreadsheet is installed, the system now:

1. **Gracefully handles missing library** - Shows user-friendly error messages instead of crashing
2. **CSV-only mode** - Only accepts CSV files for import
3. **Visual indicators** - Excel buttons are disabled with warning icons
4. **Clear messaging** - Users see notifications about the missing library

### Current Features:
- ✅ CSV import works fully
- ✅ Employee ID auto-generation works
- ✅ Role-based import works
- ✅ Preview and validation works
- ❌ Excel template download disabled
- ❌ Excel file import disabled

## Files Modified for Graceful Handling:

1. **EmployeeService.php** - Added class_exists check before using PhpSpreadsheet
2. **ExcelTemplateService.php** - Added class_exists check with user-friendly error
3. **EmployeeController.php** - Updated validation to only accept CSV files
4. **Employee index view** - Added warnings and disabled Excel buttons

## Post-Installation Steps:

Once PhpSpreadsheet is installed:

1. **Update validation** in `EmployeeController.php`:
   ```php
   'file' => 'required|file|mimes:csv,xlsx,xls|max:5120',
   ```

2. **Re-enable Excel button** in the view by removing the `disabled` attribute

3. **Update file input** to accept Excel files:
   ```html
   <input type="file" id="importFile" name="file" accept=".xlsx,.xls,.csv" class="hidden">
   ```

## Testing After Installation:

1. Download Excel template should work
2. Excel file import should work
3. CSV import should continue working
4. Preview functionality should work for both formats

## Dependencies in composer.json:
```json
{
    "require": {
        "phpoffice/phpspreadsheet": "^2.3"
    }
}
```

This is already correctly listed, just needs proper installation.