# Sisfo-Cuti: Sistem Informasi Pengajuan Cuti

Selamat datang di repositori **Sisfo-Cuti**! Aplikasi ini dibangun dengan arsitektur terpisah (*decoupled*) memanfaatkan kekuatan **Vue.js** pada sisi *frontend*, **Laravel** sebagai *robust backend API*, serta didukung penuh oleh **Supabase (PostgreSQL)** untuk manajemen data yang persisten dan *real-time*.

Aplikasi ini disiapkan khusus untuk keperluan demo pada kegiatan Expo, memungkinkan pengunjung untuk menguji langsung alur pengajuan cuti secara end-to-end dengan sangat mudah.

---

## 🚀 Link Akses Live Demo
Pengunjung Expo dapat langsung mencoba antarmuka dan fitur aplikasi melalui tautan berikut:
👉 **[Akses Sisfo-Cuti di Vercel](https://sisfocuti.vercel.app/)**

---

## 🔐 Akun Uji Coba (Kredensial per Role)
Sistem ini mengimplementasikan *Role-Based Access Control* (RBAC). Untuk merasakan pengalaman penuh mulai dari pengajuan oleh staf hingga persetujuan dari manajemen, silakan gunakan akun simulasi di bawah ini:

| No | Role / Peran | NIK (Username) | Password | Akses Fitur Utama |
|---|---|---|---|---|
| 1 | **Karyawan** | `STF001` | `password` | Mengajukan cuti baru, memantau sisa kuota cuti tahunan, membatalkan pengajuan (*cancel request*), melihat riwayat personal. |
| 2 | **Atasan / Manajer** | `MGR001` | `password` | Validasi pengajuan tim bawahannya, memberikan persetujuan (*approve*) atau penolakan (*reject*). |
| 3 | **HRD / Admin** | `HRD001` | `password` | Mengelola data induk karyawan, alokasi/pembaruan kuota cuti, serta memantau dan mencetak dokumen laporan rekapitulasi cuti global. |

---

## 🛠️ Arsitektur & Teknologi Utama
- **Frontend App:** Vue.js (Vite), Axios untuk interkoneksi API, Tailwind CSS untuk antarmuka yang responsif (di-deploy menggunakan infrastruktur serverless **Vercel**).
- **Backend API:** Laravel Framework, memanfaatkan JWT/Sanctum untuk pengamanan *endpoint* (di-deploy ke **Railway**).
- **Database Engine:** Supabase PostgreSQL Cloud Database server.

Terima kasih telah berkunjung ke stan Expo kami! 
