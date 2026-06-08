# Tabel Skenario Use Case Sistem Informasi Cuti SC

Dokumen ini menyajikan tabel skenario use case yang lebih rinci untuk dua proses bisnis paling krusial pada aplikasi **sc**, yaitu proses pengajuan cuti oleh pegawai dan proses approval atau rejection cuti oleh atasan/admin.

## 1. Use Case: Pengajuan Cuti oleh Pegawai

### 1.1 Ringkasan Skenario

| Elemen | Keterangan |
|---|---|
| Nama Use Case | Pengajuan Cuti oleh Pegawai |
| Aktor | Pegawai |
| Tujuan | Pegawai mengajukan permohonan cuti ke sistem untuk diproses pada tahap rekomendasi dan persetujuan. |

### 1.2 Kondisi Awal dan Kondisi Akhir

| Elemen | Keterangan |
|---|---|
| Pre-condition | Pegawai sudah login ke sistem, memiliki akun aktif, dan dapat mengakses menu pengajuan cuti. |
| Post-condition | Permohonan cuti tersimpan dengan status awal PENDING dan siap diproses oleh atasan. |

### 1.3 Main Flow

| Langkah | Deskripsi |
|---|---|
| 1 | Pegawai membuka halaman atau menu pengajuan cuti. |
| 2 | Sistem menampilkan daftar jenis cuti, informasi sisa cuti, dan daftar hari libur nasional yang masih relevan. |
| 3 | Pegawai memilih jenis cuti yang akan diajukan. |
| 4 | Pegawai mengisi tanggal mulai, tanggal selesai, alasan pengajuan, dan lampiran apabila diperlukan. |
| 5 | Sistem melakukan validasi awal pada sisi antarmuka, seperti kelengkapan form, urutan tanggal, dan batas durasi cuti. |
| 6 | Jika data dianggap valid, pegawai menekan tombol submit. |
| 7 | Sistem mengirim data ke backend untuk divalidasi kembali secara server-side. |
| 8 | Backend memeriksa kesesuaian data terhadap aturan bisnis, termasuk kuota cuti, jenis cuti, masa kerja, dan konflik dengan hari libur. |
| 9 | Jika seluruh validasi berhasil, sistem menyimpan data pengajuan cuti. |
| 10 | Sistem menetapkan status pengajuan menjadi PENDING. |
| 11 | Sistem menampilkan notifikasi bahwa pengajuan berhasil dikirim. |

### 1.4 Alternative Flow / Exception Flow

| Kode | Kondisi | Respons Sistem |
|---|---|---|
| AF-01 | Form pengajuan belum lengkap | Sistem menolak submit dan menampilkan pesan bahwa seluruh field wajib harus diisi. |
| AF-02 | Tanggal selesai lebih awal dari tanggal mulai | Sistem menolak pengajuan dan menampilkan informasi bahwa urutan tanggal tidak valid. |
| AF-03 | Durasi cuti melebihi batas maksimum jenis cuti | Sistem menolak pengajuan dan menampilkan pesan bahwa durasi melebihi batas maksimal. |
| AF-04 | Sisa cuti tahunan tidak mencukupi | Sistem menolak pengajuan dan memberi tahu pegawai bahwa kuota cuti tidak cukup. |
| AF-05 | Tanggal cuti bertabrakan dengan hari libur nasional | Sistem menolak pengajuan dan menampilkan informasi konflik dengan tanggal merah. |
| AF-06 | Pegawai belum memenuhi masa kerja minimum untuk cuti tahunan | Sistem menolak pengajuan cuti tahunan dan menampilkan pesan bahwa syarat masa kerja belum terpenuhi. |
| AF-07 | Lampiran wajib tidak diunggah untuk jenis cuti tertentu | Sistem menolak submit dan meminta pegawai melengkapi lampiran. |
| AF-08 | Koneksi ke backend gagal saat submit | Sistem menampilkan pesan gagal mengirim data dan permohonan tidak tersimpan. |

