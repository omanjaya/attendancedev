# 📝 Template Import Karyawan - Format Excel

## Struktur Template Excel

Template import karyawan memiliki **3 sheet** untuk memudahkan proses import:

### 1. **Sheet "Template Import Karyawan"** (Main Data Sheet)
Kolom yang harus diisi:

| Kolom | Nama Kolom | Tipe Data | Wajib | Contoh |
|-------|------------|-----------|-------|---------|
| A | ID Karyawan | Text | ✅ | EMP001 |
| B | Nama Lengkap | Text | ✅ | John Doe |
| C | Email | Email | ✅ | john.doe@school.com |
| D | Telepon | Text | ❌ | 081234567890 |
| E | Tipe Karyawan | permanent/honorary/staff | ✅ | permanent |
| F | Tipe Gaji | monthly/hourly/fixed | ✅ | monthly |
| G | Gaji Bulanan | Number | ❌ | 5000000 |
| H | Tarif Per Jam | Number | ❌ | 50000 |
| I | Tanggal Bergabung | Date (YYYY-MM-DD) | ✅ | 2024-01-15 |
| J | Departemen | Text | ❌ | SD Negeri 1 |
| K | Posisi | Text | ❌ | Guru Kelas |
| L | Status | Aktif/Tidak Aktif | ✅ | Aktif |

### 2. **Sheet "Petunjuk Import"** (Instructions)
Berisi panduan lengkap cara menggunakan template:
- Format file yang didukung
- Kolom wajib dan opsional
- Aturan validasi data
- Tips untuk import yang sukses

### 3. **Sheet "Data Referensi"** (Reference Data)
Berisi daftar nilai yang valid untuk kolom tertentu:
- **Tipe Karyawan**: permanent, honorary, staff
- **Tipe Gaji**: monthly, hourly, fixed
- **Status**: Aktif, Tidak Aktif
- **Departemen**: Daftar lokasi/sekolah yang tersedia

## 🎨 Fitur Template

### ✅ **Visual Styling**
- Header berwarna biru dengan teks putih
- Border pada semua sel data
- Auto-width kolom untuk readability
- Sample data dengan 3 contoh karyawan

### ✅ **Data Validation**
- Email format validation
- Date format validation (YYYY-MM-DD)
- Dropdown constraints untuk enum fields
- Required field validation

### ✅ **Sample Data**
Template sudah terisi dengan 3 contoh data:

**Karyawan 1:**
- ID: EMP001
- Nama: John Doe  
- Email: john.doe@school.com
- Tipe: permanent (Tetap)
- Gaji: Rp 5.000.000/bulan

**Karyawan 2:**
- ID: EMP002
- Nama: Jane Smith
- Email: jane.smith@school.com  
- Tipe: honorary (Honorer)
- Gaji: Rp 50.000/jam

**Karyawan 3:**
- ID: EMP003
- Nama: Bob Wilson
- Email: bob.wilson@school.com
- Tipe: staff (Staf)
- Gaji: Rp 3.500.000/bulan

## 🔍 **Validasi Import**

### **Validasi Wajib**
- ✅ ID Karyawan harus unique
- ✅ Email harus format valid dan unique
- ✅ Nama Lengkap tidak boleh kosong
- ✅ Tipe Karyawan harus: permanent/honorary/staff
- ✅ Tipe Gaji harus: monthly/hourly/fixed

### **Validasi Opsional**
- ⚠️ Telepon (jika diisi, harus format nomor)
- ⚠️ Gaji (sesuai dengan tipe gaji)
- ⚠️ Departemen (harus ada di sistem)

### **Auto-Default Values**
- 🔐 Password: `password123` (semua karyawan baru)
- 👤 Role: `guru` (bisa diubah manual setelah import)
- 📅 Import Date: Tanggal saat import dilakukan

## 🚨 **Error Handling**

Sistem akan menampilkan error spesifik untuk:
- **Baris 5**: Email tidak valid format
- **Baris 8**: ID Karyawan sudah ada
- **Baris 12**: Tipe Karyawan tidak valid
- **Baris 15**: Departemen tidak ditemukan

## 📊 **Import Summary**

Setelah import selesai, sistem menampilkan:
```
✅ Import berhasil! 25 karyawan berhasil diimpor dari 28 total baris
⚠️ 3 baris gagal diimpor:
   - Baris 5: Format email tidak valid
   - Baris 12: Tipe karyawan harus permanent/honorary/staff  
   - Baris 20: ID Karyawan EMP001 sudah ada
```

## 🎯 **Best Practices**

1. **Selalu download template terbaru** sebelum import
2. **Jangan ubah nama kolom** di baris header
3. **Hapus sample data** sebelum isi data asli
4. **Validasi email** sebelum upload (harus unique)
5. **Maksimal 1000 karyawan** per sekali import
6. **Backup data** sebelum import besar
7. **Test dengan sample kecil** dulu (5-10 karyawan)

## 🔧 **Technical Details**

- **Library**: PhpSpreadsheet untuk parsing Excel
- **Max File Size**: 5MB
- **Supported Formats**: .xlsx, .xls, .csv
- **Processing**: Server-side validation before database insert
- **Transaction**: Atomic operation (all-or-nothing per row)
- **Security**: CSRF protection, file type validation, user permission check

## 📱 **Mobile Responsive**

Import modal dirancang responsive untuk:
- 📱 **Mobile**: Stack layout, touch-friendly
- 💻 **Tablet**: 2-column layout
- 🖥️ **Desktop**: Full modal dengan drag-drop

Template ini memastikan import data karyawan berjalan lancar dan sesuai dengan struktur database yang ada.