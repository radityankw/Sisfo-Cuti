# Software Requirements Specification (SRS)
# Sistem Informasi Cuti (sisfo-cuti)

Dokumen ini merupakan draf Software Requirements Specification (SRS) untuk aplikasi **sisfo-cuti** berdasarkan analisis struktur repository dan alur kerja sistem yang telah dilakukan sebelumnya. SRS ini disusun dalam format ringkas namun formal agar dapat digunakan sebagai dasar dokumentasi teknis, pengembangan lanjutan, maupun penyelarasan kebutuhan bisnis.

## 1. Deskripsi Umum Sistem

### 1.1 Tujuan Sistem

Sistem informasi cuti bertujuan untuk mendukung proses administrasi cuti karyawan secara terpusat, terukur, dan terdokumentasi. Aplikasi ini menyediakan sarana bagi pegawai untuk mengajukan cuti, bagi atasan untuk memberikan rekomendasi, dan bagi admin/HRD untuk melakukan persetujuan akhir serta pengelolaan data pendukung.

Secara umum, sistem dirancang untuk:

- mempercepat proses pengajuan dan persetujuan cuti,
- mengurangi kesalahan administrasi manual,
- menjaga konsistensi data cuti karyawan,
- menyediakan riwayat dan laporan yang dapat ditelusuri,
- memastikan keputusan cuti mengikuti aturan bisnis perusahaan.

### 1.2 Ruang Lingkup Sistem

Ruang lingkup sistem mencakup proses-proses berikut:

- autentikasi pengguna berbasis NIK dan password,
- pengajuan cuti oleh pegawai,
- validasi pengajuan cuti berdasarkan tanggal, kuota, dan lampiran,
- proses rekomendasi oleh atasan langsung,
- proses persetujuan akhir oleh HRD atau admin yang berwenang,
- pembatalan pengajuan cuti yang masih memenuhi syarat,
- manajemen data karyawan,
- penyusunan laporan cuti dan rekap kuota,
- penyajian dashboard dan riwayat cuti.

Sistem ini **tidak** mencakup pengelolaan payroll, absensi kehadiran harian, atau modul keuangan lainnya.

### 1.3 Karakteristik Solusi

Implementasi aplikasi menunjukkan karakteristik berikut:

- **Frontend** dibangun menggunakan Vue.js sebagai SPA.
- **Backend** dibangun menggunakan Laravel sebagai REST API.
- **Autentikasi** menggunakan Laravel Sanctum dengan token berbasis Bearer.
- **Storage lampiran** menggunakan penyimpanan yang kompatibel dengan Supabase Storage.
- **Database** memanfaatkan entitas bisnis cuti seperti user, jenis cuti, request cuti, dan holiday.

## 2. Kebutuhan Fungsional

Bagian ini mendeskripsikan kebutuhan fungsional berdasarkan peran pengguna. Setiap kebutuhan diberi kode agar mudah ditelusuri pada tahap implementasi maupun pengujian.

### 2.1 Kebutuhan Fungsional untuk Pegawai

| ID | Kebutuhan |
|---|---|
| FR-01 | Sistem harus memungkinkan pegawai melakukan login menggunakan NIK dan password yang valid. |
| FR-02 | Sistem harus menampilkan dashboard pegawai yang memuat sisa cuti, riwayat pengajuan, dan informasi libur nasional. |
| FR-03 | Sistem harus menampilkan daftar jenis cuti yang dapat diajukan oleh pegawai. |
| FR-04 | Sistem harus menampilkan sisa kuota cuti tahunan sebelum pegawai mengajukan cuti. |
| FR-05 | Sistem harus memungkinkan pegawai mengajukan cuti dengan mengisi jenis cuti, tanggal mulai, tanggal selesai, alasan, dan lampiran jika diperlukan. |
| FR-06 | Sistem harus memvalidasi tanggal pengajuan agar tanggal selesai tidak lebih awal dari tanggal mulai. |
| FR-07 | Sistem harus menolak pengajuan cuti apabila durasi melebihi batas maksimum yang berlaku untuk jenis cuti tersebut. |
| FR-08 | Sistem harus menolak pengajuan cuti apabila periode cuti bertabrakan dengan hari libur nasional. |
| FR-09 | Sistem harus menolak pengajuan cuti tahunan apabila masa kerja pegawai belum memenuhi syarat yang ditentukan sistem. |
| FR-10 | Sistem harus memungkinkan pegawai mengunggah lampiran untuk jenis cuti yang membutuhkan dokumen pendukung. |
| FR-11 | Sistem harus menyimpan pengajuan cuti dengan status awal **PENDING**. |
| FR-12 | Sistem harus memungkinkan pegawai melihat riwayat seluruh pengajuan cuti miliknya. |
| FR-13 | Sistem harus memungkinkan pegawai melihat detail pengajuan cuti, termasuk status, durasi, dan catatan keputusan jika tersedia. |
| FR-14 | Sistem harus memungkinkan pegawai membatalkan pengajuan cuti yang masih aktif sesuai aturan waktu pembatalan. |
| FR-15 | Sistem harus menolak pembatalan apabila cuti telah melewati batas waktu yang diizinkan atau sudah berstatus tidak aktif. |

