# Panduan Proyek untuk Gemini CLI

Ini adalah panduan khusus untuk Gemini CLI saat berinteraksi dengan proyek `attendance-system`. Tujuannya adalah untuk memastikan semua tindakan konsisten dengan praktik terbaik proyek, meminimalkan kesalahan, dan memaksimalkan efisiensi.

---

## 1. Gambaran Umum Proyek

`attendance-system` adalah aplikasi manajemen kehadiran komprehensif yang dibangun dengan Laravel (PHP) untuk *backend* dan Vue.js dengan Tailwind CSS untuk *frontend*. Sistem ini mencakup fitur-fitur seperti manajemen kehadiran, penjadwalan, cuti, penggajian, otentikasi dua faktor, dan pengenalan wajah.

## 2. Tumpukan Teknologi Utama

### 2.1. Backend (Laravel & PHP)

*   **Framework Utama:** Laravel 12.x
*   **Bahasa:** PHP 8.2+
*   **Otentikasi & Otorisasi:**
    *   Laravel Sanctum: Untuk otentikasi API berbasis token.
    *   Spatie/laravel-permission: Sistem Kontrol Akses Berbasis Peran (RBAC) yang kuat untuk mengelola peran dan izin pengguna.
    *   Pragmarx/google2fa-laravel: Integrasi Otentikasi Dua Faktor (2FA) berbasis TOTP (Time-based One-Time Password).
*   **Komunikasi Real-time:** Pusher/pusher-php-server: Untuk fungsionalitas *real-time* seperti notifikasi.
*   **Basis Data:** Doctrine/dbal: Digunakan untuk abstraksi basis data, seringkali membantu dalam operasi skema basis data.
*   **Utilitas:**
    *   Jenssegers/agent: Untuk mengurai *user agent* dan mendeteksi perangkat.
    *   Simplesoftwareio/simple-qrcode: Untuk menghasilkan kode QR (kemungkinan untuk 2FA atau fitur lainnya).
    *   Yajra/laravel-datatables-oracle: Integrasi sisi *server* untuk DataTables, memungkinkan penanganan data tabel yang besar secara efisien.
    *   Darkaonline/l5-swagger: Untuk menghasilkan dokumentasi API OpenAPI/Swagger secara otomatis.
*   **Alat Pengembangan (Dev Dependencies):**
    *   Laravel Breeze: *Scaffolding* otentikasi minimal.
    *   Laravel Pail: Alat untuk men-*tail* log aplikasi secara *real-time*.
    *   Laravel Pint: *Code style fixer* otomatis.
    *   Laravel Sail: Lingkungan pengembangan Docker untuk Laravel.
    *   PHPUnit: *Framework* pengujian unit untuk PHP.

### 2.2. Frontend (Vue.js & JavaScript)

*   **Framework Utama:** Vue.js 3
*   **Build Tool:** Vite: *Bundler* *frontend* yang cepat dan modern.
*   **Styling:** Tailwind CSS: *Framework* CSS *utility-first* untuk desain yang cepat dan konsisten.
    *   `@tailwindcss/forms` & `@tailwindcss/typography`: Plugin Tailwind untuk gaya formulir dan tipografi.
    *   `tailwindcss-animate`: Plugin untuk animasi berbasis Tailwind.
    *   **Sistem Desain Kustom:** `resources/css/design-system.css` mendefinisikan token desain dan pola komponen yang konsisten.
*   **Manajemen State:** Pinia: Solusi manajemen *state* yang direkomendasikan untuk Vue 3.
*   **Reaktivitas & Utilitas:** VueUse: Kumpulan utilitas Composition API yang luas untuk Vue.
*   **Interaktivitas Ringan:** Alpine.js: *Framework* JavaScript minimal untuk menambahkan perilaku reaktif langsung di *markup* HTML.
*   **Pengenalan Wajah:**
    *   @mediapipe/camera_utils, @mediapipe/drawing_utils, @mediapipe/face_detection, @mediapipe/face_mesh: Pustaka Google MediaPipe untuk deteksi dan pelacakan wajah.
    *   face-api.js: Pustaka JavaScript untuk deteksi wajah dan pengenalan wajah di *browser*.
*   **Charting:** Chart.js & chartjs-adapter-date-fns: Untuk visualisasi data.
*   **Tabel Data:** DataTables.net: Pustaka JavaScript untuk tabel data interaktif.
*   **Utilitas UI:**
    *   @headlessui/vue: Komponen UI tanpa gaya yang dapat diakses.
    *   @heroicons/vue: Kumpulan ikon SVG.
    *   lucide-vue-next: Kumpulan ikon lainnya.
    *   class-variance-authority & clsx & tailwind-merge: Utilitas untuk mengelola kelas CSS secara kondisional dan menggabungkan kelas Tailwind.
*   **Alat Pengembangan (Dev Dependencies):**
    *   Vitest: *Framework* pengujian unit yang cepat untuk Vue.js.
    *   @testing-library/vue & @vue/test-utils: Utilitas pengujian untuk komponen Vue.
    *   ESLint & Prettier: Alat *linting* dan pemformatan kode untuk menjaga kualitas kode JavaScript/TypeScript/Vue.
    *   TypeScript: Digunakan untuk *type-checking* di *frontend*.

## 3. Direktori dan File Kunci

*   **`app/Models/`**: Definisi model Eloquent.
*   **`app/Http/Controllers/`**: Logika *controller*.
*   **`app/Http/Requests/`**: Validasi *form request*.
*   **`app/Services/`**: Logika bisnis inti dan layanan yang dapat digunakan kembali (misalnya, `NavigationService`, `UserSecurityService`). **Prioritaskan penggunaan dan perluasan layanan yang sudah ada.**
*   **`database/migrations/`**: Skema basis data.
*   **`database/seeders/`**: Data awal dan *dummy*.
*   **`resources/views/`**: *Template* Blade Laravel.
    *   **`resources/views/layouts/authenticated-unified.blade.php`**: **Layout utama untuk semua tampilan yang diautentikasi.**
    *   **`resources/views/layouts/guest.blade.php`**: Layout untuk tampilan yang tidak diautentikasi.
    *   **`resources/views/components/`**: Komponen Blade yang dapat digunakan kembali. **Selalu gunakan komponen yang sudah ada jika memungkinkan.**
*   **`resources/css/app.css`**: CSS utama, termasuk impor Tailwind dan `design-system.css`.
*   **`resources/css/design-system.css`**: Token desain dan pola komponen kustom. **Patuhi definisi di sini untuk konsistensi visual.**
*   **`resources/js/app.ts`**: Titik masuk JavaScript/TypeScript utama.
*   **`resources/js/app-integration.js`**: Inisialisasi aplikasi Vue modular dan layanan *frontend*.
*   **`resources/js/components/`**: Komponen Vue. **Selalu gunakan komponen yang sudah ada jika memungkinkan.**
*   **`routes/`**: Definisi rute (web, api, auth, domain-spesifik).

## 4. Standar Pengkodean dan Konvensi

*   **Gaya Kode:** Proyek ini menggunakan `Laravel Pint` untuk PHP dan `ESLint`/`Prettier` untuk JavaScript/TypeScript/Vue. **Selalu jalankan alat ini setelah membuat perubahan kode.**
    *   `php artisan pint`
    *   `npm run lint -- --fix`
    *   `npm run format`
*   **Penamaan:** Ikuti konvensi penamaan yang sudah ada (misalnya, `camelCase` untuk JavaScript, `snake_case` untuk PHP, `kebab-case` untuk nama *file* Blade/Vue).
*   **Struktur:** Pertahankan struktur direktori yang ada. Jika menambahkan fungsionalitas baru, tempatkan di direktori yang paling relevan atau buat sub-direktori baru jika diperlukan, mengikuti pola yang sudah ada.
*   **Komponen:**
    *   **Blade Components:** Manfaatkan `<x-ui.*>` dan `<x-navigation.*>` yang sudah ada. Hindari menulis ulang HTML/CSS untuk elemen UI umum.
    *   **Vue Components:** Gunakan komponen Vue yang sudah ada di `resources/js/components/`.
*   **Layout:**
    *   **Semua tampilan yang diautentikasi HARUS memperluas `resources/views/layouts/authenticated-unified.blade.php`.**
    *   Tampilan yang tidak diautentikasi HARUS memperluas `resources/views/layouts/guest.blade.php`.
*   **CSS:** Gunakan kelas Tailwind CSS. Untuk gaya kustom, tambahkan ke `resources/css/app.css` atau `resources/css/design-system.css` menggunakan `@layer` yang sesuai.

## 5. Alur Kerja Pengembangan

*   **Menjalankan Server Pengembangan:** `npm run dev` (akan menjalankan server Laravel dan Vite secara bersamaan).
*   **Menjalankan Pengujian:**
    *   Backend: `php artisan test`
    *   Frontend: `npm run test`
*   **Menyiapkan Basis Data (Hati-hati!):**
    *   Untuk membersihkan dan mengisi ulang basis data dengan data *dummy*: `php artisan migrate:fresh --seed`
    *   **PERINGATAN:** Perintah ini akan **menghapus semua data yang ada** di basis data Anda. Gunakan dengan hati-hati.
*   **Membersihkan Cache:** Setelah perubahan konfigurasi atau *view*:
    *   `php artisan config:clear`
    *   `php artisan cache:clear`
    *   `php artisan view:clear`
    *   `php artisan route:clear`

## 6. Informasi Spesifik Proyek

*   **Kredensial Admin Default:** `admin@attendance.com` dengan kata sandi `password123` (setelah menjalankan `php artisan migrate:fresh --seed`).
*   **Logika Keamanan:** Logika 2FA, penguncian akun, dan pelacakan perangkat diimplementasikan di `app/Services/UserSecurityService.php`.
*   **Navigasi Dinamis:** Menu navigasi dibuat secara dinamis berdasarkan peran pengguna melalui `app/Services/NavigationService.php`.

## 7. Pedoman Umum untuk Gemini

*   **Baca File Terlebih Dahulu:** Jangan pernah berasumsi tentang konten *file*. Selalu gunakan `read_file` atau `read_many_files` untuk memverifikasi.
*   **Prioritaskan Pola yang Ada:** Sebelum memperkenalkan pola atau pustaka baru, periksa apakah ada cara yang sudah ada untuk mencapai tujuan yang sama.
*   **Jelaskan Perintah Destruktif:** Sebelum menjalankan perintah *shell* yang memodifikasi sistem *file* atau basis data secara signifikan (misalnya, `rm -rf`, `migrate:fresh`), jelaskan dampaknya kepada pengguna.
*   **Konfirmasi Ambiguitas:** Jika permintaan pengguna tidak jelas atau memiliki banyak interpretasi, mintalah klarifikasi.
*   **Fokus pada Permintaan Pengguna:** Tetap pada tugas yang diminta.
*   **Komentar Minimal:** Tambahkan komentar kode hanya untuk menjelaskan *mengapa* sesuatu dilakukan, bukan *apa* yang dilakukan.
*   **Koreksi Diri:** Jika perubahan menyebabkan masalah, segera batalkan dan evaluasi kembali pendekatan.
*   **Hindari Spekulasi:** Berikan analisis dan solusi yang jelas dan pasti. Jangan gunakan kata-kata yang mengambang seperti 'mungkin' atau 'kemungkinan besar'.
*   **Fokus pada Revisi:** Prioritaskan untuk merevisi atau memperbaiki kode dan konfigurasi yang sudah ada. Jangan menyarankan penambahan pustaka, alat, atau fitur baru kecuali diminta secara eksplisit oleh pengguna.


## 8. Pedoman UI/UX Design System

**🚨 PENTING: SELALU PATUHI PANDUAN INI UNTUK MEMINIMALKAN KESALAHAN DAN INCONSISTENCY**

### 8.1. Prinsip Desain Utama