## 2. Use Case: Approval atau Rejection Cuti oleh Atasan / Admin

### 2.1 Ringkasan Skenario

| Elemen | Keterangan |
|---|---|
| Nama Use Case | Approval atau Rejection Cuti oleh Atasan / Admin |
| Aktor | Atasan, Admin, HRD |
| Tujuan | Memproses permohonan cuti yang masuk melalui mekanisme rekomendasi atau persetujuan akhir. |

### 2.2 Kondisi Awal dan Kondisi Akhir

| Elemen | Keterangan |
|---|---|
| Pre-condition | Pengguna sudah login sebagai atasan, admin, atau HRD, dan memiliki hak akses ke menu persetujuan. |
| Post-condition | Status pengajuan berubah menjadi RECOMMENDED, APPROVED, atau REJECTED sesuai keputusan yang diambil. |

### 2.3 Main Flow

| Langkah | Deskripsi |
|---|---|
| 1 | Atasan atau admin membuka halaman persetujuan cuti. |
| 2 | Sistem menampilkan daftar pengajuan cuti yang menunggu proses sesuai role pengguna. |
| 3 | Pengguna memilih salah satu pengajuan untuk melihat detailnya. |
| 4 | Sistem menampilkan informasi lengkap seperti data pemohon, jenis cuti, tanggal cuti, alasan, lampiran, dan status saat ini. |
| 5 | Pengguna meninjau data pengajuan berdasarkan kebutuhan operasional dan aturan bisnis. |
| 6 | Jika pengajuan dinilai layak, atasan memberikan rekomendasi atau admin memberikan approval akhir. |
| 7 | Jika pengajuan tidak layak, pengguna memilih penolakan dan menambahkan catatan penolakan. |
| 8 | Sistem mengirim keputusan ke backend. |
| 9 | Backend memperbarui status pengajuan sesuai tindakan pengguna. |
| 10 | Sistem menampilkan notifikasi bahwa proses persetujuan atau penolakan telah berhasil dilakukan. |

### 2.4 Alternative Flow / Exception Flow

| Kode | Kondisi | Respons Sistem |
|---|---|---|
| AF-01 | Pengguna tidak memiliki role yang berwenang | Sistem menolak akses ke halaman persetujuan dengan status forbidden. |
| AF-02 | Tidak ada pengajuan cuti yang menunggu proses | Sistem menampilkan pesan bahwa data pengajuan belum tersedia. |
| AF-03 | Pengguna menekan approve atau recommend tanpa membuka detail pengajuan yang valid | Sistem menolak aksi karena data target tidak ditemukan atau tidak aktif. |
| AF-04 | Catatan penolakan belum diisi saat pengguna memilih reject | Sistem menolak submit dan meminta pengguna mengisi alasan penolakan. |
| AF-05 | Backend gagal memperbarui status pengajuan | Sistem menampilkan pesan gagal memproses data dan status tetap tidak berubah. |
| AF-06 | Pengajuan sudah berada pada status akhir sebelum diproses | Sistem menolak perubahan karena request sudah tidak aktif. |
| AF-07 | Data pengajuan tidak sesuai dengan hak proses pengguna saat ini | Sistem menolak aksi, misalnya request belum direkomendasikan tetapi dicoba langsung di-approve. |

## 3. Catatan Analisis

- Proses pengajuan cuti menekankan validasi berlapis, yaitu validasi pada frontend untuk pengalaman pengguna dan validasi pada backend untuk menjaga integritas aturan bisnis.
- Proses approval atau rejection bersifat bertahap dan bergantung pada role pengguna, sehingga sistem harus konsisten dalam mengecek hak akses sebelum mengubah status request.
- Status pengajuan yang digunakan dalam alur bisnis meliputi PENDING, RECOMMENDED, APPROVED, REJECTED, dan CANCELLED.