### 2.2 Kebutuhan Fungsional untuk Atasan

| ID | Kebutuhan |
|---|---|
| FR-16 | Sistem harus memungkinkan atasan login menggunakan akun yang telah terdaftar. |
| FR-17 | Sistem harus menampilkan daftar pengajuan cuti yang memerlukan rekomendasi dari atasan. |
| FR-18 | Sistem harus menampilkan detail pengajuan cuti sebelum atasan memberikan keputusan. |
| FR-19 | Sistem harus memungkinkan atasan memberikan rekomendasi terhadap pengajuan cuti bawahan. |
| FR-20 | Sistem harus memungkinkan atasan menolak pengajuan cuti dengan menyertakan catatan penolakan. |
| FR-21 | Sistem harus menyimpan identitas atasan yang memberikan rekomendasi pada data pengajuan cuti. |
| FR-22 | Sistem harus memperbarui status pengajuan menjadi **RECOMMENDED** setelah rekomendasi diberikan. |
| FR-23 | Sistem harus menolak akses atasan terhadap data atau fitur yang hanya diperuntukkan bagi HRD/admin, apabila tidak memiliki otorisasi yang sesuai. |

### 2.3 Kebutuhan Fungsional untuk Admin / HRD

| ID | Kebutuhan |
|---|---|
| FR-24 | Sistem harus memungkinkan admin/HRD login menggunakan akun yang sah. |
| FR-25 | Sistem harus menampilkan pengajuan cuti yang telah direkomendasikan dan menunggu persetujuan akhir. |
| FR-26 | Sistem harus memungkinkan admin/HRD menyetujui pengajuan cuti yang telah direkomendasikan. |
| FR-27 | Sistem harus memungkinkan admin/HRD menolak pengajuan cuti dengan catatan penolakan. |
| FR-28 | Sistem harus memperbarui status pengajuan menjadi **APPROVED** setelah disetujui. |
| FR-29 | Sistem harus memperbarui status pengajuan menjadi **REJECTED** setelah ditolak. |
| FR-30 | Sistem harus memungkinkan admin/HRD melihat laporan cuti berdasarkan periode tanggal dan departemen. |
| FR-31 | Sistem harus memungkinkan admin/HRD mengekspor laporan cuti dalam format PDF. |
| FR-32 | Sistem harus memungkinkan admin/HRD mengelola data karyawan, termasuk tambah, ubah, dan hapus data. |
| FR-33 | Sistem harus memungkinkan admin/HRD melihat struktur organisasi berdasarkan relasi manager-bawahan. |
| FR-34 | Sistem harus menyediakan daftar manager yang dapat dipilih saat pengelolaan data karyawan. |
| FR-35 | Sistem harus menolak akses ke modul laporan dan data karyawan apabila pengguna tidak memiliki role HRD. |

### 2.4 Kebutuhan Fungsional Umum Sistem

| ID | Kebutuhan |
|---|---|
| FR-36 | Sistem harus menyediakan logout untuk mengakhiri sesi pengguna. |
| FR-37 | Sistem harus menyediakan endpoint untuk mengambil data profil pengguna yang sedang login. |
| FR-38 | Sistem harus menyimpan dan menampilkan data hari libur nasional untuk kebutuhan validasi dan dashboard. |
| FR-39 | Sistem harus menghitung sisa cuti berdasarkan cuti yang telah disetujui dan aturan kuota yang berlaku. |
| FR-40 | Sistem harus menggunakan status pengajuan yang konsisten sepanjang alur proses bisnis. |

## 3. Kebutuhan Non-Fungsional

Bagian ini mendeskripsikan karakteristik kualitas sistem yang harus dipenuhi agar aplikasi layak digunakan secara operasional.

### 3.1 Performa

| ID | Kebutuhan |
|---|---|
| NFR-01 | Sistem harus memberikan respons API dalam waktu yang wajar untuk operasi umum seperti login, pemuatan dashboard, dan pengambilan data form. |
| NFR-02 | Sistem harus mampu memuat data tabel pengajuan dan data karyawan secara bertahap agar tidak membebani antarmuka pengguna. |
| NFR-03 | Sistem harus mampu menangani proses export laporan PDF tanpa mengganggu alur utama aplikasi. |
| NFR-04 | Sistem harus menggunakan pagination pada daftar data yang berpotensi besar, seperti data karyawan. |