*   **Konsistensi:** Semua elemen UI harus mengikuti sistem desain yang sudah ditetapkan di `design-system.css`
*   **Aksesibilitas:** Semua komponen harus dapat diakses dengan keyboard dan screen reader
*   **Responsif:** Layout harus bekerja optimal di semua ukuran layar (mobile-first approach)
*   **Simetri:** Elemen dashboard dan layout harus simetris dan seimbang secara visual
*   **Hierarki Visual:** Gunakan tipografi, warna, dan spacing yang konsisten untuk menciptakan hierarki informasi yang jelas

### 8.1.1. Best Practices Implementasi

*   **SELALU GUNAKAN KOMPONEN YANG SUDAH ADA** - Cari dulu di `resources/views/components/` dan `resources/js/components/`
*   **JANGAN BUAT FILE BARU** tanpa mencari file yang sudah ada terlebih dahulu
*   **IKUTI POLA YANG SUDAH DITETAPKAN** - Lihat implementasi serupa yang sudah ada
*   **PRIORITASKAN REVISI** file yang sudah ada daripada membuat yang baru

### 8.2. Grid System & Layout

*   **Grid Simetris:** Gunakan grid yang simetris untuk dashboard dan listing
    *   Desktop: `grid-cols-1 md:grid-cols-2 xl:grid-cols-4` untuk stats cards
    *   Mobile: Selalu gunakan `grid-cols-1` sebagai base dengan responsive breakpoints
    *   Tablet: `md:grid-cols-2` untuk balance antara mobile dan desktop
*   **Spacing Konsisten:** Gunakan spacing Tailwind yang konsisten (gap-6, p-6, m-6)
*   **Container:** Semua konten utama harus menggunakan container yang tepat dengan padding yang konsisten

### 8.3. Color Palette & Semantics

*   **Primary Colors:** Gunakan variabel CSS yang sudah didefinisikan di `design-system.css`
*   **Semantic Colors:**
    *   Success (green): untuk status berhasil, hadir, aktif
    *   Warning (yellow): untuk terlambat, pending, perhatian
    *   Destructive (red): untuk error, absent, bahaya
    *   Info (blue): untuk informasi umum
    *   Muted (gray): untuk secondary content
*   **Background Hierarchy:**
    *   `bg-background`: Background utama
    *   `bg-card`: Background cards dan panels
    *   `bg-muted/30`: Background untuk highlighted content
    *   `bg-accent`: Background untuk accent areas

### 8.4. Typography

*   **Hierarki Text:**
    *   H1: Page titles - `text-2xl font-bold`
    *   H2: Section titles - `text-xl font-semibold`
    *   H3: Card titles - `text-lg font-medium`
    *   Body: Regular text - `text-sm font-normal`
    *   Caption: Meta text - `text-xs text-muted-foreground`
*   **Konsistensi Font:** Selalu gunakan font stack yang sudah didefinisikan
*   **Line Height:** Pastikan line-height yang readable untuk semua ukuran text

### 8.5. Component Standards

**🎯 KOMPONEN YANG SUDAH TERSEDIA DAN HARUS DIGUNAKAN:**

*   **Cards:** Gunakan `<x-ui.card>` dengan variant yang tepat
    *   `variant="metric"`: untuk statistical cards
    *   `variant="default"`: untuk content cards
*   **Buttons:** Gunakan `<x-ui.button>` dengan variant yang konsisten
    *   `variant="default"`: untuk primary actions
    *   `variant="outline"`: untuk secondary actions
    *   `variant="ghost"`: untuk tertiary actions
*   **Badges:** Gunakan `<x-ui.badge>` untuk status indicators
*   **Progress Bars:** Gunakan `<x-ui.progress>` untuk metrics dan loading states
*   **Forms:** Gunakan `<x-ui.label>`, `<x-ui.input>`, `<x-ui.select>`, `<x-ui.textarea>`
*   **Tables:** Gunakan `<x-ui.table>` dengan responsive behavior
*   **Navigation:** Gunakan `<x-navigation.unified-nav>` dan `<x-navigation.nav-item>`
*   **Empty States:** Gunakan `<x-ui.empty-state>` untuk data kosong
*   **Loading States:** Gunakan `<x-ui.skeleton>` dan `<x-ui.spinner>`

**📁 LOKASI KOMPONEN:**
*   Blade Components: `resources/views/components/`
*   Vue Components: `resources/js/components/`
*   CSS Classes: `resources/css/design-system.css`

### 8.6. Icon Guidelines

*   **Consistency:** Gunakan Heroicons atau Lucide sebagai icon library utama
*   **Size Standards:**
    *   Small icons: `w-4 h-4` untuk button icons
    *   Medium icons: `w-6 h-6` untuk card icons
    *   Large icons: `w-8 h-8` untuk empty states
*   **Semantic Usage:** Icons harus memiliki makna yang jelas dan konsisten across aplikasi

### 8.7. Dashboard Design Rules

**🎯 TEMPLATE DASHBOARD YANG SUDAH ADA:**
*   Super Admin Dashboard: `resources/views/pages/dashboard/super-admin.blade.php`
*   Admin Dashboard: `resources/views/pages/dashboard/admin.blade.php`
*   Manager Dashboard: `resources/views/pages/dashboard/manager.blade.php`
*   Employee Dashboard: `resources/views/pages/dashboard/employee.blade.php`

**📐 LAYOUT STANDARDS:**
*   **Symmetrical Layouts:** Dashboard harus menggunakan grid yang simetris
    *   Primary stats: 4-column grid untuk metrik utama (`grid-cols-1 md:grid-cols-2 xl:grid-cols-4`)
    *   Secondary stats: 4-column grid untuk metrik tambahan
    *   Content areas: 2-3 column grid untuk content sections
*   **Visual Balance:** Pastikan visual weight terdistribusi merata
*   **Information Hierarchy:** Informasi penting di atas, detail di bawah
*   **Quick Actions:** Aksi cepat harus mudah diakses dan grouped logically
*   **Full Dashboard Content:** Dashboard harus berisi content yang lengkap dan informatif, bukan hanya placeholder

### 8.8. Responsive Design Standards

*   **Mobile First:** Mulai dengan design mobile kemudian scale up
*   **Breakpoints:**
    *   `sm:` 640px+ (mobile landscape)
    *   `md:` 768px+ (tablet)
    *   `lg:` 1024px+ (desktop)
    *   `xl:` 1280px+ (large desktop)
    *   `2xl:` 1536px+ (extra large)
*   **Touch Targets:** Minimum 44px untuk touch targets di mobile
*   **Navigation:** 
    *   Mobile: Bottom navigation atau hamburger menu
    *   Desktop: Sidebar navigation

### 8.9. Animation & Transitions

*   **Consistent Timing:** Gunakan duration yang konsisten (150ms, 300ms)
*   **Easing:** Gunakan easing yang natural (`ease-in-out`)
*   **Purpose:** Animasi harus memiliki tujuan UX yang jelas
*   **Performance:** Hindari animasi yang berat untuk mobile devices

### 8.10. Accessibility (A11Y) Requirements

*   **Keyboard Navigation:** Semua interaktable elements harus dapat diakses dengan keyboard
*   **Screen Reader:** Gunakan proper ARIA labels dan semantic HTML
*   **Color Contrast:** Pastikan contrast ratio minimum 4.5:1 untuk text
*   **Focus States:** Semua interactive elements harus memiliki visible focus states
*   **Alt Text:** Semua images harus memiliki alt text yang descriptive

### 8.11. Form Design Standards

*   **Layout:** Forms harus menggunakan consistent spacing dan alignment
*   **Validation:** Error states harus jelas dan helpful
*   **Labels:** Labels harus descriptive dan properly associated
*   **Input States:** Hover, focus, error, disabled states harus konsisten
*   **Success Feedback:** Feedback positif untuk form submissions yang berhasil

### 8.12. Data Visualization Guidelines

*   **Charts:** Gunakan Chart.js dengan color palette yang konsisten
*   **Tables:** Gunakan component table yang sudah ada dengan responsive behavior
*   **Empty States:** Gunakan `<x-ui.empty-state>` untuk data kosong
*   **Loading States:** Implementasi loading states yang smooth
*   **Data Density:** Balance antara information density dan readability

### 8.13. Error Handling & Feedback

*   **Error Messages:** Jelas, actionable, dan tidak menakutkan
*   **Success Messages:** Celebratory tapi tidak intrusive  
*   **Loading States:** Memberikan feedback untuk long-running operations
*   **Empty States:** Helpful dan actionable untuk state kosong
*   **Confirmation:** Confirmation untuk destructive actions

### 8.14. Performance & Optimization

*   **Image Optimization:** Gunakan format yang tepat dan lazy loading
*   **Bundle Size:** Minimize JavaScript bundle size
*   **Critical CSS:** Inline critical CSS untuk faster rendering
*   **Caching:** Leverage browser caching untuk assets
*   **Lazy Loading:** Implement lazy loading untuk content yang tidak immediately visible

## 9. Workflow Implementasi UI/UX

### 9.1. Sebelum Membuat Komponen/View Baru

**🔍 ANALISIS REQUIREMENTS:**
1. **Identifikasi komponen yang sudah ada** - Periksa `resources/views/components/` untuk komponen yang bisa digunakan kembali
2. **Gunakan dashboard sebagai base template** - Semua page content harus mengikuti pattern dari dashboard
3. **Cek design system yang ada** - Review `resources/css/design-system.css` untuk class utilities
4. **Periksa Vue.js components** - Cek `resources/js/components/` untuk komponen Vue yang relevan

### 9.2. Template Page Content Pattern

**📋 MANDATORY BASE TEMPLATE:**
Semua page content HARUS menggunakan pattern dashboard sebagai base template:

```blade
@extends('layouts.authenticated-unified')

@section('title', 'Page Title')

@section('page-content')
<!-- Background Container -->
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
    <div class="p-6 lg:p-8">
        <!-- Header Section - MANDATORY -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Page Title</h1>
                    <p class="text-gray-600 dark:text-gray-300 mt-2">Page description</p>
                </div>
                <div class="flex items-center space-x-3">
                    <!-- Action buttons -->
                </div>
            </div>
        </div>

        <!-- Statistics Cards Section - RECOMMENDED -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
            <!-- Stats cards following dashboard pattern -->
        </div>

        <!-- Main Content Section -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <!-- Content goes here -->
        </div>
    </div>
</div>
@endsection
```

**🎨 DESIGN ELEMENTS - DASHBOARD PATTERN:**
- **Background**: `bg-gray-50 dark:bg-gray-900` sebagai base background
- **Cards**: `bg-white dark:bg-gray-800` dengan `rounded-xl shadow-sm border`
- **Headers**: `text-3xl font-bold text-gray-900 dark:text-white`
- **Typography**: Menggunakan gray scale untuk hierarchy
- **Spacing**: `p-6 lg:p-8` untuk container, `mb-8` untuk section spacing
- **Grid**: `grid-cols-1 md:grid-cols-2 xl:grid-cols-4` untuk stats

**❌ AVOID PATTERNS:**
- Glassmorphism effects (`backdrop-blur`, `bg-white/20`)
- Gradient backgrounds (`bg-gradient-to-br`)
- Complex shadow effects (`shadow-lg hover:shadow-xl`)
- Scale transforms (`hover:scale-105`)
- Glass containers (`bg-white/30 backdrop-blur-sm`)

### 9.3. Component Integration Pattern

**🔗 KOMPONEN YANG HARUS DIGUNAKAN:**
1. **Navigation**: `<x-navigation.unified-nav>` - sudah terintegrasi dalam layout
2. **Buttons**: `<x-ui.button>` dengan variant dashboard-style
3. **Cards**: `<x-ui.card>` dengan pattern dashboard
4. **Forms**: `<x-forms.input>`, `<x-forms.select>` yang konsisten
5. **Tables**: `<x-ui.data-table>` dengan design dashboard
6. **Icons**: `<x-ui.icon>` atau IconService untuk consistency

**📊 DATA VISUALIZATION:**
- Charts: Gunakan pattern dari dashboard dengan Chart.js
- Colors: Stick dengan gray/blue palette dari dashboard
- Cards: Follow exact stat card pattern dari dashboard

### 9.4. Page Layout Standards

