# Sistem Cuti - SC (Separated API + SPA)

Aplikasi manajemen cuti karyawan dengan arsitektur terpisah antara Backend API (Laravel) dan Frontend SPA (Vue.js).

## 📋 Daftar Isi

- [Arsitektur](#arsitektur)
- [Teknologi](#teknologi)
- [Prasyarat](#prasyarat)
- [Instalasi](#instalasi)
  - [Backend Setup](#backend-setup)
  - [Frontend Setup](#frontend-setup)
- [Konfigurasi](#konfigurasi)
- [Menjalankan Aplikasi](#menjalankan-aplikasi)
- [Fitur](#fitur)
- [Struktur Folder](#struktur-folder)
- [Troubleshooting](#troubleshooting)

---

## 🏗️ Arsitektur

Aplikasi ini menggunakan arsitektur **Separated Backend + Frontend**:

```
┌─────────────────┐         API Request         ┌─────────────────┐
│                 │ ◄────────────────────────── │                 │
│  Laravel API    │                             │   Vue 3 SPA     │
│  (Backend)      │ ─────────────────────────► │   (Frontend)    │
│                 │      JSON Response          │                 │
└────────┬────────┘                             └─────────────────┘
         │
         │
    ┌────▼────┐
    │PostgreSQL│
    │(Supabase)│
    └─────────┘
```

- **Backend**: Laravel 12 REST API dengan autentikasi Sanctum
- **Frontend**: Vue 3 + Vue Router + Axios
- **Database**: PostgreSQL (Supabase)
- **Storage**: Supabase Storage (S3-compatible)

---

## 💻 Teknologi

### Backend
- **Laravel** 12.x
- **PHP** ^8.2
- **Laravel Sanctum** - API Authentication
- **DomPDF** - PDF Generation
- **Supabase** - Database & Storage
- **Flysystem S3** - Cloud Storage Driver

### Frontend
- **Vue.js** 3.x
- **Vue Router** 4.x
- **Axios** - HTTP Client
- **Tailwind CSS** 3.x
- **Vite** - Build Tool

---

## ✅ Prasyarat

Pastikan sudah terinstall:

- **PHP** >= 8.2
- **Composer** (Package Manager PHP)
- **Node.js** >= 18.x
- **npm** atau **yarn**
- **Git**
- **PostgreSQL Client** (opsional, untuk debug database)

---

## 🚀 Instalasi

### Backend Setup

#### 1. Masuk ke folder backend
```bash
cd sc/backend
```

#### 2. Install dependencies PHP
```bash
composer install
```

**Dependencies utama yang terinstall:**
- `laravel/framework` - Framework Laravel
- `laravel/sanctum` - API Authentication
- `barryvdh/laravel-dompdf` - PDF Generator
- `league/flysystem-aws-s3-v3` - S3 Storage Driver

#### 3. Copy environment file
```bash
copy .env.example .env
```

#### 4. Generate Application Key
```bash
php artisan key:generate
```

#### 5. Konfigurasi Database & Storage

Edit file `.env` dengan konfigurasi Supabase Anda:

```env
# Database Configuration
DB_CONNECTION=pgsql
DB_HOST=<your-supabase-db-host>
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=<your-password>

# Supabase Storage Configuration (S3 Compatible)
AWS_ACCESS_KEY_ID=<your-supabase-access-key>
AWS_SECRET_ACCESS_KEY=<your-supabase-secret-key>
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=pkl-storage
AWS_ENDPOINT=https://<project-id>.storage.supabase.co/storage/v1/s3
AWS_URL=https://<project-id>.supabase.co/storage/v1/object/public/pkl-storage
AWS_USE_PATH_STYLE_ENDPOINT=true

# App Configuration
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:5173

# Session & Sanctum
SESSION_DRIVER=cookie
SANCTUM_STATEFUL_DOMAINS=localhost:5173
```

#### 6. Jalankan migrasi database
```bash
php artisan migrate
```

#### 7. (Opsional) Seed data dummy
```bash
php artisan db:seed
```

#### 8. Clear cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

### Frontend Setup

#### 1. Masuk ke folder frontend
```bash
cd sc/frontend
```

#### 2. Install dependencies Node.js
```bash
npm install
```

**Dependencies utama yang terinstall:**
- `vue` - Framework Vue.js
- `vue-router` - Routing
- `axios` - HTTP Client
- `tailwindcss` - CSS Framework
- `@vitejs/plugin-vue` - Vite plugin untuk Vue

#### 3. Konfigurasi API Base URL

Edit file `src/lib/axios.js` untuk memastikan URL backend benar:

```javascript
const api = axios.create({
    baseURL: 'http://localhost:8000/api', // Sesuaikan dengan backend URL
    withCredentials: true,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    }
});
```

---

## ⚙️ Konfigurasi

### CORS Configuration (Backend)

File: `backend/bootstrap/app.php`

Pastikan middleware CORS sudah dikonfigurasi untuk menerima request dari frontend:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->api(prepend: [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    ]);
    
    $middleware->statefulApi();
})
```

### Sanctum Configuration

File: `backend/config/sanctum.php`

```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost:5173')),
```

---

## ▶️ Menjalankan Aplikasi

### Development Mode

Buka **2 terminal** terpisah:

#### Terminal 1 - Backend
```bash
cd sc/backend
php artisan serve
```
Backend akan berjalan di: `http://localhost:8000`

#### Terminal 2 - Frontend
```bash
cd sc/frontend
npm run dev
```
Frontend akan berjalan di: `http://localhost:5173`

### Production Build

#### Backend
```bash
cd backend
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Frontend
```bash
cd frontend
npm run build
```

File production akan ada di folder `frontend/dist`

---

## 🎯 Fitur

### 1. **Autentikasi**
- Login dengan NIK dan Password
- Token-based authentication (Laravel Sanctum)
- Session management

### 2. **Dashboard**
- Statistik cuti karyawan
- Kuota cuti tersisa
- Libur nasional upcoming

### 3. **Pengajuan Cuti** (Staff)
- Form pengajuan cuti
- Upload lampiran ke Supabase Storage
- Lihat history pengajuan
- Batalkan pengajuan (status PENDING)

### 4. **Persetujuan Cuti** (Manager & HRD)
- **Manager**: Memberikan rekomendasi
- **HRD**: Approve final
- Lihat lampiran pengajuan
- Tolak dengan catatan

### 5. **Laporan Cuti** (HRD Only)
- Filter berdasarkan periode & departemen
- 2 Tab: Riwayat Ketidakhadiran & Rekapitulasi Kuota
- Export to PDF (Landscape)
- Summary cards (Normatif & Non-Normatif)

### 6. **Data Karyawan** (HRD Only)
- CRUD Karyawan
- Struktur Organisasi (Tree View)
- Filter & Search

---

## 📁 Struktur Folder

```
sc/
├── backend/                    # Laravel API
│   ├── app/
│   │   ├── Http/
│   │   │   └── Controllers/    # API Controllers
│   │   └── Models/             # Eloquent Models
│   ├── config/                 # Configuration files
│   ├── database/
│   │   ├── migrations/         # Database migrations
│   │   └── seeders/            # Database seeders
│   ├── public/
│   │   └── images/             # Logo untuk PDF
│   ├── resources/
│   │   └── views/
│   │       └── pdf/            # PDF Templates (Blade)
│   ├── routes/
│   │   └── api.php             # API Routes
│   └── .env                    # Environment variables
│
└── frontend/                   # Vue.js SPA
    ├── public/
    │   └── images/             # Static assets
    ├── src/
    │   ├── assets/             # CSS, fonts, etc.
    │   ├── components/         # Vue components
    │   │   └── Sidebar.vue
    │   ├── lib/
    │   │   └── axios.js        # Axios instance
    │   ├── router/
    │   │   └── index.js        # Vue Router
    │   ├── views/              # Page components
    │   │   ├── DashboardView.vue
    │   │   ├── Pengajuan.vue
    │   │   ├── Persetujuan.vue
    │   │   ├── Laporan.vue
    │   │   └── DataKaryawan/
    │   ├── App.vue
    │   └── main.js             # Entry point
    └── package.json
```

---

## 🔧 Troubleshooting

### 1. **CORS Error di Browser**
**Masalah**: Frontend tidak bisa hit API backend

**Solusi**:
```bash
# Di backend
php artisan config:clear
php artisan cache:clear

# Pastikan .env sudah benar:
SANCTUM_STATEFUL_DOMAINS=localhost:5173
FRONTEND_URL=http://localhost:5173
```

### 2. **PDF Export Gagal**
**Masalah**: Error saat download PDF

**Solusi**:
```bash
# Pastikan dompdf terinstall
composer require barryvdh/laravel-dompdf

# Clear cache
php artisan config:clear

# Pastikan logo.png ada di public/images/
```

### 3. **Lampiran Tidak Muncul**
**Masalah**: Blank page saat buka lampiran

**Solusi**:
- Pastikan `AWS_URL` di `.env` sudah benar
- Format URL: `https://<project>.supabase.co/storage/v1/object/public/<bucket>`
- Pastikan file di Supabase Storage bersifat **public**

### 4. **Unauthorized Error**
**Masalah**: 401 setelah login

**Solusi**:
```javascript
// Di frontend/src/lib/axios.js
// Pastikan token disimpan di header
api.interceptors.request.use((config) => {
    const token = localStorage.getItem('token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});
```

### 5. **Database Migration Error**
**Masalah**: Error saat migrate

**Solusi**:
```bash
# Drop semua table dan migrate ulang
php artisan migrate:fresh

# Atau rollback dulu
php artisan migrate:rollback
php artisan migrate
```

---

## 📝 API Endpoints

### Authentication
- `POST /api/login` - Login
- `POST /api/logout` - Logout
- `GET /api/me` - Get current user

### Dashboard
- `GET /api/dashboard` - Get dashboard data

### Pengajuan Cuti
- `GET /api/pengajuan` - Get all requests + form data
- `POST /api/pengajuan` - Submit new request
- `PATCH /api/pengajuan/{id}/cancel` - Cancel request

### Persetujuan
- `GET /api/persetujuan` - Get pending approvals
- `PATCH /api/persetujuan/{id}` - Approve/Reject

### Laporan
- `GET /api/laporan` - Get report data
- `GET /api/laporan/export` - Download PDF

### Data Karyawan
- `GET /api/data-karyawan` - Get all employees
- `POST /api/data-karyawan` - Create employee
- `PUT /api/data-karyawan/{nik}` - Update employee
- `DELETE /api/data-karyawan/{nik}` - Delete employee
- `GET /api/data-karyawan/struktur` - Get organization tree

---

## 👥 Role & Permission

| Role | Dashboard | Pengajuan | Persetujuan | Laporan | Data Karyawan |
|------|-----------|-----------|-------------|---------|---------------|
| **Staff** | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Manager** | ✅ | ✅ | ✅ (Rekomendasi) | ❌ | ❌ |
| **HRD** | ✅ | ✅ | ✅ (Final Approval) | ✅ | ✅ |

---

## 📦 Package List

### Backend (composer.json)
```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0",
        "laravel/sanctum": "^4.0",
        "barryvdh/laravel-dompdf": "^3.1",
        "league/flysystem-aws-s3-v3": "3.0"
    }
}
```

### Frontend (package.json)
```json
{
    "dependencies": {
        "vue": "^3.4.0",
        "vue-router": "^4.2.0",
        "axios": "^1.6.0"
    },
    "devDependencies": {
        "@vitejs/plugin-vue": "^5.0.0",
        "tailwindcss": "^3.4.0",
        "vite": "^5.0.0"
    }
}
```

---

## 📄 License

MIT License - Feel free to use this project.

---

## 👨‍💻 Developer

Developed for PKL Project - PT. Menara Kudus Indonesia

---

**Last Updated**: February 2026