### 3.2 Keamanan

| ID | Kebutuhan |
|---|---|
| NFR-05 | Sistem harus menggunakan autentikasi token berbasis Laravel Sanctum. |
| NFR-06 | Sistem harus mengirim token autentikasi melalui header `Authorization: Bearer` pada setiap request terproteksi. |
| NFR-07 | Sistem harus membatasi akses endpoint berdasarkan role pengguna. |
| NFR-08 | Sistem harus menolak request tanpa token yang valid. |
| NFR-09 | Sistem harus menyimpan password dalam bentuk ter-hash, bukan teks asli. |
| NFR-10 | Sistem harus memvalidasi semua input penting di sisi server, termasuk tanggal, durasi, role, dan referensi foreign key. |
| NFR-11 | Sistem harus mencegah pengguna mengakses atau memodifikasi data yang bukan miliknya, terutama pada fitur pembatalan pengajuan cuti. |
| NFR-12 | Sistem harus menjaga agar data lampiran cuti hanya dapat diakses melalui mekanisme penyimpanan yang aman dan terkontrol. |

### 3.3 UI/UX

| ID | Kebutuhan |
|---|---|
| NFR-13 | Antarmuka sistem harus responsif dan dapat digunakan dengan baik pada layar desktop. |
| NFR-14 | Sistem harus memiliki navigasi yang jelas berdasarkan role pengguna. |
| NFR-15 | Sistem harus memberikan umpan balik visual yang jelas ketika proses login, submit cuti, atau approval sedang berlangsung. |
| NFR-16 | Sistem harus menampilkan pesan validasi yang mudah dipahami ketika input tidak sesuai aturan. |
| NFR-17 | Sistem harus memisahkan tampilan data, modal detail, dan form agar interaksi pengguna lebih terstruktur. |
| NFR-18 | Sistem harus menyajikan informasi penting seperti sisa cuti, status pengajuan, dan rekomendasi secara ringkas dan mudah dibaca. |
| NFR-19 | Sistem harus mempertahankan konsistensi layout antar halaman utama agar pengguna tidak mengalami perubahan pola navigasi yang membingungkan. |

### 3.4 Maintainability dan Keterawatan

| ID | Kebutuhan |
|---|---|
| NFR-20 | Sistem harus menggunakan pemisahan yang jelas antara komponen frontend, controller backend, dan model data. |
| NFR-21 | Sistem harus mempertahankan penamaan route dan status proses yang konsisten. |
| NFR-22 | Sistem harus memungkinkan pengembangan fitur lanjutan tanpa memerlukan perubahan besar pada arsitektur dasar. |

### 3.5 Reliability

| ID | Kebutuhan |
|---|---|
| NFR-23 | Sistem harus menolak data yang tidak valid secara deterministik melalui validasi backend. |
| NFR-24 | Sistem harus menjaga konsistensi status pengajuan meskipun pengguna melakukan refresh atau login ulang. |
| NFR-25 | Sistem harus tetap dapat menampilkan data riwayat, dashboard, dan laporan selama backend dan storage tersedia. |

## 4. Catatan Implementasi yang Relevan terhadap SRS

Beberapa kebutuhan di atas memiliki dasar implementasi langsung pada codebase yang dianalisis, antara lain:

- autentikasi token menggunakan Laravel Sanctum,
- role-based access control di controller backend,
- validasi tanggal dan kuota pada pengajuan cuti,
- alur persetujuan bertahap dengan status `PENDING`, `RECOMMENDED`, `APPROVED`, `REJECTED`, dan `CANCELLED`,
- pengelolaan data karyawan dan laporan oleh HRD,
- penggunaan Vue Router dan Axios pada frontend.

## 5. Prioritas Kebutuhan

Untuk kebutuhan pengembangan lanjutan, prioritas dapat dibagi sebagai berikut:

| Prioritas | Kebutuhan |
|---|---|
| Tinggi | Login, pengajuan cuti, validasi server-side, rekomendasi, approval, dan pembatalan pengajuan. |
| Sedang | Laporan PDF, manajemen data karyawan, dan struktur organisasi. |
| Rendah | Penyempurnaan UI/UX lanjutan, optimasi performa, dan pengayaan informasi dashboard. |

## 6. Kesimpulan

Draf SRS ini menunjukkan bahwa sistem informasi cuti telah memiliki kebutuhan inti yang cukup jelas untuk mendukung proses operasional perusahaan. Kebutuhan fungsional telah dibagi menurut role agar mudah ditelusuri, sedangkan kebutuhan non-fungsional menekankan aspek performa, keamanan, UI/UX, maintainability, dan reliability yang sesuai dengan implementasi Laravel dan Vue.js pada codebase saat ini.
