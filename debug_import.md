# Debug Import Issue

## Potential Issues:

1. **Header Mapping**: Template header might not match expected keys
2. **Role Validation**: Role names might not match database
3. **Employee Model Boot**: Auto-generation might fail
4. **Transaction Rollback**: Database transaction might be rolling back

## Debug Steps:

### 1. Check Template Headers
Template CSV headers should be:
```
full_name,email,phone,employee_type,role,salary_type,salary_amount,hourly_rate,hire_date,department,position,status
```

### 2. Check Role Names
Valid roles in database:
- Super Admin
- Admin  
- kepala_sekolah
- guru
- pegawai

### 3. Test Sample Data
Try importing with exact data:
```csv
full_name,email,phone,employee_type,role,salary_type,salary_amount,hourly_rate,hire_date,department,position,status
Test User,test@example.com,081234567890,permanent,guru,monthly,5000000,,2024-01-15,SD Negeri 1,Guru Kelas,Aktif
```

### 4. Check Error Messages
- View browser console for JavaScript errors
- Check Laravel logs for PHP errors
- Verify import response messages

### 5. Verify Database
Check if data partially created:
- Users table
- Employees table  
- Role assignments

## Fixed Issues:
- ✅ Header mapping for CSV/Excel parsing
- ✅ Role name normalization
- ✅ Employee ID auto-generation
- ✅ Validation logic cleanup