# Sequence Diagram Sistem Informasi Cuti SC

Dokumen ini memuat sequence diagram untuk alur utama yang teridentifikasi pada codebase aplikasi **SC**. Seluruh diagram ditulis menggunakan sintaks Mermaid.js agar dapat langsung dipindahkan ke dokumentasi teknis atau laporan akademik.

## 1. Sequence Diagram Login

```mermaid
sequenceDiagram
    actor Pegawai
    participant Frontend as Vue Frontend
    participant AuthAPI as AuthController
    participant DB as Database

    Pegawai->>Frontend: Input NIK dan password
    Frontend->>AuthAPI: POST /api/login
    AuthAPI->>DB: Validasi kredensial user
    DB-->>AuthAPI: Data user valid
    AuthAPI-->>Frontend: access_token, token_type, user
    Frontend->>Frontend: Simpan token dan user ke localStorage
    Frontend-->>Pegawai: Redirect ke dashboard
```

## 2. Sequence Diagram Melihat Dashboard

```mermaid
sequenceDiagram
    actor Pegawai
    participant Frontend as Vue Frontend
    participant DashboardAPI as DashboardController
    participant DB as Database

    Pegawai->>Frontend: Buka halaman dashboard
    Frontend->>DashboardAPI: GET /api/dashboard
    DashboardAPI->>DB: Ambil data user, request cuti, dan holiday
    DB-->>DashboardAPI: Data dashboard
    DashboardAPI-->>Frontend: JSON leaveStats, leaveHistory, nationalHolidays
    Frontend-->>Pegawai: Tampilkan dashboard
```

## 3. Sequence Diagram Pengajuan Cuti

```mermaid
sequenceDiagram
    actor Pegawai
    participant Frontend as Vue Frontend
    participant LeaveAPI as PengajuanController
    participant DB as Database
    participant Storage as Supabase Storage

    Pegawai->>Frontend: Buka form pengajuan cuti
    Frontend->>LeaveAPI: GET /api/pengajuan
    LeaveAPI->>DB: Ambil daftar jenis cuti, holiday, dan sisa cuti
    DB-->>LeaveAPI: Data form pengajuan
    LeaveAPI-->>Frontend: JSON leaves, holidays, sisaCuti

    Pegawai->>Frontend: Isi form dan upload lampiran
    Frontend->>LeaveAPI: POST /api/pengajuan (multipart/form-data)
    LeaveAPI->>DB: Validasi leave, user, tanggal, kuota, holiday
    alt Lampiran diunggah
        LeaveAPI->>Storage: Simpan file lampiran
        Storage-->>LeaveAPI: Path file tersimpan
    end
    LeaveAPI->>DB: Simpan request dengan status PENDING
    DB-->>LeaveAPI: Data request tersimpan
    LeaveAPI-->>Frontend: Response sukses
    Frontend-->>Pegawai: Notifikasi pengajuan berhasil
```

## 4. Sequence Diagram Validasi Pengajuan Cuti

```mermaid
sequenceDiagram
    actor Pegawai
    participant Frontend as Vue Frontend
    participant LeaveAPI as PengajuanController
    participant DB as Database

    Pegawai->>Frontend: Klik submit pengajuan
    Frontend->>Frontend: Validasi awal di sisi client
    alt Validasi client gagal
        Frontend-->>Pegawai: Tampilkan pesan error form
    else Validasi client lolos
        Frontend->>LeaveAPI: Kirim request ke backend
        LeaveAPI->>DB: Cek jenis cuti, holiday, tanggal, dan kuota
        alt Validasi server gagal
            LeaveAPI-->>Frontend: 422 validation error
            Frontend-->>Pegawai: Tampilkan error dari server
        else Validasi server lolos
            LeaveAPI->>DB: Simpan request PENDING
            DB-->>LeaveAPI: Data tersimpan
            LeaveAPI-->>Frontend: Success response
            Frontend-->>Pegawai: Pengajuan berhasil dikirim
        end
    end
```

## 5. Sequence Diagram Rekomendasi Cuti oleh Atasan

```mermaid
sequenceDiagram
    actor Atasan
    participant Frontend as Vue Frontend
    participant ApprovalAPI as PersetujuanController
    participant DB as Database

    Atasan->>Frontend: Buka halaman persetujuan
    Frontend->>ApprovalAPI: GET /api/persetujuan
    ApprovalAPI->>DB: Ambil request status PENDING sesuai manager_nik
    DB-->>ApprovalAPI: Daftar request rekomendasi
    ApprovalAPI-->>Frontend: JSON recommendationRequests
    Frontend-->>Atasan: Tampilkan daftar request

    Atasan->>Frontend: Pilih request dan klik recommend/reject
    Frontend->>ApprovalAPI: PATCH /api/persetujuan/{id}
    ApprovalAPI->>DB: Validasi role dan status request
    alt Action = recommend
        ApprovalAPI->>DB: Update status menjadi RECOMMENDED
        ApprovalAPI->>DB: Simpan manager_nik sebagai approver
    else Action = reject
        ApprovalAPI->>DB: Update status menjadi REJECTED
        ApprovalAPI->>DB: Simpan catatan penolakan
    end
    DB-->>ApprovalAPI: Data diperbarui
    ApprovalAPI-->>Frontend: Response sukses
    Frontend-->>Atasan: Tampilkan notifikasi berhasil
```