**📐 LAYOUT HIERARCHY:**
1. **Header Section** (h1 + description + actions)
2. **Statistics Section** (grid cards - optional tapi recommended)
3. **Main Content** (single card container)
4. **Footer/Actions** (jika diperlukan)

**🎯 SPECIFIC REQUIREMENTS:**
- Semua page HARUS menggunakan `layouts.authenticated-unified`
- Header section WAJIB ada dengan format yang sama seperti dashboard
- Main content HARUS dalam white/dark card container
- Typography hierarchy HARUS konsisten dengan dashboard
- Color scheme HARUS mengikuti dashboard (gray-based, bukan blue gradients)

### 9.5. Vue.js Integration Pattern

**⚛️ VUE COMPONENT MOUNTING:**
Jika memerlukan Vue.js components:

```blade
@push('scripts')
<script>
// Mount Vue component dengan pattern yang konsisten
if (document.getElementById('vue-app')) {
    const { createApp } = Vue;
    const app = createApp({
        // Component logic
    });
    app.mount('#vue-app');
}
</script>
@endpush
```

**📋 COMPONENT NAMING:**
- File: `PascalCase.vue` (e.g., `EmployeeTable.vue`)
- Props: `camelCase`
- Events: `kebab-case`

**🔍 CHECKLIST WAJIB:**
1. **Cari komponen yang sudah ada** di `resources/views/components/`
2. **Periksa implementasi serupa** di views yang sudah ada
3. **Gunakan pattern yang sudah ditetapkan** untuk layout dan styling
4. **Verifikasi design system** di `design-system.css`
5. **Follow dashboard pattern** - MANDATORY untuk semua page content

### 9.6. Dashboard Pattern Compliance

**🏠 BASE TEMPLATE REFERENCE:**
Semua halaman HARUS menggunakan `/resources/views/pages/dashboard/super-admin.blade.php` sebagai pattern reference.

**🎯 MANDATORY ELEMENTS:**
- Header dengan title dan description
- Statistics cards section (4-column grid)
- Main content dalam white card container
- Consistent spacing dan typography
- Gray-based color scheme (NO gradients/glassmorphism)

### 9.2. Saat Revisi/Update UI

**✅ PRIORITAS KERJA:**
1. **Gunakan komponen yang sudah ada** dan sesuaikan variant/props
2. **Ikuti pattern grid dan spacing** yang sudah ditetapkan
3. **Pastikan responsiveness** dengan breakpoints yang konsisten
4. **Verifikasi aksesibilitas** dengan proper ARIA labels

### 9.3. Struktur File yang Sudah Ada

**📂 LAYOUT UTAMA:**
*   `resources/views/layouts/authenticated-unified.blade.php` - Layout utama untuk semua halaman authenticated
*   `resources/views/layouts/guest.blade.php` - Layout untuk halaman guest

**🧩 KOMPONEN BLADE:**
*   `resources/views/components/ui/` - Komponen UI dasar (card, button, badge, dll)
*   `resources/views/components/navigation/` - Komponen navigasi
*   `resources/views/components/forms/` - Komponen form

**🎨 STYLING:**
*   `resources/css/design-system.css` - Token design dan utility classes
*   `resources/css/app.css` - CSS utama dengan Tailwind imports

### 9.4. Best Practices Implementasi

**🚀 DO:**
*   Gunakan komponen yang sudah ada dengan variant yang tepat
*   Ikuti pattern responsive yang sudah ditetapkan
*   Pastikan simetri dan visual balance
*   Gunakan semantic HTML dan proper accessibility
*   Follow naming convention yang konsisten

**❌ DON'T:**
*   Buat komponen baru tanpa mencari yang sudah ada
*   Hardcode styling tanpa menggunakan design system
*   Melanggar pattern responsive yang sudah ditetapkan
*   Menggunakan inline styles untuk hal-hal yang sudah ada di design system
*   Membuat file baru tanpa keperluan yang jelas

### 9.5. Validation Checklist

**📋 SEBELUM COMMIT:**
- [ ] Komponen/view mengikuti design system yang sudah ada
- [ ] Responsive di semua breakpoints (mobile, tablet, desktop)
- [ ] Aksesibilitas terjaga (keyboard navigation, screen reader)
- [ ] Consistency dengan pattern yang sudah ada
- [ ] Visual balance dan simetri terjaga
- [ ] Loading states dan error handling implemented
- [ ] Performance optimal (lazy loading, optimized images)

---

## 10. Implementasi Dashboard-Based Design untuk Semua Halaman

### 10.1. Migrasi dari Glassmorphism ke Dashboard Pattern

**🎯 TRANSISI DESIGN PHILOSOPHY:**
Semua halaman dalam sistem harus mengikuti dashboard pattern yang sudah di-establish, bukan glassmorphism approach.

**🎨 DASHBOARD PATTERN CHARACTERISTICS:**
- **Clean Background**: `bg-gray-50 dark:bg-gray-900` solid backgrounds
- **Card-based Layout**: `bg-white dark:bg-gray-800` dengan `border border-gray-200 dark:border-gray-700`
- **Simple Shadows**: `shadow-sm` untuk subtle depth, tidak berlebihan
- **Consistent Typography**: Gray-based color hierarchy untuk text
- **Minimal Effects**: Menghindari glassmorphism, gradients, dan complex animations

### 10.2. Template Konversi Pattern

**📐 CONVERT FROM GLASSMORPHISM TO DASHBOARD:**

**❌ OLD PATTERN (Glassmorphism):**
```blade
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
        <!-- Content -->
    </div>
</div>
```

**✅ NEW PATTERN (Dashboard):**
```blade
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <!-- Content -->
    </div>
</div>
```

### 10.3. Specific Conversion Guidelines

**🔄 ELEMENT CONVERSIONS:**

| Glassmorphism Element | Dashboard Equivalent |
|----------------------|---------------------|
| `bg-white/20 backdrop-blur-sm` | `bg-white dark:bg-gray-800` |
| `border-white/30` | `border-gray-200 dark:border-gray-700` |
| `text-slate-800 dark:text-white` | `text-gray-900 dark:text-white` |
| `hover:scale-105` | Remove or `hover:bg-gray-50` |
| `shadow-lg hover:shadow-xl` | `shadow-sm` |
| `bg-gradient-to-br from-blue-500 to-purple-600` | `bg-blue-600 hover:bg-blue-700` |

### 10.4. Page Conversion Checklist

**📋 LANGKAH KONVERSI:**
1. **Background**: Ganti gradient backgrounds dengan solid `bg-gray-50 dark:bg-gray-900`
2. **Cards**: Ganti glassmorphism cards dengan `bg-white dark:bg-gray-800` + border
3. **Typography**: Standardize dengan gray-based hierarchy
4. **Buttons**: Gunakan solid colors, bukan gradients
5. **Animations**: Simplify hover effects, remove scale transforms
6. **Shadows**: Gunakan `shadow-sm` sebagai standar
        <div class="relative">
            <div class="absolute inset-0 bg-white/30 dark:bg-gray-800/30 backdrop-filter backdrop-blur-lg rounded-2xl border border-white/20 dark:border-gray-700/50"></div>
            <ol class="relative flex items-center px-6 py-4 space-x-3">
                <!-- Breadcrumb items -->
            </ol>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="w-full px-4 sm:px-6 lg:px-8 space-y-8">
        <!-- Content sections -->
    </div>
</x-layouts.page-base>
```

### 10.2. Statistics Cards Pattern (WAJIB DIGUNAKAN)

**📊 TEMPLATE STATISTICS CARDS:**
```html
<!-- Gunakan pattern EXACT ini untuk semua statistics cards -->
<div class="w-full grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="w-full group relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/30 dark:to-emerald-800/20 p-4 transition-all duration-300 hover:scale-105 hover:shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">[Title]</p>
                <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">[Value]</p>
                <div class="flex items-center mt-1">
                    <span class="text-xs text-emerald-600 dark:text-emerald-400">[Description]</span>
                </div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-500 flex items-center justify-center group-hover:rotate-12 transition-transform duration-300">
                <!-- Icon SVG -->
            </div>
        </div>
    </div>
</div>
```

**🎨 COLOR VARIANTS:**
- **Emerald**: Success, hadir, positif (`from-emerald-50 to-emerald-100`)
- **Amber**: Warning, terlambat, pending (`from-amber-50 to-amber-100`)
- **Blue**: Info, masih bekerja, neutral (`from-blue-50 to-blue-100`)
- **Purple**: Additional, total, overview (`from-purple-50 to-purple-100`)

### 10.3. Glass Cards Component (WAJIB DIGUNAKAN)

**🪟 TEMPLATE GLASS CARDS:**
```html
<!-- Untuk konten utama SELALU gunakan pattern ini -->
<x-layouts.glass-card class="p-8">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8 space-y-4 sm:space-y-0">
        <div class="flex-1">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 flex items-center mb-2">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-500 mr-4">
                    <!-- Icon SVG -->
                </div>
                [Title]
            </h3>
            <p class="text-gray-600 dark:text-gray-400 ml-14">[Description]</p>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-3 sm:space-y-0 sm:space-x-3">
            <!-- Action buttons -->
        </div>
    </div>
    
    <!-- Content -->
</x-layouts.glass-card>
```

### 10.4. Action Cards Pattern (WAJIB DIGUNAKAN)

**🚀 TEMPLATE ACTION CARDS:**
```html
<!-- Untuk quick actions dan menu items -->
<a href="#" class="group relative block rounded-2xl bg-white/60 dark:bg-gray-800/60 backdrop-filter backdrop-blur-xl border border-gray-200/50 dark:border-gray-700/50 p-6 transition-all duration-300 hover:scale-[1.02] hover:shadow-xl hover:bg-white/80 dark:hover:bg-gray-800/80">
    <div class="flex items-center justify-between">
        <div class="flex items-center flex-1">
            <div class="flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 shadow-lg group-hover:shadow-emerald-500/25 transition-all duration-300">
                <!-- Icon SVG -->
            </div>
            <div class="ml-5">
                <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-1">[Title]</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">[Description]</p>
            </div>
        </div>
        <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 group-hover:bg-emerald-200 dark:group-hover:bg-emerald-800/50 transition-colors duration-300">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </div>
    </div>
</a>
```

### 10.5. Button Styles (WAJIB DIGUNAKAN)

**🔘 BUTTON TEMPLATES:**
```html
<!-- Primary Button -->
<button class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 rounded-xl transition-all duration-300 hover:scale-105 hover:shadow-lg">
    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <!-- Icon -->
    </svg>
    [Button Text]
</button>

<!-- Secondary Button -->
<button class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white/70 dark:bg-gray-700/70 backdrop-filter backdrop-blur-sm border border-gray-300 dark:border-gray-600 hover:bg-white dark:hover:bg-gray-600 rounded-xl transition-all duration-300 hover:scale-105 hover:shadow-lg">
    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <!-- Icon -->
    </svg>
    [Button Text]
</button>
```

### 10.6. Table Design Pattern (WAJIB DIGUNAKAN)

**📋 TEMPLATE TABLE:**
```html
<div class="w-full overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
    <table class="min-w-full w-full">
        <thead>
            <tr class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700">
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <!-- Icon -->
                        </svg>
                        <span>[Column Name]</span>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                <!-- Table cells -->
            </tr>
        </tbody>
    </table>
</div>
```

### 10.7. Responsive Grid System (WAJIB DIGUNAKAN)

**📱 RESPONSIVE BREAKPOINTS:**
- **Statistics Cards**: `grid-cols-1 sm:grid-cols-2 lg:grid-cols-4`
- **Content Layout**: `grid-cols-1 xl:grid-cols-4` (3 kolom content + 1 kolom sidebar)
- **Form Layout**: `grid-cols-1 md:grid-cols-2`
- **Dashboard Sections**: `space-y-8` untuk main sections, `space-y-6` untuk cards

### 10.8. Animation & Transitions (WAJIB DIGUNAKAN)

**🎭 STANDARD ANIMATIONS:**
```css
/* Hover Effects */
.hover-scale { transition-all duration-300 hover:scale-105; }
.hover-shadow { hover:shadow-lg; }
.hover-rotate { group-hover:rotate-12 transition-transform duration-300; }
.hover-translate { group-hover:translate-x-1 transition-transform duration-300; }

