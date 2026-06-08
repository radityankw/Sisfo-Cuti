# BAB II
# TINJAUAN PUSTAKA DAN ANALISIS SISTEM

## 2.1 Gambaran Umum Sistem

Sistem informasi cuti pada proyek SC merupakan aplikasi berbasis web yang dirancang untuk mendukung proses administrasi pengajuan cuti karyawan secara terstruktur. Sistem ini memfasilitasi aktivitas utama berupa autentikasi pengguna, pengajuan cuti, proses rekomendasi dan persetujuan, pengelolaan data karyawan, serta penyusunan laporan ketidakhadiran dan rekap kuota cuti.

Arsitektur aplikasi menerapkan pola **decoupled** atau pemisahan frontend dan backend. Pendekatan ini memungkinkan antarmuka pengguna dikembangkan secara independen menggunakan Vue.js, sementara logika bisnis, otorisasi, dan penyediaan layanan data dikelola oleh Laravel sebagai API.

## 2.2 Arsitektur Aplikasi

### 2.2.1 Lapisan Frontend

Frontend dibangun menggunakan Vue.js 3 dan berfungsi sebagai lapisan presentasi. Lapisan ini bertanggung jawab atas:

- rendering halaman aplikasi,
- navigasi antarmuka menggunakan Vue Router,
- pengelolaan state lokal berbasis Composition API,
- komunikasi HTTP ke backend menggunakan Axios,
- validasi awal pada sisi klien untuk meningkatkan responsivitas interaksi.

### 2.2.2 Lapisan Backend

Backend dibangun menggunakan Laravel 12 dan berfungsi sebagai lapisan layanan serta pemrosesan logika bisnis. Backend menangani:

- autentikasi berbasis token menggunakan Laravel Sanctum,
- validasi data pengajuan cuti,
- kontrol akses berdasarkan role,
- perhitungan sisa cuti dan durasi pengajuan,
- pengolahan data laporan,
- pengelolaan data karyawan.

### 2.2.3 Integrasi Storage

Selain database, sistem juga menggunakan storage yang kompatibel dengan S3 untuk menyimpan lampiran pengajuan cuti. Berdasarkan konfigurasi filesystem, disk `supabase` digunakan sebagai media penyimpanan file lampiran.

## 2.3 Struktur Direktori

### 2.3.1 Struktur Backend

| Direktori / File | Fungsi |
|---|---|
| `backend/app/Http/Controllers/` | Menyimpan controller untuk login, dashboard, pengajuan, persetujuan, laporan, dan data karyawan. |
| `backend/app/Models/` | Menyimpan model Eloquent dan definisi relasi antar entitas. |
| `backend/routes/api.php` | Menyimpan definisi endpoint API yang diakses oleh frontend. |
| `backend/resources/views/pdf/` | Menyimpan template PDF untuk laporan. |
| `backend/config/filesystems.php` | Menyimpan konfigurasi disk storage, termasuk `supabase`. |
| `backend/database/migrations/` | Menyimpan migration infrastruktur Laravel dan Sanctum yang tersedia pada repository. |

### 2.3.2 Struktur Frontend

| Direktori / File | Fungsi |
|---|---|
| `frontend/src/views/` | Menyimpan halaman utama aplikasi. |
| `frontend/src/router/index.js` | Menyimpan routing halaman dan guard autentikasi. |
| `frontend/src/lib/axios.js` | Menyimpan instance Axios terpusat dengan interceptor token. |
| `frontend/src/components/Sidebar.vue` | Menyimpan navigasi utama berdasarkan role pengguna. |
| `frontend/src/views/DataKaryawan/` | Menyimpan komponen struktur organisasi dan manajemen data karyawan. |

## 2.4 State Management pada Frontend

Implementasi frontend saat ini **tidak menggunakan Pinia maupun Vuex**. State dikelola secara lokal menggunakan Composition API Vue, terutama melalui `ref`, `reactive`, `computed`, `watch`, dan `onMounted`.

Secara praktis, pendekatan ini menghasilkan karakteristik berikut:

- state lebih sederhana dan mudah ditelusuri,
- tidak terdapat store global yang menjadi sumber kebenaran tunggal,
- data halaman diambil ulang ketika diperlukan setelah aksi CRUD atau approval,
- autentikasi disimpan pada `localStorage` dalam bentuk token dan profil pengguna.

## 2.5 Analisis Basis Data

### 2.5.1 Catatan Umum

Berdasarkan repository yang tersedia, migration yang terlihat hanya mencakup tabel infrastruktur Laravel dan Sanctum. Sementara itu, model dan controller mengindikasikan keberadaan tabel bisnis utama, yaitu `users`, `leaves`, `requests`, dan `holidays`.

Dengan demikian, analisis tabel di bagian ini disusun dari kombinasi model, relasi Eloquent, dan pemanggilan atribut pada controller.

### 2.5.2 Tabel `users`

