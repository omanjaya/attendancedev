# 🔐 Menambahkan Permission Import Employees

## ❌ **Masalah yang Ditemukan:**

Button **"Import Data"** tidak muncul di Employee Management karena:
- Permission `import_employees_data` tidak ada di database
- Kondisi `@can('import_employees_data')` menghasilkan `false`

## ✅ **Solusi yang Diterapkan:**

### 1. **Update Permission Seeder** 
Menambahkan permission baru di `RolesAndPermissionsSeeder.php`:
```php
'import_employees_data',         // Import employee data from Excel/CSV
```

### 2. **Update Role Permissions**
Permission diberikan ke:
- ✅ **Super Admin**: Otomatis (sudah dapat semua permission)
- ✅ **Admin**: Ditambahkan secara eksplisit  
- ✅ **Kepala Sekolah**: Ditambahkan untuk import data
- ❌ **Guru/Staff**: Tidak perlu akses import

### 3. **Update UI Condition** 
Mengubah condition dari:
```blade
@can('import_employees_data')
```

Ke fallback yang lebih robust:
```blade
@canany(['manage_employees', 'create_employees', 'import_employees_data'])
```

## 🚀 **Cara Menjalankan Update:**

### **Option 1: Run Seeder** (Recommended)
```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```

### **Option 2: Run Full Migration** (Jika diperlukan)
```bash
php artisan migrate:fresh --seed
```

### **Option 3: Manual Permission Add**
```bash
php artisan tinker
```
```php
// Di Tinker console:
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Create permission
Permission::firstOrCreate(['name' => 'import_employees_data']);

// Add to Admin role
$admin = Role::findByName('Admin');
$admin->givePermissionTo('import_employees_data');

// Add to Kepala Sekolah role  
$principal = Role::findByName('kepala_sekolah');
$principal->givePermissionTo('import_employees_data');

exit
```

## 🎯 **Verification:**

Setelah menjalankan update, button **"Import Data"** akan muncul untuk user dengan role:
- Super Admin ✅
- Admin ✅  
- Kepala Sekolah ✅

## 📋 **Permission Structure Update:**

```php
// Employee Management Permissions
'view_employees',                // View employee list and details
'create_employees',              // Add new employees  
'edit_employees',                // Modify employee information
'delete_employees',              // Remove employees
'manage_employees',              // Full employee management
'export_employees_data',         // Export employee data
'import_employees_data',         // Import employee data from Excel/CSV ⬅️ NEW
```

## 🔧 **Fallback UI Condition:**

Button sekarang menggunakan `@canany()` untuk multiple permission check:
- Jika ada `manage_employees` → Show button
- Jika ada `create_employees` → Show button  
- Jika ada `import_employees_data` → Show button

Ini memastikan button tetap muncul meskipun permission structure berubah.