# 🆔 Auto-Generate Employee ID Implementation

## Overview
Sistem sekarang akan otomatis generate Employee ID berdasarkan kombinasi Role + Employee Type, baik saat import Excel maupun saat tambah pegawai manual.

## Implementation Details

### 1. EmployeeIdGeneratorService
Location: `app/Services/EmployeeIdGeneratorService.php`

Service ini menangani:
- Generate ID dengan format prefix + 3 digit angka
- Locking mechanism untuk mencegah duplicate
- Retry mechanism jika terjadi collision

### 2. ID Format Mapping

| Role | Employee Type | Prefix | Example |
|------|--------------|--------|---------|
| Super Admin | Any | SADM | SADM001 |
| Admin | Any | ADMN | ADMN001 |
| Kepala Sekolah | Any | KPS | KPS001 |
| Guru | permanent | GTTP | GTTP001 |
| Guru | honorary | GHNR | GHNR001 |
| Guru | other | GURU | GURU001 |
| Pegawai | honorary | PHNR | PHNR001 |
| Pegawai | other | PGWI | PGWI001 |

### 3. Changes Made

#### Employee Model
- Added `boot()` method untuk auto-generate ID saat creating
- ID akan digenerate jika tidak disediakan manual

#### EmployeeService
- Injected `EmployeeIdGeneratorService`
- Updated `create()` method untuk generate ID
- Updated `mapImportData()` untuk remove employee_id requirement

#### Excel Template
- Removed employee_id column
- Added role column dengan dropdown
- Updated instructions dengan info auto-generate

#### CSV Template
- Removed employee_id dari headers
- Added role column
- Added detailed instructions di bagian bawah

#### Create Employee Form
- Employee ID field menjadi readonly
- Added JavaScript preview untuk menampilkan format ID
- Reordered fields: Employee Type & Role di atas

#### Import Modal
- Updated kolom wajib (removed employee_id, added role)
- Added visual guide untuk format ID
- Updated instructions

### 4. Usage

#### Manual Creation
1. User pilih Employee Type dan Role
2. System preview format ID yang akan digenerate
3. Saat save, ID otomatis digenerate

#### Excel/CSV Import
1. User isi semua kolom kecuali employee_id
2. Pastikan kolom role terisi dengan benar
3. System akan generate ID untuk setiap row

### 5. Testing Scenarios

```php
// Test cases untuk validasi:
1. Guru Tetap → GTTP001, GTTP002, dst
2. Guru Honor → GHNR001, GHNR002, dst
3. Multiple import → Sequential numbering
4. Concurrent creation → No duplicates
```

### 6. Rollback Plan

Jika perlu rollback ke manual ID:
1. Comment out boot method di Employee model
2. Update form untuk enable employee_id field
3. Update template untuk add employee_id column

## Notes
- Password default tetap: `password123`
- Existing employees dengan ID manual tidak terpengaruh
- ID sequence per prefix (GTTP001, GHNR001 bisa exist bersamaan)