Tabel `users` menyimpan data karyawan sekaligus akun login.

| Kolom | Keterangan |
|---|---|
| `nik` | Primary key bisnis dan identifier autentikasi. |
| `nama` | Nama lengkap karyawan. |
| `tglMasuk` | Tanggal masuk kerja, dipakai untuk evaluasi kelayakan cuti tahunan. |
| `departemen` | Departemen karyawan. |
| `role` | Peran pengguna dalam sistem, misalnya Staff, Manager, atau HRD. |
| `password` | Kata sandi login. |
| `manager_nik` | NIK atasan langsung. |

Relasi utama:

- satu user dapat memiliki banyak request cuti,
- satu user dapat memiliki satu manager,
- satu user dapat memiliki banyak bawahan.

### 2.5.3 Tabel `leaves`

Tabel `leaves` menyimpan master jenis cuti.

| Kolom | Keterangan |
|---|---|
| `id` | Primary key jenis cuti. |
| `namaCuti` | Nama jenis cuti. |
| `kategori` | Kategori cuti, digunakan dalam laporan. |
| `maxHari` | Batas maksimal durasi cuti untuk jenis tertentu. |

### 2.5.4 Tabel `requests`

Tabel `requests` menyimpan transaksi pengajuan cuti.

| Kolom | Keterangan |
|---|---|
| `id` | Primary key pengajuan. |
| `user_nik` | NIK pemohon cuti. |
| `leave_id` | Jenis cuti yang diajukan. |
| `tglRequest` | Waktu pengajuan dibuat. |
| `tglMulai` | Tanggal mulai cuti. |
| `tglSelesai` | Tanggal selesai cuti. |
| `alasan` | Alasan pengajuan cuti. |
| `lampiran` | Dokumen pendukung bila dibutuhkan. |
| `status` | Status proses pengajuan. |
| `manager_nik` | NIK approver atau pihak yang merekomendasikan. |
| `tglApproval` | Tanggal keputusan approval. |
| `catatanManager` | Catatan penolakan. |

### 2.5.5 Tabel `holidays`

Tabel `holidays` menyimpan hari libur nasional yang digunakan untuk validasi konflik tanggal pengajuan.

| Kolom | Keterangan |
|---|---|
| `tgl` | Tanggal libur nasional dan primary key tabel. |
| `deskripsi` | Keterangan hari libur. |

## 2.6 Relasi Antar Entitas

| Sumber | Target | Jenis Relasi | Keterangan |
|---|---|---|---|
| `users.nik` | `requests.user_nik` | One-to-Many | Satu user dapat membuat banyak pengajuan cuti. |
| `leaves.id` | `requests.leave_id` | One-to-Many | Satu jenis cuti dapat dipakai banyak pengajuan. |
| `users.nik` | `users.manager_nik` | Self-Reference | Menyusun struktur atasan dan bawahan. |
| `users.nik` | `requests.manager_nik` | One-to-Many | Menandai approver awal atau pemberi rekomendasi. |

## 2.7 Hak Akses Berdasarkan Role

| Role | Hak Akses |
|---|---|
| Staff | Melihat dashboard, mengajukan cuti, dan membatalkan pengajuan yang masih valid. |
| Manager | Seluruh hak Staff ditambah memberi rekomendasi atau menolak pengajuan bawahan. |
| HRD | Seluruh hak Manager ditambah menyetujui akhir, mengelola data karyawan, melihat laporan, dan melihat struktur organisasi. |

## 2.8 Ringkasan API Utama

| Method | Endpoint | Fungsi |
|---|---|---|
| POST | `/api/login` | Autentikasi pengguna dan penerbitan token. |
| GET | `/api/dashboard` | Mengambil data dashboard. |
| GET | `/api/pengajuan` | Mengambil data form pengajuan dan sisa cuti. |
| POST | `/api/pengajuan` | Menyimpan pengajuan cuti. |
| PATCH | `/api/pengajuan/{id}/cancel` | Membatalkan pengajuan cuti. |
| GET | `/api/persetujuan` | Mengambil daftar request untuk persetujuan. |
| PATCH | `/api/persetujuan/{id}` | Memproses rekomendasi, persetujuan, atau penolakan. |
| GET | `/api/laporan` | Mengambil data laporan cuti. |
| GET | `/api/laporan/export` | Mengekspor laporan ke PDF. |
| GET | `/api/data-karyawan` | Mengambil data karyawan dan daftar manager. |
| GET | `/api/data-karyawan/struktur` | Mengambil struktur organisasi. |

## 2.9 Kesimpulan Bab II

Berdasarkan analisis struktur dan implementasi kode, sistem ini memiliki pemisahan peran yang jelas antara frontend dan backend, struktur entitas yang cukup terdefinisi, serta pola otorisasi berbasis role yang sesuai untuk sistem cuti perusahaan. Namun, dokumentasi skema database bisnis secara eksplisit belum tersedia pada migration yang ada di repository.
