# Analisis Teknis dan Bisnis Proyek SC

Dokumen ini merangkum struktur dan perilaku aplikasi sistem informasi cuti berdasarkan codebase yang ada saat ini. Fokus utama analisis adalah repository `backend` Laravel dan `frontend` Vue.js.

## Ringkasan Eksekutif

- Arsitektur aplikasi sudah terpisah jelas antara frontend Vue.js dan backend Laravel.
- Frontend berperan sebagai UI SPA yang mengonsumsi API Laravel melalui Axios.
- Backend memakai Laravel Sanctum untuk autentikasi token dan menyediakan endpoint JSON untuk modul cuti, persetujuan, laporan, dan manajemen karyawan.
- Ada indikasi kuat integrasi storage ke Supabase untuk lampiran file cuti, tetapi konfigurasi database pada repo yang terlihat masih menggunakan `sqlite` di file contoh environment.
- Skema tabel bisnis utama seperti `leaves`, `requests`, dan `holidays` tidak ditemukan pada migration yang tersedia di repo. Karena itu, struktur tabel tersebut di bawah ini adalah hasil inferensi dari model dan controller, bukan hasil migration resmi.

## 1. Arsitektur dan Struktur Direktori

### Pemisahan Frontend dan Backend

Repository ini memakai pola decoupled:

- `backend/` berisi Laravel sebagai penyedia API, autentikasi, business logic, dan rendering PDF.
- `frontend/` berisi Vue 3 sebagai SPA yang menangani navigasi halaman, form, tabel, modal, dan interaksi user.
- Komunikasi antar lapisan dilakukan lewat HTTP REST API menggunakan Axios.
- Autentikasi memakai Bearer token dari Laravel Sanctum. Token disimpan di `localStorage` dan dipasang otomatis oleh interceptor Axios.

### Direktori Penting Backend

| Direktori / File | Fungsi |
|---|---|
| `backend/app/Http/Controllers/` | Pusat logika API. Berisi controller untuk login, dashboard, pengajuan cuti, persetujuan, laporan, dan data karyawan. |
| `backend/app/Models/` | Model Eloquent untuk `User`, `Leave`, `Request`, dan `Holiday`. Relasi bisnis didefinisikan di sini. |
| `backend/database/migrations/` | Migration yang ada saat ini hanya untuk tabel infrastruktur Laravel bawaan dan Sanctum, bukan tabel bisnis cuti. |
| `backend/routes/api.php` | Definisi endpoint API utama. Ini adalah surface paling penting untuk frontend. |
| `backend/resources/views/pdf/` | Template Blade untuk export laporan PDF. |
| `backend/config/filesystems.php` | Konfigurasi storage, termasuk disk `supabase` untuk lampiran. |
| `backend/config/sanctum.php` | Konfigurasi stateful request Sanctum. |

### Direktori Penting Frontend

| Direktori / File | Fungsi |
|---|---|
| `frontend/src/main.js` | Bootstrap aplikasi Vue dan memasang router. |
| `frontend/src/router/index.js` | Routing SPA dan guard berdasarkan token. |
| `frontend/src/lib/axios.js` | Instance Axios terpusat dengan base URL API dan interceptor Authorization header. |
| `frontend/src/views/` | Halaman utama seperti Login, Dashboard, Pengajuan, Persetujuan, Laporan, dan Data Karyawan. |
| `frontend/src/components/Sidebar.vue` | Navigasi utama dan logout. |
| `frontend/src/views/DataKaryawan/` | UI tree organisasi dan manajemen karyawan. |
| `frontend/src/assets/` | CSS global dan aset statis frontend. |

### State Management Frontend

Frontend tidak memakai Pinia atau Vuex.

Yang dipakai adalah:

- `ref`, `reactive`, `computed`, `watch`, dan `onMounted` dari Vue Composition API.
- `localStorage` untuk menyimpan `token` dan objek `user` setelah login.
- Data per halaman diambil langsung dari API saat komponen dimount.

Implikasinya:

- State bersifat lokal per halaman.
- Tidak ada store global terpusat.
- Konsistensi data antar halaman dijaga dengan refetch setelah aksi CRUD atau approval.

## 2. Skema Database dan Relasi

### Catatan Penting Soal Database

Ada perbedaan antara asumsi bisnis dan bukti kode:

- Repo ini **tidak menampilkan migration untuk tabel bisnis utama** `leaves`, `requests`, dan `holidays`.
- Migration yang tersedia hanya tabel infrastruktur Laravel seperti `users`, `password_reset_tokens`, `sessions`, `cache`, `jobs`, dan `personal_access_tokens`.
- File `.env.example` yang ada di repo masih memakai `DB_CONNECTION=sqlite`, sehingga konfigurasi Supabase database belum terlihat di repository ini.
- Yang terlihat jelas terkait Supabase adalah disk storage untuk upload lampiran cuti pada `config/filesystems.php` dan pemakaian `store('lampiran', 'supabase')` di controller pengajuan.

### Tabel yang Terlihat dari Migration Repo

| Tabel | Sumber | Kolom Utama |
|---|---|---|
| `users` | Migration bawaan Laravel | `id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at` |
| `password_reset_tokens` | Migration bawaan Laravel | `email`, `token`, `created_at` |
| `sessions` | Migration bawaan Laravel | `id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity` |
| `cache` | Migration cache | `key`, `value`, `expiration` |
| `cache_locks` | Migration cache | `key`, `owner`, `expiration` |
| `jobs` | Migration queue | `id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at` |
| `job_batches` | Migration queue | `id`, `name`, `total_jobs`, `pending_jobs`, `failed_jobs`, `failed_job_ids`, `options`, `cancelled_at`, `created_at`, `finished_at` |
| `failed_jobs` | Migration queue | `id`, `uuid`, `connection`, `queue`, `payload`, `exception`, `failed_at` |
| `personal_access_tokens` | Sanctum | `id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at` |

### Tabel Bisnis yang Diinformasikan oleh Model dan Controller

#### `users`

Model `App\Models\User` mengindikasikan struktur aplikasi sebenarnya jauh lebih kaya daripada migration bawaan Laravel.

| Kolom | Tipe Logis | Peran |
|---|---|---|
| `nik` | string, primary key | Identitas login dan primary key bisnis. |
| `nama` | string | Nama karyawan. |
| `tglMasuk` | date/datetime | Tanggal masuk kerja, dipakai untuk eligibilitas cuti tahunan. |
| `departemen` | string | Departemen karyawan. |
| `role` | string | Role pengguna, seperti `Staff`, `Manager`, `HRD`. |
| `password` | string | Password login. |
| `manager_nik` | string nullable | NIK atasan langsung. |

Relasi:

- `User hasMany Request` melalui `requests.user_nik`.
- `User belongsTo User` sebagai `manager` melalui `manager_nik`.
- `User hasMany User` sebagai `bawahan` melalui `manager_nik`.

#### `leaves`

Model `App\Models\Leave` menunjukkan tabel jenis cuti.

| Kolom | Tipe Logis | Peran |
|---|---|---|
| `id` | integer/bigint, primary key | Identitas jenis cuti. |
| `namaCuti` | string | Nama jenis cuti, misalnya cuti tahunan atau cuti tertentu. |
| `kategori` | string | Kategori bisnis, dipakai untuk laporan: `Normatif` atau non-normatif. |
| `maxHari` | integer nullable | Batas maksimal durasi cuti untuk jenis tertentu. |

Relasi:

- `Leave hasMany Request` melalui `requests.leave_id`.

#### `requests`

Model `App\Models\Request` adalah inti workflow cuti.

| Kolom | Tipe Logis | Peran |
|---|---|---|
| `id` | integer/bigint, primary key | Identitas pengajuan cuti. |
| `user_nik` | string | Pemohon cuti. |
| `leave_id` | integer | Jenis cuti yang dipilih. |
| `tglRequest` | datetime | Waktu pengajuan dibuat. |
| `tglMulai` | date/datetime | Tanggal mulai cuti. |
| `tglSelesai` | date/datetime | Tanggal selesai cuti. |
| `alasan` | string/text | Alasan pengajuan. |
| `lampiran` | string nullable | Path atau URL lampiran dokumen. |
| `status` | string | Status proses: `PENDING`, `RECOMMENDED`, `APPROVED`, `REJECTED`, `CANCELLED`. |
| `manager_nik` | string nullable | Pihak yang merekomendasikan atau approver awal. |
| `tglApproval` | datetime nullable | Waktu approval/reject. |
| `catatanManager` | string/text nullable | Catatan penolakan dari manager/HRD. |

Relasi:

- `Request belongsTo User` melalui `user_nik`.
- `Request belongsTo Leave` melalui `leave_id`.
- `Request belongsTo User` sebagai `approver` melalui `manager_nik`.