/* Micro-interactions */
.hover-slight { hover:scale-[1.02]; }
.backdrop-blur { backdrop-filter backdrop-blur-xl; }
```

### 10.9. Instructions for AI Assistant (MANDATORY)

**🤖 SAAT MEMBUAT HALAMAN BARU:**
1. **WAJIB** gunakan page structure template dari section 10.1
2. **WAJIB** implement glassmorphism breadcrumb navigation
3. **WAJIB** gunakan semantic color system (emerald, amber, blue, purple)
4. **WAJIB** include hover effects dengan `hover:scale-105` dan `transition-all duration-300`
5. **WAJIB** ensure dark mode compatibility dengan proper dark: classes

**🎨 SAAT MEMBUAT KOMPONEN:**
1. **WAJIB** gunakan glass-card component untuk main content
2. **WAJIB** implement consistent icon + title pattern
3. **WAJIB** gunakan gradient backgrounds untuk visual depth
4. **WAJIB** include loading states dengan skeleton atau spinner
5. **WAJIB** follow responsive design rules dengan breakpoints yang tepat

**⚙️ SAAT STYLING ELEMENTS:**
1. **WAJIB** gunakan `rounded-xl` atau `rounded-2xl` untuk consistency
2. **WAJIB** implement `hover:scale-105` untuk interactive elements
3. **WAJIB** gunakan `backdrop-blur-lg` untuk glassmorphism effect
4. **WAJIB** maintain consistent spacing dengan `space-y-8` dan `space-y-6`
5. **WAJIB** gunakan `transition-all duration-300` untuk smooth animations

**🎯 COLOR USAGE GUIDELINES:**
- **Emerald**: Success, positive actions, primary buttons, hadir
- **Amber**: Warnings, pending states, caution actions, terlambat
- **Blue**: Information, secondary actions, neutral states, working
- **Purple**: Additional features, special functions, total overview
- **Red**: Errors, destructive actions, danger states, absent

**❌ FORBIDDEN PRACTICES:**
- Tidak menggunakan arbitrary colors
- Tidak menggunakan sharp corners (`rounded-none`)
- Tidak menggunakan harsh transitions
- Tidak menggunakan inline styles untuk styling yang sudah ada di design system
- Tidak membuat komponen baru tanpa mencari yang sudah ada

**✅ MANDATORY PRACTICES:**
- Selalu gunakan Tailwind CSS classes
- Selalu implement dark mode support
- Selalu gunakan semantic HTML
- Selalu include accessibility attributes
- Selalu test responsiveness di semua breakpoints

**🔧 CONSISTENCY REQUIREMENTS:**
- Semua halaman HARUS menggunakan pattern yang sama seperti halaman `/attendance`
- Semua statistics cards HARUS menggunakan template yang sama
- Semua action cards HARUS menggunakan glassmorphism dengan backdrop-blur
- Semua buttons HARUS menggunakan gradient backgrounds dan hover effects
- Semua tables HARUS menggunakan enhanced design dengan icons di header

---

## 11. BREADCRUMB, SIDEBAR & HEADER REDESIGN - WARNA HIJAU ELEGANT

**🚨 KRITICAL ISSUE: BREADCRUMB TERLALU BESAR & INCONSISTENT DESIGN**

### 11.1. Breadcrumb Compact Design (WAJIB DIPERBAIKI)

**❌ MASALAH YANG HARUS DISELESAIKAN:**
- Breadcrumb terlalu besar dan tidak proporsional
- Spacing berlebihan yang mengganggu layout
- Tidak konsisten dengan design system

**✅ SOLUSI BREADCRUMB COMPACT:**
```html
<!-- Breadcrumb Compact dengan Glassmorphism -->
<nav class="mb-4" aria-label="Breadcrumb">
    <div class="relative">
        <div class="absolute inset-0 bg-white/10 dark:bg-gray-800/10 backdrop-blur-sm rounded-lg border border-white/20 dark:border-gray-700/30"></div>
        <ol class="relative flex items-center px-3 py-2 space-x-2 text-sm">
            <li class="flex items-center">
                <a href="{{ route('dashboard') }}" class="flex items-center text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                    </svg>
                    <span class="font-medium">Dashboard</span>
                </a>
            </li>
            <li class="flex items-center">
                <svg class="w-3 h-3 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="ml-2 text-gray-600 dark:text-gray-300 font-medium">[Current Page]</span>
            </li>
        </ol>
    </div>