## 6. Sequence Diagram Approval Akhir oleh HRD

```mermaid
sequenceDiagram
    actor HRD
    participant Frontend as Vue Frontend
    participant ApprovalAPI as PersetujuanController
    participant DB as Database

    HRD->>Frontend: Buka halaman persetujuan
    Frontend->>ApprovalAPI: GET /api/persetujuan
    ApprovalAPI->>DB: Ambil request PENDING dan RECOMMENDED
    DB-->>ApprovalAPI: Data request untuk HRD
    ApprovalAPI-->>Frontend: recommendationRequests dan approvalRequests
    Frontend-->>HRD: Tampilkan daftar persetujuan

    HRD->>Frontend: Pilih request RECOMMENDED dan klik approve/reject
    Frontend->>ApprovalAPI: PATCH /api/persetujuan/{id}
    ApprovalAPI->>DB: Validasi request dan role HRD
    alt Action = approve
        ApprovalAPI->>DB: Update status menjadi APPROVED
        ApprovalAPI->>DB: Simpan tglApproval
    else Action = reject
        ApprovalAPI->>DB: Update status menjadi REJECTED
        ApprovalAPI->>DB: Simpan catatan penolakan
    end
    DB-->>ApprovalAPI: Data tersimpan
    ApprovalAPI-->>Frontend: Response sukses
    Frontend-->>HRD: Tampilkan notifikasi berhasil
```

## 7. Sequence Diagram Pembatalan Cuti

```mermaid
sequenceDiagram
    actor Pegawai
    participant Frontend as Vue Frontend
    participant CancelAPI as PengajuanController
    participant DB as Database

    Pegawai->>Frontend: Buka detail pengajuan
    Pegawai->>Frontend: Klik batalkan cuti
    Frontend->>CancelAPI: PATCH /api/pengajuan/{id}/cancel
    CancelAPI->>DB: Ambil data request berdasarkan id
    CancelAPI->>DB: Validasi kepemilikan dan status

    alt Tidak memenuhi syarat pembatalan
        CancelAPI-->>Frontend: 400/403 error
        Frontend-->>Pegawai: Tampilkan pesan gagal membatalkan
    else Memenuhi syarat pembatalan
        CancelAPI->>DB: Update status menjadi CANCELLED
        DB-->>CancelAPI: Data diperbarui
        CancelAPI-->>Frontend: Response sukses
        Frontend-->>Pegawai: Tampilkan notifikasi berhasil
    end
```

## 8. Sequence Diagram Laporan Cuti

```mermaid
sequenceDiagram
    actor HRD
    participant Frontend as Vue Frontend
    participant ReportAPI as LaporanController
    participant DB as Database

    HRD->>Frontend: Buka halaman laporan
    Frontend->>ReportAPI: GET /api/laporan?filter
    ReportAPI->>DB: Ambil data approved request dan data user
    DB-->>ReportAPI: Data laporan
    ReportAPI-->>Frontend: JSON summary, absenceLog, quotaRecap
    Frontend-->>HRD: Tampilkan tabel laporan

    HRD->>Frontend: Klik export PDF
    Frontend->>ReportAPI: GET /api/laporan/export
    ReportAPI->>DB: Ambil data sesuai filter
    ReportAPI-->>Frontend: File PDF blob
    Frontend-->>HRD: Unduhan file PDF dimulai
```

## 9. Sequence Diagram Manajemen Data Karyawan

```mermaid
sequenceDiagram
    actor HRD
    participant Frontend as Vue Frontend
    participant EmployeeAPI as DataKaryawanController
    participant DB as Database

    HRD->>Frontend: Buka halaman data karyawan
    Frontend->>EmployeeAPI: GET /api/data-karyawan
    EmployeeAPI->>DB: Ambil data karyawan dan daftar manager
    DB-->>EmployeeAPI: Data karyawan
    EmployeeAPI-->>Frontend: JSON karyawan dan managers
    Frontend-->>HRD: Tampilkan tabel data karyawan

    HRD->>Frontend: Tambah / edit / hapus data karyawan
    Frontend->>EmployeeAPI: POST / PUT / DELETE /api/data-karyawan
    EmployeeAPI->>DB: Validasi role HRD dan simpan perubahan
    DB-->>EmployeeAPI: Data tersimpan
    EmployeeAPI-->>Frontend: Response sukses
    Frontend-->>HRD: Tampilkan notifikasi berhasil
```

## 10. Catatan Implementasi

- Diagram login, dashboard, pengajuan cuti, approval, pembatalan, laporan, dan data karyawan di atas disusun berdasarkan endpoint dan alur controller yang tersedia pada codebase.
- Jika ingin digunakan di dokumen Word, setiap code block Mermaid dapat ditempel langsung ke editor yang mendukung Mermaid atau dirender terlebih dahulu menjadi gambar.
- Alur approval dipisah menjadi dua sequence diagram karena implementasi backend membedakan rekomendasi oleh Atasan dan approval akhir oleh HRD.