#### `holidays`

Model `App\Models\Holiday` menunjukkan tabel hari libur nasional.

| Kolom | Tipe Logis | Peran |
|---|---|---|
| `tgl` | date, primary key | Tanggal libur nasional. |
| `deskripsi` | string | Nama atau keterangan libur. |

Relasi:

- Tidak terlihat relasi formal ke tabel lain, tetapi dipakai untuk validasi overlap tanggal cuti.

### Relasi Antar Tabel

| Dari | Ke | Jenis Relasi | Keterangan |
|---|---|---|---|
| `users.nik` | `requests.user_nik` | 1 ke banyak | Satu user bisa punya banyak pengajuan cuti. |
| `leaves.id` | `requests.leave_id` | 1 ke banyak | Satu jenis cuti bisa dipakai banyak pengajuan. |
| `users.nik` | `users.manager_nik` | self reference | Struktur atasan-bawahan organisasi. |
| `users.nik` | `requests.manager_nik` | 1 ke banyak | Menandai siapa yang merekomendasikan/menangani request. |

### Catatan Desain Skema

- `nik` dipakai sebagai primary key bisnis untuk tabel `users`, bukan kolom auto increment standar Laravel.
- `holiday.tgl` juga dipakai sebagai primary key, sehingga tabel ini bersifat tanggal-sentris.
- Model dan controller sangat bergantung pada naming field campuran bahasa Indonesia dan camelCase seperti `tglMasuk`, `namaCuti`, `catatanManajer`.

## 3. Roles dan Permissions

### Role yang Terlihat di Codebase

| Role | Bukti di Code | Fungsi Bisnis |
|---|---|---|
| `Staff` | Dipakai pada form CRUD karyawan dan sidebar | Pengaju cuti biasa. |
| `Manager` | Dipakai di approval flow dan sidebar | Pemberi rekomendasi cuti untuk bawahan. |
| `HRD` | Dipakai di approval akhir, laporan, dan data karyawan | Approver akhir, admin data karyawan, dan pembuat laporan. |

### Permission Per Role

#### Staff

- Login menggunakan NIK dan password.
- Melihat dashboard pribadi.
- Melihat sisa cuti, riwayat cuti, dan libur nasional.
- Membuat pengajuan cuti.
- Mengunggah lampiran jika cuti bukan cuti tahunan.
- Membatalkan pengajuan yang masih memenuhi aturan waktu pembatalan.

#### Manager

- Melakukan semua kemampuan Staff, sejauh UI menampilkannya.
- Melihat menu Persetujuan.
- Melihat daftar permintaan rekomendasi untuk bawahan langsung.
- Memberi rekomendasi atau menolak permintaan cuti.
- Tidak terlihat punya akses ke laporan atau manajemen karyawan.

#### HRD

- Memiliki semua hak Manager.
- Melihat daftar approval lanjutan untuk request yang sudah direkomendasikan.
- Memberi persetujuan akhir atau penolakan.
- Mengakses Data Karyawan.
- Mengakses Laporan.
- Mengelola struktur organisasi dan data master karyawan.

### Pengamanan Akses yang Ditulis di Backend

- `PersetujuanController` menolak role selain `Manager` dan `HRD`.
- `LaporanController` membatasi akses ke `HRD` saja.
- `DataKaryawanController` membatasi akses ke `HRD` saja.
- Frontend juga menyembunyikan menu berdasarkan role dari `localStorage`, tetapi kontrol utama tetap ada di backend.

## 4. Alur Kerja Bisnis

### Alur Pengajuan Cuti dari Sudut Pandang User

1. User login dengan NIK dan password.
2. Frontend menyimpan token dan data user di `localStorage`.
3. User membuka halaman Pengajuan.
4. Vue melakukan fetch ke endpoint pengajuan untuk mengambil:
   - daftar jenis cuti,
   - daftar hari libur,
   - sisa cuti tahunan.
5. User memilih jenis cuti, tanggal mulai, tanggal selesai, alasan, dan lampiran jika diperlukan.
6. Frontend melakukan validasi awal di browser:
   - tanggal selesai tidak boleh sebelum tanggal mulai,
   - durasi tidak melewati kuota/limit jenis cuti,
   - tanggal tidak boleh overlap dengan hari libur,
   - lampiran wajib untuk jenis cuti tertentu.
