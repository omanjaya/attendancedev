# Audit Middleware Izin (Permission)

Dokumen ini menganalisis cara kerja sistem *middleware* izin dalam aplikasi. Tujuannya adalah untuk memberikan pemahaman yang jelas tentang bagaimana rute (routes) dilindungi dan bagaimana hak akses pengguna diberlakukan.

## 1. Middleware Utama: `CheckPermission`

Middleware inti yang bertanggung jawab untuk melindungi rute berdasarkan izin adalah `App\Http\Middleware\CheckPermission.php`.

### 1.1. Cara Kerja

1.  **Menerima Parameter Izin**: Middleware ini menerima satu argumen, yaitu nama izin yang diperlukan untuk mengakses rute tersebut. Contoh: `permission:manage_users`.
2.  **Memeriksa Autentikasi**: Langkah pertama adalah memastikan pengguna telah login. Jika tidak, pengguna akan dialihkan ke halaman login.
3.  **Memuat Ulang Izin & Peran**: Untuk memastikan data yang digunakan selalu yang terbaru dan menghindari masalah akibat *caching*, middleware ini secara eksplisit memuat ulang relasi `roles` dan `permissions` milik pengguna saat ini dengan perintah `$user->load('roles.permissions')`.
4.  **Verifikasi Izin**: Proses verifikasi dilakukan dengan memanggil metode `$user->can($permission)` dari paket `spatie/laravel-permission`. Metode ini akan mengembalikan `true` jika pengguna memiliki izin tersebut, baik secara langsung maupun melalui peran (role) yang dimilikinya.
5.  **Penanganan Akses Ditolak**: Jika verifikasi gagal (`$user->can()` mengembalikan `false`), middleware akan melakukan dua hal:
    *   **Mencatat Log**: Upaya akses yang tidak sah dicatat ke dalam log aplikasi (`storage/logs/laravel.log`) dengan level `warning`. Log ini berisi informasi penting seperti ID pengguna, izin yang diminta, peran pengguna, URL, dan alamat IP. Ini sangat berguna untuk audit keamanan.
    *   **Mengembalikan Respons 403**: Aplikasi akan menghentikan proses dan mengembalikan respons `403 Forbidden`, yang menandakan bahwa akses ditolak.

### 1.2. Pendaftaran Alias

Middleware ini didaftarkan di `bootstrap/app.php` dengan alias `permission`.

```php
// bootstrap/app.php

$middleware->alias([
    'permission' => \App\Http\Middleware\CheckPermission::class,
    // ... alias lainnya
]);
```

Ini memungkinkan penggunaan yang lebih ringkas dan mudah dibaca di dalam file definisi rute.

## 2. Middleware Bawaan Spatie

Selain middleware kustom `CheckPermission`, aplikasi ini juga mendaftarkan middleware standar dari paket `spatie/laravel-permission`:

-   `'role' => \Spatie\Permission\Middleware\RoleMiddleware::class`
-   `'permission.any' => \Spatie\Permission\Middleware\PermissionMiddleware::class`
-   `'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class`

Namun, berdasarkan analisis kode dan konvensi proyek, **hanya alias `permission` (yang menunjuk ke `CheckPermission` kustom) yang secara aktif digunakan** untuk melindungi rute. Middleware kustom ini lebih disukai karena menyediakan logging keamanan tambahan yang tidak ada di middleware bawaan Spatie.

## 3. Implementasi pada Rute

Untuk melindungi sebuah rute, kita cukup menambahkan middleware `permission` diikuti dengan nama izin yang diperlukan. Ini memastikan bahwa hanya pengguna yang memiliki izin tersebut yang dapat mengakses rute.

### Contoh Penggunaan:

Misalkan kita memiliki rute untuk mengelola pengaturan sistem yang hanya boleh diakses oleh pengguna dengan izin `manage_settings`.

```php
// routes/system.php

use App\Http\Controllers\SystemSettingsController;

Route::get('/settings', [SystemSettingsController::class, 'index'])
    ->name('settings.index')
    ->middleware('permission:manage_settings');
```

Dalam contoh ini:

-   Setiap permintaan ke `/settings` akan dicegat oleh `CheckPermission`.
-   Middleware akan memeriksa apakah pengguna yang sedang login memiliki izin `manage_settings`.
-   Jika ya, permintaan akan dilanjutkan ke `SystemSettingsController@index`.
-   Jika tidak, pengguna akan melihat halaman `403 Forbidden`.

## 4. Kesimpulan Audit

Sistem izin aplikasi ini terpusat pada middleware kustom `CheckPermission` yang kuat dan aman. Penggunaannya konsisten di seluruh file rute, dan adanya logging terperinci untuk setiap kegagalan akses memberikan lapisan keamanan dan audit yang sangat baik.

**Rekomendasi:**

-   **Terus Gunakan `permission`**: Selalu gunakan alias `middleware('permission:nama_izin')` untuk semua rute baru yang memerlukan perlindungan izin. Hindari menggunakan middleware bawaan Spatie secara langsung untuk menjaga konsistensi dan memastikan semua kegagalan akses tercatat dalam log.
-   **Definisikan Izin dengan Jelas**: Pastikan semua izin yang digunakan dalam middleware didefinisikan dengan benar di `database/seeders/RolesAndPermissionsSeeder.php` untuk menghindari error.
