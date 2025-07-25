<?php

return [
    'title' => 'Manajemen Karyawan',
    'subtitle' => 'Kelola data pegawai organisasi Anda dengan mudah',
    'directory_title' => 'Direktori Karyawan',
    'directory_subtitle' => 'Kelola data pegawai organisasi Anda dengan mudah',

    'navigation' => [
        'employees' => 'Karyawan',
        'directory' => 'Direktori Karyawan',
        'management' => 'Manajemen Karyawan',
        'list' => 'Daftar Karyawan',
        'add' => 'Tambah Karyawan',
        'edit' => 'Edit Karyawan',
        'view' => 'Lihat Karyawan',
        'profile' => 'Profil Karyawan',
        'details' => 'Detail Karyawan',
    ],

    'actions' => [
        'add_new' => 'Tambah Karyawan',
        'add_employee' => 'Tambah Karyawan Baru',
        'edit_employee' => 'Edit Karyawan',
        'view_employee' => 'Lihat Karyawan',
        'delete_employee' => 'Hapus Karyawan',
        'bulk_edit' => 'Edit Massal',
        'bulk_delete' => 'Hapus Massal',
        'export_data' => 'Ekspor Data',
        'import_data' => 'Impor Data',
        'download_template' => 'Unduh Template',
        'upload_photo' => 'Unggah Foto',
        'register_face' => 'Daftarkan Wajah',
        'update_face' => 'Perbarui Data Wajah',
        'activate' => 'Aktifkan',
        'deactivate' => 'Nonaktifkan',
        'suspend' => 'Tangguhkan',
        'restore' => 'Pulihkan',
        'archive' => 'Arsipkan',
        'assign_schedule' => 'Atur Jadwal',
        'assign_location' => 'Atur Lokasi',
        'view_attendance' => 'Lihat Absensi',
        'view_payroll' => 'Lihat Gaji',
        'send_invitation' => 'Kirim Undangan',
        'reset_password' => 'Reset Kata Sandi',
        'select_all' => 'Pilih Semua',
        'clear_selection' => 'Hapus Pilihan',
        'bulk_actions' => 'Aksi Massal',
    ],

    'fields' => [
        'employee_id' => 'ID Karyawan',
        'employee_number' => 'Nomor Karyawan',
        'full_name' => 'Nama Lengkap',
        'first_name' => 'Nama Depan',
        'last_name' => 'Nama Belakang',
        'email' => 'Email',
        'phone' => 'Telepon',
        'mobile' => 'Telepon Seluler',
        'address' => 'Alamat',
        'city' => 'Kota',
        'state' => 'Provinsi',
        'postal_code' => 'Kode Pos',
        'country' => 'Negara',
        'birth_date' => 'Tanggal Lahir',
        'birth_place' => 'Tempat Lahir',
        'gender' => 'Jenis Kelamin',
        'marital_status' => 'Status Pernikahan',
        'religion' => 'Agama',
        'nationality' => 'Kewarganegaraan',
        'id_number' => 'Nomor KTP',
        'passport_number' => 'Nomor Paspor',
        'tax_number' => 'NPWP',
        'social_security' => 'BPJS',
        'emergency_contact' => 'Kontak Darurat',
        'emergency_phone' => 'Telepon Darurat',
        'emergency_relationship' => 'Hubungan Darurat',
        'photo' => 'Foto',
        'avatar' => 'Avatar',
        'profile_picture' => 'Foto Profil',
        'position' => 'Posisi',
        'job_title' => 'Jabatan',
        'department' => 'Departemen',
        'division' => 'Divisi',
        'unit' => 'Unit Kerja',
        'location' => 'Lokasi Kerja',
        'supervisor' => 'Atasan',
        'manager' => 'Manajer',
        'employee_type' => 'Jenis Karyawan',
        'employment_status' => 'Status Kepegawaian',
        'work_status' => 'Status Kerja',
        'hire_date' => 'Tanggal Masuk',
        'start_date' => 'Tanggal Mulai',
        'end_date' => 'Tanggal Berakhir',
        'probation_end' => 'Akhir Masa Percobaan',
        'contract_end' => 'Akhir Kontrak',
        'termination_date' => 'Tanggal Berhenti',
        'termination_reason' => 'Alasan Berhenti',
        'resignation_date' => 'Tanggal Pengunduran Diri',
        'retirement_date' => 'Tanggal Pensiun',
        'salary' => 'Gaji',
        'base_salary' => 'Gaji Pokok',
        'hourly_rate' => 'Tarif per Jam',
        'monthly_salary' => 'Gaji Bulanan',
        'annual_salary' => 'Gaji Tahunan',
        'allowances' => 'Tunjangan',
        'benefits' => 'Manfaat',
        'insurance' => 'Asuransi',
        'bank_name' => 'Nama Bank',
        'bank_account' => 'Nomor Rekening',
        'account_holder' => 'Nama Pemegang Rekening',
        'education' => 'Pendidikan',
        'degree' => 'Gelar',
        'major' => 'Jurusan',
        'university' => 'Universitas',
        'graduation_year' => 'Tahun Lulus',
        'skills' => 'Keahlian',
        'certifications' => 'Sertifikasi',
        'languages' => 'Bahasa',
        'experience' => 'Pengalaman',
        'previous_company' => 'Perusahaan Sebelumnya',
        'notes' => 'Catatan',
        'remarks' => 'Keterangan',
        'description' => 'Deskripsi',
        'is_active' => 'Aktif',
        'is_suspended' => 'Ditangguhkan',
        'is_archived' => 'Diarsipkan',
        'face_registered' => 'Wajah Terdaftar',
        'face_template' => 'Template Wajah',
        'face_confidence' => 'Tingkat Keyakinan Wajah',
        'last_login' => 'Login Terakhir',
        'last_attendance' => 'Absensi Terakhir',
        'attendance_count' => 'Jumlah Absensi',
        'late_count' => 'Jumlah Terlambat',
        'absent_count' => 'Jumlah Tidak Hadir',
        'overtime_hours' => 'Jam Lembur',
        'leave_balance' => 'Saldo Cuti',
        'sick_leave' => 'Cuti Sakit',
        'annual_leave' => 'Cuti Tahunan',
        'personal_leave' => 'Cuti Pribadi',
        'maternity_leave' => 'Cuti Melahirkan',
        'created_at' => 'Dibuat Pada',
        'updated_at' => 'Diperbarui Pada',
        'created_by' => 'Dibuat Oleh',
        'updated_by' => 'Diperbarui Oleh',
    ],

    'placeholders' => [
        'search_employees' => 'Cari karyawan...',
        'search_by_name' => 'Cari berdasarkan nama...',
        'search_by_id' => 'Cari berdasarkan ID...',
        'search_by_email' => 'Cari berdasarkan email...',
        'enter_employee_id' => 'Masukkan ID karyawan (contoh: EMP001)',
        'enter_full_name' => 'Masukkan nama lengkap',
        'enter_email' => 'Masukkan alamat email',
        'enter_phone' => 'Masukkan nomor telepon',
        'enter_address' => 'Masukkan alamat lengkap',
        'enter_position' => 'Masukkan posisi/jabatan',
        'enter_salary' => 'Masukkan gaji (Rp)',
        'enter_notes' => 'Masukkan catatan tambahan',
        'select_department' => 'Pilih departemen',
        'select_position' => 'Pilih posisi',
        'select_type' => 'Pilih jenis karyawan',
        'select_status' => 'Pilih status',
        'select_location' => 'Pilih lokasi kerja',
        'select_supervisor' => 'Pilih atasan',
        'select_gender' => 'Pilih jenis kelamin',
        'select_religion' => 'Pilih agama',
        'select_education' => 'Pilih tingkat pendidikan',
        'choose_file' => 'Pilih file',
        'choose_photo' => 'Pilih foto',
        'optional' => 'Opsional',
    ],

    'types' => [
        'permanent' => 'Tetap',
        'contract' => 'Kontrak',
        'temporary' => 'Sementara',
        'intern' => 'Magang',
        'freelance' => 'Freelance',
        'part_time' => 'Paruh Waktu',
        'full_time' => 'Penuh Waktu',
        'honorary' => 'Honorer',
        'staff' => 'Staf',
        'teacher' => 'Guru',
        'lecturer' => 'Dosen',
        'administrator' => 'Administrator',
        'manager' => 'Manajer',
        'supervisor' => 'Supervisor',
        'director' => 'Direktur',
        'consultant' => 'Konsultan',
        'volunteer' => 'Sukarelawan',
    ],

    'status' => [
        'active' => 'Aktif',
        'inactive' => 'Tidak Aktif',
        'suspended' => 'Ditangguhkan',
        'terminated' => 'Diberhentikan',
        'resigned' => 'Mengundurkan Diri',
        'retired' => 'Pensiun',
        'on_leave' => 'Cuti',
        'probation' => 'Masa Percobaan',
        'contract_ended' => 'Kontrak Berakhir',
        'archived' => 'Diarsipkan',
        'pending' => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'under_review' => 'Sedang Ditinjau',
    ],

    'gender' => [
        'male' => 'Laki-laki',
        'female' => 'Perempuan',
        'other' => 'Lainnya',
        'not_specified' => 'Tidak Disebutkan',
    ],

    'marital_status' => [
        'single' => 'Belum Menikah',
        'married' => 'Menikah',
        'divorced' => 'Bercerai',
        'widowed' => 'Janda/Duda',
        'separated' => 'Berpisah',
    ],

    'religion' => [
        'islam' => 'Islam',
        'christian' => 'Kristen',
        'catholic' => 'Katolik',
        'hindu' => 'Hindu',
        'buddhist' => 'Buddha',
        'confucian' => 'Kong Hu Cu',
        'other' => 'Lainnya',
        'not_specified' => 'Tidak Disebutkan',
    ],

    'education_level' => [
        'elementary' => 'SD',
        'junior_high' => 'SMP',
        'senior_high' => 'SMA/SMK',
        'diploma' => 'Diploma',
        'bachelor' => 'Sarjana (S1)',
        'master' => 'Magister (S2)',
        'doctorate' => 'Doktor (S3)',
        'other' => 'Lainnya',
    ],

    'messages' => [
        'created_successfully' => 'Karyawan berhasil ditambahkan',
        'updated_successfully' => 'Data karyawan berhasil diperbarui',
        'deleted_successfully' => 'Karyawan berhasil dihapus',
        'activated_successfully' => 'Karyawan berhasil diaktifkan',
        'deactivated_successfully' => 'Karyawan berhasil dinonaktifkan',
        'suspended_successfully' => 'Karyawan berhasil ditangguhkan',
        'restored_successfully' => 'Karyawan berhasil dipulihkan',
        'archived_successfully' => 'Karyawan berhasil diarsipkan',
        'bulk_updated_successfully' => ':count karyawan berhasil diperbarui',
        'bulk_deleted_successfully' => ':count karyawan berhasil dihapus',
        'imported_successfully' => ':count karyawan berhasil diimpor',
        'exported_successfully' => 'Data karyawan berhasil diekspor',
        'photo_uploaded_successfully' => 'Foto berhasil diunggah',
        'face_registered_successfully' => 'Data wajah berhasil didaftarkan',
        'face_updated_successfully' => 'Data wajah berhasil diperbarui',
        'invitation_sent_successfully' => 'Undangan berhasil dikirim ke :email',
        'password_reset_successfully' => 'Kata sandi berhasil direset',

        'error_creating' => 'Gagal menambahkan karyawan',
        'error_updating' => 'Gagal memperbarui data karyawan',
        'error_deleting' => 'Gagal menghapus karyawan',
        'error_activating' => 'Gagal mengaktifkan karyawan',
        'error_deactivating' => 'Gagal menonaktifkan karyawan',
        'error_suspending' => 'Gagal menangguhkan karyawan',
        'error_importing' => 'Gagal mengimpor data karyawan',
        'error_exporting' => 'Gagal mengekspor data karyawan',
        'error_uploading_photo' => 'Gagal mengunggah foto',
        'error_registering_face' => 'Gagal mendaftarkan data wajah',
        'error_sending_invitation' => 'Gagal mengirim undangan',
        'error_resetting_password' => 'Gagal mereset kata sandi',

        'not_found' => 'Karyawan tidak ditemukan',
        'already_exists' => 'ID karyawan atau email sudah digunakan',
        'cannot_delete_self' => 'Anda tidak dapat menghapus data diri sendiri',
        'cannot_delete_active' => 'Tidak dapat menghapus karyawan yang masih aktif',
        'cannot_suspend_self' => 'Anda tidak dapat menangguhkan diri sendiri',
        'has_active_attendance' => 'Karyawan memiliki data absensi aktif',
        'has_pending_leave' => 'Karyawan memiliki pengajuan cuti yang menunggu',
        'invalid_file_format' => 'Format file tidak didukung',
        'file_too_large' => 'Ukuran file terlalu besar (maksimal :size)',
        'face_already_registered' => 'Data wajah sudah terdaftar',
        'face_not_detected' => 'Wajah tidak terdeteksi pada foto',
        'face_quality_low' => 'Kualitas foto wajah terlalu rendah',
        'multiple_faces_detected' => 'Terdeteksi lebih dari satu wajah pada foto',
        'email_already_used' => 'Email sudah digunakan oleh karyawan lain',
        'employee_id_already_used' => 'ID karyawan sudah digunakan',
        'invalid_date_range' => 'Rentang tanggal tidak valid',
        'probation_cannot_exceed' => 'Masa percobaan tidak boleh melebihi :months bulan',
        'end_date_before_start' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai',
        'retirement_age_not_met' => 'Belum mencapai usia pensiun',
        'contract_still_active' => 'Kontrak masih aktif',
        'termination_in_probation' => 'Karyawan dalam masa percobaan',

        'confirm_delete' => 'Apakah Anda yakin ingin menghapus karyawan ini?',
        'confirm_delete_multiple' => 'Apakah Anda yakin ingin menghapus :count karyawan yang dipilih?',
        'confirm_activate' => 'Apakah Anda yakin ingin mengaktifkan karyawan ini?',
        'confirm_deactivate' => 'Apakah Anda yakin ingin menonaktifkan karyawan ini?',
        'confirm_suspend' => 'Apakah Anda yakin ingin menangguhkan karyawan ini?',
        'confirm_restore' => 'Apakah Anda yakin ingin memulihkan karyawan ini?',
        'confirm_archive' => 'Apakah Anda yakin ingin mengarsipkan karyawan ini?',
        'confirm_bulk_action' => 'Apakah Anda yakin ingin melakukan aksi ini pada :count karyawan yang dipilih?',
        'confirm_reset_password' => 'Apakah Anda yakin ingin mereset kata sandi karyawan ini?',
        'confirm_send_invitation' => 'Apakah Anda yakin ingin mengirim undangan ke karyawan ini?',

        'no_employees_found' => 'Tidak ada karyawan ditemukan',
        'no_employees_selected' => 'Tidak ada karyawan yang dipilih',
        'no_face_data' => 'Belum ada data wajah',
        'face_registered' => 'Wajah terdaftar',
        'face_not_registered' => 'Wajah belum terdaftar',
        'loading_employees' => 'Memuat data karyawan...',
        'processing_request' => 'Sedang memproses permintaan...',
        'uploading_photo' => 'Sedang mengunggah foto...',
        'processing_face' => 'Sedang memproses data wajah...',
        'sending_invitation' => 'Sedang mengirim undangan...',
        'generating_report' => 'Sedang membuat laporan...',
        'preparing_export' => 'Sedang menyiapkan ekspor data...',
        'processing_import' => 'Sedang memproses impor data...',
    ],

    'statistics' => [
        'total_employees' => 'Total Karyawan',
        'active_employees' => 'Karyawan Aktif',
        'inactive_employees' => 'Karyawan Tidak Aktif',
        'suspended_employees' => 'Karyawan Ditangguhkan',
        'new_this_month' => 'Baru Bulan Ini',
        'terminated_this_month' => 'Berhenti Bulan Ini',
        'on_leave_today' => 'Cuti Hari Ini',
        'pending_approvals' => 'Menunggu Persetujuan',
        'face_registered' => 'Wajah Terdaftar',
        'face_not_registered' => 'Wajah Belum Terdaftar',
        'present_today' => 'Hadir Hari Ini',
        'absent_today' => 'Tidak Hadir Hari Ini',
        'late_today' => 'Terlambat Hari Ini',
        'overtime_this_month' => 'Lembur Bulan Ini',
        'average_attendance' => 'Rata-rata Kehadiran',
        'attendance_rate' => 'Tingkat Kehadiran',
    ],

    'filters' => [
        'all_departments' => 'Semua Departemen',
        'all_positions' => 'Semua Posisi',
        'all_types' => 'Semua Jenis',
        'all_statuses' => 'Semua Status',
        'all_locations' => 'Semua Lokasi',
        'active_only' => 'Hanya Aktif',
        'inactive_only' => 'Hanya Tidak Aktif',
        'with_face' => 'Dengan Data Wajah',
        'without_face' => 'Tanpa Data Wajah',
        'recently_added' => 'Baru Ditambahkan',
        'recently_updated' => 'Baru Diperbarui',
        'on_probation' => 'Masa Percobaan',
        'contract_expiring' => 'Kontrak Akan Berakhir',
        'birthday_this_month' => 'Ulang Tahun Bulan Ini',
        'anniversary_this_month' => 'Anniversary Bulan Ini',
    ],

    'export' => [
        'filename' => 'data-karyawan',
        'excel_format' => 'Format Excel (.xlsx)',
        'csv_format' => 'Format CSV (.csv)',
        'pdf_format' => 'Format PDF (.pdf)',
        'include_all_data' => 'Sertakan semua data',
        'include_basic_info' => 'Hanya informasi dasar',
        'include_contact_info' => 'Sertakan informasi kontak',
        'include_employment_info' => 'Sertakan informasi kepegawaian',
        'include_salary_info' => 'Sertakan informasi gaji',
        'include_attendance_stats' => 'Sertakan statistik absensi',
        'date_range' => 'Rentang tanggal',
        'export_selected' => 'Ekspor yang dipilih',
        'export_filtered' => 'Ekspor hasil filter',
        'export_all' => 'Ekspor semua data',
    ],

    'import' => [
        'title' => 'Impor Data Karyawan',
        'subtitle' => 'Unggah file Excel atau CSV untuk mengimpor data karyawan',
        'download_template' => 'Unduh Template',
        'template_description' => 'Unduh template untuk format yang benar',
        'choose_file' => 'Pilih File',
        'supported_formats' => 'Format yang didukung: .xlsx, .csv',
        'max_file_size' => 'Ukuran maksimal: :size',
        'import_options' => 'Opsi Impor',
        'update_existing' => 'Perbarui data yang sudah ada',
        'skip_existing' => 'Lewati data yang sudah ada',
        'validate_only' => 'Hanya validasi (tidak simpan)',
        'import_preview' => 'Pratinjau Impor',
        'rows_to_import' => 'Baris yang akan diimpor',
        'rows_with_errors' => 'Baris dengan kesalahan',
        'total_rows' => 'Total baris',
        'valid_rows' => 'Baris valid',
        'invalid_rows' => 'Baris tidak valid',
        'duplicate_rows' => 'Baris duplikat',
        'import_summary' => 'Ringkasan Impor',
        'successfully_imported' => 'Berhasil diimpor',
        'failed_imports' => 'Gagal diimpor',
        'updated_records' => 'Record diperbarui',
        'new_records' => 'Record baru',
        'skipped_records' => 'Record dilewati',
    ],

    'bulk_actions' => [
        'select_action' => 'Pilih aksi',
        'activate_selected' => 'Aktifkan yang dipilih',
        'deactivate_selected' => 'Nonaktifkan yang dipilih',
        'suspend_selected' => 'Tangguhkan yang dipilih',
        'delete_selected' => 'Hapus yang dipilih',
        'archive_selected' => 'Arsipkan yang dipilih',
        'export_selected' => 'Ekspor yang dipilih',
        'assign_department' => 'Atur departemen',
        'assign_location' => 'Atur lokasi',
        'assign_supervisor' => 'Atur atasan',
        'update_salary' => 'Perbarui gaji',
        'send_notifications' => 'Kirim notifikasi',
        'generate_reports' => 'Buat laporan',
        'items_selected' => ':count item dipilih',
        'no_items_selected' => 'Tidak ada item yang dipilih',
        'action_completed' => 'Aksi berhasil dilakukan pada :count item',
        'action_failed' => 'Aksi gagal dilakukan',
        'partial_success' => 'Aksi berhasil pada :success dari :total item',
    ],

    'validation' => [
        'employee_id_required' => 'ID karyawan wajib diisi',
        'employee_id_unique' => 'ID karyawan sudah digunakan',
        'employee_id_format' => 'Format ID karyawan tidak valid',
        'full_name_required' => 'Nama lengkap wajib diisi',
        'full_name_min' => 'Nama lengkap minimal :min karakter',
        'full_name_max' => 'Nama lengkap maksimal :max karakter',
        'email_required' => 'Email wajib diisi',
        'email_unique' => 'Email sudah digunakan',
        'email_format' => 'Format email tidak valid',
        'phone_format' => 'Format nomor telepon tidak valid',
        'birth_date_required' => 'Tanggal lahir wajib diisi',
        'birth_date_before' => 'Tanggal lahir harus sebelum hari ini',
        'hire_date_required' => 'Tanggal masuk wajib diisi',
        'hire_date_before_or_equal' => 'Tanggal masuk tidak boleh di masa depan',
        'end_date_after' => 'Tanggal berakhir harus setelah tanggal mulai',
        'salary_numeric' => 'Gaji harus berupa angka',
        'salary_min' => 'Gaji minimal :min',
        'salary_max' => 'Gaji maksimal :max',
        'photo_image' => 'File harus berupa gambar',
        'photo_max' => 'Ukuran foto maksimal :max KB',
        'photo_dimensions' => 'Dimensi foto minimal :width x :height pixels',
        'department_required' => 'Departemen wajib dipilih',
        'position_required' => 'Posisi wajib dipilih',
        'type_required' => 'Jenis karyawan wajib dipilih',
        'status_required' => 'Status wajib dipilih',
        'contract_end_required_for_contract' => 'Tanggal berakhir kontrak wajib untuk karyawan kontrak',
        'probation_end_required_for_probation' => 'Tanggal berakhir masa percobaan wajib untuk karyawan baru',
        'termination_reason_required' => 'Alasan berhenti wajib diisi',
        'supervisor_different' => 'Atasan tidak boleh sama dengan karyawan itu sendiri',
        'supervisor_exists' => 'Atasan yang dipilih tidak valid',
    ],

    'help' => [
        'employee_id' => 'ID unik untuk setiap karyawan (contoh: EMP001, EMP002)',
        'photo_requirements' => 'Foto harus berformat JPG/PNG, maksimal 2MB, minimal 200x200 pixels',
        'face_registration' => 'Daftarkan wajah untuk fitur absensi dengan pengenalan wajah',
        'salary_calculation' => 'Gaji akan digunakan untuk perhitungan payroll otomatis',
        'probation_period' => 'Masa percobaan standar adalah 3 bulan dari tanggal masuk',
        'contract_expiry' => 'Sistem akan mengirim notifikasi sebelum kontrak berakhir',
        'bulk_import' => 'Gunakan template Excel untuk mengimpor banyak karyawan sekaligus',
        'department_assignment' => 'Karyawan dapat dipindahkan antar departemen melalui edit data',
        'status_workflow' => 'Alur status: Aktif → Ditangguhkan → Tidak Aktif → Diarsipkan',
        'face_update' => 'Perbarui data wajah jika ada perubahan signifikan pada penampilan',
    ],

    'tooltips' => [
        'view_details' => 'Lihat detail karyawan',
        'edit_employee' => 'Edit data karyawan',
        'delete_employee' => 'Hapus karyawan',
        'activate_employee' => 'Aktifkan karyawan',
        'deactivate_employee' => 'Nonaktifkan karyawan',
        'suspend_employee' => 'Tangguhkan karyawan',
        'register_face' => 'Daftarkan data wajah',
        'view_attendance' => 'Lihat riwayat absensi',
        'view_payroll' => 'Lihat data gaji',
        'send_invitation' => 'Kirim undangan email',
        'reset_password' => 'Reset kata sandi',
        'face_registered' => 'Wajah sudah terdaftar',
        'face_not_registered' => 'Wajah belum terdaftar',
        'employee_active' => 'Karyawan aktif',
        'employee_inactive' => 'Karyawan tidak aktif',
        'employee_suspended' => 'Karyawan ditangguhkan',
        'on_probation' => 'Masa percobaan',
        'contract_expiring' => 'Kontrak akan berakhir',
    ],
];