7. Saat submit, Vue mengirim `multipart/form-data` ke backend.
8. Backend memvalidasi ulang di server.
9. Jika lolos validasi, backend menyimpan request dengan status `PENDING`.
10. Request masuk ke dashboard dan halaman persetujuan untuk approver yang berwenang.

### Alur Persetujuan dari Sudut Pandang Manager/HRD

1. Approver login dan membuka halaman Persetujuan.
2. Frontend memanggil endpoint persetujuan untuk mengambil dua kelompok data:
   - `recommendationRequests` untuk status `PENDING`,
   - `approvalRequests` untuk status `RECOMMENDED` khusus HRD.
3. Manager melihat request bawahan langsung dan memberi tindakan:
   - recommend,
   - reject.
4. Jika `recommend`, status request berubah menjadi `RECOMMENDED` dan `manager_nik` diisi dengan NIK approver.
5. Jika `reject`, status berubah menjadi `REJECTED` dan catatan penolakan disimpan.
6. HRD kemudian melihat request yang sudah direkomendasikan.
7. HRD memberi tindakan akhir:
   - approve,
   - reject.
8. Jika `approve`, status berubah menjadi `APPROVED` dan timestamp approval diisi.
9. Jika `reject`, status berubah menjadi `REJECTED` dan catatan disimpan.
10. Setelah request approved, kuota cuti tahunan dihitung dari request yang sudah disetujui ketika dashboard atau laporan dibuka.

### Pemotongan Kuota Cuti

Pemotongan kuota tidak disimpan sebagai angka terpisah di database pada codebase yang terlihat. Logikanya dihitung on-the-fly:

- kuota awal cuti tahunan dianggap 12 hari,
- cuti yang sudah approved dan bertipe tahunan dijumlahkan berdasarkan selisih `tglMulai` dan `tglSelesai`,
- sisa cuti = kuota awal - cuti terpakai,
- hasilnya dipakai di dashboard, pengajuan, dan laporan.

### Mekanisme Pembatalan Cuti

1. User membuka detail pengajuan di dashboard.
2. User menekan batal jika status masih aktif.
3. Backend menolak pembatalan jika:
   - pengajuan milik orang lain,
   - status sudah `REJECTED` atau `CANCELLED`,
   - tanggal cuti sudah lewat,
   - pembatalan dilakukan pada hari H setelah jam 08:00.
4. Jika valid, status diubah menjadi `CANCELLED`.

## 5. Integrasi API dan State Management

### Ringkasan Endpoint API Utama

| Method | Endpoint | Fungsi | Role |
|---|---|---|---|
| POST | `/api/login` | Login menggunakan NIK dan password, lalu menerima token Sanctum. | Public |
| GET | `/api/me` | Mengambil data user login aktif. | Auth |
| POST | `/api/logout` | Logout dan menghapus token aktif. | Auth |
| GET | `/api/dashboard` | Mengambil statistik cuti, riwayat, dan hari libur. | Auth |
| GET | `/api/pengajuan` | Mengambil form data cuti, daftar libur, dan sisa cuti. | Auth |
| POST | `/api/pengajuan` | Submit pengajuan cuti baru. | Auth |
| PATCH | `/api/pengajuan/{id}/cancel` | Membatalkan pengajuan cuti. | Auth |
| GET | `/api/persetujuan` | Mengambil daftar request untuk rekomendasi dan approval. | Manager/HRD |
| PATCH | `/api/persetujuan/{id}` | Memberi recommend, approve, atau reject pada request. | Manager/HRD |
| GET | `/api/laporan` | Mengambil data laporan cuti dan rekap kuota. | HRD |
| GET | `/api/laporan/export` | Mengunduh laporan PDF. | HRD |
| GET | `/api/data-karyawan/struktur` | Mengambil struktur organisasi. | HRD |
| GET | `/api/data-karyawan` | Mengambil daftar karyawan dan daftar manager. | HRD |
| POST | `/api/data-karyawan` | Menambah karyawan baru. | HRD |
| PUT | `/api/data-karyawan/{nik}` | Memperbarui data karyawan. | HRD |
| DELETE | `/api/data-karyawan/{nik}` | Menghapus data karyawan. | HRD |

### Cara Vue Melakukan Fetching Data

- Semua request memakai instance Axios di `frontend/src/lib/axios.js`.
- Base URL mengarah ke Laravel API lokal.
- Interceptor request menambahkan `Authorization: Bearer <token>` dari `localStorage`.
- Setiap halaman melakukan fetch di `onMounted`.
- Setelah aksi yang mengubah data, halaman melakukan refetch agar UI sinkron.