</nav>
```

**📏 COMPACT SPECIFICATIONS:**
- Height: `py-2` (bukan `py-4` atau `py-6`)
- Font size: `text-sm` (bukan `text-base`)
- Padding: `px-3` (bukan `px-6`)
- Margin: `mb-4` (bukan `mb-8`)
- Icon size: `w-4 h-4` (bukan `w-6 h-6`)
- Separator: `w-3 h-3` (bukan `w-4 h-4`)

### 11.2. Sidebar Design dengan Warna Hijau Elegant

**🌿 DOMINANT GREEN PALETTE SYSTEM:**
```css
/* Primary Green Gradients */
.sidebar-bg { background: linear-gradient(to bottom, #ecfdf5, #f0fdf4, #f0fdfa); }
.sidebar-bg-dark { background: linear-gradient(to bottom, #064e3b, #065f46, #134e4a); }

/* Card & Component Backgrounds */
.card-glass { background: rgba(255, 255, 255, 0.3); }
.card-glass-dark { background: rgba(16, 185, 129, 0.1); }

/* Text Colors */
.text-primary { color: #065f46; } /* dark:text-emerald-100 */
.text-secondary { color: #047857; } /* dark:text-emerald-200 */
.text-muted { color: #059669; } /* dark:text-emerald-400 */
```

**🎨 SIDEBAR TEMPLATE:**
```html
<!-- Enhanced Sidebar dengan Dominant Green -->
<aside class="fixed left-0 top-0 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0" aria-label="Sidebar">
    <div class="h-full px-3 py-4 overflow-y-auto bg-gradient-to-b from-emerald-50 via-green-50 to-teal-50 dark:from-emerald-900 dark:via-green-900 dark:to-teal-900 border-r border-emerald-200/50 dark:border-emerald-700/50 backdrop-blur-lg">
        
        <!-- Logo & Brand - Compact -->
        <div class="flex items-center p-3 mb-4 bg-white/30 dark:bg-emerald-800/30 backdrop-blur-sm rounded-lg border border-emerald-200/40 dark:border-emerald-700/40">
            <div class="w-8 h-8 bg-gradient-to-br from-emerald-500 to-green-600 rounded-lg flex items-center justify-center shadow-sm">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-base font-bold text-emerald-800 dark:text-emerald-100">Attendance</p>
                <p class="text-xs text-emerald-600 dark:text-emerald-400">Management System</p>
            </div>
        </div>

        <!-- Navigation Menu - Compact -->
        <nav class="space-y-2">
            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}" class="group flex items-center w-full p-3 text-emerald-700 dark:text-emerald-200 bg-white/20 dark:bg-emerald-800/20 backdrop-blur-sm rounded-lg border border-emerald-200/30 dark:border-emerald-700/30 hover:bg-emerald-100/50 dark:hover:bg-emerald-700/30 hover:scale-[1.02] transition-all duration-300 shadow-sm hover:shadow-md">
                <div class="w-8 h-8 bg-gradient-to-br from-emerald-400 to-green-500 rounded-lg flex items-center justify-center shadow-sm group-hover:rotate-3 transition-transform duration-300">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                    </svg>
                </div>
                <span class="ml-3 font-medium">Dashboard</span>
            </a>

            <!-- Attendance -->
            <a href="{{ route('attendance.index') }}" class="group flex items-center w-full p-3 text-emerald-700 dark:text-emerald-200 bg-white/20 dark:bg-emerald-800/20 backdrop-blur-sm rounded-lg border border-emerald-200/30 dark:border-emerald-700/30 hover:bg-emerald-100/50 dark:hover:bg-emerald-700/30 hover:scale-[1.02] transition-all duration-300 shadow-sm hover:shadow-md">
                <div class="w-8 h-8 bg-gradient-to-br from-emerald-400 to-green-500 rounded-lg flex items-center justify-center shadow-sm group-hover:rotate-3 transition-transform duration-300">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="ml-3 font-medium">Attendance</span>
            </a>

            <!-- Employees -->
            <a href="{{ route('employees.index') }}" class="group flex items-center w-full p-3 text-emerald-700 dark:text-emerald-200 bg-white/20 dark:bg-emerald-800/20 backdrop-blur-sm rounded-lg border border-emerald-200/30 dark:border-emerald-700/30 hover:bg-emerald-100/50 dark:hover:bg-emerald-700/30 hover:scale-[1.02] transition-all duration-300 shadow-sm hover:shadow-md">
                <div class="w-8 h-8 bg-gradient-to-br from-emerald-400 to-green-500 rounded-lg flex items-center justify-center shadow-sm group-hover:rotate-3 transition-transform duration-300">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <span class="ml-3 font-medium">Employees</span>
            </a>
        </nav>

        <!-- User Profile Section - Compact -->
        <div class="absolute bottom-4 left-3 right-3">
            <div class="p-3 bg-white/30 dark:bg-emerald-800/30 backdrop-blur-sm rounded-lg border border-emerald-200/40 dark:border-emerald-700/40">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-gradient-to-br from-emerald-500 to-green-600 rounded-full flex items-center justify-center shadow-sm">
                        <span class="text-white font-semibold text-xs">{{ substr(auth()->user()->name, 0, 2) }}</span>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-emerald-800 dark:text-emerald-100">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-emerald-600 dark:text-emerald-400">{{ auth()->user()->roles->first()->name ?? 'User' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</aside>
```

### 11.3. Header Design dengan Warna Hijau Elegant

**🎯 HEADER TEMPLATE:**
```html
<!-- Enhanced Header dengan Dominant Green -->
<header class="fixed top-0 left-0 right-0 z-30 bg-white/80 dark:bg-emerald-900/80 backdrop-blur-lg border-b border-emerald-200/50 dark:border-emerald-700/50 sm:ml-64">
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            
            <!-- Mobile Menu Button -->
            <button type="button" class="inline-flex items-center p-2 text-emerald-700 dark:text-emerald-200 rounded-lg sm:hidden hover:bg-emerald-100/50 dark:hover:bg-emerald-800/50 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <!-- Page Title -->
            <div class="flex-1 flex items-center justify-center sm:justify-start">
                <h1 class="text-xl font-bold text-emerald-800 dark:text-emerald-100">{{ $pageTitle ?? 'Dashboard' }}</h1>
            </div>

            <!-- Header Actions -->
            <div class="flex items-center space-x-2">
                
                <!-- Notifications -->
                <div class="relative">
                    <button class="p-2 text-emerald-700 dark:text-emerald-200 bg-white/30 dark:bg-emerald-800/30 backdrop-blur-sm rounded-lg border border-emerald-200/30 dark:border-emerald-700/30 hover:bg-emerald-100/50 dark:hover:bg-emerald-700/30 hover:scale-105 transition-all duration-300 shadow-sm hover:shadow-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5-5v5zM12 3v1m6.5 1.5l-.8.8M21 12h-1M17.5 18.5l-.8-.8M12 22v-1m-6.5-1.5l.8-.8M3 12h1m3.5-6.5l.8.8"/>
                        </svg>
                    </button>
                    <div class="absolute -top-1 -right-1 w-2 h-2 bg-gradient-to-br from-red-500 to-pink-500 rounded-full border border-white dark:border-emerald-900"></div>
                </div>

                <!-- User Menu -->
                <div class="relative">
                    <button class="flex items-center p-2 text-emerald-700 dark:text-emerald-200 bg-white/30 dark:bg-emerald-800/30 backdrop-blur-sm rounded-lg border border-emerald-200/30 dark:border-emerald-700/30 hover:bg-emerald-100/50 dark:hover:bg-emerald-700/30 hover:scale-105 transition-all duration-300 shadow-sm hover:shadow-md">
                        <div class="w-6 h-6 bg-gradient-to-br from-emerald-500 to-green-600 rounded-lg flex items-center justify-center shadow-sm mr-2">
                            <span class="text-white font-semibold text-xs">{{ substr(auth()->user()->name, 0, 2) }}</span>
                        </div>
                        <span class="font-medium hidden sm:block text-sm">{{ auth()->user()->name }}</span>
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</header>
```

### 11.4. Symmetric Layout System (WAJIB DIPATUHI)

**📐 SPACING STANDARDS:**
```css
/* Consistent Spacing - NO OVER SPACING */
.spacing-xs { padding: 0.5rem; }      /* 8px */
.spacing-sm { padding: 0.75rem; }     /* 12px */
.spacing-md { padding: 1rem; }        /* 16px */
.spacing-lg { padding: 1.5rem; }      /* 24px */
.spacing-xl { padding: 2rem; }        /* 32px */

/* Grid System - Perfect Symmetry */
.grid-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; }
.grid-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }

/* Component Heights - Consistent */
.h-nav-item { height: 3rem; }         /* 48px */
.h-card-sm { min-height: 6rem; }      /* 96px */
.h-card-md { min-height: 8rem; }      /* 128px */
.h-card-lg { min-height: 12rem; }     /* 192px */
```

**🎯 SYMMETRY RULES:**
- **Breadcrumb**: `mb-4` (16px) - tidak `mb-8` (32px)
- **Section spacing**: `space-y-6` (24px) - tidak `space-y-8` (32px)
- **Card padding**: `p-4` (16px) untuk small, `p-6` (24px) untuk medium
- **Button padding**: `px-4 py-2` (16px/8px) - tidak `px-6 py-3`
- **Icon sizes**: `w-4 h-4` untuk small, `w-5 h-5` untuk medium, `w-6 h-6` untuk large

### 11.5. Kontras Warna Dark/Light Mode (CRITICAL)

**🌓 CONTRAST SPECIFICATIONS:**
```css
/* Light Mode - High Contrast */
.text-primary-light { color: #065f46; }        /* Emerald-800 */
.text-secondary-light { color: #047857; }      /* Emerald-700 */
.text-muted-light { color: #059669; }          /* Emerald-600 */
.bg-primary-light { background: #ecfdf5; }     /* Emerald-50 */
.bg-secondary-light { background: #f0fdf4; }   /* Green-50 */

/* Dark Mode - High Contrast */
.text-primary-dark { color: #d1fae5; }         /* Emerald-100 */
.text-secondary-dark { color: #a7f3d0; }       /* Emerald-200 */
.text-muted-dark { color: #6ee7b7; }           /* Emerald-300 */
.bg-primary-dark { background: #064e3b; }      /* Emerald-900 */
.bg-secondary-dark { background: #065f46; }    /* Emerald-800 */

/* Contrast Ratios - WCAG AAA Compliant */
.contrast-light { background: #ffffff; color: #065f46; }  /* 8.2:1 */
.contrast-dark { background: #064e3b; color: #d1fae5; }   /* 9.1:1 */
```

**🎨 IMPLEMENTATION GUIDELINES:**
```html
<!-- Text dengan Proper Contrast -->
<h1 class="text-emerald-800 dark:text-emerald-100">Primary Heading</h1>
<p class="text-emerald-700 dark:text-emerald-200">Secondary Text</p>
<span class="text-emerald-600 dark:text-emerald-400">Muted Text</span>

<!-- Backgrounds dengan Proper Contrast -->
<div class="bg-emerald-50 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-100">
    High Contrast Container
</div>

<!-- Borders dengan Proper Visibility -->
<div class="border border-emerald-200 dark:border-emerald-700">
    Visible Border
</div>
```

### 11.6. Route & Page Validation System (MANDATORY)

**🔍 COMPLETE ROUTE SCANNING RESULTS:**

**📊 ROUTE STATISTICS:**
- Total Routes: 331
- Route Files: 11 (web.php, attendance.php, employees.php, reports.php, etc.)
- Controllers: 42 controllers
- View Pages: 64 blade files

**🎯 CORE ROUTES & PAGES STATUS:**

**✅ WORKING ROUTES:**
```php
// Main Application Routes
Route::get('/', redirect to dashboard) ✓
Route::get('/dashboard', [DashboardController::class, 'index']) ✓

// Attendance Management
Route::get('/attendance', [AttendanceController::class, 'index']) ✓
Route::get('/attendance/check-in', [AttendanceController::class, 'checkIn']) ✓
Route::get('/attendance/history', [AttendanceController::class, 'history']) ✓

// Employee Management
Route::get('/employees', [EmployeeController::class, 'index']) ✓
Route::get('/employees/create', [EmployeeController::class, 'create']) ✓
Route::get('/employees/{employee}', [EmployeeController::class, 'show']) ✓
Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit']) ✓

// Leave Management
Route::get('/leave', [LeaveController::class, 'index']) ✓
Route::get('/leave/create', [LeaveController::class, 'create']) ✓
Route::get('/leave/calendar', view('pages.leave.calendar')) ✓

// Reports & Analytics
Route::get('/reports', [ReportsController::class, 'index']) ✓
Route::get('/reports/attendance', [ReportsController::class, 'attendance']) ✓
Route::get('/reports/payroll', [ReportsController::class, 'payroll']) ✓
Route::get('/reports/analytics', [AnalyticsController::class, 'index']) ✓

// System Administration
Route::get('/system/settings', view('pages.settings.settings')) ✓
Route::get('/system/permissions', view('pages.settings.permissions')) ✓

// Holiday Management
Route::get('/holidays', [HolidayController::class, 'index']) ✓
Route::get('/holidays/calendar/view', [HolidayController::class, 'calendar']) ✓

// Schedule Management
Route::get('/schedules', [ScheduleController::class, 'index']) ✓
Route::get('/schedules/calendar', [ScheduleController::class, 'calendar']) ✓

// Payroll Management
Route::get('/payroll', [PayrollController::class, 'index']) ✓
Route::get('/payroll/bulk-calculate', view('pages.payroll.bulk_calculate')) ✓

// User Management
Route::get('/users', [UserController::class, 'index']) ✓

// Location Management
Route::get('/locations', [LocationController::class, 'index']) ✓
```

**🚨 CRITICAL ROUTES TO MONITOR:**
```php
// These routes MUST be accessible and working
'/dashboard' → DashboardController@index
'/attendance' → AttendanceController@index
'/attendance/check-in' → AttendanceController@checkIn
'/employees' → EmployeeController@index
'/reports' → ReportsController@index
'/system/settings' → view('pages.settings.settings')
```

**📋 VIEW FILES STATUS:**
```bash
# Main Pages (CRITICAL)
✅ /pages/dashboard.blade.php
✅ /pages/attendance/index.blade.php
✅ /pages/attendance/checkin.blade.php
✅ /pages/attendance/history.blade.php
✅ /pages/management/employees/index.blade.php
✅ /pages/management/employees/create.blade.php
✅ /pages/management/employees/show.blade.php
✅ /pages/management/employees/edit.blade.php
✅ /pages/reports/index.blade.php
✅ /pages/settings/settings.blade.php

# Dashboard Variants (ROLE-SPECIFIC)
✅ /pages/dashboard/super-admin.blade.php
✅ /pages/dashboard/admin.blade.php
✅ /pages/dashboard/guru.blade.php
✅ /pages/dashboard/kepala-sekolah.blade.php
✅ /pages/dashboard/pegawai.blade.php

# Leave Management
✅ /pages/leave/index.blade.php
✅ /pages/leave/create.blade.php
✅ /pages/leave/calendar.blade.php
✅ /pages/leave/analytics.blade.php

# Payroll Management
✅ /pages/payroll/index.blade.php
✅ /pages/payroll/bulk_calculate.blade.php
✅ /pages/payroll/show.blade.php

# Schedule Management
✅ /pages/schedules/index.blade.php
✅ /pages/schedules/create.blade.php
✅ /pages/schedules/calendar-patra.blade.php

# Holiday Management
✅ /pages/holidays/index.blade.php
✅ /pages/holidays/create.blade.php
✅ /pages/holidays/calendar.blade.php

# Authentication
✅ /pages/auth/login.blade.php
✅ /pages/auth/register.blade.php
✅ /pages/auth/2fa/setup.blade.php
✅ /pages/auth/2fa/verify.blade.php
✅ /pages/auth/2fa/manage.blade.php

# System Administration
✅ /pages/admin/audit/index.blade.php
✅ /pages/admin/backup/index.blade.php
✅ /pages/admin/performance/index.blade.php
```

**🛡️ SECURITY ROUTES STATUS:**
```php
// Two-Factor Authentication
Route::get('/2fa/setup', [TwoFactorController::class, 'setup']) ✓
Route::get('/2fa/verify', [TwoFactorController::class, 'verify']) ✓
Route::get('/2fa/manage', [TwoFactorController::class, 'manage']) ✓

// Security Dashboard
Route::get('/security/dashboard', [SecurityController::class, 'dashboard']) ✓
Route::get('/security/devices', [SecurityController::class, 'devices']) ✓
Route::get('/security/events', [SecurityController::class, 'events']) ✓
```

**⚠️ POTENTIAL ISSUES TO MONITOR:**
```bash
# Demo Routes (Development Only)
/demo/notifications → Should be restricted in production
/demo/components → Should be restricted in production
/demo/performance → Should be restricted in production
/demo/mobile → Should be restricted in production

# API Routes (Security Critical)
/api/face-verification/* → Requires authentication
/api/v1/attendance/* → Requires authentication
/api/v1/two-factor/* → Requires authentication
```

**🚨 VALIDATION REQUIREMENTS:**
```bash
# Test Critical Routes
curl -I http://localhost:8000/dashboard
curl -I http://localhost:8000/attendance
curl -I http://localhost:8000/employees
curl -I http://localhost:8000/reports
curl -I http://localhost:8000/system/settings

# Scan Route Cache
php artisan route:cache
php artisan config:cache
php artisan view:cache

# Test Navigation Links
php artisan route:list | grep -E "(dashboard|attendance|employees|reports|system)"
```

**📊 ROUTE ORGANIZATION:**
```bash
# Route Files (11 files)
routes/web.php → Main routes (dashboard, profile, demo)
routes/attendance.php → Attendance management
routes/employees.php → Employee management
routes/reports.php → Reports & analytics
routes/system.php → System administration
routes/security.php → Security & 2FA
routes/leave.php → Leave management
routes/payroll.php → Payroll management
routes/schedules.php → Schedule management
routes/holidays.php → Holiday management
routes/auth.php → Authentication (Laravel Breeze)
```

**🎯 NAVIGATION VALIDATION:**
```php
// Main Navigation Links (MUST BE WORKING)
✅ Dashboard → /dashboard
✅ Attendance → /attendance
✅ Employees → /employees
✅ Reports → /reports
✅ Settings → /system/settings

// Sub-navigation Links
✅ Check-in → /attendance/check-in
✅ History → /attendance/history
✅ Create Employee → /employees/create
✅ Leave Management → /leave
✅ Payroll → /payroll
✅ Schedules → /schedules
✅ Holidays → /holidays
```

**🛠️ ERROR PREVENTION:**
```php
// Dalam setiap Controller
public function index()
{
    try {
        // Logic here
        return view('pages.module.index', compact('data'));
    } catch (\Exception $e) {
        return redirect()->route('dashboard')->with('error', 'Page not found');
    }
}

// Dalam setiap Blade template
@extends('layouts.authenticated-unified')

@section('title', 'Page Title')

@section('page-content')
    <!-- Content here -->
@endsection
```

### 11.7. Implementation Priority & Checklist

**🎯 HIGH PRIORITY FIXES:**
1. **Breadcrumb Size Reduction** - CRITICAL
   - [ ] Change `py-4` to `py-2`
   - [ ] Change `text-base` to `text-sm`
   - [ ] Change `px-6` to `px-3`
   - [ ] Change `mb-8` to `mb-4`

2. **Color Consistency** - CRITICAL
   - [ ] Implement emerald-green palette across all components
   - [ ] Ensure proper contrast ratios (8:1 minimum)
   - [ ] Test dark mode visibility

3. **Spacing Symmetry** - CRITICAL
   - [ ] Standardize all spacing to consistent values
   - [ ] Remove over-spacing (`space-y-8` → `space-y-6`)
   - [ ] Ensure grid alignment

4. **Route Validation** - CRITICAL
   - [ ] Scan all existing routes
   - [ ] Test all navigation links
   - [ ] Fix any 404 errors

**🔧 MEDIUM PRIORITY IMPROVEMENTS:**
- [ ] Sidebar responsiveness
- [ ] Header mobile optimization
- [ ] Animation consistency
- [ ] Loading states

**✅ VALIDATION REQUIREMENTS:**
- [ ] No breadcrumb over-spacing
- [ ] Perfect color contrast in both modes
- [ ] All routes accessible
- [ ] All navigation links working
- [ ] Consistent spacing throughout
- [ ] Proper glassmorphism effects

**❌ FORBIDDEN PRACTICES:**
- Over-spacing dengan `py-6`, `py-8`, `mb-8`
- Inconsistent color usage
- Broken navigation links
- Poor contrast ratios
- Asymmetric layouts

---

## 12. COMPLETE FRONTEND REDESIGN PROJECT - SIMPLE & CLEAN DESIGN

**🚨 CRITICAL REDESIGN REQUIREMENT: GANTI SEMUA GLASSMORPHISM KE SIMPLE CLEAN DESIGN**

### 12.1. Design Philosophy - Dashboard-Based Design System

**✅ GUNAKAN DASHBOARD SEBAGAI TEMPLATE DASAR UNTUK SEMUA PAGE:**
Dashboard super-admin telah menjadi standard design yang sempurna dan HARUS diikuti untuk semua page content. Design system ini menggunakan:

- **Modern Card Layout**: `bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700`
- **Consistent Typography**: Hierarki font yang sudah established
- **Clean Grid System**: `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6`
- **Professional Color Palette**: Emerald sebagai primary dengan supporting colors
- **Smooth Interactions**: Hover effects yang subtle dan responsive

**🎯 MANDATORY: SEMUA PAGE HARUS MENGIKUTI PATTERN DASHBOARD**

### 12.1.1. Dashboard Pattern Template (BASE TEMPLATE)

**📋 TEMPLATE STRUCTURE YANG HARUS DIGUNAKAN:**
```php
@extends('layouts.authenticated-unified')

@section('title', '[PAGE_TITLE]')

@section('page-content')
<!-- Page Header (WAJIB ADA) -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">[PAGE_TITLE]</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">[PAGE_SUBTITLE]</p>
        </div>
        <x-ui.button variant="secondary" onclick="[ACTION]">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                [ICON_PATH]
            </svg>
            [BUTTON_TEXT]
        </x-ui.button>
    </div>
</div>

<!-- Stats Cards Grid (WAJIB ADA - minimal 3-4 cards) -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-emerald-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    [ICON_PATH]
                </svg>
            </div>
            <span class="text-sm text-emerald-600">[ADDITIONAL_INFO]</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">[MAIN_VALUE]</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">[DESCRIPTION]</p>
    </x-ui.card>
    <!-- Repeat for other stats cards with different colors -->
</div>

<!-- Main Content Area (WAJIB) -->
<x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="p-6">
        [MAIN_CONTENT_HERE - Table/Form/Chart/etc]
    </div>
</x-ui.card>
@endsection
```

### 12.1.2. Color Mapping untuk Stats Cards

**🎨 GUNAKAN COLOR PATTERN INI:**
- **Success/Health**: `bg-green-600`, `text-green-600` (untuk metrics positif)
- **Primary/Main**: `bg-emerald-600`, `text-emerald-600` (untuk metrics utama)  
- **Info/Secondary**: `bg-blue-600`, `text-blue-600` (untuk informasi)
- **Warning/Attention**: `bg-amber-600`, `text-amber-600` (untuk yang perlu perhatian)
- **Error/Critical**: `bg-red-600`, `text-red-600` (untuk metrics negatif)

### 12.1.3. Page-Specific Implementations

**👔 Employee Management Pattern:**
```
Stats Cards: Total Employees (blue), Active Today (green), New This Month (emerald), On Leave (amber)
Main Content: Employee table with filters and actions
```

**⏰ Attendance System Pattern:**
```
Stats Cards: Present Today (green), Late Arrivals (amber), Absent (red), Attendance Rate (blue)  
Main Content: Real-time attendance table with check-in/out capabilities
```

**📅 Schedule Management Pattern:**
```
Stats Cards: Total Schedules (blue), Active Shifts (green), Conflicts (red), Coverage Rate (emerald)
Main Content: Schedule calendar/grid with create/edit functions
```

**💰 Payroll Management Pattern:**
```
Stats Cards: Total Payroll (blue), Processed (green), Pending (amber), Issues (red)
Main Content: Payroll table with calculation and export features
```

### 12.2. New Color Palette - Simple & Professional

**🎨 CLEAN COLOR SYSTEM:**
```css
/* Light Mode Colors */
:root {
  --primary: #059669;          /* Emerald-600 */
  --primary-hover: #047857;    /* Emerald-700 */
  --primary-light: #d1fae5;    /* Emerald-100 */
  --primary-lighter: #ecfdf5;  /* Emerald-50 */
  
  --secondary: #6b7280;        /* Gray-500 */
  --secondary-hover: #374151;  /* Gray-700 */
  --secondary-light: #f3f4f6;  /* Gray-100 */
  --secondary-lighter: #f9fafb; /* Gray-50 */
  
  --background: #ffffff;       /* White */
  --background-alt: #f9fafb;   /* Gray-50 */
  --surface: #ffffff;          /* White */
  --surface-alt: #f3f4f6;      /* Gray-100 */
  
  --text-primary: #111827;     /* Gray-900 */
  --text-secondary: #6b7280;   /* Gray-500 */
  --text-muted: #9ca3af;       /* Gray-400 */
  
  --border: #e5e7eb;           /* Gray-200 */
  --border-light: #f3f4f6;     /* Gray-100 */
  
  --success: #059669;          /* Emerald-600 */
  --warning: #d97706;          /* Amber-600 */
  --error: #dc2626;            /* Red-600 */
  --info: #2563eb;             /* Blue-600 */
}

/* Dark Mode Colors */
@media (prefers-color-scheme: dark) {
  :root {
    --primary: #10b981;          /* Emerald-500 */
    --primary-hover: #059669;    /* Emerald-600 */
    --primary-light: #064e3b;    /* Emerald-900 */
    --primary-lighter: #022c22;  /* Emerald-950 */
    
    --secondary: #9ca3af;        /* Gray-400 */
    --secondary-hover: #d1d5db;  /* Gray-300 */
    --secondary-light: #374151;  /* Gray-700 */
    --secondary-lighter: #1f2937; /* Gray-800 */
    
    --background: #111827;       /* Gray-900 */
    --background-alt: #1f2937;   /* Gray-800 */
    --surface: #1f2937;          /* Gray-800 */
    --surface-alt: #374151;      /* Gray-700 */
    
    --text-primary: #f9fafb;     /* Gray-50 */
    --text-secondary: #d1d5db;   /* Gray-300 */
    --text-muted: #9ca3af;       /* Gray-400 */
    
    --border: #374151;           /* Gray-700 */
    --border-light: #4b5563;     /* Gray-600 */
    
    --success: #10b981;          /* Emerald-500 */
    --warning: #f59e0b;          /* Amber-500 */
    --error: #ef4444;            /* Red-500 */
    --info: #3b82f6;             /* Blue-500 */
  }
}
```

### 12.3. MANDATORY REDESIGN INSTRUCTIONS

**🎯 SETIAP PAGE/VIEW/COMPONENT HARUS DIREDESIGN DENGAN PATTERN INI:**

#### 12.3.1. Layout Structure (WAJIB DIPAKAI)
```html
<!-- Main Layout Template -->
@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Header content -->
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumb - SIMPLE -->
        <nav class="mb-6" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                <li><a href="{{ route('dashboard') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Dashboard</a></li>
                <li><svg class="w-4 h-4 mx-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg></li>
                <li class="text-gray-900 dark:text-gray-100 font-medium">{{ $pageTitle }}</li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $pageTitle }}</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">{{ $pageDescription }}</p>
        </div>

        <!-- Content Area -->
        <div class="space-y-6">
            @yield('page-content')
        </div>
    </main>
</div>
@endsection
```

#### 12.3.2. Card Component (WAJIB GANTI)
```html
<!-- Simple Card Template -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <!-- Card Header -->
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Card Title</h3>
        <div class="flex space-x-2">
            <!-- Actions -->
        </div>
    </div>
    
    <!-- Card Content -->
    <div class="space-y-4">
        <!-- Content here -->
    </div>
</div>
```

#### 12.3.3. Statistics Card (WAJIB GANTI)
```html
<!-- Simple Statistics Card -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $title }}</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $value }}</p>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $description }}</p>
        </div>
        <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                {{ $icon }}
            </svg>
        </div>
    </div>
</div>
```

#### 12.3.4. Button Styles (WAJIB GANTI)
```html
<!-- Primary Button -->
<button class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2 px-4 rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
    Primary Action
</button>

<!-- Secondary Button -->
<button class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100 font-medium py-2 px-4 rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
    Secondary Action
</button>

<!-- Outline Button -->
<button class="border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium py-2 px-4 rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
    Outline Action
</button>
```

#### 12.3.5. Sidebar Design (WAJIB GANTI)
```html
<!-- Simple Sidebar -->
<aside class="w-64 bg-white dark:bg-gray-800 shadow-sm border-r border-gray-200 dark:border-gray-700 fixed inset-y-0 left-0 z-50 transform -translate-x-full transition-transform duration-200 ease-in-out lg:translate-x-0 lg:static lg:inset-0">
    <div class="flex flex-col h-full">
        <!-- Logo -->
        <div class="flex items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="w-8 h-8 bg-emerald-600 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">Attendance</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">System</p>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md group {{ request()->routeIs('dashboard') ? 'bg-emerald-50 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300' : '' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                </svg>
                Dashboard
            </a>

            <a href="{{ route('attendance.index') }}" class="flex items-center px-3 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md group {{ request()->routeIs('attendance.*') ? 'bg-emerald-50 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300' : '' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Attendance
            </a>

            <a href="{{ route('employees.index') }}" class="flex items-center px-3 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md group {{ request()->routeIs('employees.*') ? 'bg-emerald-50 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300' : '' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Employees
            </a>
        </nav>

        <!-- User Profile -->
        <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
                <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ substr(auth()->user()->name, 0, 2) }}</span>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->roles->first()->name ?? 'User' }}</p>
                </div>
            </div>
        </div>
    </div>
</aside>
```

#### 12.3.6. Table Design (WAJIB GANTI)
```html
<!-- Simple Table -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Table Title</h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Column 1
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Column 2
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                        Data 1
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                        Data 2
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
```

### 12.4. SPECIFIC REDESIGN INSTRUCTIONS

**🎯 SETIAP HALAMAN HARUS DIREDESIGN DENGAN PRIORITAS:**

#### 12.4.1. HIGH PRIORITY PAGES (REDESIGN SEGERA)
```bash
# Dashboard Pages
✅ REDESIGN: resources/views/pages/dashboard.blade.php
✅ REDESIGN: resources/views/pages/dashboard/super-admin.blade.php
✅ REDESIGN: resources/views/pages/dashboard/admin.blade.php
✅ REDESIGN: resources/views/pages/dashboard/guru.blade.php
✅ REDESIGN: resources/views/pages/dashboard/kepala-sekolah.blade.php
✅ REDESIGN: resources/views/pages/dashboard/pegawai.blade.php

# Attendance Pages
✅ REDESIGN: resources/views/pages/attendance/index.blade.php
✅ REDESIGN: resources/views/pages/attendance/checkin.blade.php
✅ REDESIGN: resources/views/pages/attendance/history.blade.php

# Employee Pages
✅ REDESIGN: resources/views/pages/management/employees/index.blade.php
✅ REDESIGN: resources/views/pages/management/employees/create.blade.php
✅ REDESIGN: resources/views/pages/management/employees/show.blade.php
✅ REDESIGN: resources/views/pages/management/employees/edit.blade.php

# Layout Components
✅ REDESIGN: resources/views/layouts/authenticated-unified.blade.php
✅ REDESIGN: resources/views/layouts/app-unified.blade.php
✅ REDESIGN: resources/views/layouts/guest.blade.php
```

#### 12.4.2. MEDIUM PRIORITY PAGES (REDESIGN SETELAH HIGH PRIORITY)
```bash
# Reports Pages
✅ REDESIGN: resources/views/pages/reports/index.blade.php
✅ REDESIGN: resources/views/pages/reports/attendance.blade.php
✅ REDESIGN: resources/views/pages/reports/payroll.blade.php

# Leave Management
✅ REDESIGN: resources/views/pages/leave/index.blade.php
✅ REDESIGN: resources/views/pages/leave/create.blade.php
✅ REDESIGN: resources/views/pages/leave/calendar.blade.php

# Payroll Management
✅ REDESIGN: resources/views/pages/payroll/index.blade.php
✅ REDESIGN: resources/views/pages/payroll/bulk_calculate.blade.php
✅ REDESIGN: resources/views/pages/payroll/show.blade.php

# Settings Pages
✅ REDESIGN: resources/views/pages/settings/settings.blade.php
✅ REDESIGN: resources/views/pages/settings/permissions.blade.php
```

#### 12.4.3. LOW PRIORITY PAGES (REDESIGN TERAKHIR)
```bash
# Holiday Management
✅ REDESIGN: resources/views/pages/holidays/index.blade.php
✅ REDESIGN: resources/views/pages/holidays/create.blade.php
✅ REDESIGN: resources/views/pages/holidays/calendar.blade.php

# Schedule Management
✅ REDESIGN: resources/views/pages/schedules/index.blade.php
✅ REDESIGN: resources/views/pages/schedules/create.blade.php
✅ REDESIGN: resources/views/pages/schedules/calendar-patra.blade.php

# Authentication Pages
✅ REDESIGN: resources/views/pages/auth/login.blade.php
✅ REDESIGN: resources/views/pages/auth/register.blade.php
✅ REDESIGN: resources/views/pages/auth/2fa/setup.blade.php
```

### 12.5. COMPONENT REDESIGN INSTRUCTIONS

**🧩 SETIAP COMPONENT HARUS DIGANTI:**

#### 12.5.1. Blade Components (WAJIB GANTI)
```bash
# UI Components
✅ REDESIGN: resources/views/components/ui/card.blade.php
✅ REDESIGN: resources/views/components/ui/button.blade.php
✅ REDESIGN: resources/views/components/ui/badge.blade.php
✅ REDESIGN: resources/views/components/ui/progress.blade.php
✅ REDESIGN: resources/views/components/ui/input.blade.php
✅ REDESIGN: resources/views/components/ui/select.blade.php
✅ REDESIGN: resources/views/components/ui/textarea.blade.php
✅ REDESIGN: resources/views/components/ui/table.blade.php

# Layout Components
✅ REDESIGN: resources/views/components/layouts/glass-card.blade.php → simple-card.blade.php
✅ REDESIGN: resources/views/components/layouts/glass-table.blade.php → simple-table.blade.php
✅ REDESIGN: resources/views/components/layouts/page-base.blade.php → simple-page.blade.php

# Navigation Components
✅ REDESIGN: resources/views/components/navigation/unified-nav.blade.php
✅ REDESIGN: resources/views/components/navigation/nav-item.blade.php
```

#### 12.5.2. Vue Components (WAJIB GANTI)
```bash
# Core Components
✅ REDESIGN: resources/js/components/AttendanceReporting.vue
✅ REDESIGN: resources/js/components/FaceRecognition.vue
✅ REDESIGN: resources/js/components/DeviceManagement.vue
✅ REDESIGN: resources/js/components/NotificationCenter.vue
✅ REDESIGN: resources/js/components/ScheduleGrid.vue
✅ REDESIGN: resources/js/components/ScheduleModal.vue

# Auth Components
✅ REDESIGN: resources/js/components/Auth/EnhancedLoginForm.vue
✅ REDESIGN: resources/js/components/Auth/TwoFactorSetup.vue
✅ REDESIGN: resources/js/components/Security/SecurityDashboard.vue
```

### 12.6. CSS REDESIGN INSTRUCTIONS

**🎨 HAPUS GLASSMORPHISM, GANTI SIMPLE DESIGN:**

#### 12.6.1. Update Design System CSS
```css
/* resources/css/design-system.css - HAPUS SEMUA & GANTI */

/* HAPUS GLASSMORPHISM */
.glass-card { /* DELETE */ }
.glass-table { /* DELETE */ }
.backdrop-blur { /* DELETE */ }
.glass-bg { /* DELETE */ }

/* GANTI DENGAN SIMPLE DESIGN */
.simple-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.simple-button {
  background: #059669;
  color: white;
  border: none;
  border-radius: 0.375rem;
  padding: 0.5rem 1rem;
  font-weight: 500;
  transition: background-color 0.2s;
}

.simple-button:hover {
  background: #047857;
}

.simple-table {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  overflow: hidden;
}

.simple-nav {
  background: white;
  border-right: 1px solid #e5e7eb;
}

/* Dark Mode */
@media (prefers-color-scheme: dark) {
  .simple-card {
    background: #1f2937;
    border-color: #374151;
  }
  
  .simple-table {
    background: #1f2937;
    border-color: #374151;
  }
  
  .simple-nav {
    background: #1f2937;
    border-color: #374151;
  }
}
```

#### 12.6.2. Update App CSS
```css
/* resources/css/app.css - HAPUS GLASSMORPHISM */

/* HAPUS SEMUA INI */
.glassmorphism { /* DELETE */ }
.backdrop-blur-lg { /* DELETE */ }
.bg-white/30 { /* DELETE */ }
.hover:scale-105 { /* DELETE */ }
.hover:rotate-12 { /* DELETE */ }
.group-hover:rotate-6 { /* DELETE */ }

/* GANTI DENGAN SIMPLE ANIMATIONS */
.simple-hover:hover {
  background-color: #f3f4f6;
  transition: background-color 0.2s ease;
}

.simple-focus:focus {
  outline: 2px solid #059669;
  outline-offset: 2px;
}

.simple-transition {
  transition: all 0.2s ease;
}
```

### 12.7. PERFORMANCE OPTIMIZATION REQUIREMENTS

**⚡ OPTIMASI PERFORMANCE SETELAH REDESIGN:**

#### 12.7.1. Remove Heavy Effects
```bash
# HAPUS SEMUA EFEK BERAT
- backdrop-filter: blur() → DELETE
- box-shadow: 0 25px 50px → DELETE
- transform: scale(1.05) → DELETE
- background: linear-gradient() → MINIMIZE
- transition: all 0.3s → OPTIMIZE to 0.2s
```

#### 12.7.2. Optimize Loading
```bash
# OPTIMASI LOADING
- Lazy load components
- Minimize CSS bundle
- Remove unused animations
- Optimize images
- Use simple hover states
```

### 12.8. MANDATORY REDESIGN CHECKLIST

**📋 SETIAP REDESIGN HARUS MEMENUHI:**

#### 12.8.1. Design Requirements
- [ ] **NO GLASSMORPHISM** - Hapus semua backdrop-blur, transparency
- [ ] **SOLID BACKGROUNDS** - Gunakan solid white/gray backgrounds
- [ ] **SIMPLE BORDERS** - Gunakan border-gray-200 only
- [ ] **MINIMAL SHADOWS** - Hanya shadow-sm atau shadow-md
- [ ] **CLEAN TYPOGRAPHY** - Consistent font sizes dan weights
- [ ] **SIMPLE HOVER** - Hanya hover:bg-gray-50 atau hover:bg-gray-100
- [ ] **FAST ANIMATIONS** - Maksimal 0.2s duration
- [ ] **ACCESSIBLE** - Proper focus states dan keyboard navigation

#### 12.8.2. Performance Requirements
- [ ] **FAST LOADING** - No heavy CSS effects
- [ ] **SMOOTH SCROLLING** - No performance bottlenecks
- [ ] **MOBILE OPTIMIZED** - Fast on mobile devices
- [ ] **MINIMAL BUNDLE** - Remove unused CSS
- [ ] **EFFICIENT ANIMATIONS** - Only essential animations

#### 12.8.3. Functionality Requirements
- [ ] **ALL FEATURES WORKING** - No broken functionality
- [ ] **RESPONSIVE** - Works on all screen sizes
- [ ] **DARK MODE** - Proper dark mode support
- [ ] **ACCESSIBILITY** - WCAG 2.1 AA compliant
- [ ] **CROSS-BROWSER** - Works on all modern browsers

### 12.9. REDESIGN VALIDATION

**🔍 SETIAP REDESIGN HARUS DIVALIDASI:**

#### 12.9.1. Visual Validation
```bash
# Test di berbagai device
- Desktop (1920x1080)
- Tablet (768x1024)
- Mobile (375x667)
- Mobile Large (414x896)

# Test di berbagai browser
- Chrome
- Firefox
- Safari
- Edge
```

#### 12.9.2. Performance Validation
```bash
# Lighthouse Score Target
- Performance: 90+
- Accessibility: 90+
- Best Practices: 90+
- SEO: 90+

# Bundle Size Target
- CSS: < 100KB
- JS: < 200KB
- Images: Optimized
```

### 12.10. IMPLEMENTATION TIMELINE

**📅 JADWAL REDESIGN:**

#### Week 1: Core Layout & Navigation
- [ ] Sidebar redesign
- [ ] Header redesign
- [ ] Main layout template
- [ ] Navigation components

#### Week 2: Dashboard & Main Pages
- [ ] Dashboard redesign
- [ ] Attendance pages
- [ ] Employee pages
- [ ] Basic components

#### Week 3: Secondary Pages
- [ ] Reports pages
- [ ] Leave management
- [ ] Payroll pages
- [ ] Settings pages

#### Week 4: Polish & Optimization
- [ ] Final touches
- [ ] Performance optimization
- [ ] Testing & validation
- [ ] Documentation update

### 12.11. PROMPT UNTUK GEMINI AI

**🤖 GUNAKAN PROMPT INI UNTUK SETIAP REDESIGN:**

```
REDESIGN INSTRUCTION - SIMPLE CLEAN DESIGN

Task: Redesign the [PAGE/COMPONENT NAME] from glassmorphism to simple, clean design.

MANDATORY REQUIREMENTS:
1. REMOVE ALL GLASSMORPHISM:
   - Delete backdrop-blur effects
   - Remove transparent backgrounds (bg-white/30, bg-gray-800/40)
   - Remove complex gradients
   - Remove heavy animations (hover:scale-105, hover:rotate-12)
   - Remove large shadows (shadow-lg, shadow-xl)

2. IMPLEMENT SIMPLE DESIGN:
   - Use solid backgrounds (bg-white, bg-gray-50)
   - Use simple borders (border-gray-200)
   - Use minimal shadows (shadow-sm only)
   - Use clean typography hierarchy
   - Use simple hover effects (hover:bg-gray-50)
   - Use fast animations (0.2s duration max)

3. MAINTAIN FUNCTIONALITY:
   - All features must work exactly the same
   - Keep responsive design
   - Maintain dark mode support
   - Keep accessibility features

4. FOLLOW DESIGN SYSTEM:
   - Use the simple color palette defined in section 12.2
   - Use the layout templates from section 12.3
   - Follow the component patterns provided
   - Maintain consistency with other redesigned pages

5. PERFORMANCE OPTIMIZATION:
   - Remove heavy CSS effects
   - Optimize loading performance
   - Minimize bundle size
   - Use efficient animations only

SPECIFIC ACTIONS:
- Replace all glassmorphism cards with simple white cards
- Change all buttons to simple solid buttons
- Update all navigation to simple design
- Remove all backdrop-blur effects
- Simplify all hover states
- Optimize all animations

VALIDATION REQUIREMENTS:
- Visual consistency with other redesigned pages
- Performance improvement over old design
- All functionality preserved
- Responsive on all devices
- Accessible to all users

Please redesign the [PAGE/COMPONENT NAME] following these exact requirements.
```

**🎯 EXAMPLE USAGE:**
```
REDESIGN INSTRUCTION - SIMPLE CLEAN DESIGN

Task: Redesign the Dashboard page (resources/views/pages/dashboard.blade.php) from glassmorphism to simple, clean design.

[... follow the complete prompt above ...]

Please redesign the Dashboard page following these exact requirements.
```

### 12.12. QUALITY ASSURANCE

**🔍 SETIAP REDESIGN HARUS LULUS QA:**

#### 12.12.1. Visual QA Checklist
- [ ] No glassmorphism effects remaining
- [ ] Consistent color usage throughout
- [ ] Proper spacing and alignment
- [ ] Clean typography hierarchy
- [ ] Simple and functional design
- [ ] Professional appearance

#### 12.12.2. Technical QA Checklist
- [ ] All features working correctly
- [ ] Responsive on all devices
- [ ] Dark mode working properly
- [ ] Accessibility requirements met
- [ ] Performance optimized
- [ ] Cross-browser compatibility

#### 12.12.3. User Experience QA
- [ ] Intuitive navigation
- [ ] Clear information hierarchy
- [ ] Easy to use interface
- [ ] Fast loading times
- [ ] Smooth interactions
- [ ] Error handling working

---

**🚨 CRITICAL SUCCESS CRITERIA:**

1. **ZERO GLASSMORPHISM** - Tidak boleh ada efek glassmorphism tersisa
2. **SIMPLE & CLEAN** - Desain harus simple, clean, dan professional
3. **FAST PERFORMANCE** - Loading harus cepat di semua device
4. **FULL FUNCTIONALITY** - Semua fitur harus tetap berfungsi
5. **RESPONSIVE DESIGN** - Harus responsive di semua ukuran layar
6. **ACCESSIBLE** - Harus accessible untuk semua pengguna

**✅ VALIDATION FINAL:**
- Lighthouse Score: 90+ di semua metrics
- Visual consistency: 100% consistent
- Functionality: 100% working
- Performance: Significantly improved
- User experience: Enhanced and simplified

---
**Akhir Panduan Redesign Project.**

---

## 13. COMPLETE REDESIGN EXECUTION PROMPT

**🤖 PROMPT UNTUK GEMINI AI - REDESIGN SEMUA FILE TANPA TERKECUALI:**

```
MASSIVE FRONTEND REDESIGN - GLASSMORPHISM TO SIMPLE CLEAN DESIGN

URGENT TASK: Redesign ALL frontend files from glassmorphism to simple, clean design.

SCOPE: REDESIGN EVERY SINGLE FILE - NO EXCEPTIONS
- ALL Blade templates (100+ files)
- ALL Vue components (50+ files)
- ALL CSS files (design-system.css, app.css, etc.)
- ALL layout files (layouts, components, pages)
- ALL authentication pages
- ALL dashboard pages
- ALL management pages
- ALL navigation components
- ALL UI components

MANDATORY REQUIREMENTS:

1. COMPLETE GLASSMORPHISM REMOVAL:
   ❌ DELETE ALL: backdrop-blur-lg, backdrop-blur-xl, backdrop-blur-sm
   ❌ DELETE ALL: bg-white/30, bg-gray-800/40, bg-emerald-50/30
   ❌ DELETE ALL: border-white/20, border-gray-600/30
   ❌ DELETE ALL: hover:scale-105, hover:rotate-12, group-hover:rotate-6
   ❌ DELETE ALL: shadow-lg, shadow-xl, shadow-2xl
   ❌ DELETE ALL: from-emerald-50 via-green-50 to-teal-50
   ❌ DELETE ALL: hover:shadow-emerald-500/10
   ❌ DELETE ALL: transition-all duration-300
   ❌ DELETE ALL: .glassmorphism, .glass-card, .glass-table classes

2. IMPLEMENT SIMPLE CLEAN DESIGN:
   ✅ USE ONLY: bg-white, bg-gray-50, bg-gray-100 (light mode)
   ✅ USE ONLY: bg-gray-800, bg-gray-900 (dark mode)
   ✅ USE ONLY: border-gray-200, border-gray-300 (light mode)
   ✅ USE ONLY: border-gray-600, border-gray-700 (dark mode)
   ✅ USE ONLY: shadow-sm, shadow-md (minimal shadows)
   ✅ USE ONLY: hover:bg-gray-50, hover:bg-gray-100 (simple hover)
   ✅ USE ONLY: rounded-lg, rounded-xl (simple borders)
   ✅ USE ONLY: transition-colors duration-200 (fast animations)

3. SPECIFIC REDESIGN PATTERNS:

   SIDEBAR REDESIGN:
   - Remove: backdrop-blur-lg, bg-gradient-to-b, border-emerald-200/50
   - Replace with: bg-white, border-gray-200, shadow-sm
   - Navigation items: bg-white hover:bg-gray-50 (active: bg-emerald-50)
   - Remove all complex gradients and glassmorphism effects

   CARDS REDESIGN:
   - Remove: backdrop-blur-xl, bg-white/30, border-white/20
   - Replace with: bg-white, border-gray-200, shadow-sm
   - Hover: hover:shadow-md, hover:border-gray-300
   - Clean corners: rounded-lg only

   BUTTONS REDESIGN:
   - Primary: bg-emerald-600 hover:bg-emerald-700 (solid)
   - Secondary: bg-gray-100 hover:bg-gray-200 (solid)
   - Remove all glassmorphism button effects

   TABLES REDESIGN:
   - Remove: backdrop-blur, bg-white/30
   - Replace with: bg-white, border-gray-200
   - Header: bg-gray-50
   - Rows: hover:bg-gray-50

   HEADERS REDESIGN:
   - Remove: backdrop-blur-lg, bg-white/80
   - Replace with: bg-white, border-gray-200, shadow-sm
   - Clean and minimal design

4. PERFORMANCE OPTIMIZATION:
   - Remove ALL heavy CSS effects
   - Optimize animations (max 0.2s duration)
   - Minimize CSS bundle size
   - Remove unused glassmorphism classes
   - Use efficient hover states only

5. MAINTAIN FUNCTIONALITY:
   - ALL features must work exactly the same
   - Keep responsive design intact
   - Maintain dark mode support
   - Keep accessibility features
   - Preserve all JavaScript functionality

6. DARK MODE REDESIGN:
   - Remove glassmorphism dark mode effects
   - Use: bg-gray-900, bg-gray-800, bg-gray-700
   - Borders: border-gray-700, border-gray-600
   - Text: text-gray-100, text-gray-300, text-gray-400
   - Simple and clean dark mode design

FILE PRIORITIZATION:

HIGH PRIORITY (REDESIGN FIRST):
1. resources/views/layouts/authenticated-unified.blade.php
2. resources/views/layouts/app-unified.blade.php
3. resources/views/layouts/guest.blade.php
4. resources/views/components/layouts/glass-card.blade.php
5. resources/views/components/navigation/unified-nav.blade.php
6. resources/views/pages/dashboard.blade.php
7. resources/views/pages/dashboard/*.blade.php (all role dashboards)
8. resources/views/pages/attendance/*.blade.php (all attendance pages)
9. resources/views/pages/management/employees/*.blade.php (all employee pages)
10. resources/css/design-system.css
11. resources/css/app.css

MEDIUM PRIORITY (REDESIGN SECOND):
12. resources/views/pages/reports/*.blade.php
13. resources/views/pages/leave/*.blade.php
14. resources/views/pages/payroll/*.blade.php
15. resources/views/pages/settings/*.blade.php
16. resources/views/components/ui/*.blade.php
17. resources/js/components/*.vue

LOW PRIORITY (REDESIGN LAST):
18. resources/views/pages/holidays/*.blade.php
19. resources/views/pages/schedules/*.blade.php
20. resources/views/pages/auth/*.blade.php
21. resources/views/pages/admin/*.blade.php

VALIDATION REQUIREMENTS:
- Visual consistency across all pages
- Performance improvement (Lighthouse 90+)
- All functionality preserved
- Responsive on all devices (mobile, tablet, desktop)
- Accessible (WCAG 2.1 AA)
- Cross-browser compatibility

SPECIFIC REPLACEMENT PATTERNS:

Replace this glassmorphism pattern:
```html
<div class="relative backdrop-blur-xl bg-white/30 border border-white/20 rounded-2xl p-6 hover:bg-white/40 shadow-lg transition-all duration-300 group">
```

With this simple pattern:
```html
<div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md hover:border-gray-300 shadow-sm transition-colors duration-200">
```

Replace this glassmorphism sidebar:
```html
<div class="bg-gradient-to-b from-emerald-50 via-green-50 to-teal-50 backdrop-blur-lg border-r border-emerald-200/50">
```

With this simple sidebar:
```html
<div class="bg-white border-r border-gray-200 shadow-sm">
```

Replace this glassmorphism navigation:
```html
<a class="bg-white/20 backdrop-blur-sm border border-emerald-200/30 hover:bg-emerald-100/50 hover:scale-105 transition-all duration-300">
```

With this simple navigation:
```html
<a class="bg-white hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-colors duration-200">
```

EXECUTION INSTRUCTIONS:
1. Start with HIGH PRIORITY files
2. Work systematically through each file
3. Apply the redesign patterns consistently
4. Remove ALL glassmorphism effects
5. Implement simple, clean design
6. Test functionality after each file
7. Validate performance improvements
8. Ensure visual consistency
9. Complete ALL files without exception
10. Document changes made

SUCCESS CRITERIA:
✅ ZERO glassmorphism effects remaining
✅ ALL files redesigned to simple, clean design
✅ Performance significantly improved
✅ All functionality preserved
✅ Visual consistency maintained
✅ Responsive design working
✅ Dark mode working properly
✅ Accessibility maintained

EXECUTE THIS REDESIGN FOR ALL 100+ FILES WITHOUT EXCEPTION.
Start with the HIGH PRIORITY files and work through systematically.
Every single file must be redesigned - NO EXCEPTIONS.
```

**🎯 SUMMARY PROMPT (COPY THIS FOR GEMINI AI):**

```
REDESIGN ALL FRONTEND FILES - GLASSMORPHISM TO SIMPLE CLEAN DESIGN

TASK: Redesign EVERY SINGLE frontend file (100+ files) from glassmorphism to simple, clean design.

ACTIONS:
1. Remove ALL glassmorphism effects (backdrop-blur, bg-white/30, complex gradients, heavy animations)
2. Replace with simple, clean design (solid backgrounds, minimal shadows, clean borders)
3. Maintain ALL functionality and responsiveness
4. Optimize performance
5. Ensure visual consistency

FILES TO REDESIGN:
- ALL Blade templates (layouts, pages, components)
- ALL Vue components
- ALL CSS files
- Start with HIGH PRIORITY files first

PATTERNS:
- glassmorphism cards → simple white cards with border-gray-200
- glassmorphism sidebar → simple white sidebar with shadow-sm
- glassmorphism buttons → solid colored buttons
- glassmorphism tables → simple white tables
- Remove ALL backdrop-blur, transparency, complex gradients

REQUIREMENTS:
- Zero glassmorphism effects
- Fast performance (Lighthouse 90+)
- All functionality preserved
- Responsive design maintained
- Dark mode support
- Accessibility maintained

REDESIGN EVERY FILE - NO EXCEPTIONS.
```