### Pola State Management Frontend

#### Login

- `LoginView.vue` memanggil POST `/login`.
- Jika sukses, token dan user disimpan ke `localStorage`.
- Router diarahkan ke dashboard.

#### Dashboard

- State lokal:
  - `leaveStats`
  - `leaveHistory`
  - `nationalHolidays`
- Data diambil dari GET `/dashboard`.

#### Pengajuan

- State lokal:
  - daftar jenis cuti,
  - daftar holiday,
  - sisa cuti,
  - form reactive,
  - error validasi server,
  - indikator validasi durasi dan overlap.
- Validasi utama dilakukan dua lapis:
  - validasi browser untuk UX cepat,
  - validasi server untuk kepastian bisnis.

#### Persetujuan

- State lokal:
  - daftar rekomendasi,
  - daftar approval,
  - request yang dipilih,
  - form tindakan approval/reject.
- Tidak ada store global.

#### Data Karyawan

- State lokal untuk tabel, paging, search, modal form, dan error handling.
- Search memakai debounce sebelum fetch ulang.

#### Laporan

- State lokal untuk filter tanggal, departemen, summary, dan tab aktif.
- Export PDF menggunakan response blob dari endpoint export.

## Highlight Logika Kompleks

### 1. Perhitungan Sisa Cuti

Logika sisa cuti dilakukan di controller, bukan di database:

- kuota default = 12 hari,
- jika masa kerja kurang dari 1 tahun, kuota cuti tahunan dianggap 0 pada dashboard,
- cuti approved bertipe tahunan dijumlahkan dari durasi hari,
- sisa cuti dihitung dengan fungsi `max(0, kuota - terpakai)`.

Catatan penting:

- Penghitungan ini dilakukan berulang di beberapa controller, sehingga logika bisnis masih terduplikasi.
- Jika nanti ada perubahan aturan cuti, satu sumber kebenaran sebaiknya dibuat agar konsisten.

### 2. Validasi Tanggal dan Overlap Libur

Pada pengajuan:

- tanggal mulai harus hari ini atau setelahnya,
- tanggal selesai tidak boleh sebelum tanggal mulai,
- jika rentang tanggal mencakup hari libur, request ditolak,
- untuk cuti non-tahunan, lampiran wajib,
- untuk cuti tertentu, durasi tidak boleh melebihi `maxHari`.

### 3. Approval Dua Tahap

Status request mengalir sebagai berikut:

- `PENDING` → menunggu rekomendasi manager,
- `RECOMMENDED` → menunggu approval final HRD,
- `APPROVED` atau `REJECTED` → selesai,
- `CANCELLED` → dibatalkan pemohon.

Untuk HRD, sistem juga mendukung peer review ketika request berasal dari sesama HRD dan tidak punya manager langsung.

## Temuan Teknis Tambahan

- Route `/api/profile` ada di `routes/api.php`, tetapi file `ProfileController` tidak ditemukan di repo. Ini berpotensi menyebabkan error jika endpoint tersebut dipanggil.
- Frontend menyimpan role di `localStorage`, sehingga tampilan menu bergantung pada data client-side. Backend tetap menjadi kontrol otorisasi utama.
- Repo saat ini belum memperlihatkan migration bisnis untuk `leaves`, `requests`, dan `holidays`, jadi jika dokumentasi database resmi dibutuhkan, perlu mengambil schema langsung dari Supabase atau dump SQL produksi.

## Kesimpulan

Aplikasi ini sudah membentuk sistem cuti yang cukup lengkap dari sisi alur bisnis inti:

- login token-based,
- pengajuan cuti dengan validasi tanggal dan lampiran,
- approval bertingkat Manager lalu HRD,
- dashboard status dan riwayat cuti,
- laporan HRD dalam bentuk tabel dan PDF,
- manajemen struktur karyawan.

Namun, dari sisi repository yang tersedia masih ada beberapa celah dokumentasi dan implementasi:

- skema tabel bisnis tidak sepenuhnya terdokumentasi lewat migration,
- konfigurasi database Supabase belum tampak di `.env.example`,
- ada route yang mengarah ke controller yang tidak ditemukan,
- logika hitung cuti masih tersebar di beberapa controller.

Dokumen ini sebaiknya dipakai sebagai baseline analisis saat menyusun dokumentasi sistem atau saat menyiapkan refactor lanjutan.